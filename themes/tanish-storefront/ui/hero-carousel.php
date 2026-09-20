<?php
$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';

$banners = new WP_Query([
    'post_type'      => 'tanish_banner',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'meta_key'       => '_tanish_banner_active',
    'meta_value'     => '1',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

$has_banners = $banners->have_posts();
?>

<!-- HERO CAROUSEL -->
<section class="tanish-hero" aria-label="Banner principal">
    <?php if ($has_banners) : ?>
        <div class="tanish-slider">
            <?php
            $banner_index = 0;
            while ($banners->have_posts()) : $banners->the_post();
                $eyebrow     = get_post_meta(get_the_ID(), '_tanish_banner_eyebrow', true);
                $button_text = get_post_meta(get_the_ID(), '_tanish_banner_button_text', true);
                $button_url  = get_post_meta(get_the_ID(), '_tanish_banner_button_url', true);
                $image_id    = get_post_thumbnail_id();
                $has_image   = !empty($image_id);
            ?>
                <article class="tanish-slide <?php echo $banner_index === 0 ? 'is-active' : ''; ?>" data-index="<?php echo esc_attr($banner_index); ?>">
                    <div class="tanish-container tanish-slide-inner">
                        <div class="tanish-slide-content">
                            <?php if ($eyebrow) : ?>
                                <span class="tanish-slide-eyebrow"><?php echo esc_html($eyebrow); ?></span>
                            <?php endif; ?>
                            <h1 class="tanish-slide-title"><?php the_title(); ?></h1>
                            <?php if (get_the_content()) : ?>
                                <p class="tanish-slide-desc"><?php echo esc_html(get_the_content()); ?></p>
                            <?php endif; ?>
                            <?php if ($button_text && $button_url) : ?>
                                <div class="tanish-slide-actions">
                                    <a href="<?php echo esc_url($button_url); ?>" class="btn btn-primary">
                                        <?php echo esc_html($button_text); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_image) : ?>
                            <div class="tanish-slide-visual">
                                <?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'tanish-slide-img']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php
                $banner_index++;
            endwhile;
            wp_reset_postdata();
            ?>

            <?php if ($banner_index > 1) : ?>
                <button class="tanish-arrow tanish-arrow--prev" aria-label="Banner anterior">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="tanish-arrow tanish-arrow--next" aria-label="Banner siguiente">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <div class="tanish-dots" role="tablist"></div>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <!-- Fallback: no banners configured -->
        <div class="tanish-slide is-active tanish-slide--fallback">
            <div class="tanish-container tanish-slide-inner">
                <div class="tanish-slide-content">
                    <h1 class="tanish-slide-title">Productos para tu<br>día a día</h1>
                    <p class="tanish-slide-desc">Consulta precios y disponibilidad en nuestro catálogo.</p>
                    <div class="tanish-slide-actions">
                        <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-primary">Ver catálogo</a>
                        <?php if ($whatsapp_url) : ?>
                            <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">Hablar por WhatsApp</a>
                        <?php endif; ?>
                    </div>
                    <?php if (current_user_can('manage_options')) : ?>
                        <p class="tanish-slide-admin-hint">Configura tus banners desde <strong>Banners TANISH</strong></p>
                    <?php endif; ?>
                </div>
                <div class="tanish-slide-visual tanish-slide-visual--fallback">
                    <div class="tanish-slide-placeholder">
                        <span class="tanish-slide-placeholder-brand">TANISH</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
