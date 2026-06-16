<?php
// =========================================================
// app/Controllers/BusinessDashboardController.php
// Controlador del panel de control privado del comercio.
// Gestiona tres flujos diferenciados:
//   · index()     — Panel principal con estadísticas, pedidos y reservas
//   · setup()     — Formulario de configuración inicial del perfil
//   · saveSetup() — Procesamiento y guardado del perfil del comercio
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use PDO;

class BusinessDashboardController
{
    public function __construct()
    {
        // El middleware ya restringe de forma global el acceso a nivel de enrutamiento
        \App\Core\Middleware::requireRole('BUSINESS');
    }

    /**
     * Muestra el panel de control del comercio (GET /business/dashboard).
     */
    public function index()
    {
        $userId = Session::get('user_id');
        $db = Database::getInstance()->getConnection();

        // Buscar el perfil de comercio vinculado al usuario actual
        $stmt = $db->prepare('SELECT * FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si todavía no ha creado el perfil, redirigir al asistente
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $bid = $business['id'];
        $stats = [];

        // ── Estadísticas rápidas del panel ──

        // Total de productos publicados
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ?');
        $stmt->execute([$bid]);
        $stats['products'] = (int)$stmt->fetch()['total'];

        // Total de servicios publicados
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM service WHERE business_id = ?');
        $stmt->execute([$bid]);
        $stats['services'] = (int)$stmt->fetch()['total'];

        // Total de reservas activas (excluye canceladas)
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservation WHERE business_id = ? AND status != 'CANCELLED'");
        $stmt->execute([$bid]);
        $stats['reservations'] = (int)$stmt->fetch()['total'];

        // ── Últimos 5 pedidos recibidos que incluyan productos de este comercio ──
        $stmt = $db->prepare(
            "SELECT p.id, p.total, p.status, p.created_at, u.first_name as client_name
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             JOIN user u        ON u.id  = p.user_id
             WHERE pr.business_id = ?
             GROUP BY p.id ORDER BY p.created_at DESC LIMIT 5"
        );
        $stmt->execute([$bid]);
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Próximas 10 reservas pendientes desde hoy ──
        $stmt = $db->prepare(
            "SELECT r.*, u.first_name as client_name, u.phone as client_phone,
                    s.name as service_name
             FROM reservation r
             JOIN user u ON u.id = r.user_id
             LEFT JOIN reservation_item ri ON ri.reservation_id = r.id
             LEFT JOIN service s ON s.id = ri.service_id
             WHERE r.business_id = ? AND r.date >= CURDATE() AND r.status != 'CANCELLED'
             ORDER BY r.date, r.start_time LIMIT 10"
        );
        $stmt->execute([$bid]);
        $upcomingReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/business/dashboard.php';
    }

    /**
     * Lista completa de pedidos que incluyen productos del comercio.
     * GET /business/dashboard/orders
     */
    public function orders()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $stmt = $db->prepare(
            "SELECT p.id, p.total, p.status, p.created_at, u.first_name as client_name
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             JOIN user u        ON u.id  = p.user_id
             WHERE pr.business_id = ?
             GROUP BY p.id ORDER BY p.created_at DESC"
        );
        $stmt->execute([$business['id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/business/orders.php';
    }

    /**
     * Muestra el formulario de configuración inicial del comercio (GET /business/setup).
     */
    public function setup()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        if ($stmt->fetch()) {
            header('Location: ' . BASE_URL . '/business/dashboard');
            exit;
        }

        require_once ROOT_DIR . '/resources/views/business/setup.php';
    }

    // ---------- servicios ------------------------------------------------

    public function servicesIndex()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $services = \App\Models\Service::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/services/index.php';
    }

    public function servicesCreate()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $cats = $db->query('SELECT id, name FROM category ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/business/services/form.php';
    }

    public function servicesStore()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services/create');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // Validar campos obligatorios
        $required = ['nombre', 'descripcion', 'duracion', 'precio'];
        foreach ($required as $field) {
            if (empty($_POST[$field] ?? null)) {
                Session::setFlash('error', 'Campo obligatorio faltante: ' . ucfirst($field));
                header('Location: ' . BASE_URL . '/business/dashboard/services/create');
                exit;
            }
        }

        // Estructura de propiedades del modelo Service en inglés
        $data = [
            'business_id' => $business['id'],
            'category_id' => $_POST['category_id'] ?? null,
            'name' => trim($_POST['nombre']),
            'description' => trim($_POST['descripcion'] ?? ''),
            'duration' => intval($_POST['duracion'] ?? 0),
            'price' => floatval($_POST['precio'] ?? 0),
            'is_active' => isset($_POST['activo']) ? 1 : 0,
        ];
        try {
            \App\Models\Service::create($data);
            Session::setFlash('success', 'Servicio creado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al crear el servicio: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/services/create');
            exit;
        }
    }

