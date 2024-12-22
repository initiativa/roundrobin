<?php

require_once '../inc/config.class.php';
require_once '../inc/logger.class.php';
require_once '../inc/config.form.class.php';

/**
 * render menu bar
 */
Html::header('TicketBalance', $_SERVER['PHP_SELF'], "plugins", TicketBalanceConfigClass::$PLUGIN_TICKETBALANCE_CODE, "config");

$pluginTicketBalanceConfigClass = new TicketBalanceConfigFormClass();

/**
 * check for post form data and perform requested action
 */
if (isset($_REQUEST['save'])) {
    PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - SAVE: POST: ', $_POST);
	$pluginTicketBalanceConfigClass->saveSettings();
    Session::AddMessageAfterRedirect('Configuração salva');
    Html::back();
}

if (isset($_REQUEST['cancel'])) {
    PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - CANCEL: POST: ', $_POST);
    Session::AddMessageAfterRedirect('Configuração resetada');
    Html::back();
}

/**
 * then render current configuration
 */
$pluginTicketBalanceConfigClass->renderTitle();
$pluginTicketBalanceConfigClass->showFormTicketBalance();