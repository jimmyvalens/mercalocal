<?php
// =========================================================
// app/Controllers/UserController.php — Controlador del área de usuario
// Gestiona las páginas privadas del cliente registrado, incluyendo
// el historial de pedidos, reservas y la gestión del perfil personal.
// =========================================================
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Session;
use App\Models\User;
use App\Core\Database;
use PDO;

/**
 * Class UserController
 * Hereda de BaseController para centralizar la inyección de usuario
 * y la renderización de vistas seguras.
 */
class UserController extends BaseController
{
    /**
     * Aplica el middleware de autenticación a todos los métodos del controlador.
     */
    public function __construct()
    {
        \App\Core\Middleware::requireRole('USER');
    }

    /**
     * Muestra el panel principal del usuario (Dashboard).
     * Recupera las últimas compras para mostrar una vista resumida de actividad.
     * * @return void
     */
    public function dashboard()
    {
        // Validación de sesión
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $userData = User::findById(Session::get('user_id'));

        if (!$userData) {
            Session::destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $user = is_array($userData) ? (object)$userData : $userData;
        $GLOBALS['user'] = $user;

        $db = Database::getInstance()->getConnection();

        // Consulta de compras recientes
        $stmt = $db->prepare('SELECT id, total, status, created_at FROM purchase WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
        $userId = is_object($user) ? $user->id : $user['id'];
        $stmt->execute([$userId]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $recentActivity = [];

        if (count($purchases) > 0) {
            foreach ($purchases as $p) {
                $icon = 'fa-box';
                $icon_bg = 'bg-green-50';
                $icon_color = 'text-green-600';

                $currentStatus = strtolower($p['status']);
                if ($currentStatus === 'pending' || $currentStatus === 'pendiente') {
                    $icon = 'fa-clock';
                    $icon_bg = 'bg-yellow-50';
                    $icon_color = 'text-yellow-600';
                }

                $recentActivity[] = [
                    'title' => 'Pedido #' . str_pad($p['id'], 4, '0', STR_PAD_LEFT) . ' (' . number_format($p['total'], 2) . ' €)',
                    'description' => 'Estado: ' . htmlspecialchars($p['status']),
                    'time' => date('d/m/Y', strtotime($p['created_at'])),
                    'icon' => $icon,
                    'icon_bg' => $icon_bg,
                    'icon_color' => $icon_color
                ];
            }
        } else {
            $createdAt = isset($user->created_at) ? $user->created_at : (isset($user['created_at']) ? $user['created_at'] : date('Y-m-d'));

            $recentActivity[] = [
                'title' => 'Cuenta creada con éxito',
                'description' => 'Bienvenido a Mercalocal',
                'time' => date('d/m/Y', strtotime($createdAt)),
                'icon' => 'fa-user-check',
                'icon_bg' => 'bg-blue-50',
                'icon_color' => 'text-blue-600'
            ];
        }

        // Renderizado mediante BaseController
        $this->view('user/dashboard', ['recentActivity' => $recentActivity]);
    }

    /**
     * Muestra la vista del perfil personal.
     */
    public function profile()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $user = User::findById(Session::get('user_id'));
        $this->view('user/profile', ['user' => $user]);
    }

    /**
     * Procesa la actualización de los datos del perfil (nombre, teléfono, imagen).
     */
    public function updateProfile()
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = User::findById(Session::get('user_id'));
            $data = [
                'name' => trim($_POST['nombre'] ?? ''),
                'last_name' => trim($_POST['apellidos'] ?? ''),
                'phone' => trim($_POST['telefono'] ?? ''),
                'address' => trim($_POST['direccion'] ?? '')
            ];

            // Gestión de subida de imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = ROOT_DIR . '/public/img/users/';
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $newFileName = 'user_' . $user->id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $newFileName)) {
                        $data['image_path'] = 'img/users/' . $newFileName;
                    }
                }
            }

            $user->update($data);
            Session::setFlash('success', 'Perfil actualizado.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
    }

    /**
     * Muestra el historial completo de pedidos y reservas del usuario.
     * Utiliza BaseController para inyectar datos y herramientas de formateo a la vista.
     */
    public function orders()
    {
        if (!$this->user) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // 1. Recuperar Pedidos
        $stmt = $db->prepare('SELECT p.id, p.total, p.status, p.created_at FROM purchase p WHERE p.user_id = ? ORDER BY p.created_at DESC');
        $stmt->execute([$this->user->id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Recuperar Ítems de cada pedido
        $stmtItems = $db->prepare('SELECT oi.quantity, oi.unit_price, pr.name FROM order_item oi JOIN product pr ON pr.id = oi.product_id WHERE oi.purchase_id = ?');
        foreach ($purchases as &$p) {
            $stmtItems->execute([$p['id']]);
            $p['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($p);

        // 3. Recuperar Reservas
        $stmtR = $db->prepare("SELECT r.id, r.date, r.start_time, r.end_time, r.status, r.created_at, b.name as business_name, s.name as service_name, ri.price FROM reservation r JOIN business b ON b.id = r.business_id LEFT JOIN reservation_item ri ON ri.reservation_id = r.id LEFT JOIN service s ON s.id = ri.service_id WHERE r.user_id = ? ORDER BY r.date DESC, r.start_time DESC");
        $stmtR->execute([$this->user->id]);
        $reservations = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // 4. Renderizado centralizado con herramientas auxiliares
        $this->view('user/orders', [
            'orders'          => $purchases,
            'reservations'    => $reservations,
            'translateStatus' => [$this, 'translateStatus'],
            'getStatusClass'  => [$this, 'getStatusClass']
        ]);
    }

    /**
     * API SSE (Server-Sent Events) para actualizaciones en tiempo real.
     */
    public function apiNotifications()
    {
        if (!Session::get('user_id')) {
            http_response_code(401);
            exit;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        while (true) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM purchase WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([Session::get('user_id')]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pending['count'] > 0) {
                echo "data: " . json_encode([['type' => 'pending_orders', 'message' => "Tienes {$pending['count']} pedido(s) pendiente(s)", 'count' => $pending['count']]]) . "\n\n";
                ob_flush();
                flush();
            }
            sleep(30);
        }
    }
}
