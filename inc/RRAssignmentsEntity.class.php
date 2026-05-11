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
    protected $rrGroupsTable;

    public function __construct() {
        global $DB;

        $this->DB = $DB;
        $this->rrAssignmentTable = PluginRoundRobinConfig::getRrAssignmentTable();
        $this->rrOptionsTable = PluginRoundRobinConfig::getRrOptionsTable();
        $this->rrGroupsTable = PluginRoundRobinConfig::getRrGroupsTable();
    }

    public function init() {
        $this->createTables();
        $this->syncCategories();
        $this->ensureOptionsRow();
    }

    public function cleanUp() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered (tables preserved so reinstall keeps configuration)');

        /**
         * Intentionally do not DROP plugin tables on uninstall so that reinstall
         * recovers assignment options and rotation state without data loss.
         */
    }

    protected function createTables() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        /**
         * create setting table - GLPI 11 compatible
         */
        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrAssignmentTable}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `itilcategories_id` int unsigned NOT NULL,
                `is_active` tinyint NOT NULL DEFAULT 0,
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

        /**
         * create group rotation table - GLPI 11 compatible
         * Stores one rotation index per group, shared across categories.
         */
        if (!$this->DB->tableExists($this->rrGroupsTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrGroupsTable}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `groups_id` int unsigned NOT NULL,
                `last_assignment_index` int DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ix_groups_uq` (`groups_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sqlCreate: ' . $query);
            $this->DB->doQueryOrDie($query, "Error creating {$this->rrGroupsTable}");
        }
    }

    /**
     * Sync rr_assignments rows with existing ITIL categories.
     * - Inserts missing categories as disabled (is_active=0)
     * - Removes orphaned rows (deleted categories)
     * - Never overwrites existing configuration
     */
    public function syncCategories() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        // Insert missing categories (idempotent)
        $result = $this->DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_itilcategories'
        ]);

        foreach ($result as $itilCategory) {
            $this->insertItilCategory((int)$itilCategory['id']);
        }

        // Remove orphaned categories
        // Keep existing configuration for valid categories untouched.
        $sql = "DELETE FROM `{$this->rrAssignmentTable}`
                WHERE `itilcategories_id` NOT IN (SELECT `id` FROM `glpi_itilcategories`)";
        $this->DB->doQueryOrDie($sql, "Error syncing {$this->rrAssignmentTable} (remove orphaned categories)");
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
        // Backward compatibility shim (older callers)
        $this->ensureOptionsRow();
    }

    public function ensureOptionsRow() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        $result = $this->DB->request([
            'FROM' => $this->rrOptionsTable,
            'LIMIT' => 1
        ]);

        if (count($result) === 0) {
            $this->DB->insert($this->rrOptionsTable, [
                'auto_assign_group' => 1
            ]);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserted default options');
        }
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
        $this->ensureOptionsRow();

        $row = $this->DB->request([
            'SELECT' => ['id'],
            'FROM' => $this->rrOptionsTable,
            'LIMIT' => 1,
        ]);

        $idrow = count($row) ? $row->current() : null;
        if ($idrow) {
            $this->DB->update(
                $this->rrOptionsTable,
                ['auto_assign_group' => (int)$autoAssignGroup],
                ['id' => (int)$idrow['id']]
            );
        }

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

    public function updateIsActive($itilcategoriesId, $isActive) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['is_active' => (int)$isActive],
            ['itilcategories_id' => (int)$itilcategoriesId]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated is_active: ' . $isActive . ' for category: ' . $itilcategoriesId);
    }

    public function isCategoryActive(int $itilcategoriesId): bool {
        $result = $this->DB->request([
            'SELECT' => ['id'],
            'FROM' => $this->rrAssignmentTable,
            'WHERE' => [
                'itilcategories_id' => (int)$itilcategoriesId,
                'is_active' => 1
            ],
            'LIMIT' => 1
        ]);

        return count($result) === 1;
    }

    /**
     * Get last rotation index for a group.
     *
     * @return int|null Returns null when group has no row yet (first assignment).
     */
    public function getLastGroupAssignmentIndex(int $groupsId): ?int {
        $result = $this->DB->request([
            'SELECT' => ['last_assignment_index'],
            'FROM' => $this->rrGroupsTable,
            'WHERE' => ['groups_id' => (int)$groupsId],
            'LIMIT' => 1
        ]);

        if (count($result) === 0) {
            return null;
        }

        $row = $result->current();
        return $row['last_assignment_index'] !== null ? (int)$row['last_assignment_index'] : null;
    }

    public function updateLastGroupAssignmentIndex(int $groupsId, int $index): void {
        $gid = (int)$groupsId;
        $idx = (int)$index;
        // UPSERT — GLPI DB::update often returns false/0 when no row matched or MY SQL "0 rows affected",
        // which broke rotation persistence and reassigned every ticket to the same technician.
        $sql = "INSERT INTO `{$this->rrGroupsTable}` (`groups_id`, `last_assignment_index`)
                VALUES ($gid, $idx)
                ON DUPLICATE KEY UPDATE `last_assignment_index` = VALUES(`last_assignment_index`)";
        $this->DB->doQueryOrDie($sql, "Error saving group rotation index for group $gid");

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated last_assignment_index: ' . $idx . ' for group: ' . $gid);
    }

    /**
     * Get all assignments with category and group info
     * 
     * @return array of array (id, itilcategories_id, category_name, groups_id, group_name, num_group_members, is_active)
     */
    public function getAll() {
        // 1) Load everything in one shot (avoid N+1 queries)
        $rows = $this->DB->request([
            'SELECT' => [
                "{$this->rrAssignmentTable}.id AS id",
                "{$this->rrAssignmentTable}.itilcategories_id AS itilcategories_id",
                "{$this->rrAssignmentTable}.is_active AS is_active",
                "c.completename AS category_name",
                "c.groups_id AS groups_id",
                "g.completename AS group_name",
            ],
            'FROM' => $this->rrAssignmentTable,
            'LEFT JOIN' => [
                'glpi_itilcategories AS c' => [
                    'ON' => [
                        $this->rrAssignmentTable => 'itilcategories_id',
                        'c' => 'id'
                    ]
                ],
                'glpi_groups AS g' => [
                    'ON' => [
                        'c' => 'groups_id',
                        'g' => 'id'
                    ]
                ],
            ],
            'ORDER' => "{$this->rrAssignmentTable}.id"
        ]);

        // 2) Bulk member counts (DB->request() breaks COUNT() on some GLPI DB wrappers)
        $memberCounts = [];
        $sql = "SELECT gu.groups_id, COUNT(*) AS cnt
                FROM `glpi_groups_users` gu
                INNER JOIN `glpi_users` u ON u.id = gu.users_id
                WHERE u.is_active = 1 AND u.is_deleted = 0
                GROUP BY gu.groups_id";

        $res = $this->DB->doQuery($sql);
        if ($res) {
            if (method_exists($this->DB, 'fetchAssoc')) {
                while ($assoc = $this->DB->fetchAssoc($res)) {
                    $memberCounts[(int)$assoc['groups_id']] = (int)$assoc['cnt'];
                }
            } else {
                while ($assoc = $this->DB->fetchArray($res)) {
                    $memberCounts[(int)$assoc['groups_id']] = (int)$assoc['cnt'];
                }
            }
        }

        $resultArray = [];
        foreach ($rows as $row) {
            $groupsId = isset($row['groups_id']) ? (int)$row['groups_id'] : 0;
            $canEnableRoundRobin = $groupsId > 0;

            $resultArray[] = [
                'id' => (int)$row['id'],
                'itilcategories_id' => (int)$row['itilcategories_id'],
                'category_name' => $row['category_name'] ?? '',
                'groups_id' => $groupsId,
                'group_name' => $row['group_name'] ?? null,
                'num_group_members' => $groupsId > 0 ? ($memberCounts[$groupsId] ?? 0) : null,
                'is_active' => (int)$row['is_active'],
                'can_enable_roundrobin' => $canEnableRoundRobin,
            ];
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - resultArray count: ' . count($resultArray));
        return $resultArray;
    }

}
