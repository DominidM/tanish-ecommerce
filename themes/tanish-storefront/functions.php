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
    add_theme_support('wc-product-gallery-zoom');      // Zoom al pasar el mouse
    add_theme_support('wc-product-gallery-lightbox');  // Lupa: ver la foto en grande
    add_theme_support('wc-product-gallery-slider');    // Carrusel si hay varias fotos

    register_nav_menus([
        'primary' => __('Menú principal', 'tanish-storefront'),
    ]);

    // Barra lateral de la tienda: permite colocar widgets (filtro de precio,
    // categorías, etc.) desde Apariencia > Widgets.
    register_sidebar([
        'name'          => __('Filtros de tienda', 'tanish-storefront'),
        'id'            => 'shop-sidebar',
        'description'   => __('Widgets que se muestran en la barra lateral del catálogo.', 'tanish-storefront'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
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


/**
 * Slug de la pagina de acceso con el estilo de la tienda.
 */
const TANISH_LOGIN_PAGE_SLUG = 'acceso';

/**
 * URL de la pagina de acceso de TANISH.
 *
 * @return string
 */
function tanish_storefront_login_page_url(): string
{
    return tanish_storefront_page_url(TANISH_LOGIN_PAGE_SLUG, '/wp-login.php');
}

/**
 * URL real del formulario de WordPress (wp-login.php).
 *
 * Se usa como "action" del formulario de la pagina de acceso. No pasa por
 * el filtro 'login_url' a proposito: ese filtro devuelve la pagina de acceso
 * y el formulario tiene que enviarse a wp-login.php, que es el motor de
 * autenticacion de WordPress.
 *
 * @return string
 */
function tanish_storefront_login_form_action(): string
{
    return (string) site_url('wp-login.php');
}

/**
 * Desenrolla un redirect_to que en realidad ya es una URL de wp-login.php.
 *
 * El nucleo de WordPress (auth_redirect()) construye en ocasiones
 *   wp-login.php?redirect_to=<destino>&reauth=1
 * y lo pasa como destino. Si lo reenviamos tal cual, la URL queda anidada
 * y es ilegible. Aqui se extrae el destino real.
 *
 * @param string $url URL de destino recibida.
 * @return string
 */
function tanish_storefront_unwrap_login_redirect(string $url): string
{
    if ('' === $url) {
        return $url;
    }

    $parts = wp_parse_url($url);

    if (empty($parts['path']) || !str_contains($parts['path'], 'wp-login.php')) {
        return $url;
    }

    if (empty($parts['query'])) {
        return $url;
    }

    $query = array();
    parse_str((string) $parts['query'], $query);

    if (empty($query['redirect_to'])) {
        return $url;
    }

    return (string) $query['redirect_to'];
}

/**
 * Redirige los enlaces de "Iniciar sesion" a la pagina de acceso de TANISH.
 *
 * WordPress genera URLs como
 *   wp-login.php?redirect_to=https%3A%2F%2F...%2Fwp-admin%2Fadmin.php%3Fpage%3Dtanish-inventory
 * que son dificiles de leer. Con este filtro el visitante aterriza en la
 * pagina de acceso (/acceso) y, tras autenticarse, sigue al destino original.
 *
 * @param string $redirect_to URL de destino tras el inicio de sesion.
 * @param bool   $forced      Si el inicio de sesion fue forzado por el core.
 * @return string
 */
function tanish_storefront_login_url($redirect_to, $forced = false): string
{
    $login_page = get_page_by_path(TANISH_LOGIN_PAGE_SLUG);

    if (!$login_page instanceof WP_Post) {
        return (string) $redirect_to;
    }

    $redirect_to = tanish_storefront_unwrap_login_redirect((string) $redirect_to);

    $url = (string) get_permalink($login_page->ID);

    if ('' !== $redirect_to) {
        $url = add_query_arg('redirect_to', rawurlencode($redirect_to), $url);
    }

    return $url;
}

add_filter('login_url', 'tanish_storefront_login_url', 10, 2);


function tanish_storefront_assets(): void
{
    $css_path = get_stylesheet_directory() . '/style.css';

    wp_enqueue_style(
        'tanish-storefront-theme',
        get_stylesheet_uri(),
        [],
        file_exists($css_path) ? filemtime($css_path) : wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'tanish-poppins',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap',
        [],
        null
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
        'Add to cart'  => 'Agregar al carrito',
        'View cart'    => 'Ver carrito',
        '%s has been added to your cart' => '%s se agregó a tu carrito',
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
// Nota: no se engancha a 'ngettext' con esta firma: ngettext pasa 5
// argumentos ($translation, $single, $plural, $number, $domain).


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


// El conteo de resultados lo imprime WooCommerce (woocommerce_result_count)
// en woocommerce_before_shop_loop. No se duplica con un subtítulo propio.


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
    if (!function_exists('is_product')) {
        return;
    }
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
    if (!function_exists('is_product') || !is_product()) {
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
    // Only run if WooCommerce is active and we're on a product page
    if (!function_exists('is_product')) {
        return;
    }
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
    if (!function_exists('is_product') || !is_product()) {
        return $tabs;
    }

    if (isset($tabs['description']['title'])) {
        $tabs['description']['title'] = 'Descripción';
    }
    if (isset($tabs['additional_information']['title'])) {
        $tabs['additional_information']['title'] = 'Información adicional';
    }
    if (isset($tabs['reviews']['title'])) {
        global $product;

        if (!($product instanceof WC_Product)) {
            $product = wc_get_product(get_the_ID());
        }

        $review_count = $product instanceof WC_Product ? (int) $product->get_review_count() : 0;

        /* translators: %d: número de reseñas del producto. */
        $tabs['reviews']['title'] = sprintf('Reseñas (%d)', $review_count);
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


/**
 * Evita la ruta (breadcrumb) duplicada en la página de producto.
 * El tema ya muestra la suya en woocommerce/single-product.php.
 */
add_action('wp', function () {
    if (function_exists('is_product') && is_product()) {
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
    }
});

/**
 * Galería de producto: flechas para pasar las fotos.
 */
add_filter('woocommerce_single_product_carousel_options', function ($options) {
    $options['directionNav'] = true;   // Flechas < >
    $options['animationLoop'] = true;  // Al llegar a la última, vuelve a la primera
    return $options;
});

/* ================================================
   GOOGLE TAG MANAGER (Sprint 03 - T-19)
   Contenedor GTM-P9CHWGQD en el <head> de todas las páginas.
   (Reemplaza los contenedores anteriores GTM-K9DZWH4C, GTM-W3H9VX9S
   y GTM-MQ2THQTS: solo debe existir UNA etiqueta de Google por página.)
   ================================================ */
add_action('wp_head', function () {
    ?>
<!-- Google Tag Manager -->
<script async src="https://www.googletagmanager.com/gtm.js?id=GTM-P9CHWGQD"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
</script>
<!-- End Google Tag Manager -->
    <?php
}, 1);

// Fallback <noscript> de GTM: se inyecta justo después de <body>
add_action('wp_body_open', function () {
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-P9CHWGQD"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
}, 1);

/* ================================================
   ANALITICA GA4 (UAT-10)
   Eventos de e-commerce empujados a window.dataLayer
   (el contenedor GTM-P9CHWGQD se carga arriba).
   ================================================ */

/**
 * Empuja un evento a la capa de datos de GTM/GA4.
 *
 * @param string $event  Nombre del evento (view_item, add_to_cart...).
 * @param array  $params Parámetros del evento.
 */
function tanish_storefront_ga4_event(string $event, array $params = []): void
{
    if ('' === $event) {
        return;
    }

    $json = wp_json_encode(
        $params,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    if (!is_string($json) || '' === $json) {
        $json = '{}';
    }

    printf(
        "<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(Object.assign(%s,{event:'%s'}));</script>\n",
        $json,
        esc_js($event)
    );
}

/**
 * Productos del carrito en el formato "items" de GA4.
 *
 * @return array
 */
function tanish_storefront_ga4_cart_items(): array
{
    $items = [];

    if (!function_exists('WC') || !WC()->cart) {
        return $items;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'] ?? null;

        if (!($product instanceof WC_Product)) {
            continue;
        }

        $items[] = [
            'item_id'   => (string) $product->get_id(),
            'item_name' => $product->get_name(),
            'sku'       => (string) $product->get_sku(),
            'price'     => (float) $product->get_price(),
            'quantity'  => (int) ($cart_item['quantity'] ?? 1),
        ];
    }

    return $items;
}

/**
 * Evento view_item en la ficha de producto.
 */
function tanish_storefront_ga4_view_item(): void
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    global $product;

    if (!($product instanceof WC_Product)) {
        $product = wc_get_product(get_the_ID());
    }

    if (!($product instanceof WC_Product)) {
        return;
    }

    tanish_storefront_ga4_event('view_item', [
        'currency' => 'PEN',
        'value'    => (float) $product->get_price(),
        'items'    => [
            [
                'item_id'   => (string) $product->get_id(),
                'item_name' => $product->get_name(),
                'sku'       => (string) $product->get_sku(),
                'price'     => (float) $product->get_price(),
                'quantity'  => 1,
            ],
        ],
    ]);
}
add_action('wp_footer', 'tanish_storefront_ga4_view_item', 20);

/**
 * Evento begin_checkout en la página de checkout.
 */
function tanish_storefront_ga4_begin_checkout(): void
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return;
    }

    $items = tanish_storefront_ga4_cart_items();

    if (empty($items)) {
        return;
    }

    $total = 0.0;

    foreach ($items as $item) {
        $total += (float) $item['price'] * (int) $item['quantity'];
    }

    tanish_storefront_ga4_event('begin_checkout', [
        'currency' => 'PEN',
        'value'    => $total,
        'items'    => $items,
    ]);
}
add_action('wp_footer', 'tanish_storefront_ga4_begin_checkout', 20);

/**
 * Evento add_to_cart: escucha el evento jQuery "added_to_cart" que dispara
 * WooCommerce tras el alta por AJAX y recoge los datos del botón.
 */
add_action('wp_footer', function (): void {
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    (function () {
        if (!window.jQuery) { return; }
        window.jQuery(function ($) {
            $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
                window.dataLayer = window.dataLayer || [];

                var $btn = ($button && $button.length) ? $button : $();
                var productId = $btn.data('product_id') || '';
                var sku = $btn.data('product_sku') || '';
                var quantity = parseInt($btn.data('quantity'), 10) || 1;
                var name = $btn.data('product_name') || $btn.attr('aria-label') || '';

                if (!name) {
                    name = $btn.closest('.product').find('.woocommerce-loop-product__title').text() || '';
                }

                var price = 0;
                var $price = $btn.closest('.product, .tanish-product-summary').find('.price .amount').first();

                if ($price.length) {
                    price = parseFloat($price.text().replace(/[^0-9.,]/g, '').replace(',', '.')) || 0;
                }

                window.dataLayer.push({
                    event: 'add_to_cart',
                    currency: 'PEN',
                    value: price * quantity,
                    items: [{
                        item_id: String(productId),
                        item_name: String(name),
                        sku: String(sku),
                        price: price,
                        quantity: quantity
                    }]
                });
            });
        });
    })();
    </script>
    <?php
}, 21);

/**
 * Evento purchase en la página de "gracias" del pedido.
 *
 * Se marca el pedido para no duplicar la compra si el usuario recarga.
 *
 * @param int $order_id ID del pedido.
 */
function tanish_storefront_ga4_purchase(int $order_id): void
{
    if (!function_exists('wc_get_order')) {
        return;
    }

    $order = wc_get_order($order_id);

    if (!($order instanceof WC_Order)) {
        return;
    }

    if ('1' === $order->get_meta('_tanish_ga4_purchase_sent')) {
        return;
    }

    $items = [];

    foreach ($order->get_items() as $item) {
        if (!($item instanceof WC_Order_Item_Product)) {
            continue;
        }

        $product = $item->get_product();

        $items[] = [
            'item_id'   => (string) $item->get_product_id(),
            'item_name' => $item->get_name(),
            'sku'       => $product instanceof WC_Product ? (string) $product->get_sku() : '',
            'price'     => (float) $order->get_item_total($item, false, false),
            'quantity'  => (int) $item->get_quantity(),
        ];
    }

    tanish_storefront_ga4_event('purchase', [
        'transaction_id' => (string) $order->get_order_number(),
        'currency'       => $order->get_currency() ?: 'PEN',
        'value'          => (float) $order->get_total(),
        'tax'            => (float) $order->get_total_tax(),
        'shipping'       => (float) $order->get_shipping_total(),
        'items'          => $items,
    ]);

    $order->update_meta_data('_tanish_ga4_purchase_sent', '1');
    $order->save();
}
add_action('woocommerce_thankyou', 'tanish_storefront_ga4_purchase', 10, 1);

/* ================================================
   CORREOS HTML (Contacto / Libro de Reclamaciones)
   Plantilla con logo e identidad de la marca.
   ================================================ */
require_once get_template_directory() . '/inc/email-template.php';

/* ================================================
   CARRITO LATERAL (drawer)
   Render reusable del contador y del contenido del
   drawer + fragments para refrescarlo tras un
   "add to cart" por AJAX.
   ================================================ */
require_once get_template_directory() . '/inc/cart-drawer.php';

/* ================================================
   OPTIMIZACION DE CARGA (LCP)
   Preload de la imagen principal del producto,
   lazy loading en la galeria y prefetch de la
   navegacion.
   ================================================ */
require_once get_template_directory() . '/inc/performance.php';

/* ================================================
   TOAST DE CONFIRMACIÓN (Contacto / Reclamos)
   Redirige al inicio tras enviar un formulario y muestra
   un aviso arriba a la derecha (patrón PRG).
   ================================================ */

function tanish_form_success_redirect(string $message): void
{
    $token = wp_generate_password(24, false, false);
    set_transient('tanish_toast_' . $token, $message, 2 * MINUTE_IN_SECONDS);

    $url = add_query_arg('tanish_toast', $token, home_url('/'));

    wp_safe_redirect($url, 303);
    exit;
}

add_action('wp_footer', function (): void {
    if (empty($_GET['tanish_toast'])) {
        return;
    }

    $token   = sanitize_key(wp_unslash((string) $_GET['tanish_toast']));
    $message = get_transient('tanish_toast_' . $token);

    if (!is_string($message) || $message === '') {
        return;
    }

    delete_transient('tanish_toast_' . $token);
    ?>
    <div class="tanish-toast" id="tanish-toast" role="status" aria-live="polite">
        <span class="tanish-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <span class="tanish-toast-text"><?php echo esc_html($message); ?></span>
        <button type="button" class="tanish-toast-close" aria-label="Cerrar">&times;</button>
    </div>
    <script>
    (function () {
        var toast = document.getElementById('tanish-toast');
        if (!toast) { return; }

        function dismiss() {
            toast.classList.add('tanish-toast--hide');
            setTimeout(function () { toast.remove(); }, 400);
        }

        toast.querySelector('.tanish-toast-close').addEventListener('click', dismiss);
        setTimeout(dismiss, 6000);

        // Limpia el parámetro de la URL sin recargar.
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.delete('tanish_toast');
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
        }
    })();
    </script>
    <?php
}, 99);
