<?php

/**
 * =========================================================
 * app/Core/Database.php — Capa de acceso a la base de datos
 *
 * Gestiona la conexión PDO única para consultas SQL:
 * · Crea una instancia Singleton de la clase Database
 * · Inicializa PDO con configuración de conexión y opciones
 * · Proporciona el objeto PDO para ejecutar consultas SQL
 * =========================================================
 */

namespace App\Core;

use PDO;

class Database
{
    // Instancia única (patrón Singleton)
    private static ?Database $instance = null;

    // Objeto de conexión PDO
    private PDO $pdo;

    /**
     * Constructor privado: evita que se pueda instanciar
     * la clase directamente desde fuera con `new Database()`.
     * Establece la conexión con los parámetros de config.php.
     *
     * @return void
     */
    private function __construct()
    {
        require_once __DIR__ . '/../../config.php';

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Devuelve la instancia única de Database.
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Devuelve el objeto PDO para ejecutar consultas SQL.
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}
