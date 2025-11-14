<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI 11 - PRODUCTION READY
 * -------------------------------------------------------------------------
 * @license GPLv3
 */

if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}

/**
 * Init the hooks of the plugins - Needed
 */
function plugin_init_roundrobin() {
    global $PLUGIN_HOOKS;
    
    // Register config page
    $PLUGIN_HOOKS['config_page']['roundrobin'] = 'front/config.form.php';
    
    // Register ticket creation hook - GLPI 11 format
    $PLUGIN_HOOKS['pre_item_add']['roundrobin'] = [
        'Ticket' => 'plugin_roundrobin_pre_item_add_ticket'
    ];
    
    // Register ITIL category hooks for automatic sync
    $PLUGIN_HOOKS['item_add']['roundrobin'] = [
        'ITILCategory' => 'plugin_roundrobin_item_add_category'
    ];
    
    $PLUGIN_HOOKS['item_purge']['roundrobin'] = [
        'ITILCategory' => 'plugin_roundrobin_item_purge_category'
    ];
    
    return true;
}

/**
 * Get the name and the version of the plugin - Needed
 */
function plugin_version_roundrobin() {
    return [
        'name' => 'Round Robin',
        'version' => '2.1.0',
        'author' => 'initiativa s.r.l.',
        'license' => 'GPLv3',
        'homepage' => 'https://github.com/initiativa/roundrobin/',
        'requirements' => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '11.0.99'
            ],
            'php' => [
                'min' => '8.1'
            ]
        ]
    ];
}

/**
 * Check prerequisites before install
 * GLPI automatically checks version requirements from plugin_version_roundrobin()
 */
function plugin_roundrobin_check_prerequisites() {
    // Check PHP version
    $version = plugin_version_roundrobin();
    if (version_compare(PHP_VERSION, $version['requirements']['php']['min'], '<')) {
        echo "This plugin requires PHP >= " . $version['requirements']['php']['min'];
        return false;
    }
    
    // GLPI version check is handled automatically by GLPI from the requirements array
    return true;
}

/**
 * Check configuration process for plugin
 */
function plugin_roundrobin_check_config($verbose = false) {
    return true;
}

/**
 * Install hook - Create database tables
 */
function plugin_install_roundrobin() {
    global $DB;
    
    $migration = new Migration(210);
    $pluginCode = 'roundrobin';
    $rrAssignmentTable = "glpi_plugin_{$pluginCode}_rr_assignments";
    $rrOptionsTable = "glpi_plugin_{$pluginCode}_rr_options";
    
    // Create assignment table
    if (!$DB->tableExists($rrAssignmentTable)) {
        $query = "CREATE TABLE `{$rrAssignmentTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `itilcategories_id` int unsigned NOT NULL,
            `is_active` tinyint NOT NULL DEFAULT 0,
            `last_assignment_index` int DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ix_itilcategories_uq` (`itilcategories_id`),
            KEY `ix_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->doQueryOrDie($query, "Error creating {$rrAssignmentTable}");
    }
    
    // Create options table
    if (!$DB->tableExists($rrOptionsTable)) {
        $query = "CREATE TABLE `{$rrOptionsTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `auto_assign_group` tinyint NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        
        $DB->doQueryOrDie($query, "Error creating {$rrOptionsTable}");
        
        // Insert default option
        $DB->insert($rrOptionsTable, ['auto_assign_group' => 1]);
    }
    
    // Populate with existing ITIL categories
    $categories = $DB->request([
        'SELECT' => ['id'],
        'FROM' => 'glpi_itilcategories'
    ]);
    
    foreach ($categories as $category) {
        $exists = $DB->request([
            'FROM' => $rrAssignmentTable,
            'WHERE' => ['itilcategories_id' => $category['id']],
            'LIMIT' => 1
        ]);
        
        if (count($exists) === 0) {
            $DB->insert($rrAssignmentTable, [
                'itilcategories_id' => $category['id'],
                'is_active' => 0
            ]);
        }
    }
    
    return true;
}

/**
 * Uninstall hook - Clean up database
 */
function plugin_uninstall_roundrobin() {
    global $DB;
    
    $pluginCode = 'roundrobin';
    $rrAssignmentTable = "glpi_plugin_{$pluginCode}_rr_assignments";
    $rrOptionsTable = "glpi_plugin_{$pluginCode}_rr_options";
    
    // Drop tables if they exist
    if ($DB->tableExists($rrAssignmentTable)) {
        $DB->doQueryOrDie("DROP TABLE `{$rrAssignmentTable}`", "Error dropping {$rrAssignmentTable}");
    }
    
    if ($DB->tableExists($rrOptionsTable)) {
        $DB->doQueryOrDie("DROP TABLE `{$rrOptionsTable}`", "Error dropping {$rrOptionsTable}");
    }
    
    return true;
}

// Alternative function names for backward compatibility
function plugin_roundrobin_install() {
    return plugin_install_roundrobin();
}

function plugin_roundrobin_uninstall() {
    return plugin_uninstall_roundrobin();
}