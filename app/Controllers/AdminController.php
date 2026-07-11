<?php

/**
 * =========================================================
 * app/Controllers/AdminController.php — Panel de administración
 *
 * Controla la gestión de comercios y operaciones administrativas:
 * · Listado, búsqueda, filtrado y paginación de comercios
 * · Creación, edición y eliminación de comercios con direcciones
 * · Visualización de detalles y búsqueda de usuarios vía API
 * =========================================================
 */

namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use PDO;

class AdminController
{
    /**
     * Requiere rol ADMIN para acceder al controlador.
     *
     * @return void
     */
    public function __construct()
    {
        \App\Core\Middleware::requireRole('ADMIN');
    }

    /**
     * Muestra el panel de administración (GET /admin/dashboard).
     *
     * @return void
     */
    public function index()
    {
        $stats = \App\Models\Stat::getAdminStats();
        $evolution = method_exists('\App\Models\Stat', 'getMonthlyEvolution')
            ? \App\Models\Stat::getMonthlyEvolution()
            : [];
        require_once ROOT_DIR . '/resources/views/admin/dashboard.php';
    }

    /**
     * Redirección limpia para "Admin Test" evitando modales de error
     * GET /admin/test
     *
     * @return void
     */
    public function adminTest()
    {
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }

    /**
     * Listado completo de comercios con Buscador, Filtros dinámicos y Paginación (GET /admin/businesses)
     *
     * @return void
     */
    public function businesses()
    {
        $db = Database::getInstance()->getConnection();
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';
        $whereSql = " WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $whereSql .= " AND (b.nombre LIKE ? OR b.email LIKE ? OR u.nombre LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($status !== '') {
            $whereSql .= " AND b.activo = ?";
            $params[] = ($status === 'active') ? 1 : 0;
        }
        if ($category !== '') {
            $whereSql .= " AND b.id IN (SELECT DISTINCT business_id FROM product WHERE category_id = ?)";
            $params[] = $category;
        }
        $countSql = "SELECT COUNT(DISTINCT b.id) FROM business b JOIN user u ON b.user_id = u.id" . $whereSql;
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRows / $limit);
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $limit;
        $sql = "SELECT b.*, u.nombre as owner_name, u.email as owner_email,
               (SELECT COUNT(*) FROM product p WHERE p.business_id = b.id AND p.activo = 1) as product_count
        FROM business b
        JOIN user u ON b.user_id = u.id "
            . $whereSql
            . " ORDER BY b.created_at DESC 
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $catStmt = $db->query("SELECT * FROM category WHERE parent_id IS NULL OR parent_id = 0 ORDER BY nombre ASC");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalComercios = $db->query("SELECT COUNT(*) FROM business")->fetchColumn();
        $totalActivos = $db->query("SELECT COUNT(*) FROM business WHERE activo = 1")->fetchColumn();
        $totalInactivos = $totalComercios - $totalActivos;
        $totalProductos = $db->query("SELECT COUNT(*) FROM product")->fetchColumn();
        require_once ROOT_DIR . '/resources/views/admin/businesses.php';
    }

