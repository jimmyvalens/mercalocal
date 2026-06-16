<?php
// =========================================================
// app/Models/Review.php
// Modelo para reseñas y valoraciones de comercios en Mercalocal
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class Review
{
    // Propiedades tipadas estrictas alineadas con PHP 8+
    public ?int $id = null;
    public int $business_id;
    public int $user_id;
    public int $rating; // Rango de 1 a 5
    public ?string $comment = null;
    public string $created_at;

    // Propiedad extra para los resultados de consultas con JOIN
    public ?string $user_name = null;

    /**
     * Crear una nueva reseña en la plataforma.
     * Incorpora validación de rango crítico para el tribunal.
     *
     * @param int $businessId ID del comercio reseñado
     * @param int $userId ID del usuario que emite el comentario
     * @param int $rating Puntuación de 1 a 5
     * @param string|null $comment Comentario de texto opcional
     * @return int ID de la reseña insertada
     * @throws \InvalidArgumentException Si la puntuación se sale del rango permitido
     */
    public static function create(int $businessId, int $userId, int $rating, ?string $comment = null): int
    {
        // Validación en la capa del modelo para blindar la integridad
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('La puntuación debe estar comprendida entre 1 y 5.');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO review (business_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$businessId, $userId, $rating, $comment]);
        return (int)$db->lastInsertId();
    }

    /**
     * Obtener reseñas paginadas de un comercio específico.
     * Corregida la columna 'nombre' por 'first_name' tras la normalización.
     *
     * @param int $businessId ID del comercio
     * @param int|null $limit Límite de registros para paginación
     * @param int $offset Desplazamiento inicial
     * @return array Listado de reseñas en formato de array asociativo
     */
    public static function getByBusiness(int $businessId, ?int $limit = null, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();
        // Corregido: u.nombre pasa a ser u.first_name
        $sql = "SELECT r.*, u.first_name AS user_name FROM review r JOIN user u ON r.user_id = u.id WHERE r.business_id = ? ORDER BY r.created_at DESC";
        $params = [$businessId];

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $db->prepare($sql);

        // Si usas emulación de prepares o tipos estrictos en el execute con LIMIT, 
        // a veces PDO interpreta los números como strings si pasas un array plano. 
        // Para estar 100% seguros con LIMIT en PDO nativo sin fallos:
        if ($limit !== null) {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $businessId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute($params);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular promedio de rating y volumen de participación para un comercio.
     *
     * @param int $businessId ID del comercio
     * @return array Contiene las claves 'average' (float) y 'total' (int)
     */
    public static function getAverageRating(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM review WHERE business_id = ?");
        $stmt->execute([$businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'average' => round((float)($result['avg_rating'] ?? 0.0), 1),
            'total' => (int)($result['total_reviews'] ?? 0)
        ];
    }

    /**
     * Verificar si un usuario ya ha valorado un comercio concreto.
     * Previene la duplicidad no deseada de opiniones.
     *
     * @param int $businessId ID del comercio
     * @param int $userId ID del usuario activo
     * @return bool True si ya existe registro, false si está libre
     */
    public static function hasReviewed(int $businessId, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM review WHERE business_id = ? AND user_id = ?");
        $stmt->execute([$businessId, $userId]);
        return $stmt->fetch() !== false;
    }
}
