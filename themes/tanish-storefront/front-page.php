<?php
defined('ABSPATH') || exit;

get_header();

$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';

/* ── Fetch Banners ──────────────────────────────────── */
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

/* ── Fetch Videos ───────────────────────────────────── */
$videos = new WP_Query([
    'post_type'      => 'tanish_video',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'meta_key'       => '_tanish_video_active',
    'meta_value'     => '1',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

$has_videos = $videos->have_posts();
$featured_video = null;
$side_videos = [];

if ($has_videos) {
    while ($videos->have_posts()) {
        $videos->the_post();
        $is_featured = get_post_meta(get_the_ID(), '_tanish_video_featured', true);
        $video_url = get_post_meta(get_the_ID(), '_tanish_video_url', true);
        $item = [
            'id'         => get_the_ID(),
            'title'      => get_the_title(),
            'url'        => $video_url,
            'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: '',
            'is_featured' => ($is_featured === '1'),
        ];
        if ($item['is_featured'] && !$featured_video) {
            $featured_video = $item;
        } else {
            $side_videos[] = $item;
        }
    }
    wp_reset_postdata();

    // If no featured, use first
    if (!$featured_video && !empty($side_videos)) {
        $featured_video = array_shift($side_videos);
    }
}
?>

<main>

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
                                <?php if ($product->is_in_stock()) : ?>
                                    <span class="stock-badge">Disponible</span>
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

    <!-- BENEFICIOS -->
    <section class="home-section home-section--benefits">
        <div class="tanish-container">
            <div class="benefits" data-reveal="up">

                <div class="benefit">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h3>Stock actualizado</h3>
                    <p>Consulta disponibilidad antes de realizar tu pedido.</p>
                </div>

                <div class="benefit">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                    </div>
                    <h3>Atención directa</h3>
                    <p>Coordina tu compra directamente mediante WhatsApp.</p>
                </div>

                <div class="benefit">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h3>Compra simple</h3>
                    <p>Encuentra tus productos sin procesos innecesariamente complejos.</p>
                </div>

                <div class="benefit">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <h3>Catálogo online</h3>
                    <p>Accede al catálogo desde computadora, tablet o smartphone.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- VIDEOS / NOVEDADES -->
    <?php if ($has_videos && $featured_video) : ?>
        <section class="home-section home-section--videos tanish-videos" data-reveal="up">
            <div class="tanish-container">
                <div class="section-header">
                    <div>
                        <div class="section-eyebrow">Novedades</div>
                        <h2 class="section-title">Mira nuestras novedades</h2>
                        <p class="section-description">Descubre productos, promociones y novedades de TANISH.</p>
                    </div>
                </div>

                <div class="videos-layout">
                    <!-- Main video -->
                    <div class="video-main">
                        <div class="video-main-player" data-video-url="<?php echo esc_url($featured_video['url']); ?>">
                            <?php if ($featured_video['thumbnail']) : ?>
                                <div class="video-poster">
                                    <img src="<?php echo esc_url($featured_video['thumbnail']); ?>" alt="<?php echo esc_attr($featured_video['title']); ?>" loading="lazy">
                                    <button class="video-play-btn" aria-label="Reproducir video">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="video-main-title"><?php echo esc_html($featured_video['title']); ?></h3>
                    </div>

                    <!-- Sidebar videos -->
                    <?php if (!empty($side_videos)) : ?>
                        <div class="video-sidebar">
                            <?php foreach ($side_videos as $sv) : ?>
                                <div class="video-sidebar-item" data-video-url="<?php echo esc_url($sv['url']); ?>" tabindex="0" role="button" aria-label="Reproducir <?php echo esc_attr($sv['title']); ?>">
                                    <div class="video-sidebar-thumb">
                                        <?php if ($sv['thumbnail']) : ?>
                                            <img src="<?php echo esc_url($sv['thumbnail']); ?>" alt="<?php echo esc_attr($sv['title']); ?>" loading="lazy">
                                        <?php endif; ?>
                                        <button class="video-play-btn video-play-btn--small" aria-label="Reproducir">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        </button>
                                    </div>
                                    <div class="video-sidebar-info">
                                        <div class="video-sidebar-title"><?php echo esc_html($sv['title']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php elseif (current_user_can('manage_options')) : ?>
        <!-- Admin hint: no videos -->
        <section class="home-section home-section--videos tanish-videos tanish-videos--admin-hint">
            <div class="tanish-container">
                <div class="admin-video-hint">
                    <p>Agrega videos desde <a href="<?php echo esc_url(admin_url('edit.php?post_type=tanish_video')); ?>">Videos TANISH</a>.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>

    <!-- CTA WHATSAPP -->
    <?php if ($whatsapp_url) : ?>
        <section class="home-section home-section--cta">
            <div class="tanish-container">
                <div class="whatsapp-cta" data-reveal="up">
                    <div>
                        <h2>¿Necesitas ayuda con tu pedido?</h2>
                        <p>Consulta disponibilidad directamente con nosotros.</p>
                    </div>
                    <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">
                        Hablar por WhatsApp
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