    /**
     * Detalle de un comercio específico con estadísticas individuales
     * GET /admin/business/{id}
     *
     * @param int $id
     * @return void
     */
    public function businessDetail(int $id)
    {
        $db = Database::getInstance()->getConnection();
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
        $stmt = $db->prepare(
            "SELECT p.*, c.nombre as category_name
             FROM product p
             LEFT JOIN category c ON p.category_id = c.id
             WHERE p.business_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total_sales,
                    IFNULL(SUM(oi.cantidad * oi.precio_unitario), 0) as total_revenue
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'COMPLETADO'"
        );
        $stmt->execute([$id]);
        $businessStats = $stmt->fetch(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/admin/business-detail.php';
    }

    /**
     * Muestra el formulario para crear un nuevo comercio.
     * GET /admin/business/create
     *
     * @return void
     */
    public function create()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, nombre, email FROM user ORDER BY nombre ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmtCategory = $db->query("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $categorias_padre = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
        require_once ROOT_DIR . '/resources/views/layout/business_form.php';
    }

    /**
     * Guarda un nuevo comercio y su dirección asociada.
     * POST /admin/business/store
     *
     * @return void
     */
    public function store()
    {
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Token no válido");
        }
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');
        try {
            $formData = \App\Core\BusinessFormHandler::process($_POST);
            $images = $uploader->uploadBusinessImages($_FILES);
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', 'Por favor, corrige los campos marcados en rojo.');
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmtAddr = $db->prepare("
                INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtAddr->execute([
                $formData['calle'],
                $formData['numero'],
                $formData['codigo_postal'],
                $formData['ciudad'],
                $formData['provincia']
            ]);
            $addressId = $db->lastInsertId();
            $stmtBus = $db->prepare("
                INSERT INTO business (nombre, descripcion, telefono, email, web, user_id, activo, logo_path, hero_path, id_categoria) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtBus->execute([
                $formData['nombre'],
                $formData['descripcion'],
                $formData['telefono'],
                $formData['email'],
                $formData['web'],
                $formData['user_id'],
                $formData['activo'],
                $images['logo_path'],
                $images['hero_path'],
                $formData['categoria_id']
            ]);
            $businessId = $db->lastInsertId();
            $stmtPivot = $db->prepare("
                INSERT INTO business_address (business_id, address_id) 
                VALUES (?, ?)
            ");
            $stmtPivot->execute([$businessId, $addressId]);
            $db->commit();
            Session::setFlash('success', 'Comercio creado correctamente con su ubicación.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::setFlash('error', 'Error al guardar en la base de datos: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }
    }

    /**
     * Carga el formulario de edición de un comercio.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('
        SELECT b.*, a.calle, a.numero, a.codigo_postal, a.ciudad, a.provincia 
        FROM business b
        LEFT JOIN business_address ba ON b.id = ba.business_id
        LEFT JOIN address a ON ba.address_id = a.id
        WHERE b.id = ?
    ');
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            Session::setFlash('error', 'Comercio no encontrado.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }
        $stmtUsers = $db->query("SELECT id, nombre, email FROM user ORDER BY nombre ASC");
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        $stmtCategory = $db->query("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $categorias_padre = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
        $stmtUser = $db->prepare('SELECT nombre, email FROM user WHERE id = ?');
        $stmtUser->execute([$business['user_id']]);
        $owner = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $business['owner_name'] = $owner ? $owner['nombre'] . ' (' . $owner['email'] . ')' : 'Sin propietario';
        require_once ROOT_DIR . '/resources/views/layout/business_form.php';
    }

    /**
     * Actualiza un comercio y su dirección asociada.
     * POST /admin/business/{id}/update
     *
     * @param int $id
     * @return void
     */
    public function update(int $id)
    {
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Token no válido");
        }
        $db = Database::getInstance()->getConnection();
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');
        $stmtCurrent = $db->prepare("SELECT logo_path, hero_path FROM business WHERE id = ?");
        $stmtCurrent->execute([$id]);
        $currentImages = $stmtCurrent->fetch(\PDO::FETCH_ASSOC) ?: ['logo_path' => null, 'hero_path' => null];
        try {
            $formData = \App\Core\BusinessFormHandler::process($_POST);
            $images = $uploader->uploadBusinessImages($_FILES, $currentImages['logo_path'], $currentImages['hero_path']);
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', 'Por favor, corrige los campos marcados en rojo.');
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        }
        try {
            $db->beginTransaction();
            $stmtBus = $db->prepare("
                UPDATE business 
                SET nombre = ?, descripcion = ?, telefono = ?, email = ?, web = ?, user_id = ?, activo = ?, logo_path = ?, hero_path = ?, id_categoria = ? 
                WHERE id = ?
            ");
            $stmtBus->execute([
                $formData['nombre'],
                $formData['descripcion'],
                $formData['telefono'],
                $formData['email'],
                $formData['web'],
                $formData['user_id'],
                $formData['activo'],
                $images['logo_path'],
                $images['hero_path'],
                $formData['categoria_id'],
                $id
            ]);
            $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
            $stmtGetAddr->execute([$id]);
            $addressId = $stmtGetAddr->fetchColumn();
            if ($addressId) {
                $stmtAddr = $db->prepare("
                    UPDATE address 
                    SET calle = ?, numero = ?, codigo_postal = ?, ciudad = ?, provincia = ? 
                    WHERE id = ?
                ");
                $stmtAddr->execute([
                    $formData['calle'],
                    $formData['numero'],
                    $formData['codigo_postal'],
                    $formData['ciudad'],
                    $formData['provincia'],
                    $addressId
                ]);
            } else {
                $stmtNewAddr = $db->prepare("INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)");
                $stmtNewAddr->execute([
                    $formData['calle'],
                    $formData['numero'],
                    $formData['codigo_postal'],
                    $formData['ciudad'],
                    $formData['provincia']
                ]);
                $newAddrId = $db->lastInsertId();
                $stmtPivot = $db->prepare("INSERT INTO business_address (business_id, address_id) VALUES (?, ?)");
                $stmtPivot->execute([$id, $newAddrId]);
            }
            $db->commit();
            Session::setFlash('success', 'Comercio actualizado correctamente.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::setFlash('error', 'Error al actualizar en la base de datos: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        }
    }

    /**
     * Elimina un comercio tras la validación CSRF.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id)
    {
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Error: Token CSRF no válido.");
        }
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT COUNT(*) FROM order_item ci 
                      JOIN product p ON ci.product_id = p.id 
                      WHERE p.business_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                Session::setFlash('error', 'No se puede eliminar el comercio porque hay clientes con productos en el carrito.');
            }
            $stmtGetProd = $db->prepare("SELECT id FROM product WHERE business_id = ?");
            $stmtGetProd->execute([$id]);
            $productos = $stmtGetProd->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($productos)) {
                $placeholdersProd = implode(',', array_fill(0, count($productos), '?'));
                $db->prepare("DELETE FROM order_item WHERE product_id IN ($placeholdersProd)")->execute($productos);
            }
            $db->prepare("DELETE FROM product WHERE business_id = ?")->execute([$id]);
            $stmtBusiness = $db->prepare("DELETE FROM business WHERE id = ?");
            $stmtBusiness->execute([$id]);
            $db->commit();
            header("Location: " . BASE_URL . "/admin/businesses");
            exit;
        } catch (\PDOException $e) {
            $db->rollBack();
            die("Error al eliminar el comercio: " . $e->getMessage());
        }
    }

    /**
     * Busca usuarios por término y devuelve JSON.
     *
     * @return void
     */
    public function apiSearch()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $query = $_GET['q'] ?? '';
        $query = trim($query);
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $sql = "SELECT id, nombre, email 
            FROM user 
            WHERE nombre LIKE :nombre OR email LIKE :email 
            LIMIT 10";
            $stmt = $db->prepare($sql);
            $searchTerm = '%' . $query . '%';
            $stmt->execute([
                'nombre' => $searchTerm,
                'email'  => $searchTerm
            ]);
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode($users);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
