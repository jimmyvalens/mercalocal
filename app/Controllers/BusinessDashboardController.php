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
     * Muestra el panel principal con estadísticas rápidas de productos, ventas y últimos pedidos.
     */
    public function index()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];
        $db = Database::getInstance()->getConnection();
        $stats = [];

        // ── 1. Productos Activos ──
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['products_active'] = (int)$stmt->fetch()['total'];

        // ── 2. Productos Inactivos ──
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 0');
        $stmt->execute([$bid]);
        $stats['products_inactive'] = (int)$stmt->fetch()['total'];

        // ── 3. Pedidos PENDIENTES reales (Lo dejamos por si tu menú superior lo usa para las notificaciones) ──
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];

        // ── 4. Ventas Reales del Mes Actual (¡Modificado: Solo pedidos COMPLETADOS!) ──
        // Cambiamos el '!= CANCELADO' por '= COMPLETADO' para reflejar el ciclo de vida cerrado
        $stmt = $db->prepare(
            "SELECT SUM(oi.precio_unitario * oi.cantidad) as total_mes
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? 
               AND p.estado = 'COMPLETADO' -- 🔥 Filtro exacto para el ciclo de venta cerrado
               AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
               AND YEAR(p.created_at) = YEAR(CURRENT_DATE())"
        );
        $stmt->execute([$bid]);
        $stats['monthly_sales'] = (float)($stmt->fetch()['total_mes'] ?? 0);


        // ── 5. Últimos 5 pedidos recibidos (Tabla del panel) ──
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

        // 🌟 Servicios y Reservas eliminados por completo del flujo

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

        // 🌟 TRADUCCIÓN COMPLETA: Mapeamos el HTML al ENUM exacto en Mayúsculas de la BD
        // Usamos match para mantener el código limpio, directo y libre de estructuras pesadas
        $estado = match (strtolower($statusForm)) {
            'pendiente'                                   => 'PENDIENTE',
            'preparando', 'preparacion', 'en_preparacion' => 'PREPARANDO',
            'listo'                                       => 'LISTO',
            'completado', 'pagado', 'entregado'           => 'COMPLETADO',
            'cancelado'                                   => 'CANCELADO',
            default                                       => ''
        };

        // 2. Calculamos las estadísticas para que la barra lateral compartida no falle
        $stats = [];

        // Productos Activos
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['products_active'] = (int)$stmt->fetch()['total'];

        // Productos Inactivos
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 0');
        $stmt->execute([$bid]);
        $stats['products_inactive'] = (int)$stmt->fetch()['total'];

        // Pedidos PENDIENTES reales
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];


        // 3. Consulta BASE (Traer los pedidos del comercio usando la tabla 'purchase')
        $sql = "SELECT p.id, p.total, p.estado, p.created_at, u.nombre as client_name, u.telefono as client_phone
                FROM purchase p
                JOIN order_item oi ON oi.purchase_id = p.id
                JOIN product pr    ON pr.id = oi.product_id
                JOIN user u        ON u.id  = p.user_id
                WHERE pr.business_id = :bid";

        // Inicializamos el array de parámetros para la consulta preparada
        $params = [':bid' => $bid];

        // 4. FILTRO DINÁMICO: Si el comerciante busca por Nombre o Teléfono
        if ($search !== '') {
            $sql .= " AND (u.nombre LIKE :search_name OR u.telefono LIKE :search_phone)";
            $params[':search_name'] = "%" . $search . "%";
            $params[':search_phone'] = "%" . $search . "%";
        }

        // 5. FILTRO DINÁMICO: Si el comerciante selecciona un estado concreto en el desplegable
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
     * Actualiza el estado de un pedido desde el panel del comercio.
     */
    public function updateStatus()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $purchaseId = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
            $nuevoEstado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

            // 🌟 CORREGIDO: Lista con tus 5 estados reales de la Base de Datos
            $estadosValidos = ['PENDIENTE', 'PREPARANDO', 'LISTO', 'COMPLETADO', 'CANCELADO'];

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
                    // Si todo es correcto, actualizamos el estado del pedido en la tabla 'purchase'
                    $updateStmt = $db->prepare("UPDATE purchase SET estado = ? WHERE id = ?");
                    $updateStmt->execute([$nuevoEstado, $purchaseId]);
                }
            }
        }

        // Redirigimos de vuelta a la pantalla de pedidos para ver los cambios reflejados
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
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

        require_once ROOT_DIR . '/resources/views/layout/business_form.php';
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

    // // ---------------------------------------------------------------------
    // // GESTIÓN DE SERVICIOS
    // // ---------------------------------------------------------------------

    // public function servicesIndex()
    // {
    //     $business = $this->requireBusinessProfile();
    //     $services = \App\Models\Service::getByBusiness($business['id']);
    //     require_once ROOT_DIR . '/resources/views/business/services/index.php';
    // }

    // public function servicesCreate()
    // {
    //     die('Estoy aquí y no debería');

    //     // 1. Cargamos el perfil del comercio
    //     $business = $this->requireBusinessProfile();

    //     // 2. Extraemos el ID de la categoría principal
    //     $comercio_categoria_id = $business['id_categoria'];

    //     // 3. Traemos SOLO las subcategorías de tipo 'servicio' para este comercio
    //     $cats = \App\Models\Category::getChildrenByParentAndType($comercio_categoria_id, 'servicio');

    //     // 4. Cargamos la vista del formulario de servicios
    //     require_once ROOT_DIR . '/resources/views/business/services/form.php';
    // }

    // public function servicesStore()
    // {
    //     $business = $this->requireBusinessProfile();

    //     $required = ['nombre', 'descripcion', 'duracion', 'precio'];
    //     foreach ($required as $field) {
    //         if (empty($_POST[$field] ?? null)) {
    //             Session::setFlash('error', 'Campo obligatorio faltante: ' . ucfirst($field));
    //             header('Location: ' . BASE_URL . '/business/dashboard/services/create');
    //             exit;
    //         }
    //     }

    //     // 🔥 SOLUCIÓN AL ERROR 1366: Forzamos null real si llega un string vacío
    //     $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

    //     // 📷 PROCESAR IMAGEN (Nuevo)
    //     $imagen_nombre = null;
    //     if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    //         $fileTmpPath = $_FILES['imagen']['tmp_name'];
    //         $fileName = $_FILES['imagen']['name'];
    //         $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    //         $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    //         if (in_array($fileExtension, $allowedExtensions)) {
    //             $uploadDir = ROOT_DIR . '/public/img/services/';
    //             // Si la carpeta no existe, la creamos
    //             if (!is_dir($uploadDir)) {
    //                 mkdir($uploadDir, 0755, true);
    //             }
    //             // Generamos un nombre único para evitar duplicados
    //             $imagen_nombre = uniqid('srv_', true) . '.' . $fileExtension;
    //             move_uploaded_file($fileTmpPath, $uploadDir . $imagen_nombre);
    //         }
    //     }

    //     $data = [
    //         'business_id' => $business['id'],
    //         'category_id' => $category_id, // Guardado seguro
    //         'nombre' => trim($_POST['nombre']),
    //         'descripcion' => trim($_POST['descripcion'] ?? ''),
    //         'duracion' => intval($_POST['duracion'] ?? 0),
    //         'precio' => floatval($_POST['precio'] ?? 0),
    //         'activo' => isset($_POST['activo']) ? 1 : 0,
    //         'imagen' => $imagen_nombre // Guardamos el nombre en la BD
    //     ];

    //     try {
    //         \App\Models\Service::create($data);
    //         Session::setFlash('success', 'Servicio creado exitosamente.');
    //         header('Location: ' . BASE_URL . '/business/dashboard/services');
    //         exit;
    //     } catch (\Throwable $e) {
    //         // Si la base de datos falla, limpiamos la imagen física que acabamos de subir
    //         if ($imagen_nombre && file_exists(ROOT_DIR . '/public/img/services/' . $imagen_nombre)) {
    //             @unlink(ROOT_DIR . '/public/img/services/' . $imagen_nombre);
    //         }
    //         Session::setFlash('error', 'Error al crear el servicio: ' . $e->getMessage());
    //         header('Location: ' . BASE_URL . '/business/dashboard/services/create');
    //         exit;
    //     }
    // }

    // public function servicesEdit($id)
    // {
    //     $business = $this->requireBusinessProfile();
    //     $service = \App\Models\Service::findById($id);

    //     if (!$service || $service->business_id != $business['id']) {
    //         Session::setFlash('error', 'Servicio no encontrado.');
    //         header('Location: ' . BASE_URL . '/business/dashboard/services');
    //         exit;
    //     }

    //     // 🌟 CAMBIO AQUÍ: Filtramos dinámicamente usando el id_categoria del comercio
    //     $cats = \App\Models\Category::getChildrenByParentAndType($business['id_categoria'], 'servicio');

    //     require_once ROOT_DIR . '/resources/views/business/services/form.php';
    // }

    // public function servicesUpdate($id)
    // {
    //     $business = $this->requireBusinessProfile();
    //     $service = \App\Models\Service::findById($id);

    //     if (!$service || $service->business_id != $business['id']) {
    //         Session::setFlash('error', 'Servicio no encontrado.');
    //         header('Location: ' . BASE_URL . '/business/dashboard/services');
    //         exit;
    //     }

    //     $required = ['nombre', 'descripcion', 'duracion', 'precio'];
    //     foreach ($required as $field) {
    //         if (empty($_POST[$field] ?? null)) {
    //             Session::setFlash('error', 'Campo obligatorio faltante: ' . ucfirst($field));
    //             header('Location: ' . BASE_URL . '/business/dashboard/services/' . $id . '/edit');
    //             exit;
    //         }
    //     }

    //     // 🔥 SOLUCIÓN AL ERROR 1366: Mismo tratamiento para la edición
    //     $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

    //     // 📷 PROCESAR NUEVA IMAGEN EN EDICIÓN (Nuevo)
    //     $imagen_nombre = $service->imagen; // Mantenemos la actual por defecto
    //     if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    //         $fileTmpPath = $_FILES['imagen']['tmp_name'];
    //         $fileName = $_FILES['imagen']['name'];
    //         $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    //         $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    //         if (in_array($fileExtension, $allowedExtensions)) {
    //             $uploadDir = ROOT_DIR . '/public/img/services/';

    //             // Si ya tenía una foto vieja en el disco, la borramos para no acumular basura
    //             if (!empty($service->imagen) && file_exists($uploadDir . $service->imagen)) {
    //                 @unlink($uploadDir . $service->imagen);
    //             }

    //             $imagen_nombre = uniqid('srv_', true) . '.' . $fileExtension;
    //             move_uploaded_file($fileTmpPath, $uploadDir . $imagen_nombre);
    //         }
    //     }

    //     $data = [
    //         'category_id' => $category_id,
    //         'nombre' => trim($_POST['nombre']),
    //         'descripcion' => trim($_POST['descripcion']),
    //         'duracion' => intval($_POST['duracion']),
    //         'precio' => floatval($_POST['precio']),
    //         'activo' => isset($_POST['activo']) ? 1 : 0,
    //         'imagen' => $imagen_nombre // Actualizamos el registro de la imagen
    //     ];

    //     try {
    //         \App\Models\Service::update($id, $data);
    //         Session::setFlash('success', 'Servicio actualizado.');
    //         header('Location: ' . BASE_URL . '/business/dashboard/services');
    //         exit;
    //     } catch (\Throwable $e) {
    //         Session::setFlash('error', 'Error al actualizar el servicio: ' . $e->getMessage());
    //         header('Location: ' . BASE_URL . '/business/dashboard/services/' . $id . '/edit');
    //         exit;
    //     }
    // }

    // public function servicesDelete($id)
    // {
    //     $business = $this->requireBusinessProfile();
    //     $service = \App\Models\Service::findById($id);

    //     if (!$service || $service->business_id != $business['id']) {
    //         Session::setFlash('error', 'Servicio no encontrado.');
    //         header('Location: ' . BASE_URL . '/business/dashboard/services');
    //         exit;
    //     }

    //     try {
    //         // 📷 LIMPIEZA DE DISCO AL ELIMINAR (Nuevo)
    //         if (!empty($service->imagen)) {
    //             $filePatch = ROOT_DIR . '/public/img/services/' . $service->imagen;
    //             if (file_exists($filePatch)) {
    //                 @unlink($filePatch);
    //             }
    //         }

    //         \App\Models\Service::delete($id);
    //         Session::setFlash('success', 'Servicio eliminado.');
    //     } catch (\Throwable $e) {
    //         Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
    //     }
    //     header('Location: ' . BASE_URL . '/business/dashboard/services');
    //     exit;
    // }

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

        // 🌟 CONTROL DE DUPLICADOS: Frenamos antes de intentar el "create"
        if (\App\Models\Schedule::exists($data['business_id'], $data['dia_semana'], $data['hora_apertura'], $data['hora_cierre'])) {
            Session::setFlash('error', 'Este horario ya está registrado para tu comercio.');
            header('Location: ' . BASE_URL . '/business/dashboard/schedules');
            exit;
        }

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

        // ── NUEVO: Validar unidad de medida recibida del formulario ──
        $unidad_medida = $_POST['unidad_medida'] ?? 'ud';
        if (!in_array($unidad_medida, ['ud', 'kg'])) {
            $unidad_medida = 'ud'; // Respaldo por seguridad
        }

        // ── NUEVO: Tratar el stock según la unidad (permitir decimales si es peso) ──
        $rawStock = $_POST['stock'] ?? '0';
        $rawStock = str_replace(',', '.', $rawStock); // Cambia comas por puntos por si teclean en formato ES
        $stockFinal = ($unidad_medida === 'kg') ? floatval($rawStock) : intval($rawStock);

        // ── Construcción del array de datos para el Modelo ──
        $data = [
            'business_id' => $business['id'],
            'category_id' => $category_id,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval(str_replace(',', '.', $_POST['precio'] ?? 0)), // Aseguramos formato decimal también en precio
            'unidad_medida' => $unidad_medida, // 🔥 NUEVO: Guardamos la unidad de medida
            'stock' => $stockFinal,             // 🔥 MODIFICADO: Ahora guarda enteros o decimales
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

        // ── NUEVO: Validar unidad de medida recibida del formulario de edición ──
        $unidad_medida = $_POST['unidad_medida'] ?? 'ud';
        if (!in_array($unidad_medida, ['ud', 'kg'])) {
            $unidad_medida = 'ud'; // Respaldo por seguridad
        }

        // ── NUEVO: Tratar el stock según la unidad (permitir decimales si es peso) ──
        $rawStock = $_POST['stock'] ?? '0';
        $rawStock = str_replace(',', '.', $rawStock); // Cambia comas por puntos por si teclean en formato ES
        $stockFinal = ($unidad_medida === 'kg') ? floatval($rawStock) : intval($rawStock);

        // ── Reconstrucción del array de datos para la actualización ──
        $data = [
            'category_id' => $category_id,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval(str_replace(',', '.', $_POST['precio'] ?? 0)), // Aseguramos formato decimal en precio
            'unidad_medida' => $unidad_medida, // 🔥 NUEVO: Actualizamos la unidad de medida
            'stock' => $stockFinal,             // 🔥 MODIFICADO: Guarda enteros o decimales según corresponda
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
            // 1. Intentamos primero el borrado físico en la Base de Datos
            \App\Models\Product::delete($id);

            // 2. SI Y SOLO SI se borró de la BD con éxito, limpiamos el disco
            if ($product->imagen) {
                $imagePath = ROOT_DIR . '/public/img/products/' . $product->imagen;
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            Session::setFlash('success', 'Producto eliminado permanentemente de la plataforma.');
        } catch (\Throwable $e) {
            // 3. Capturamos si el error es por la restricción de clave foránea (SQLSTATE 23000)
            if ($e->getCode() == 23000 || strpos($e->getMessage(), '1451') !== false) {

                // Pasamos al "Borrado Lógico": Lo dejamos marcado como inactivo (activo = 0)
                // NOTA: Adapta esta línea a cómo guardes o actualices en tu modelo Product
                // Por ejemplo, si usas una consulta directa o un método update:
                // Prueba con getConnection()
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE product SET activo = 0 WHERE id = :id");
                $stmt->execute(['id' => $id]);

                // Mantenemos la imagen a salvo en el disco para que las compras antiguas no se queden rotas
                Session::setFlash('success', 'El producto tiene pedidos asociados y no se puede destruir. Se ha desactivado y ocultado del marketplace automáticamente.');
            } else {
                // Si es cualquier otro error real de código o conexión, sí mostramos el error
                Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
            }
        }

        header('Location: ' . BASE_URL . '/business/dashboard/products');
        exit;
    }

    // ---------------------------------------------------------------------
    // CONFIGURACIÓN DE PERFIL (SETTINGS)
    // ---------------------------------------------------------------------

    public function settings()
    {
        // 1. Obtener el perfil básico del comercio
        $business = $this->requireBusinessProfile();

        $db = Database::getInstance()->getConnection();

        // 🔑 ASEGURAMOS EL ID DEL NEGOCIO
        // Si por algún motivo $business['id'] no estuviera definido, usamos el de la sesión como salvavidas
        $businessId = $business['id'] ?? Session::get('business_id');

        // 2. Buscamos el address_id en la tabla pivote (IGUAL que hace tu AdminController)
        $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
        $stmtGetAddr->execute([$businessId]);
        $addressId = $stmtGetAddr->fetchColumn();

        if ($addressId) {
            // 3. Si existe el puente, traemos los campos específicos de la tabla address
            $stmtAddr = $db->prepare("SELECT calle, numero, codigo_postal, ciudad, provincia FROM address WHERE id = ?");
            $stmtAddr->execute([$addressId]);
            $address = $stmtAddr->fetch(PDO::FETCH_ASSOC);

            if ($address) {
                // 🔑 ASIGNACIÓN MANUAL: Evitamos usar array_merge para que el 'id' de la dirección 
                // NO destruya ni pise el 'id' del negocio en la vista.
                $business['calle']         = $address['calle'];
                $business['numero']        = $address['numero'];
                $business['codigo_postal'] = $address['codigo_postal'];
                $business['ciudad']        = $address['ciudad'];
                $business['provincia']     = $address['provincia'];
            }
        } else {
            // Colchón de seguridad para comercios nuevos que entran por primera vez
            $business['calle']         = '';
            $business['numero']        = '';
            $business['codigo_postal'] = '';
            $business['ciudad']        = '';
            $business['provincia']     = '';
        }

        // 4. Cargar las categorías padre para el select unificado
        $stmtCategory = $db->query("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $categorias_padre = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

        // 5. Definir el rol del panel actual
        $rol = 'business';

        // 6. Cargar la vista compartida
        require_once ROOT_DIR . '/resources/views/layout/business_form.php';
    }

    /**
     * Actualiza la configuración y dirección del comercio desde el panel del comerciante.
     * POST /business/dashboard/settings
     */
    public function updateSettings()
    {
        // Recuperamos el perfil actual del negocio (debe contener id, logo_path y hero_path)
        $business = $this->requireBusinessProfile();
        $db = Database::getInstance()->getConnection();
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            // 1. PROCESAR Y VALIDAR TODO EL FORMULARIO DE GOLPE
            $formData = \App\Core\BusinessFormHandler::process($_POST);

            // 2. Validación específica del teléfono (9 dígitos exactos) para paridad con setup()
            if (!preg_match('/^\d{9}$/', $formData['telefono'])) {
                throw new \InvalidArgumentException('El número de teléfono debe constar exactamente de 9 dígitos numéricos.');
            }

            // 3. GESTIÓN DE ARCHIVOS UNIFICADA
            // Pasamos los paths actuales guardados en $business para conservarlos si no se sube nada nuevo
            $images = $uploader->uploadBusinessImages(
                $_FILES,
                $business['logo_path'] ?? null,
                $business['hero_path'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            // Captura errores de validación de campos de texto o del teléfono
            Session::setFlash('error', $e->getMessage());
            Session::set('setup_old', $_POST); // Almacenar para repoblar en caso de error
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        } catch (\Exception $e) {
            // Captura errores de imágenes (Formatos incorrectos, archivos corruptos, etc.)
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        }

        // ==========================================
        // 4. TRANSACCIÓN ATÓMICA EN BASE DE DATOS
        // ==========================================
        try {
            $db->beginTransaction();

            // ACCIÓN A: Actualizar datos básicos e imágenes en la tabla business
            // 🔒 SEGURIDAD: Como este es el panel del comerciante, bajo ningún concepto 
            // incluimos en el UPDATE campos críticos como 'user_id' o 'activo'.
            $sqlBus = "UPDATE business SET 
                        nombre = ?, 
                        descripcion = ?, 
                        telefono = ?, 
                        email = ?, 
                        web = ?, 
                        logo_path = ?, 
                        hero_path = ?, 
                        id_categoria = ?, 
                        updated_at = NOW() 
                    WHERE id = ?";

            $stmtBus = $db->prepare($sqlBus);
            $stmtBus->execute([
                $formData['nombre'],
                $formData['descripcion'],
                $formData['telefono'],
                $formData['email'],
                $formData['web'],
                $images['logo_path'], // Nueva ruta física o la que ya existía
                $images['hero_path'],  // Nueva ruta física o la que ya existía
                $formData['categoria_id'],
                $business['id']
            ]);

            // ACCIÓN B: Buscar si ya tiene una dirección asociada en la tabla pivote
            $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
            $stmtGetAddr->execute([$business['id']]);
            $addressId = $stmtGetAddr->fetchColumn();

            if ($addressId) {
                // Si ya existe la dirección, se hace UPDATE en la tabla address
                $sqlAddr = "UPDATE address SET 
                            calle = ?, 
                            numero = ?, 
                            codigo_postal = ?, 
                            ciudad = ?, 
                            provincia = ? 
                        WHERE id = ?";
                $stmtAddr = $db->prepare($sqlAddr);
                $stmtAddr->execute([
                    $formData['calle'],
                    $formData['numero'],
                    $formData['codigo_postal'],
                    $formData['ciudad'],
                    $formData['provincia'],
                    $addressId
                ]);
            } else {
                // Si por algún motivo raro inicial no tenía dirección, la creamos y vinculamos
                $sqlNewAddr = "INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)";
                $stmtNewAddr = $db->prepare($sqlNewAddr);
                $stmtNewAddr->execute([
                    $formData['calle'],
                    $formData['numero'],
                    $formData['codigo_postal'],
                    $formData['ciudad'],
                    $formData['provincia']
                ]);
                $newAddrId = $db->lastInsertId();

                $stmtPivot = $db->prepare("INSERT INTO business_address (business_id, address_id) VALUES (?, ?)");
                $stmtPivot->execute([$business['id'], $newAddrId]);
            }

            // Si todo es correcto, consolidamos la transacción
            $db->commit();
            Session::setFlash('success', 'Perfil y dirección actualizados correctamente.');
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::setFlash('error', 'Error al guardar los cambios en la base de datos: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
        }

        header('Location: ' . BASE_URL . '/business/dashboard');
        exit;
    }
}
