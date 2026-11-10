<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 by initiativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

class PluginRoundRobinSetupMenu extends CommonGLPI {

    public static function getIcon() {
        return 'ti ti-arrows-exchange';
    }

    public static function getMenuContent() {
        if (!Session::haveRight('config', READ)) {
            return false;
        }

        // GLPI 12: plugin URLs must use the /plugins/ path (Plugin::getWebDir removed).
        return [
            'title' => __('RoundRobin', 'roundrobin'),
            'page'  => '/plugins/roundrobin/front/config.form.php',
            'icon'  => self::getIcon(),
        ];
    }
}
