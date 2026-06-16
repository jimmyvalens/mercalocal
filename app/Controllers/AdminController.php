<?php
// =========================================================
// app/Controllers/AdminController.php — Panel de administración
// Accesible únicamente para usuarios con rol ADMIN.
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use App\Core\FileUploader;
use PDO;

/**
 * Clase AdminController
 *
 * Gestiona de forma centralizada las acciones del administrador global:
 * aprobación, edición y filtrado estratégico de comercios, control de estadísticas
 * y moderación de productos delegados.
 */
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

        // TODO: Implementar la evolución mensual en el modelo Stat más adelante
        $evolution = [];

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
        $status = $_GET['status'] ?? ''; // Ahora recibe: 'PENDING', 'ACTIVE' o 'SUSPENDED'
        $business_type = $_GET['type'] ?? ''; // Nuevo filtro estratégico: 'PRODUCTS', 'SERVICES', 'HYBRID'
        $category = $_GET['category'] ?? '';

        // Consulta base adaptada al inglés técnico de la base de datos
        $sql = "SELECT b.*, u.first_name as owner_name, u.email as owner_email,
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
            $sql .= " AND (b.name LIKE ? OR b.email LIKE ? OR u.first_name LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro profesional por Estado de moderación (ENUM)
        if ($status !== '' && in_array($status, ['PENDING', 'ACTIVE', 'SUSPENDED'])) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        // Filtro estratégico por Tipo de Negocio (ENUM)
        if ($business_type !== '' && in_array($business_type, ['PRODUCTS', 'SERVICES', 'HYBRID'])) {
            $sql .= " AND b.business_type = ?";
            $params[] = $business_type;
        }

        // Filtro por Categoría
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
    public function businessDetail(int $id)
    {
        $db = Database::getInstance()->getConnection();

        // Obtener información del comercio adaptado a las columnas en inglés
        $stmt = $db->prepare(
            "SELECT b.*, u.first_name as owner_name, u.email as owner_email, u.phone as owner_phone
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

        // 1. Traer lista de usuarios comerciantes o posibles dueños
        $stmt = $db->query("SELECT id, first_name as nombre, email FROM user ORDER BY first_name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Traer la lista de categorías de la base de datos
        $stmtCat = $db->query("SELECT id, nombre FROM category ORDER BY nombre ASC");
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // Carga del formulario apuntando a la vista con guion medio corregido
        require_once ROOT_DIR . '/resources/views/admin/business-form.php';
    }

    /**
     * Procesa el guardado de un nuevo comercio.
     * POST /admin/business/store
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // 1. Sanitizar y capturar datos del formulario
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $web = trim($_POST['web'] ?? '');
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

        $business_type = $_POST['business_type'] ?? 'PRODUCTS';
        $status = $_POST['status'] ?? 'ACTIVE';

        if (empty($nombre) || !$categoria_id || !$user_id) {
            Session::setFlash('error', 'Por favor, rellena todos los campos obligatorios (*).');
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }

        $logo_path = null;
        $hero_path = null;

        try {
            // 2. Subida de archivos usando FileUploader
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoUploader = new FileUploader(ROOT_DIR . '/public/uploads/logos');
                $logo_path = $logoUploader->upload($_FILES['logo'], 'logo_');
            }

            if (isset($_FILES['hero']) && $_FILES['hero']['error'] === UPLOAD_ERR_OK) {
                $heroUploader = new FileUploader(ROOT_DIR . '/public/uploads/heroes');
                $hero_path = $heroUploader->upload($_FILES['hero'], 'hero_');
            }

            // 3. Insertar en la base de datos alineado al inglés técnico
            $sql = "INSERT INTO business (name, category_id, user_id, email, phone, address, description, website, is_active, business_type, status, logo_path, hero_path, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $nombre,
                $categoria_id,
                $user_id,
                $email,
                $telefono,
                $direccion,
                $descripcion,
                $web,
                $activo,
                $business_type,
                $status,
                $logo_path,
                $hero_path
            ]);

            Session::setFlash('success', 'Comercio creado correctamente.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }
    }

    /**
     * Muestra el formulario para editar un comercio existente.
     * GET /admin/business/{id}/edit
     */
    public function edit(int $id)
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

        $stmtUsers = $db->query("SELECT id, first_name as nombre, email FROM user ORDER BY first_name ASC");
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $stmtCat = $db->query("SELECT id, nombre FROM category ORDER BY nombre ASC");
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // Reutiliza el formulario apuntando a la vista con guion medio corregido
        require_once ROOT_DIR . '/resources/views/admin/business-form.php';
    }

    /**
     * Procesa la actualización de un comercio existente.
     * POST /admin/business/{id}/update
     */
    public function update(int $id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            Session::setFlash('error', 'El comercio que intentas editar no existe.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $web = trim($_POST['web'] ?? '');
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
        $business_type = $_POST['business_type'] ?? 'PRODUCTS';
        $status = $_POST['status'] ?? 'ACTIVE';

        if (empty($nombre) || !$categoria_id || !$user_id) {
            Session::setFlash('error', 'Los campos con (*) son obligatorios.');
            header('Location: ' . BASE_URL . "/admin/business/{$id}/edit");
            exit;
        }

        $logo_path = $business['logo_path'];
        $hero_path = $business['hero_path'];

        try {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoUploader = new FileUploader(ROOT_DIR . '/public/uploads/logos');
                $logo_path = $logoUploader->upload($_FILES['logo'], 'logo_');

                if (!empty($business['logo_path']) && file_exists(ROOT_DIR . '/public/' . $business['logo_path'])) {
                    @unlink(ROOT_DIR . '/public/' . $business['logo_path']);
                }
            }

            if (isset($_FILES['hero']) && $_FILES['hero']['error'] === UPLOAD_ERR_OK) {
                $heroUploader = new FileUploader(ROOT_DIR . '/public/uploads/heroes');
                $hero_path = $heroUploader->upload($_FILES['hero'], 'hero_');

                if (!empty($business['hero_path']) && file_exists(ROOT_DIR . '/public/' . $business['hero_path'])) {
                    @unlink(ROOT_DIR . '/public/' . $business['hero_path']);
                }
            }

            // Actualizar registro en la BD usando los nuevos campos en inglés
            $sql = "UPDATE business SET 
                        name = ?, category_id = ?, user_id = ?, email = ?, phone = ?, 
                        address = ?, description = ?, website = ?, is_active = ?, business_type = ?, 
                        status = ?, logo_path = ?, hero_path = ? 
                    WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $nombre,
                $categoria_id,
                $user_id,
                $email,
                $telefono,
                $direccion,
                $descripcion,
                $web,
                $activo,
                $business_type,
                $status,
                $logo_path,
                $hero_path,
                $id
            ]);

            Session::setFlash('success', 'Comercio actualizado con éxito.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . "/admin/business/{$id}/edit");
            exit;
        }
    }

    public function delete(int $id)
    {
        $db = Database::getInstance()->getConnection();
        // Soft delete sincronizado con la columna 'is_active'
        $stmt = $db->prepare('UPDATE business SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);

        Session::setFlash('success', 'Comercio deshabilitado correctamente.');
        header('Location: ' . BASE_URL . '/admin/businesses');
        exit;
    }

    // =========================================================
    // CRUD DE PRODUCTOS DELEGADO AL ADMINISTRADOR
    // =========================================================

    public function createProduct(int $businessId)
    {
        $db = Database::getInstance()->getConnection();
        $categories = $db->query("SELECT * FROM category ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/admin/product_form.php';
    }

    public function storeProduct(int $businessId)
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

    public function editProduct(int $id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM product WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $categories = $db->query("SELECT * FROM category ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/product_form.php';
    }

    public function updateProduct(int $id)
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

    public function deleteProduct(int $id)
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
