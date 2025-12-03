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
class PluginRoundRobinLogger {

    protected static $DEBUG = 100;
    protected static $INFO = 200;
    protected static $NOTICE = 250;
    protected static $WARNING = 300;
    protected static $ERROR = 400;
    protected static $CRITICAL = 500;
    protected static $ALERT = 550;
    protected static $EMERGENCY = 600;

    /**
     * GLPI 11 compatible logging
     * Uses Toolbox::logInFile when available
     * 
     * @param int $type
     * @param string $message
     * @param array $details
     */
    protected static function add($type, $message, $details = []) {
        // Build log message
        $logMessage = $message;
        if (!empty($details)) {
            $logMessage .= ' - ' . print_r($details, true);
        }
        
        // Check if Toolbox class is available (GLPI loaded)
        if (!class_exists('Toolbox')) {
            // GLPI not loaded yet, skip logging
            return;
        }
        
        try {
            switch ($type) {
                case self::$DEBUG:
                    Toolbox::logInFile('roundrobin', 'DEBUG: ' . $logMessage);
                    break;
                case self::$INFO:
                    Toolbox::logInFile('roundrobin', 'INFO: ' . $logMessage);
                    break;
                case self::$NOTICE:
                    Toolbox::logInFile('roundrobin', 'NOTICE: ' . $logMessage);
                    break;
                case self::$WARNING:
                    Toolbox::logInFile('roundrobin', 'WARNING: ' . $logMessage);
                    break;
                case self::$ERROR:
                    Toolbox::logInFile('php-errors', 'RoundRobin ERROR: ' . $logMessage);
                    break;
                case self::$CRITICAL:
                    Toolbox::logInFile('php-errors', 'RoundRobin CRITICAL: ' . $logMessage);
                    break;
                default:
                    Toolbox::logInFile('roundrobin', $logMessage);
                    break;
            }
        } catch (Exception $e) {
            // Silently fail if logging fails
        }
    }

    public static function addDebug($message, $details = []) {
        self::add(self::$DEBUG, $message, $details);
    }

    public static function addInfo($message, $details = []) {
        self::add(self::$INFO, $message, $details);
    }

    public static function addNotice($message, $details = []) {
        self::add(self::$NOTICE, $message, $details);
    }

    public static function addWarning($message, $details = []) {
        self::add(self::$WARNING, $message, $details);
    }

    public static function addError($message, $details = []) {
        self::add(self::$ERROR, $message, $details);
    }

    public static function addCritical($message, $details = []) {
        self::add(self::$CRITICAL, $message, $details);
    }

}
