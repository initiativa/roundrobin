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
class PluginRoundRobinRequest {

    protected static function getInputParam($type, $key) {
        $value = filter_input($type, $key);
        return $value === false || is_null($value) ? null : $value;
    }

    public static function getServerParam($key) {
        return self::getInputParam(INPUT_SERVER, $key);
    }

    public static function getSessionParam($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function getRequestParam($key) {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }

    public static function getUserProfileId() {
        return $_SESSION['glpiactiveprofile']['id'];
    }

    public static function getCurrentProfileSettings() {
        global $DB;

        $pluginCode = PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_CODE;
        $settingTableConfig = "glpi_plugin_" . $pluginCode . "_config";
        $profileId = self::getUserProfileId();
        $sql = <<< EOT
                SELECT c.*
                FROM $settingTableConfig c JOIN glpi_profiles p ON p.id = c.profile_id
                WHERE c.profile_id = $profileId;
EOT;
        $collection = $DB->queryOrDie($sql, $DB->error());
        $configArray = iterator_to_array($collection);

        $settingsArray = iterator_to_array($collection);
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - profile_id: $profileId results: " . var_export($settingsArray, true));
        return count($settingsArray) === 1 ? $settingsArray[0]['hasTypeAsCategory'] : null;
    }

    public static function getUserProfile() {
        if (isset($_SESSION["glpiactiveprofile"]["interface"]) && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
            return 'admin';
        }
        return 'helpdesk';
    }

    public static function initService() {
        header("Content-Type: text/html; charset=UTF-8");
        Html::header_nocache();
    }

    public static function initJavaScriptBlock() {
        header("Content-type: application/javascript");

        Html::header_nocache();
        Session::checkLoginUser();

        $plugin = new Plugin();
        if (!$plugin->isActivated(PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_CODE)) {
            exit;
        }
    }

}
