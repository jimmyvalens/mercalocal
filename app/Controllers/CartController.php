<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\Product;

/**
 * Controlador del Carrito de Compras
 * * Gestiona de forma centralizada el ciclo de vida de una orden:
 * desde la persistencia temporal en sesión hasta la consolidación
 * transaccional en la base de datos.
 */
class CartController
{
    /**
     * Muestra el estado actual del carrito de compra (GET /cart).
     * * @return void
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
     * Añade un producto o incrementa su cantidad en la sesión (POST /cart/add).
     * * Valida la existencia del producto y la disponibilidad de stock físico
     * antes de alterar la estructura del carrito.
     * * @return void
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
     * Elimina un artículo específico del carrito (POST /cart/remove).
     * * @return void
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
     * Modifica de forma incremental o decremental las unidades de un ítem (POST /cart/update).
     * * Realiza un control estricto de fluctuación de stock en tiempo real.
     * Si las unidades descienden a cero, el producto se remueve automáticamente.
     * * @return void
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
                } {
                    if ($accion === 'restar') {
                        $cart[$productId]['cantidad']--;
                        if ($cart[$productId]['cantidad'] <= 0) {
                            unset($cart[$productId]);
                            Session::setFlash('success', 'Producto eliminado del carrito.');
                        }
                    }
                }
                Session::set('cart', $cart);
            }
        }

        header('Location: ' . BASE_URL . '/cart');
        exit;
    }

    /**
     * Limpia por completo la estructura del carrito en la sesión (POST /cart/clear).
     * * @return void
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
     * Redirige al flujo intermedio de simulación de pasarela de pago (POST /checkout).
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

        // 🌟 LA CORRECCIÓN AQUÍ: Capturamos el POST del carrito ANTES de la redirección
        $deliveryMethod = $_POST['delivery_method'] ?? 'domicilio';
        Session::set('delivery_method', $deliveryMethod);

        header('Location: ' . BASE_URL . '/checkout/simulation');
        exit;
    }

    /**
     * Renderiza la interfaz visual de la pasarela de pago simulada.
     * @return void
     */
    public function showSimulation()
    {
        if (!Session::get('user_id')) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // 🌟 CORRECCIÓN AQUÍ: Eliminamos la línea del $_POST para no sobreescribir la sesión.
        // El dato ya está guardado de forma segura en la sesión gracias al método checkout().

        $cart = Session::get('cart', []);
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        require_once ROOT_DIR . '/resources/views/user/checkout_simulation.php';
    }

    /**
     * Consolida el pedido de forma definitiva en la base de datos (POST /checkout/confirm).
     * * Opera bajo una transacción ACID estricta que asegura:
     * 1. Persistencia de la dirección física del comprador.
     * 2. Inserción de la cabecera de la compra (`purchase`).
     * 3. Desglose detallado de las líneas del pedido (`order_item`).
     * 4. Sustracción del stock remanente con control de concurrencia.
     * * @throws \RuntimeException Si se detecta una ruptura de stock durante el procesamiento.
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

        // 🌟 CAPTURAMOS EL MÉTODO DE ENTREGA SELECCIONADO POR EL USUARIO
        $deliveryMethod = Session::get('delivery_method', 'domicilio');

        $db = Database::getInstance()->getConnection();
        $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart));

        try {
            $db->beginTransaction();

            // Extracción de la dirección del comprador de la tabla 'user'
            $userRow = User::findById(Session::get('user_id'));
            $direccionTexto = $userRow->direccion ?? 'Dirección no especificada';

            // Registro de la localización en la tabla unificada de direcciones
            $stmtNewAddr = $db->prepare('INSERT INTO address (calle, numero, codigo_postal, ciudad, provincia) VALUES (?, ?, ?, ?, ?)');
            $stmtNewAddr->execute([$direccionTexto, '-', '-', 'Villafranca de los Barros', 'Badajoz']);
            $addressId = $db->lastInsertId();

            // 🌟 ACTUALIZADO: Añadimos delivery_method al registro maestro del pedido
            $stmt = $db->prepare('INSERT INTO purchase (user_id, address_id, total, estado, delivery_method) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([Session::get('user_id'), $addressId, $total, 'PENDIENTE', $deliveryMethod]);
            $purchaseId = (int)$db->lastInsertId();

            // Procesamiento síncrono de líneas de pedido y actualización de inventario
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
