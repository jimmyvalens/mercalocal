<?php

/**
 * =========================================================
 * app/Core/ExceptionHandler.php — Manejador Global de Excepciones
 *
 * Gestiona excepciones y errores no controlados:
 * · Registra manejadores globales de errores y excepciones
 * · Convierte errores de PHP en excepciones
 * · Maneja el apagado para errores fatales
 * =========================================================
 */

namespace App\Core;

class ExceptionHandler
{
    /**
     * Registra los manejadores globales.
     *
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL);

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Convierte un error de PHP en una excepción.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Procesa la excepción y muestra la respuesta adecuada.
     *
     * @param \Throwable $exception
     * @return void
     */
    public static function handleException(\Throwable $exception)
    {
        $code = $exception->getCode();
        if ($code != 404) {
            $code = 500;
        }

        http_response_code($code);

        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<h1>Excepción de la aplicación</h1>";
            echo "<p><strong>Tipo:</strong> " . get_class($exception) . "</p>";
            echo "<p><strong>Mensaje:</strong> '" . $exception->getMessage() . "'</p>";
            echo "<p><strong>Archivo:</strong> " . $exception->getFile() . " en la línea " . $exception->getLine() . "</p>";
            echo "<h2>Stack trace:</h2>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        } else {
            $logFile = ROOT_DIR . '/logs/error.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logMessage = "[" . date('Y-m-d H:i:s') . "] " . get_class($exception) . ": " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL;
            error_log($logMessage, 3, $logFile);

            if ($code == 404) {
                echo "<h1>Página no encontrada (404)</h1>";
            } else {
                $errorView = ROOT_DIR . '/resources/views/errors/500.php';
                if (file_exists($errorView)) {
                    require_once $errorView;
                } else {
                    echo "<h1>Ha ocurrido un error inesperado (500)</h1><p>Por favor, inténtelo de nuevo más tarde.</p>";
                }
            }
        }
        exit;
    }

    /**
     * Maneja errores fatales al apagar el script.
     *
     * @return void
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }
}
