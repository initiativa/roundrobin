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

// Load dependencies in correct order using __DIR__
require_once __DIR__ . '/inc/logger.class.php';
require_once __DIR__ . '/inc/config.class.php';
require_once __DIR__ . '/inc/RRAssignmentsEntity.class.php';
require_once __DIR__ . '/inc/IHookItemHandler.php';
require_once __DIR__ . '/inc/TicketHookHandler.class.php';
require_once __DIR__ . '/inc/ITILCategoryHookHandler.class.php';

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

    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');
    
    try {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        /**
         * create setting table
         */
        $rrAssignmentsEntity->init();
        return true;
    } catch (Exception $e) {
        PluginRoundRobinLogger::addError(__FUNCTION__ . ' - Error: ' . $e->getMessage());
        return false;
    }
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
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered...');
    
    try {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        /**
         * drop settings
         */
        $rrAssignmentsEntity->cleanUp();
        return true;
    } catch (Exception $e) {
        PluginRoundRobinLogger::addError(__FUNCTION__ . ' - Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * hook handlers
 */

/**
 * pre item add - GLPI 11 compatible
 * Uses _actors array for ticket assignment
 */
function plugin_roundrobin_hook_pre_item_add_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - pre add item.");
    if ($item->getType() !== 'Ticket') {
        return true;
    }

    $categoryId = isset($item->input['itilcategories_id']) ? $item->input['itilcategories_id'] : null;
    if ($categoryId !== null && $categoryId > 0) {
        $handler = new PluginRoundRobinTicketHookHandler();
        $assignmentData = $handler->findUserIdToAssign($categoryId, true);
        
        if ($assignmentData !== null) {
            $input = $item->input;

            // Clear any existing assignment in input
            if (isset($input['_users_id_assign'])) {
                unset($input['_users_id_assign']);
            }

            if (isset($input['_groups_id_assign'])) {
                unset($input['_groups_id_assign']);
            }

            // For GLPI 11, use _actors array
            if (!isset($input['_actors'])) {
                $input['_actors'] = [];
            }
            if (!isset($input['_actors']['assign'])) {
                $input['_actors']['assign'] = [];
            }

            // Add user assignment
            $input['_actors']['assign'][] = [
                'itemtype' => 'User',
                'items_id' => $assignmentData['user_id'],
                'use_notification' => 1
            ];

            // Add group assignment if enabled
            if ($assignmentData['group_id'] !== null) {
                $input['_actors']['assign'][] = [
                    'itemtype' => 'Group',
                    'items_id' => $assignmentData['group_id'],
                    'use_notification' => 1
                ];
            }

            $item->input = $input;
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - assigned user: " . $assignmentData['user_id']);
        }
    }

    return true;
}

/**
 * ticket added - GLPI 11: assignment already done in pre_item_add via _actors
 */
function plugin_roundrobin_hook_item_add_handler(Ticket $ticket) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - entered with item: " . print_r($ticket->getType(), true));
    // In GLPI 11, the assignment is already done via _actors in pre_item_add
    // This hook is kept for logging/compatibility
    return $ticket;
}

function plugin_roundrobin_hook_itil_item_add_handler(ITILCategory $category) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . print_r($category, true));
    $handler = new PluginRoundRobinITILCategoryHookHandler();
    $handler->itemAdded($category);
    return $category;
}

function plugin_roundrobin_hook_item_pre_update_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - pre update item: " . print_r($item, true));
    return $item;
}


/**
 * item updated
 */
function plugin_roundrobin_hook_item_update_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Hook Hanlder: ITEM UPDATE'), $item->getType()));
    return $item;
}

/**
 * pre item delete
 */
function plugin_roundrobin_hook_pre_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    return $item;
}

/**
 * item deleted
 */
function plugin_roundrobin_hook_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug( __FUNCTION__ . " - item: " . print_r($item, true));
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
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - entered with item: " . print_r($item, true));
    $HOOK_HANDLERS = plugin_roundrobin_getHookHandlers();
    if (array_key_exists($item->getType(), $HOOK_HANDLERS)) {
        $handler = $HOOK_HANDLERS[$item->getType()];
        $handler->itemPurged($item);
    }
    return $item;
}
