<?php
// app/Core/Logger.php
// Clase para logging básico
namespace App\Core;

class Logger
{
    private static $logFile = __DIR__ . '/../../logs/app.log';

    public static function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function error($message)
    {
        self::log($message, 'ERROR');
    }

    public static function info($message)
    {
        self::log($message, 'INFO');
    }

    public static function warning($message)
    {
        self::log($message, 'WARNING');
    }
}
