<?php
// app/Models/Review.php
// Modelo para reseñas y valoraciones de comercios
namespace App\Models;

use App\Core\Database;
use PDO;

class Review
{
    public $id;
    public $business_id;
    public $user_id;
    public $rating; // 1-5
    public $comment;
    public $created_at;

    /**
     * Crear una nueva reseña
     */
    public static function create($businessId, $userId, $rating, $comment = null)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO review (business_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$businessId, $userId, $rating, $comment]);
        return $db->lastInsertId();
    }

    /**
     * Obtener reseñas de un comercio
     */
    public static function getByBusiness($businessId, $limit = null, $offset = 0)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT r.*, u.nombre as user_name FROM review r JOIN user u ON r.user_id = u.id WHERE r.business_id = ? ORDER BY r.created_at DESC";
        $params = [$businessId];

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular promedio de rating para un comercio
     */
    public static function getAverageRating($businessId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM review WHERE business_id = ?");
        $stmt->execute([$businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'average' => round((float)$result['avg_rating'], 1),
            'total' => (int)$result['total_reviews']
        ];
    }

    /**
     * Verificar si usuario ya reseñó este comercio
     */
    public static function hasReviewed($businessId, $userId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM review WHERE business_id = ? AND user_id = ?");
        $stmt->execute([$businessId, $userId]);
        return $stmt->fetch() !== false;
    }
}
