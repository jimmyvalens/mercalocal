<?php
// =========================================================
// app/Models/Schedule.php — Modelo de horarios de apertura
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Schedule
{
    // Propiedades mapeadas con control de inicialización estricto para PHP 8+
    public ?int $id = null;
    public int $business_id;
    public int $day_of_week;
    public string $opening_time = '00:00:00'; // Inicializado por seguridad para evitar UninitializedError
    public string $closing_time = '00:00:00';

    /**
     * Obtiene los horarios de un comercio ordenados por día y hora.
     * Mapea de forma segura a instancias de la clase Schedule.
     * * @param int $businessId ID del comercio
     * @return Schedule[] Lista de objetos Schedule
     */
    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM schedule WHERE business_id = ? ORDER BY day_of_week, opening_time");
        $stmt->execute([$businessId]);

        // Al usar FETCH_CLASS en PHP 8+, las propiedades con valores por defecto 
        // garantizan que no haya conflictos de inicialización estricta.
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Crea un nuevo registro de horario de apertura para un comercio.
     * * @param array $data Datos del horario (business_id, day_of_week, opening_time, closing_time)
     * @return int ID del registro insertado
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO schedule (business_id, day_of_week, opening_time, closing_time) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['day_of_week'],
            $data['opening_time'],
            $data['closing_time'],
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Elimina un horario de apertura por su identificador único.
     * * @param int $id ID del horario a eliminar
     * @return bool True si se eliminó con éxito, false en caso contrario
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM schedule WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
