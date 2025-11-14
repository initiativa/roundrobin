<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI 11 - Config Page
 * -------------------------------------------------------------------------
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

require_once '../inc/RRAssignmentsEntity.class.php';

// Check permissions
Session::checkRight("config", UPDATE);

// Handle form submission BEFORE header
if (isset($_POST['save'])) {
    $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
    
    // Update auto assign group setting
    $autoAssign = isset($_POST['auto_assign_group']) ? (int)$_POST['auto_assign_group'] : 1;
    $rrAssignmentsEntity->updateAutoAssignGroup($autoAssign);
    
    // Update category settings
    $settings = $rrAssignmentsEntity->getAll();
    foreach ($settings as $row) {
        $fieldName = "is_active_{$row['id']}";
        if (isset($_POST[$fieldName])) {
            $newValue = (int)$_POST[$fieldName];
            $rrAssignmentsEntity->updateIsActive($row['itilcategories_id'], $newValue);
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1');
    exit;
}

Html::header('RoundRobin Settings', $_SERVER['PHP_SELF'], "plugins", "roundrobin", "config");

// Show success message if redirected after save
if (isset($_GET['saved'])) {
    echo '<div class="center"><div class="alert alert-success">Configuration saved successfully!</div></div>';
}

// Display content
$rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
$auto_assign_group = $rrAssignmentsEntity->getOptionAutoAssignGroup();
$settings = $rrAssignmentsEntity->getAll();

echo '<div class="center">';
echo '<h1>RoundRobin Settings</h1>';

echo '<form name="settingsForm" method="post">';
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

echo '<table class="tab_cadre_fixe">';
echo '<tr><th colspan="4">Enable Group Round Robin Ticket Assignment for each ITILCategory</th></tr>';
echo '<tr><th colspan="4"><hr/></th></tr>';

echo '<tr><th colspan="4">';
echo 'Assign also to the original Group:&nbsp;&nbsp;';
echo '<input type="radio" name="auto_assign_group" value="1"' . ($auto_assign_group ? ' checked="checked"' : '') . '> Yes&nbsp;&nbsp;';
echo '<input type="radio" name="auto_assign_group" value="0"' . (!$auto_assign_group ? ' checked="checked"' : '') . '> No';
echo '</th></tr>';

echo '<tr><th colspan="4"><hr/></th></tr>';
echo '<tr><th>ITILCategory</th><th>Group</th><th>Members #</th><th>Settings</th></tr>';

if (empty($settings)) {
    echo '<tr><td colspan="4" class="center"><em>No ITIL categories found. Please create some categories first.</em></td></tr>';
} else {
    foreach ($settings as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . ($row['group_name'] ? htmlspecialchars($row['group_name'], ENT_QUOTES, 'UTF-8') : '<em>No group assigned</em>') . '</td>';
        echo '<td class="center">' . ($row['num_group_members'] > 0 ? $row['num_group_members'] : '<em>N/A</em>') . '</td>';
        echo '<td>';
        echo '<input type="radio" name="is_active_' . $row['id'] . '" value="1"' . ($row['is_active'] ? ' checked="checked"' : '') . '> Enabled&nbsp;&nbsp;';
        echo '<input type="radio" name="is_active_' . $row['id'] . '" value="0"' . (!$row['is_active'] ? ' checked="checked"' : '') . '> Disabled';
        echo '</td>';
        echo '</tr>';
    }
}

echo '<tr><td colspan="4"><hr/></td></tr>';
echo '<tr><td colspan="3">&nbsp;</td>';
echo '<td>';
echo '<input type="submit" name="save" class="submit" value="Save">';
echo '&nbsp;&nbsp;';
echo '<input type="submit" name="cancel" class="submit" value="Cancel">';
echo '</td></tr>';
echo '</table>';
echo '</form>';
echo '</div>';

Html::footer();
