<?php

/**
 * =========================================================
 * app/Controllers/HomeController.php — Controlador de inicio
 *
 * Gestiona la visualización de la página principal.
 * · Carga la vista principal con el buscador
 * · Incluye el listado de comercios destacados
 * · Presenta el contenido inicial del sitio
 * =========================================================
 */

namespace App\Controllers;

class HomeController
{
    /**
     * Muestra la página de inicio (GET /).
     *
     * @return void
     */
    public function index()
    {
        require_once ROOT_DIR . '/resources/views/home.php';
    }
}
