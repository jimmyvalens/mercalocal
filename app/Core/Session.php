<?php

/**
 * =========================================================
 * app/Core/Session.php — Gestión de la sesión de usuario
 *
 * Centraliza la gestión de datos de sesión y seguridad CSRF:
 * · Inicia, regenera y destruye sesiones
 * · Maneja valores, flash messages y tokens CSRF
 * · Proporciona utilidades para formularios y validación
 * =========================================================
 */

namespace App\Core;

class Session
{
    /**
     * Inicia la sesión PHP si todavía no está iniciada.
     *
     * @return void
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Regenera el identificador de sesión tras cambios de autenticación.
     *
     * @return void
     */
    public static function regenerate()
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Guarda un valor en la sesión bajo la clave indicada.
     *
     * @param string $key   Nombre de la variable de sesión
     * @param mixed  $value Valor a guardar
     * @return void
     */
    public static function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Recupera un valor de la sesión.
     *
     * @param string $key     Clave a buscar
     * @param mixed  $default Valor por defecto si la clave no existe
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Elimina una variable específica de la sesión.
     *
     * @param string $key
     * @return void
     */
    public static function remove(string $key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destruye completamente la sesión actual.
     *
     * @return void
     */
    public static function destroy()
    {
        self::start();
        session_destroy();
    }

    /**
     * Guarda un mensaje flash (de un solo uso) en la sesión.
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Recupera y elimina el mensaje flash de la sesión.
     *
     * @return array|null
     */
    public static function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Genera (o devuelve) un token CSRF asociado a la sesión.
     *
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Comprueba que el token recibido coincide con el almacenado en sesión.
     *
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Devuelve el campo oculto CSRF listo para incluir en formularios POST.
     *
     * @return string
     */
    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::generateCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
