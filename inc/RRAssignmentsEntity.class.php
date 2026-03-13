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

require_once __DIR__ . '/config.class.php';
require_once __DIR__ . '/logger.class.php';

class PluginRoundRobinRRAssignmentsEntity extends CommonDBTM {

    protected $DB;
    protected $rrAssignmentTable;
    protected $rrOptionsTable;
    protected $rrGroupsTable;

    public function __construct() {
        global $DB;

        $this->DB              = $DB;
        $this->rrAssignmentTable = PluginRoundRobinConfig::getRrAssignmentTable();
        $this->rrOptionsTable    = PluginRoundRobinConfig::getRrOptionsTable();
        $this->rrGroupsTable     = PluginRoundRobinConfig::getRrGroupsTable();
    }

    // =========================================================================
    // Install / uninstall
    // =========================================================================

    /**
     * Initialize plugin tables on install/upgrade.
     *
     * Idempotent: creates tables if they don't exist, syncs categories
     * without wiping existing is_active settings. Safe to call on every
     * install or upgrade.
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

        foreach ([$this->rrAssignmentTable, $this->rrGroupsTable, $this->rrOptionsTable] as $table) {
            if ($this->DB->tableExists($table)) {
                $this->DB->doQueryOrDie("DROP TABLE `{$table}`", "Error dropping {$table}");
                PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped: ' . $table);
            }
        }
    }

    // =========================================================================
    // Table creation
    // =========================================================================

    protected function createTable() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');

        // --- assignments (per-category config + is_active) ---
        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $this->DB->doQueryOrDie(
                "CREATE TABLE IF NOT EXISTS `{$this->rrAssignmentTable}` (
                    `id`                int unsigned NOT NULL AUTO_INCREMENT,
                    `itilcategories_id` int unsigned NOT NULL,
                    `is_active`         tinyint      NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `ix_itilcategories_uq` (`itilcategories_id`),
                    KEY `ix_is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
                "Error creating {$this->rrAssignmentTable}"
            );
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - created: ' . $this->rrAssignmentTable);
        } else {
            // Upgrade path: drop legacy last_assignment_index column if it exists
            // (index is now tracked per-group in rr_groups table)
            $this->DB->doQueryOrDie(
                "ALTER TABLE `{$this->rrAssignmentTable}`
                 MODIFY COLUMN `is_active` tinyint NOT NULL DEFAULT 0",
                "Error altering {$this->rrAssignmentTable}"
            );
            if ($this->DB->fieldExists($this->rrAssignmentTable, 'last_assignment_index')) {
                $this->DB->doQueryOrDie(
                    "ALTER TABLE `{$this->rrAssignmentTable}`
                     DROP COLUMN `last_assignment_index`",
                    "Error dropping last_assignment_index from {$this->rrAssignmentTable}"
                );
                PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - dropped legacy column last_assignment_index');
            }
        }

        // --- group rotation index (shared counter per group) ---
        if (!$this->DB->tableExists($this->rrGroupsTable)) {
            $this->DB->doQueryOrDie(
                "CREATE TABLE IF NOT EXISTS `{$this->rrGroupsTable}` (
                    `id`                    int unsigned NOT NULL AUTO_INCREMENT,
                    `groups_id`             int unsigned NOT NULL,
                    `last_assignment_index` int          DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `ix_groups_uq` (`groups_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
                "Error creating {$this->rrGroupsTable}"
            );
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - created: ' . $this->rrGroupsTable);
        }

        // --- options ---
        if (!$this->DB->tableExists($this->rrOptionsTable)) {
            $this->DB->doQueryOrDie(
                "CREATE TABLE IF NOT EXISTS `{$this->rrOptionsTable}` (
                    `id`                int unsigned NOT NULL AUTO_INCREMENT,
                    `auto_assign_group` tinyint      NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
                "Error creating {$this->rrOptionsTable}"
            );
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - created: ' . $this->rrOptionsTable);
        }
    }

    // =========================================================================
    // Category sync
    // =========================================================================

    /**
     * Sync the assignment table with current ITIL categories.
     * Inserts missing rows (is_active=0) and removes orphaned ones.
     * Existing is_active values are never touched.
     */
    protected function syncCategories() {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - syncing...');

        $allCategories = $this->DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_itilcategories',
        ]);
        foreach ($allCategories as $cat) {
            $this->insertItilCategory($cat['id']);
        }

        // Remove rows for categories that no longer exist
        $orphans = $this->DB->request([
            'SELECT'    => ['a.id'],
            'FROM'      => $this->rrAssignmentTable . ' AS a',
            'LEFT JOIN' => [
                'glpi_itilcategories AS c' => [
                    'ON' => ['a' => 'itilcategories_id', 'c' => 'id'],
                ],
            ],
            'WHERE' => ['c.id' => null],
        ]);
        foreach ($orphans as $o) {
            $this->DB->delete($this->rrAssignmentTable, ['id' => (int) $o['id']]);
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - done');
    }

    /**
     * Insert a category row if not already present (idempotent).
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
        }
    }

    // =========================================================================
    // Options
    // =========================================================================

    protected function ensureOptionsRow() {
        $result = $this->DB->request(['FROM' => $this->rrOptionsTable, 'LIMIT' => 1]);
        if (count($result) === 0) {
            $this->DB->insert($this->rrOptionsTable, ['auto_assign_group' => 1]);
        }
    }

    /** @deprecated Use ensureOptionsRow() via init(). Kept for BC. */
    public function insertOptions() {
        $this->ensureOptionsRow();
    }

    public function getOptionAutoAssignGroup() {
        $result = $this->DB->request(['FROM' => $this->rrOptionsTable, 'LIMIT' => 1]);
        if (count($result) > 0) {
            return (int) $result->current()['auto_assign_group'];
        }
        return 1;
    }

    public function updateAutoAssignGroup($autoAssignGroup) {
        $this->DB->update(
            $this->rrOptionsTable,
            ['auto_assign_group' => (int) $autoAssignGroup],
            ['id' => 1]
        );
    }

    // =========================================================================
    // Category helpers
    // =========================================================================

    public function getGroupByItilCategory($itilCategory) {
        $result = $this->DB->request([
            'SELECT' => ['groups_id'],
            'FROM'   => 'glpi_itilcategories',
            'WHERE'  => ['id' => (int) $itilCategory],
            'LIMIT'  => 1,
        ]);
        if (count($result) > 0) {
            $groupsId = (int) $result->current()['groups_id'];
            return $groupsId !== 0 ? $groupsId : false;
        }
        return false;
    }

    public function deleteItilCategory($itilCategory) {
        $this->DB->delete($this->rrAssignmentTable, ['itilcategories_id' => (int) $itilCategory]);
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        $this->DB->update(
            $this->rrAssignmentTable,
            ['is_active'         => (int) $isActive],
            ['itilcategories_id' => (int) $itilcategoriesId]
        );
    }

    /**
     * Returns the is_active flag for a given category, or false if not found.
     */
    public function isActiveForCategory(int $itilcategoriesId): bool {
        $result = $this->DB->request([
            'SELECT' => ['is_active'],
            'FROM'   => $this->rrAssignmentTable,
            'WHERE'  => ['itilcategories_id' => $itilcategoriesId],
            'LIMIT'  => 1,
        ]);
        if (count($result) === 0) {
            return false;
        }
        return (bool) $result->current()['is_active'];
    }

    // =========================================================================
    // Group-based rotation index (the actual RR state)
    // =========================================================================

    /**
     * Get the current rotation index for a group.
     * Returns NULL on first assignment (no history yet), or false if the group
     * row doesn't exist at all (shouldn't happen after ensureGroupRow).
     *
     * @param  int        $groupId
     * @return int|null|false
     */
    public function getLastAssignmentIndexByGroup(int $groupId) {
        $result = $this->DB->request([
            'SELECT' => ['last_assignment_index'],
            'FROM'   => $this->rrGroupsTable,
            'WHERE'  => ['groups_id' => $groupId],
            'LIMIT'  => 1,
        ]);
        if (count($result) === 0) {
            return false;
        }
        return $result->current()['last_assignment_index']; // may be NULL
    }

    /**
     * Persist the new rotation index for a group.
     * Upserts: inserts the row if it doesn't exist yet.
     *
     * @param int $groupId
     * @param int $index
     */
    public function updateLastAssignmentIndexByGroup(int $groupId, int $index) {
        $exists = $this->DB->request([
            'SELECT' => ['id'],
            'FROM'   => $this->rrGroupsTable,
            'WHERE'  => ['groups_id' => $groupId],
            'LIMIT'  => 1,
        ]);

        if (count($exists) > 0) {
            $this->DB->update(
                $this->rrGroupsTable,
                ['last_assignment_index' => $index],
                ['groups_id'             => $groupId]
            );
        } else {
            $this->DB->insert($this->rrGroupsTable, [
                'groups_id'             => $groupId,
                'last_assignment_index' => $index,
            ]);
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - group {$groupId} → index {$index}");
    }

    // =========================================================================
    // Legacy per-category index methods (kept for BC, delegate to group)
    // =========================================================================

    /**
     * @deprecated Use getLastAssignmentIndexByGroup() directly.
     *             Kept so any external code that calls this still works.
     */
    public function getLastAssignmentIndex($itilcategoriesId) {
        // Category must be active for RR
        if (!$this->isActiveForCategory((int) $itilcategoriesId)) {
            return false;
        }
        $groupId = $this->getGroupByItilCategory($itilcategoriesId);
        if ($groupId === false) {
            return false;
        }
        $index = $this->getLastAssignmentIndexByGroup($groupId);
        // false = no row in rr_groups yet → first assignment → start at 0
        if ($index === false) {
            return null;
        }
        return $index;
    }

    /**
     * @deprecated Use updateLastAssignmentIndexByGroup() directly.
     */
    public function updateLastAssignmentIndex($itilcategoriesId, $index) {
        $groupId = $this->getGroupByItilCategory($itilcategoriesId);
        if ($groupId !== false) {
            $this->updateLastAssignmentIndexByGroup((int) $groupId, (int) $index);
        }
    }

    // =========================================================================
    // UI data
    // =========================================================================

    /**
     * Return all assignments with category/group info for the config page.
     * Single JOIN query + one bulk COUNT — no N+1.
     *
     * @return array  id, itilcategories_id, category_name, groups_id,
     *                group_name, num_group_members, is_active
     */
    public function getAll() {
        $rows = $this->DB->request([
            'SELECT'    => [
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
            'ORDER' => 'category_name ASC',
        ]);

        $resultArray = [];
        $groupIds    = [];
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

        // Member counts in a single raw query.
        // GLPI's ORM backticks every SELECT element, breaking COUNT(id) syntax,
        // so we use doQuery() directly here.
        if (!empty($groupIds)) {
            $ids      = implode(',', array_map('intval', array_keys($groupIds)));
            $result   = $this->DB->doQuery(
                "SELECT `groups_id`, COUNT(`id`) AS `cnt`
                 FROM `glpi_groups_users`
                 WHERE `groups_id` IN ({$ids})
                 GROUP BY `groups_id`"
            );
            $countMap = [];
            if ($result) {
                while ($row = $this->DB->fetchAssoc($result)) {
                    $countMap[(int) $row['groups_id']] = (int) $row['cnt'];
                }
            }
            foreach ($resultArray as &$e) {
                if ($e['groups_id'] > 0) {
                    $e['num_group_members'] = $countMap[$e['groups_id']] ?? 0;
                }
            }
            unset($e);
        }

        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - returned ' . count($resultArray) . ' rows');
        return $resultArray;
    }
}