    public function servicesEdit(int $id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $service = \App\Models\Service::findById($id);
        if (!$service || $service->business_id != $business['id']) {
            Session::setFlash('error', 'Servicio no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }
        $cats = $db->query('SELECT id, name FROM category ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/business/services/form.php';
    }

    public function servicesUpdate(int $id)
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services/' . $id . '/edit');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $service = \App\Models\Service::findById($id);
        if (!$service || $service->business_id != $business['id']) {
            Session::setFlash('error', 'Servicio no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }

        $required = ['nombre', 'descripcion', 'duracion', 'precio'];
        foreach ($required as $field) {
            if (empty($_POST[$field] ?? null)) {
                Session::setFlash('error', 'Campo obligatorio faltante: ' . ucfirst($field));
                header('Location: ' . BASE_URL . '/business/dashboard/services/' . $id . '/edit');
                exit;
            }
        }

        $data = [
            'category_id' => $_POST['category_id'] ?? null,
            'name' => trim($_POST['nombre']),
            'description' => trim($_POST['descripcion']),
            'duration' => intval($_POST['duracion']),
            'price' => floatval($_POST['precio']),
            'is_active' => isset($_POST['activo']) ? 1 : 0,
        ];
        try {
            \App\Models\Service::update($id, $data);
            Session::setFlash('success', 'Servicio actualizado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al actualizar el servicio: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/services/' . $id . '/edit');
            exit;
        }
    }

    public function servicesDelete(int $id)
    {
        // Protección CSRF (Recomendado enviar mediante formulario POST o Ajax con Token)
        $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token de seguridad expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $service = \App\Models\Service::findById($id);
        if (!$service || $service->business_id != $business['id']) {
            Session::setFlash('error', 'Servicio no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }
        try {
            \App\Models\Service::delete($id);
            Session::setFlash('success', 'Servicio eliminado.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/services');
        exit;
    }

    // ---------- horarios ------------------------------------------------

    public function schedulesIndex()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $schedules = \App\Models\Schedule::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/schedules/index.php';
    }

    public function schedulesStore()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida.');
            header('Location: ' . BASE_URL . '/business/dashboard/schedules');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $data = [
            'business_id' => $business['id'],
            'day_of_week' => intval($_POST['dia_semana'] ?? 0),
            'open_time' => $_POST['hora_apertura'] ?? '09:00',
            'close_time' => $_POST['hora_cierre'] ?? '18:00',
        ];
        try {
            \App\Models\Schedule::create($data);
            Session::setFlash('success', 'Horario añadido.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al guardar horario: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/schedules');
        exit;
    }

    public function schedulesDelete(int $id)
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida.');
            header('Location: ' . BASE_URL . '/business/dashboard/schedules');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        try {
            \App\Models\Schedule::delete($id);
            Session::setFlash('success', 'Horario eliminado.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al eliminar horario: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/schedules');
        exit;
    }

    /**
     * Guarda el perfil del comercio enviado desde el formulario (POST /business/setup).
     * Valida los campos obligatorios e inserta el registro en la tabla `business`.
     * Tras guardar, redirige al panel de control con un mensaje de bienvenida.
     */
    public function saveSetup()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // Recoger y sanear los datos del formulario
        $name = trim($_POST['nombre'] ?? '');
        $description = trim($_POST['descripcion'] ?? '');
        $phone = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['web'] ?? '') ?: null; // Opcional; si está vacío se guarda como NULL

        // Comprobar campos obligatorios
        if (empty($name) || empty($description) || empty($phone) || empty($email)) {
            Session::setFlash('error', 'Por favor rellena todos los campos obligatorios.');
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        // Insertar el nuevo comercio en la BD con estructura unificada
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO business (user_id, name, description, phone, email, website, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([Session::get('user_id'), $name, $description, $phone, $email, $website]);

        Session::setFlash('success', '¡Tu comercio "' . $name . '" está listo en Mercalocal!');
        header('Location: ' . BASE_URL . '/business/dashboard');
        exit;
    }

    /**
     * Listado de productos para el comercio (GET /business/dashboard/products)
     */
    public function productsIndex()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $products = \App\Models\Product::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/products/index.php';
    }

    public function productsCreate()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        // Cargar categorías para el selector
        $cats = $db->query('SELECT id, name FROM category ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    public function productsStore()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products/create');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $imageName = null;

        // ── Procesar carga de imagen ──
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];
            $productSlug = preg_replace('/[^a-z0-9_-]/i', '', $_POST['nombre'] ?? 'product');

            // Validar tipo de archivo
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes)) {
                Session::setFlash('error', 'Solo se permiten imágenes (JPG, PNG, GIF, WebP).');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }

            // Validar tamaño (máx 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                Session::setFlash('error', 'La imagen no debe exceder 5MB.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }

            // Generar nombre único estructurado
            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            $imageName = 'product_' . $productSlug . '_' . $business['id'] . '_' . time() . '.' . $ext;
            $uploadDir = ROOT_DIR . '/public/img/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $imageName;

            // Mover archivo y comprimir
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    \App\Core\ImageHelper::compress($uploadPath, $uploadPath, 80, 1200, 1200);
                } catch (\Throwable $e) {
                    // Si falla la compresión, se mantiene la original
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }
        }

