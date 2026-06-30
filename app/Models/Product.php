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
    // Propiedades que corresponden a las columnas de la tabla `product`
    public $id;
    public $business_id; // ID del comercio al que pertenece el producto
    public $category_id; // ID de la categoría del producto
    public $nombre;
    public $descripcion;
    public $precio; // Precio unitario en euros
    public $stock; // Unidades disponibles
    public $imagen; // Nombre del archivo de imagen (almacenado en public/img/)
    public $activo; // 1 = visible en el catálogo, 0 = oculto
    public $created_at;
    public $category_name;
    public $updated_at;
    public $unidad_medida; // Campo calculado mediante JOIN con la tabla category

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
        $stmt = $db->prepare("SELECT p.*, c.nombre AS category_name FROM product p LEFT JOIN category c ON c.id = p.category_id WHERE p.business_id = ? ORDER BY p.created_at DESC");
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
        // Añadimos 'unidad_medida' en los campos y un '?' más en los VALUES
        $sql = "INSERT INTO product (business_id, category_id, nombre, descripcion, precio, unidad_medida, stock, imagen, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['business_id'],
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['precio'] ?? 0,
            $data['unidad_medida'] ?? 'ud', // 🔥 NUEVO: Mapeado en la posición correcta
            $data['stock'] ?? 0,
            $data['imagen'] ?? null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza un producto existente
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        // Añadimos 'unidad_medida = ?' en el SET
        $sql = "UPDATE product SET category_id = ?, nombre = ?, descripcion = ?, precio = ?, unidad_medida = ?, stock = ?, imagen = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['precio'] ?? 0,
            $data['unidad_medida'] ?? 'ud', // 🔥 NUEVO: Mapeado en la posición correcta
            $data['stock'] ?? 0,
            $data['imagen'] ?? null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
            $id, // El ID siempre al final por el WHERE id = ?
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
