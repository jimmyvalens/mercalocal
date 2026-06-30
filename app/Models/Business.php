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
    public $id_categoria;

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

        // Consulta base corregida: Trae el comercio y SOLO su categoría principal
        $sql = "SELECT b.*, c.nombre as categorias
        FROM business b
        LEFT JOIN category c ON b.id_categoria = c.id
        WHERE b.activo = 1";

        $params = [];

        // Filtro de búsqueda por nombre (búsqueda parcial con LIKE)
        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        // Filtro por categoría (restringido únicamente a productos)
        // Filtro por categoría del comercio (b.id_categoria)
        if (!empty($categoryId)) {
            $sql .= " AND b.id_categoria = ?";
            $params[] = (int)$categoryId; // Forzamos a entero por seguridad
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

        // Consulta base limpia: solo cruzamos con productos físicos
        $sql = "SELECT COUNT(DISTINCT b.id) as total
                FROM business b
                LEFT JOIN product p ON b.id = p.business_id
                WHERE b.activo = 1";

        $params = [];

        // Filtro por nombre del comercio
        if (!empty($search)) {
            $sql .= " AND b.nombre LIKE ?";
            $params[] = '%' . $search . '%';
        }

        // Filtro por categoría (restringido únicamente a productos)
        // Filtro por categoría del comercio (b.id_categoria)
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

    /**
     * Crea un nuevo registro de comercio en la base de datos.
     *
     * @param  array $data Datos saneados del comercio
     * @return int          ID del comercio recién insertado
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
            $data['activo'] ?? 1, // No requiere aprobación previa de ADMIN
            $data['logo_path'] ?? null,
            $data['hero_path'] ?? null
        ]);

        // Al crear o modificar comercios, es buena práctica limpiar la caché del catálogo
        // para que el nuevo negocio aparezca inmediatamente a los clientes.
        \App\Core\Cache::clear();

        return (int)$db->lastInsertId();
    }
}
