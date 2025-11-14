<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI 11 - Hook Handlers
 * -------------------------------------------------------------------------
 * @license GPLv3
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}

require_once PLUGIN_ROUNDROBIN_DIR . '/inc/RRAssignmentsEntity.class.php';

/**
 * Hook: pre_item_add for Ticket
 * Automatically assigns tickets to technicians in round-robin fashion
 * 
 * @param Ticket $item The ticket object being created
 * @return void
 */
function plugin_roundrobin_pre_item_add_ticket($item) {
    global $DB;
    
    try {
        // Only process if there's a category
        if (!isset($item->input['itilcategories_id']) || empty($item->input['itilcategories_id'])) {
            return;
        }
        
        $categoryId = (int)$item->input['itilcategories_id'];
        
        // Initialize database entity
        $rrEntity = new PluginRoundRobinRRAssignmentsEntity();
        
        // Check if round-robin is active for this category
        if (!$rrEntity->isCategoryActive($categoryId)) {
            return;
        }
        
        // Get the group associated with this ITIL category
        $groupId = $rrEntity->getGroupByItilCategory($categoryId);
        
        if (!$groupId) {
            return;
        }
        
        // Get group members
        $members = $rrEntity->getGroupMembers($groupId);
        
        if (empty($members)) {
            return;
        }
        
        // Filter out inactive users
        $activeMembers = [];
        foreach ($members as $member) {
            $user = $DB->request([
                'FROM' => 'glpi_users',
                'WHERE' => [
                    'id' => $member['users_id'],
                    'is_active' => 1,
                    'is_deleted' => 0
                ],
                'LIMIT' => 1
            ]);
            
            if (count($user) > 0) {
                $activeMembers[] = $member;
            }
        }
        
        if (empty($activeMembers)) {
            return;
        }
        
        // Get last assignment index for this GROUP (not just this category)
        // This ensures fair distribution across all categories using the same group
        $lastIndex = $rrEntity->getLastAssignmentIndexByGroup($groupId);
        
        // Calculate next index (round-robin)
        if ($lastIndex === false || $lastIndex === null) {
            $nextIndex = 0;
        } else {
            $nextIndex = ($lastIndex + 1) % count($activeMembers);
        }
        
        // Get the next user to assign
        $assignedMember = $activeMembers[$nextIndex];
        $assignedUserId = $assignedMember['users_id'];
        
        // Update the last assignment index for ALL categories using this group
        $rrEntity->updateLastAssignmentIndexByGroup($groupId, $nextIndex);
        
        // Assign the user - GLPI 11 format using _actors array
        if (!isset($item->input['_actors'])) {
            $item->input['_actors'] = [];
        }
        
        if (!isset($item->input['_actors']['assign'])) {
            $item->input['_actors']['assign'] = [];
        }
        
        // Add user to assignment
        $item->input['_actors']['assign'][] = [
            'itemtype' => 'User',
            'items_id' => $assignedUserId,
            'use_notification' => 1
        ];
        
        // Optionally assign the group as well
        $autoAssignGroup = $rrEntity->getOptionAutoAssignGroup();
        
        if ($autoAssignGroup) {
            $item->input['_actors']['assign'][] = [
                'itemtype' => 'Group',
                'items_id' => $groupId,
                'use_notification' => 1
            ];
        }
        
        // Log the assignment for debugging
        if (defined('GLPI_LOG_DIR')) {
            Toolbox::logInFile('roundrobin', "Assigned ticket to User #{$assignedUserId} from Group #{$groupId} for Category #{$categoryId}");
        }
        
    } catch (Exception $e) {
        Toolbox::logInFile('php-errors', "RoundRobin pre_item_add error: " . $e->getMessage());
    }
}

/**
 * Hook: item_add for ITILCategory
 * Automatically adds new categories to the round-robin configuration
 * 
 * @param ITILCategory $item The category being added
 * @return void
 */
function plugin_roundrobin_item_add_category($item) {
    try {
        if ($item instanceof ITILCategory) {
            $rrEntity = new PluginRoundRobinRRAssignmentsEntity();
            $rrEntity->addCategory($item->getID());
        }
    } catch (Exception $e) {
        Toolbox::logInFile('php-errors', "RoundRobin item_add_category error: " . $e->getMessage());
    }
}

/**
 * Hook: item_purge for ITILCategory
 * Removes purged categories from the round-robin configuration
 * 
 * @param ITILCategory $item The category being purged
 * @return void
 */
function plugin_roundrobin_item_purge_category($item) {
    try {
        if ($item instanceof ITILCategory) {
            $rrEntity = new PluginRoundRobinRRAssignmentsEntity();
            $rrEntity->removeCategory($item->getID());
        }
    } catch (Exception $e) {
        Toolbox::logInFile('php-errors', "RoundRobin item_purge_category error: " . $e->getMessage());
    }
}
