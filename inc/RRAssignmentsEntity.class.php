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
        $this->rrOptionsTable    = PluginRoundRobinConfig::getRrOptionsTable();
    }

    /**
     * Initialize plugin tables on install/upgrade.
     *
     * Idempotent: creates tables if they don't exist, then syncs categories
     * without wiping existing is_active settings. Safe to call on every
     * install or plugin update.
     */
    public function init() {
        $this->createTable();
        $this->syncCategories();
        $this->ensureOptionsRow();
    }

    /**
     * Drop plugin tables on uninstall.
     */
    public function cleanUp() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $this->DB->doQueryOrDie(
                "DROP TABLE `{$this->rrAssignmentTable}`",
                "Error dropping {$this->rrAssignmentTable}"
            );
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped table: ' . $this->rrAssignmentTable);
        }

        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $this->DB->doQueryOrDie(
                "DROP TABLE `{$this->rrOptionsTable}`",
                "Error dropping {$this->rrOptionsTable}"
            );
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped table: ' . $this->rrOptionsTable);
        }
    }

    /**
     * Create plugin tables if they do not already exist.
     */
    protected function createTable() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrAssignmentTable}` (
                `id`                    int unsigned NOT NULL AUTO_INCREMENT,
                `itilcategories_id`     int unsigned NOT NULL,
                `is_active`             tinyint NOT NULL DEFAULT 0,
                `last_assignment_index` int DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ix_itilcategories_uq` (`itilcategories_id`),
                KEY `ix_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - creating table: ' . $this->rrAssignmentTable);
            $this->DB->doQueryOrDie($query, "Error creating {$this->rrAssignmentTable}");
        }

        if (!$this->DB->tableExists($this->rrOptionsTable)) {
            $query = "CREATE TABLE IF NOT EXISTS `{$this->rrOptionsTable}` (
                `id`                int unsigned NOT NULL AUTO_INCREMENT,
                `auto_assign_group` tinyint NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";

            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - creating table: ' . $this->rrOptionsTable);
            $this->DB->doQueryOrDie($query, "Error creating {$this->rrOptionsTable}");
        }
    }

    /**
     * Sync the assignment table with the current ITIL categories:
     *  - Insert rows for categories that don't have one yet (is_active=0 by default).
     *  - Remove rows whose category no longer exists.
     *
     * Existing rows (and their is_active / last_assignment_index values) are
     * left untouched, so reinstalling or upgrading the plugin never resets
     * the administrator's configuration.
     */
    protected function syncCategories() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - syncing categories...');

        // --- insert missing categories ---
        $allCategories = $this->DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_itilcategories',
        ]);

        foreach ($allCategories as $cat) {
            $this->insertItilCategory($cat['id']);
        }

        // --- remove orphaned rows (category was deleted outside the hook) ---
        $orphans = $this->DB->request([
            'SELECT' => ['a.id'],
            'FROM'   => $this->rrAssignmentTable . ' AS a',
            'LEFT JOIN' => [
                'glpi_itilcategories AS c' => [
                    'ON' => ['a' => 'itilcategories_id', 'c' => 'id'],
                ],
            ],
            'WHERE' => ['c.id' => null],
        ]);

        foreach ($orphans as $orphan) {
            $this->DB->delete($this->rrAssignmentTable, ['id' => (int) $orphan['id']]);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - removed orphaned row id: ' . $orphan['id']);
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - sync complete');
    }

    /**
     * Ensure the options table has exactly one row with default values.
     * Does nothing if the row already exists.
     */
    protected function ensureOptionsRow() {
        $result = $this->DB->request([
            'FROM'  => $this->rrOptionsTable,
            'LIMIT' => 1,
        ]);

        if (count($result) === 0) {
            $this->DB->insert($this->rrOptionsTable, ['auto_assign_group' => 1]);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserted default options row');
        }
    }

    /**
     * Insert a single ITIL category into the assignment table.
     * Does nothing if already present (idempotent).
     *
     * @param int $itilCategory
     */
    public function insertItilCategory($itilCategory) {
        $exists = $this->DB->request([
            'SELECT' => ['id'],
            'FROM'   => $this->rrAssignmentTable,
            'WHERE'  => ['itilcategories_id' => (int) $itilCategory],
            'LIMIT'  => 1,
        ]);

        if (count($exists) === 0) {
            $this->DB->insert($this->rrAssignmentTable, [
                'itilcategories_id' => (int) $itilCategory,
                'is_active'         => 0,
            ]);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - inserted category: ' . $itilCategory);
        }
    }

    /**
     * @deprecated Use ensureOptionsRow() via init(). Kept for BC.
     */
    public function insertOptions() {
        $this->ensureOptionsRow();
    }

    // -------------------------------------------------------------------------
    // Getters / setters
    // -------------------------------------------------------------------------

    public function getOptionAutoAssignGroup() {
        $result = $this->DB->request([
            'FROM'  => $this->rrOptionsTable,
            'LIMIT' => 1,
        ]);

        if (count($result) > 0) {
            $row = $result->current();
            return (int) $row['auto_assign_group'];
        }
        return 1; // safe default
    }

    public function getGroupByItilCategory($itilCategory) {
        $result = $this->DB->request([
            'SELECT' => ['groups_id'],
            'FROM'   => 'glpi_itilcategories',
            'WHERE'  => ['id' => (int) $itilCategory],
            'LIMIT'  => 1,
        ]);

        if (count($result) > 0) {
            $row      = $result->current();
            $groupsId = (int) $row['groups_id'];
            return $groupsId !== 0 ? $groupsId : false;
        }
        return false;
    }

    public function updateAutoAssignGroup($autoAssignGroup) {
        $this->DB->update(
            $this->rrOptionsTable,
            ['auto_assign_group' => (int) $autoAssignGroup],
            ['id' => 1]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - updated to: ' . $autoAssignGroup);
    }

    public function deleteItilCategory($itilCategory) {
        $this->DB->delete(
            $this->rrAssignmentTable,
            ['itilcategories_id' => (int) $itilCategory]
        );
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - deleted category: ' . $itilCategory);
    }

    public function updateLastAssignmentIndex($itilcategoriesId, $index) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['last_assignment_index' => (int) $index],
            ['itilcategories_id'     => (int) $itilcategoriesId]
        );
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['is_active'         => (int) $isActive],
            ['itilcategories_id' => (int) $itilcategoriesId]
        );
    }

    public function getLastAssignmentIndex($itilcategoriesId) {
        $result = $this->DB->request([
            'SELECT' => ['last_assignment_index'],
            'FROM'   => $this->rrAssignmentTable,
            'WHERE'  => [
                'itilcategories_id' => (int) $itilcategoriesId,
                'is_active'         => 1,
            ],
            'LIMIT'  => 1,
        ]);

        $rows = iterator_to_array($result);

        if (count($rows) !== 1) {
            // Category not configured for RR or unique index violated
            return false;
        }

        return $rows[0]['last_assignment_index'];
    }

    /**
     * Return all assignments joined with category and group data.
     * Single query via LEFT JOIN — avoids the N+1 pattern.
     *
     * @return array  Each row: id, itilcategories_id, category_name,
     *                groups_id, group_name, num_group_members, is_active
     */
    public function getAll() {
        // One query: assignments + category completename + group completename
        $rows = $this->DB->request([
            'SELECT' => [
                'a.id',
                'a.itilcategories_id',
                'a.is_active',
                'c.completename AS category_name',
                'c.groups_id',
                'g.completename AS group_name',
            ],
            'FROM'      => $this->rrAssignmentTable . ' AS a',
            'LEFT JOIN' => [
                'glpi_itilcategories AS c' => [
                    'ON' => ['a' => 'itilcategories_id', 'c' => 'id'],
                ],
                'glpi_groups AS g' => [
                    'ON' => ['c' => 'groups_id', 'g' => 'id'],
                ],
            ],
            'ORDER' => 'a.id ASC',
        ]);

        // Collect group IDs that actually exist so we can count members in bulk
        $groupIds = [];
        $resultArray = [];
        foreach ($rows as $row) {
            $entry = [
                'id'                => (int) $row['id'],
                'itilcategories_id' => (int) $row['itilcategories_id'],
                'category_name'     => $row['category_name'] ?? '',
                'groups_id'         => (int) ($row['groups_id'] ?? 0),
                'group_name'        => $row['group_name'] ?? null,
                'num_group_members' => 0,
                'is_active'         => (int) $row['is_active'],
            ];
            $resultArray[] = $entry;

            if (!empty($row['groups_id'])) {
                $groupIds[(int) $row['groups_id']] = true;
            }
        }

        // Count members per group in a single query
        if (!empty($groupIds)) {
            $memberCounts = $this->DB->request([
                'SELECT' => ['groups_id', 'COUNT(id) AS cnt'],
                'FROM'   => 'glpi_groups_users',
                'WHERE'  => ['groups_id' => array_keys($groupIds)],
                'GROUP'  => 'groups_id',
            ]);

            $countMap = [];
            foreach ($memberCounts as $mc) {
                $countMap[(int) $mc['groups_id']] = (int) $mc['cnt'];
            }

            foreach ($resultArray as &$entry) {
                if ($entry['groups_id'] > 0) {
                    $entry['num_group_members'] = $countMap[$entry['groups_id']] ?? 0;
                }
            }
            unset($entry);
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - returned ' . count($resultArray) . ' rows');
        return $resultArray;
    }
}
