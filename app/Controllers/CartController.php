<?php

namespace App\Controllers;

// =========================================================
// app/Controllers/CartController.php — Controlador del carrito de compra
// Gestiona todo el ciclo de compra:
//   · Ver el carrito de la sesión
//   · Añadir y eliminar productos
//   · Procesar el pago (checkout) con transacción de BD
//   · Enviar notificaciones por email al cliente y al comercio
// =========================================================

use App\Core\Session;
use App\Core\Database;
use App\Core\Mailer;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use PDO;

class CartController
{
    /**
     * Muestra el carrito de compra del usuario (GET /cart).
     * El carrito se almacena en la sesión como un array indexado por ID de producto.
     */
    public function index()
    {
        // Bloquear acceso al carrito para usuarios con rol BUSINESS
        if (Session::get('user_role') === 'BUSINESS') {
            Session::setFlash('error', 'Los comercios no pueden acceder al carrito.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Debes iniciar sesión para ver tu carrito.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $cart = Session::get('cart', []);

        // Calcular el importe total sumando price × quantity de cada línea (en inglés)
        $total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

        require_once ROOT_DIR . '/resources/views/user/cart.php';
    }

    /**
     * Añade un producto al carrito (POST /cart/add).
     * Comprueba que el producto existe y que hay stock suficiente.
     * Si el producto ya estaba en el carrito, incrementa la cantidad.
     */
    public function add()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
            exit;
        }

        // Bloquear acceso al carrito para comercios
        if (Session::get('user_role') === 'BUSINESS') {
            Session::setFlash('error', 'Los comercios no pueden usar el carrito.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Verificar que el usuario está autenticado
        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Debes iniciar sesión para añadir productos.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['cantidad'] ?? 1)); // Mínimo 1 unidad

        if (!$productId) {
            Session::setFlash('error', 'Datos inválidos.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Verificar que el producto existe y tiene stock
        $product = Product::findById($productId);
        if (!$product || $product->stock < $quantity) {
            Session::setFlash('error', 'Producto no disponible en la cantidad solicitada.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
            exit;
        }

        $cart = Session::get('cart', []);

        // Si ya estaba en el carrito, sumar la cantidad; si no, crear nueva línea mapeada al inglés
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'business_id' => $product->business_id, // Necesario para notificar al comercio
            ];
        }

        Session::set('cart', $cart);
        Session::setFlash('success', '✅ ' . $product->name . ' añadido al carrito.');

        // Volver a la página anterior (ficha del comercio)
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
        exit;
    }

    /**
     * Elimina un producto del carrito (POST /cart/remove).
     */
    public function remove()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        // Bloquear acceso al carrito para comercios
        if (Session::get('user_role') === 'BUSINESS') {
            Session::setFlash('error', 'Los comercios no pueden usar el carrito.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId) {
            $cart = Session::get('cart', []);
            unset($cart[$productId]);
            Session::set('cart', $cart);
            Session::setFlash('success', 'Producto eliminado del carrito.');
        }

        header('Location: ' . BASE_URL . '/cart');
        exit;
    }

    /**
     * Actualiza la cantidad de un producto en el carrito (POST /cart/update).
     * Recibe 'product_id' y 'accion' ('sumar' o 'restar').
     */
    public function update()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $action = $_POST['accion'] ?? '';

        if ($productId && in_array($action, ['sumar', 'restar'])) {
            $cart = Session::get('cart', []);

            if (isset($cart[$productId])) {
                if ($action === 'sumar') {
                    // Verificar stock antes de sumar
                    $product = Product::findById($productId);
                    if ($product && $cart[$productId]['quantity'] < $product->stock) {
                        $cart[$productId]['quantity']++;
                    } else {
                        Session::setFlash('error', 'No hay más stock disponible.');
                    }
                } else {
                    $cart[$productId]['quantity']--;
                    // Si llega a 0, eliminar del carrito
                    if ($cart[$productId]['quantity'] <= 0) {
                        unset($cart[$productId]);
                        Session::setFlash('success', 'Producto eliminado del carrito.');
                    }
                }
                Session::set('cart', $cart);
            }
        }

        header('Location: ' . BASE_URL . '/cart');
        exit;
    }

