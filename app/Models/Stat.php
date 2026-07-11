<?php

/**
 * =========================================================
 * app/Models/Stat.php — Modelo de estadísticas
 *
 * Proporciona indicadores generales y por comercio para el panel:
 * · Recupera estadísticas globales de usuarios, negocios, productos y ventas
 * · Devuelve métricas específicas para un comercio por su ID
 * · Calcula la evolución mensual de facturación del año actual
 * =========================================================
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Stat
{
    /**
     * Obtiene estadísticas globales para el panel de administración.
     *
     * @return array Estadísticas agregadas de usuarios, negocios, productos y ventas.
     */
    public static function getAdminStats()
    {
        $db = Database::getInstance()->getConnection();
        $stats = [];

        $stats['users'] = $db->query("SELECT count(*) as total FROM user")->fetch()['total'];
        $stats['businesses'] = $db->query("SELECT count(*) as total FROM business")->fetch()['total'];
        $stats['businesses_active'] = $db->query("SELECT count(*) as total FROM business WHERE activo = 1")->fetch()['total'];
        $stats['businesses_inactive'] = $db->query("SELECT count(*) as total FROM business WHERE activo = 0")->fetch()['total'];
        $stats['products'] = $db->query("SELECT count(*) as total FROM product WHERE activo = 1")->fetch()['total'];
        $stats['sales'] = $db->query("SELECT sum(total) as total FROM purchase WHERE estado='COMPLETADO'")->fetch()['total'] ?? 0;
        $stats['orders'] = $db->query("SELECT count(*) as total FROM purchase WHERE estado='COMPLETADO'")->fetch()['total'];

        return $stats;
    }

    /**
     * Obtiene las estadísticas específicas de un solo comercio.
     *
     * @param int $businessId ID del comercio actual.
     * @return array Estadísticas de productos y pedidos pendientes.
     */
    public static function getBusinessStats(int $businessId): array
    {
        $db = Database::getInstance()->getConnection();
        $stats = [];

        $stmt = $db->prepare("SELECT count(*) as total FROM product WHERE business_id = ? AND activo = 1");
        $stmt->execute([$businessId]);
        $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $stmt = $db->prepare("SELECT count(*) as total FROM purchase WHERE business_id = ? AND estado = 'PENDIENTE'");
        $stmt->execute([$businessId]);
        $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return $stats;
    }

    /**
     * Obtiene la evolución de facturación mensual del año actual.
     *
     * @return array Monto facturado por mes del 1 al 12.
     */
    public static function getMonthlyEvolution()
    {
        $db = Database::getInstance()->getConnection();

        $evolution = array_fill(1, 12, 0);

        $sql = "SELECT 
                    MONTH(created_at) as mes, 
                    SUM(total) as total_mes 
                FROM purchase 
                WHERE estado = 'PAGADO' AND YEAR(created_at) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(created_at)
                ORDER BY mes ASC";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $index = (int)$row['mes'];
            $evolution[$index] = (float)$row['total_mes'];
        }

        return $evolution;
    }
}
