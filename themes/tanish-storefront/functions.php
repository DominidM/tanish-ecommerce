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

    $js_path = get_stylesheet_directory() . '/assets/js/storefront.js';

    if (file_exists($js_path)) {
        wp_enqueue_script(
            'tanish-storefront',
            get_stylesheet_directory_uri() . '/assets/js/storefront.js',
            [],
            filemtime($js_path),
            true
        );
    }
}

add_action('wp_enqueue_scripts', 'tanish_storefront_assets');


function tanish_storefront_products_per_page(int $cols): int
{
    return 9;
}
add_filter('loop_shop_per_page', 'tanish_storefront_products_per_page');


function tanish_storefront_wc_translations(string $translation, string $text, string $domain): string
{
    if ($domain !== 'woocommerce') {
        return $translation;
    }

    $map = [
        'Showing all %1$d result' => 'Mostrando todos los %1$d resultados',
        'Showing all %1$d results' => 'Mostrando todos los %1$d resultados',
        'Showing %1$d&ndash;%2$d of %3$d result' => 'Mostrando %1$d–%2$d de %3$d resultados',
        'Showing %1$d&ndash;%2$d of %3$d results' => 'Mostrando %1$d–%2$d de %3$d resultados',
        'Showing the single result' => 'Mostrando el único resultado',
        'Default sorting' => 'Orden predeterminado',
        'Sorted by popularity' => 'Ordenado por popularidad',
        'Sorted by average rating' => 'Ordenado por valoración',
        'Sorted by latest' => 'Ordenado por más recientes',
        'Sorted by price: low to high' => 'Ordenado por precio: menor a mayor',
        'Sorted by price: high to low' => 'Ordenado por precio: mayor a menor',
        'Sort by popularity' => 'Ordenar por popularidad',
        'Sort by average rating' => 'Ordenar por valoración',
        'Sort by latest' => 'Ordenar por más recientes',
        'Sort by price: low to high' => 'Ordenar por precio: menor a mayor',
        'Sort by price: high to low' => 'Ordenar por precio: mayor a menor',
        'Shop order' => 'Orden de tienda',
        'Default' => 'Predeterminado',
        'Popularity' => 'Popularidad',
        'Average rating' => 'Valoración',
        'Latest' => 'Más recientes',
        'Price: low to high' => 'Precio: menor a mayor',
        'Price: high to low' => 'Precio: mayor a menor',
    ];

    return $map[$text] ?? $translation;
}
add_filter('gettext', 'tanish_storefront_wc_translations', 10, 3);
add_filter('ngettext', 'tanish_storefront_wc_translations', 10, 3);


function tanish_storefront_wc_ordering_options(array $options): array
{
    return [
        'menu_order' => 'Orden predeterminado',
        'popularity' => 'Ordenar por popularidad',
        'rating'     => 'Ordenar por valoración',
        'date'       => 'Ordenar por más recientes',
        'price'      => 'Ordenar por precio: menor a mayor',
        'price-desc' => 'Ordenar por precio: mayor a menor',
    ];
}
add_filter('woocommerce_catalog_orderedby', 'tanish_storefront_wc_ordering_options');


function tanish_storefront_shop_subtitle(): void
{
    if (!is_shop()) {
        return;
    }
    echo '<p class="page-description">Explora nuestros productos y consulta disponibilidad.</p>';
}
add_action('woocommerce_before_shop_loop', 'tanish_storefront_shop_subtitle', 1);


function tanish_storefront_custom_product_search(): void
{
    $action = esc_url(home_url('/'));
    ?>
    <form role="search" method="get" class="shop-search-form" action="<?php echo $action; ?>">
        <input type="search" name="s" placeholder="Buscar productos..." value="<?php echo get_search_query(); ?>" aria-label="Buscar productos">
        <input type="hidden" name="post_type" value="product">
        <button type="submit" aria-label="Buscar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </button>
    </form>
    <?php
}


