<?php
// ── Servidor PHP built-in: servir archivos estáticos directamente ─────────
// Cuando se usa `php -S`, si el archivo solicitado existe en /public,
// devolvemos false para que el servidor lo sirva sin pasar por el router.
if (php_sapi_name() === 'cli-server') {
    $staticFile = __DIR__ . '/' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if (is_file($staticFile)) {
        return false; // Dejar que el servidor PHP lo sirva directamente
    }
}
// =========================================================
// public/index.php — Punto de entrada único de la aplicación
// Toda petición HTTP pasa por este archivo (front controller).
// Se encarga de:
//   · Cargar la configuración global
//   · Registrar el autocargador de clases (PSR-4)
//   · Iniciar la sesión
//   · Registrar todas las rutas de la aplicación
//   · Despachar la petición al controlador correspondiente
// =========================================================

// Configurar modo de desarrollo/producción
define('APP_DEBUG', true); // Cambiar a false en producción

// Cargar configuración de BD, URLs y Email
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/ExceptionHandler.php';

// Registrar manejador de excepciones
\App\Core\ExceptionHandler::register();

// Definir la ruta absoluta a la raíz del proyecto (útil en require_once de vistas)
define('ROOT_DIR', realpath(__DIR__ . '/..'));

// ── Autocargador de clases del namespace App\ ────────────
// Mapea App\Core\Session → src/Core/Session.php automáticamente
// siguiendo la convención de directorios PSR-4.
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

use App\Core\Session;
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\BusinessController;
use App\Controllers\CartController;
use App\Controllers\UserController;
use App\Controllers\BusinessDashboardController;
use App\Controllers\AdminController;
use App\Controllers\ReservationController;

// Iniciar la sesión PHP (una sola vez por petición)
Session::start();

// ── Registro de rutas ─────────────────────────────────────
$router = new Router();

// Página de inicio
$router->get('/', [HomeController::class, 'index']);

// Autenticación
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Catálogo público de comercios
$router->get('/businesses', [BusinessController::class, 'index']);

// ── Rutas específicas de /business/* ANTES del patrón dinámico /business/{id} ──
// Si se registrasen después, el router capturaría "setup" y "dashboard"
// como si fueran IDs de negocio y llamaría a BusinessController::detail().

// Asistente de configuración inicial para nuevos comercios
$router->get('/business/setup', [BusinessDashboardController::class, 'setup']);
$router->post('/business/setup', [BusinessDashboardController::class, 'saveSetup']);

// Paneles privados (comercio y administrador)
$router->get('/business/dashboard', [BusinessDashboardController::class, 'index']);
$router->get('/admin/dashboard', [AdminController::class, 'index']);

// ── Rutas de Admin: gestión de comercios ──────────────────
$router->get('/admin/businesses', [AdminController::class, 'businesses']);
$router->get('/admin/business/create', [AdminController::class, 'create']);
$router->post('/admin/business/store', [AdminController::class, 'store']);
$router->get('/admin/business/{id}/edit', [AdminController::class, 'edit']);
$router->post('/admin/business/{id}/update', [AdminController::class, 'update']);
$router->post('/admin/business/{id}/delete', [AdminController::class, 'delete']);
$router->get('/admin/business/{id}', [AdminController::class, 'businessDetail']);

// Productos (CRUD) dentro del panel de comercio
$router->get('/business/dashboard/products', [BusinessDashboardController::class, 'productsIndex']);

// Pedidos recibidos por el comercio
$router->get('/business/dashboard/orders', [BusinessDashboardController::class, 'orders']);
$router->get('/business/dashboard/products/create', [BusinessDashboardController::class, 'productsCreate']);
$router->post('/business/dashboard/products/store', [BusinessDashboardController::class, 'productsStore']);
$router->get('/business/dashboard/products/{id}/edit', [BusinessDashboardController::class, 'productsEdit']);
$router->post('/business/dashboard/products/{id}/update', [BusinessDashboardController::class, 'productsUpdate']);
$router->post('/business/dashboard/products/{id}/delete', [BusinessDashboardController::class, 'productsDelete']);

// Servicios (CRUD) dentro del panel de comercio
$router->get('/business/dashboard/services', [BusinessDashboardController::class, 'servicesIndex']);
$router->get('/business/dashboard/services/create', [BusinessDashboardController::class, 'servicesCreate']);
$router->post('/business/dashboard/services/store', [BusinessDashboardController::class, 'servicesStore']);
$router->get('/business/dashboard/services/{id}/edit', [BusinessDashboardController::class, 'servicesEdit']);
$router->post('/business/dashboard/services/{id}/update', [BusinessDashboardController::class, 'servicesUpdate']);
$router->post('/business/dashboard/services/{id}/delete', [BusinessDashboardController::class, 'servicesDelete']);

// Horarios (CRUD simplificado)
$router->get('/business/dashboard/schedules', [BusinessDashboardController::class, 'schedulesIndex']);
$router->post('/business/dashboard/schedules/store', [BusinessDashboardController::class, 'schedulesStore']);
$router->post('/business/dashboard/schedules/{id}/delete', [BusinessDashboardController::class, 'schedulesDelete']);

