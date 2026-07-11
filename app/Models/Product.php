<?php

/**
 * =========================================================
 * app/Models/Product.php — Modelo de producto
 *
 * Administra productos de un comercio:
 * · Busca productos por ID
 * · Lista productos por comercio
 * · Crea, actualiza y elimina registros
 * =========================================================
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Product
{
    // Propiedades que corresponden a las columnas de la tabla `product`
    /** @var int|null */
    public $id;
    /** @var int */
    public $business_id; // ID del comercio al que pertenece el producto
    /** @var int|null */
    public $category_id; // ID de la categoría del producto
    /** @var string */
    public $nombre;
    /** @var string|null */
    public $descripcion;
    /** @var float|string */
    public $precio; // Precio unitario en euros
    /** @var int|string */
    public $stock; // Unidades disponibles
    /** @var string|null */
    public $imagen; // Nombre del archivo de imagen (almacenado en public/img/)
    /** @var int */
    public $activo; // 1 = visible en el catálogo, 0 = oculto
    /** @var string|null */
    public $created_at;
    /** @var string|null */
    public $category_name;
    /** @var string|null */
    public $updated_at;
    /** @var string|null */
    public $unidad_medida; // Campo calculado mediante JOIN con la tabla category

    /**
     * Busca un producto por su ID.
     *
     * @param int $id
     * @return Product|false
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
     * Lista los productos de un comercio.
     *
     * @param int $businessId
     * @return array
     */
    public static function getByBusiness(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT p.*, c.nombre AS category_name FROM product p LEFT JOIN category c ON c.id = p.category_id WHERE p.business_id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Crea un nuevo producto.
     *
     * @param array $data
     * @return int
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO product (business_id, category_id, nombre, descripcion, precio, unidad_medida, stock, imagen, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['precio'] ?? 0,
            $data['unidad_medida'] ?? 'ud',
            $data['stock'] ?? 0,
            $data['imagen'] ?? null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza un producto existente.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE product SET category_id = ?, nombre = ?, descripcion = ?, precio = ?, unidad_medida = ?, stock = ?, imagen = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['precio'] ?? 0,
            $data['unidad_medida'] ?? 'ud',
            $data['stock'] ?? 0,
            $data['imagen'] ?? null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
            $id,
        ]);
    }

    /**
     * Elimina un producto por id.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM product WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
