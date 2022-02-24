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
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/config.class.php';

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
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * drop settings
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlDropAssign = <<< EOT
            DROP TABLE {$this->rrAssignmentTable}
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlDrop: ' . $sqlDropAssign);
            $this->DB->queryOrDie($sqlDropAssign, $this->DB->error());
        } else {
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - table not dropped because it does not exist: " . $this->rrAssignmentTable);
        }

        /**
         * drop options
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $sqlDropOptions = <<< EOT
            DROP TABLE {$this->rrOptionsTable}
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlDrop: ' . $sqlDropOptions);
            $this->DB->queryOrDie($sqlDropOptions, $this->DB->error());
        } else {
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - table not dropped because it does not exist: " . $this->rrOptionsTable);
        }
    }

    protected function createTable() {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * create setting table
         */
        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlCreateAssign = <<< EOT
                    CREATE TABLE IF NOT EXISTS {$this->rrAssignmentTable} (
                        id INT(11) NOT NULL auto_increment,
                        itilcategories_id INT(11),
                        is_active INT(1) DEFAULT 0,
                        last_assignment_index INT(11) DEFAULT NULL,
                        PRIMARY KEY (id),
                        UNIQUE INDEX ix_itilcategories_uq (itilcategories_id ASC)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlCreate: ' . $sqlCreateAssign);
            $this->DB->queryOrDie($sqlCreateAssign, $this->DB->error());
        }

        /**
         * create option table
         */
        if (!$this->DB->tableExists($this->rrOptionsTable)) {
            $sqlCreateOption = <<< EOT
                    CREATE TABLE IF NOT EXISTS {$this->rrOptionsTable} (
                        id INT(11) NOT NULL auto_increment,
                        auto_assign_group INT(1) DEFAULT 1,
                        PRIMARY KEY (id)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlCreate: ' . $sqlCreateOption);
            $this->DB->queryOrDie($sqlCreateOption, $this->DB->error());
        }
    }

    protected function truncateTable() {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * truncate all settings
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlTruncAssign = <<< EOT
                TRUNCATE TABLE {$this->rrAssignmentTable}
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlTrunc: ' . $sqlTruncAssign);
            $this->DB->queryOrDie($sqlTruncAssign, $this->DB->error());
        }

        /**
         * truncate all options
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $sqlTruncOptions = <<< EOT
                TRUNCATE TABLE {$this->rrOptionsTable}
EOT;
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlTrunc: ' . $sqlTruncOptions);
            $this->DB->queryOrDie($sqlTruncOptions, $this->DB->error());
        }
    }

    protected function insertAllItilCategory() {
        $sqlCategory_0 = <<< EOT
                SELECT id FROM glpi_itilcategories
                WHERE itilcategories_id = 0 AND groups_id <> 0
EOT;
        $sqlCategory = <<< EOT
                SELECT id FROM glpi_itilcategories
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlCategory: ' . $sqlCategory);
        $itilCategoriesCollection = $this->DB->queryOrDie($sqlCategory, $this->DB->error());
        $itilCategoriesArray = iterator_to_array($itilCategoriesCollection);
        foreach ($itilCategoriesArray as $itilCategory) {
            $this->insertItilCategory($itilCategory['id']);
        }
    }

    public function insertItilCategory($itilCategory) {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * insert a single entry
         */
        $sqlInsert = <<< EOT
                INSERT INTO {$this->rrAssignmentTable} (itilcategories_id) VALUES ({$itilCategory})
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlInsert: ' . $sqlInsert);
        $this->DB->queryOrDie($sqlInsert, $this->DB->error());
    }

    public function insertOptions() {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * insert a single entry
         */
        $sqlInsert = <<< EOT
                INSERT INTO {$this->rrOptionsTable} (auto_assign_group) VALUES (1)
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlInsert: ' . $sqlInsert);
        $this->DB->queryOrDie($sqlInsert, $this->DB->error());
    }

    public function getOptionAutoAssignGroup() {
        $sql = <<< EOT
                SELECT auto_assign_group FROM {$this->rrOptionsTable} LIMIT 1
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        return $resultArray[0]['auto_assign_group'];
    }

    public function getGroupByItilCategory($itilCategory) {
        $sql = <<< EOT
                SELECT groups_id FROM glpi_itilcategories
                WHERE id = {$itilCategory}
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        $groupsId = $resultArray[0]['groups_id'];
        return $groupsId !== 0 ? $groupsId : false;
    }

    public function updateAutoAssignGroup($autoAssignGroup) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrOptionsTable}
                SET auto_assign_group = {$autoAssignGroup}
                WHERE id = 1
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function deleteItilCategory($itilCategory) {
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');

        /**
         * delete a single entry
         */
        $sqlDelete = <<< EOT
                DELETE FROM {$this->rrAssignmentTable} WHERE itilcategories_id = {$itilCategory}
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlDelete: ' . $sqlDelete);
        $this->DB->queryOrDie($sqlDelete, $this->DB->error());
    }

    public function updateLastAssignmentIndex($itilcategoriesId, $index) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrAssignmentTable}
                SET last_assignment_index = {$index}
                WHERE itilcategories_id = {$itilcategoriesId}
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrAssignmentTable}
                SET is_active = {$isActive}
                WHERE itilcategories_id = {$itilcategoriesId}
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function getLastAssignmentIndex($itilcategoriesId) {
        $sql = <<< EOT
                SELECT last_assignment_index FROM {$this->rrAssignmentTable} 
                WHERE itilcategories_id = {$itilcategoriesId} AND is_active = 1
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - $resultArray: ' . print_r($resultArray, true));
        if (count($resultArray) === 0 || count($resultArray) > 1) {
            /**
             * for the specified category behaviour is not required
             * or there are more than just one line for category
             */
            return false;
        } else {
            PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - category has entry');
            return $resultArray[0]['last_assignment_index'];
        }
    }

    /**
     * 
     * @return array of array (id, itilcategories_id, category_name, groups_id, group_name, num_group_members, is_active)
     */
    public function getAll() {
        $sql_0 = <<< EOT
                SELECT 
                    a.id,
                    a.itilcategories_id,
                    c.completename AS category_name,
                    c.groups_id,
                    g.completename AS group_name,
                    (SELECT 
                            COUNT(id)
                        FROM
                            glpi_groups_users gu
                        WHERE
                            gu.groups_id = g.id) AS num_group_members,
                    a.is_active
                FROM
                    glpi_plugin_roundrobin_rr_assignments a
                        JOIN
                    glpi_itilcategories c ON c.id = a.itilcategories_id
                        JOIN
                    glpi_groups g ON g.id = c.groups_id
EOT;
        $sql = <<< EOT
                SELECT 
                    a.id,
                    a.itilcategories_id,
                    c.completename AS category_name,
                    c.groups_id,
                    g.completename AS group_name,
                    (SELECT 
                            COUNT(id)
                        FROM
                            glpi_groups_users gu
                        WHERE
                            gu.groups_id = g.id) AS num_group_members,
                    a.is_active
                FROM
                    glpi_plugin_roundrobin_rr_assignments a
                        JOIN
                    glpi_itilcategories c ON c.id = a.itilcategories_id
                        LEFT JOIN
                    glpi_groups g ON g.id = c.groups_id
EOT;
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - $resultArray: ' . print_r($resultArray, true));
        return $resultArray;
    }

}
