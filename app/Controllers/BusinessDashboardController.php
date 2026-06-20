<?php
// =========================================================
// src/Controllers/BusinessDashboardController.php
// Controlador del panel de control privado del comercio.
// Gestiona el flujo de estadísticas, configuración, productos,
// servicios y horarios del comercio.
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use PDO;

class BusinessDashboardController
{
    public function __construct()
    {
        // Control de acceso centralizado: si no es BUSINESS, el middleware expulsa al usuario
        \App\Core\Middleware::requireRole('BUSINESS');
    }

    /**
     * Extrae y verifica el perfil del comercio logueado.
     * Si no existe, redirige automáticamente al asistente de configuración.
     */
    private function requireBusinessProfile()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM business WHERE user_id = ? LIMIT 1');
        $stmt->execute([Session::get('user_id')]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        return $business;
    }

    /**
     * Muestra el panel principal con estadísticas rápidas, últimos pedidos y próximas reservas.
     */
    public function index()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];
        $db = Database::getInstance()->getConnection();
        $stats = [];

        // ── Estadísticas rápidas del panel ──
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['products'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM service WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['services'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservation WHERE business_id = ? AND estado != 'CANCELADA'");
        $stmt->execute([$bid]);
        $stats['reservations'] = (int)$stmt->fetch()['total'];

        // 🌟 NUEVO 1: Contar los pedidos PENDIENTES reales de este comercio (adiós al '3' de la barra)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];

        // 🌟 NUEVO 2: Calcular las Ventas Reales del Mes Actual para este comercio
        $stmt = $db->prepare(
            "SELECT SUM(oi.precio_unitario * oi.cantidad) as total_mes
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? 
               AND p.estado = 'PAGADO' 
               AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
               AND YEAR(p.created_at) = YEAR(CURRENT_DATE())"
        );
        $stmt->execute([$bid]);
        $stats['monthly_sales'] = (float)($stmt->fetch()['total_mes'] ?? 0);


        // ── Últimos 5 pedidos recibidos ──
        $stmt = $db->prepare(
            "SELECT p.id, p.total, p.estado, p.created_at, u.nombre as client_name
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             JOIN user u        ON u.id  = p.user_id
             WHERE pr.business_id = ?
             GROUP BY p.id ORDER BY p.created_at DESC LIMIT 5"
        );
        $stmt->execute([$bid]);
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Próximas 10 reservas pendientes ──
        $stmt = $db->prepare(
            "SELECT r.*, u.nombre as client_name, u.telefono as client_phone, s.nombre as service_name
             FROM reservation r
             JOIN user u ON u.id = r.user_id
             LEFT JOIN reservation_item ri ON ri.reservation_id = r.id
             LEFT JOIN service s ON s.id = ri.service_id
             WHERE r.business_id = ? AND r.fecha >= CURDATE() AND r.estado != 'CANCELADA'
             ORDER BY r.fecha, r.hora_inicio LIMIT 10"
        );
        $stmt->execute([$bid]);
        $upcomingReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/business/dashboard.php';
    }

    /**
     * Muestra el listado de pedidos del comercio aplicando los filtros del formulario.
     */
    public function orders()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];
        $db = Database::getInstance()->getConnection();

        // 1. Recogemos los filtros que vienen del formulario
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusForm = isset($_GET['status']) ? trim($_GET['status']) : '';

        // 🌟 TRADUCCIÓN COMPLETA: Mapeamos el HTML al ENUM exacto de la Base de Datos
        $estado = '';
        switch (strtolower($statusForm)) {
            case 'pendiente':
                $estado = 'PENDIENTE';
                break;
            case 'pagado':
            case 'completado': // Por si en el HTML se llama 'completado'
                $estado = 'PAGADO';
                break;
            case 'preparacion':
            case 'en-preparacion':
            case 'en_preparacion':
                $estado = 'EN PREPARACION';
                break;
            case 'enviado':
                $estado = 'ENVIADO';
                break;
            case 'entregado':
                $estado = 'ENTREGADO';
                break;
            case 'cancelado':
                $estado = 'CANCELADO';
                break;
        }

        // 2. Calculamos las estadísticas para que la barra lateral no falle
        $stats = [];
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];


        // 3. Consulta BASE (Traer los pedidos del comercio)
        $sql = "SELECT p.id, p.total, p.estado, p.created_at, u.nombre as client_name, u.telefono as client_phone
                FROM purchase p
                JOIN order_item oi ON oi.purchase_id = p.id
                JOIN product pr    ON pr.id = oi.product_id
                JOIN user u        ON u.id  = p.user_id
                WHERE pr.business_id = :bid";

        // Inicializamos el array de parámetros para la consulta preparada
        $params = [':bid' => $bid];

        // 4. FILTRO DINÁMICO: Si el usuario busca por Nombre o Teléfono
        if ($search !== '') {
            $sql .= " AND (u.nombre LIKE :search_name OR u.telefono LIKE :search_phone)";
            $params[':search_name'] = "%" . $search . "%";
            $params[':search_phone'] = "%" . $search . "%";
        }

        // 5. FILTRO DINÁMICO: Si el usuario selecciona un estado concreto
        if ($estado !== '') {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $estado;
        }

        // 6. Agrupamos y ordenamos (los más recientes primero)
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

        // 7. Ejecutamos la consulta final con todos sus filtros pasados de forma segura
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cargamos la vista de pedidos pasando $orders, $stats, $search y $estado
        require_once ROOT_DIR . '/resources/views/business/orders.php';
    }

    /**
     * Asistente de configuración inicial (sólo si no tiene perfil creado aún).
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

    /**
     * Procesa y guarda el perfil inicial enviado por POST.
     */
    public function saveSetup()
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $web = trim($_POST['web'] ?? '') ?: null;

        if (empty($nombre) || empty($descripcion) || empty($telefono) || empty($email)) {
            Session::setFlash('error', 'Por favor rellena todos los campos obligatorios.');
            header('Location: ' . BASE_URL . '/business/setup');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO business (user_id, nombre, descripcion, telefono, email, web, activo)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([Session::get('user_id'), $nombre, $descripcion, $telefono, $email, $web]);

        Session::setFlash('success', '¡Tu comercio "' . $nombre . '" está listo en Mercalocal!');
        header('Location: ' . BASE_URL . '/business/dashboard');
        exit;
    }

    // ---------------------------------------------------------------------
    // GESTIÓN DE SERVICIOS
    // ---------------------------------------------------------------------

    public function servicesIndex()
    {
        $business = $this->requireBusinessProfile();
        $services = \App\Models\Service::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/services/index.php';
    }

    public function servicesCreate()
    {
        // 1. Cargamos el perfil del comercio
        $business = $this->requireBusinessProfile();

        // 2. Extraemos el ID de la categoría principal
        $comercio_categoria_id = $business['id_categoria'];

        // 3. Traemos SOLO las subcategorías de tipo 'servicio' para este comercio
        $cats = \App\Models\Category::getChildrenByParentAndType($comercio_categoria_id, 'servicio');

        // 4. Cargamos la vista del formulario de servicios
        require_once ROOT_DIR . '/resources/views/business/services/form.php';
    }

    public function servicesStore()
    {
        $business = $this->requireBusinessProfile();

        $required = ['nombre', 'descripcion', 'duracion', 'precio'];
        foreach ($required as $field) {
            if (empty($_POST[$field] ?? null)) {
                Session::setFlash('error', 'Campo obligatorio faltante: ' . ucfirst($field));
                header('Location: ' . BASE_URL . '/business/dashboard/services/create');
                exit;
            }
        }

        // 🔥 SOLUCIÓN AL ERROR 1366: Forzamos null real si llega un string vacío
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

        // 📷 PROCESAR IMAGEN (Nuevo)
        $imagen_nombre = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen']['tmp_name'];
            $fileName = $_FILES['imagen']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = ROOT_DIR . '/public/img/services/';
                // Si la carpeta no existe, la creamos
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                // Generamos un nombre único para evitar duplicados
                $imagen_nombre = uniqid('srv_', true) . '.' . $fileExtension;
                move_uploaded_file($fileTmpPath, $uploadDir . $imagen_nombre);
            }
        }

        $data = [
            'business_id' => $business['id'],
            'category_id' => $category_id, // Guardado seguro
            'nombre' => trim($_POST['nombre']),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'duracion' => intval($_POST['duracion'] ?? 0),
            'precio' => floatval($_POST['precio'] ?? 0),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'imagen' => $imagen_nombre // Guardamos el nombre en la BD
        ];

        try {
            \App\Models\Service::create($data);
            Session::setFlash('success', 'Servicio creado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        } catch (\Throwable $e) {
            // Si la base de datos falla, limpiamos la imagen física que acabamos de subir
            if ($imagen_nombre && file_exists(ROOT_DIR . '/public/img/services/' . $imagen_nombre)) {
                @unlink(ROOT_DIR . '/public/img/services/' . $imagen_nombre);
            }
            Session::setFlash('error', 'Error al crear el servicio: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/services/create');
            exit;
        }
    }

    public function servicesEdit($id)
    {
        $business = $this->requireBusinessProfile();
        $service = \App\Models\Service::findById($id);

        if (!$service || $service->business_id != $business['id']) {
            Session::setFlash('error', 'Servicio no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }

        // 🌟 CAMBIO AQUÍ: Filtramos dinámicamente usando el id_categoria del comercio
        $cats = \App\Models\Category::getChildrenByParentAndType($business['id_categoria'], 'servicio');

        require_once ROOT_DIR . '/resources/views/business/services/form.php';
    }

    public function servicesUpdate($id)
    {
        $business = $this->requireBusinessProfile();
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

        // 🔥 SOLUCIÓN AL ERROR 1366: Mismo tratamiento para la edición
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

        // 📷 PROCESAR NUEVA IMAGEN EN EDICIÓN (Nuevo)
        $imagen_nombre = $service->imagen; // Mantenemos la actual por defecto
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen']['tmp_name'];
            $fileName = $_FILES['imagen']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = ROOT_DIR . '/public/img/services/';

                // Si ya tenía una foto vieja en el disco, la borramos para no acumular basura
                if (!empty($service->imagen) && file_exists($uploadDir . $service->imagen)) {
                    @unlink($uploadDir . $service->imagen);
                }

                $imagen_nombre = uniqid('srv_', true) . '.' . $fileExtension;
                move_uploaded_file($fileTmpPath, $uploadDir . $imagen_nombre);
            }
        }

        $data = [
            'category_id' => $category_id,
            'nombre' => trim($_POST['nombre']),
            'descripcion' => trim($_POST['descripcion']),
            'duracion' => intval($_POST['duracion']),
            'precio' => floatval($_POST['precio']),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'imagen' => $imagen_nombre // Actualizamos el registro de la imagen
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

    public function servicesDelete($id)
    {
        $business = $this->requireBusinessProfile();
        $service = \App\Models\Service::findById($id);

        if (!$service || $service->business_id != $business['id']) {
            Session::setFlash('error', 'Servicio no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/services');
            exit;
        }

        try {
            // 📷 LIMPIEZA DE DISCO AL ELIMINAR (Nuevo)
            if (!empty($service->imagen)) {
                $filePatch = ROOT_DIR . '/public/img/services/' . $service->imagen;
                if (file_exists($filePatch)) {
                    @unlink($filePatch);
                }
            }

            \App\Models\Service::delete($id);
            Session::setFlash('success', 'Servicio eliminado.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/services');
        exit;
    }

    // ---------------------------------------------------------------------
    // GESTIÓN DE HORARIOS
    // ---------------------------------------------------------------------

    public function schedulesIndex()
    {
        $business = $this->requireBusinessProfile();
        $schedules = \App\Models\Schedule::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/schedules/index.php';
    }

    public function schedulesStore()
    {
        $business = $this->requireBusinessProfile();

        $data = [
            'business_id' => $business['id'],
            'dia_semana' => intval($_POST['dia_semana'] ?? 0),
            'hora_apertura' => $_POST['hora_apertura'] ?? '09:00',
            'hora_cierre' => $_POST['hora_cierre'] ?? '18:00',
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

    public function schedulesDelete($id)
    {
        $business = $this->requireBusinessProfile();

        try {
            \App\Models\Schedule::delete($id);
            Session::setFlash('success', 'Horario eliminado.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Error al eliminar horario: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/business/dashboard/schedules');
        exit;
    }

    // ---------------------------------------------------------------------
    // GESTIÓN DE PRODUCTOS
    // ---------------------------------------------------------------------

    public function productsIndex()
    {
        $business = $this->requireBusinessProfile();
        $products = \App\Models\Product::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/products/index.php';
    }

    public function productsCreate()
    {
        // 1. Cargamos el perfil del comercio (que ahora ya incluye 'id_categoria')
        $business = $this->requireBusinessProfile();

        // 2. Extraemos el ID de la categoría usando la clave correcta en español
        $comercio_categoria_id = $business['id_categoria'];

        // 3. Llamamos al modelo para traer SOLO los productos hijos de esta categoría (Alimentación)
        $cats = \App\Models\Category::getChildrenByParentAndType($comercio_categoria_id, 'producto');

        // 4. Cargamos la vista del formulario
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    public function productsStore()
    {
        $business = $this->requireBusinessProfile();
        $imagenNombre = null;

        // ── Procesar carga de imagen ──
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];

            // Validar tipo de archivo real (MIME bytes)
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

            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };

            $imagenNombre = 'producto_' . $business['id'] . '_' . time() . '.' . $ext;
            $uploadDir = ROOT_DIR . '/public/img/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $imagenNombre;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    \App\Core\ImageHelper::compress($uploadPath, $uploadPath, 80, 1200, 1200);
                } catch (\Throwable $e) {
                    // Si falla la compresión se continúa con la original
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }
        }

        // 🔥 SOLUCIÓN AL ERROR 1366: Forzamos null si llega vacío
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

        $data = [
            'business_id' => $business['id'],
            'category_id' => $category_id, // Guardado seguro
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval($_POST['precio'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'imagen' => $imagenNombre,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];

        try {
            \App\Models\Product::create($data);
            Session::setFlash('success', 'Producto creado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        } catch (\Throwable $e) {
            // Si la BD falla, limpiamos el archivo físico subido
            if ($imagenNombre && file_exists(ROOT_DIR . '/public/img/products/' . $imagenNombre)) {
                @unlink(ROOT_DIR . '/public/img/products/' . $imagenNombre);
            }
            Session::setFlash('error', 'Error al crear el producto: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/products/create');
            exit;
        }
    }

    public function productsEdit($id)
    {
        $business = $this->requireBusinessProfile();

        // Tu código actual para buscar el producto (puede ser findById o similar)
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            \App\Core\Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        // 🌟 REEMPLAZA TU CONSULTA ANTIGUA ($db->query...) POR ESTA LÍNEA:
        $cats = \App\Models\Category::getChildrenByParentAndType($business['id_categoria'], 'producto');

        // Cargamos la vista que me acabas de enseñar
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    public function productsUpdate($id)
    {
        $business = $this->requireBusinessProfile();
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        $imagenNombre = $product->imagen;

        // ── Procesar nueva imagen si se sube ──
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes)) {
                Session::setFlash('error', 'Solo se permiten imágenes (JPG, PNG, GIF, WebP).');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                Session::setFlash('error', 'La imagen no debe exceder 5MB.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }

            // Eliminar imagen física anterior para optimizar espacio
            if ($product->imagen) {
                $oldPath = ROOT_DIR . '/public/img/products/' . $product->imagen;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            $imagenNombre = 'producto_' . $business['id'] . '_' . time() . '.' . $ext;
            $uploadDir = ROOT_DIR . '/public/img/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $imagenNombre;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    \App\Core\ImageHelper::compress($uploadPath, $uploadPath, 80, 1200, 1200);
                } catch (\Throwable $e) {
                    // Continuar si falla compresión
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }
        }

        // 🔥 SOLUCIÓN AL ERROR 1366: Mismo tratamiento en la edición
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

        $data = [
            'category_id' => $category_id, // Guardado seguro
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval($_POST['precio'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'imagen' => $imagenNombre,
            'activo' => isset($_POST['activo']) ? 1 : 0,
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
        $business = $this->requireBusinessProfile();
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        try {
            if ($product->imagen) {
                $imagePath = ROOT_DIR . '/public/img/products/' . $product->imagen;
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
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

    /**
     * Actualiza el estado de un pedido desde el panel del comercio.
     */
    public function updateStatus()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $purchaseId = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
            $nuevoEstado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

            // Lista de estados permitidos (seguridad para tu ENUM)
            $estadosValidos = ['PENDIENTE', 'PAGADO', 'EN PREPARACION', 'ENVIADO', 'ENTREGADO', 'CANCELADO'];

            if ($purchaseId > 0 && in_array($nuevoEstado, $estadosValidos)) {
                $db = Database::getInstance()->getConnection();

                // SEGURIDAD: Verificamos que el pedido contiene al menos un producto de este comercio
                $checkStmt = $db->prepare(
                    "SELECT COUNT(*) 
                     FROM order_item oi
                     JOIN product pr ON pr.id = oi.product_id
                     WHERE oi.purchase_id = ? AND pr.business_id = ?"
                );
                $checkStmt->execute([$purchaseId, $bid]);

                if ((int)$checkStmt->fetchColumn() > 0) {
                    // Si todo es correcto, actualizamos el estado del pedido
                    $updateStmt = $db->prepare("UPDATE purchase SET estado = ? WHERE id = ?");
                    $updateStmt->execute([$nuevoEstado, $purchaseId]);
                }
            }
        }

        // Redirigimos de vuelta a la pantalla de pedidos para ver los cambios reflejados
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // ---------------------------------------------------------------------
    // CONFIGURACIÓN DE PERFIL (SETTINGS)
    // ---------------------------------------------------------------------

    public function settings()
    {
        $business = $this->requireBusinessProfile();
        require_once ROOT_DIR . '/resources/views/business/settings.php';
    }

    public function updateSettings()
    {
        $business = $this->requireBusinessProfile();
        $db = Database::getInstance()->getConnection();

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'web' => trim($_POST['web'] ?? ''),
        ];

        if (empty($data['nombre']) || empty($data['descripcion']) || empty($data['telefono']) || empty($data['email'])) {
            Session::setFlash('error', 'Los campos nombre, descripción, teléfono y email son obligatorios.');
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        }

        try {
            $sql = "UPDATE business SET nombre = ?, descripcion = ?, telefono = ?, email = ?, web = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['telefono'],
                $data['email'],
                $data['web'],
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
