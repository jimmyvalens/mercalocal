<?php
// =========================================================
// app/Models/Stat.php — Modelo de estadísticas analíticas
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Clase Stat
 *
 * Gestiona la recopilación de datos estadísticos y métricas globales
 * del sistema para su visualización en los paneles de control.
 */
class Stat
{
    /**
     * Obtiene las estadísticas globales del sistema para el panel de administración.
     *
     * Recupera el conteo consolidado de usuarios, comercios, productos,
     * servicios, reservas e ingresos financieros totales de forma tipada.
     *
     * @return array Conjunto de datos estadísticos indexados por métrica con tipos nativos
     */
    public static function getAdminStats(): array
    {
        $db = Database::getInstance()->getConnection();
        $stats = [];

        // Forzamos FETCH_ASSOC y casteamos los resultados para cumplir con el tipado estricto de PHP 8+
        $stats['users'] = (int)($db->query("SELECT COUNT(*) as total FROM user")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stats['businesses'] = (int)($db->query("SELECT COUNT(*) as total FROM business")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stats['businesses_active'] = (int)($db->query("SELECT COUNT(*) as total FROM business WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stats['products'] = (int)($db->query("SELECT COUNT(*) as total FROM product WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stats['services'] = (int)($db->query("SELECT COUNT(*) as total FROM service WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // El SUM puede devolver NULL si no hay compras; controlamos con float y operador null coalescing
        $stats['sales'] = (float)($db->query("SELECT SUM(total) as total FROM purchase WHERE status='PAGADO'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0.0);

        $stats['orders'] = (int)($db->query("SELECT COUNT(*) as total FROM purchase WHERE status='PAGADO'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stats['reservations'] = (int)($db->query("SELECT COUNT(*) as total FROM reservation WHERE status='CONFIRMADA'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        return $stats;
    }
}
