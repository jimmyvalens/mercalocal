<?php

/**
 * =========================================================
 * app/Models/User.php — Modelo de usuario
 *
 * Representa a un usuario registrado, con métodos de consulta y actualización:
 * · Buscar usuario por identificador, email o ID
 * · Crear nuevo registro con contraseña hasheada
 * · Actualizar perfil existente
 * =========================================================
 */

namespace App\Models;

use App\Core\Database;

class User
{
    // Propiedades que corresponden a las columnas de la tabla `user`
    public ?int $id = null;
    public ?string $nombre = null;
    public ?string $apellidos = null;
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $password_hash = null; // Contraseña almacenada como hash bcrypt
    public ?string $imagen = null;
    public ?string $direccion = null; // Dirección de entrega
    public ?string $rol = null; // 'USER', 'BUSINESS' o 'ADMIN'
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Busca un usuario por email o teléfono.
     *
     * @param string $identificador Email o teléfono del usuario
     * @return self|null           Objeto User o null si no existe
     */
    public static function findByIdentifier(string $identificador): ?self
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
     * @param  string $email Email a buscar
     * @return self|null     Objeto User si existe, null si no
     */
    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
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
     * Busca un usuario por su ID numérico.
     * Se usa para recuperar el perfil del usuario autenticado.
     *
     * @param  int       $id ID del usuario
     * @return self|null     Objeto User o null si no existe
     */
    public static function findById(int $id): ?self
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
    public static function create(array $data): int
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
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['rol'] ?? 'USER'
        ]);
        return $db->lastInsertId();
    }

    /**
     * Actualiza el perfil del usuario.
     *
     * @param  array $data Campos del perfil a actualizar
     * @return bool        true si se actualizó correctamente, false si no
     */
    public function update(array $data): bool
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
