<?php

defined('ABSPATH') || exit;

function tanish_storefront_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    add_theme_support(
        'html5',
        [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]
    );

    add_theme_support('woocommerce');

    register_nav_menus([
        'primary' => __('Menú principal', 'tanish-storefront'),
    ]);
}

add_action('after_setup_theme', 'tanish_storefront_setup');

function tanish_storefront_page_url(string $slug, string $fallback = '/'): string
{
    $page = get_page_by_path($slug);

    if ($page instanceof WP_Post) {
        return (string) get_permalink($page->ID);
    }

    return (string) home_url($fallback);
}

function tanish_storefront_ensure_pretty_permalinks(): void
{
    if ('' === get_option('permalink_structure')) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules(true);
    }
}

add_action('init', 'tanish_storefront_ensure_pretty_permalinks');


function tanish_storefront_assets(): void
{
    $css_path = get_stylesheet_directory() . '/style.css';

    wp_enqueue_style(
        'tanish-storefront-theme',
        get_stylesheet_uri(),
        [],
        file_exists($css_path) ? filemtime($css_path) : wp_get_theme()->get('Version')
    );
}

add_action('wp_enqueue_scripts', 'tanish_storefront_assets');


function tanish_storefront_menu_fallback(): void
{
    ?>
    <ul>
        <li>
            <a href="<?php echo esc_url(home_url('/')); ?>">
                Inicio
            </a>
        </li>

        <?php if (function_exists('wc_get_page_permalink')) : ?>
            <li>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
                    Tienda
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="<?php echo esc_url(tanish_storefront_page_url('nosotros', '/nosotros/')); ?>">
                Nosotros
            </a>
        </li>

        <li>
            <a href="<?php echo esc_url(tanish_storefront_page_url('contacto', '/contacto/')); ?>">
                Contacto
            </a>
        </li>
    </ul>
    <?php
}