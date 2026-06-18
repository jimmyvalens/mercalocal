<?php
// =========================================================
// src/Controllers/UserController.php — Controlador del área de usuario
// Gestiona las páginas privadas del cliente registrado:
//   · Perfil personal
//   · Historial de pedidos y reservas
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Models\User;
use App\Core\Database;
use PDO;

class UserController
{
    public function __construct()
    {
        \App\Core\Middleware::requireAuth();
    }

    private function requireUserRole()
    {
        if (Session::get('user_role') !== 'USER') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    /**
     * Muestra el panel principal del usuario (GET /user/dashboard).
     */
    public function dashboard()
    {
        $this->requireUserRole();

        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $user = User::findById(Session::get('user_id'));
        if (!$user) {
            Session::destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare('SELECT id, total, estado, created_at FROM purchase WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
        $stmt->execute([$user->id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $recentActivity = [];

        if (count($purchases) > 0) {
            foreach ($purchases as $p) {
                $icon = 'fa-box';
                $icon_bg = 'bg-green-50';
                $icon_color = 'text-green-600';

                if (strtolower($p['estado']) === 'pendiente') {
                    $icon = 'fa-clock';
                    $icon_bg = 'bg-yellow-50';
                    $icon_color = 'text-yellow-600';
                }

                $recentActivity[] = [
                    'title' => 'Pedido #' . str_pad($p['id'], 4, '0', STR_PAD_LEFT) . ' (' . number_format($p['total'], 2) . ' €)',
                    'description' => 'Estado: ' . htmlspecialchars($p['estado']),
                    'time' => date('d/m/Y', strtotime($p['created_at'])),
                    'icon' => $icon,
                    'icon_bg' => $icon_bg,
                    'icon_color' => $icon_color
                ];
            }
        } else {
            $recentActivity[] = [
                'title' => 'Cuenta creada con éxito',
                'description' => 'Bienvenido a Mercalocal',
                'time' => date('d/m/Y', strtotime($user->created_at ?? date('Y-m-d'))),
                'icon' => 'fa-user-check',
                'icon_bg' => 'bg-blue-50',
                'icon_color' => 'text-blue-600'
            ];
        }

        require_once ROOT_DIR . '/resources/views/user/dashboard.php';
    }

    /**
     * Muestra el perfil del usuario autenticado (GET /profile).
     */
    public function profile()
    {
        // Redirigir al login si el usuario no está autenticado
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Cargar los datos del usuario desde la BD
        $user = User::findById(Session::get('user_id'));
        if (!$user) {
            Session::destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        require_once ROOT_DIR . '/resources/views/user/profile.php';
    }

    /**
     * Procesa la actualización del perfil (POST /user/profile/update).
     */
    public function updateProfile()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = User::findById(Session::get('user_id'));
            if (!$user) {
                Session::destroy();
                header('Location: ' . BASE_URL . '/login');
                exit;
            }

            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellidos' => trim($_POST['apellidos'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? '')
            ];

            // Handle image upload
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("Intentando subir imagen: " . $_FILES['imagen']['name'] . " (Size: " . $_FILES['imagen']['size'] . ", Error: " . $_FILES['imagen']['error'] . ")");
                if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                    error_log("Error de upload no ok: " . $_FILES['imagen']['error']);
                    Session::setFlash('error', 'Error al subir la imagen. Es posible que el archivo sea demasiado grande.');
                    header('Location: ' . BASE_URL . '/profile');
                    exit;
                }

                $uploadDir = ROOT_DIR . '/public/img/users/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = 'user_' . $user->id . '_' . time() . '.' . $fileExtension;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $newFileName)) {
                        error_log("Imagen movida correctamente a: " . $uploadDir . $newFileName);
                        $data['imagen'] = 'img/users/' . $newFileName;

                        // Delete old image if exists and is not default
                        if ($user->imagen && file_exists(ROOT_DIR . '/public/' . $user->imagen) && strpos($user->imagen, 'default') === false) {
                            unlink(ROOT_DIR . '/public/' . $user->imagen);
                        }
                    } else {
                        error_log("Fallo move_uploaded_file desde " . $_FILES['imagen']['tmp_name'] . " a " . $uploadDir . $newFileName);
                        Session::setFlash('error', 'No se pudo guardar la imagen en el servidor.');
                        header('Location: ' . BASE_URL . '/profile');
                        exit;
                    }
                } else {
                    error_log("Extensión no permitida: " . $fileExtension);
                    Session::setFlash('error', 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
                    header('Location: ' . BASE_URL . '/profile');
                    exit;
                }
            }

            $updateResult = $user->update($data);
            if (!$updateResult) {
                Session::setFlash('error', 'No se pudo actualizar el perfil. Inténtalo de nuevo.');
                header('Location: ' . BASE_URL . '/profile');
                exit;
            }

            Session::setFlash('success', 'Perfil actualizado correctamente.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
    }

    /**
     * Muestra el historial de compras y reservas del usuario (GET /orders).
     * Devuelve dos conjuntos de datos a la vista:
     *   · $orders       — pedidos de productos con sus líneas
     *   · $reservations — reservas de servicios con detalles de comercio y servicio
     */
    public function orders()
    {
        $this->requireUserRole();

        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $userId = Session::get('user_id');
        $db = Database::getInstance()->getConnection();

        // ── Obtener pedidos del usuario ordenados por fecha descendente ──
        $stmt = $db->prepare(
            'SELECT p.id, p.total, p.estado, p.created_at FROM purchase p
             WHERE p.user_id = ? ORDER BY p.created_at DESC'
        );
        $stmt->execute([$userId]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada pedido, cargar sus líneas de artículos con nombre y precio
        $stmtItems = $db->prepare(
            'SELECT oi.cantidad, oi.precio_unitario, pr.nombre
             FROM order_item oi JOIN product pr ON pr.id = oi.product_id
             WHERE oi.purchase_id = ?'
        );

        foreach ($purchases as &$p) {
            $stmtItems->execute([$p['id']]);
            $p['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($p); // Romper la referencia para evitar efectos secundarios

        // ── Obtener reservas del usuario con detalles del comercio y servicio ──
        $stmtR = $db->prepare(
            "SELECT r.id, r.fecha, r.hora_inicio, r.hora_fin, r.estado, r.created_at,
                    b.nombre as business_name, s.nombre as service_name, ri.precio
             FROM reservation r
             JOIN business b ON b.id = r.business_id
             LEFT JOIN reservation_item ri ON ri.reservation_id = r.id
             LEFT JOIN service s ON s.id = ri.service_id
             WHERE r.user_id = ? ORDER BY r.fecha DESC, r.hora_inicio DESC"
        );
        $stmtR->execute([$userId]);
        $reservations = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // $orders es un alias de $purchases para mayor claridad en la vista
        $orders = $purchases;
        require_once ROOT_DIR . '/resources/views/user/orders.php';
    }

    /**
     * API SSE para notificaciones en tiempo real
     */
    public function apiNotifications()
    {
        $this->requireUserRole();

        if (!Session::get('user_id')) {
            http_response_code(401);
            exit;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $userId = Session::get('user_id');

        while (true) {
            // Simular notificaciones (en producción, consultar BD por cambios)
            $notifications = [];

            // Ejemplo: nuevos pedidos pendientes
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM purchase WHERE user_id = ? AND estado = 'pendiente'");
            $stmt->execute([$userId]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pending['count'] > 0) {
                $notifications[] = [
                    'type' => 'pending_orders',
                    'message' => "Tienes {$pending['count']} pedido(s) pendiente(s)",
                    'count' => $pending['count']
                ];
            }

            if (!empty($notifications)) {
                echo "data: " . json_encode($notifications) . "\n\n";
                ob_flush();
                flush();
            }

            sleep(30); // Actualizar cada 30 segundos
        }
    }
}
