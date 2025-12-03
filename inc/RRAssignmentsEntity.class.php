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

// Include dependencies from same directory using __DIR__
require_once __DIR__ . '/config.class.php';
require_once __DIR__ . '/logger.class.php';

class PluginRoundRobinRRAssignmentsEntity extends CommonDBTM {

    protected $DB;
    protected $rrAssignmentTable;
    protected $rrOptionsTable;

    public function __construct() {
        global $DB;

        $this->DB = $DB;
        $this->rrAssignmentTable = PluginRoundRobinConfig::getRrAssignmentTable();
        $this->rrOptionsTable = PluginRoundRobinConfig::getRrOptionsTable();
    }

    public function init() {
        $this->createTable();
        $this->truncateTable();
        $this->insertAllItilCategory();
        $this->insertOptions();
    }

    public function cleanUp() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        /**
         * drop settings
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $this->DB->doQueryOrDie("DROP TABLE `{$this->rrAssignmentTable}`", "Error dropping {$this->rrAssignmentTable}");
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped table: ' . $this->rrAssignmentTable);
        } else {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - table not dropped because it does not exist: " . $this->rrAssignmentTable);
        }

        /**
         * drop options
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $this->DB->doQueryOrDie("DROP TABLE `{$this->rrOptionsTable}`", "Error dropping {$this->rrOptionsTable}");
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped table: ' . $this->rrOptionsTable);
        } else {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - table not dropped because it does not exist: " . $this->rrOptionsTable);
        }
    }

    protected function createTable() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        /**
         * create setting table - GLPI 11 compatible
         */
        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrAssignmentTable}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `itilcategories_id` int unsigned NOT NULL,
                `is_active` tinyint NOT NULL DEFAULT 0,
                `last_assignment_index` int DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ix_itilcategories_uq` (`itilcategories_id`),
                KEY `ix_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
            
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlCreate: ' . $query);
            $this->DB->doQueryOrDie($query, "Error creating {$this->rrAssignmentTable}");
        }

        /**
         * create option table - GLPI 11 compatible
         */
        if (!$this->DB->tableExists($this->rrOptionsTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrOptionsTable}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `auto_assign_group` tinyint NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
            
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlCreate: ' . $query);
            $this->DB->doQueryOrDie($query, "Error creating {$this->rrOptionsTable}");
        }
    }

    protected function truncateTable() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        /**
         * truncate all settings
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $this->DB->doQueryOrDie("TRUNCATE TABLE `{$this->rrAssignmentTable}`", "Error truncating {$this->rrAssignmentTable}");
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - truncated: ' . $this->rrAssignmentTable);
        }

        /**
         * truncate all options
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $this->DB->doQueryOrDie("TRUNCATE TABLE `{$this->rrOptionsTable}`", "Error truncating {$this->rrOptionsTable}");
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - truncated: ' . $this->rrOptionsTable);
        }
    }

    protected function insertAllItilCategory() {
        // GLPI 11 compatible - use DB->request()
        $result = $this->DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_itilcategories'
        ]);
        
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserting all ITIL categories');
        foreach ($result as $itilCategory) {
            $this->insertItilCategory($itilCategory['id']);
        }
    }

    public function insertItilCategory($itilCategory) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered with category: ' . $itilCategory);

        // Check if already exists
        $exists = $this->DB->request([
            'FROM' => $this->rrAssignmentTable,
            'WHERE' => ['itilcategories_id' => (int)$itilCategory],
            'LIMIT' => 1
        ]);
        
        if (count($exists) === 0) {
            $this->DB->insert($this->rrAssignmentTable, [
                'itilcategories_id' => (int)$itilCategory,
                'is_active' => 0
            ]);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserted category: ' . $itilCategory);
        }
    }

    public function insertOptions() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        $this->DB->insert($this->rrOptionsTable, [
            'auto_assign_group' => 1
        ]);
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserted default options');
    }

    public function getOptionAutoAssignGroup() {
        $result = $this->DB->request([
            'FROM' => $this->rrOptionsTable,
            'LIMIT' => 1
        ]);
        
        if (count($result) > 0) {
            $row = $result->current();
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - auto_assign_group: ' . $row['auto_assign_group']);
            return (int)$row['auto_assign_group'];
        }
        return 1; // default
    }

    public function getGroupByItilCategory($itilCategory) {
        $result = $this->DB->request([
            'SELECT' => ['groups_id'],
            'FROM' => 'glpi_itilcategories',
            'WHERE' => ['id' => (int)$itilCategory],
            'LIMIT' => 1
        ]);
        
        if (count($result) > 0) {
            $row = $result->current();
            $groupsId = (int)$row['groups_id'];
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - groups_id: ' . $groupsId);
            return $groupsId !== 0 ? $groupsId : false;
        }
        return false;
    }

    public function updateAutoAssignGroup($autoAssignGroup) {
        $this->DB->update(
            $this->rrOptionsTable,
            ['auto_assign_group' => (int)$autoAssignGroup],
            ['id' => 1]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated auto_assign_group: ' . $autoAssignGroup);
    }

    public function deleteItilCategory($itilCategory) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered with category: ' . $itilCategory);

        $this->DB->delete(
            $this->rrAssignmentTable,
            ['itilcategories_id' => (int)$itilCategory]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - deleted category: ' . $itilCategory);
    }

    public function updateLastAssignmentIndex($itilcategoriesId, $index) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['last_assignment_index' => (int)$index],
            ['itilcategories_id' => (int)$itilcategoriesId]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated last_assignment_index: ' . $index . ' for category: ' . $itilcategoriesId);
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['is_active' => (int)$isActive],
            ['itilcategories_id' => (int)$itilcategoriesId]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated is_active: ' . $isActive . ' for category: ' . $itilcategoriesId);
    }

    public function getLastAssignmentIndex($itilcategoriesId) {
        $result = $this->DB->request([
            'SELECT' => ['last_assignment_index'],
            'FROM' => $this->rrAssignmentTable,
            'WHERE' => [
                'itilcategories_id' => (int)$itilcategoriesId,
                'is_active' => 1
            ],
            'LIMIT' => 1
        ]);
        
        $resultArray = iterator_to_array($result);
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - resultArray: ' . print_r($resultArray, true));
        
        if (count($resultArray) === 0 || count($resultArray) > 1) {
            /**
             * for the specified category behaviour is not required
             * or there are more than just one line for category
             */
            return false;
        } else {
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - category has entry');
            return $resultArray[0]['last_assignment_index'];
        }
    }

    /**
     * Get all assignments with category and group info
     * 
     * @return array of array (id, itilcategories_id, category_name, groups_id, group_name, num_group_members, is_active)
     */
    public function getAll() {
        // Get all assignments
        $assignments = $this->DB->request([
            'FROM' => $this->rrAssignmentTable,
            'ORDER' => 'id'
        ]);
        
        $resultArray = [];
        foreach ($assignments as $assignment) {
            $row = [
                'id' => (int)$assignment['id'],
                'itilcategories_id' => (int)$assignment['itilcategories_id'],
                'category_name' => '',
                'groups_id' => 0,
                'group_name' => null,
                'num_group_members' => 0,
                'is_active' => (int)$assignment['is_active']
            ];
            
            // Get category info
            $category = $this->DB->request([
                'FROM' => 'glpi_itilcategories',
                'WHERE' => ['id' => $assignment['itilcategories_id']],
                'LIMIT' => 1
            ]);
            
            if (count($category) > 0) {
                $cat = $category->current();
                $row['category_name'] = $cat['completename'];
                $row['groups_id'] = (int)$cat['groups_id'];
                
                if ($cat['groups_id'] > 0) {
                    // Get group info
                    $group = $this->DB->request([
                        'FROM' => 'glpi_groups',
                        'WHERE' => ['id' => $cat['groups_id']],
                        'LIMIT' => 1
                    ]);
                    
                    if (count($group) > 0) {
                        $grp = $group->current();
                        $row['group_name'] = $grp['completename'];
                        
                        // Get member count
                        $members = $this->DB->request([
                            'FROM' => 'glpi_groups_users',
                            'WHERE' => ['groups_id' => $cat['groups_id']]
                        ]);
                        $row['num_group_members'] = count($members);
                    }
                }
            }
            
            $resultArray[] = $row;
        }
        
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - resultArray count: ' . count($resultArray));
        return $resultArray;
    }

}
