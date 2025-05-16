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
require_once '../inc/config.class.php';
require_once '../inc/logger.class.php';
require_once '../inc/config.form.class.php';

/**
 * render menu bar
 */
Html::header('RoundRobin Settings', $_SERVER['PHP_SELF'], "plugins", PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_CODE, "config");

$pluginRoundRobinConfigForm = new PluginRoundRobinSettings();

/**
 * check for post form data and perform requested action
 */
if (isset($_REQUEST['save'])) {
    PluginRoundRobinLogger::addDebug(__METHOD__ . ' - SAVE: POST: ', $_POST);
    $pluginRoundRobinConfigForm::saveSettings();
    Session::AddMessageAfterRedirect('Config saved');
    Html::back();
}

if (isset($_REQUEST['cancel'])) {
    PluginRoundRobinLogger::addDebug(__METHOD__ . ' - CANCEL: POST: ', $_POST);
    Session::AddMessageAfterRedirect('Config reset');
    Html::back();
}

/**
 * then render current configuration
 */
$pluginRoundRobinConfigForm->showFormRoundRobin();
