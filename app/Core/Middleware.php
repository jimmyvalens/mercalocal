<?php
// =========================================================
// app/Core/Middleware.php — Gestión de permisos y acceso
// =========================================================
namespace App\Core;

class Middleware
{
    /**
     * Verifica que el usuario haya iniciado sesión.
     */
    public static function requireAuth()
    {
        if (!Session::get('user_id')) {
            // Session::setFlash('error', 'Debes iniciar sesión para acceder.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Verifica que el usuario tenga un rol específico.
     * @param string|array $roles Rol requerido ('ADMIN', 'BUSINESS', 'USER')
     */
    public static function requireRole($roles)
    {
        self::requireAuth();

        $userRole = Session::get('user_role');
        $roles = (array)$roles;

        if (!in_array($userRole, $roles, true)) {
            // Session::setFlash('error', 'Acceso denegado. No tienes permisos para ver esta página.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Verifica que el usuario sea un invitado (no logueado).
     */
    public static function requireGuest()
    {
        if (Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }
}
