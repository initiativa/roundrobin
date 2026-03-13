<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of RoundRobin GLPI Plugin.
 *
 * RoundRobin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * RoundRobin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with RoundRobin. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 by initiativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/initiativa/roundrobin
 * -------------------------------------------------------------------------
 */

// Dependencies are loaded by hook.php

class PluginRoundRobinTicketHookHandler extends CommonDBTM implements IPluginRoundRobinHookItemHandler {

    protected $DB;
    protected $rrAssignmentsEntity;

    public function __construct() {
        global $DB;

        $this->DB                  = $DB;
        $this->rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
    }

    public function itemAdded(CommonDBTM $item) {
        // In GLPI 11, assignment is done via _actors in pre_item_add.
        // This method is intentionally a no-op.
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - assignment already handled in pre_item_add');
    }

    protected function getTicketId(CommonDBTM $item) {
        return $item->fields['id'];
    }

    protected function getTicketCategory(CommonDBTM $item) {
        return $item->fields['itilcategories_id'];
    }

    /**
     * Return active members of the group linked to a category.
     * Ordered by glpi_groups_users.id ASC for a stable, predictable rotation.
     *
     * @param  int   $categoryId
     * @return array Each row has UserId, Username, UserFirstname, UserRealname,
     *               UserGroupId, Category, CategoryCompleteName, Group.
     */
    public function getGroupsUsersByCategory(int $categoryId): array {
        // Resolve category → group in one query
        $catResult = $this->DB->request([
            'SELECT' => ['id', 'name', 'completename', 'groups_id'],
            'FROM'   => 'glpi_itilcategories',
            'WHERE'  => ['id' => $categoryId],
            'LIMIT'  => 1,
        ]);

        $catData = $catResult->current();
        if (!$catData || empty($catData['groups_id'])) {
            PluginRoundRobinLogger::addDebug(__METHOD__ . " - category {$categoryId} has no group");
            return [];
        }

        $groupId = (int) $catData['groups_id'];

        $grpResult = $this->DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_groups',
            'WHERE'  => ['id' => $groupId],
            'LIMIT'  => 1,
        ]);

        $grpData = $grpResult->current();
        if (!$grpData) {
            PluginRoundRobinLogger::addDebug(__METHOD__ . " - group {$groupId} not found");
            return [];
        }

        // Active, non-deleted users in the group
        $usersResult = $this->DB->request([
            'SELECT' => [
                'gu.id AS UserGroupId',
                'gu.users_id AS UserId',
                'u.name AS Username',
                'u.firstname AS UserFirstname',
                'u.realname AS UserRealname',
            ],
            'FROM'       => 'glpi_groups_users AS gu',
            'INNER JOIN' => [
                'glpi_users AS u' => [
                    'ON' => ['gu' => 'users_id', 'u' => 'id'],
                ],
            ],
            'WHERE' => [
                'gu.groups_id' => $groupId,
                'u.is_active'  => 1,
                'u.is_deleted' => 0,
            ],
            'ORDER' => 'gu.id ASC',
        ]);

        $members = [];
        foreach ($usersResult as $row) {
            $row['Category']             = $catData['name'];
            $row['CategoryCompleteName'] = $catData['completename'];
            $row['Group']                = $grpData['name'];
            $members[]                   = $row;
        }

        PluginRoundRobinLogger::addDebug(__METHOD__ . " - found " . count($members) . " active members for group {$groupId}");
        return $members;
    }

    /**
     * Compute the next user to assign for a category using group-level rotation.
     *
     * All categories that share the same group advance the SAME counter, so
     * distribution is fair regardless of which category the ticket arrives on.
     *
     * @param  int   $itilcategoriesId
     * @param  bool  $storeChoice  Persist the new index (false = dry-run / preview)
     * @return array|null  ['user_id' => int, 'group_id' => int|false] or null
     */
    public function findUserIdToAssign(int $itilcategoriesId, bool $storeChoice = true): ?array {
        // 1. Category must be active for RR
        if (!$this->rrAssignmentsEntity->isActiveForCategory($itilcategoriesId)) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - category {$itilcategoriesId} is not active for RR");
            return null;
        }

        // 2. Resolve the group for this category
        $groupId = $this->rrAssignmentsEntity->getGroupByItilCategory($itilcategoriesId);
        if ($groupId === false) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - category {$itilcategoriesId} has no group");
            return null;
        }

        // 3. Get active members (ordered by gu.id for stable rotation)
        $members = $this->getGroupsUsersByCategory($itilcategoriesId);
        if (empty($members)) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - no active members in group {$groupId}");
            return null;
        }

        $total = count($members);

        // 4. Advance the shared group counter (round-robin)
        $lastIndex = $this->rrAssignmentsEntity->getLastAssignmentIndexByGroup($groupId);
        // NULL means "never assigned yet" → start at 0
        $nextIndex = ($lastIndex === null || $lastIndex === false)
            ? 0
            : (((int) $lastIndex + 1) % $total);

        // 5. Persist
        if ($storeChoice) {
            $this->rrAssignmentsEntity->updateLastAssignmentIndexByGroup($groupId, $nextIndex);
        }

        $userId = (int) $members[$nextIndex]['UserId'];

        // 6. Resolve group assignment preference
        $assignGroupId = null;
        if ($this->rrAssignmentsEntity->getOptionAutoAssignGroup() === 1) {
            $assignGroupId = $groupId;
        }

        PluginRoundRobinLogger::addDebug(
            __FUNCTION__ . " - cat={$itilcategoriesId} group={$groupId}"
            . " members={$total} index={$nextIndex} user={$userId}"
        );

        return [
            'user_id'  => $userId,
            'group_id' => $assignGroupId,
        ];
    }

    // -------------------------------------------------------------------------
    // IPluginRoundRobinHookItemHandler contract
    // -------------------------------------------------------------------------

    public function itemPurged(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }
}
