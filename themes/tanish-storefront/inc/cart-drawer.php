<?php
/**
 * Carrito lateral (drawer) del header.
 *
 * El markup queda dividido en dos piezas reutilizables para que WooCommerce
 * pueda refrescarlas vía fragments después de un "add to cart" por AJAX:
 *
 *   - #cart-count          -> contador del header
 *   - #cart-drawer-content -> cuerpo + footer del drawer
 *
 * @package Tanish_Storefront
 */

defined('ABSPATH') || exit;

/**
 * Contador de productos del carrito en el header.
 */
function tanish_render_cart_count(): void
{
    $count = 0;

    if (function_exists('WC') && WC()->cart) {
        $count = (int) WC()->cart->get_cart_contents_count();
    }

    echo '<span class="cart-count" id="cart-count">' . esc_html($count) . '</span>';
}

/**
 * Contenido dinámico del drawer: lista de productos + total.
 * Se envuelve en #cart-drawer-content para poder reemplazarlo por fragmento.
 */
function tanish_render_cart_drawer_content(): void
{
    ?>
    <div id="cart-drawer-content">
        <div class="cart-sidebar-body" id="cart-body">
            <?php if (function_exists('WC') && WC()->cart) :
                $cart_items = WC()->cart->get_cart();
                if (!empty($cart_items)) :
            ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $cart_item) :
                        $product = $cart_item['data'];
                        $product_id = $cart_item['product_id'];
                        $quantity = $cart_item['quantity'];
                        $thumbnail = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail');
                        $permalink = get_permalink($product_id);
                    ?>
                        <div class="cart-item">
                            <a href="<?php echo esc_url($permalink); ?>" class="cart-item-img">
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
                            </a>
                            <div class="cart-item-info">
                                <a href="<?php echo esc_url($permalink); ?>" class="cart-item-name"><?php echo esc_html($product->get_name()); ?></a>
                                <div class="cart-item-qty">Cant: <?php echo esc_html($quantity); ?></div>
                                <div class="cart-item-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="cart-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <p>Tu carrito está vacío</p>
                </div>
            <?php endif; ?>
            <?php else : ?>
                <div class="cart-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <p>Tu carrito está vacío</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (function_exists('WC') && WC()->cart && WC()->cart->get_cart_contents_count() > 0) : ?>
            <div class="cart-sidebar-footer">
                <div class="cart-total">
                    <span>Total</span>
                    <span><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></span>
                </div>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="btn btn-primary cart-checkout-btn">Ver carrito</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Fragmentos que WooCommerce reemplaza tras un alta de producto por AJAX.
 * Sin esto, el contador del header y el drawer quedarían desactualizados.
 */
add_filter('woocommerce_add_to_cart_fragments', function (array $fragments): array {
    ob_start();
    tanish_render_cart_count();
    $fragments['#cart-count'] = ob_get_clean();

    ob_start();
    tanish_render_cart_drawer_content();
    $fragments['#cart-drawer-content'] = ob_get_clean();

    return $fragments;
});
