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
 * Merge RoundRobin assignee into Ticket input (GLPI 11 `_actors`).
 *
 * @param array<string,mixed> $input
 * @param array{user_id:int, group_id:int|null} $assignmentData
 * @return array<string,mixed>
 */
function plugin_roundrobin_merge_rr_into_ticket_input(array $input, array $assignmentData): array {
    if (isset($input['_users_id_assign'])) {
        unset($input['_users_id_assign']);
    }
    if (isset($input['_groups_id_assign'])) {
        unset($input['_groups_id_assign']);
    }
    if (!isset($input['_actors'])) {
        $input['_actors'] = [];
    }
    if (!isset($input['_actors']['assign'])) {
        $input['_actors']['assign'] = [];
    }
    $input['_actors']['assign'][] = [
        'itemtype'         => 'User',
        'items_id'         => (int) $assignmentData['user_id'],
        'use_notification' => 1,
    ];
    $gid = $assignmentData['group_id'] ?? null;
    if ($gid !== null && (int) $gid > 0) {
        $input['_actors']['assign'][] = [
            'itemtype'         => 'Group',
            'items_id'         => (int) $gid,
            'use_notification' => 1,
        ];
    }

    return $input;
}

/**
 * Assign a round-robin technician directly (actor tables) when the ticket has a
 * category but no assignee yet. Used for tickets created without the category in
 * the initial input (e.g. mail receiver + business rules).
 *
 * @return bool True when an assignee was applied
 */
function plugin_roundrobin_assign_ticket_direct(int $ticketId, int $categoryId): bool {
    if ($ticketId < 1 || $categoryId < 1) {
        return false;
    }

    $ticket = new Ticket();
    if (!$ticket->getFromDB($ticketId)) {
        return false;
    }

    $assignType = CommonITILActor::ASSIGN;
    if ((int) $ticket->countUsers($assignType) > 0) {
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - skip: ticket #' . $ticketId . ' already has assignee(s)');

        return false;
    }

    $handler = new PluginRoundRobinTicketHookHandler();
    $assignmentData = $handler->findUserIdToAssign($categoryId, true);
    if ($assignmentData === null) {
        return false;
    }

    $ticketUser = new Ticket_User();
    $added = $ticketUser->add([
        'tickets_id'       => $ticketId,
        'users_id'         => (int) $assignmentData['user_id'],
        'type'             => $assignType,
        'use_notification' => 1,
    ]);
    if (!$added) {
        PluginRoundRobinLogger::addError(__FUNCTION__ . ' - failed to add assignee to ticket #' . $ticketId);

        return false;
    }

    $gid = $assignmentData['group_id'] ?? null;
    if ($gid !== null && (int) $gid > 0 && (int) $ticket->countGroups($assignType) === 0) {
        $groupTicket = new Group_Ticket();
        $groupTicket->add([
            'tickets_id' => $ticketId,
            'groups_id'  => (int) $gid,
            'type'       => $assignType,
        ]);
    }

    // Move the ticket out of "New" if GLPI did not recompute the status itself
    if ($ticket->getFromDB($ticketId) && (int) $ticket->fields['status'] === Ticket::INCOMING) {
        $ticket->update([
            'id'     => $ticketId,
            'status' => Ticket::ASSIGNED,
        ]);
    }

    PluginRoundRobinLogger::addDebug(
        __FUNCTION__ . ' - assigned user ' . $assignmentData['user_id'] . ' to ticket #' . $ticketId
    );

    return true;
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
 * Upgrade hook — required so GLPI updates `glpi_plugins.version` when code is newer than the DB.
 *
 * @param string $currentversion Previously installed version from the database.
 *
 * @return boolean
 */
function plugin_roundrobin_upgrade($currentversion) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - from version: ' . (string) $currentversion);

    try {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
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
            $item->input = plugin_roundrobin_merge_rr_into_ticket_input($item->input, $assignmentData);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . " - assigned user: " . $assignmentData['user_id']);
        }
    }

    return true;
}

/**
 * After ticket creation — covers mail/receiver tickets whose category is applied
 * by business rules during add (so it was missing from pre_item_add input).
 */
function plugin_roundrobin_hook_item_add_handler(CommonDBTM $item) {
    if ($item->getType() !== 'Ticket') {
        return $item;
    }

    $ticketId = (int) ($item->fields['id'] ?? 0);
    $categoryId = (int) ($item->fields['itilcategories_id'] ?? 0);
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - ticket #' . $ticketId . ' category ' . $categoryId);

    plugin_roundrobin_assign_ticket_direct($ticketId, $categoryId);

    return $item;
}

