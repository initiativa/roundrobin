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
require_once __DIR__ . '/RRAssignmentsEntity.class.php';

use Glpi\Application\View\TemplateRenderer;


class PluginRoundRobinSettings extends CommonDBTM {

    public function __construct() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - constructor called');
    }

    public function showFormRoundRobin() {
        global $CFG_GLPI, $DB;

        if (self::checkCentralInterface()) {
            PluginRoundRobinLogger::addDebug(__METHOD__ . ' - display contents');
            self::displayContent();
        } else {
            echo "<div align='center'><br><img src='" . $CFG_GLPI['root_doc'] . "/pics/warning.png'><br>" . __("Access denied") . "</div>";
        }
    }

    public static function checkCentralInterface() {
        $currentInterface = Session::getCurrentInterface();
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - current interface: ' . $currentInterface);
        return $currentInterface === 'central';
    }

    public static function displayContent() {
        $twig = TemplateRenderer::getInstance();
        $twig->display('@roundrobin/config.form.twig', [
            'csrf_token' => Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]),
            'auto_assign_group' => self::getAutoAssignGoup(),
            'centralInterfaceCheck' => self::checkCentralInterface(),
            'settings' => self::getSettings()
        ]);
        
    }

    protected static function getSettings() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getAll();
    }

    protected static function getAutoAssignGoup() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getOptionAutoAssignGroup();
    }

    public static function saveSettings() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - POST: ' . print_r($_POST, true));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();

        /**
         * save option(s)
         */
        $rrAssignmentsEntity->updateAutoAssignGroup($_POST['auto_assign_group']);

        /**
         * save all assignments
         */
        foreach (self::getSettings() as $row) {
            $itilCategoryId = $_POST["itilcategories_id_{$row['id']}"];
            $newValue = $_POST["is_active_{$row['id']}"];
            $rrAssignmentsEntity->updateIsActive($itilCategoryId, $newValue);
        }
    }

}
