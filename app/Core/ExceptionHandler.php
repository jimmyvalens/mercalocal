<?php
// =========================================================
// src/Core/ExceptionHandler.php — Manejador Global de Excepciones
// Captura errores y excepciones no controladas, los registra
// en un log y muestra una vista amigable de error 500.
// =========================================================
namespace App\Core;

class ExceptionHandler
{
    /**
     * Registra los manejadores globales.
     */
    public static function register()
    {
        error_reporting(E_ALL);
        
        // Manejador de excepciones
        set_exception_handler([self::class, 'handleException']);
        
        // Manejador de errores (convierte errores de PHP a excepciones)
        set_error_handler([self::class, 'handleError']);
        
        // Manejador de apagado para errores fatales
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($level, $message, $file = '', $line = 0)
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public static function handleException(\Throwable $exception)
    {
        $code = $exception->getCode();
        if ($code != 404) {
            $code = 500;
        }
        
        http_response_code($code);

        // Si la aplicación está en modo DEBUG, mostrar detalles
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<h1>Excepción de la aplicación</h1>";
            echo "<p><strong>Tipo:</strong> " . get_class($exception) . "</p>";
            echo "<p><strong>Mensaje:</strong> '" . $exception->getMessage() . "'</p>";
            echo "<p><strong>Archivo:</strong> " . $exception->getFile() . " en la línea " . $exception->getLine() . "</p>";
            echo "<h2>Stack trace:</h2>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        } else {
            // Modo Producción: Mostrar vista genérica y registrar log
            $logFile = ROOT_DIR . '/logs/error.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logMessage = "[" . date('Y-m-d H:i:s') . "] " . get_class($exception) . ": " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL;
            error_log($logMessage, 3, $logFile);

            if ($code == 404) {
                // TODO: create 404 view
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

    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }
}
