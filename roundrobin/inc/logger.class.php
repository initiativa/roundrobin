<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI - Logger class for GLPI 11
 * -------------------------------------------------------------------------
 */
class PluginRoundRobinLogger {
    
    public static function addDebug($message) {
        Toolbox::logInFile('roundrobin', 'DEBUG: ' . $message);
    }
    
    public static function addError($message) {
        Toolbox::logInFile('php-errors', 'RoundRobin ERROR: ' . $message);
    }
    
    public static function addInfo($message) {
        Toolbox::logInFile('roundrobin', 'INFO: ' . $message);
    }
}