function plugin_roundrobin_hook_itil_item_add_handler(ITILCategory $category) {
    PluginRoundRobinLogger::addDebug(
        __FUNCTION__ . ' - ITILCategory id=' . (int) ($category->fields['id'] ?? 0)
        . ' name=' . ($category->fields['name'] ?? '')
    );
    $handler = new PluginRoundRobinITILCategoryHookHandler();
    $handler->itemAdded($category);
    return $category;
}

/**
 * When a ticket's ITIL category changes, apply Round Robin if the new category is enabled
 * and the ticket has no existing assignee.
 */
function plugin_roundrobin_hook_pre_item_update_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - entered');
    if ($item->getType() !== 'Ticket') {
        return;
    }
    if (!isset($item->input['itilcategories_id'])) {
        return;
    }
    $newCat = (int) $item->input['itilcategories_id'];
    $oldCat = (int) ($item->fields['itilcategories_id'] ?? 0);
    if ($newCat < 1 || $newCat === $oldCat) {
        return;
    }

    $ticketId = (int) ($item->fields['id'] ?? 0);
    if ($ticketId > 0 && class_exists('Ticket')) {
        $t = new Ticket();
        if ($t->getFromDB($ticketId) && method_exists($t, 'countUsers')) {
            $assignType = defined('CommonITILActor::ASSIGN') ? CommonITILActor::ASSIGN : 2;
            if ((int) $t->countUsers($assignType) > 0) {
                PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - skip: ticket already has assignee(s)');

                return;
            }
        }
    }

    $handler = new PluginRoundRobinTicketHookHandler();
    $assignmentData = $handler->findUserIdToAssign($newCat, true);
    if ($assignmentData === null) {
        return;
    }

    $item->input = plugin_roundrobin_merge_rr_into_ticket_input($item->input, $assignmentData);
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - assigned user: ' . $assignmentData['user_id']);
}

/**
 * ITIL category updated: ensure row exists after restore; reset group rotation when group changes.
 */
function plugin_roundrobin_hook_itil_item_update_handler(CommonDBTM $item) {
    if ($item->getType() !== 'ITILCategory') {
        return $item;
    }

    $entity = new PluginRoundRobinRRAssignmentsEntity();
    $cid = (int) ($item->fields['id'] ?? 0);
    if ($cid < 1) {
        return $item;
    }

    if (isset($item->input['is_deleted']) && (int) $item->input['is_deleted'] === 0) {
        $entity->insertItilCategory($cid);
        PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - restored category id=' . $cid);

        return $item;
    }

    if (isset($item->input['groups_id'])) {
        $oldG = (int) ($item->fields['groups_id'] ?? 0);
        $newG = (int) $item->input['groups_id'];
        if ($newG > 0 && $newG !== $oldG) {
            $entity->resetGroupRotationIndex($newG);
            PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - groups_id changed for category id=' . $cid . ' new_group=' . $newG);
        }
    }

    return $item;
}

function plugin_roundrobin_hook_item_pre_update_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - item type: ' . $item->getType() . ' id=' . (int) ($item->fields['id'] ?? 0));
    return $item;
}


/**
 * After ticket update — fallback when rules set/changed the category during an
 * update and the pre_item_update `_actors` merge was not applied.
 */
function plugin_roundrobin_hook_ticket_item_update_handler(CommonDBTM $item) {
    if ($item->getType() !== 'Ticket') {
        return $item;
    }
    // Only act when the category actually changed in this update
    if (!isset($item->oldvalues['itilcategories_id'])) {
        return $item;
    }

    $newCat = (int) ($item->fields['itilcategories_id'] ?? 0);
    if ($newCat < 1) {
        return $item;
    }

    $ticketId = (int) ($item->fields['id'] ?? 0);
    plugin_roundrobin_assign_ticket_direct($ticketId, $newCat);

    return $item;
}

/**
 * pre item delete
 */
function plugin_roundrobin_hook_pre_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - item type: ' . $item->getType() . ' id=' . (int) ($item->fields['id'] ?? 0));
    return $item;
}

/**
 * item deleted
 */
function plugin_roundrobin_hook_item_delete_handler(CommonDBTM $item) {
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - item type: ' . $item->getType() . ' id=' . (int) ($item->fields['id'] ?? 0));
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
    PluginRoundRobinLogger::addDebug(__FUNCTION__ . ' - item type: ' . $item->getType() . ' id=' . (int) ($item->fields['id'] ?? 0));
    $HOOK_HANDLERS = plugin_roundrobin_getHookHandlers();
    if (array_key_exists($item->getType(), $HOOK_HANDLERS)) {
        $handler = $HOOK_HANDLERS[$item->getType()];
        $handler->itemPurged($item);
    }
    return $item;
}
