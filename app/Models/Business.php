<?php
// =========================================================
// app/Models/Business.php — Modelo de comercio
// Representa un negocio registrado en la plataforma.
// Gestiona la lectura de datos de la tabla `business`
// y provee métodos para obtener sus productos, servicios
// y horarios de apertura relacionados.
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Business
{
    // Propiedades que corresponden a las columnas de la tabla `business`
    public $id;
    public $user_id; // ID del usuario propietario del comercio
    public $nombre;
    public $descripcion;
    public $telefono;
    public $email;
    public $web;
    public $activo; // 1 = visible en el catálogo, 0 = oculto
    public $logo_path;
    public $hero_path;
    public $created_at;
    public $updated_at;
    public $categorias; // Campo calculado: categorías concatenadas con GROUP_CONCAT

    /**
     * Devuelve todos los comercios activos.
     * Admite búsqueda por nombre y filtrado por categoría.
     *
     * @param  string      $search     Texto a buscar en el nombre del comercio
     * @param  int|null    $categoryId ID de categoría para filtrar (opcional)
     * @param  int         $limit      Número máximo de resultados (paginación)
     * @param  int         $offset     Desplazamiento para paginación
     * @return Business[]              Array de objetos Business
     */
    public static function getAll($search = '', $categoryId = null, $limit = null, $offset = 0)
    {
        $cacheKey = 'businesses_' . md5($search . '_' . $categoryId . '_' . $limit . '_' . $offset);

        $cached = \App\Core\Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $db = Database::getInstance()->getConnection();

        // Consulta base: lista comercios activos con sus categorías agrupadas
        $sql = "SELECT b.*, GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                FROM business b
                LEFT JOIN product p  ON b.id = p.business_id
                LEFT JOIN service s  ON b.id = s.business_id
                LEFT JOIN category c ON p.category_id = c.id OR s.category_id = c.id
                WHERE b.activo = 1";

        $params = [];

        // Filtro de búsqueda por nombre (búsqueda parcial con LIKE)
        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        // Filtro por categoría (aplica a productos y servicios)
        if (!empty($categoryId)) {
            $sql .= " AND (p.category_id = ? OR s.category_id = ?)";
            $params[] = $categoryId;
            $params[] = $categoryId;
        }

        $sql .= " GROUP BY b.id"; // Agrupar para que GROUP_CONCAT funcione correctamente

        // Paginación
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_CLASS, self::class);

        // Cache por 30 minutos
        \App\Core\Cache::set($cacheKey, $result, 1800);

        return $result;
    }

    /**
     * Cuenta el total de comercios activos según filtros.
     *
     * @param  string      $search     Texto a buscar
     * @param  int|null    $categoryId ID de categoría
     * @return int                     Total de comercios
     */
    public static function countAll($search = '', $categoryId = null)
    {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(DISTINCT b.id) as total
                FROM business b
                LEFT JOIN product p  ON b.id = p.business_id
                LEFT JOIN service s  ON b.id = s.business_id
                WHERE b.activo = 1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        if (!empty($categoryId)) {
            $sql .= " AND (p.category_id = ? OR s.category_id = ?)";
            $params[] = $categoryId;
            $params[] = $categoryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Busca un comercio por su ID.
     *
     * @param  int          $id ID del comercio
     * @return Business|false   Objeto Business o false si no existe
     */
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch();
    }

    /**
     * Obtener rating promedio y total de reseñas
     */
    public function getRating()
    {
        return \App\Models\Review::getAverageRating($this->id);
    }

    /**
     * Devuelve todos los servicios activos de este comercio
     * junto con el nombre de su categoría.
     *
     * @return array Array asociativo con los servicios
     */
    public function getServices()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT s.*, s.duracion_minutos AS duracion, c.nombre as category_name
             FROM service s
             LEFT JOIN category c ON s.category_id = c.id
             WHERE s.business_id = ? AND s.activo = 1"
        );
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve todos los productos activos de este comercio
     * junto con el nombre de su categoría.
     *
     * @return array Array asociativo con los productos
     */
    public function getProducts()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT p.*, c.nombre as category_name
             FROM product p
             LEFT JOIN category c ON p.category_id = c.id
             WHERE p.business_id = ? AND p.activo = 1"
        );
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve los horarios de apertura del comercio
     * ordenados por día de la semana y hora de apertura.
     *
     * @return array Array con los horarios de la tabla `schedule`
     */
    public function getSchedules()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM schedule
             WHERE business_id = ?
             ORDER BY dia_semana, hora_apertura"
        );
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
}
