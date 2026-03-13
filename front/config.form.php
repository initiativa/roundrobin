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

include('../../../inc/includes.php');

require_once __DIR__ . '/../inc/logger.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/RRAssignmentsEntity.class.php';
require_once __DIR__ . '/../inc/config.form.class.php';

// Require GLPI admin rights to access this page
Session::checkRight('config', READ);

Html::header(
    __('RoundRobin Settings', 'roundrobin'),
    $_SERVER['PHP_SELF'],
    'plugins',
    PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_CODE,
    'config'
);

$form = new PluginRoundRobinSettings();

if (isset($_POST['save'])) {
    // CSRF is validated by GLPI core before reaching this point
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - SAVE action: ' . ($_POST['action'] ?? 'all'));
    $form::saveSettings();
    Session::addMessageAfterRedirect(
        __('Configuration saved successfully.', 'roundrobin'),
        true,
        INFO
    );
    Html::back();
}

$form->showFormRoundRobin();

Html::footer();
