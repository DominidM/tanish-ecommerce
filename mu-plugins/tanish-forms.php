<?php
/**
 * Plugin Name: TANISH - Registro de formularios
 * Description: Guarda los envíos de Contacto y del Libro de Reclamaciones en la base de datos y los muestra en el panel de WordPress.
 */

defined('ABSPATH') || exit;

/**
 * Capacidades de los CPT privados: todo el acceso requiere manage_woocommerce
 * (administradores y shop managers). Así Autor/Editor no ven datos personales
 * (DNI, email, teléfono) aunque tengan acceso al panel.
 *
 * @return array<string, string>
 */
function tanish_private_cpt_caps(): array
{
    return array_fill_keys([
        'edit_post',
        'read_post',
        'delete_post',
        'edit_posts',
        'edit_others_posts',
        'publish_posts',
        'read_private_posts',
        'delete_posts',
        'delete_private_posts',
        'delete_published_posts',
        'delete_others_posts',
        'edit_private_posts',
        'edit_published_posts',
        'create_posts',
    ], 'manage_woocommerce');
}

/**
 * Registra los tipos de contenido (privados, solo panel admin).
 */
add_action('init', function (): void {
    register_post_type('tanish_reclamo', [
        'labels' => [
            'name'          => 'Reclamos',
            'singular_name' => 'Reclamo',
            'menu_name'     => 'Reclamos',
            'all_items'     => 'Todos los reclamos',
            'edit_item'     => 'Ver reclamo',
            'search_items'  => 'Buscar reclamos',
            'not_found'     => 'No hay reclamos registrados.',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => false,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-megaphone',
        'capabilities'        => tanish_private_cpt_caps(),
        'map_meta_cap'        => true,
        'supports'            => ['title', 'editor'],
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
    ]);

    register_post_type('tanish_contacto', [
        'labels' => [
            'name'          => 'Mensajes de contacto',
            'singular_name' => 'Mensaje',
            'menu_name'     => 'Mensajes',
            'all_items'     => 'Todos los mensajes',
            'edit_item'     => 'Ver mensaje',
            'search_items'  => 'Buscar mensajes',
            'not_found'     => 'No hay mensajes registrados.',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => false,
        'menu_position'       => 27,
        'menu_icon'           => 'dashicons-email-alt',
        'capabilities'        => tanish_private_cpt_caps(),
        'map_meta_cap'        => true,
        'supports'            => ['title', 'editor'],
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
    ]);
});

/**
 * ¿La IP pertenece a un proxy de confianza (privada o loopback)?
 */
function tanish_is_trusted_proxy(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    // Si NO es pública (es privada o reservada/loopback), es un proxy local.
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

/**
 * IP real del visitante (detrás del Worker de Cloudflare y Caddy).
 *
 * - HTTP_CF_CONNECTING_IP lo escribe Cloudflare y es la fuente preferente.
 * - HTTP_X_FORWARDED_FOR solo se acepta si REMOTE_ADDR es un proxy de
 *   confianza (IP privada/loopback). Si el origen recibe tráfico directo,
 *   un cliente podría falsificarlo y por eso se ignora.
 * - En cualquier otro caso se usa REMOTE_ADDR.
 */
function tanish_client_ip(): string
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidate = trim(explode(',', (string) $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $remoteValid = (bool) filter_var($remote, FILTER_VALIDATE_IP);

    if ($remoteValid && tanish_is_trusted_proxy($remote) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
            $candidate = trim($part);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    return $remoteValid ? $remote : '0.0.0.0';
}

/**
 * Guarda un envío en la base de datos.
 *
 * @param string $type   'reclamo' | 'contacto'
 * @param array  $fields Pares etiqueta => valor.
 * @param string $title  Título del registro en el panel.
 */
function tanish_save_form_entry(string $type, array $fields, string $title): int
{
    $post_type = $type === 'reclamo' ? 'tanish_reclamo' : 'tanish_contacto';

    $content = '';
    foreach ($fields as $label => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $content .= '<p><strong>' . esc_html((string) $label) . ':</strong><br>'
            . nl2br(esc_html((string) $value)) . '</p>';
    }

    $post_id = wp_insert_post([
        'post_type'    => $post_type,
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_content' => $content,
    ], true);

    if (is_wp_error($post_id)) {
        return 0;
    }

    update_post_meta($post_id, '_tanish_fields', $fields);
    update_post_meta($post_id, '_tanish_ip', tanish_client_ip());

    return (int) $post_id;
}

/**
 * Columnas del listado de Reclamos.
 */
add_filter('manage_tanish_reclamo_posts_columns', function (array $columns): array {
    return [
        'cb'       => $columns['cb'] ?? '<input type="checkbox" />',
        'title'    => 'Referencia',
        'tipo'     => 'Tipo',
        'correo'   => 'Correo',
        'telefono' => 'Teléfono',
        'date'     => 'Fecha',
    ];
});

add_action('manage_tanish_reclamo_posts_custom_column', function (string $column, int $post_id): void {
    $fields = get_post_meta($post_id, '_tanish_fields', true);
    $fields = is_array($fields) ? $fields : [];

    switch ($column) {
        case 'tipo':
            echo esc_html($fields['Tipo de trámite'] ?? '—');
            break;
        case 'correo':
            echo esc_html($fields['Correo'] ?? '—');
            break;
        case 'telefono':
            echo esc_html($fields['Teléfono'] ?? '—');
            break;
    }
}, 10, 2);

/**
 * Columnas del listado de Mensajes de contacto.
 */
add_filter('manage_tanish_contacto_posts_columns', function (array $columns): array {
    return [
        'cb'       => $columns['cb'] ?? '<input type="checkbox" />',
        'title'    => 'Contacto',
        'tipo'     => 'Tipo de consulta',
        'correo'   => 'Correo',
        'telefono' => 'Teléfono',
        'date'     => 'Fecha',
    ];
});

add_action('manage_tanish_contacto_posts_custom_column', function (string $column, int $post_id): void {
    $fields = get_post_meta($post_id, '_tanish_fields', true);
    $fields = is_array($fields) ? $fields : [];

    switch ($column) {
        case 'tipo':
            echo esc_html($fields['Tipo'] ?? '—');
            break;
        case 'correo':
            echo esc_html($fields['Correo'] ?? '—');
            break;
        case 'telefono':
            echo esc_html($fields['Teléfono'] ?? '—');
            break;
    }
}, 10, 2);
