<?php
// =========================================================
// app/Controllers/ReservationController.php — Controlador de reservas
// Gestiona el sistema de citas/reservas de servicios:
//   · Mostrar el formulario de reserva de un comercio
//   · Guardar la cita validando disponibilidad horaria
//   · Enviar notificaciones por email al cliente y al comercio
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use App\Core\Mailer;
use App\Models\Business;
use App\Models\User;
use PDO;

class ReservationController
{
    /**
     * Muestra el formulario de reserva de un comercio (GET /business/{id}/reserve).
     * Carga los servicios disponibles y los horarios de atención del comercio.
     *
     * @param int $businessId ID del comercio donde se quiere hacer la reserva
     */
    public function showForm(int $businessId)
    {
        // Solo usuarios autenticados pueden hacer reservas
        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Debes iniciar sesión para hacer una reserva.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Verificar que el comercio existe
        $business = Business::findById($businessId);
        if (!$business) {
            http_response_code(404);
            echo 'Comercio no encontrado.';
            return;
        }

        // Cargar los servicios y horarios necesarios para el formulario
        $services = $business->getServices();
        $schedules = $business->getSchedules();

        require_once ROOT_DIR . '/resources/views/business/reservation.php';
    }

    /**
     * Guarda una nueva reserva (POST /reserve).
     * Proceso:
     *   1. Valida los campos del formulario
     *   2. Obtiene el servicio para calcular la hora de fin
     *   3. Comprueba que no haya conflicto con otra cita existente
     *   4. Inserta la reserva y la línea de servicio en la BD
     *   5. Envía emails de confirmación al cliente y al comercio
     */
    public function store()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Recoger y validar los datos del formulario de reserva
        $businessId = (int)($_POST['business_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $horaInicio = $_POST['hora_inicio'] ?? '';

        if (!$businessId || !$serviceId || !$fecha || !$horaInicio) {
            Session::setFlash('error', 'Por favor rellena todos los campos de la reserva.');
            header('Location: ' . BASE_URL . '/business/' . $businessId . '/reserve');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Obtener los detalles del servicio para calcular la duración y el precio
        $stmtSvc = $db->prepare('SELECT * FROM service WHERE id = ? AND business_id = ? AND activo = 1');
        $stmtSvc->execute([$serviceId, $businessId]);
        $service = $stmtSvc->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            Session::setFlash('error', 'Servicio no disponible.');
            header('Location: ' . BASE_URL . '/business/' . $businessId . '/reserve');
            exit;
        }

        // Calcular la hora de fin sumando la duración en minutos al inicio
        $startTime = \DateTime::createFromFormat('H:i', $horaInicio);
        $endTime = (clone $startTime)->modify('+' . (int)$service['duracion_minutos'] . ' minutes');
        $horaFin = $endTime->format('H:i');

        // Comprobar solapamiento con reservas existentes del mismo comercio y día
        // La consulta detecta cualquier traslado: si el inicio o el fin coinciden con un rango ya ocupado
        $conflict = $db->prepare(
            "SELECT id FROM reservation
             WHERE business_id = ? AND fecha = ? AND estado != 'CANCELADA'
             AND ((hora_inicio <= ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin >= ?))"
        );
        $conflict->execute([$businessId, $fecha, $horaInicio, $horaInicio, $horaFin, $horaFin]);
        if ($conflict->fetch()) {
            Session::setFlash('error', 'Ese horario ya está reservado. Elige otra hora.');
            header('Location: ' . BASE_URL . '/business/' . $businessId . '/reserve');
            exit;
        }

        // Insertar la reserva con estado inicial PENDIENTE
        $stmt = $db->prepare(
            'INSERT INTO reservation (user_id, business_id, fecha, hora_inicio, hora_fin, estado)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            Session::get('user_id'),
            $businessId,
            $fecha,
            $horaInicio,
            $horaFin,
            'PENDIENTE'
        ]);
        $reservationId = (int)$db->lastInsertId();

        // Relacionar el servicio con la reserva en la tabla intermedia
        $db->prepare('INSERT INTO reservation_item (reservation_id, service_id, precio) VALUES (?, ?, ?)')
            ->execute([$reservationId, $serviceId, $service['precio']]);

        // ── Enviar notificaciones por email ──
        $business = Business::findById($businessId);
        $userRow = User::findById(Session::get('user_id'));

        // Datos comunes de la reserva para incluir en los emails
        $reservationData = [
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'service_name' => $service['nombre'],
        ];

        // Email al cliente con los detalles de su cita
        if ($userRow) {
            Mailer::sendReservationToClient(
                ['nombre' => $userRow->first_name, 'email' => $userRow->email],
                $reservationData,
                $business ? $business->name : 'el comercio'
            );
        }

        // Email al comercio con los datos del cliente y la cita
        if ($business) {
            $userArray = [
                'nombre' => $userRow->nombre ?? '',
                'apellidos' => $userRow->apellidos ?? '',
                'telefono' => $userRow->telefono ?? '',
            ];
            Mailer::sendReservationToBusiness(
                $business->email,
                $business->name,
                $userArray,
                $reservationData
            );
        }

        Session::setFlash('success', '¡Reserva enviada! El comercio la confirmará en breve.');
        header('Location: ' . BASE_URL . '/orders');
        exit;
    }
}
