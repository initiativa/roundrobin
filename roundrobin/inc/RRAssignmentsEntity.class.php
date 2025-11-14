<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI - Complete database entity for GLPI 11
 * -------------------------------------------------------------------------
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

if (!defined('PLUGIN_ROUNDROBIN_DIR')) {
    define('PLUGIN_ROUNDROBIN_DIR', __DIR__);
}

class PluginRoundRobinRRAssignmentsEntity extends CommonDBTM {

    protected $DB;
    protected $rrAssignmentTable;
    protected $rrOptionsTable;

    public function __construct() {
        global $DB;
        $this->DB = $DB;
        $this->rrAssignmentTable = "glpi_plugin_roundrobin_rr_assignments";
        $this->rrOptionsTable = "glpi_plugin_roundrobin_rr_options";
    }

    public function getOptionAutoAssignGroup() {
        try {
            $result = $this->DB->request([
                'FROM' => $this->rrOptionsTable,
                'LIMIT' => 1
            ]);
            
            if (count($result) > 0) {
                $row = $result->current();
                return (int)$row['auto_assign_group'];
            }
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getOptionAutoAssignGroup error: ' . $e->getMessage());
        }
        return 1; // default value
    }

    public function updateAutoAssignGroup($autoAssignGroup) {
        try {
            // First check if there's any row in the options table
            $result = $this->DB->request([
                'FROM' => $this->rrOptionsTable,
                'LIMIT' => 1
            ]);
            
            if (count($result) > 0) {
                // Update existing row
                $row = $result->current();
                $this->DB->update(
                    $this->rrOptionsTable,
                    ['auto_assign_group' => (int)$autoAssignGroup],
                    ['id' => $row['id']]
                );
            } else {
                // Insert new row if none exists
                $this->DB->insert(
                    $this->rrOptionsTable,
                    ['auto_assign_group' => (int)$autoAssignGroup]
                );
            }
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin updateAutoAssignGroup error: ' . $e->getMessage());
            return false;
        }
    }

    public function getGroupByItilCategory($itilCategory) {
        try {
            $result = $this->DB->request([
                'FROM' => 'glpi_itilcategories',
                'WHERE' => ['id' => (int)$itilCategory],
                'LIMIT' => 1
            ]);
            
            if (count($result) > 0) {
                $row = $result->current();
                return (int)$row['groups_id'] > 0 ? (int)$row['groups_id'] : false;
            }
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getGroupByItilCategory error: ' . $e->getMessage());
        }
        return false;
    }

