<?php
// =========================================================
// src/Controllers/AdminController.php — Panel de administración
// Accesible únicamente para usuarios con rol ADMIN.
// Muestra estadísticas globales de la plataforma:
//   · Número total de usuarios registrados
//   · Número total de comercios dados de alta
//   · Volumen de ventas completadas
//   · Listado detallado de comercios con productos y servicios
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
     * Requiere rol ADMIN; cualquier otro usuario es rechazado.
     */
    public function index()
    {
        $stats = \App\Models\Stat::getAdminStats();

        require_once ROOT_DIR . '/resources/views/admin/dashboard.php';
    }

    /**
     * Listado completo de comercios (GET /admin/businesses)
     */
    public function businesses()
    {
        $db = Database::getInstance()->getConnection();

        // Obtener todos los comercios con información del propietario
        $stmt = $db->prepare(
            "SELECT b.*, u.nombre as owner_name, u.email as owner_email,
                    COUNT(DISTINCT p.id) as product_count,
                    COUNT(DISTINCT s.id) as service_count
             FROM business b
             JOIN user u ON b.user_id = u.id
             LEFT JOIN product p ON p.business_id = b.id AND p.activo = 1
             LEFT JOIN service s ON s.business_id = b.id AND s.activo = 1
             GROUP BY b.id
             ORDER BY b.created_at DESC"
        );
        $stmt->execute();
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once ROOT_DIR . '/resources/views/admin/businesses.php';
    }

    /**
     * Detalle de un comercio específico (GET /admin/business/{id})
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

        // Estadísticas del comercio
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT p.id) as total_sales,
                    SUM(p.total) as total_revenue
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
            // Vista sin datos previos
            require_once ROOT_DIR . '/resources/views/admin/business_form.php';
        }

        /**
         * Guarda un nuevo comercio en la base de datos.
         * POST /admin/business/store
         */
        public function store()
        {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $web = trim($_POST['web'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;
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
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO business (nombre, descripcion, telefono, email, web, activo, logo_path, hero_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $descripcion, $telefono, $email, $web, $activo, $logoPath, $heroPath]);
            Session::setFlash('success', 'Comercio creado correctamente.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        /**
         * Muestra el formulario para editar un comercio existente.
         * GET /admin/business/{id}/edit
         */
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
            // La vista reutiliza el mismo formulario, pasándole $business
            require_once ROOT_DIR . '/resources/views/admin/business_form.php';
        }

        /**
         * Actualiza la información de un comercio existente.
         * POST /admin/business/{id}/update
         */
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
            $stmt = $db->prepare('UPDATE business SET nombre = ?, descripcion = ?, telefono = ?, email = ?, web = ?, activo = ?, logo_path = ?, hero_path = ? WHERE id = ?');
            $stmt->execute([$nombre, $descripcion, $telefono, $email, $web, $activo, $logoPath, $heroPath, $id]);
            Session::setFlash('success', 'Comercio actualizado correctamente.');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }

        /**
         * Elimina (soft‑delete) un comercio; requiere confirmación en UI.
         * POST /admin/business/{id}/delete
         */
        public function delete($id)
        {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('UPDATE business SET activo = 0 WHERE id = ?');
            $stmt->execute([$id]);
            Session::setFlash('success', 'Comercio eliminado (inactivo).');
            header('Location: ' . BASE_URL . '/admin/businesses');
            exit;
        }
    }
