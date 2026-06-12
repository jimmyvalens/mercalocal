<?php
// =========================================================
// src/Core/Mailer.php — Sistema de notificaciones por email
// Encapsula PHPMailer para enviar emails transaccionales
// con diseño de marca de Mercalocal.
//
// PHPMailer se instala con: composer require phpmailer/phpmailer
// Para activar el envío, establece MAIL_ENABLED = true en config.php
// y rellena las credenciales SMTP (p.ej. Brevo o Mailtrap).
// =========================================================
namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /**
     * Crea y devuelve una instancia de PHPMailer ya configurada
     * con los parámetros SMTP definidos en config.php.
     * Devuelve null si el envío de emails está desactivado.
     */
    private static function build(): ?PHPMailer
    {
        // Si MAIL_ENABLED es false, no se envía ningún email (modo test)
        if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
            return null;
        }

        $mail = new PHPMailer(true); // true = lanza excepciones ante errores
        $mail->isSMTP(); // Usar protocolo SMTP
        $mail->CharSet = 'UTF-8'; // Codificación para soportar tildes y ñ
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true; // Autenticación obligatoria
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cifrado TLS
        $mail->Port = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->isHTML(true); // El cuerpo del email será HTML

        return $mail;
    }

    /**
     * Método interno que envía el email.
     * Llama a build() para obtener la instancia configurada,
     * adjunta el destinatario y dispara el envío.
     *
     * @param string $toEmail Email del destinatario
     * @param string $toName  Nombre del destinatario
     * @param string $subject Asunto del email
     * @param string $body    Contenido HTML del cuerpo
     * @return bool           true si el email se envió, false si no
     */
    private static function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $mail = self::build();
        if ($mail === null) {
            return false; // Email desactivado — se omite silenciosamente
        }

        try {
            $mail->addAddress($toEmail, $toName); // Añadir destinatario
            $mail->Subject = $subject;
            $mail->Body = self::wrap($subject, $body); // Envolver en plantilla de marca
            $mail->send();
            return true;
        }
        catch (Exception $e) {
            // Registrar el error en el log de PHP sin interrumpir la petición
            error_log('[Mailer] Error al enviar email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía un email de confirmación de pedido al cliente.
     * Se llama desde CartController tras procesar el checkout.
     *
     * @param array $user       Datos del cliente (nombre, email)
     * @param array $items      Líneas del pedido (nombre, cantidad, precio_unitario)
     * @param float $total      Importe total del pedido
     * @param int   $purchaseId ID del pedido en la base de datos
     */
    public static function sendOrderToClient(array $user, array $items, float $total, int $purchaseId): bool
    {
        // Construir las filas de la tabla de productos
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
     * Se llama desde CartController tras completar el checkout.
     *
     * @param string $businessEmail Email de contacto del comercio
     * @param string $businessName  Nombre del comercio
     * @param array  $items         Líneas del pedido
     * @param float  $total         Importe total del pedido
     * @param int    $purchaseId    ID del pedido
     */
    public static function sendOrderToBusiness(string $businessEmail, string $businessName, array $items, float $total, int $purchaseId): bool
    {
        // Construir las filas de la tabla de productos
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
     * Se llama desde ReservationController tras guardar la cita.
     *
     * @param array  $user         Datos del cliente (nombre, email)
     * @param array  $reservation  Datos de la reserva (fecha, hora_inicio, service_name…)
     * @param string $businessName Nombre del comercio donde se hace la reserva
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
     * Se llama desde ReservationController tras guardar la reserva.
     *
     * @param string $businessEmail Email del comercio
     * @param string $businessName  Nombre del comercio
     * @param array  $user          Datos del cliente (nombre, apellidos, teléfono)
     * @param array  $reservation   Datos de la cita (fecha, hora_inicio, service_name…)
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
     * Envía un email de bienvenida al nuevo usuario tras completar el registro.
     * El mensaje se adapta según el rol:
     *   - USER     → le invita a explorar los comercios y hacer su primer pedido
     *   - BUSINESS → le recuerda que debe completar el perfil de su comercio
     *
     * Se llama desde AuthController::register() justo después de crear la cuenta.
     * Si MAIL_ENABLED es false, el método devuelve false silenciosamente.
     *
     * @param  string $nombre Nombre del nuevo usuario
     * @param  string $email  Email del nuevo usuario
     * @param  string $rol    Rol asignado: 'USER' o 'BUSINESS'
     * @return bool           true si el email se envió, false si no
     */
    public static function sendWelcome(string $nombre, string $email, string $rol): bool
    {
        // Personalizar el cuerpo según el tipo de cuenta creada
        if ($rol === 'BUSINESS') {
            // Email para nuevos comercios: les guía al asistente de configuración
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
        }
        else {
            // Email para clientes: les invita a explorar el catálogo
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
     * Envuelve el contenido HTML del email en la plantilla
     * de marca de Mercalocal (cabecera verde + pie de página).
     *
     * @param string $title Asunto / título del email
     * @param string $body  Contenido principal HTML
     * @return string       HTML completo del email
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
