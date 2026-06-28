<?php
// =========================================================
// src/Controllers/BusinessController.php — Controlador público de comercios
// Gestiona las páginas visibles para todos los visitantes:
//   · Listado/catálogo de comercios con búsqueda y filtro por categoría
//   · Ficha detallada de un comercio con sus productos, servicios y horarios
// =========================================================
namespace App\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Core\Database;
use App\Core\Session;
use App\Core\Middleware;
use App\Core\Validator;

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
        $perPage = 9; // Comercios por página
        $offset = ($page - 1) * $perPage;

        try {
            // Obtener comercios activos según los filtros aplicados con paginación
            $businesses = Business::getAll($search, $categoryId, $perPage, $offset);
            $totalBusinesses = Business::countAll($search, $categoryId);
            $totalPages = ceil($totalBusinesses / $perPage);

            // Obtener todas las categorías para el selector del filtro
            $categories = Category::getParents();

            $viewPath = ROOT_DIR . '/resources/views/business/list.php';
            if (!file_exists($viewPath)) {
                die("Vista no encontrada: " . $viewPath);
            }
            require_once $viewPath;
        } catch (\Throwable $e) {
            // Mostrar el error (útil en desarrollo; en producción se debería loguear y mostrar una página amigable)
            echo "Error: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine();
        }
    }

    /**
     * Muestra la ficha detallada de un comercio (GET /business/{id}).
     * Carga sus productos y horarios de apertura (Enfoque exclusivo: Productos físicos).
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

        // Cargar únicamente los datos relacionados necesarios
        $products = $business->getProducts();   // Productos físicos disponibles para compra
        $schedules = $business->getSchedules(); // Horarios de atención al público

        require ROOT_DIR . '/resources/views/business/detail.php';
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

            // Mapear los datos sin contaminar los modelos
            $businessesData = array_map(function ($business) {
                return [
                    'id' => $business->id,
                    'nombre' => $business->nombre,
                    'descripcion' => $business->descripcion,
                    'telefono' => $business->telefono,
                    'email' => $business->email,
                    'web' => $business->web,
                    'categorias' => $business->categorias,
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

            // Preparar datos del comercio con relaciones sin contaminar el modelo
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

    // =========================================================================
    // ZONA PRIVADA — ASISTENTE DE CONFIGURACIÓN (ONBOARDING)
    // =========================================================================

    /**
     * Muestra el asistente de configuración inicial para comercios (GET /business/setup).
     * Custodiado para que solo accedan cuentas BUSINESS sin perfil creado.
     */
    public function showSetup()
    {
        // El guardián verifica que esté logueado, sea BUSINESS y NO tenga un business_id activo
        Middleware::requireBusinessPending();

        // Recuperar datos viejos por si hubo un error de validación previo
        $oldInput = Session::get('setup_old', []);
        Session::remove('setup_old'); // Limpiar para visitas limpias

        // 1. Consultamos las categorías padre de la base de datos
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $stmt->execute();
        $categorias_padre = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Inicializamos los campos para evitar errores de "Key indefinida" en la vista
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
     * Procesa el formulario del asistente de configuración (POST /business/setup).
     */
    public function setup()
    {
        Middleware::requireBusinessPending();

        // 1. Validar el token de seguridad CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'La petición ha caducado. Inténtalo de nuevo.');
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            // 2. PROCESAR Y VALIDAR TODO EL FORMULARIO DE GOLPE
            $formData = \App\Core\BusinessFormHandler::process($_POST);

            // 🔥 SEGURIDAD MÁXIMA: Forzamos el ID del usuario logueado desde la sesión.
            // Esto evita que un usuario malicioso inyecte un 'user_id' diferente en el POST.
            $formData['user_id'] = Session::get('user_id');

            // 3. Validación específica del teléfono (9 dígitos exactos)
            // Tip: Si quieres, puedes mover esta regex dentro de tu BusinessFormHandler más adelante.
            if (!preg_match('/^\d{9}$/', $formData['telefono'])) {
                throw new \InvalidArgumentException('El número de teléfono debe constar exactamente de 9 dígitos numéricos.');
            }

            // 4. PROCESAR IMÁGENES CON TU MÉTODO UNIFICADO
            // Al ser un Alta, no le pasamos imágenes previas (asume null por defecto)
            $images = $uploader->uploadBusinessImages($_FILES);
        } catch (\InvalidArgumentException $e) {
            // Captura errores de validación de campos de texto o del teléfono
            Session::setFlash('error', $e->getMessage());
            Session::set('setup_old', $_POST); // Almacenar para repoblar el formulario
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        } catch (\Exception $e) {
            // Captura errores de imágenes (Formatos no válidos o > 5MB)
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // ==========================================
        // 5. PERSISTENCIA ATÓMICA EN BASE DE DATOS
        // ==========================================
        $db = Database::getInstance()->getConnection();

        try {
            // 🔑 INICIAMOS LA TRANSACCIÓN
            $db->beginTransaction();

            // PASO A: Insertar los datos básicos en la tabla business
            // Mantenemos tu regla de negocio: el auto-registro se crea como activo (1) directamente
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
                $images['logo_path'], // Ruta limpia (ej: "uploads/businesses/logo_xyz.png")
                $images['hero_path']   // Ruta limpia (ej: "uploads/businesses/hero_xyz.webp")
            ]);

            // Capturamos el ID asignado por MySQL para el nuevo comercio
            $businessId = $db->lastInsertId();

            // PASO B: Insertar la localización en la tabla address
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

            // Capturamos el ID generado para la dirección física
            $addressId = $db->lastInsertId();

            // PASO C: Construir el puente relacional en la tabla intermedia
            $stmtPivot = $db->prepare("INSERT INTO business_address (business_id, address_id) VALUES (?, ?)");
            $stmtPivot->execute([$businessId, $addressId]);

            // Si todo es consistente, consolidamos los inserts en disco de forma atómica
            $db->commit();

            // Inyectamos el ID recién creado en la sesión de este usuario
            Session::set('business_id', $businessId);

            Session::setFlash('success', '¡Enhorabuena! El perfil de tu comercio se ha configurado correctamente. Ya puedes gestionar tus productos.');

            session_write_close();
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        } catch (\Exception $e) {
            // Si algo falla, revertimos cualquier registro parcial para evitar filas huérfanas
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
