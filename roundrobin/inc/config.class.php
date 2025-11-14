<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI - Config class for GLPI 11
 * -------------------------------------------------------------------------
 */
class PluginRoundRobinConfig {

    public static $PLUGIN_ROUNDROBIN_ENV = 'development';
    public static $PLUGIN_ROUNDROBIN_NAME = 'Round Robin';
    public static $PLUGIN_ROUNDROBIN_CODE = 'roundrobin';
    public static $PLUGIN_ROUNDROBIN_VERSION = '2.0.0';
    public static $PLUGIN_ROUNDROBIN_AUTHOR = '<a href="https://www.initiativa.it/glpi.php" target="_blank">initiativa s.r.l.</a>';
    public static $PLUGIN_ROUNDROBIN_LICENSE = 'GPLv3';
    public static $PLUGIN_ROUNDROBIN_HOME_PAGE = 'https://github.com/initiativa/roundrobin/';
    public static $PLUGIN_ROUNDROBIN_MIN_GLPI_VERSION = '11.0.0';
    public static $PLUGIN_ROUNDROBIN_GLPI_VERSION_ERROR = "This plugin requires GLPI >= 11.0.0 and GLPI <= 11.0.99";
    public static $PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION = '11.0.99';
    public static $PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION_ERROR = 'This plugin requires ';
    public static $PLUGIN_ROUNDROBIN_MIN_PHP_VERSION = '8.1';

    public static function init() {
        global $PLUGIN_HOOKS;
        
        // Register hooks
        $PLUGIN_HOOKS['config_page']['roundrobin'] = 'front/config_simple.php';
        
        // Register item hooks
        $PLUGIN_HOOKS['pre_item_add']['roundrobin'] = [
            'Ticket' => 'plugin_roundrobin_hook_pre_item_add_handler'
        ];
        
        $PLUGIN_HOOKS['item_add']['roundrobin'] = [
            'Ticket' => 'plugin_roundrobin_hook_item_add_handler'
        ];
        
        $PLUGIN_HOOKS['item_add']['roundrobin']['ITILCategory'] = 'plugin_roundrobin_hook_itil_item_add_handler';
        $PLUGIN_HOOKS['item_delete']['roundrobin']['ITILCategory'] = 'plugin_roundrobin_hook_item_delete_handler';
        $PLUGIN_HOOKS['item_purge']['roundrobin']['ITILCategory'] = 'plugin_roundrobin_hook_item_purge_handler';
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

    public static function getRrAssignmentTable() {
        $pluginCode = self::$PLUGIN_ROUNDROBIN_CODE;
        return "glpi_plugin_" . $pluginCode . "_rr_assignments";
    }

    public static function getRrOptionsTable() {
        $pluginCode = self::$PLUGIN_ROUNDROBIN_CODE;
        return "glpi_plugin_" . $pluginCode . "_rr_options";
    }
}