<?php
// =========================================================
// app/Controllers/AuthController.php — Controlador de autenticación
// Gestiona el flujo completo de identidad del usuario:
//   · Mostrar el formulario de login
//   · Procesar el inicio de sesión con protección anti-fijación
//   · Mostrar el formulario de registro
//   · Crear nuevas cuentas normalizadas
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
     * Verifica credenciales, previene fijación de sesión y establece la identidad.
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
            $attempts = 0; // Reset tras 15 minutos
        }

        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida. Intenta de nuevo.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $identificador = trim($_POST['identificador'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validación centralizada
        $validator = new \App\Core\Validator($_POST);
        $validator->required('identificador', 'El email o teléfono es obligatorio.')
            ->required('password', 'La contraseña es obligatoria.')
            ->minLength('password', 6, 'La contraseña debe tener al menos 6 caracteres.');

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            Session::setFlash('error', implode(' ', $errors));
            Session::set($attemptsKey, $attempts + 1);
            Session::set($timeKey, $currentTime);
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Buscar el usuario por identificador (Email o Teléfono en BD)
        $user = User::findByIdentifier($identificador);
        if ($user && password_verify($password, $user->password_hash)) {

            // Resetear intentos de Login fallidos
            Session::set($attemptsKey, 0);

            // MITIGACIÓN CRÍTICA: Regenerar ID de sesión para prevenir Session Fixation
            Session::regenerateId(true);

            // Guardar datos del usuario alineados con el modelo en inglés (Acepta email nullable)
            Session::set('user_id', $user->id);
            Session::set('user_role', $user->role);
            Session::set('user_name', $user->first_name);
            Session::set('user_email', $user->email); // Puede ser null de forma segura

            if ($isAjax) {
                $redirectUrl = BASE_URL . '/';
                if ($user->role === 'BUSINESS') {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
                    $stmt->execute([$user->id]);
                    if (!$stmt->fetch()) {
                        Session::setFlash('info', 'Completa el perfil de tu comercio para comenzar.');
                        $redirectUrl = BASE_URL . '/business/setup';
                    } else {
                        $redirectUrl = BASE_URL . '/business/dashboard';
                    }
                } elseif ($user->role === 'ADMIN') {
                    $redirectUrl = BASE_URL . '/admin/dashboard';
                }

                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                exit;
            }

            // Flujo de redirección síncrona según el rol del usuario
            if ($user->role === 'BUSINESS') {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare('SELECT id FROM business WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user->id]);
                if (!$stmt->fetch()) {
                    Session::setFlash('info', 'Completa el perfil de tu comercio para comenzar.');
                    session_write_close();
                    header('Location: ' . BASE_URL . '/business/setup');
                    exit;
                }
                session_write_close();
                header('Location: ' . BASE_URL . '/business/dashboard');
                exit;
            }

            if ($user->role === 'ADMIN') {
                session_write_close();
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            }

            Session::setFlash('success', '¡Bienvenido de nuevo, ' . htmlspecialchars($user->first_name) . '!');
            session_write_close();
            header('Location: ' . BASE_URL . '/user/dashboard');
            exit;
        }

        // Credenciales incorrectas
        Session::set($attemptsKey, $attempts + 1);
        Session::set($timeKey, $currentTime);
        Session::setFlash('error', 'Credenciales incorrectas. Revisa tus datos de acceso.');
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /**
     * Muestra el formulario de registro de nuevos usuarios.
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
     */
    public function register()
    {
        // Comprobar token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida. Intenta de nuevo.');
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        // Recoger y sanear datos estructurándolos para el modelo User en inglés
        $data = [
            'first_name' => trim($_POST['nombre'] ?? ''),
            'last_name' => trim($_POST['apellidos'] ?? ''),
            'identificador' => trim($_POST['identificador'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => in_array($_POST['rol'] ?? '', ['USER', 'BUSINESS']) ? $_POST['rol'] : 'USER',
        ];

        $errors = [];

        // ── Validar primer nombre ──────────────────────────────────────────
        if (strlen($data['first_name']) < 3) {
            $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
        } elseif (!preg_match('/[aeiouáéíóúAEIOUÁÉÍÓÚ]/u', $data['first_name'])) {
            $errors['nombre'] = 'El nombre debe contener al menos una vocal.';
        }

        // ── Validar apellidos ──────────────────────────────────────────────
        if (!empty($data['last_name'])) {
            if (strlen($data['last_name']) < 3) {
                $errors['apellidos'] = 'Los apellidos deben tener al menos 3 caracteres.';
            } elseif (!preg_match('/[aeiouáéíóúAEIOUÁÉÍÓÚ]/u', $data['last_name'])) {
                $errors['apellidos'] = 'Los apellidos deben contener al menos una vocal.';
            }
        }

        // ── Validar Identificador (Email o Teléfono) ────────────────────
        $identificador = $data['identificador'];
        $data['email'] = null;
        $data['phone'] = null;

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
                $data['phone'] = $telefonoLimpio;
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

        // Si hay fallos de validación, rellenar datos temporales para la vista
        if (!empty($errors)) {
            Session::set('register_old', [
                'nombre' => $data['first_name'],
                'apellidos' => $data['last_name'],
                'identificador' => $identificador,
                'rol' => $data['role'],
            ]);
            Session::set('register_errors', $errors);
            header('Location: ' . BASE_URL . '/register');
            exit;
        }

        // Guardar registro en el sistema
        try {
            $userId = User::create($data);

            // MITIGACIÓN CRÍTICA: Regenerar ID de sesión tras alta exitosa
            Session::regenerateId(true);

            Session::set('user_id', $userId);
            Session::set('user_role', $data['role']);
            Session::set('user_name', $data['first_name']);
            Session::set('user_email', $data['email']); // Almacena null si se registró con teléfono

            Session::remove('register_old');
            Session::remove('register_errors');

            // CONTROL CRÍTICO: Enviar correo de bienvenida SOLO si el usuario suministró un email
            if (!empty($data['email'])) {
                Mailer::sendWelcome($data['first_name'], $data['email'], $data['role']);
            }

            if ($data['role'] === 'BUSINESS') {
                Session::setFlash('info', 'Cuenta creada. Ahora completa el perfil de tu comercio.');
                header('Location: ' . BASE_URL . '/business/setup');
            } else {
                Session::setFlash('success', '¡Bienvenido a Mercalocal, ' . $data['first_name'] . '!');
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
     * Cierra la sesión del usuario y limpia la memoria de la cookie de sesión.
     */
    public function logout()
    {
        Session::destroy();
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}
