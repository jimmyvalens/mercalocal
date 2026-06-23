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

    /**
     * NUEVO: Verifica que el comercio HAYA COMPLETADO el setup inicial.
     * Se usará en: dashboard, productos, servicios, horarios, pedidos...
     */
    public static function requireBusinessSetup()
    {
        // Primero nos aseguramos de que al menos sea un usuario tipo BUSINESS
        self::requireRole('BUSINESS');

        // Si no tiene un ID de comercio asignado en la sesión, al formulario de cabeza
        if (!Session::get('business_id')) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
    }

    /**
     * NUEVO: Verifica que el comercio TENGA PENDIENTE el setup inicial.
     * Se usará ÚNICAMENTE en la ruta y vista de 'views/business/setup.php'.
     */
    public static function requireBusinessPending()
    {
        self::requireRole('BUSINESS');

        // Si ya tiene un ID de comercio, no tiene sentido que vuelva a rellenar el setup
        if (Session::get('business_id')) {
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        }
    }
}
