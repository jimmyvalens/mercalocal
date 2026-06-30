<?php
// =========================================================
// app/Models/Schedule.php — Modelo de horarios de apertura
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Schedule
{
    public $id;
    public $business_id;
    public $dia_semana; // 0=domingo..6=sabado
    public $hora_apertura; // TIME
    public $hora_cierre;   // TIME

    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM schedule WHERE business_id = ? ORDER BY dia_semana, hora_apertura");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO schedule (business_id, dia_semana, hora_apertura, hora_cierre) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['dia_semana'],
            $data['hora_apertura'],
            $data['hora_cierre'],
        ]);
        return (int)$db->lastInsertId();
    }

    // En App\Models\Schedule.php

    public static function exists($business_id, $dia_semana, $hora_apertura, $hora_cierre)
    {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(*) FROM schedule 
            WHERE business_id = :business_id 
              AND dia_semana = :dia_semana 
              AND hora_apertura = :hora_apertura 
              AND hora_cierre = :hora_cierre";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':business_id'   => $business_id,
            ':dia_semana'    => $dia_semana,
            ':hora_apertura' => $hora_apertura,
            ':hora_cierre'   => $hora_cierre
        ]);

        // Si el conteo es mayor que 0, es que ya existe
        return $stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM schedule WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
