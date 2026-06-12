<?php
// =========================================================
// src/Models/Service.php — Modelo de servicio para comercios
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Service
{
    public $id;
    public $business_id;
    public $category_id;
    public $nombre;
    public $descripcion;
    public $duracion; // minutos
    public $precio;
    public $activo;
    public $created_at;
    public $category_name;

    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM service WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch();
    }

    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT s.*, c.nombre AS category_name FROM service s LEFT JOIN category c ON c.id = s.category_id WHERE s.business_id = ? ORDER BY s.created_at DESC");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO service (business_id, category_id, nombre, descripcion, duracion, precio, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['duracion'] ?? 0,
            $data['precio'] ?? 0,
            isset($data['activo']) ? (int)$data['activo'] : 1,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE service SET category_id = ?, nombre = ?, descripcion = ?, duracion = ?, precio = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['duracion'] ?? 0,
            $data['precio'] ?? 0,
            isset($data['activo']) ? (int)$data['activo'] : 1,
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM service WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
