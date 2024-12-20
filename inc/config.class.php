<?php

/**
 * -------------------------------------------------------------------------
 * Plugin TicketBalance para GLPI
 * -------------------------------------------------------------------------
 *
 * LICENÇA
 *
 * Este arquivo é parte do Plugin TicketBalance GLPI.
 *
 * TicketBalance é um software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela
 * Free Software Foundation; seja na versão 3 da Licença, ou (a seu critério) qualquer versão posterior.
 *
 * TicketBalance é distribuído na esperança de que seja útil,
 * mas SEM QUALQUER GARANTIA; sem mesmo a garantia implícita de
 * COMERCIALIZAÇÃO ou ADEQUAÇÃO A UM PROPÓSITO PARTICULAR. Consulte a Licença Pública Geral GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com o TicketBalance. Se não, veja <http://www.gnu.org/licenses/>
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 - https://www.linkedin.com/in/richard-ti/
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/RPGMais/ticketbalance
 * -------------------------------------------------------------------------
 */
class PluginRoundRobinConfig {

    public static $PLUGIN_ROUNDROBIN_ENV = 'desenvolvimento';
    public static $PLUGIN_ROUNDROBIN_NAME = 'Ticket Balance';
    public static $PLUGIN_ROUNDROBIN_CODE = 'ticketbalance';
    public static $PLUGIN_ROUNDROBIN_VERSION = '1.0.3';
    public static $PLUGIN_ROUNDROBIN_AUTHOR = '<a href="https://www.linkedin.com/in/richard-ti/" target="_blank">Richard Loureiro</a>';
    public static $PLUGIN_ROUNDROBIN_LICENSE = 'GPLv3';
    public static $PLUGIN_ROUNDROBIN_HOME_PAGE = 'https://www.initiativa.it/glpi.php';
    public static $PLUGIN_ROUNDROBIN_MIN_GLPI_VERSION = '9.5.2';
    public static $PLUGIN_ROUNDROBIN_GLPI_VERSION_ERROR = "Este plugin requer GLPI >= 9.5.2 ou GLPI <= 10.0.17";
    public static $PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION = '10.0.17';
    public static $PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION_ERROR = 'Este plugin requer ';
    public static $PLUGIN_ROUNDROBIN_MIN_PHP_VERSION = '7.3';

    public static function init() {
        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - definindo manipuladores de hooks');
        global $PLUGIN_HOOKS;
        $PLUGIN_HOOKS['csrf_compliant'][self::$PLUGIN_ROUNDROBIN_CODE] = true;
        /**
         * declarações de hooks
         */
        $PLUGIN_HOOKS['pre_item_add'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_pre_item_add_handler'
        ];
        $PLUGIN_HOOKS['item_add'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_item_add_handler',
            'ITILCategory' => 'plugin_roundrobin_hook_item_add_handler'
        ];

        $PLUGIN_HOOKS['item_update'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_item_update_handler'
        ];

        $PLUGIN_HOOKS['pre_item_delete'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_pre_item_delete_handler'
        ];

        $PLUGIN_HOOKS['item_delete'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_item_delete_handler',
            'ITILCategory' => 'plugin_roundrobin_hook_item_delete_handler'
        ];
        $PLUGIN_HOOKS['item_purge'][self::$PLUGIN_ROUNDROBIN_CODE] = [
            'Ticket' => 'plugin_roundrobin_hook_item_purge_handler',
            'ITILCategory' => 'plugin_roundrobin_hook_item_purge_handler'
        ];
    }

    public static function getVersion() {
        return [
            'name' => self::$PLUGIN_ROUNDROBIN_NAME,
            'version' => self::$PLUGIN_ROUNDROBIN_VERSION,
            'author' => self::$PLUGIN_ROUNDROBIN_AUTHOR,
            'license' => self::$PLUGIN_ROUNDROBIN_LICENSE,
            'homepage' => self::$PLUGIN_ROUNDROBIN_HOME_PAGE,
            'requirements' => [
                'glpi' => [
                    'min' => self::$PLUGIN_ROUNDROBIN_MIN_GLPI_VERSION,
                    'max' => self::$PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION
                ],
                'php' => [
                    'min' => self::$PLUGIN_ROUNDROBIN_MIN_PHP_VERSION
                ]
            ]
        ];
    }

    public static function loadSources() {
        global $PLUGIN_HOOKS;

        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - carregando recursos...');
        /**
         * adicionar seção de configuração
         */
        $PLUGIN_HOOKS['config_page'][self::$PLUGIN_ROUNDROBIN_CODE] = 'front/config.form.php';
    }

    public static function hookAddSource($uriArray, $hook, $sourceFile) {
        global $PLUGIN_HOOKS;

        /**
         * na página URI, carregue o código js ou estilos necessários
         */
        if (is_array($uriArray) === false) {
            throw new Exception("estrutura de URI inválida, esperado array");
        }
        foreach ($uriArray as $uri) {
            if (strpos(PluginRoundRobinRequest::getServerParam('REQUEST_URI'), $uri) !== false) {
                $PLUGIN_HOOKS[$hook][self::$PLUGIN_ROUNDROBIN_CODE] = $sourceFile;
                PluginRoundRobinLogger::addWarning(__METHOD__ . " - recurso $sourceFile carregado!");
                break;
            }
        }
    }

    public static function getRrAssignmentTable() {
        $pluginCode = self::$PLUGIN_ROUNDROBIN_CODE;
        return "glpi_plugin_" . $pluginCode . "_rr_assignments";
    }

    public static function getRrOptionsTable() {
        $pluginCode = self::$PLUGIN_ROUNDROBIN_CODE;
        return "glpi_plugin_" . $pluginCode . "_rr_options";
    }

}
