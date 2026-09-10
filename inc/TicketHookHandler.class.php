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
require_once 'IHookItemHandler.php';
require_once __DIR__ . '/rotation.class.php';

class PluginRoundRobinTicketHookHandler extends CommonDBTM implements IPluginRoundRobinHookItemHandler {

    protected $DB;
    protected $rrAssignmentsEntity;

    public function __construct() {
        global $DB;

        $this->DB = $DB;
        $this->rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
    }

    public function itemAdded(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'Ticket') {
            return;
        }
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - TicketId: " . $this->getTicketId($item));
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - CategoryId: " . $this->getTicketCategory($item));
        $this->assignTicket($item);
    }

    protected function getTicketId(CommonDBTM $item) {
        return $item->fields['id'];
    }

    protected function getTicketCategory(CommonDBTM $item) {
        return $item->fields['itilcategories_id'];
    }

    public function getGroupsUsersByCategory($categoryId) {
        $sql = <<< EOT
                SELECT 
                    c.name AS Category,
                    c.completename AS CategoryCompleteName,
                    g.name AS 'Group',
                    gu.id AS UserGroupId,
                    gu.users_id AS UserId,
                    u.name AS Username,
                    u.firstname AS UserFirstname,
                    u.realname AS UserRealname
                FROM
                    glpi_itilcategories c
                        JOIN
                    glpi_groups g ON c.groups_id = g.id
                        JOIN
                    glpi_groups_users gu ON gu.groups_id = g.id
                        JOIN
                    glpi_users u ON gu.users_id = u.id
                WHERE
                    c.id = {$categoryId}
                ORDER BY gu.id ASC
EOT;
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - result array: ', $resultArray);
        return $resultArray;
    }

    protected function assignTicket(CommonDBTM $item) {
        $itilcategoriesId = (int) $this->getTicketCategory($item);
        $userId = $this->findUserIdToAssign($itilcategoriesId, true);
        if ($userId === null) {
            return null;
        }
        $this->setAssignment($this->getTicketId($item), $userId, $itilcategoriesId);
        return $userId;
    }

    /**
     * Let other plugins filter the members eligible for assignment.
     * Hook: $PLUGIN_HOOKS['roundrobin_filter_members'][<plugin>] = callable(array $params): array
     * $params = ['members' => rows from getGroupsUsersByCategory(), 'itilcategories_id' => int, 'groups_id' => int]
     *
     * @return array filtered member rows (same shape as getGroupsUsersByCategory)
     */
    public function applyMemberFilters(array $members, int $categoryId, int $groupId): array {
        global $PLUGIN_HOOKS;

        $params = ['members' => $members, 'itilcategories_id' => $categoryId, 'groups_id' => $groupId];
        if (!isset($PLUGIN_HOOKS['roundrobin_filter_members']) || !is_array($PLUGIN_HOOKS['roundrobin_filter_members'])) {
            return $members;
        }
        foreach ($PLUGIN_HOOKS['roundrobin_filter_members'] as $pluginKey => $callable) {
            if (!Plugin::isPluginActive($pluginKey)) {
                continue;
            }
            // The callback usually lives in the plugin's hook.php, which GLPI only includes on demand
            // (same as Plugin::doHookFunction()). Without this, CLI/cron ticket creation would skip the filter.
            Plugin::includeHook($pluginKey);
            if (!is_callable($callable)) {
                PluginRoundRobinLogger::addWarning(__METHOD__ . ' - filter from plugin ' . $pluginKey . ' is not callable, ignored');
                continue;
            }
            try {
                $result = call_user_func($callable, $params);
                if (is_array($result) && isset($result['members']) && is_array($result['members'])) {
                    $params['members'] = $result['members'];
                }
            } catch (\Throwable $e) {
                PluginRoundRobinLogger::addError(__METHOD__ . ' - filter from plugin ' . $pluginKey . ' failed: ' . $e->getMessage());
            }
        }
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - eligible members after filters: ' . count($params['members']) . '/' . count($members));
        return $params['members'];
    }

    /**
     * Choose the next technician for a category. The rotation index is kept over the full
     * member list and ineligible members (see applyMemberFilters) are skipped.
     *
     * @return int|null user id, or null when the category is disabled or nobody is eligible
     */
    public function findUserIdToAssign(int $itilcategoriesId, bool $storeChoice = true) {
        if (($lastAssignmentIndex = $this->getLastAssignmentIndexId($itilcategoriesId)) === false) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do (category is disabled or not configured)');
            return null;
        }
        $categoryGroupMembers = $this->getGroupsUsersByCategory($itilcategoriesId);
        if (count($categoryGroupMembers) === 0) {
            /**
             * category w/o group, or group w/o users
             */
            return null;
        }

        $groupId     = (int) ($this->rrAssignmentsEntity->getGroupByItilCategory($itilcategoriesId) ?: 0);
        $eligible    = $this->applyMemberFilters($categoryGroupMembers, $itilcategoriesId, $groupId);
        $eligibleIds = array_map(static function (array $m): int {
            return (int) $m['UserId'];
        }, $eligible);

        $last = ($lastAssignmentIndex === null || $lastAssignmentIndex === '') ? null : (int) $lastAssignmentIndex;
        $newAssignmentIndex = PluginRoundRobinRotation::pickNextIndex($last, $categoryGroupMembers, $eligibleIds);
        if ($newAssignmentIndex === null) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - no eligible member for category ' . $itilcategoriesId . ', ticket left unassigned');
            return null;
        }

        if ($storeChoice) {
            $this->rrAssignmentsEntity->updateLastAssignmentIndex($itilcategoriesId, $newAssignmentIndex);
        }

        return (int) $categoryGroupMembers[$newAssignmentIndex]['UserId'];
    }

    protected function getLastAssignmentIndexId(int $categoryId) {
        return $this->rrAssignmentsEntity->getLastAssignmentIndex($categoryId);
    }


    protected function getLastAssignmentIndex(CommonDBTM $item) {
        $itilcategoriesId = $this->getTicketCategory($item);
        return $this->rrAssignmentsEntity->getLastAssignmentIndex($itilcategoriesId);
    }

    protected function setAssignment($ticketId, $userId, $itilcategoriesId) {
        /**
         * remove any prevous user assignment
         */
        $sqlDelete_glpi_tickets_users = <<< EOT
            DELETE FROM glpi_tickets_users 
            WHERE tickets_id = {$ticketId};
EOT;
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlDelete_glpi_tickets_users: ' . $sqlDelete_glpi_tickets_users);
        $this->DB->queryOrDie($sqlDelete_glpi_tickets_users, $this->DB->error());

        /**
         * remove any previous group assignment
         */
        $sqlDelete_glpi_groups_tickets = <<< EOT
            DELETE FROM glpi_groups_tickets 
            WHERE tickets_id = {$ticketId};
EOT;

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlDelete_glpi_groups_tickets: ' . $sqlDelete_glpi_groups_tickets);
        $this->DB->queryOrDie($sqlDelete_glpi_groups_tickets, $this->DB->error());

        /**
         * insert the new assignment, based on rr
         */
        $sqlInsert_glpi_tickets_users = <<< EOT
                    INSERT INTO glpi_tickets_users (tickets_id, users_id, type, use_notification) VALUES ({$ticketId}, {$userId}, 2, 0)
EOT;
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlInsert_glpi_tickets_users: ' . $sqlInsert_glpi_tickets_users);
        $this->DB->queryOrDie($sqlInsert_glpi_tickets_users, $this->DB->error());

        /**
         * if auto group assign is enabled assign the group too
         */
        if ($this->rrAssignmentsEntity->getOptionAutoAssignGroup() === 1) {
            $groups_id = $this->rrAssignmentsEntity->getGroupByItilCategory($itilcategoriesId);
            $sqlInsert_glpi_tickets_groups = <<< EOT
                    INSERT INTO glpi_groups_tickets (tickets_id, groups_id, type) VALUES ({$ticketId}, {$groups_id}, 2)
EOT;
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlInsert_glpi_tickets_groups: ' . $sqlInsert_glpi_tickets_groups);
            $this->DB->queryOrDie($sqlInsert_glpi_tickets_groups, $this->DB->error());
        }
    }

    public function itemPurged(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }

}
