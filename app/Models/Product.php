<?php
// =========================================================
// app/Models/Product.php — Modelo de producto
// Representa un artículo que un comercio pone a la venta.
// Gestiona la lectura de registros de la tabla `product`.
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Product
{
    // Propiedades que corresponden a las columnas de la tabla `product`.
    // Propiedades tipadas con valores por defecto para el control estricto de PHP 8+
    public ?int $id = null;
    public int $business_id;
    public ?int $category_id = null;
    public string $name;
    public ?string $description = null;
    public float $price = 0.0;
    public int $stock = 0;
    public ?string $image_path = null;
    public bool $is_active = true;
    public string $created_at;
    public ?string $category_name = null; // Campo calculado por JOIN

    /**
     * Busca un producto por su ID.
     * Se usa en el carrito para validar que el producto existe
     * y que hay suficiente stock antes de añadirlo.
     *
     * @param  int            $id ID del producto
     * @return Product|false      Objeto Product o false si no existe
     */
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM product WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch();
    }

    /**
     * Lista los productos de un comercio
     * @param int $businessId
     * @return array
     */
    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        // Cambiado c.nombre por c.name de forma definitiva
        $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM product p LEFT JOIN category c ON c.id = p.category_id WHERE p.business_id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Crea un nuevo producto
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO product (business_id, category_id, name, description, price, stock, image_path, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['stock'] ?? 0,
            $data['image_path'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza un producto existente
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE product SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image_path = ?, is_active = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['stock'] ?? 0,
            $data['image_path'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $id,
        ]);
    }
    /**
     * Elimina un producto por id
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM product WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
