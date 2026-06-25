<?php
// =========================================================
// app/Core/Session.php — Gestión de la sesión de usuario
// Centraliza todas las operaciones con $_SESSION para
// facilitar el mantenimiento y evitar accesos directos
// a la superglobal desde los controladores y vistas.
// =========================================================
namespace App\Core;

class Session
{
    /**
     * Inicia la sesión PHP si todavía no está iniciada.
     * Se llama una sola vez desde public/index.php.
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
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Recupera un valor de la sesión.
     * Devuelve $default si la clave no existe.
     *
     * @param string $key     Clave a buscar
     * @param mixed  $default Valor por defecto si la clave no existe
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Elimina una variable específica de la sesión.
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destruye completamente la sesión actual.
     * Se usa al hacer logout.
     */
    public static function destroy()
    {
        self::start();
        session_destroy();
    }

    /**
     * Guarda un mensaje flash (de un solo uso) en la sesión.
     * Se usa para transmitir notificaciones entre redirecciones.
     *
     * @param string $type    Tipo del mensaje: 'success', 'error' o 'info'
     * @param string $message Texto del mensaje
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type, // Determina el color del aviso en la vista
            'message' => $message // Texto legible para el usuario
        ];
    }

    /**
     * Recupera y elimina el mensaje flash de la sesión.
     * Al ser de un solo uso, desaparece tras la primera lectura.
     * Devuelve null si no hay ningún mensaje pendiente.
     */
    public static function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']); // Eliminar después de leer
            return $flash;
        }
        return null;
    }

    /**
     * Genera (o devuelve) un token CSRF asociado a la sesión.
     * Se reutiliza durante toda la sesión.
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
     */
    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::generateCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