// Configuración del perfil del comercio
$router->get('/business/dashboard/settings', [BusinessDashboardController::class, 'settings']);
$router->post('/business/dashboard/settings/update', [BusinessDashboardController::class, 'updateSettings']);

// Sistema de reservas de citas
$router->get('/business/{id}/reserve', [ReservationController::class, 'showForm']);
$router->post('/reserve', [ReservationController::class, 'store']);

// Ruta genérica de detalle de comercio (debe ir DESPUÉS de las rutas fijas)
$router->get('/business/{id}', [BusinessController::class, 'detail']);

// Carrito de compra y proceso de pago
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/clear', [CartController::class, 'clear']);
$router->post('/checkout', [CartController::class, 'checkout']);
$router->get('/checkout/simulation', [CartController::class, 'showSimulation']);
$router->post('/checkout/confirm', [CartController::class, 'confirmCheckout']);

// Área privada del usuario (dashboard, perfil e historial)
$router->get('/user/dashboard', [UserController::class, 'dashboard']);
$router->get('/profile', [UserController::class, 'profile']);
$router->post('/user/profile/update', [UserController::class, 'updateProfile']);
$router->get('/orders', [UserController::class, 'orders']);

// ── Rutas de Depuración (Visualización de Vistas) ──────────
// Estas rutas permiten previsualizar los diseños sin base de datos real
$router->get('/debug/admin', function () {
    $stats = ['users' => 1250, 'businesses' => 45, 'sales' => 15750.50];
    require_once ROOT_DIR . '/resources/views/admin/dashboard.php';
});
$router->get('/debug/business', function () {
    $stats = ['products' => 12, 'services' => 5, 'reservations' => 8];
    $recentOrders = [
        ['id' => 101, 'client_name' => 'Juan Perez', 'total' => 45.50, 'estado' => 'pendiente', 'created_at' => date('Y-m-d')],
        ['id' => 102, 'client_name' => 'Maria Garcia', 'total' => 22.00, 'estado' => 'completado', 'created_at' => date('Y-m-d')]
    ];
    $upcomingReservations = [];
    require_once ROOT_DIR . '/resources/views/business/dashboard.php';
});
$router->get('/debug/user/dashboard', function () {
    $user = (object)['id' => 99, 'nombre' => 'Usuario Demo', 'apellidos' => '', 'email' => 'demo@mercalocal.es', 'rol' => 'user'];
    $recentActivity = [
        ['title' => 'Pedido #1042 Completado', 'description' => 'Hace 2 días en Frutería La Fresca', 'time' => '01-03', 'icon' => 'fa-check', 'icon_bg' => 'bg-green-100', 'icon_color' => 'text-green-600'],
        ['title' => 'Cuenta creada con éxito', 'description' => 'Bienvenido a Mercalocal', 'time' => '01-02', 'icon' => 'fa-user-check', 'icon_bg' => 'bg-blue-100', 'icon_color' => 'text-blue-600']
    ];
    require_once ROOT_DIR . '/resources/views/user/dashboard.php';
});
$router->get('/debug/profile', function () {
    $user = (object)['id' => 99, 'nombre' => 'Usuario', 'apellidos' => 'Demo', 'email' => 'demo@mercalocal.es', 'telefono' => '600000000', 'rol' => 'user'];
    require_once ROOT_DIR . '/resources/views/user/profile.php';
});
$router->get('/debug/orders', function () {
    $orders = [
        ['id' => 1, 'total' => 25.00, 'estado' => 'completado', 'created_at' => '2026-05-01', 'business_name' => 'Frutería Paco'],
        ['id' => 2, 'total' => 15.50, 'estado' => 'pendiente', 'created_at' => '2026-05-02', 'business_name' => 'Carnicería Selecta']
    ];
    require_once ROOT_DIR . '/resources/views/user/orders.php';
});
$router->get('/debug/products', function () {
    $products = [
        (object)['id' => 1, 'nombre' => 'Producto 1', 'descripcion' => 'Descripción corta', 'precio' => 10.50, 'stock' => 20],
        (object)['id' => 2, 'nombre' => 'Producto 2', 'descripcion' => 'Otra descripción', 'precio' => 5.00, 'stock' => 0]
    ];
    require_once ROOT_DIR . '/resources/views/business/products/index.php';
});
$router->get('/debug/products/form', function () {
    $cats = [['id' => 1, 'nombre' => 'Alimentación']];
    require_once ROOT_DIR . '/resources/views/business/products/form.php';
});
$router->get('/debug/settings', function () {
    $business = ['nombre' => 'Mi Comercio', 'descripcion' => 'Descripción del negocio', 'telefono' => '912345678', 'email' => 'comercio@test.com', 'web' => ''];
    require_once ROOT_DIR . '/resources/views/business/settings.php';
});

// API REST
$router->get('/api/businesses', [BusinessController::class, 'apiIndex']);
$router->get('/api/business/{id}', [BusinessController::class, 'apiDetail']);
$router->get('/api/notifications', [UserController::class, 'apiNotifications']);

// ── Despachar la petición ─────────────────────────────────
// El router analiza el método HTTP y la URL para ejecutar
// el controlador y método apropiados.
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$router->dispatch($method, $uri);
