<?php
// =========================================================
// app/Models/User.php — Modelo de usuario
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Clase User
 *
 * Gestiona la persistencia, consulta y actualización de los usuarios
 * registrados en la plataforma, controlando las credenciales y los roles asignados.
 */
class User
{
    // Propiedades tipadas con valores por defecto para evitar errores "uninitialized"
    public ?int $id = null;
    public string $first_name;
    public ?string $last_name = null;
    public ?string $phone = null;
    public ?string $email = null;
    public string $password_hash;
    public ?string $image_path = null;
    public ?string $address = null;
    public string $role;
    public string $created_at;
    public ?string $updated_at = null;

    /**
     * Busca un usuario por su dirección de correo electrónico o número de teléfono.
     *
     * @param string $identificador Email o teléfono del usuario
     * @return User|null Objeto User si se encuentra coincidencia, null en caso contrario
     */
    public static function findByIdentifier($identificador)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ? OR phone = ?");
        $stmt->execute([$identificador, $identificador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user = new self();
            foreach ($row as $key => $value) {
                // Evitamos asignar null a propiedades estrictas si vinieran vacías de BD
                if (property_exists($user, $key)) {
                    $user->$key = $value;
                }
            }
            return $user;
        }
        return null;
    }

    /**
     * Busca un usuario por su dirección de correo electrónico.
     *
     * @param string $email Correo electrónico del usuario
     * @return User|null Objeto User si existe, null en caso contrario
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user = new self();
            foreach ($row as $key => $value) {
                if (property_exists($user, $key)) {
                    $user->$key = $value;
                }
            }
            return $user;
        }
        return null;
    }

    /**
     * Busca un usuario por su identificador numérico único.
     *
     * @param int $id Identificador del usuario
     * @return User|null Objeto User o null si no se encuentra
     */
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user = new self();
            foreach ($row as $key => $value) {
                if (property_exists($user, $key)) {
                    $user->$key = $value;
                }
            }
            return $user;
        }
        return null;
    }

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * @param array $data Datos del perfil del usuario
     * @return int Identificador del registro insertado
     */
    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO user (first_name, last_name, phone, email, password_hash, role)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['first_name'],
            $data['last_name'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'USER'
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza los datos del perfil del usuario en la base de datos.
     *
     * @param array $data Datos modificados del perfil (usando claves en inglés)
     * @return bool True si la operación se realiza con éxito, false en caso contrario
     */
    public function update($data)
    {
        $db = Database::getInstance()->getConnection();
        $fields = [];
        $params = [];

        // Mapeo directo uno a uno con la tabla de la base de datos
        if (isset($data['first_name'])) {
            $fields[] = "first_name = ?";
            $params[] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $fields[] = "last_name = ?";
            $params[] = $data['last_name'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        if (isset($data['address'])) {
            $fields[] = "address = ?";
            $params[] = $data['address'];
        }
        if (isset($data['image_path'])) {
            $fields[] = "image_path = ?";
            $params[] = $data['image_path'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $this->id;

        $sql = "UPDATE user SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
