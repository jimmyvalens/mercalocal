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
}
