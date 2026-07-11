<?php

/**
 * =========================================================
 * app/Core/Mailer.php — Sistema de notificaciones por email
 *
 * Envía emails transaccionales con plantilla de marca:
 * · Configura PHPMailer con SMTP
 * · Envía confirmaciones y alertas para pedidos y reservas
 * · Genera mensajes personalizados para clientes y comercios
 * =========================================================
 */

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /**
     * Crea y devuelve una instancia de PHPMailer configurada según config.php.
     *
     * @return PHPMailer|null
     */
    private static function build(): ?PHPMailer
    {
        if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
            return null;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->isHTML(true);

        return $mail;
    }

    /**
     * Envía el email usando PHPMailer.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $body
     * @return bool
     */
    private static function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $mail = self::build();
        if ($mail === null) {
            return false;
        }

        try {
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body = self::wrap($subject, $body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[Mailer] Error al enviar email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía un email de confirmación de pedido al cliente.
     *
     * @param array $user
     * @param array $items
     * @param float $total
     * @param int $purchaseId
     * @return bool
     */
    public static function sendOrderToClient(array $user, array $items, float $total, int $purchaseId): bool
    {
        $rows = '';
        foreach ($items as $it) {
            $rows .= "<tr>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee;'>{$it['nombre']}</td>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee; text-align:center;'>{$it['cantidad']}</td>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee; text-align:right; font-weight:700;'>" . number_format($it['precio_unitario'] * $it['cantidad'], 2) . " €</td>
            </tr>";
        }

        $body = "
            <p style='font-size:1rem;'>Hola, <strong>{$user['nombre']}</strong> 👋</p>
            <p>Tu pedido <strong>#$purchaseId</strong> ha sido registrado correctamente y el comercio ya ha sido notificado.</p>
            <table style='width:100%; border-collapse:collapse; margin:1rem 0;'>
                <thead><tr>
                    <th style='text-align:left; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Producto</th>
                    <th style='text-align:center; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Uds.</th>
                    <th style='text-align:right; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Importe</th>
                </tr></thead>
                <tbody>$rows</tbody>
            </table>
            <p style='text-align:right; font-size:1.25rem;'><strong>Total: " . number_format($total, 2) . " €</strong></p>
            <p style='color:#5a7a64; font-size:.875rem;'>Pronto el comercio te confirmará cuando tu pedido esté listo.</p>
        ";

        return self::send($user['email'], $user['nombre'], "✅ Pedido #{$purchaseId} confirmado — Mercalocal", $body);
    }

    /**
     * Envía una alerta al comercio cuando recibe un nuevo pedido.
     *
     * @param string $businessEmail
     * @param string $businessName
     * @param array $items
     * @param float $total
     * @param int $purchaseId
     * @return bool
     */
    public static function sendOrderToBusiness(string $businessEmail, string $businessName, array $items, float $total, int $purchaseId): bool
    {
        $rows = '';
        foreach ($items as $it) {
            $rows .= "<tr>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee;'>{$it['nombre']}</td>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee; text-align:center;'>{$it['cantidad']}</td>
                <td style='padding:.5rem 0; border-bottom:1px solid #e6f7ee; text-align:right; font-weight:700;'>" . number_format($it['precio_unitario'] * $it['cantidad'], 2) . " €</td>
            </tr>";
        }

        $body = "
            <p style='font-size:1rem;'>Hola, <strong>{$businessName}</strong> 🏪</p>
            <p>Has recibido un <strong>nuevo pedido #$purchaseId</strong> en Mercalocal.</p>
            <table style='width:100%; border-collapse:collapse; margin:1rem 0;'>
                <thead><tr>
                    <th style='text-align:left; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Producto</th>
                    <th style='text-align:center; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Uds.</th>
                    <th style='text-align:right; padding:.5rem 0; border-bottom:2px solid #00b050; color:#00b050;'>Importe</th>
                </tr></thead>
                <tbody>$rows</tbody>
            </table>
            <p style='text-align:right; font-size:1.25rem;'><strong>Total: " . number_format($total, 2) . " €</strong></p>
            <p style='color:#5a7a64; font-size:.875rem;'>Accede a tu panel para gestionar el pedido.</p>
        ";

        return self::send($businessEmail, $businessName, "🛒 Nuevo pedido #{$purchaseId} en Mercalocal", $body);
    }

    /**
     * Envía la confirmación de una reserva al cliente.
     *
     * @param array $user
     * @param array $reservation
     * @param string $businessName
     * @return bool
     */
    public static function sendReservationToClient(array $user, array $reservation, string $businessName): bool
    {
        $body = "
            <p>Hola, <strong>{$user['nombre']}</strong> 👋</p>
            <p>Tu reserva en <strong>{$businessName}</strong> ha sido creada correctamente.</p>
            <table style='width:100%; border-collapse:collapse; margin:1rem 0;'>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Fecha:</td><td style='padding:.4rem 0; font-weight:700;'>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Hora:</td><td style='padding:.4rem 0; font-weight:700;'>{$reservation['hora_inicio']}</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Servicio:</td><td style='padding:.4rem 0; font-weight:700;'>{$reservation['service_name']}</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Estado:</td><td style='padding:.4rem 0;'><span style='background:#e6f7ee; color:#166534; padding:.2rem .6rem; border-radius:6px;'>Pendiente de confirmación</span></td></tr>
            </table>
            <p style='color:#5a7a64; font-size:.875rem;'>El comercio confirmará tu cita en breve.</p>
        ";

        return self::send($user['email'], $user['nombre'], "📅 Reserva confirmada en {$businessName} — Mercalocal", $body);
    }

    /**
     * Envía una alerta al comercio cuando recibe una nueva solicitud de cita.
     *
     * @param string $businessEmail
     * @param string $businessName
     * @param array $user
     * @param array $reservation
     * @return bool
     */
    public static function sendReservationToBusiness(string $businessEmail, string $businessName, array $user, array $reservation): bool
    {
        $body = "
            <p>Hola, <strong>{$businessName}</strong> 🏪</p>
            <p>Tienes una <strong>nueva solicitud de cita</strong> en Mercalocal.</p>
            <table style='width:100%; border-collapse:collapse; margin:1rem 0;'>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Cliente:</td><td style='padding:.4rem 0; font-weight:700;'>{$user['nombre']} {$user['apellidos']}</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Teléfono:</td><td style='padding:.4rem 0; font-weight:700;'>{$user['telefono']}</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Fecha:</td><td style='padding:.4rem 0; font-weight:700;'>" . date('d/m/Y', strtotime($reservation['fecha'])) . "</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Hora:</td><td style='padding:.4rem 0; font-weight:700;'>{$reservation['hora_inicio']}</td></tr>
                <tr><td style='padding:.4rem 0; color:#5a7a64;'>Servicio:</td><td style='padding:.4rem 0; font-weight:700;'>{$reservation['service_name']}</td></tr>
            </table>
            <p style='color:#5a7a64; font-size:.875rem;'>Accede a tu panel de Mercalocal para confirmar o gestionar la cita.</p>
        ";

        return self::send($businessEmail, $businessName, "📅 Nueva reserva de {$user['nombre']} — Mercalocal", $body);
    }

    /**
     * Envía un email de bienvenida al nuevo usuario tras completarse el registro.
     *
     * @param string $nombre
     * @param string $email
     * @param string $rol
     * @return bool
     */
    public static function sendWelcome(string $nombre, string $email, string $rol): bool
    {
        if ($rol === 'BUSINESS') {
            $body = "
                <p style='font-size:1rem;'>Hola, <strong>{$nombre}</strong> 👋</p>
                <p>¡Tu cuenta de comercio en <strong>Mercalocal</strong> ha sido creada con éxito!</p>
                <p>El siguiente paso es configurar el perfil de tu negocio para que los clientes puedan
                   encontrarte, ver tus productos y reservar tus servicios.</p>
                <div style='margin:1.5rem 0; text-align:center;'>
                    <a href='" . BASE_URL . "/business/setup'
                       style='background:#00b050; color:white; font-weight:700; padding:.75rem 2rem;
                              border-radius:9999px; text-decoration:none; font-size:.95rem;'>
                        Configurar mi comercio →
                    </a>
                </div>
                <p style='color:#5a7a64; font-size:.875rem;'>
                    Si tienes alguna duda, no dudes en contactarnos. ¡Bienvenido a la comunidad Mercalocal!
                </p>
            ";
            $subject = '🏪 ¡Bienvenido a Mercalocal! Configura tu perfil de comercio';
        } else {
            $body = "
                <p style='font-size:1rem;'>Hola, <strong>{$nombre}</strong> 👋</p>
                <p>¡Ya formas parte de <strong>Mercalocal</strong>! Gracias por unirte a nuestra comunidad
                   de apoyo al comercio local.</p>
                <p>Con tu cuenta puedes:</p>
                <ul style='margin:.5rem 0 1.25rem 1.25rem; color:#1a2e1f; line-height:1.8;'>
                    <li>🛒 Comprar productos frescos y artesanales de tu barrio</li>
                    <li>📅 Reservar citas en peluquerías, talleres y otros servicios locales</li>
                    <li>❤️ Apoyar a los comerciantes de tu comunidad</li>
                </ul>
                <div style='margin:1.5rem 0; text-align:center;'>
                    <a href='" . BASE_URL . "/businesses'
                       style='background:#00b050; color:white; font-weight:700; padding:.75rem 2rem;
                              border-radius:9999px; text-decoration:none; font-size:.95rem;'>
                        Explorar comercios →
                    </a>
                </div>
                <p style='color:#5a7a64; font-size:.875rem;'>
                    ¡Esperamos que disfrutes de la experiencia Mercalocal!
                </p>
            ";
            $subject = '🎉 ¡Bienvenido a Mercalocal! Tu cuenta está lista';
        }

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Envuelve el contenido HTML del email en la plantilla de Mercalocal.
     *
     * @param string $title
     * @param string $body
     * @return string
     */
    private static function wrap(string $title, string $body): string
    {
        return "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
        <body style='margin:0; padding:0; background:#f7fbf8; font-family:Arial,sans-serif;'>
            <div style='max-width:580px; margin:2rem auto; background:white; border-radius:1rem; overflow:hidden; border:1px solid #d4e8da;'>
                <!-- Cabecera con el logotipo de texto de Mercalocal -->
                <div style='background:#0a1f10; padding:1.5rem 2rem; display:flex; align-items:center; gap:.75rem;'>
                    <div style='font-size:1.4rem; font-weight:900; color:white;'>Merca<span style='color:#ffe295;'>local</span></div>
                </div>
                <!-- Cuerpo del email -->
                <div style='padding:2rem;'>
                    <h2 style='color:#1a2e1f; font-size:1.25rem; margin:0 0 1.25rem;'>{$title}</h2>
                    {$body}
                </div>
                <!-- Pie de página -->
                <div style='background:#f7fbf8; padding:1rem 2rem; border-top:1px solid #d4e8da; text-align:center;'>
                    <p style='color:#5a7a64; font-size:.75rem; margin:0;'>&copy; " . date('Y') . " Mercalocal &mdash; Tu comercio de barrio</p>
                </div>
            </div>
        </body></html>";
    }
}
