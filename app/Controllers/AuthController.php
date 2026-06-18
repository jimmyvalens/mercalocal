<?php
// =========================================================
// src/Controllers/AuthController.php — Controlador de autenticación
// Gestiona el flujo completo de identidad del usuario:
//   · Mostrar el formulario de login
//   · Procesar el inicio de sesión
//   · Mostrar el formulario de registro
//   · Crear nuevas cuentas
//   · Cerrar sesión
// =========================================================
namespace App\Controllers;

use App\Core\Session;
use App\Core\Mailer;
use App\Models\User;
use App\Core\Database;

class AuthController
{
    /**
     * Muestra el formulario de inicio de sesión.
     * Si el usuario ya está autenticado, lo redirige al inicio.
     */
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard de su rol
        if (Session::get('user_id')) {
            $role = Session::get('user_role');
            if ($role === 'ADMIN') {
                header('Location: ' . BASE_URL . '/admin/dashboard');
            } elseif ($role === 'BUSINESS') {
                header('Location: ' . BASE_URL . '/business/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/user/dashboard');
            }
            exit;
        }
        require_once ROOT_DIR . '/resources/views/auth/login.php';
    }

    /**
     * Procesa el formulario de login (POST /login).
     * Verifica credenciales y establece la sesión del usuario.
     * Los comercios sin perfil creado se redirigen al asistente de configuración.
     */
    public function login()
    {
        // Detectar si es una solicitud AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Rate limiting: máximo 5 intentos por IP en 15 minutos
        $ip = $_SERVER['REMOTE_ADDR'];
        $attemptsKey = 'login_attempts_' . $ip;
        $timeKey = 'login_time_' . $ip;
        $currentTime = time();
        $attempts = Session::get($attemptsKey, 0);
        $lastAttempt = Session::get($timeKey, 0);

        if ($currentTime - $lastAttempt < 900) { // 15 minutos
            if ($attempts >= 5) {
                Session::setFlash('error', 'Demasiados intentos de login. Intenta de nuevo en 15 minutos.');
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        } else {
            $attempts = 0; // Reset after 15 min
        }

        // Protegemos contra CSRF en todos los envíos POST
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida. Intenta de nuevo.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $identificador = $_POST['identificador'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validación centralizada
        $validator = new \App\Core\Validator($_POST);
        $validator->required('identificador', 'El identificador es obligatorio.')
            ->required('password', 'La contraseña es obligatoria.')
            ->minLength('password', 6, 'La contraseña debe tener al menos 6 caracteres.');

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            Session::setFlash('error', implode(' ', $errors));
            // Incrementar intentos
            Session::set($attemptsKey, $attempts + 1);
            Session::set($timeKey, $currentTime);
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Buscar el usuario por email o teléfono y verificar la contraseña
        $user = User::findByIdentifier($identificador);
        if ($user && password_verify($password, $user->password_hash)) {
            Session::regenerate();
            // Reset attempts on success
            Session::set($attemptsKey, 0);
            // Guardar datos del usuario en la sesión
            Session::set('user_id', $user->id);
            Session::set('user_role', $user->rol);
            Session::set('user_name', $user->nombre);
            Session::set('user_email', $user->email);

            // Importante: No cerramos la sesión forzosamente si es AJAX porque queremos que Session Fixation ande fluido
            if ($isAjax) {
                $redirectUrl = BASE_URL . '/';
                if ($user->rol === 'BUSINESS') {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
                    $stmt->execute([$user->id]);
                    if (!$stmt->fetch()) {
                        Session::setFlash('info', 'Completa el perfil de tu comercio para comenzar.');
                        $redirectUrl = BASE_URL . '/business/setup';
                    } else {
                        $redirectUrl = BASE_URL . '/business/dashboard';
                    }
                } elseif ($user->rol === 'ADMIN') {
                    $redirectUrl = BASE_URL . '/admin/dashboard';
                }

                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                exit;
            }

            // Si es un comercio, comprobar si ya ha completado su perfil
            if ($user->rol === 'BUSINESS') {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user->id]);
                if (!$stmt->fetch()) {
                    // Todavía no tiene perfil de comercio → redirigir al asistente
                    Session::setFlash('info', 'Completa el perfil de tu comercio para comenzar.');
                    session_write_close();
                    header('Location: ' . BASE_URL . '/business/setup');
                    exit;
                }
                session_write_close();
                header('Location: ' . BASE_URL . '/business/dashboard');
                exit;
            }

            // Si es admin → su panel
            if ($user->rol === 'ADMIN') {
                session_write_close();
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            }

            Session::setFlash('success', '¡Bienvenido de nuevo, ' . htmlspecialchars($user->nombre) . '!');
            session_write_close();
            header('Location: ' . BASE_URL . '/user/dashboard');
            exit;
        }

        // Credenciales incorrectas - incrementar intentos
        Session::set($attemptsKey, $attempts + 1);
        Session::set($timeKey, $currentTime);
        Session::setFlash('error', 'Credenciales incorrectas. Revisa tu email y contraseña.');
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /**
     * Muestra el formulario de registro de nuevos usuarios.
     * Si el usuario ya está autenticado, lo redirige al inicio.
     */
    public function showRegister()
    {
        if (Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        require_once ROOT_DIR . '/resources/views/auth/register.php';
    }

    /**
     * Procesa el formulario de registro (POST /register).
     * Valida cada campo de forma individualizada y devuelve los valores
     * introducidos + errores a la vista para no perder lo que el usuario escribió.
     *
     * Validaciones aplicadas:
     *   - nombre / apellidos : mín 3 caracteres y al menos una vocal
     *   - email              : formato válido
     *   - teléfono           : exactamente 9 dígitos
     *   - password           : mín 8 caracteres
     */
    public function register()
    {
        // comprobar token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida. Intenta de nuevo.');
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        // Recoger y sanear los datos del formulario
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'identificador' => trim($_POST['identificador'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'rol' => in_array($_POST['rol'] ?? '', ['USER', 'BUSINESS']) ? $_POST['rol'] : 'USER',
        ];

        $errors = []; // Errores individuales por campo

        // ── Validar nombre ──────────────────────────────────────────────
        if (strlen($data['nombre']) < 3) {
            $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
        } elseif (!preg_match('/[aeiouáéíóúAEIOUÁÉÍÓÚ]/u', $data['nombre'])) {
            $errors['nombre'] = 'El nombre debe contener al menos una vocal.';
        }

        // ── Validar apellidos (opcional pero si se rellena, mismas reglas) ──
        if (!empty($data['apellidos'])) {
            if (strlen($data['apellidos']) < 3) {
                $errors['apellidos'] = 'Los apellidos deben tener al menos 3 caracteres.';
            } elseif (!preg_match('/[aeiouáéíóúAEIOUÁÉÍÓÚ]/u', $data['apellidos'])) {
                $errors['apellidos'] = 'Los apellidos deben contener al menos una vocal.';
            }
        }

        // ── Validar Identificador (Email o Teléfono) ────────────────────
        $identificador = $data['identificador'];
        $data['email'] = null;
        $data['telefono'] = null;

        if (empty($identificador)) {
            $errors['identificador'] = 'El email o teléfono es obligatorio.';
        } elseif (filter_var($identificador, FILTER_VALIDATE_EMAIL)) {
            $data['email'] = $identificador;
            if (User::findByIdentifier($identificador)) {
                $errors['identificador'] = 'Este email ya está registrado. ¿Quieres iniciar sesión?';
            }
        } else {
            $telefonoLimpio = preg_replace('/\s+/', '', $identificador);
            if (!preg_match('/^\d{9}$/', $telefonoLimpio)) {
                $errors['identificador'] = 'Ingresa un email válido o un teléfono de 9 dígitos.';
            } else {
                $data['telefono'] = $telefonoLimpio;
                if (User::findByIdentifier($telefonoLimpio)) {
                    $errors['identificador'] = 'Este teléfono ya está registrado. ¿Quieres iniciar sesión?';
                }
            }
        }

        // ── Validar contraseña ──────────────────────────────────────────
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es obligatoria.';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        // ── Si hay errores, devolver al formulario con los valores y errores ──
        if (!empty($errors)) {
            // Guardar los valores válidos en sesión para repoblar el formulario
            // La contraseña nunca se devuelve por seguridad
            Session::set('register_old', [
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'identificador' => $data['identificador'],
                'rol' => $data['rol'],
            ]);
            Session::set('register_errors', $errors);
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        // ── Sin errores: crear usuario ──────────────────────────────────
        try {
            $userId = User::create($data);
            Session::regenerate();
            Session::set('user_id', $userId);
            Session::set('user_role', $data['rol']);
            Session::set('user_name', $data['nombre']);
            Session::set('user_email', $data['email']);

            // Limpiar los datos temporales del formulario de la sesión
            Session::remove('register_old');
            Session::remove('register_errors');

            // Enviar email de bienvenida (no bloquea si MAIL_ENABLED = false o si falla)
            Mailer::sendWelcome($data['nombre'], $data['email'], $data['rol']);

            // Redirigir según el rol elegido
            if ($data['rol'] === 'BUSINESS') {
                Session::setFlash('info', 'Cuenta creada. Ahora completa el perfil de tu comercio.');
                header('Location: ' . BASE_URL . '/business/setup');
            } else {
                Session::setFlash('success', '¡Bienvenido a Mercalocal, ' . $data['nombre'] . '!');
                header('Location: ' . BASE_URL . '/user/dashboard');
            }
            exit;
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error inesperado al crear la cuenta: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
    }

    /**
     * Cierra la sesión del usuario y lo redirige al inicio.
     */
    public function logout()
    {
        Session::destroy();
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}
