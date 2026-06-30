<?php
// =========================================================
// app/Core/Database.php — Capa de acceso a la base de datos
// Implementa el patrón Singleton para reutilizar una única
// conexión PDO a lo largo de toda la petición HTTP.
// =========================================================
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    // Instancia única (patrón Singleton)
    private static $instance = null;

    // Objeto de conexión PDO
    private $pdo;

    /**
     * Constructor privado: evita que se pueda instanciar
     * la clase directamente desde fuera con `new Database()`.
     * Establece la conexión con los parámetros de config.php.
     */
    private function __construct()
    {
        require_once __DIR__ . '/../../config.php';

        // DSN (Data Source Name): especifica el tipo de BD, host, nombre y charset
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        // Opciones de PDO: modo de error, modo de fetch por defecto y consultas de verdad (no emuladas)
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanza excepciones ante errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve filas como arrays asociativos
            PDO::ATTR_EMULATE_PREPARES => false, // Usa consultas preparadas nativas de MySQL
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Forzar el juego de caracteres UTF-8 para soportar tildes y caracteres especiales
            $this->pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Devuelve la instancia única de Database.
     * Si todavía no existe, la crea (lazy initialization).
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
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}
