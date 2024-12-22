<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class PluginTicketBalanceLogger {

    protected static $logger = null; // Define a propriedade estática

    protected static function getLogger() {
        if (self::$logger === null) {
            // Cria a nova instância do logger
            self::$logger = new Logger('ticketbalance');

            // Caminho do diretório de logs
            $logDir = PLUGIN_TICKETBALANCE_DIR . '/logs/';
            $logFile = $logDir . 'ticketbalance.log';

            // Verifica se a pasta de logs existe, caso contrário cria
            if (!file_exists($logDir)) {
                if (!mkdir($logDir, 0755, true)) {
                    error_log("Erro: Não foi possível criar o diretório de logs: $logDir");
                    return null;
                }
            }

            // Verifica se o arquivo de log existe, caso contrário cria
            if (!file_exists($logFile)) {
                if (!touch($logFile)) {
                    error_log("Erro: Não foi possível criar o arquivo de log: $logFile");
                    return null;
                }
                chmod($logFile, 0644); // Define permissões de leitura e escrita
            }

            // Adiciona o manipulador de arquivos ao logger
            try {
                self::$logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
            } catch (\Exception $e) {
                error_log("Erro ao configurar o logger: " . $e->getMessage());
                return null;
            }
        }

        return self::$logger;
    }

    protected static function add($type, $message, $details = []) {
        $logger = self::getLogger();

        if ($logger === null) {
            // Se o logger não foi configurado corretamente, grava no log PHP padrão
            error_log("[$type] $message - " . json_encode($details));
            return;
        }

        // Mapeia os tipos para os níveis do Monolog
        switch ($type) {
            case 100:
                $logger->debug($message, $details);
                break;
            case 200:
                $logger->info($message, $details);
                break;
            case 250:
                $logger->notice($message, $details);
                break;
            case 300:
                $logger->warning($message, $details);
                break;
            case 400:
                $logger->error($message, $details);
                break;
            case 500:
                $logger->critical($message, $details);
                break;
            default:
                $logger->info($message, $details);
                break;
        }
    }

    public static function addDebug($message, $details = []) {
        self::add(100, $message, $details);
    }

    public static function addInfo($message, $details = []) {
        self::add(200, $message, $details);
    }

    public static function addNotice($message, $details = []) {
        self::add(250, $message, $details);
    }

    public static function addWarning($message, $details = []) {
        self::add(300, $message, $details);
    }

    public static function addError($message, $details = []) {
        self::add(400, $message, $details);
    }

    public static function addCritical($message, $details = []) {
        self::add(500, $message, $details);
    }
}