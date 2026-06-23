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

        // die("¡Sí, se está ejecutando showSetup y la variable tiene: " . count($categorias_padre) . " elementos!");

        require ROOT_DIR . '/resources/views/business/setup.php';
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

        // 2. Saneamiento inicial de strings básicos
        $data = [
            'user_id'     => Session::get('user_id'),
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'categoria_id' => $_POST['categoria_id'] ?? null,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'telefono'    => preg_replace('/\s+/', '', $_POST['telefono'] ?? ''), // Limpiar espacios del teléfono
            'email'       => trim($_POST['email'] ?? ''),
            'web'         => trim($_POST['web'] ?? ''),
            'activo'      => 1 // Visible por defecto una vez rellenado el perfil
        ];

        // 3. Validación de campos obligatorios y formatos
        $validator = new Validator($_POST);
        $validator->required('nombre', 'El nombre comercial del negocio es obligatorio.')
            ->required('categoria_id', 'La categoría del comercio es obligatoria.')
            ->required('descripcion', 'Cuéntanos un poco qué ofrece tu comercio (descripción obligatoria).')
            ->required('telefono', 'El teléfono de contacto es obligatorio.')
            ->required('email', 'El correo electrónico comercial es obligatorio.');

        if (!$validator->isValid()) {
            Session::setFlash('error', implode(' ', $validator->getErrors()));
            Session::set('setup_old', $_POST); // Almacenar para repoblar el formulario
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // 4. Validación específica del teléfono (9 dígitos exactos)
        if (!preg_match('/^\d{9}$/', $data['telefono'])) {
            Session::setFlash('error', 'El número de teléfono debe constar exactamente de 9 dígitos numéricos.');
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // 5. Validación de formato de email comercial
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'La dirección de correo electrónico introducida no es válida.');
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $uploader = new \App\Core\FileUploader();

        $data['logo'] = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $data['logo'] = $uploader->upload($_FILES['logo'], 'uploads/logos/');
        }

        $data['hero'] = null;
        if (isset($_FILES['hero']) && $_FILES['hero']['error'] === UPLOAD_ERR_OK) {
            $data['hero'] = $uploader->upload($_FILES['hero'], 'uploads/heroes/');
        }

        // 6. Persistencia de datos y cierre del ciclo de aislamiento
        try {
            // Guardamos el comercio en la tabla `business`
            $businessId = Business::create($data);

            // ¡EL PASO CLAVE! Inyectamos el ID recién creado en la sesión de este usuario.
            // A partir de este milisegundo exacto, el middleware requireBusinessSetup()
            // le abrirá las puertas de todos sus paneles privados.
            Session::set('business_id', $businessId);

            Session::setFlash('success', '¡Enhorabuena! El perfil de tu comercio se ha configurado correctamente. Ya puedes gestionar tus productos y servicios.');

            session_write_close();
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Ocurrió un error inesperado al guardar los datos del comercio: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
    }
}
