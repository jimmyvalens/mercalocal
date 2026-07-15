<?php

/**
 * =========================================================
 * app/Models/Business.php — Modelo de comercio
 *
 * Representa un negocio registrado en la plataforma.
 * · Gestiona la lectura de datos del comercio
 * · Proporciona métodos para productos y horarios
 * · Soporta búsqueda, filtrado y creación
 * =========================================================
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Business
{
    // Propiedades que corresponden a las columnas de la tabla `business`
    public int $id;
    public int $user_id; // ID del usuario propietario del comercio
    public string $nombre;
    public ?string $descripcion;
    public ?string $telefono;
    public ?string $email;
    public ?string $web;
    public int $activo; // 1 = visible en el catálogo, 0 = oculto
    public ?string $logo_path;
    public ?string $hero_path;
    public ?string $created_at;
    public ?string $updated_at;
    public ?string $categorias; // Campo calculado: categorías concatenadas con GROUP_CONCAT
    public ?int $id_categoria;

    /**
     * Obtiene todos los comercios activos.
     *
     * @param string $search
     * @param int|null $categoryId
     * @param int|null $limit
     * @param int $offset
     * @return Business[]
     */
    public static function getAll($search = '', $categoryId = null, $limit = null, $offset = 0)
    {
        // Solo busca en caché si está activa en las variables de entorno
        $cacheEnabled = filter_var($_ENV['CACHE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $cacheKey = 'businesses_' . md5($search . '_' . $categoryId . '_' . $limit . '_' . $offset);

        if ($cacheEnabled) {
            $cached = \App\Core\Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $db = Database::getInstance()->getConnection();

        $sql = "SELECT b.*, c.nombre as categorias
        FROM business b
        LEFT JOIN category c ON b.id_categoria = c.id
        WHERE b.activo = 1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        if (!empty($categoryId)) {
            $sql .= " AND b.id_categoria = ?";
            $params[] = (int)$categoryId;
        }

        $sql .= " GROUP BY b.id";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_CLASS, self::class);

        if ($cacheEnabled) {
            \App\Core\Cache::set($cacheKey, $result, 1800);
        }

        return $result;
    }

    /**
     * Cuenta los comercios activos según filtros.
     *
     * @param string $search
     * @param int|null $categoryId
     * @return int
     */
    public static function countAll($search = '', $categoryId = null)
    {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(DISTINCT b.id) as total
                FROM business b
                LEFT JOIN product p ON b.id = p.business_id
                WHERE b.activo = 1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        if (!empty($categoryId)) {
            $sql .= " AND b.id_categoria = ?";
            $params[] = (int)$categoryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Busca un comercio por su ID.
     *
     * @param int $id
     * @return Business|false
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
     * Obtiene los productos activos del comercio.
     *
     * @return array
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
     * Obtiene los horarios de apertura del comercio.
     *
     * @return array
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

    /**
     * Inserta un nuevo comercio en la base de datos.
     *
     * @param array $data
     * @return int
     */
    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();

        $sql = "INSERT INTO business (
                    user_id, nombre, descripcion, telefono, email, web, 
                    activo, logo_path, hero_path, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['nombre'],
            $data['descripcion'],
            $data['telefono'],
            $data['email'],
            $data['web'] ?? null,
            $data['activo'] ?? 1,
            $data['logo_path'] ?? null,
            $data['hero_path'] ?? null
        ]);

        \App\Core\Cache::clear();

        return (int)$db->lastInsertId();
    }
}
