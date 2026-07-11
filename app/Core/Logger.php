<?php

/**
 * =========================================================
 * app/Core/Logger.php — Clase de registro de actividad
 *
 * Registra mensajes de aplicación e imprime entradas en un archivo:
 * · Genera y almacena entradas con marca de tiempo y nivel
 * · Soporta niveles INFO, WARNING y ERROR
 * · Usa bloqueo de archivo para escritura segura
 * =========================================================
 */

namespace App\Core;

class Logger
{
    private static string $logFile = __DIR__ . '/../../logs/app.log';

    /**
     * Registra un mensaje en el archivo de log con nivel y marca de tiempo.
     *
     * @param string $message Mensaje a registrar.
     * @param string $level Nivel de log, por defecto INFO.
     * @return void
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Registra un mensaje de error.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    public static function error(string $message): void
    {
        self::log($message, 'ERROR');
    }

    /**
     * Registra un mensaje informativo.
     *
     * @param string $message Mensaje informativo.
     * @return void
     */
    public static function info(string $message): void
    {
        self::log($message, 'INFO');
    }

    /**
     * Registra un mensaje de advertencia.
     *
     * @param string $message Mensaje de advertencia.
     * @return void
     */
    public static function warning(string $message): void
    {
        self::log($message, 'WARNING');
    }
}