    public function isCategoryActive($itilCategoryId) {
        try {
            $result = $this->DB->request([
                'FROM' => $this->rrAssignmentTable,
                'WHERE' => [
                    'itilcategories_id' => (int)$itilCategoryId,
                    'is_active' => 1
                ],
                'LIMIT' => 1
            ]);
            
            return count($result) > 0;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin isCategoryActive error: ' . $e->getMessage());
        }
        return false;
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        try {
            $this->DB->update(
                $this->rrAssignmentTable,
                ['is_active' => (int)$isActive],
                ['itilcategories_id' => (int)$itilcategoriesId]
            );
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin updateIsActive error: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastAssignmentIndex($itilcategoriesId) {
        try {
            $result = $this->DB->request([
                'FROM' => $this->rrAssignmentTable,
                'WHERE' => [
                    'itilcategories_id' => (int)$itilcategoriesId,
                    'is_active' => 1
                ],
                'LIMIT' => 1
            ]);
            
            if (count($result) > 0) {
                $row = $result->current();
                // Return null if never assigned, otherwise return the index
                return $row['last_assignment_index'] !== null ? (int)$row['last_assignment_index'] : null;
            }
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getLastAssignmentIndex error: ' . $e->getMessage());
        }
        return false;
    }
    
    public function getLastAssignmentIndexByGroup($groupId) {
        try {
            // Find all active categories that use this group
            $categories = $this->DB->request([
                'FROM' => 'glpi_itilcategories',
                'WHERE' => ['groups_id' => (int)$groupId]
            ]);
            
            $categoryIds = [];
            foreach ($categories as $cat) {
                $categoryIds[] = $cat['id'];
            }
            
            if (empty($categoryIds)) {
                return null;
            }
            
            // Get the maximum last_assignment_index from all these categories
            $result = $this->DB->request([
                'FROM' => $this->rrAssignmentTable,
                'WHERE' => [
                    'itilcategories_id' => $categoryIds,
                    'is_active' => 1
                ]
            ]);
            
            $maxIndex = null;
            foreach ($result as $row) {
                if ($row['last_assignment_index'] !== null) {
                    if ($maxIndex === null || (int)$row['last_assignment_index'] > $maxIndex) {
                        $maxIndex = (int)$row['last_assignment_index'];
                    }
                }
            }
            
            return $maxIndex;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getLastAssignmentIndexByGroup error: ' . $e->getMessage());
        }
        return null;
    }

    public function updateLastAssignmentIndex($itilcategoriesId, $index) {
        try {
            $this->DB->update(
                $this->rrAssignmentTable,
                ['last_assignment_index' => (int)$index],
                ['itilcategories_id' => (int)$itilcategoriesId]
            );
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin updateLastAssignmentIndex error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastAssignmentIndexByGroup($groupId, $index) {
        try {
            // Find all categories that use this group
            $categories = $this->DB->request([
                'FROM' => 'glpi_itilcategories',
                'WHERE' => ['groups_id' => (int)$groupId]
            ]);
            
            $categoryIds = [];
            foreach ($categories as $cat) {
                $categoryIds[] = $cat['id'];
            }
            
            if (empty($categoryIds)) {
                return false;
            }
            
            // Update ALL categories using this group with the same index
            // This keeps the rotation synchronized across categories
            $this->DB->update(
                $this->rrAssignmentTable,
                ['last_assignment_index' => (int)$index],
                ['itilcategories_id' => $categoryIds]
            );
            
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin updateLastAssignmentIndexByGroup error: ' . $e->getMessage());
            return false;
        }
    }

    public function getGroupMembers($groupId) {
        try {
            $result = $this->DB->request([
                'FROM' => 'glpi_groups_users',
                'WHERE' => ['groups_id' => (int)$groupId],
                'ORDER' => 'users_id ASC' // Consistent ordering for round-robin
            ]);
            
            $members = [];
            foreach ($result as $row) {
                $members[] = [
                    'users_id' => (int)$row['users_id'],
                    'groups_id' => (int)$row['groups_id']
                ];
            }
            return $members;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getGroupMembers error: ' . $e->getMessage());
            return [];
        }
    }

    public function addCategory($itilCategoryId) {
        try {
            // Check if category already exists
            $result = $this->DB->request([
                'FROM' => $this->rrAssignmentTable,
                'WHERE' => ['itilcategories_id' => (int)$itilCategoryId],
                'LIMIT' => 1
            ]);
            
            if (count($result) === 0) {
                $this->DB->insert($this->rrAssignmentTable, [
                    'itilcategories_id' => (int)$itilCategoryId,
                    'is_active' => 0
                ]);
            }
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin addCategory error: ' . $e->getMessage());
            return false;
        }
    }

    public function removeCategory($itilCategoryId) {
        try {
            $this->DB->delete(
                $this->rrAssignmentTable,
                ['itilcategories_id' => (int)$itilCategoryId]
            );
            return true;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin removeCategory error: ' . $e->getMessage());
            return false;
        }
    }

    public function getAll() {
        try {
            // Get all assignments
            $assignments = $this->DB->request([
                'FROM' => $this->rrAssignmentTable,
                'ORDER' => 'id'
            ]);
            
            $resultArray = [];
            foreach ($assignments as $assignment) {
                $row = [
                    'id' => (int)$assignment['id'],
                    'itilcategories_id' => (int)$assignment['itilcategories_id'],
                    'category_name' => '',
                    'groups_id' => 0,
                    'group_name' => '',
                    'num_group_members' => 0,
                    'is_active' => (int)$assignment['is_active']
                ];
                
                // Get category info
                $category = $this->DB->request([
                    'FROM' => 'glpi_itilcategories',
                    'WHERE' => ['id' => $assignment['itilcategories_id']],
                    'LIMIT' => 1
                ]);
                
                if (count($category) > 0) {
                    $cat = $category->current();
                    $row['category_name'] = $cat['completename'];
                    $row['groups_id'] = (int)$cat['groups_id'];
                    
                    if ($cat['groups_id'] > 0) {
                        // Get group info
                        $group = $this->DB->request([
                            'FROM' => 'glpi_groups',
                            'WHERE' => ['id' => $cat['groups_id']],
                            'LIMIT' => 1
                        ]);
                        
                        if (count($group) > 0) {
                            $grp = $group->current();
                            $row['group_name'] = $grp['completename'];
                            
                            // Get member count
                            $members = $this->getGroupMembers($cat['groups_id']);
                            $row['num_group_members'] = count($members);
                        }
                    }
                }
                
                $resultArray[] = $row;
            }
            
            return $resultArray;
        } catch (Exception $e) {
            Toolbox::logInFile('php-errors', 'RoundRobin getAll error: ' . $e->getMessage());
            return [];
        }
    }
}
