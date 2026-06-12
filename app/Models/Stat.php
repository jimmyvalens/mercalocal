<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Stat
{
    /**
     * Obtiene estadísticas globales para el panel de administración.
     * @return array
     */
    public static function getAdminStats()
    {
        $db = Database::getInstance()->getConnection();
        $stats = [];
        
        $stats['users'] = $db->query("SELECT count(*) as total FROM user")->fetch()['total'];
        $stats['businesses'] = $db->query("SELECT count(*) as total FROM business")->fetch()['total'];
        $stats['businesses_active'] = $db->query("SELECT count(*) as total FROM business WHERE activo = 1")->fetch()['total'];
        $stats['products'] = $db->query("SELECT count(*) as total FROM product WHERE activo = 1")->fetch()['total'];
        $stats['services'] = $db->query("SELECT count(*) as total FROM service WHERE activo = 1")->fetch()['total'];
        $stats['sales'] = $db->query("SELECT sum(total) as total FROM purchase WHERE estado='PAGADO'")->fetch()['total'] ?? 0;
        $stats['orders'] = $db->query("SELECT count(*) as total FROM purchase WHERE estado='PAGADO'")->fetch()['total'];
        $stats['reservations'] = $db->query("SELECT count(*) as total FROM reservation WHERE estado='CONFIRMADA'")->fetch()['total'];

        return $stats;
    }
}
