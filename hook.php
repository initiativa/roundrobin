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
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/TicketHookHandler.class.php';
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/ITILCategoryHookHandler.class.php';
require_once PLUGIN_ROUNDROBIN_DIR . '/inc/RRAssignmentsEntity.class.php';

/**
 * Hook Item Handlers by Item Type
 */
function plugin_roundrobin_getHookHandlers() {
    $HOOK_HANDLERS = [
        'Ticket' => new PluginRoundRobinTicketHookHandler(),
        'ITILCategory' => new PluginRoundRobinITILCategoryHookHandler()
    ];
    return $HOOK_HANDLERS;
}

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_roundrobin_install() {
    global $DB;

    PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');
    $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();

    /**
     * create setting table
     */
    $rrAssignmentsEntity->init();
    return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_roundrobin_uninstall() {
    global $DB;
    /**
     * @todo removing tables, generated files, ...
     */
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . ' - entered...');
    $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
    /**
     * drop settings
     */
    $rrAssignmentsEntity->cleanUp();
    return true;
}

/**
 * hook handlers
 */

/**
 * pre item add
 */
function plugin_roundrobin_hook_pre_item_add_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    return $item;
}

/**
 * item added
 */
function plugin_roundrobin_hook_item_add_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    $HOOK_HANDLERS = plugin_roundrobin_getHookHandlers();
    if (array_key_exists($item->getType(), $HOOK_HANDLERS)) {
        $handler = $HOOK_HANDLERS[$item->getType()];
        $handler->itemAdded($item);
    }
    return $item;
}

/**
 * item updated
 */
function plugin_roundrobin_hook_item_update_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - Hook Hanlder: ITEM UPDATE: " . $item->getType());
    Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Hook Hanlder: ITEM UPDATE'), $item->getType()));
    return $item;
}

/**
 * pre item delete
 */
function plugin_roundrobin_hook_pre_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    return $item;
}

/**
 * item deleted
 */
function plugin_roundrobin_hook_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    $HOOK_HANDLERS = plugin_roundrobin_getHookHandlers();
    if (array_key_exists($item->getType(), $HOOK_HANDLERS)) {
        $handler = $HOOK_HANDLERS[$item->getType()];
        $handler->itemDeleted($item);
    }
    return $item;
}

/**
 * item purged
 */
function plugin_roundrobin_hook_item_purge_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addWarning(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    $HOOK_HANDLERS = plugin_roundrobin_getHookHandlers();
    if (array_key_exists($item->getType(), $HOOK_HANDLERS)) {
        $handler = $HOOK_HANDLERS[$item->getType()];
        $handler->itemPurged($item);
    }
    return $item;
}
