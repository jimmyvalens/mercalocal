<?php

/**
 * =========================================================
 * app/Controllers/BusinessController.php — Controlador de comercios
 *
 * Controla las vistas públicas y las APIs de comercios:
 * · Listado, búsqueda y filtrado de comercios
 * · Detalle de comercio con productos y horarios
 * · Asistente de configuración inicial para comercios
 * =========================================================
 */

namespace App\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Core\Database;
use App\Core\Session;
use App\Core\Middleware;

class BusinessController
{
    /**
     * Muestra el catálogo de comercios.
     *
     * @return void
     */
    public function index()
    {
        $search = $_GET['q'] ?? '';
        $categoryId = $_GET['categoria'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        try {
            $businesses = Business::getAll($search, $categoryId, $perPage, $offset);
            $totalBusinesses = Business::countAll($search, $categoryId);
            $totalPages = ceil($totalBusinesses / $perPage);

            $categories = Category::getParents();

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
     * Muestra la ficha detallada de un comercio.
     *
     * @param int $id
     * @return void
     */
    public function detail(int $id)
    {
        $business = Business::findById($id);
        if (!$business) {
            http_response_code(404);
            echo "Comercio no encontrado";
            return;
        }

        $products = $business->getProducts();
        $schedules = $business->getSchedules();

        require ROOT_DIR . '/resources/views/business/detail.php';
    }

    /**
     * Devuelve lista de comercios en JSON.
     *
     * @return void
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

            $businessesData = array_map(function ($business) {
                return [
                    'id' => $business->id,
                    'nombre' => $business->nombre,
                    'descripcion' => $business->descripcion,
                    'telefono' => $business->telefono,
                    'email' => $business->email,
                    'web' => $business->web,
                    'categorias' => $business->categorias,
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
     * Devuelve detalle de un comercio en JSON.
     *
     * @param int $id
     * @return void
     */
    public function apiDetail(int $id)
    {
        header('Content-Type: application/json');
        try {
            $business = Business::findById($id);
            if (!$business) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comercio no encontrado']);
                return;
            }

            $businessData = [
                'id' => $business->id,
                'user_id' => $business->user_id,
                'nombre' => $business->nombre,
                'descripcion' => $business->descripcion,
                'telefono' => $business->telefono,
                'email' => $business->email,
                'web' => $business->web,
                'activo' => $business->activo,
                'categorias' => $business->categorias,
                'created_at' => $business->created_at,
                'updated_at' => $business->updated_at,
                'products' => $business->getProducts(),
                'schedules' => $business->getSchedules()
            ];

            echo json_encode(['success' => true, 'data' => $businessData]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // ZONA PRIVADA — ASISTENTE DE CONFIGURACIÓN (ONBOARDING)
    // =========================================================================

    /**
     * Muestra el asistente de configuración inicial para comercios.
     *
     * @return void
     */
    public function showSetup()
    {
        Middleware::requireBusinessPending();

        $oldInput = Session::get('setup_old', []);
        Session::remove('setup_old');

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $stmt->execute();
        $categorias_padre = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $business = array_merge([
            'nombre'        => '',
            'telefono'      => '',
            'email'         => '',
            'web'           => '',
            'descripcion'   => '',
            'categoria_id'  => '',
            'calle'         => '',
            'numero'        => '',
            'codigo_postal' => '',
            'ciudad'        => '',
            'provincia'     => ''
        ], $oldInput);

        require ROOT_DIR . '/resources/views/layout/business_form.php';
    }

    /**
     * Procesa el formulario del asistente de configuración.
     *
     * @return void
     */
    public function setup()
    {
        Middleware::requireBusinessPending();

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'La petición ha caducado. Inténtalo de nuevo.');
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            $formData = \App\Core\BusinessFormHandler::process($_POST);
            $formData['user_id'] = Session::get('user_id');

            if (!preg_match('/^\d{9}$/', $formData['telefono'])) {
                throw new \InvalidArgumentException('El número de teléfono debe constar exactamente de 9 dígitos numéricos.');
            }

            $images = $uploader->uploadBusinessImages($_FILES);
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $sqlBus = "INSERT INTO business (nombre, descripcion, telefono, email, web, id_categoria, user_id, activo, logo_path, hero_path, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())";

            $stmtBus = $db->prepare($sqlBus);
            $stmtBus->execute([
                $formData['nombre'],
                $formData['descripcion'],
                $formData['telefono'],
                $formData['email'],
                $formData['web'],
                $formData['categoria_id'],
                $formData['user_id'],
                $images['logo_path'],
                $images['hero_path']
            ]);

            $businessId = $db->lastInsertId();

            $sqlAddr = "INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmtAddr = $db->prepare($sqlAddr);
            $stmtAddr->execute([
                $formData['calle'],
                $formData['numero'],
                $formData['codigo_postal'],
                $formData['ciudad'],
                $formData['provincia']
            ]);

            $addressId = $db->lastInsertId();

            $stmtPivot = $db->prepare("INSERT INTO business_address (business_id, address_id) VALUES (?, ?)");
            $stmtPivot->execute([$businessId, $addressId]);

            $db->commit();

            Session::set('business_id', $businessId);

            Session::setFlash('success', '¡Enhorabuena! El perfil de tu comercio se ha configurado correctamente. Ya puedes gestionar tus productos.');

            session_write_close();
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::setFlash('error', 'Ocurrió un error inesperado al guardar los datos del comercio: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
    }
}