function tanish_storefront_menu_fallback(): void
{
    ?>
    <ul class="menu">
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


/* ── Single Product Page ──────────────────────────────────────────────────── */

/**
 * Remove sidebar on single product pages.
 */
function tanish_storefront_product_no_sidebar(): void
{
    if (is_product()) {
        remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    }
}
add_action('woocommerce_before_main_content', 'tanish_storefront_product_no_sidebar');


/**
 * Render WhatsApp CTA on single product pages.
 * Duplicates plugin logic to avoid instantiating a second Tanish_WhatsApp
 * (which would re-register hooks). The plugin already handles add-to-cart
 * removal and catalog "Ver producto" replacement.
 */
function tanish_storefront_whatsapp_button(): void
{
    if (!is_product()) {
        return;
    }

    global $product;

    if (!is_a($product, 'WC_Product') || !$product->is_type('simple') || !$product->is_in_stock() || !$product->is_purchasable()) {
        return;
    }

    $whatsapp_number = get_option('tanish_whatsapp_number', '');
    $whatsapp_number = preg_replace('/\D/', '', $whatsapp_number);

    if (empty($whatsapp_number)) {
        return;
    }

    $product_name = $product->get_name();
    $sku          = $product->get_sku();
    $price_html   = $product->get_price_html();
    $price_text   = !empty($price_html) ? wp_strip_all_tags($price_html) : 'Consultar';
    $permalink    = get_permalink($product->get_id());

    $lines   = [];
    $lines[] = 'Hola, deseo comprar este producto de TANISH.';
    $lines[] = '';
    $lines[] = 'Producto: ' . $product_name;
    if (!empty($sku)) {
        $lines[] = 'SKU: ' . $sku;
    }
    $lines[] = 'Precio: ' . $price_text;
    $lines[] = 'Enlace: ' . $permalink;
    $lines[] = '';
    $lines[] = '¿Podrían confirmarme disponibilidad y coordinar el pedido?';

    $message         = implode("\n", $lines);
    $encoded_message = rawurlencode($message);
    $whatsapp_url    = 'https://wa.me/' . $whatsapp_number . '?text=' . $encoded_message;

    $svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:6px;margin-top:-2px;">'
         . '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path>'
         . '</svg>';

    echo '<div class="tanish-whatsapp-wrapper">';
    echo '<a href="' . esc_url($whatsapp_url) . '" class="button alt tanish-whatsapp-button" target="_blank" rel="noopener noreferrer">';
    echo $svg;
    echo 'Comprar por WhatsApp';
    echo '</a>';
    echo '</div>';
}


/**
 * Add WhatsApp button below add-to-cart on single product page.
 */
function tanish_storefront_whatsapp_button_hook(): void
{
    if (is_product()) {
        add_action('woocommerce_single_product_summary', 'tanish_storefront_whatsapp_button', 35);
    }
}
add_action('wp', 'tanish_storefront_whatsapp_button_hook');


/**
 * Translate WooCommerce tabs on single product pages.
 */
function tanish_storefront_wc_tabs_translation(array $tabs): array
{
    if (!is_product()) {
        return $tabs;
    }

    if (isset($tabs['description']['title'])) {
        $tabs['description']['title'] = 'Descripción';
    }
    if (isset($tabs['additional_information']['title'])) {
        $tabs['additional_information']['title'] = 'Información adicional';
    }
    if (isset($tabs['reviews']['title'])) {
        $tabs['reviews']['title'] = 'Reseñas (0)';
    }

    return $tabs;
}
add_filter('woocommerce_product_tabs', 'tanish_storefront_wc_tabs_translation');


/**
 * Translate product meta labels (Category, Tags) and Related products.
 * Handles both gettext and ngettext (for _n() singular/plural).
 */
function tanish_storefront_product_meta_translation(string $translation, string $text, string $domain = ''): string
{
    if ($domain !== 'woocommerce') {
        return $translation;
    }

    $map = [
        'Category'          => 'Categoría',
        'Categories'        => 'Categorías',
        'Tag'               => 'Etiqueta',
        'Tags'              => 'Etiquetas',
        'SKU'               => 'SKU',
        'Related products'  => 'Productos relacionados',
    ];

    return $map[$text] ?? $translation;
}


function tanish_storefront_product_meta_ngettext(string $translation, string $single, string $plural, int $number, string $domain): string
{
    if ($domain !== 'woocommerce') {
        return $translation;
    }

    $map = [
        'Category:'  => 'Categoría:',
        'Categories:' => 'Categorías:',
        'Tag:'       => 'Etiqueta:',
        'Tags:'      => 'Etiquetas:',
    ];

    if (isset($map[$translation])) {
        return $map[$translation];
    }

    return $translation;
}
add_filter('gettext', 'tanish_storefront_product_meta_translation', 30, 3);
add_filter('ngettext', 'tanish_storefront_product_meta_ngettext', 30, 5);