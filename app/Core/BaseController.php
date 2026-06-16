<?php

namespace App\Core;

abstract class BaseController
{
    protected $user;

    public function __construct()
    {
        if (Session::get('user_id')) {
            $userData = \App\Models\User::findById(Session::get('user_id'));
            $this->user = is_array($userData) ? (object)$userData : $userData;
        }
    }

    protected function view($path, $data = [])
    {
        // Inyectamos el usuario siempre
        $data['user'] = $this->user;

        // Convertimos el array en variables accesibles ($orders, $user, etc.)
        extract($data);

        require_once ROOT_DIR . '/resources/views/' . $path . '.php';
    }

    public static function translateStatus($status)
    {
        $map = ['pending' => 'Pendiente', 'completed' => 'Completado', 'cancelled' => 'Cancelado', 'en proceso' => 'En proceso'];
        return $map[strtolower($status)] ?? $status;
    }

    public static function getStatusClass($status)
    {
        $classes = ['pending' => 'bg-yellow-50 text-yellow-700 border-yellow-200', 'completed' => 'bg-green-50 text-green-700 border-green-200', 'cancelled' => 'bg-red-50 text-red-700 border-red-200', 'en proceso' => 'bg-blue-50 text-blue-700 border-blue-200'];
        return $classes[strtolower($status)] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
