<?php

/**
 * =========================================================
 * app/Controllers/BusinessDashboardController.php — Controlador del panel de negocio
 *
 * Controla estadísticas, pedidos, productos, horarios y configuración del comercio.
 * · Requiere perfil de negocio autenticado
 * · Gestiona operaciones CRUD sobre productos y horarios
 * · Actualiza estado de pedidos y datos de negocio
 * =========================================================
 */

namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use PDO;

class BusinessDashboardController
{
    /**
     * Constructor del controlador.
     *
     * @return void
     */
    public function __construct()
    {
        \App\Core\Middleware::requireRole('BUSINESS');
    }

    /**
     * Extrae y verifica el perfil del comercio logueado.
     * Si no existe, redirige al asistente de configuración.
     *
     * @return array
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
     *
     * @return void
     */
    public function index()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];
        $db = Database::getInstance()->getConnection();
        $stats = [];

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['products_active'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 0');
        $stmt->execute([$bid]);
        $stats['products_inactive'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare(
            "SELECT SUM(oi.precio_unitario * oi.cantidad) as total_mes
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? 
               AND p.estado = 'COMPLETADO' 
               AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
               AND YEAR(p.created_at) = YEAR(CURRENT_DATE())"
        );
        $stmt->execute([$bid]);
        $stats['monthly_sales'] = (float)($stmt->fetch()['total_mes'] ?? 0);

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

        require_once ROOT_DIR . '/resources/views/business/dashboard.php';
    }

    /**
     * Muestra el listado de pedidos del comercio aplicando los filtros del formulario.
     *
     * @return void
     */
    public function orders()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];
        $db = Database::getInstance()->getConnection();

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusForm = isset($_GET['status']) ? trim($_GET['status']) : '';

        $estado = match (strtolower($statusForm)) {
            'pendiente'                                   => 'PENDIENTE',
            'preparando', 'preparacion', 'en_preparacion' => 'PREPARANDO',
            'listo'                                       => 'LISTO',
            'completado', 'pagado', 'entregado'           => 'COMPLETADO',
            'cancelado'                                   => 'CANCELADO',
            default                                       => ''
        };

        $stats = [];

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 1');
        $stmt->execute([$bid]);
        $stats['products_active'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM product WHERE business_id = ? AND activo = 0');
        $stmt->execute([$bid]);
        $stats['products_inactive'] = (int)$stmt->fetch()['total'];

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total 
             FROM purchase p
             JOIN order_item oi ON oi.purchase_id = p.id
             JOIN product pr    ON pr.id = oi.product_id
             WHERE pr.business_id = ? AND p.estado = 'PENDIENTE'"
        );
        $stmt->execute([$bid]);
        $stats['pending_orders'] = (int)$stmt->fetch()['total'];

        $sql = "SELECT p.id, p.total, p.estado, p.created_at, p.delivery_method, 
                       u.nombre as client_name, u.telefono as client_phone,
                       a.calle, a.numero, a.codigo_postal, a.ciudad, a.provincia -- 🌟 Campos de dirección
                FROM purchase p
                JOIN order_item oi ON oi.purchase_id = p.id
                JOIN product pr    ON pr.id = oi.product_id
                JOIN user u        ON u.id  = p.user_id
                LEFT JOIN address a ON a.id = p.address_id -- 🌟 Vinculamos la dirección real de esta compra
                WHERE pr.business_id = :bid";

        $params = [':bid' => $bid];

        if ($search !== '') {
            $sql .= " AND (u.nombre LIKE :search_name OR u.telefono LIKE :search_phone)";
            $params[':search_name'] = "%" . $search . "%";
            $params[':search_phone'] = "%" . $search . "%";
        }

        if ($estado !== '') {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtItems = $db->prepare(
            "SELECT oi.cantidad, oi.precio_unitario, pr.nombre as producto_nombre
             FROM order_item oi
             JOIN product pr ON pr.id = oi.product_id
             WHERE oi.purchase_id = ? AND pr.business_id = ?"
        );

        foreach ($orders as &$o) {
            $stmtItems->execute([$o['id'], $bid]);
            $o['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }

        unset($o);

        require_once ROOT_DIR . '/resources/views/business/orders.php';
    }

    /**
     * Actualiza el estado de un pedido desde el panel del comercio.
     *
     * @return void
     */
    public function updateStatus()
    {
        $business = $this->requireBusinessProfile();
        $bid = $business['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $purchaseId = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
            $nuevoEstado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

            $estadosValidos = ['PENDIENTE', 'PREPARANDO', 'LISTO', 'COMPLETADO', 'CANCELADO'];

            if ($purchaseId > 0 && in_array($nuevoEstado, $estadosValidos)) {
                $db = Database::getInstance()->getConnection();

                $checkStmt = $db->prepare(
                    "SELECT COUNT(*) 
                     FROM order_item oi
                     JOIN product pr ON pr.id = oi.product_id
                     WHERE oi.purchase_id = ? AND pr.business_id = ?"
                );
                $checkStmt->execute([$purchaseId, $bid]);

                if ((int)$checkStmt->fetchColumn() > 0) {
                    $updateStmt = $db->prepare("UPDATE purchase SET estado = ? WHERE id = ?");
                    $updateStmt->execute([$nuevoEstado, $purchaseId]);
                }
            }
        }

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * Asistente de configuración inicial.
     *
     * @return void
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
     *
     * @return void
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

    /**
     * Muestra los horarios del comercio.
     *
     * @return void
     */
    public function schedulesIndex()
    {
        $business = $this->requireBusinessProfile();
        $schedules = \App\Models\Schedule::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/schedules/index.php';
    }

    /**
     * Guarda un nuevo horario del comercio.
     *
     * @return void
     */
    public function schedulesStore()
    {
        $business = $this->requireBusinessProfile();

        $data = [
            'business_id' => $business['id'],
            'dia_semana' => intval($_POST['dia_semana'] ?? 0),
            'hora_apertura' => $_POST['hora_apertura'] ?? '09:00',
            'hora_cierre' => $_POST['hora_cierre'] ?? '18:00',
        ];

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

    /**
     * Elimina un horario existente.
     *
     * @param int $id
     * @return void
     */
    public function schedulesDelete(int $id)
    {
        $this->requireBusinessProfile();

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
     * Muestra el listado de productos del comercio.
     *
     * @return void
     */
    public function productsIndex()
    {
        $business = $this->requireBusinessProfile();
        $products = \App\Models\Product::getByBusiness($business['id']);
        require_once ROOT_DIR . '/resources/views/business/products/index.php';
    }

    /**
     * Muestra el formulario para crear un producto.
     *
     * @return void
     */
    public function productsCreate()
    {
        $business = $this->requireBusinessProfile();
        $comercio_categoria_id = $business['id_categoria'];
        $cats = \App\Models\Category::getChildrenByParentAndType($comercio_categoria_id, 'producto');
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    /**
     * Guarda un nuevo producto enviado por POST.
     *
     * @return void
     */
    public function productsStore()
    {
        $business = $this->requireBusinessProfile();
        $imagenNombre = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowedMimes)) {
                Session::setFlash('error', 'Solo se permiten imágenes (JPG, PNG, GIF, WebP).');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }

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
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/create');
                exit;
            }
        }

        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
        $unidad_medida = $_POST['unidad_medida'] ?? 'ud';
        if (!in_array($unidad_medida, ['ud', 'kg'])) {
            $unidad_medida = 'ud';
        }

        $rawStock = $_POST['stock'] ?? '0';
        $rawStock = str_replace(',', '.', $rawStock);
        $stockFinal = ($unidad_medida === 'kg') ? floatval($rawStock) : intval($rawStock);

        $data = [
            'business_id' => $business['id'],
            'category_id' => $category_id,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval(str_replace(',', '.', $_POST['precio'] ?? 0)),
            'unidad_medida' => $unidad_medida,
            'stock' => $stockFinal,
            'imagen' => $imagenNombre,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];

        try {
            \App\Models\Product::create($data);
            Session::setFlash('success', 'Producto creado exitosamente.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        } catch (\Throwable $e) {
            if ($imagenNombre && file_exists(ROOT_DIR . '/public/img/products/' . $imagenNombre)) {
                @unlink(ROOT_DIR . '/public/img/products/' . $imagenNombre);
            }
            Session::setFlash('error', 'Error al crear el producto: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/business/dashboard/products/create');
            exit;
        }
    }

    /**
     * Muestra el formulario de edición de producto.
     *
     * @param int $id
     * @return void
     */
    public function productsEdit(int $id)
    {
        $business = $this->requireBusinessProfile();
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            \App\Core\Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        $cats = \App\Models\Category::getChildrenByParentAndType($business['id_categoria'], 'producto');
        require_once ROOT_DIR . '/resources/views/business/products/form.php';
    }

    /**
     * Actualiza un producto existente.
     *
     * @param int $id
     * @return void
     */
    public function productsUpdate(int $id)
    {
        $business = $this->requireBusinessProfile();
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        $imagenNombre = $product->imagen;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

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
                }
            } else {
                Session::setFlash('error', 'Error al subir la imagen.');
                header('Location: ' . BASE_URL . '/business/dashboard/products/' . $id . '/edit');
                exit;
            }
        }

        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
        $unidad_medida = $_POST['unidad_medida'] ?? 'ud';
        if (!in_array($unidad_medida, ['ud', 'kg'])) {
            $unidad_medida = 'ud';
        }

        $rawStock = $_POST['stock'] ?? '0';
        $rawStock = str_replace(',', '.', $rawStock);
        $stockFinal = ($unidad_medida === 'kg') ? floatval($rawStock) : intval($rawStock);

        $data = [
            'category_id' => $category_id,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval(str_replace(',', '.', $_POST['precio'] ?? 0)),
            'unidad_medida' => $unidad_medida,
            'stock' => $stockFinal,
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

    /**
     * Elimina un producto o lo desactiva si tiene relaciones.
     *
     * @param int $id
     * @return void
     */
    public function productsDelete(int $id)
    {
        $business = $this->requireBusinessProfile();
        $product = \App\Models\Product::findById($id);

        if (!$product || $product->business_id != $business['id']) {
            Session::setFlash('error', 'Producto no encontrado.');
            header('Location: ' . BASE_URL . '/business/dashboard/products');
            exit;
        }

        try {
            \App\Models\Product::delete($id);

            if ($product->imagen) {
                $imagePath = ROOT_DIR . '/public/img/products/' . $product->imagen;
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            Session::setFlash('success', 'Producto eliminado permanentemente de la plataforma.');
        } catch (\Throwable $e) {
            if ($e->getCode() == 23000 || strpos($e->getMessage(), '1451') !== false) {
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE product SET activo = 0 WHERE id = :id");
                $stmt->execute(['id' => $id]);

                Session::setFlash('success', 'El producto tiene pedidos asociados y no se puede destruir. Se ha desactivado y ocultado del marketplace automáticamente.');
            } else {
                Session::setFlash('error', 'Error al eliminar: ' . $e->getMessage());
            }
        }

        header('Location: ' . BASE_URL . '/business/dashboard/products');
        exit;
    }

    /**
     * Muestra el formulario de configuración del perfil.
     *
     * @return void
     */
    public function settings()
    {
        $business = $this->requireBusinessProfile();

        $db = Database::getInstance()->getConnection();
        $businessId = $business['id'] ?? Session::get('business_id');

        $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
        $stmtGetAddr->execute([$businessId]);
        $addressId = $stmtGetAddr->fetchColumn();

        if ($addressId) {
            $stmtAddr = $db->prepare("SELECT calle, numero, codigo_postal, ciudad, provincia FROM address WHERE id = ?");
            $stmtAddr->execute([$addressId]);
            $address = $stmtAddr->fetch(PDO::FETCH_ASSOC);

            if ($address) {
                $business['calle']         = $address['calle'];
                $business['numero']        = $address['numero'];
                $business['codigo_postal'] = $address['codigo_postal'];
                $business['ciudad']        = $address['ciudad'];
                $business['provincia']     = $address['provincia'];
            }
        } else {
            $business['calle']         = '';
            $business['numero']        = '';
            $business['codigo_postal'] = '';
            $business['ciudad']        = '';
            $business['provincia']     = '';
        }

        $stmtCategory = $db->query("SELECT id, nombre FROM category WHERE parent_id IS NULL ORDER BY nombre ASC");
        $categorias_padre = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

        $rol = 'business';

        require_once ROOT_DIR . '/resources/views/layout/business_form.php';
    }

    /**
     * Actualiza la configuración y dirección del comercio.
     *
     * @return void
     */
    public function updateSettings()
    {
        $business = $this->requireBusinessProfile();
        $db = Database::getInstance()->getConnection();
        $uploader = new \App\Core\FileUploader(ROOT_DIR . '/public/uploads/businesses');

        try {
            $formData = \App\Core\BusinessFormHandler::process($_POST);

            if (!preg_match('/^\d{9}$/', $formData['telefono'])) {
                throw new \InvalidArgumentException('El número de teléfono debe constar exactamente de 9 dígitos numéricos.');
            }

            $images = $uploader->uploadBusinessImages(
                $_FILES,
                $business['logo_path'] ?? null,
                $business['hero_path'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error multimedia: ' . $e->getMessage());
            Session::set('setup_old', $_POST);
            header('Location: ' . BASE_URL . '/business/dashboard/settings');
            exit;
        }

        try {
            $db->beginTransaction();

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
                $images['logo_path'],
                $images['hero_path'],
                $formData['categoria_id'],
                $business['id']
            ]);

            $stmtGetAddr = $db->prepare("SELECT address_id FROM business_address WHERE business_id = ?");
            $stmtGetAddr->execute([$business['id']]);
            $addressId = $stmtGetAddr->fetchColumn();

            if ($addressId) {
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
