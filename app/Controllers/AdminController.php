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
     * Listado completo de comercios con Buscador, Filtros dinámicos y Paginación (GET /admin/businesses)
     */
    public function businesses()
    {
        $db = Database::getInstance()->getConnection();

        // 1. Configuración de la paginación
        $limit = 10; // Número de comercios por página
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        // Recoger filtros de la URL
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';

        // Construir la condición WHERE dinámica compartida
        $whereSql = " WHERE 1=1";
        $params = [];

        // Filtro por término de búsqueda (Nombre, email o teléfono)
        if ($search !== '') {
            $whereSql .= " AND (b.nombre LIKE ? OR b.email LIKE ? OR u.nombre LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro por Estado (Activo / Inactivo)
        if ($status !== '') {
            $whereSql .= " AND b.activo = ?";
            $params[] = ($status === 'active') ? 1 : 0;
        }

        // Filtro por Categoría
        if ($category !== '') {
            $whereSql .= " AND b.id IN (SELECT DISTINCT business_id FROM product WHERE category_id = ?)";
            $params[] = $category;
        }

        // 2. OBTENER EL TOTAL DE REGISTROS (Esencial para calcular las páginas totales)
        $countSql = "SELECT COUNT(DISTINCT b.id) FROM business b JOIN user u ON b.user_id = u.id" . $whereSql;
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();

        // Calcular las páginas totales
        $totalPages = ceil($totalRows / $limit);
        if ($totalPages < 1) $totalPages = 1;

        // Ajustar la página actual si el usuario escribe un número mayor al total de páginas
        if ($page > $totalPages) $page = $totalPages;

        // Calcular el desplazamiento (OFFSET) para la base de datos
        $offset = ($page - 1) * $limit;

        // 3. CONSULTA PRINCIPAL LIMITADA (Trae solo el bloque de la página actual)
        $sql = "SELECT b.*, u.nombre as owner_name, u.email as owner_email,
                       COUNT(DISTINCT p.id) as product_count,
                       COUNT(DISTINCT s.id) as service_count
                FROM business b
                JOIN user u ON b.user_id = u.id
                LEFT JOIN product p ON p.business_id = b.id AND p.activo = 1
                LEFT JOIN service s ON s.business_id = b.id AND s.activo = 1"
            . $whereSql
            . " GROUP BY b.id 
                   ORDER BY b.created_at DESC 
                   LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener categorías disponibles para poblar el selector del buscador
        $catStmt = $db->query("SELECT * FROM category ORDER BY nombre ASC");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        // Requerimos la vista. Las variables $businesses, $categories, $page y $totalPages 
        // bajan listas y limpias hacia businesses.php
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
            "SELECT s.*, s.duracion_minutos AS duracion, c.nombre as category_name
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
                    IFNULL(SUM(oi.cantidad * oi.precio_unitario), 0) as total_revenue
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
     * Guarda un nuevo comercio y su dirección asociada.
     * POST /admin/business/store
     */
    public function store()
    {
        // 1. Validación CSRF
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Token no válido");
        }

        // 2. Recogida de datos del formulario
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $web = trim($_POST['web'] ?? '');
        $user_id = $_POST['user_id'] ?? null;
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Datos de dirección
        $calle = trim($_POST['calle'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');

        // 3. Gestión de archivos
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

        // 4. Transacción (Creación atómica)
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            // A) Insertar Dirección primero
            $stmtAddr = $db->prepare("
                INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtAddr->execute([$calle, $numero, $codigo_postal, $ciudad, $provincia]);
            $addressId = $db->lastInsertId();

            // B) Insertar Negocio
            $stmtBus = $db->prepare("
                INSERT INTO business (nombre, descripcion, telefono, email, web, user_id, activo, logo_path, hero_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtBus->execute([$nombre, $descripcion, $telefono, $email, $web, $user_id, $activo, $logoPath, $heroPath]);
            $businessId = $db->lastInsertId();

            // C) Insertar en tabla pivote para unir ambos
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
            $db->rollBack();
            // Limpiar archivos si se subieron pero la DB falló
            // (Opcional, pero recomendado si quieres ser muy estricto)
            Session::setFlash('error', 'Error al crear el comercio: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/create');
            exit;
        }
    }

    public function edit($id)
    {
        $db = Database::getInstance()->getConnection();

        // 1. Buscamos el negocio con sus direcciones
        $stmt = $db->prepare('
        SELECT b.*, a.calle, a.numero, a.codigo_postal, a.ciudad, a.provincia 
        FROM business b
        LEFT JOIN business_address ba ON b.id = ba.business_id
        LEFT JOIN address a ON ba.address_id = a.id
        WHERE b.id = ?
    ');
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        // Control de existencia (mejor ponerlo arriba para evitar consultas innecesarias si no existe)
        if (!$business) {
            Session::setFlash('error', 'Comercio no encontrado.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        // 2. Buscamos TODOS los usuarios para el desplegable de propietarios
        $stmtUsers = $db->query("SELECT id, nombre, email FROM user ORDER BY nombre ASC");
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        // 3. ¡EL CAMBIO CLAVE! Buscamos TODAS las categorías para el desplegable
        // (Nota: Si tu tabla de categorías se llama "categoria" o "categories" en plural, cámbialo aquí)
        $stmtCategory = $db->query("SELECT id, nombre FROM category ORDER BY nombre ASC");
        $category = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

        // 4. Buscamos el propietario actual de este negocio
        $stmtUser = $db->prepare('SELECT nombre, email FROM user WHERE id = ?');
        $stmtUser->execute([$business['user_id']]);
        $owner = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $business['owner_name'] = $owner ? $owner['nombre'] . ' (' . $owner['email'] . ')' : 'Sin propietario';

        // 5. Cargamos la vista con todas las variables listas
        require_once ROOT_DIR . '/resources/views/admin/business_form.php';
    }

    /**
     * Actualiza un comercio y su dirección asociada.
     * POST /admin/business/{id}/update
     */
    public function update($id)
    {
        // 1. Validación CSRF
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Token no válido");
        }

        // 2. Recogida de datos del formulario
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $web = trim($_POST['web'] ?? '');
        $user_id = $_POST['user_id'] ?? null;
        $activo = isset($_POST['activo']) ? 1 : 0;

        $calle = trim($_POST['calle'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');

        // 3. Gestión de archivos (solo si se suben nuevos)
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        // Obtenemos los paths actuales para no perderlos si no se sube imagen nueva
        $db = Database::getInstance()->getConnection();
        $stmtCurrent = $db->prepare("SELECT logo_path, hero_path FROM business WHERE id = ?");
        $stmtCurrent->execute([$id]);
        $currentImages = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

        $logoPath = $currentImages['logo_path'] ?? null;
        $heroPath = $currentImages['hero_path'] ?? null;

        try {
            if (!empty($_FILES['logo']['tmp_name'])) {
                $logoPath = $uploader->upload($_FILES['logo'], 'logo_');
            }
            if (!empty($_FILES['hero']['tmp_name'])) {
                $heroPath = $uploader->upload($_FILES['hero'], 'hero_');
            }
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        }

        // 4. Transacción
        try {
            $db->beginTransaction();

            // A) Actualizar el Negocio
            $stmtBus = $db->prepare("
                UPDATE business 
                SET nombre = ?, descripcion = ?, telefono = ?, email = ?, web = ?, user_id = ?, activo = ?, logo_path = ?, hero_path = ? 
                WHERE id = ?
            ");
            $stmtBus->execute([$nombre, $descripcion, $telefono, $email, $web, $user_id, $activo, $logoPath, $heroPath, $id]);

            // B) Localizar la dirección asociada y actualizarla
            $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
            $stmtGetAddr->execute([$id]);
            $addressId = $stmtGetAddr->fetchColumn();

            if ($addressId) {
                $stmtAddr = $db->prepare("
                    UPDATE address 
                    SET calle = ?, numero = ?, codigo_postal = ?, ciudad = ?, provincia = ? 
                    WHERE id = ?
                ");
                $stmtAddr->execute([$calle, $numero, $codigo_postal, $ciudad, $provincia, $addressId]);
            } else {
                // Si por alguna razón no existía dirección (caso raro), la insertamos
                $stmtNewAddr = $db->prepare("INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)");
                $stmtNewAddr->execute([$calle, $numero, $codigo_postal, $ciudad, $provincia]);
                $newAddrId = $db->lastInsertId();

                $stmtPivot = $db->prepare("INSERT INTO business_address (business_id, address_id) VALUES (?, ?)");
                $stmtPivot->execute([$id, $newAddrId]);
            }

            $db->commit();

            Session::setFlash('success', 'Comercio actualizado correctamente.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Error al actualizar: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/business/' . $id . '/edit');
            exit;
        }
    }

    public function delete($id)
    {
        // 1. Validación CSRF
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            die("Error: Token CSRF no válido.");
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            // Antes de borrar, lanza esta consulta:
            $stmt = $db->prepare("SELECT COUNT(*) FROM order_item ci 
                      JOIN product p ON ci.product_id = p.id 
                      WHERE p.business_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                // Lanza un mensaje: "No se puede eliminar el comercio porque hay clientes con productos en el carrito"
                Session::setFlash('error', 'No se puede eliminar el comercio porque hay clientes con productos en el carrito.');
            }

            // 2. BORRAR RESERVAS Y SUS ITEMS
            $db->prepare("DELETE FROM reservation WHERE business_id = ?")->execute([$id]);

            $stmtGetServ = $db->prepare("SELECT id FROM service WHERE business_id = ?");
            $stmtGetServ->execute([$id]);
            $servicios = $stmtGetServ->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($servicios)) {
                $placeholders = implode(',', array_fill(0, count($servicios), '?'));
                $db->prepare("DELETE FROM reservation_item WHERE service_id IN ($placeholders)")->execute($servicios);
            }

            // 3. BORRAR ITEMS DE PEDIDOS (Order Items)
            // Primero buscamos los productos del negocio para limpiar sus items de pedido
            $stmtGetProd = $db->prepare("SELECT id FROM product WHERE business_id = ?");
            $stmtGetProd->execute([$id]);
            $productos = $stmtGetProd->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($productos)) {
                $placeholdersProd = implode(',', array_fill(0, count($productos), '?'));
                $db->prepare("DELETE FROM order_item WHERE product_id IN ($placeholdersProd)")->execute($productos);
            }

            // 4. BORRAR SERVICIOS Y PRODUCTOS
            // Para servicios usamos el ID del comercio directamente
            $db->prepare("DELETE FROM service WHERE business_id = ?")->execute([$id]);

            // Para productos, también podemos usar el ID del comercio (más simple y seguro)
            $db->prepare("DELETE FROM product WHERE business_id = ?")->execute([$id]);

            // 5. FINALMENTE: Borrar el comercio
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

    public function apiSearch()
    {
        // 1. Limpiamos cualquier espacio en blanco o eco previo para no romper el JSON
        if (ob_get_length()) ob_clean();

        // 2. Cabecera obligatoria para que el fetch() de JS entienda que recibe JSON
        header('Content-Type: application/json; charset=utf-8');

        // 3. Capturamos el parámetro 'q' de la URL
        $query = $_GET['q'] ?? '';
        $query = trim($query);

        // Si viene vacío o es muy corto, devolvemos un array vacío rápido
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            // 1. Usamos marcadores únicos para evitar que PDO se confunda
            $sql = "SELECT id, nombre, email 
            FROM user 
            WHERE nombre LIKE :nombre OR email LIKE :email 
            LIMIT 10";

            $stmt = $db->prepare($sql);

            // Concatenamos los comodines % a la antigua usanza para asegurar compatibilidad total
            $searchTerm = '%' . $query . '%';

            $stmt->execute([
                'nombre' => $searchTerm,
                'email'  => $searchTerm
            ]);

            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode($users);
        } catch (\Exception $e) {
            // Si hay un error de base de datos, enviamos un código 500 y el error estructurado
            http_response_code(500);
            echo json_encode([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ]);
        }

        // Cortamos la ejecución para que PHP no intente renderizar ninguna vista encima
        exit;
    }
}
