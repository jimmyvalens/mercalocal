<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<div style="background-color: #f7fbf8; min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; font-family: 'Inter', sans-serif;">
    <div style="max-width: 500px; width: 100%; background: white; border-radius: 32px; border: 1px solid #d4e8da; padding: 50px; text-align: center; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);">

        <div style="width: 80px; height: 80px; background: #e6f7ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
            <i class="fa-solid fa-credit-card" style="font-size: 32px; color: #00b050;"></i>
        </div>

        <h1 style="font-size: 28px; font-weight: 900; color: #1a2e1f; margin-bottom: 10px; letter-spacing: -1px;">Simulación de Pago</h1>
        <p style="color: #5a7a64; font-size: 15px; margin-bottom: 35px; line-height: 1.6;">
            Esta es una pasarela de pago simulada para tu <strong>Proyecto de Fin de Grado</strong>. No se realizarán cargos reales en ninguna tarjeta de crédito.
        </p>

        <div style="background: #f7fbf8; border-radius: 16px; padding: 25px; margin-bottom: 35px; border: 1px dashed #d4e8da;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 15px;">
                <span style="font-size: 13px; font-weight: 800; color: #5a7a64; text-transform: uppercase;">Importe Total</span>
                <span style="font-size: 32px; font-weight: 900; color: #1a2e1f; letter-spacing: -1px;"><?= number_format($total, 2) ?> &euro;</span>
            </div>

            <div style="height: 1px; background: #e2e8f0; margin-bottom: 15px;"></div>

            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                <i class="fa-brands fa-cc-visa" style="font-size: 32px; color: #1434CB;"></i>
                <i class="fa-brands fa-cc-mastercard" style="font-size: 32px; color: #EB001B;"></i>
                <i class="fa-brands fa-cc-paypal" style="font-size: 32px; color: #003087;"></i>
            </div>
        </div>

        <form action="<?= BASE_URL ?>/checkout/confirm" method="POST">
            <button type="submit" style="width: 100%; background: #00b050; color: white; border: none; border-radius: 16px; padding: 20px; font-size: 17px; font-weight: 900; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 10px 15px -3px rgba(0, 176, 80, 0.2);" onmouseover="this.style.background='#008c3d'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#00b050'; this.style.transform='translateY(0)'">
                Confirmar y Pagar
            </button>
        </form>

        <div style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/cart" style="color: #94a3b8; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                Cancelar y Volver al Carrito
            </a>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>