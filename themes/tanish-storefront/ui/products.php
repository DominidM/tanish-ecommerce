<?php
$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');
?>

<!-- PRODUCTOS -->
<section class="home-section home-section--products">
    <div class="tanish-container">
        <div class="section-header" data-reveal="up">
            <div>
                <div class="section-eyebrow">Productos</div>
                <h2 class="section-title">Productos disponibles</h2>
                <p class="section-description">Stock actualizado directamente desde nuestro catálogo.</p>
            </div>
            <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-secondary">
                Ver catálogo
            </a>
        </div>
        <div class="tanish-products">
            <?php
            if (class_exists('WooCommerce')) :
                $products = wc_get_products([
                    'status'  => 'publish',
                    'limit'   => 8,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                ]);

                $i = 0;
                foreach ($products as $product) :
                    $product_url = get_permalink($product->get_id());
                    ?>
                    <article class="product-card" data-reveal="up" style="--reveal-delay: <?php echo esc_attr($i * 70); ?>ms">
                        <a class="product-image" href="<?php echo esc_url($product_url); ?>">
                            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
                            <?php if ($product->is_in_stock()) :
                                $stock_qty = $product->get_stock_quantity();
                                if ($stock_qty !== null && $stock_qty <= 5) : ?>
                                    <span class="stock-badge stock-badge--low">Stock bajo</span>
                                <?php else : ?>
                                    <span class="stock-badge">Disponible</span>
                                <?php endif;
                            else : ?>
                                <span class="stock-badge stock-badge--out">Agotado</span>
                            <?php endif; ?>
                        </a>
                        <div class="product-body">
                            <h3 class="product-name">
                                <a href="<?php echo esc_url($product_url); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <?php echo wp_kses_post($product->get_price_html()); ?>
                            </div>
                            <div class="product-action">
                                <a href="<?php echo esc_url($product_url); ?>" class="btn-link">
                                    Ver producto &rarr;
                                </a>
                            </div>
                        </div>
                    </article>
                <?php
                    $i++;
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>
