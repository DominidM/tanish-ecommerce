<?php
/**
 * Plugin Name: TANISH - Brevo SMTP
 * Description: Envía el correo de WordPress (pedidos, usuarios, etc.) a través de Brevo SMTP. Configurado por variables de entorno (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_FROM_EMAIL, SMTP_FROM_NAME).
 */

add_action('phpmailer_init', function (PHPMailer\PHPMailer\PHPMailer $phpmailer): void {
    $host = getenv('SMTP_HOST');
    if (!$host) {
        return; // Sin configuración: se mantiene el envío por defecto.
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->Port       = (int) (getenv('SMTP_PORT') ?: 587);
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = getenv('SMTP_USER') ?: '';
    $phpmailer->Password   = getenv('SMTP_PASSWORD') ?: '';
    $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    // Remitente (debe estar verificado en Brevo).
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'solvegrades@gmail.com';
    $fromName  = getenv('SMTP_FROM_NAME') ?: get_bloginfo('name');
    $phpmailer->setFrom($fromEmail, $fromName, false);

    // El "reply-to" sigue siendo quien corresponda (ej.: cliente que responde).
    $phpmailer->addReplyTo($fromEmail, $fromName);
}, 10, 1);
