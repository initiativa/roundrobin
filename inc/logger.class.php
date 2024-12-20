<?php

/**
 * -------------------------------------------------------------------------
 * Plugin TicketBalance para GLPI
 * -------------------------------------------------------------------------
 *
 * LICENÇA
 *
 * Este arquivo é parte do Plugin TicketBalance GLPI.
 *
 * TicketBalance é um software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela
 * Free Software Foundation; seja na versão 3 da Licença, ou (a seu critério) qualquer versão posterior.
 *
 * TicketBalance é distribuído na esperança de que seja útil,
 * mas SEM QUALQUER GARANTIA; sem mesmo a garantia implícita de
 * COMERCIALIZAÇÃO ou ADEQUAÇÃO A UM PROPÓSITO PARTICULAR. Consulte a Licença Pública Geral GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com o TicketBalance. Se não, veja <http://www.gnu.org/licenses/>
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 - https://www.linkedin.com/in/richard-ti/
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/RPGMais/ticketbalance
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
     * 
     * @global Logger $PHPLOGGER
     * @param int $type
     * @param string $message
     * @param array $details
     */
    protected static function add($type, $message, $details = []) {
        global $PHPLOGGER;
        switch ($type) {
            case self::$DEBUG:
                $recordType = Monolog\Logger::DEBUG;
                break;
            case self::$INFO:
                $recordType = Monolog\Logger::INFO;
                break;
            case self::$NOTICE:
                $recordType = Monolog\Logger::NOTICE;
                break;
            case self::$WARNING:
                $recordType = Monolog\Logger::WARNING;
                break;
            case self::$ERROR:
                $recordType = Monolog\Logger::ERROR;
                break;
            case self::$CRITICAL:
                $recordType = Monolog\Logger::CRITICAL;
                break;
            default:
                $recordType = Monolog\Logger::INFO;
                break;
        }
        $PHPLOGGER->addRecord($recordType, $message, $details);
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
