<?php
namespace App\Controllers;

// =========================================================
// src/Controllers/CartController.php — Controlador del carrito de compra
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
        // Calcular el importe total sumando precio × cantidad de cada línea
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        require_once ROOT_DIR . '/resources/views/user/cart.php';
    }

    /**
     * Añade un producto al carrito (POST /cart/add).
     * Comprueba que el producto existe y que hay stock suficiente.
     * Si el producto ya estaba en el carrito, incrementa la cantidad.
     */
    public function add()
    {

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
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1)); // Mínimo 1 unidad

        if (!$productId) {
            Session::setFlash('error', 'Datos inválidos.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Verificar que el producto existe y tiene stock
        $product = Product::findById($productId);
        if (!$product || $product->stock < $cantidad) {
            Session::setFlash('error', 'Producto no disponible en la cantidad solicitada.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
            exit;
        }

        $cart = Session::get('cart', []);

        // Si ya estaba en el carrito, sumar la cantidad; si no, crear nueva línea
        if (isset($cart[$productId])) {
            $cart[$productId]['cantidad'] += $cantidad;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'nombre' => $product->nombre,
                'precio' => $product->precio,
                'cantidad' => $cantidad,
                'business_id' => $product->business_id, // Necesario para notificar al comercio
            ];
        }

        Session::set('cart', $cart);
        Session::setFlash('success', '✅ ' . $product->nombre . ' añadido al carrito.');

        // Volver a la página anterior (ficha del comercio)
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
        exit;
    }

    /**
     * Elimina un producto del carrito (POST /cart/remove).
     * La vista envía el campo con name="product_id".
     */
    public function remove()
    {
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
     * Si la cantidad llega a 0 o menos, elimina el producto del carrito.
     */
    public function update()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $accion = $_POST['accion'] ?? '';

        if ($productId && in_array($accion, ['sumar', 'restar'])) {
            $cart = Session::get('cart', []);

            if (isset($cart[$productId])) {
                if ($accion === 'sumar') {
                    // Verificar stock antes de sumar
                    $product = Product::findById($productId);
                    if ($product && $cart[$productId]['cantidad'] < $product->stock) {
                        $cart[$productId]['cantidad']++;
                    } else {
                        Session::setFlash('error', 'No hay más stock disponible.');
                    }
                } else {
                    $cart[$productId]['cantidad']--;
                    // Si llega a 0, eliminar del carrito
                    if ($cart[$productId]['cantidad'] <= 0) {
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
     * Procesa el pago del carrito (POST /checkout).
     * Ejecuta las siguientes operaciones en una transacción de BD:
     *   1. Crea el registro de compra (tabla `purchase`)
     *   2. Inserta cada línea del pedido (tabla `order_item`)
     *   3. Descuenta el stock de cada producto (tabla `product`)
     * Si cualquier operación falla, hace rollback para mantener la consistencia.
     * Tras el éxito, envía emails de confirmación al cliente y a cada comercio.
     */
    /**
     * Paso 1: Redirigir a la pantalla de simulación de pago.
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
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        require_once ROOT_DIR . '/resources/views/user/checkout_simulation.php';
    }

    /**
     * Paso 3: Confirmar y procesar la compra real en BD.
     */
    public function confirmCheckout()
    {
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
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        try {
            $db->beginTransaction();

            // --- FIX address_id: Asegurar que existe al menos una dirección ---
            // En una app real esto vendría de un formulario o del perfil del usuario.
            $stmtAddr = $db->query('SELECT id FROM address LIMIT 1');
            $addressId = $stmtAddr->fetchColumn();

            if (!$addressId) {
                // Crear una dirección de prueba si no hay ninguna en el sistema
                $stmtNewAddr = $db->prepare('INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)');
                $stmtNewAddr->execute(['Calle Mayor', '1', '28001', 'Madrid', 'Madrid']);
                $addressId = $db->lastInsertId();
            }

            // 1. Crear el registro principal de compra (ahora con address_id)
            $stmt = $db->prepare('INSERT INTO purchase (user_id, address_id, total, estado) VALUES (?, ?, ?, ?)');
            $stmt->execute([Session::get('user_id'), $addressId, $total, 'PENDIENTE']);
            $purchaseId = (int)$db->lastInsertId();

            // Preparar las líneas del pedido
            $stmtItem = $db->prepare('INSERT INTO order_item (purchase_id, product_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $stmtStock = $db->prepare('UPDATE product SET stock = stock - ? WHERE id = ? AND stock >= ?');

            $itemsForEmail = [];
            foreach ($cart as $productId => $item) {
                $stmtStock->execute([$item['cantidad'], $productId, $item['cantidad']]);
                if ($stmtStock->rowCount() === 0) {
                    throw new \RuntimeException('Stock insuficiente para: ' . $item['nombre']);
                }
                $stmtItem->execute([$purchaseId, $productId, $item['cantidad'], $item['precio']]);
                $itemsForEmail[] = [
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                ];
            }

            $db->commit();
            Session::set('cart', []);

            // Notificaciones por Email
            $userRow = User::findById(Session::get('user_id'));
            if ($userRow) {
                $userArr = ['nombre' => $userRow->nombre, 'email' => $userRow->email];
                Mailer::sendOrderToClient($userArr, $itemsForEmail, $total, $purchaseId);
            }

            foreach (array_unique(array_column($cart, 'business_id')) as $bid) {
                $biz = Business::findById($bid);
                if ($biz) {
                    $bizItems = array_filter($cart, fn($i) => $i['business_id'] === $bid);
                    $bizItems = array_map(fn($i) => [
                        'nombre' => $i['nombre'],
                        'cantidad' => $i['cantidad'],
                        'precio_unitario' => $i['precio'],
                    ], $bizItems);
                    $bizTotal = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $bizItems));
                    Mailer::sendOrderToBusiness($biz->email, $biz->nombre, array_values($bizItems), $bizTotal, $purchaseId);
                }
            }

            Session::setFlash('success', "¡Pago realizado con éxito! Tu pedido #{$purchaseId} está en marcha.");
            header('Location: ' . BASE_URL . '/orders');
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction())
                $db->rollBack();
            Session::setFlash('error', 'Hubo un problema al procesar el pago: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/cart');
            exit;
        }
    }
}
