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

    /**
     * 📊 NUEVO: Obtiene la evolución de facturación mensual del año actual.
     * Genera un mapa perfecto del mes 1 al 12 para alimentar gráficos en el Frontend (Chart.js).
     * @return array
     */
    public static function getMonthlyEvolution()
    {
        $db = Database::getInstance()->getConnection();

        // 1. Inicializamos un contenedor con los 12 meses del año a 0 para evitar huecos en la gráfica
        $evolution = array_fill(1, 12, 0);

        // 2. Consulta agrupada por mes para las compras completadas de este año
        $sql = "SELECT 
                    MONTH(created_at) as mes, 
                    SUM(total) as total_mes 
                FROM purchase 
                WHERE estado = 'PAGADO' AND YEAR(created_at) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(created_at)
                ORDER BY mes ASC";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Mapeamos las filas encontradas sobre nuestro molde de 12 meses
        foreach ($rows as $row) {
            $index = (int)$row['mes'];
            $evolution[$index] = (float)$row['total_mes'];
        }

        return $evolution;
    }
}
