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

class PluginRoundRobinTicketHookHandler extends CommonDBTM implements IPluginRoundRobinHookItemHandler {

    protected $DB;
    protected $rrAssignmentsEntity;

    public function __construct() {
        global $DB;

        $this->DB = $DB;
        $this->rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
    }

    public function itemAdded(CommonDBTM $item) {
        PluginRoundRobinLogger::addWarning(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'Ticket') {
            return;
        }
        PluginRoundRobinLogger::addWarning(__METHOD__ . " - TicketId: " . $this->getTicketId($item));
        PluginRoundRobinLogger::addWarning(__METHOD__ . " - CategoryId: " . $this->getTicketCategory($item));
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
        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - result array: ', $resultArray);
        return $resultArray;
    }

    protected function assignTicket(CommonDBTM $item) {
        $itilcategoriesId = $this->getTicketCategory($item);
        if (($lastAssignmentIndex = $this->getLastAssignmentIndex($item)) === false) {
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - nothing to to (category is disabled or not configured; getLastAssignmentIndex: ' . $lastAssignmentIndex);
            return;
        }
        $categoryGroupMembers = $this->getGroupsUsersByCategory($this->getTicketCategory($item));
        $newAssignmentIndex = isset($lastAssignmentIndex) ? $lastAssignmentIndex + 1 : 0;
        /**
         * round robin
         */
        if ($newAssignmentIndex > (count($categoryGroupMembers) - 1)) {
            $newAssignmentIndex = $newAssignmentIndex % count($categoryGroupMembers);
            if ($newAssignmentIndex > (count($categoryGroupMembers) - 1)) {
                $newAssignmentIndex = 0;
            }
        }
        $this->rrAssignmentsEntity->updateLastAssignmentIndex($itilcategoriesId, $newAssignmentIndex);

        /**
         * set the assignment
         */
        $ticketId = $this->getTicketId($item);
        $userId = $categoryGroupMembers[$newAssignmentIndex]['UserId'];
        $this->setAssignment($ticketId, $userId, $itilcategoriesId);
        return $userId;
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
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlDelete_glpi_tickets_users: ' . $sqlDelete_glpi_tickets_users);
        $this->DB->queryOrDie($sqlDelete_glpi_tickets_users, $this->DB->error());

        /**
         * remove any previous group assignment
         */
        $sqlDelete_glpi_groups_tickets = <<< EOT
            DELETE FROM glpi_groups_tickets 
            WHERE tickets_id = {$ticketId};
EOT;

        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlDelete_glpi_groups_tickets: ' . $sqlDelete_glpi_groups_tickets);
        $this->DB->queryOrDie($sqlDelete_glpi_groups_tickets, $this->DB->error());

        /**
         * insert the new assignment, based on rr
         */
        $sqlInsert_glpi_tickets_users = <<< EOT
                    INSERT INTO glpi_tickets_users (tickets_id, users_id, type, use_notification) VALUES ({$ticketId}, {$userId}, 2, 0)
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlInsert_glpi_tickets_users: ' . $sqlInsert_glpi_tickets_users);
        $this->DB->queryOrDie($sqlInsert_glpi_tickets_users, $this->DB->error());

        /**
         * if auto group assign is enabled assign the group too
         */
        if ($this->rrAssignmentsEntity->getOptionAutoAssignGroup() === 1) {
            $groups_id = $this->rrAssignmentsEntity->getGroupByItilCategory($itilcategoriesId);
            $sqlInsert_glpi_tickets_groups = <<< EOT
                    INSERT INTO glpi_groups_tickets (tickets_id, groups_id, type) VALUES ({$ticketId}, {$groups_id}, 2)
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlInsert_glpi_tickets_groups: ' . $sqlInsert_glpi_tickets_groups);
            $this->DB->queryOrDie($sqlInsert_glpi_tickets_groups, $this->DB->error());
        }
    }

    public function itemPurged(CommonDBTM $item) {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - nothing to do');
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - nothing to do');
    }

}
