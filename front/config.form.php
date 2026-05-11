<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 by initiativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

<<<<<<< Updated upstream
// GLPI 11 - include GLPI core
=======
// GLPI 11 — include GLPI core
>>>>>>> Stashed changes
include('../../../inc/includes.php');

// Include plugin classes using __DIR__
require_once __DIR__ . '/../inc/logger.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/RRAssignmentsEntity.class.php';
require_once __DIR__ . '/../inc/config.form.class.php';

<<<<<<< Updated upstream
// Check rights
=======
>>>>>>> Stashed changes
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

if (isset($_POST['save_options'])) {
    Session::checkRight('config', UPDATE);
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - SAVE OPTIONS: POST ', $_POST);
    PluginRoundRobinSettings::saveGeneralOptions();
    Html::back();
}

if (isset($_POST['save_assignments'])) {
    Session::checkRight('config', UPDATE);
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - SAVE ASSIGNMENTS: POST ', $_POST);
    PluginRoundRobinSettings::saveCategoryAssignments();
    Html::back();
}

$pluginRoundRobinConfigForm = new PluginRoundRobinSettings();
<<<<<<< Updated upstream

/**
 * check for post form data and perform requested action
 */
if (isset($_REQUEST['save'])) {
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - SAVE: POST: ', $_POST);
    $pluginRoundRobinConfigForm::saveSettings();
    Session::AddMessageAfterRedirect('Config saved');
    Html::back();
}

if (isset($_REQUEST['cancel'])) {
    PluginRoundRobinLogger::addDebug(__FILE__ . ' - CANCEL: POST: ', $_POST);
    Session::AddMessageAfterRedirect('Config reset');
    Html::back();
}

/**
 * then render current configuration
 */
=======
>>>>>>> Stashed changes
$pluginRoundRobinConfigForm->showFormRoundRobin();

Html::footer();
