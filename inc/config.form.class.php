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
require_once __DIR__ . '/RRAssignmentsEntity.class.php';

use Glpi\Application\View\TemplateRenderer;

class PluginRoundRobinSettings extends CommonDBTM {

    public function __construct() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - constructor called');
    }

    /**
     * Render the configuration page.
     * Access control is already enforced in front/config.form.php via
     * Session::checkRight(). This method only handles rendering.
     */
    public function showFormRoundRobin() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - displaying config form');

        TemplateRenderer::getInstance()->display('@roundrobin/config.form.twig', [
            'csrf_token'        => Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]),
            'auto_assign_group' => self::getAutoAssignGroup(),
            'settings'          => self::getSettings(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Data
    // -------------------------------------------------------------------------

    protected static function getSettings(): array {
        return (new PluginRoundRobinRRAssignmentsEntity())->getAll();
    }

    protected static function getAutoAssignGroup(): int {
        return (new PluginRoundRobinRRAssignmentsEntity())->getOptionAutoAssignGroup();
    }

    // -------------------------------------------------------------------------
    // Save
    // -------------------------------------------------------------------------

    /**
     * Persist configuration from POST data.
     *
     * Two independent actions are dispatched by the template:
     *   action=save_options    → only the global auto_assign_group toggle
     *   action=save_categories → all per-category is_active switches
     *
     * Falls back to saving everything if action is absent (BC).
     */
    public static function saveSettings(): void {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - saving, action: ' . ($_POST['action'] ?? 'all'));

        $entity = new PluginRoundRobinRRAssignmentsEntity();
        $action = $_POST['action'] ?? 'all';

        if ($action === 'save_options' || $action === 'all') {
            // Checkbox unchecked → key absent from POST → default 0
            $autoAssignGroup = isset($_POST['auto_assign_group']) ? 1 : 0;
            $entity->updateAutoAssignGroup($autoAssignGroup);
        }

        if ($action === 'save_categories' || $action === 'all') {
            foreach (self::getSettings() as $row) {
                $rowId = (int) $row['id'];

                // Validate that the POSTed category ID matches what we expect
                $postedCategoryId = (int) ($_POST["itilcategories_id_{$rowId}"] ?? 0);
                if ($postedCategoryId !== (int) $row['itilcategories_id']) {
                    PluginRoundRobinLogger::addDebug(
                        __METHOD__ . " - skipping row {$rowId}: category ID mismatch"
                    );
                    continue;
                }

                // Checkbox unchecked → key absent from POST → 0
                $isActive = isset($_POST["is_active_{$rowId}"]) ? 1 : 0;
                $entity->updateIsActive($postedCategoryId, $isActive);
            }
        }
    }
}
