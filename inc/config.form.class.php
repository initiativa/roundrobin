<<<<<<< Updated upstream
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

// Include dependencies from same directory using __DIR__
require_once __DIR__ . '/config.class.php';
require_once __DIR__ . '/logger.class.php';
require_once __DIR__ . '/RRAssignmentsEntity.class.php';

use Glpi\Application\View\TemplateRenderer;


class PluginRoundRobinSettings extends CommonDBTM {

    public function __construct() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - constructor called');
    }

    public function showFormRoundRobin() {
        global $CFG_GLPI, $DB;

        if (self::checkCentralInterface()) {
            PluginRoundRobinLogger::addDebug(__METHOD__ . ' - display contents');
            self::displayContent();
        } else {
            echo "<div align='center'><br><img src='" . $CFG_GLPI['root_doc'] . "/pics/warning.png'><br>" . __("Access denied") . "</div>";
        }
    }

    public static function checkCentralInterface() {
        $currentInterface = Session::getCurrentInterface();
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - current interface: ' . $currentInterface);
        return $currentInterface === 'central';
    }

    public static function displayContent() {
        $twig = TemplateRenderer::getInstance();
        $twig->display('@roundrobin/config.form.twig', [
            'csrf_token' => Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]),
            'auto_assign_group' => self::getAutoAssignGoup(),
            'centralInterfaceCheck' => self::checkCentralInterface(),
            'settings' => self::getSettings()
        ]);
        
    }

    protected static function getSettings() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getAll();
    }

    protected static function getAutoAssignGoup() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getOptionAutoAssignGroup();
    }

    public static function saveSettings() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - POST: ' . print_r($_POST, true));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();

        /**
         * save option(s)
         */
        $rrAssignmentsEntity->updateAutoAssignGroup($_POST['auto_assign_group']);

        /**
         * save all assignments
         */
        foreach (self::getSettings() as $row) {
            $itilCategoryId = $_POST["itilcategories_id_{$row['id']}"];
            $newValue = $_POST["is_active_{$row['id']}"];
            $rrAssignmentsEntity->updateIsActive($itilCategoryId, $newValue);
        }
    }

}
=======
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

// Include dependencies from same directory using __DIR__
require_once __DIR__ . '/config.class.php';
require_once __DIR__ . '/logger.class.php';
require_once __DIR__ . '/RRAssignmentsEntity.class.php';

use Glpi\Application\View\TemplateRenderer;

class PluginRoundRobinSettings extends CommonDBTM {

    public function __construct() {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - constructor called');
    }

    public function showFormRoundRobin() {
        self::printConfigPage();
    }

    /**
     * Page shell + scoped UI root ({@see front/config.form.php} entry via showForm).
     */
    public static function printConfigPage(): void {
        PluginRoundRobinLogger::addDebug(__METHOD__);

        if (!self::checkCentralInterface()) {
            echo '<div class="container-fluid pt-3">' . '<div class="alert alert-important alert-warning d-flex"><i class="ti ti-alert-triangle icon alert-icon ms-2 me-3"></i>'
                . '<div>' . __("Access denied") . '</div></div></div>';

            return;
        }

        echo '<div class="container-fluid pt-3 pb-5">' . "\n";
        echo '<div class="plugin-roundrobin-config mx-auto" style="max-width:980px;">' . "\n";
        self::displayContent();
        echo '</div>' . "\n" . '</div>';
    }

    public static function checkCentralInterface(): bool {
        $currentInterface = Session::getCurrentInterface();
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - current interface: ' . $currentInterface);
        return $currentInterface === 'central';
    }

