<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 by initiativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

// GLPI 11 — include GLPI core
include('../../../inc/includes.php');

// Include plugin classes using __DIR__
require_once __DIR__ . '/../inc/logger.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/RRAssignmentsEntity.class.php';
require_once __DIR__ . '/../inc/config.form.class.php';

Session::checkRight('config', READ);

/**
 * Prefer highlighting the Setup entry when accessed from Setup → RoundRobin
 */
Html::header(
    __('RoundRobin', 'roundrobin'),
    $_SERVER['PHP_SELF'],
    'config',
    'pluginroundrobinsetupmenu'
);

if (isset($_POST['save_config'])) {
    Session::checkRight('config', UPDATE);
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - SAVE CONFIG: POST ', $_POST);
    PluginRoundRobinSettings::persistListLimit();
    PluginRoundRobinSettings::saveConfig();
    Html::back();
}

$pluginRoundRobinConfigForm = new PluginRoundRobinSettings();
$pluginRoundRobinConfigForm->showFormRoundRobin();

Html::footer();
