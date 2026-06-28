<?php
// =========================================================
// src/Models/Category.php — Modelo de categoría
// Representa una categoría temática (p.ej. Alimentación,
// Peluquería, Deportes) que agrupa productos y servicios.
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
    // Propiedades que corresponden a las columnas de la tabla `category`
    public $id;
    public $nombre;
    public $tipo;
    public $parent_id;

    /**
     * Devuelve todas las categorías disponibles ordenadas alfabéticamente.
     * Se usa en el filtro de búsqueda del catálogo de comercios.
     *
     * @return Category[] Array de objetos Category
     */
    public static function getAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM category ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Obtiene únicamente las categorías padre como objetos.
     */
    public static function getParents(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM category WHERE parent_id IS NULL OR parent_id = '' ORDER BY nombre ASC");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Obtiene las subcategorías filtradas por la categoría del comercio y el tipo (producto/servicio)
     * * @param int $parentId ID de la categoría del comercio (ej: 1 para Alimentación)
     * @param string $type Tipo de subcategoría ('producto' o 'servicio')
     * @return array Array asociativo con las subcategorías
     */
    public static function getChildrenByParentAndType($parentId, $type)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
        SELECT id, nombre 
        FROM category 
        WHERE parent_id = :parent_id AND tipo = :tipo 
        ORDER BY nombre ASC
    ");

        $stmt->execute([
            ':parent_id' => $parentId,
            ':tipo' => $type
        ]);

        // Usamos FETCH_ASSOC porque tu vista utiliza la sintaxis de array: $c['id']
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
