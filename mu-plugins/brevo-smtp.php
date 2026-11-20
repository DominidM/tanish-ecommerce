<?php
/**
 * Plugin Name: TANISH - SMTP (Brevo / Mailpit)
 * Description: Envía el correo de WordPress (pedidos, usuarios, formularios) por SMTP. Se configura con variables de entorno: SMTP_HOST, SMTP_PORT, SMTP_AUTH, SMTP_SECURE, SMTP_USER, SMTP_PASSWORD, SMTP_FROM_EMAIL y SMTP_FROM_NAME. En desarrollo apunta a Mailpit; en producción, a Brevo.
 */

defined('ABSPATH') || exit;

/**
 * Interpreta una variable de entorno como booleano ("0", "false", "no", "off" => false).
 */
function tanish_smtp_env_bool(string $value, bool $default = false): bool
{
    $normalized = strtolower(trim($value));
    if ($normalized === '') {
        return $default;
    }

    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

/**
 * Determina el cifrado de PHPMailer.
 *
 * Prioridad: SMTP_SECURE explícito (ssl|tls|none) y, si está vacío, el puerto
 * (465 => ssl, 587 => tls, 1025 => sin cifrado). Por defecto, STARTTLS.
 */
function tanish_smtp_encryption(string $secure, int $port): string
{
    $secure = strtolower(trim($secure));

    if ($secure === 'none' || $secure === 'no' || $secure === 'off') {
        return '';
    }
    if ($secure === 'ssl' || $secure === 'smtps') {
        return PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    }
    if ($secure === 'tls' || $secure === 'starttls') {
        return PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    // Sin valor explícito: deducir por puerto.
    if ($port === 465) {
        return PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    }
    if ($port === 1025) {
        return ''; // Mailpit local no usa cifrado.
    }

    return PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
}

/**
 * Corrige el remitente antes de que WordPress llame a setFrom().
 *
 * WordPress usa `wordpress@<host>` por defecto; con un host local (localhost)
 * PHPMailer lanza "Invalid address" antes de que se ejecute phpmailer_init.
 */
add_filter('wp_mail_from', function (string $from): string {
    if (trim((string) getenv('SMTP_HOST')) === '') {
        return $from;
    }

    $configured = trim((string) getenv('SMTP_FROM_EMAIL'));

    return is_email($configured) ? $configured : $from;
});

add_filter('wp_mail_from_name', function (string $name): string {
    if (trim((string) getenv('SMTP_HOST')) === '') {
        return $name;
    }

    $configured = trim((string) getenv('SMTP_FROM_NAME'));

    return $configured !== '' ? $configured : $name;
});

/**
 * Configura PHPMailer con las variables SMTP_* del entorno.
 */
add_action('phpmailer_init', function (PHPMailer\PHPMailer\PHPMailer $phpmailer): void {
    $host = trim((string) getenv('SMTP_HOST'));
    if ($host === '') {
        return; // Sin configuración: se mantiene el envío por defecto (mail()).
    }

    $port     = (int) (getenv('SMTP_PORT') ?: 587);
    $auth     = tanish_smtp_env_bool((string) getenv('SMTP_AUTH'), true);
    $secure   = tanish_smtp_encryption((string) getenv('SMTP_SECURE'), $port);

    $phpmailer->isSMTP();
    $phpmailer->Host        = $host;
    $phpmailer->Port        = $port;
    $phpmailer->SMTPAuth    = $auth;
    $phpmailer->SMTPSecure  = $secure;

    // Sin cifrado no intentar STARTTLS oportunista (rompe con Mailpit y con
    // relays que no lo ofrecen).
    $phpmailer->SMTPAutoTLS = ($secure !== '');

    if ($auth) {
        $phpmailer->Username = (string) (getenv('SMTP_USER') ?: '');
        $phpmailer->Password = (string) (getenv('SMTP_PASSWORD') ?: '');
    }

    // Remitente: debe existir y estar verificado en Brevo. Si el valor del
    // entorno no es un email válido, se usa el admin del sitio.
    $fromEmail = trim((string) getenv('SMTP_FROM_EMAIL'));
    if (!is_email($fromEmail)) {
        $fromEmail = (string) get_option('admin_email');
    }

    $fromName = trim((string) getenv('SMTP_FROM_NAME'));
    if ($fromName === '') {
        $fromName = (string) get_bloginfo('name');
    }

    if (is_email($fromEmail)) {
        // El Reply-To lo define la plantilla del tema; no duplicarlo aquí.
        $phpmailer->setFrom($fromEmail, $fromName, false);
    }
}, 10, 1);

/**
 * Registra fallos de wp_mail() para poder diagnosticar sin depender de la UI.
 */
add_action('wp_mail_failed', function (WP_Error $error): void {
    error_log('[TANISH SMTP] wp_mail() falló: ' . $error->get_error_message());
}, 10, 1);
