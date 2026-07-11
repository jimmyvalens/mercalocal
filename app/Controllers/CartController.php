<?php

/**
 * =========================================================
 * app/Controllers/CartController.php — Controlador de carrito
 *
 * Gestiona el carrito de compras y la finalización de pedidos:
 * · Añade, actualiza y elimina productos en sesión
 * · Simula el flujo de pago antes de confirmar la compra
 * · Registra el pedido y actualiza el stock en la base de datos
 * =========================================================
 */

namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\Product;

class CartController
{
    /**
     * Muestra el carrito de compras.
     *
     * @return void
     */
    public function index()
    {
        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Debes iniciar sesión para ver tu carrito.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $cart = Session::get('cart', []);
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        require_once ROOT_DIR . '/resources/views/user/cart.php';
    }

    /**
     * Añade un producto o incrementa su cantidad en la sesión.
     *
     * @return void
     */
    public function add()
    {
        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Debes iniciar sesión para añadir productos.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

        if (!$productId) {
            Session::setFlash('error', 'Datos inválidos.');
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $product = Product::findById($productId);
        if (!$product || $product->stock < $cantidad) {
            Session::setFlash('error', 'Producto no disponible en la cantidad solicitada.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
            exit;
        }

        // Evitar que un comercio compre sus propios productos
        // Comprobamos si el usuario tiene un comercio asignado en su sesión y si coincide con el del producto
        $miComercioId = Session::get('business_id');
        if ($miComercioId && (int)$miComercioId === (int)$product->business_id) {
            Session::setFlash('error', 'No puedes añadir al carrito productos de tu propio negocio.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
            exit;
        }

        $cart = Session::get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['cantidad'] += $cantidad;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'nombre' => $product->nombre,
                'precio' => $product->precio,
                'cantidad' => $cantidad,
                'business_id' => $product->business_id,
                'imagen' => $product->imagen
            ];
        }

        Session::set('cart', $cart);
        Session::setFlash('success', '✅ ' . $product->nombre . ' añadido al carrito.');

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/'));
        exit;
    }

    /**
     * Elimina un artículo específico del carrito.
     *
     * @return void
     */
    public function remove()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
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
     * Actualiza la cantidad de un producto en el carrito.
     *
     * @return void
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
                    $product = Product::findById($productId);
                    if ($product && $cart[$productId]['cantidad'] < $product->stock) {
                        $cart[$productId]['cantidad']++;
                    } else {
                        Session::setFlash('error', 'No hay más stock disponible.');
                    }
                } elseif ($accion === 'restar') {
                    $cart[$productId]['cantidad']--;
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
     * Limpia el carrito almacenado en sesión.
     *
     * @return void
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
     * Inicia el flujo de checkout y almacena el método de entrega.
     *
     * @return void
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

        $deliveryMethod = $_POST['delivery_method'] ?? 'domicilio';
        Session::set('delivery_method', $deliveryMethod);

        header('Location: ' . BASE_URL . '/checkout/simulation');
        exit;
    }

    /**
     * Renderiza la pasarela de pago simulada.
     *
     * @return void
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
     * Consolida el pedido en la base de datos y resta el stock.
     *
     * @throws \RuntimeException
     * @return void
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

        $deliveryMethod = Session::get('delivery_method', 'domicilio');

        $db = Database::getInstance()->getConnection();
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        try {
            $db->beginTransaction();

            $userRow = User::findById(Session::get('user_id'));
            $direccionTexto = $userRow->direccion ?? 'Dirección no especificada';

            $stmtNewAddr = $db->prepare('INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)');
            $stmtNewAddr->execute([$direccionTexto, '-', '-', 'Villafranca de los Barros', 'Badajoz']);
            $addressId = $db->lastInsertId();

            $stmt = $db->prepare('INSERT INTO purchase (user_id, address_id, total, estado, delivery_method) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([Session::get('user_id'), $addressId, $total, 'PENDIENTE', $deliveryMethod]);
            $purchaseId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare('INSERT INTO order_item (purchase_id, product_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $stmtStock = $db->prepare('UPDATE product SET stock = stock - ? WHERE id = ? AND stock >= ?');

            $itemsForEmail = [];
            foreach ($cart as $productId => $item) {
                $stmtStock->execute([$item['cantidad'], $productId, $item['cantidad']]);
                if ($stmtStock->rowCount() === 0) {
                    throw new \RuntimeException('Stock insuficiente para el producto: ' . $item['nombre']);
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
