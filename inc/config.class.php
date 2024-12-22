<?php
// Antigo PluginTicketBalanceConfig ou PluginRoundRobinConfig
class TicketBalanceConfigClass {

    public static $PLUGIN_TICKETBALANCE_ENV = 'desenvolvimento';
    public static $PLUGIN_TICKETBALANCE_NAME = 'Ticket Balance';
    public static $PLUGIN_TICKETBALANCE_CODE = 'ticketbalance';
    public static $PLUGIN_TICKETBALANCE_VERSION = '1.0.2';
    public static $PLUGIN_TICKETBALANCE_AUTHOR = 'Richard Loureiro';
    public static $PLUGIN_TICKETBALANCE_LICENSE = 'GPLv3';
    public static $PLUGIN_TICKETBALANCE_HOME_PAGE = 'https://www.linkedin.com/in/richard-ti/';
    public static $PLUGIN_TICKETBALANCE_MIN_GLPI_VERSION = '9.5.2';
    public static $PLUGIN_TICKETBALANCE_GLPI_VERSION_ERROR = "Este plugin requer GLPI >= 9.5.2 e GLPI <= 11.0.0";
    public static $PLUGIN_TICKETBALANCE_MAX_GLPI_VERSION = '11.0.0';
    public static $PLUGIN_TICKETBALANCE_MIN_PHP_VERSION = '7.3';

    public static function init() {
        PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - definindo manipuladores de hooks');
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS['csrf_compliant'][self::$PLUGIN_TICKETBALANCE_CODE] = true;

        // Declaração de hooks
        $PLUGIN_HOOKS['pre_item_add'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_pre_item_add_handler'
        ];
        $PLUGIN_HOOKS['item_add'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_item_add_handler',
            'ITILCategory' => 'plugin_ticketbalance_hook_item_add_handler'
        ];
        $PLUGIN_HOOKS['item_update'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_item_update_handler'
        ];
        $PLUGIN_HOOKS['pre_item_delete'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_pre_item_delete_handler'
        ];
        $PLUGIN_HOOKS['item_delete'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_item_delete_handler',
            'ITILCategory' => 'plugin_ticketbalance_hook_item_delete_handler'
        ];
        $PLUGIN_HOOKS['item_purge'][self::$PLUGIN_TICKETBALANCE_CODE] = [
            'Ticket' => 'plugin_ticketbalance_hook_item_purge_handler',
            'ITILCategory' => 'plugin_ticketbalance_hook_item_purge_handler'
        ];
    }

    public static function getVersion() {
        return [
            'name' => self::$PLUGIN_TICKETBALANCE_NAME,
            'version' => self::$PLUGIN_TICKETBALANCE_VERSION,
            'author' => self::$PLUGIN_TICKETBALANCE_AUTHOR,
            'license' => self::$PLUGIN_TICKETBALANCE_LICENSE,
            'homepage' => self::$PLUGIN_TICKETBALANCE_HOME_PAGE,
            'requirements' => [
                'glpi' => [
                    'min' => self::$PLUGIN_TICKETBALANCE_MIN_GLPI_VERSION,
                    'max' => self::$PLUGIN_TICKETBALANCE_MAX_GLPI_VERSION
                ],
                'php' => [
                    'min' => self::$PLUGIN_TICKETBALANCE_MIN_PHP_VERSION
                ]
            ]
        ];
    }

    public static function loadSources() {
        global $PLUGIN_HOOKS;

        PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - carregando fontes...');
        $PLUGIN_HOOKS['config_page'][self::$PLUGIN_TICKETBALANCE_CODE] = 'front/config.form.php';
    }

    public static function hookAddSource($uriArray, $hook, $sourceFile) {
        global $PLUGIN_HOOKS;

        if (!is_array($uriArray)) {
            throw new InvalidArgumentException("Estrutura de URI inválida, esperado array.");
        }
        foreach ($uriArray as $uri) {
            if (strpos(PluginTicketBalanceRequest::getServerParam('REQUEST_URI'), $uri) !== false) {
                $PLUGIN_HOOKS[$hook][self::$PLUGIN_TICKETBALANCE_CODE] = $sourceFile;
                PluginTicketBalanceLogger::addWarning(__METHOD__ . " - recurso $sourceFile carregado!");
                break;
            }
        }
    }

    public static function getRrAssignmentTable() {
        $pluginCode = self::$PLUGIN_TICKETBALANCE_CODE;
        return "glpi_plugin_" . $pluginCode . "_assignments";
    }

    public static function getRrOptionsTable() {
        $pluginCode = self::$PLUGIN_TICKETBALANCE_CODE;
        return "glpi_plugin_" . $pluginCode . "_options";
    }
	
	// Define o nome do menu.
    static function getMenuName() {
        return __('Ticket Balance');
    }

    // Define o conteúdo do menu.
    static function getMenuContent() {
        global $CFG_GLPI;
        $menu = [];
        $menu['title'] = __('Ticket Balance');
        $menu['page']  = $CFG_GLPI['root_doc'] . "/plugins/ticketbalance/front/config.form.php";
		$menu['icon']  = 'fas fa-user-check';
        return $menu;
    }
}