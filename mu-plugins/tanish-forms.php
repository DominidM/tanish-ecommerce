<?php
/**
 * Plugin Name: TANISH - Registro de formularios
 * Description: Guarda los envíos de Contacto y del Libro de Reclamaciones en la base de datos y los muestra en el panel de WordPress.
 */

defined('ABSPATH') || exit;

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
        'capability_type'     => 'post',
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
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => ['title', 'editor'],
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
    ]);
});

/**
 * IP real del visitante (detrás del Worker de Cloudflare y Caddy).
 */
function tanish_client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $candidate = trim(explode(',', (string) $_SERVER[$key])[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '0.0.0.0';
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
