<?php

/**
 * =========================================================
 * app/Core/Middleware.php — Gestión de permisos y acceso
 *
 * Controla permisos de usuarios y acceso a rutas protegidas:
 * · Valida sesiones y roles antes de permitir acceso
 * · Redirige según estado de configuración de negocio
 * · Impide acceso a páginas de invitados si hay sesión activa
 * =========================================================
 */

namespace App\Core;

class Middleware
{
    /**
     * Verifica que el usuario haya iniciado sesión.
     *
     * @return void
     */
    public static function requireAuth()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Verifica que el usuario tenga un rol específico.
     *
     * @param string|array $roles Rol requerido ('ADMIN', 'BUSINESS', 'USER')
     * @return void
     */
    public static function requireRole($roles)
    {
        self::requireAuth();

        $userRole = Session::get('user_role');
        $roles = (array)$roles;

        if (!in_array($userRole, $roles, true)) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Verifica que el usuario sea un invitado (no logueado).
     *
     * @return void
     */
    public static function requireGuest()
    {
        if (Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    /**
     * Verifica que el comercio HAYA COMPLETADO el setup inicial.
     *
     * @return void
     */
    public static function requireBusinessSetup()
    {
        self::requireRole('BUSINESS');

        if (!Session::get('business_id')) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
    }

    /**
     * Verifica que el comercio TENGA PENDIENTE el setup inicial.
     *
     * @return void
     */
    public static function requireBusinessPending()
    {
        self::requireRole('BUSINESS');

        if (Session::get('business_id')) {
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        }
    }
}
