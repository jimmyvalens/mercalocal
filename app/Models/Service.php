<?php
// =========================================================
// app/Models/Service.php — Modelo de servicio para comercios
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Service
{
    // Propiedades correspondientes a la tabla `service` con tipado estricto
    public ?int $id = null;
    public int $business_id;
    public ?int $category_id = null;
    public string $name;
    public ?string $description = null;
    public int $duration = 0;
    public float $price = 0.0;
    public bool $is_active = true; // Manejado como bool en PHP para mayor comodidad
    public string $created_at;
    public ?string $category_name = null; // Campo calculado por JOIN inicializado a null

    /**
     * Busca un servicio por su identificador único.
     * * @param int $id ID del servicio
     * @return Service|false Instancia de Service o false si no se encuentra
     */
    public static function findById(int $id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM service WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch();
    }

    /**
     * Obtiene los servicios vinculados a un comercio específico.
     * * @param int $businessId ID del comercio
     * @return Service[] Lista de objetos de tipo Service
     */
    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT s.*, c.name AS category_name FROM service s LEFT JOIN category c ON c.id = s.category_id WHERE s.business_id = ? ORDER BY s.created_at DESC");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Registra un nuevo servicio en la base de datos.
     * * @param array $data Datos del servicio
     * @return int ID del registro insertado
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO service (business_id, category_id, name, description, duration, price, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);

        // Conversión limpia a entero para la persistencia en la columna TINYINT
        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        $stmt->execute([
            $data['business_id'],
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['duration'] ?? 0,
            $data['price'] ?? 0.0,
            $isActive,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza los datos de un servicio existente.
     * * @param int $id ID del servicio a modificar
     * @param array $data Nuevos datos del servicio
     * @return bool True si la operación fue exitosa, false en caso contrario
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE service SET category_id = ?, name = ?, description = ?, duration = ?, price = ?, is_active = ? WHERE id = ?";
        $stmt = $db->prepare($sql);

        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['duration'] ?? 0,
            $data['price'] ?? 0.0,
            $isActive,
            $id,
        ]);
    }

    /**
     * Elimina físicamente un servicio de la base de datos.
     * * @param int $id ID del servicio a eliminar
     * @return bool True si se eliminó correctamente, false de lo contrario
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM service WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
