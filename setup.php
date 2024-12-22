<?php

if (!defined('PLUGIN_TICKETBALANCE_DIR')) {
    define('PLUGIN_TICKETBALANCE_DIR', __DIR__);
}

require_once PLUGIN_TICKETBALANCE_DIR . '/inc/logger.class.php';
require_once PLUGIN_TICKETBALANCE_DIR . '/inc/request.class.php';
require_once PLUGIN_TICKETBALANCE_DIR . '/inc/config.class.php';

// Inicializa os hooks do plugin
function plugin_init_ticketbalance() {
    global $PLUGIN_HOOKS, $CFG_GLPI, $LANG;

    $PLUGIN_HOOKS['csrf_compliant']['ticketbalance'] = true;
    $PLUGIN_HOOKS['menu_toadd']['ticketbalance'] = ['plugins' => 'TicketBalanceConfigClass'];

    // Inicializa configurações do plugin
    TicketBalanceConfigClass::init();
    TicketBalanceConfigClass::loadSources();
}

// Obtém o nome e a versão do plugin @return array
function plugin_version_ticketbalance() {
    return TicketBalanceConfigClass::getVersion();
}

// Verifica os pré-requisitos antes da instalação @return boolean
function plugin_ticketbalance_check_prerequisites() {
    if (version_compare(GLPI_VERSION, TicketBalanceConfigClass::$PLUGIN_TICKETBALANCE_MIN_GLPI_VERSION, '<') ||
        version_compare(GLPI_VERSION, TicketBalanceConfigClass::$PLUGIN_TICKETBALANCE_MAX_GLPI_VERSION, '>')) {
        
        PluginTicketBalanceLogger::addCritical(__FUNCTION__ . ' - pré-requisitos não atendidos: ' . TicketBalanceConfigClass::$PLUGIN_TICKETBALANCE_GLPI_VERSION_ERROR);
        
        if (method_exists('Plugin', 'messageIncompatible')) {
            Plugin::messageIncompatible('core', TicketBalanceConfigClass::$PLUGIN_TICKETBALANCE_GLPI_VERSION_ERROR);
        }
        return false;
    }

    PluginTicketBalanceLogger::addDebug(__FUNCTION__ . ' - pré-requisitos atendidos');
    return true;
}

/**
 * Verifica o processo de configuração do plugin
 *
 * @param boolean $verbose Habilita verbosidade. Padrão é false
 * @return boolean
 */
function plugin_ticketbalance_check_config($verbose = false) {
    if (true) {
        return true; // Configuração válida
    }

    if ($verbose) {
        echo "Instalado, mas não configurado";
    }
    return false;
}