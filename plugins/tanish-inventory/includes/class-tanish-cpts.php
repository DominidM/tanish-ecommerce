<?php
/**
 * TANISH Custom Post Types: Banners & Videos
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

class Tanish_CPTs {

    public function __construct() {
        add_action('init', [$this, 'register_banners_cpt']);
        add_action('init', [$this, 'register_videos_cpt']);
        add_action('add_meta_boxes', [$this, 'add_banner_meta_boxes']);
        add_action('add_meta_boxes', [$this, 'add_video_meta_boxes']);
        add_action('save_post_tanish_banner', [$this, 'save_banner_meta'], 10, 2);
        add_action('save_post_tanish_video', [$this, 'save_video_meta'], 10, 2);
        add_filter('manage_tanish_banner_posts_columns', [$this, 'banner_admin_columns']);
        add_action('manage_tanish_banner_posts_custom_column', [$this, 'banner_admin_column_content'], 10, 2);
    }

    /* ── Banners CPT ────────────────────────────────── */

    public function register_banners_cpt(): void {
        register_post_type('tanish_banner', [
            'labels' => [
                'name'          => 'Banners TANISH',
                'singular_name' => 'Banner',
                'add_new_item'  => 'Agregar nuevo banner',
                'edit_item'     => 'Editar banner',
                'all_items'     => 'Todos los banners',
                'menu_name'     => 'Banners TANISH',
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-slides',
            'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'publicly_queryable' => false,
            'has_archive'        => false,
            'rewrite'            => false,
        ]);
    }

    /* ── Videos CPT ─────────────────────────────────── */

    public function register_videos_cpt(): void {
        register_post_type('tanish_video', [
            'labels' => [
                'name'          => 'Videos TANISH',
                'singular_name' => 'Video',
                'add_new_item'  => 'Agregar nuevo video',
                'edit_item'     => 'Editar video',
                'all_items'     => 'Todos los videos',
                'menu_name'     => 'Videos TANISH',
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 7,
            'menu_icon'          => 'dashicons-video-alt3',
            'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'publicly_queryable' => false,
            'has_archive'        => false,
            'rewrite'            => false,
        ]);
    }

    /* ── Banner Admin Columns ───────────────────────── */

    public function banner_admin_columns(array $columns): array {
        $new = [];
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new['title'] = 'Título';
            } elseif ($key === 'date') {
                $new['thumb'] = 'Imagen';
                $new['active'] = 'Activo';
                $new['order'] = 'Orden';
                $new['date'] = 'Fecha';
            } else {
                $new[$key] = $value;
            }
        }
        return $new;
    }

    public function banner_admin_column_content(string $column, int $post_id): void {
        switch ($column) {
            case 'thumb':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [80, 45], ['style' => 'object-fit:cover;border-radius:3px;']);
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
                break;
            case 'active':
                $active = get_post_meta($post_id, '_tanish_banner_active', true);
                echo $active === '1'
                    ? '<span style="color:#16a34a;font-weight:600;">Sí</span>'
                    : '<span style="color:#999;">No</span>';
                break;
            case 'order':
                echo esc_html(get_post($post_id)->menu_order);
                break;
        }
    }

    /* ── Banner Meta Boxes ──────────────────────────── */

    public function add_banner_meta_boxes(): void {
        add_meta_box(
            'tanish_banner_settings',
            'Configuración del Banner',
            [$this, 'render_banner_meta_box'],
            'tanish_banner',
            'normal',
            'high'
        );
    }

    public function render_banner_meta_box($post): void {
        wp_nonce_field('tanish_banner_meta', 'tanish_banner_nonce');

        $eyebrow    = get_post_meta($post->ID, '_tanish_banner_eyebrow', true);
        $button_text = get_post_meta($post->ID, '_tanish_banner_button_text', true);
        $button_url  = get_post_meta($post->ID, '_tanish_banner_button_url', true);
        $active      = get_post_meta($post->ID, '_tanish_banner_active', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tanish_banner_eyebrow">Etiqueta superior (opcional)</label></th>
                <td><input type="text" id="tanish_banner_eyebrow" name="tanish_banner_eyebrow" value="<?php echo esc_attr($eyebrow); ?>" class="regular-text" placeholder="Ej: Nuevo"></td>
            </tr>
            <tr>
                <th><label for="tanish_banner_button_text">Texto del botón</label></th>
                <td><input type="text" id="tanish_banner_button_text" name="tanish_banner_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text" placeholder="Ej: Ver productos"></td>
            </tr>
            <tr>
                <th><label for="tanish_banner_button_url">URL del botón</label></th>
                <td><input type="url" id="tanish_banner_button_url" name="tanish_banner_button_url" value="<?php echo esc_url($button_url); ?>" class="regular-text" placeholder="https://"></td>
            </tr>
            <tr>
                <th><label for="tanish_banner_active">Activo</label></th>
                <td>
                    <select id="tanish_banner_active" name="tanish_banner_active">
                        <option value="1" <?php selected($active, '1', true); ?>>Sí</option>
                        <option value="0" <?php selected($active, '', true); ?>>No</option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="description">Usa <strong>Imagen destacada</strong> como imagen principal del banner. Recomendación: 1600 × 600 px o proporción horizontal similar.</p>
        <?php
    }

    public function save_banner_meta(int $post_id, $post): void {
        if (!isset($_POST['tanish_banner_nonce']) || !wp_verify_nonce($_POST['tanish_banner_nonce'], 'tanish_banner_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_tanish_banner_eyebrow', sanitize_text_field($_POST['tanish_banner_eyebrow'] ?? ''));
        update_post_meta($post_id, '_tanish_banner_button_text', sanitize_text_field($_POST['tanish_banner_button_text'] ?? ''));
        update_post_meta($post_id, '_tanish_banner_button_url', esc_url_raw($_POST['tanish_banner_button_url'] ?? ''));
        update_post_meta($post_id, '_tanish_banner_active', sanitize_text_field($_POST['tanish_banner_active'] ?? '1'));
    }

    /* ── Video Meta Boxes ───────────────────────────── */

    public function add_video_meta_boxes(): void {
        add_meta_box(
            'tanish_video_settings',
            'Configuración del Video',
            [$this, 'render_video_meta_box'],
            'tanish_video',
            'normal',
            'high'
        );
    }

    public function render_video_meta_box($post): void {
        wp_nonce_field('tanish_video_meta', 'tanish_video_nonce');

        $video_url    = get_post_meta($post->ID, '_tanish_video_url', true);
        $featured_vid = get_post_meta($post->ID, '_tanish_video_featured', true);
        $active       = get_post_meta($post->ID, '_tanish_video_active', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tanish_video_url">URL del video (YouTube/Vimeo)</label></th>
                <td>
                    <input type="url" id="tanish_video_url" name="tanish_video_url" value="<?php echo esc_url($video_url); ?>" class="regular-text" placeholder="https://www.youtube.com/watch?v=...">
                    <p class="description">Soporta YouTube y Vimeo. Se embebe automáticamente.</p>
                </td>
            </tr>
            <tr>
                <th><label for="tanish_video_featured">Destacado</label></th>
                <td>
                    <select id="tanish_video_featured" name="tanish_video_featured">
                        <option value="1" <?php selected($featured_vid, '1', true); ?>>Sí (video principal)</option>
                        <option value="0" <?php selected($featured_vid, '', true); ?>>No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="tanish_video_active">Activo</label></th>
                <td>
                    <select id="tanish_video_active" name="tanish_video_active">
                        <option value="1" <?php selected($active, '1', true); ?>>Sí</option>
                        <option value="0" <?php selected($active, '', true); ?>>No</option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="description">La imagen se establece desde la imagen destacada (thumbnail del video).</p>
        <?php
    }

    public function save_video_meta(int $post_id, $post): void {
        if (!isset($_POST['tanish_video_nonce']) || !wp_verify_nonce($_POST['tanish_video_nonce'], 'tanish_video_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_tanish_video_url', esc_url_raw($_POST['tanish_video_url'] ?? ''));
        update_post_meta($post_id, '_tanish_video_featured', sanitize_text_field($_POST['tanish_video_featured'] ?? '0'));
        update_post_meta($post_id, '_tanish_video_active', sanitize_text_field($_POST['tanish_video_active'] ?? '1'));
    }
}
