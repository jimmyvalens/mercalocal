<?php
// =========================================================
// app/Controllers/HomeController.php — Controlador de inicio
// Renderiza la página principal de Mercalocal.
// =========================================================
namespace App\Controllers;

class HomeController
{
    /**
     * Muestra la página de inicio (GET /).
     * Carga la vista principal con el buscador y el listado de comercios destacados.
     */
    public function index()
    {
        require_once ROOT_DIR . '/resources/views/home.php';
    }
}