        $data = [
            'business_id' => $business['id'],
            'category_id' => $_POST['category_id'] ?? null,
            'name' => trim($_POST['nombre'] ?? ''),
            'description' => trim($_POST['descripcion'] ?? ''),
            'price' => floatval($_POST['precio'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'image' => $imageName,
            'is_active' => isset($_POST['activo']) ? 1 : 0,
        ];

        try {
            \App\Models\Product::create($data);
            Session::setFlash('success', 'Producto creado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al crear el producto: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/products/create');
            exit;
        }
    }

    public function productsEdit(int $id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $product = \App\Models\Product::findById($id);
        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }
        $cats = $db->query('SELECT id, name FROM category ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    public function productsUpdate(int $id)
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $product = \App\Models\Product::findById($id);
        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        $imageName = $product->image_path; // Mantener imagen actual por defecto

        // ── Procesar nueva imagen si se sube ──
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];
            $productSlug = preg_replace('/[^a-z0-9_-]/i', '', $_POST['nombre'] ?? 'product');

            // Validar tipo de archivo
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes)) {
                Session::setFlash('error', 'Solo se permiten imágenes (JPG, PNG, GIF, WebP).');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }

            // Validar tamaño (máx 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                Session::setFlash('error', 'La imagen no debe exceder 5MB.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }

            // Eliminar imagen anterior si existe
            if ($product->image_path) {
                $oldPath = ROOT_DIR . '/public/img/products/' . $product->image_path;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Generar nombre único estructurado
            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            $imageName = 'product_' . $productSlug . '_' . $business['id'] . '_' . time() . '.' . $ext;
            $uploadDir = ROOT_DIR . '/public/img/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $imageName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    \App\Core\ImageHelper::compress($uploadPath, $uploadPath, 80, 1200, 1200);
                } catch (\Throwable $e) {
                    // Si falla la compresión se mantiene la original
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }
        }

        $data = [
            'category_id' => $_POST['category_id'] ?? null,
            'name' => trim($_POST['nombre'] ?? ''),
            'description' => trim($_POST['descripcion'] ?? ''),
            'price' => floatval($_POST['precio'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'image' => $imageName,
            'is_active' => isset($_POST['activo']) ? 1 : 0,
        ];

        try {
            \App\Models\Product::update($id, $data);
            Session::setFlash('success', 'Producto actualizado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al actualizar el producto: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
            exit;
        }
    }

    public function productsDelete($id)
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        $product = \App\Models\Product::findById($id);
        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }
        try {
            // Eliminar imagen física del servidor si existe
            if ($product->image_path) {
                $imagePath = ROOT_DIR . '/public/img/products/' . $product->image_path;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            \App\Models\Product::delete($id);
            Session::setFlash('success', 'Producto eliminado exitosamente.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/products');
        exit;
    }

    // ---------- configuración --------------------------------------------

    /**
     * Muestra el formulario de edición del perfil del comercio.
     * GET /business/dashboard/settings
     */
    public function settings()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }
        require_once ROOT_DIR . '/resources/views/business/settings.php';
    }

    /**
     * Procesa la actualización de los datos del perfil del comercio.
     * POST /business/dashboard/settings/update
     */
    public function updateSettings()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $data = [
            'name' => trim($_POST['nombre'] ?? ''),
            'description' => trim($_POST['descripcion'] ?? ''),
            'phone' => trim($_POST['telefono'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['web'] ?? ''),
        ];

        if (empty($data['name']) || empty($data['description']) || empty($data['phone']) || empty($data['email'])) {
            Session::setFlash('error', 'Los campos nombre, descripción, teléfono y email son obligatorios.');
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        }

        try {
            $sql = "UPDATE business SET name = ?, description = ?, phone = ?, email = ?, website = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['phone'],
                $data['email'],
                $data['website'],
                $business['id'],
            ]);
            Session::setFlash('success', 'Perfil actualizado correctamente.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al actualizar: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/settings');
        exit;
    }
}
