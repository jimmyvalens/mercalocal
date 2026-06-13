<?php
// =========================================================
// app/Controllers/AdminController.php — Panel de administración
// Accesible únicamente para usuarios con rol ADMIN.
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use PDO;

class AdminController
{
    public function __construct()
    {
        \App\Core\Middleware::requireRole('ADMIN');
    }

    /**
     * Muestra el panel de administración (GET /admin/dashboard).
     */
    public function index()
    {
        // Estadísticas generales para la vista de evolución
        $stats = \App\Models\Stat::getAdminStats();

        // Obtenemos desglose de evolución mensual/reciente si el modelo lo permite
        $evolution = method_exists('\App\Models\Stat', 'getMonthlyEvolution')
            ? \App\Models\Stat::getMonthlyEvolution()
            : [];

        require_once ROOT_DIR . '/resources/views/admin/dashboard.php';
    }

    /**
     * Redirección limpia para "Admin Test" evitando modales de error
     * GET /admin/test
     */
    public function adminTest()
    {
        // Al estar validados por el constructor, ya sabemos que es ADMIN
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }

    /**
     * Listado completo de comercios con Buscador y Filtros dinámicos (GET /admin/businesses)
     */
    public function businesses()
    {
        $db = Database::getInstance()->getConnection();

        // Recoger filtros de la URL
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';

        // Consulta base
        $sql = "SELECT b.*, u.nombre as owner_name, u.email as owner_email,
                       COUNT(DISTINCT p.id) as product_count,
                       COUNT(DISTINCT s.id) as service_count
                FROM business b
                JOIN user u ON b.user_id = u.id
                LEFT JOIN product p ON p.business_id = b.id AND p.activo = 1
                LEFT JOIN service s ON s.business_id = b.id AND s.activo = 1
                WHERE 1=1";

        $params = [];

        // Filtro por término de búsqueda (Nombre, email o teléfono)
        if ($search !== '') {
            $sql .= " AND (b.nombre LIKE ? OR b.email LIKE ? OR u.nombre LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro por Estado (Activo / Inactivo)
        if ($status !== '') {
            $sql .= " AND b.activo = ?";
            $params[] = ($status === 'active') ? 1 : 0;
        }

        // Filtro por Categoría (Requiere relación con categorías en tabla intermedia o campo si existe)
        if ($category !== '') {
            $sql .= " AND b.id IN (SELECT DISTINCT business_id FROM product WHERE category_id = ?)";
            $params[] = $category;
        }

        $sql .= " GROUP BY b.id ORDER BY b.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener categorías disponibles para poblar el selector del buscador
        $catStmt = $db->query("SELECT * FROM category ORDER BY nombre ASC");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/businesses.php';
    }

    /**
     * Detalle de un comercio específico con estadísticas individuales
     * GET /admin/business/{id}
     */
    public function businessDetail($id)
    {
        $db = Database::getInstance()->getConnection();

        // Obtener información del comercio
        $stmt = $db->prepare(
            "SELECT b.*, u.nombre as owner_name, u.email as owner_email, u.telefono as owner_phone
             FROM business b
             JOIN user u ON b.user_id = u.id
             WHERE b.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            Session::setFlash('error', 'Comercio no encontrado.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        // Productos del comercio
        $stmt = $db->prepare(
            "SELECT p.*, c.nombre as category_name
             FROM product p
             LEFT JOIN category c ON p.category_id = c.id
             WHERE p.business_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Servicios del comercio
        $stmt = $db->prepare(
            "SELECT s.*, c.nombre as category_name
             FROM service s
             LEFT JOIN category c ON s.category_id = c.id
             WHERE s.business_id = ?
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([$id]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estadísticas avanzadas del comercio
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total_sales,
                    IFNULL(SUM(oi.cantidad * oi.precio), 0) as total_revenue
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PAGADO'"
        );
        $stmt->execute([$id]);
        $businessStats = $stmt->fetch(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/business-detail.php';
    }

    /**
     * Muestra el formulario para crear un nuevo comercio.
     * GET /admin/business/create
     */
    public function create()
    {
        $db = Database::getInstance()->getConnection();
        // Traer lista de usuarios comerciantes o posibles dueños para asociarlos
        $stmt = $db->query("SELECT id, nombre, email FROM user ORDER BY nombre ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/business_form.php';
    }

    /**
     * Guarda un nuevo comercio en la base de datos de manera consistente.
     * POST /admin/business/store
     */
    public function store()
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $web = trim($_POST['web'] ?? '');
        $user_id = $_POST['user_id'] ?? null; // Forzar asignación de propietario
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Si el admin no selecciona dueño, se auto-asigna de forma temporal o por defecto
        if (!$user_id) {
            $user_id = $_SESSION['user']['id'] ?? 1;
        }

        $logoPath = null;
        $heroPath = null;
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            if (!empty($_FILES['logo']['tmp_name'])) {
                $logoPath = $uploader->upload($_FILES['logo'], 'logo_');
            }
            if (!empty($_FILES['hero']['tmp_name'])) {
                $heroPath = $uploader->upload($_FILES['hero'], 'hero_');
            }
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }

        // Si se solicita vista previa y no persistencia real inmediata
        if (isset($_POST['is_preview']) && $_POST['is_preview'] == '1') {
            // Se guardan los datos en flash para renderizarlos en una capa modal/mockup
            Session::setFlash('preview_business', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/create?preview=true');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO business (nombre, descripcion, telefono, email, web, user_id, activo, logo_path, hero_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nombre, $descripcion, $telefono, $email, $web, $user_id, $activo, $logoPath, $heroPath]);

        Session::setFlash('success', 'Comercio creado correctamente.');
        header('Location: ' . BASE_URL . '/admin/businesses');
        exit;
    }

    public function edit($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM business WHERE id = ?');
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            Session::setFlash('error', 'Comercio no encontrado.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        $stmtUsers = $db->query("SELECT id, nombre, email FROM user ORDER BY nombre ASC");
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/business_form.php';
    }

    public function update($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM business WHERE id = ?');
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            Session::setFlash('error', 'Comercio no encontrado.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? $business['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? $business['descripcion']);
        $telefono = trim($_POST['telefono'] ?? $business['telefono']);
        $email = trim($_POST['email'] ?? $business['email']);
        $web = trim($_POST['web'] ?? $business['web']);
        $user_id = $_POST['user_id'] ?? $business['user_id'];
        $activo = isset($_POST['activo']) ? 1 : 0;

        $logoPath = $business['logo_path'];
        $heroPath = $business['hero_path'];
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            if (!empty($_FILES['logo']['tmp_name'])) {
                $newLogo = $uploader->upload($_FILES['logo'], 'logo_');
                if ($newLogo) $logoPath = $newLogo;
            }
            if (!empty($_FILES['hero']['tmp_name'])) {
                $newHero = $uploader->upload($_FILES['hero'], 'hero_');
                if ($newHero) $heroPath = $newHero;
            }
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        }

        $stmt = $db->prepare('UPDATE business SET nombre = ?, descripcion = ?, telefono = ?, email = ?, web = ?, user_id = ?, activo = ?, logo_path = ?, hero_path = ? WHERE id = ?');
        $stmt->execute([$nombre, $descripcion, $telefono, $email, $web, $user_id, $activo, $logoPath, $heroPath, $id]);

        Session::setFlash('success', 'Comercio actualizado correctamente.');
        header('Location: ' . BASE_URL . '/admin/businesses');
        exit;
    }

    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE business SET activo = 0 WHERE id = ?');
        $stmt->execute([$id]);
        Session::setFlash('success', 'Comercio deshabilitado correctamente.');
        header('Location: ' . BASE_URL . '/admin/businesses');
        exit;
    }

    // =========================================================
    // CRUD DE PRODUCTOS DELEGADO AL ADMINISTRADOR
    // =========================================================

    /**
     * Muestra el formulario de creación de producto para un comercio
     * GET /admin/business/{business_id}/product/create
     */
    public function createProduct($businessId)
    {
        $db = Database::getInstance()->getConnection();

        // Obtener categorías para el combo select
        $stmt = $db->query("SELECT * FROM category ORDER BY nombre ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/product_form.php';
    }

    /**
     * Guarda el producto creado por el Admin
     * POST /admin/business/{business_id}/product/store
     */
    public function storeProduct($businessId)
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $category_id = $_POST['category_id'] ?? null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        $imagePath = null;

        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/img/products');
        try {
            if (!empty($_FILES['imagen']['tmp_name'])) {
                $imagePath = $uploader->upload($_FILES['imagen'], 'prod_');
            }
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . "/admin/business/$businessId/product/create");
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO product (business_id, category_id, nombre, descripcion, precio, activo, imagen_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$businessId, $category_id, $nombre, $descripcion, $precio, $activo, $imagePath]);

        Session::setFlash('success', 'Producto agregado correctamente por el Administrador.');
        header('Location: ' . BASE_URL . '/admin/business/' . $businessId);
        exit;
    }

    /**
     * Muestra formulario de edición de un producto
     * GET /admin/product/{id}/edit
     */
    public function editProduct($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM product WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $categories = $db->query("SELECT * FROM category ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/product_form.php';
    }

    /**
     * Modifica el producto
     * POST /admin/product/{id}/update
     */
    public function updateProduct($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM product WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $nombre = trim($_POST['nombre'] ?? $product['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? $product['descripcion']);
        $precio = floatval($_POST['precio'] ?? $product['precio']);
        $category_id = $_POST['category_id'] ?? $product['category_id'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        $imagePath = $product['imagen_path'];

        if (!empty($_FILES['imagen']['tmp_name'])) {
            $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/img/products');
            try {
                $newImg = $uploader->upload($_FILES['imagen'], 'prod_');
                if ($newImg) $imagePath = $newImg;
            } catch (\Exception $e) {
                Session::setFlash('error', $e->getMessage());
                header('Location: ' . BASE_URL . "/admin/product/$id/edit");
                exit;
            }
        }

        $stmt = $db->prepare('UPDATE product SET nombre = ?, descripcion = ?, precio = ?, category_id = ?, activo = ?, imagen_path = ? WHERE id = ?');
        $stmt->execute([$nombre, $descripcion, $precio, $category_id, $activo, $imagePath, $id]);

        Session::setFlash('success', 'Producto actualizado correctamente.');
        header('Location: ' . BASE_URL . '/admin/business/' . $product['business_id']);
        exit;
    }

    /**
     * Desactiva un producto (Soft Delete)
     * POST /admin/product/{id}/delete
     */
    public function deleteProduct($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT business_id FROM product WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('UPDATE product SET activo = 0 WHERE id = ?');
        $stmt->execute([$id]);

        Session::setFlash('success', 'Producto deshabilitado por el administrador.');
        header('Location: ' . BASE_URL . '/admin/business/' . $product['business_id']);
        exit;
    }
}