    /**
     * Vacía completamente el carrito (POST /cart/clear).
     */
    public function clear()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        Session::set('cart', []);
        Session::setFlash('success', 'Carrito vaciado.');
        header('Location: ' . BASE_URL . '/cart');
        exit;
    }

    /**
     * Paso 1: Redirigir a la pantalla de simulación de pago (POST o GET /checkout).
     */
    public function checkout()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $cart = Session::get('cart', []);
        if (empty($cart)) {
            Session::setFlash('error', 'Tu carrito está vacío.');
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        header('Location: ' . BASE_URL . '/checkout/simulation');
        exit;
    }

    /**
     * Paso 2: Mostrar pantalla de simulación de pago.
     */
    public function showSimulation()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $cart = Session::get('cart', []);
        $total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

        require_once ROOT_DIR . '/resources/views/user/checkout_simulation.php';
    }

    /**
     * Paso 3: Confirmar y procesar la compra real en BD utilizando transacciones.
     */
    public function confirmCheckout()
    {
        // Protección CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Petición inválida o token expirado.');
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $cart = Session::get('cart', []);
        if (empty($cart)) {
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

        try {
            $db->beginTransaction();

            // --- Asegurar que existe al menos una dirección (Estructura unificada) ---
            $stmtAddr = $db->query('SELECT id FROM address LIMIT 1');
            $addressId = $stmtAddr->fetchColumn();

            if (!$addressId) {
                // Crear dirección de prueba con nombres unificados en inglés si la tabla migró,
                // o manteniendo compatibilidad si mantienes columnas nativas. Ajustado a nomenclatura limpia:
                $stmtNewAddr = $db->prepare('INSERT INTO address (street, number, zip_code, city, province) VALUES (?, ?, ?, ?, ?)');
                $stmtNewAddr->execute(['Calle Mayor', '1', '28001', 'Madrid', 'Madrid']);
                $addressId = $db->lastInsertId();
            }

            // 1. Crear el registro principal de compra (Mapeado a tablas/columnas en inglés)
            $stmt = $db->prepare('INSERT INTO purchase (user_id, address_id, total, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([Session::get('user_id'), $addressId, $total, 'PENDING']);
            $purchaseId = (int)$db->lastInsertId();

            // Preparar las consultas de líneas de pedido unificadas
            $stmtItem = $db->prepare('INSERT INTO order_item (purchase_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stmtStock = $db->prepare('UPDATE product SET stock = stock - ? WHERE id = ? AND stock >= ?');

            $itemsForEmail = [];
            foreach ($cart as $productId => $item) {
                $stmtStock->execute([$item['quantity'], $productId, $item['quantity']]);
                if ($stmtStock->rowCount() === 0) {
                    throw new \RuntimeException('Stock insuficiente para: ' . $item['name']);
                }

                $stmtItem->execute([$purchaseId, $productId, $item['quantity'], $item['price']]);

                // Array estructurado para los métodos de correo electrónico
                $itemsForEmail[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ];
            }

            $db->commit();
            Session::set('cart', []);

            // Notificaciones por Email (Adaptado al modelo unificado en inglés)
            $userRow = User::findById(Session::get('user_id'));
            if ($userRow) {
                $userArr = ['name' => $userRow->first_name, 'email' => $userRow->email];
                Mailer::sendOrderToClient($userArr, $itemsForEmail, $total, $purchaseId);
            }

            // Agrupar y enviar correos a cada comercio implicado en el carrito
            foreach (array_unique(array_column($cart, 'business_id')) as $bid) {
                $biz = Business::findById($bid);
                if ($biz) {
                    $bizItems = array_filter($cart, fn($item) => $item['business_id'] === $bid);
                    $bizItems = array_map(fn($item) => [
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                    ], $bizItems);

                    $bizTotal = array_sum(array_map(fn($item) => $item['unit_price'] * $item['quantity'], $bizItems));
                    Mailer::sendOrderToBusiness($biz->email, $biz->name, array_values($bizItems), $bizTotal, $purchaseId);
                }
            }

            Session::setFlash('success', "¡Pago realizado con éxito! Tu pedido #{$purchaseId} está en marcha.");
            header('Location: ' . BASE_URL . '/orders');
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::setFlash('error', 'Hubo un problema al procesar el pago: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }
    }
}
