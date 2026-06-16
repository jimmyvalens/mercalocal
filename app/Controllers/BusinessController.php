<?php
// =========================================================
// app/Controllers/BusinessController.php — Controlador público de comercios
// =========================================================
namespace App\Controllers;

use App\Models\Business;
use App\Models\Category;

/**
 * Clase BusinessController
 *
 * Controla el flujo público del catálogo de comercios, filtros de categorías,
 * pasarela hacia la ficha detallada y los endpoints de la API en JSON.
 */
class BusinessController
{
    /**
     * Muestra el catálogo de comercios (GET /businesses).
     * Admite búsqueda por nombre (?q=...) y filtrado por categoría (?categoria=ID).
     */
    public function index()
    {
        // Parámetros opcionales de búsqueda y filtrado de la URL
        $search = $_GET['q'] ?? '';
        $categoryId = $_GET['categoria'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 10; // Comercios por página
        $offset = ($page - 1) * $perPage;

        try {
            // Obtener comercios activos según los filtros aplicados con paginación
            $businesses = Business::getAll($search, $categoryId, $perPage, $offset);
            $totalBusinesses = Business::countAll($search, $categoryId);
            $totalPages = ceil($totalBusinesses / $perPage);

            // Obtener todas las categorías para el selector del filtro
            $categories = Category::getAll();

            $viewPath = ROOT_DIR . '/resources/views/business/list.php';
            if (!file_exists($viewPath)) {
                die("Vista no encontrada: " . $viewPath);
            }
            require_once $viewPath;
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine();
        }
    }

    /**
     * Muestra la ficha detallada de un comercio (GET /business/{id}).
     * Carga sus productos, servicios y horarios de apertura.
     *
     * @param int $id ID del comercio a mostrar
     */
    public function detail($id)
    {
        // Buscar el comercio; si no existe devolver error 404
        $business = Business::findById($id);
        if (!$business) {
            http_response_code(404);
            echo "Comercio no encontrado";
            return;
        }

        // Cargar los datos relacionados del comercio
        $products = $business->getProducts(); // Productos disponibles para compra
        $services = $business->getServices(); // Servicios disponibles para reserva
        $schedules = $business->getSchedules(); // Horarios de atención al público

        require_once ROOT_DIR . '/resources/views/business/detail.php';
    }

    /**
     * API: Devuelve lista de comercios en JSON
     */
    public function apiIndex()
    {
        header('Content-Type: application/json');
        try {
            $search = $_GET['q'] ?? '';
            $categoryId = $_GET['categoria'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $businesses = Business::getAll($search, $categoryId, $perPage, $offset);
            $total = Business::countAll($search, $categoryId);

            // Mapear los datos sincronizados con las nuevas propiedades del modelo
            $businessesData = array_map(function ($business) {
                return [
                    'id' => $business->id,
                    'nombre' => $business->name,
                    'descripcion' => $business->description,
                    'telefono' => $business->phone,
                    'email' => $business->email,
                    'web' => $business->website,
                    'categorias' => $business->categories,
                    'rating' => $business->getRating()
                ];
            }, $businesses);

            echo json_encode([
                'success' => true,
                'data' => $businessesData,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API: Devuelve detalle de un comercio en JSON
     */
    public function apiDetail($id)
    {
        header('Content-Type: application/json');
        try {
            $business = Business::findById($id);
            if (!$business) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comercio no encontrado']);
                return;
            }

            // Preparar datos del comercio mapeando al JSON esperado por el frontend
            $businessData = [
                'id' => $business->id,
                'user_id' => $business->user_id,
                'nombre' => $business->name,
                'descripcion' => $business->description,
                'telefono' => $business->phone,
                'email' => $business->email,
                'web' => $business->website,
                'activo' => $business->is_active,
                'categorias' => $business->categories,
                'created_at' => $business->created_at,
                'updated_at' => $business->updated_at,
                'rating' => $business->getRating(),
                'products' => $business->getProducts(),
                'services' => $business->getServices(),
                'schedules' => $business->getSchedules()
            ];

            echo json_encode(['success' => true, 'data' => $businessData]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