    public static function displayContent(): void {
        $twig = TemplateRenderer::getInstance();

        $twig->display('@roundrobin/config.form.twig', [
            'csrf_token'            => Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]),
            'auto_assign_group'     => self::getAutoAssignGoup(),
            'centralInterfaceCheck' => self::checkCentralInterface(),
            'settings'              => self::getSettings(),
            'can_write'             => Session::haveRight('config', UPDATE),
        ]);
    }

    protected static function getSettings(): array {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        $rows               = $rrAssignmentsEntity->getAll();

        foreach ($rows as &$row) {
            $row['category_url'] = self::safeItemFormUrl(\ITILCategory::class, (int)$row['itilcategories_id']);
            $gid                 = isset($row['groups_id']) ? (int)$row['groups_id'] : 0;
            $row['group_url']    = $gid > 0 ? self::safeItemFormUrl(\Group::class, $gid) : null;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param class-string<CommonDBTM> $class
     */
    protected static function safeItemFormUrl(string $class, int $id): ?string {
        if ($id < 1 || !class_exists($class)) {
            return null;
        }

        if (!method_exists($class, 'getFormURLWithID')) {
            return null;
        }

        /** @var class-string<\CommonGLPI>|class-string<\CommonDBTM> $class */
        $url = call_user_func([$class, 'getFormURLWithID'], $id);
        return $url ?: null;
    }

    protected static function getAutoAssignGoup(): int {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getOptionAutoAssignGroup();
    }

    /** General options — returns true when at least one field was updated */
    public static function saveGeneralOptions(): bool {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - POST: ' . print_r($_POST, true));

        if (!isset($_POST['auto_assign_group'])) {
            return false;
        }

        $rr = new PluginRoundRobinRRAssignmentsEntity();
        $new = ((int)$_POST['auto_assign_group'] === 1) ? 1 : 0;
        $old = $rr->getOptionAutoAssignGroup();

        if ($old === $new) {
            Session::addMessageAfterRedirect(
                __('No changes were made.', 'roundrobin'),
                false,
                INFO
            );
            return false;
        }

        $rr->updateAutoAssignGroup($new);

        Session::addMessageAfterRedirect(
            __('Configuration saved.', 'roundrobin'),
            false,
            INFO
        );

        return true;
    }

    /** Per-category toggles */
    public static function saveCategoryAssignments(): bool {
        PluginRoundRobinLogger::addDebug(__METHOD__ . ' - POST: ' . print_r($_POST, true));

        $rr             = new PluginRoundRobinRRAssignmentsEntity();
        $rows           = self::getSettings();
        $changedRows    = false;
        $blockedAttempt = false;

        foreach ($rows as $row) {
            $formId       = $row['id'];
            $itilCategoriesId = (int)$row['itilcategories_id'];
            $canEnable       = !empty($row['can_enable_roundrobin']);

            if (!isset($_POST["itilcategories_id_{$formId}"])) {
                continue;
            }

            $postedItilCat = (int)$_POST["itilcategories_id_{$formId}"];
            if ($postedItilCat !== $itilCategoriesId) {
                PluginRoundRobinLogger::addWarning(__METHOD__ . " CSRF/itil mismatch for row $formId");
                continue;
            }

            $postedActive = isset($_POST["is_active_{$formId}"]) ? (int)$_POST["is_active_{$formId}"] : 0;
            $desired      = $postedActive === 1 ? 1 : 0;

            if ($desired === 1 && !$canEnable) {
                $blockedAttempt = true;
                if ((int)$row['is_active'] !== 0) {
                    $rr->updateIsActive($itilCategoriesId, 0);
                    $changedRows = true;
                }

                continue;
            }

            if ((int)$row['is_active'] !== $desired) {
                $rr->updateIsActive($itilCategoriesId, $desired);
                $changedRows = true;
            }
        }

        if ($blockedAttempt) {
            Session::addMessageAfterRedirect(
                __('Assign a technician group to the ITIL category first, then enable round-robin here.', 'roundrobin'),
                false,
                WARNING
            );
        }

        if ($changedRows) {
            Session::addMessageAfterRedirect(
                __('Category settings saved.', 'roundrobin'),
                false,
                INFO
            );
        } elseif (!$blockedAttempt) {
            Session::addMessageAfterRedirect(
                __('No changes were made.', 'roundrobin'),
                false,
                INFO
            );
        }

        return $changedRows || $blockedAttempt;
    }
}
>>>>>>> Stashed changes
