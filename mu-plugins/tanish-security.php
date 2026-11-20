<?php
/**
 * Plugin Name: TANISH - Endurecimiento básico
 * Description: Desactiva XML-RPC y añade cabeceras de seguridad conservadoras. No desactiva la REST API.
 */

defined('ABSPATH') || exit;

// XML-RPC es un vector de fuerza bruta y no lo usa el sitio.
add_filter('xmlrpc_enabled', '__return_false');

// Evita el sniffing de tipos MIME. No interfiere con la REST API ni con el panel.
add_action('send_headers', function (): void {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
    }
}, 20);
