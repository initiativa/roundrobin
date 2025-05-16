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
if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}

require_once PLUGIN_ROUNDROBIN_DIR . '/inc/logger.class.php';
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/request.class.php';
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/config.class.php';

/**
 * Init the hooks of the plugins - Needed
 *
 * @return void
 */
function plugin_init_roundrobin() {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - plugin initialization');
    PluginRoundRobinConfig::init();
    PluginRoundRobinConfig::loadSources();
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_roundrobin() {
    return PluginRoundRobinConfig::getVersion();
}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *            (to disable the check, return always true)
 *
 * @return boolean
 */
function plugin_roundrobin_check_prerequisites() {
    /*
     * glpi version check
     */
    if (version_compare(GLPI_VERSION, PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_MIN_GLPI_VERSION, 'le') ||
            version_compare(GLPI_VERSION, PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_MAX_GLPI_VERSION, 'ge')) {
        PluginRoundRobinLogger::addCritical(__FUNCTION__ . ' - plugin prerequisites do not match: ' . PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_GLPI_VERSION_ERROR);
        if (method_exists('Plugin', 'messageIncompatible')) {
            Plugin::messageIncompatible('core', PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_GLPI_VERSION_ERROR);
        }
        return false;
    }
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - plugin CAN be installed AND activated');
    return true;
}

/**
 * Check configuration process for plugin : need to return true if succeeded
 * Can display a message only if failure and $verbose is true
 *
 * @param boolean $verbose Enable verbosity. Default to false
 *
 * @return boolean
 */
function plugin_roundrobin_check_config($verbose = false) {
    /**
     * @todo if needed add check behaviour
     */
    if (true) {
        return true;
    }

    if ($verbose) {
        echo "Installed, but not configured";
    }
    return false;
}
