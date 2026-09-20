<!-- CART SIDEBAR -->
<div class="cart-overlay" id="cart-overlay"></div>
<aside class="cart-sidebar" id="cart-sidebar">
    <div class="cart-sidebar-header">
        <h3>Carrito</h3>
        <button class="cart-close" id="cart-close" aria-label="Cerrar carrito">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

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
</aside>
