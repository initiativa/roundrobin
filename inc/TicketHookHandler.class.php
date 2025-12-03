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
        // In GLPI 11, assignment is done via _actors in pre_item_add hook
        // This method is kept for compatibility but assignment already happened
    }

    protected function getTicketId(CommonDBTM $item) {
        return $item->fields['id'];
    }

    protected function getTicketCategory(CommonDBTM $item) {
        return $item->fields['itilcategories_id'];
    }

    /**
     * Get group members for a category - GLPI 11 compatible using DB->request()
     */
    public function getGroupsUsersByCategory($categoryId) {
        // Get category group
        $categoryResult = $this->DB->request([
            'SELECT' => ['c.id', 'c.name', 'c.completename', 'c.groups_id'],
            'FROM' => 'glpi_itilcategories AS c',
            'WHERE' => ['c.id' => (int)$categoryId]
        ]);
        
        $categoryData = $categoryResult->current();
        if (!$categoryData || empty($categoryData['groups_id'])) {
            return [];
        }
        
        $groupId = $categoryData['groups_id'];
        
        // Get group info
        $groupResult = $this->DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => 'glpi_groups',
            'WHERE' => ['id' => (int)$groupId]
        ]);
        
        $groupData = $groupResult->current();
        if (!$groupData) {
            return [];
        }
        
        // Get group users - filter only active users
        $usersResult = $this->DB->request([
            'SELECT' => [
                'gu.id AS UserGroupId',
                'gu.users_id AS UserId',
                'u.name AS Username',
                'u.firstname AS UserFirstname',
                'u.realname AS UserRealname'
            ],
            'FROM' => 'glpi_groups_users AS gu',
            'INNER JOIN' => [
                'glpi_users AS u' => [
                    'ON' => [
                        'gu' => 'users_id',
                        'u' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'gu.groups_id' => (int)$groupId,
                'u.is_active' => 1,
                'u.is_deleted' => 0
            ],
            'ORDER' => 'gu.id ASC'
        ]);
        
        $resultArray = [];
        foreach ($usersResult as $row) {
            $row['Category'] = $categoryData['name'];
            $row['CategoryCompleteName'] = $categoryData['completename'];
            $row['Group'] = $groupData['name'];
            $resultArray[] = $row;
        }
        
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - result array: ', $resultArray);
        return $resultArray;
    }

    /**
     * Find user to assign for a category - returns array with user_id and optionally group_id
     * GLPI 11 compatible - returns data for _actors array
     * 
     * @param int $itilcategoriesId
     * @param bool $storeChoice Whether to update the last assignment index
     * @return array|null Array with 'user_id' and 'group_id' keys, or null if no assignment
     */
    public function findUserIdToAssign(int $itilcategoriesId, bool $storeChoice = true) {
        if (($lastAssignmentIndex = $this->getLastAssignmentIndexId($itilcategoriesId)) === false) {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do (category is disabled or not configured; getLastAssignmentIndex: ' . var_export($lastAssignmentIndex, true));
            return null;
        }
        
        $categoryGroupMembers = $this->getGroupsUsersByCategory($itilcategoriesId);
        if (count($categoryGroupMembers) === 0) {
            /**
             * category w/o group, or group w/o users
             */
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - no group members found for category: ' . $itilcategoriesId);
            return null;
        }
        
        $newAssignmentIndex = isset($lastAssignmentIndex) && $lastAssignmentIndex !== null ? $lastAssignmentIndex + 1 : 0;
        /**
         * round robin
         */
        if ($newAssignmentIndex > (count($categoryGroupMembers) - 1)) {
            $newAssignmentIndex = $newAssignmentIndex % count($categoryGroupMembers);
            if ($newAssignmentIndex > (count($categoryGroupMembers) - 1)) {
                $newAssignmentIndex = 0;
            }
        }

        if ($storeChoice) {
            $this->rrAssignmentsEntity->updateLastAssignmentIndex($itilcategoriesId, $newAssignmentIndex);
        }

        $userId = $categoryGroupMembers[$newAssignmentIndex]['UserId'];
        
        // Check if group should also be assigned
        $groupId = null;
        if ($this->rrAssignmentsEntity->getOptionAutoAssignGroup() === 1) {
            $groupId = $this->rrAssignmentsEntity->getGroupByItilCategory($itilcategoriesId);
        }
        
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - assigned user_id: ' . $userId . ', group_id: ' . var_export($groupId, true));
        
        return [
            'user_id' => $userId,
            'group_id' => $groupId
        ];
    }

    protected function getLastAssignmentIndexId(int $categoryId) {
        return $this->rrAssignmentsEntity->getLastAssignmentIndex($categoryId);
    }

    protected function getLastAssignmentIndex(CommonDBTM $item) {
        $itilcategoriesId = $this->getTicketCategory($item);
        return $this->rrAssignmentsEntity->getLastAssignmentIndex($itilcategoriesId);
    }

    public function itemPurged(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - nothing to do');
    }

}
