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

        $plug = Plugin::getWebDir('roundrobin', false);
        $page = '/' . trim((string)$plug, '/') . '/front/config.form.php';
        $page = preg_replace('#/+#', '/', $page);

        return [
            'title' => __('RoundRobin', 'roundrobin'),
            'page'  => $page,
            'icon'  => self::getIcon(),
        ];
    }
}
