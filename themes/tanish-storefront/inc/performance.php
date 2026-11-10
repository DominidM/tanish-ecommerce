<?php
/**
 * Optimizacion de carga (LCP) de la pagina de producto.
 *
 *-Objectivo: que la imagen principal del producto aparezca antes y que la
 *  navegacion hacia la tienda no espere a que se descargue todo.
 *
 * - La imagen principal se precarga en el <head> y se marca como prioritaria
 *   (loading="eager" + fetchpriority="high"). Las demas imagenes de la
 *   galeria se aplazan (loading="lazy" + decoding="async").
 * - Se anticipa la descarga de la pagina de tienda para que al pulsar
 *   "Tienda" la pagina este ya casi lista.
 *
 * @package Tanish_Storefront
 */

defined('ABSPATH') || exit;

/**
 * URL de la imagen principal del producto en la pagina actual.
 *
 * @return string URL vacia si no hay producto en pantalla.
 */
function tanish_storefront_main_product_image_url(): string
{
    if (!function_exists('is_product') || !is_product()) {
        return '';
    }

    global $product;

    if (!($product instanceof WC_Product)) {
        $product = wc_get_product();
    }

    if (!($product instanceof WC_Product)) {
        return '';
    }

    $image_id = (int) $product->get_image_id();

    if (!$image_id) {
        return '';
    }

    // "woocommerce_single" es el tamano que usa la galería del producto.
    $src = wp_get_attachment_image_src($image_id, 'woocommerce_single');

    if (empty($src[0])) {
        $src = wp_get_attachment_image_src($image_id, 'large');
    }

    return empty($src[0]) ? '' : (string) $src[0];
}

/**
 * Preload de la imagen principal del producto.
 *
 * Se imprime en el <head> para que el navegador la empiece a descargar
 * antes de recibir el resto del HTML.
 */
function tanish_storefront_preload_product_image(): void
{
    if (function_exists('is_admin') && is_admin()) {
        return;
    }

    $url = tanish_storefront_main_product_image_url();

    if ('' === $url) {
        return;
    }

    // No duplicar el preload si otro plugin ya lo hizo.
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    printf(
        '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
        esc_url($url)
    );
}
add_action('wp_head', 'tanish_storefront_preload_product_image', 2);

/**
 * Marca la imagen principal como prioritaria y aplaza las secundarias.
 *
 * WooCommerce 11 pasa por wp_get_attachment_image(), que ya anade
 * fetchpriority="high" a la imagen principal, pero no controla el atributo
 * loading de cada imagen de la galeria.
 */
function tanish_storefront_gallery_image_params(array $params, int $attachment_id, string $image_size, bool $main_image): array
{
    if ($main_image) {
        $params['loading'] = 'eager';
        $params['fetchpriority'] = 'high';
        $params['decoding'] = 'sync';
    } else {
        $params['loading'] = 'lazy';
        $params['decoding'] = 'async';
    }

    return $params;
}
add_filter('woocommerce_gallery_image_html_attachment_image_params', 'tanish_storefront_gallery_image_params', 10, 4);

/**
 * Anticipa la carga de la tienda y del contacto.
 *
 * "prefetch" es de baja prioridad: no compite con los recursos criticos de
 * la pagina actual, pero deja la tienda casi lista cuando el usuario pulsa
 * el enlace del menu.
 */
function tanish_storefront_prefetch_navigation(): void
{
    if (function_exists('is_admin') && is_admin()) {
        return;
    }

    $targets = array(
        home_url('/shop/'),
        home_url('/contacto/'),
    );

    foreach ($targets as $url) {
        printf('<link rel="prefetch" href="%s">' . "\n", esc_url($url));
    }
}
add_action('wp_head', 'tanish_storefront_prefetch_navigation', 3);

/*
 * Nota: se probo retirar los scripts de los bloques de carrito/checkout en
 * las paginas donde no hacen falta, pero resulto contraproducente.
 * "wc-settings" y "wc-price-format" son dependencias transversales de todos
 * los bloques (wc-blocks-registry, wc-blocks-components, wc-blocks-checkout).
 * Al deregistrarlos, los gateways de pago se desactivaban en el Carrito y el
 * Checkout con el aviso "Payment gateway ... has been deactivated because
 * its dependency 'wc-settings' is missing".
 *
 * El peso real de la pagina estaba en las imagenes, ya optimizadas.
 */
