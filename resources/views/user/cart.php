<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<div style="background-color: #f7fbf8; min-height: 100vh; padding: 40px 0; font-family: 'Inter', sans-serif;">
    <style>
        .cart-container {
            max-width: 1050px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .cart-wrapper {
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .products-column {
            flex: 0 0 62%;
        }

        .summary-column {
            flex: 0 0 34%;
        }

        .top-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .btn-clear {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-clear:hover {
            color: #ef4444;
        }

        .product-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #d4e8da;
            padding: 30px;
            margin-bottom: 24px;
            position: relative;
            display: flex;
            gap: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .product-img-wrapper {
            width: 120px;
            height: 120px;
            background: #f7fbf8;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d4e8da;
            flex-shrink: 0;
        }

        .product-details-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .product-controls-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .price-wrapper {
            text-align: right;
        }

        .delivery-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #d4e8da;
            cursor: pointer;
            transition: 0.2s;
        }

        .delivery-option:hover,
        .delivery-option.active {
            border-color: #00b050;
            background: #f0fdf4;
        }

        /* --- Estilos Responsivos Estrictos --- */
        @media (max-width: 992px) {
            .cart-wrapper {
                flex-direction: column;
            }

            .products-column,
            .summary-column {
                flex: 0 0 100%;
                width: 100%;
            }

            .summary-column {
                position: static !important;
                margin-top: 20px;
            }
        }

        @media (max-width: 768px) {
            .top-nav-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                margin-bottom: 30px;
            }

            .product-card {
                flex-direction: column;
                padding: 20px;
                gap: 15px;
            }

            .product-img-wrapper {
                width: 100%;
                height: 140px;
            }

            .product-details-content {
                padding-right: 0;
            }

            .product-title {
                padding-right: 40px;
            }

            .product-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .product-controls-left {
                flex-wrap: wrap;
                gap: 10px;
                width: 100%;
            }

            .price-wrapper {
                width: 100%;
                text-align: right;
                padding-top: 10px;
                border-top: 1px dashed #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: baseline;
            }

            .price-wrapper::before {
                content: "Subtotal:";
                font-size: 11px;
                font-weight: 800;
                color: #94a3b8;
                text-transform: uppercase;
            }
        }
    </style>

    <div class="cart-container">
        <!-- TOP NAV: SEGUIR COMPRANDO & TU CESTA -->
        <div class="top-nav-header">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; display: flex; flex-wrap: wrap; gap: 8px;">
                    <a href="<?= BASE_URL ?>/" style="color: #94a3b8; text-decoration: none;">Inicio</a>
                    <span style="color: #94a3b8;">/</span>
                    <span style="color: #1a2e1f;">Tu Cesta</span>
                </div>
                <h1 style="font-size: 32px; font-weight: 900; color: #1a2e1f; margin: 0; letter-spacing: -1px;">Tu Cesta</h1>
            </div>

            <a href="<?= BASE_URL ?>/businesses" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; color: #00b050; font-weight: 800; font-size: 13px; padding: 12px 20px; background: white; border-radius: 12px; border: 1.5px solid #00b050; transition: 0.2s; width: fit-content;" onmouseover="this.style.background='#00b050'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#00b050'">
                <i class="fa-solid fa-arrow-left"></i> Seguir comprando
            </a>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div style="background: white; border-radius: 32px; padding: 80px 20px; text-align: center; border: 1px solid #d4e8da;">
                <i class="fa-solid fa-basket-shopping" style="font-size: 60px; color: #d4e8da; margin-bottom: 30px; display: block;"></i>
                <h2 style="font-size: 24px; font-weight: 800; color: #1a2e1f; margin-bottom: 10px;">¡Vaya! Tu cesta est&aacute; vac&iacute;a</h2>
                <p style="color: #5a7a64; margin-bottom: 40px; font-weight: 500;">Parece que a&uacute;n no has a&ntilde;adido ning&uacute;n producto local.</p>
                <a href="<?= BASE_URL ?>/businesses" style="background: #00b050; color: white; padding: 18px 40px; border-radius: 16px; font-weight: 800; text-decoration: none; display: inline-block; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; transition: 0.2s;">
                    Explorar comercios
                </a>
            </div>
        <?php else: ?>

            <!-- VACIAR CESTA -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px; padding-right: 10px;">
                <form action="<?= BASE_URL ?>/cart/clear" method="POST" onsubmit="return confirm('¿Vaciar toda la cesta?')">
                    <?= \App\Core\Session::csrfField() ?>
                    <button type="submit" class="btn-clear">
                        <i class="fa-solid fa-trash-can"></i> Vaciar mi cesta
                    </button>
                </form>
            </div>

            <div class="cart-wrapper">

                <!-- COLUMNA PRODUCTOS -->
                <div class="products-column">
                    <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                        <div class="product-card">

                            <!-- BOTÓN ELIMINAR (X) -->
                            <form action="<?= BASE_URL ?>/cart/remove" method="POST" style="position: absolute; top: 25px; right: 25px;">
                                <?= \App\Core\Session::csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <button type="submit" style="background: white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #94a3b8; transition: 0.2s;" onmouseover="this.style.color='#ef4444'; this.style.borderColor='#ef4444';" onmouseout="this.style.color='#94a3b8'; this.style.borderColor='#e2e8f0';">
                                    <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                                </button>
                            </form>

                            <div class="product-img-wrapper">
                                <i class="fa-solid fa-box-open" style="font-size: 40px; color: #d4e8da;"></i>
                            </div>

                            <div class="product-details-content">
                                <div>
                                    <h3 class="product-title" style="font-size: 18px; font-weight: 900; color: #1a2e1f; margin: 0 0 12px 0; line-height: 1.2;">
                                        <?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <div style="display: flex; align-items: center;">
                                            <i class="fa-solid fa-circle-check" style="color: #00b050; font-size: 14px; margin-right: 12px;"></i>
                                            <span style="font-size: 11px; font-weight: 700; color: #5a7a64;">Recogida en tienda gratuita</span>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            <i class="fa-solid fa-truck-fast" style="color: #00b050; font-size: 14px; margin-right: 12px;"></i>
                                            <span style="font-size: 11px; font-weight: 700; color: #5a7a64;">Env&iacute;o local disponible</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="product-controls">
                                    <div class="product-controls-left">
                                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Cantidad</span>

                                        <div style="display: flex; align-items: center; background: #f1f5f9; border-radius: 50px; padding: 4px; border: 1px solid #e2e8f0;">
                                            <form action="<?= BASE_URL ?>/cart/update" method="POST" style="margin: 0;">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <input type="hidden" name="accion" value="restar">
                                                <button type="submit" style="width: 28px; height: 28px; border-radius: 50%; border: none; background: white; color: #00b050; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center;">&minus;</button>
                                            </form>
                                            <span style="width: 32px; text-align: center; font-weight: 800; color: #1a2e1f; font-size: 13px;"><?= $item['cantidad'] ?></span>
                                            <form action="<?= BASE_URL ?>/cart/update" method="POST" style="margin: 0;">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <input type="hidden" name="accion" value="sumar">
                                                <button type="submit" style="width: 28px; height: 28px; border-radius: 50%; border: none; background: white; color: #00b050; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center;">&plus;</button>
                                            </form>
                                        </div>

                                        <span style="font-size: 12px; font-weight: 700; color: #64748b; background:#f1f5f9; padding: 4px 8px; border-radius:8px; white-space: nowrap;">&times; <?= number_format($item['precio'], 2) ?> &euro;</span>
                                    </div>

                                    <div class="price-wrapper">
                                        <span style="font-size: 20px; font-weight: 900; color: #1a2e1f; letter-spacing: -0.5px; white-space: nowrap;"><?= number_format($item['precio'] * $item['cantidad'], 2) ?> &euro;</span>
                                    </div>
                                </div>

                                <!-- VOLVER AL COMERCIO -->
                                <div style="margin-top: 15px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                                    <a href="<?= BASE_URL ?>/business/<?= $item['business_id'] ?>" style="font-size: 10px; font-weight: 800; color: #00b050; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fa-solid fa-shop"></i> Volver al comercio
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- COLUMNA RESUMEN -->
                <div class="summary-column">
                    <div style="background: white; border-radius: 32px; border: 1px solid #d4e8da; padding: 35px; position: sticky; top: 100px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);">
                        <h2 style="font-size: 20px; font-weight: 900; color: #1a2e1f; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">Resumen de pedido</h2>

                        <!-- OPCIONES ENTREGA -->
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
                            <label class="delivery-option active">
                                <input type="radio" name="d" checked style="accent-color: #00b050; width: 18px; height: 18px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 13px; font-weight: 800; color: #1a2e1f;">Env&iacute;o a Domicilio</span>
                                    <span style="font-size: 10px; font-weight: 800; color: #00b050; text-transform: uppercase;">Ma&ntilde;ana mismo</span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="d" style="accent-color: #00b050; width: 18px; height: 18px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 13px; font-weight: 800; color: #1a2e1f;">Recogida en Tienda</span>
                                    <span style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Gratis</span>
                                </div>
                            </label>
                        </div>

                        <!-- TOTALES -->
                        <div style="margin-bottom: 35px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Subtotal</span>
                                <span style="font-size: 13px; font-weight: 800; color: #1a2e1f; white-space: nowrap;"><?= number_format($total, 2) ?> &euro;</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Gesti&oacute;n env&iacute;o</span>
                                <span style="font-size: 13px; font-weight: 800; color: #00b050;">Gratis</span>
                            </div>

                            <div style="height: 1px; background: #e2e8f0; margin-bottom: 20px;"></div>

                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <span style="font-size: 14px; font-weight: 900; color: #1a2e1f; text-transform: uppercase;">Total</span>
                                <span style="font-size: 32px; font-weight: 900; color: #1a2e1f; letter-spacing: -1px; line-height: 1; white-space: nowrap;"><?= number_format($total, 2) ?> &euro;</span>
                            </div>
                        </div>

                        <!-- BOTÓN PAGO -->
                        <form action="<?= BASE_URL ?>/checkout" method="POST">
                            <?= \App\Core\Session::csrfField() ?>
                            <button type="submit" style="width: 100%; background: #00b050; color: white; border: none; border-radius: 14px; padding: 18px; font-size: 15px; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 6px -1px rgba(0, 176, 80, 0.2);" onmouseover="this.style.background='#008c3d'" onmouseout="this.style.background='#00b050'">
                                Tramitar Pedido <i class="fa-solid fa-arrow-right-long"></i>
                            </button>
                        </form>

                        <div style="margin-top: 25px; text-align: center;">
                            <p style="font-size: 10px; font-weight: 800; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fa-solid fa-shield-check" style="color: #00b050; font-size: 13px;"></i> Seguridad Certificada
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
