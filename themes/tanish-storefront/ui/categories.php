<!-- CATEGORÍAS -->
<section class="home-section home-section--categories">
    <div class="tanish-container">
        <div class="section-header" data-reveal="up">
            <div>
                <div class="section-eyebrow">Catálogo</div>
                <h2 class="section-title">Compra por categoría</h2>
                <p class="section-description">Encuentra rápidamente lo que necesitas.</p>
            </div>
        </div>
        <div class="category-grid">
            <?php
            $categories = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'parent'     => 0,
                'number'     => 4,
            ]);

            if (!is_wp_error($categories)) :
                $i = 0;
                foreach ($categories as $category) :
                    $link = get_term_link($category);
                    if (is_wp_error($link)) continue;

                    $thumb_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    $has_thumb = !empty($thumb_id);
                    ?>
                    <a href="<?php echo esc_url($link); ?>" class="category-card" data-reveal="up" style="--reveal-delay: <?php echo esc_attr($i * 80); ?>ms">
                        <div class="category-image">
                            <?php if ($has_thumb) : ?>
                                <?php echo wp_get_attachment_image($thumb_id, 'woocommerce_thumbnail', false, [
                                    'class'   => 'category-img',
                                    'loading' => ($i < 2) ? 'eager' : 'lazy',
                                ]); ?>
                            <?php else : ?>
                                <div class="category-placeholder">
                                    <span><?php echo esc_html(mb_strtoupper(mb_substr($category->name, 0, 1))); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="category-text">
                            <div class="category-name"><?php echo esc_html($category->name); ?></div>
                            <div class="category-count"><?php echo esc_html($category->count); ?> productos</div>
                        </div>
                    </a>
                <?php
                    $i++;
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>
