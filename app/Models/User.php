<?php
// =========================================================
// src/Models/User.php — Modelo de usuario
// Representa a un usuario registrado en la plataforma.
// Gestiona la lectura y creación de registros en la tabla `user`.
// Los roles posibles son: USER, BUSINESS y ADMIN.
// =========================================================
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    // Propiedades que corresponden a las columnas de la tabla `user`
    public $id;
    public $nombre;
    public $apellidos;
    public $telefono;
    public $email;
    public $password_hash; // Contraseña almacenada como hash bcrypt
    public $imagen;
    public $direccion; // Dirección de entrega
    public $rol; // 'USER', 'BUSINESS' o 'ADMIN'
    public $created_at;
    public $updated_at;

    /**
     * Busca un usuario por email o teléfono.
     */
    public static function findByIdentifier($identificador)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ? OR telefono = ?");
        $stmt->execute([$identificador, $identificador]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new self();
            foreach ($row as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        return null;
    }

    /**
     * Busca un usuario por su dirección de email.
     * Se usa principalmente en el proceso de login.
     *
     * @param  string    $email Email a buscar
     * @return User|null        Objeto User si existe, null si no
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if ($row) {
            // Mapear el array de la BD a un objeto User
            $user = new self();
            foreach ($row as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        return null;
    }

    /**
     * Busca un usuario por su ID numérico.
     * Se usa para recuperar el perfil del usuario autenticado.
     *
     * @param  int       $id ID del usuario
     * @return User|null     Objeto User o null si no existe
     */
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new self();
            foreach ($row as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        return null;
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     * La contraseña se guarda como hash bcrypt (nunca en texto plano).
     *
     * @param  array $data Datos del formulario de registro
     * @return int         ID del nuevo usuario insertado
     */
    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO user (nombre, apellidos, telefono, email, password_hash, rol)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['apellidos'] ?? null,
            $data['telefono'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT), // Generar hash seguro
            $data['rol'] ?? 'USER'
        ]);
        return $db->lastInsertId(); // Devolver el ID generado
    }

    /**
     * Actualiza el perfil del usuario
     */
    public function update($data)
    {
        $db = Database::getInstance()->getConnection();
        $fields = [];
        $params = [];

        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $params[] = $data['nombre'];
        }
        if (isset($data['apellidos'])) {
            $fields[] = "apellidos = ?";
            $params[] = $data['apellidos'];
        }
        if (isset($data['telefono'])) {
            $fields[] = "telefono = ?";
            $params[] = $data['telefono'];
        }
        if (isset($data['direccion'])) {
            $fields[] = "direccion = ?";
            $params[] = $data['direccion'];
        }
        if (isset($data['imagen'])) {
            $fields[] = "imagen = ?";
            $params[] = $data['imagen'];
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
