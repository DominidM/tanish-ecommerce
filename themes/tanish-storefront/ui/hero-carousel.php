<?php
/**
 * Hero de la portada.
 *
 * Si existe un banner activo del CPT "tanish_banner", el hero usa su imagen
 * destacada, su etiqueta superior, el título del banner y su botón. Si no,
 * se mantiene el hero estático de siempre.
 */

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';

$banner = new WP_Query([
    'post_type'      => 'tanish_banner',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'no_found_rows'  => true,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => '_tanish_banner_active',
            'value'   => '1',
            'compare' => '=',
        ],
    ],
]);

$hero_banner = null;

if ($banner->have_posts()) {
    $banner->the_post();

    $hero_banner = [
        'title'       => get_the_title(),
        'image'       => (string) get_the_post_thumbnail_url(get_the_ID(), 'full'),
        'eyebrow'     => (string) get_post_meta(get_the_ID(), '_tanish_banner_eyebrow', true),
        'description' => (string) get_the_excerpt(),
        'button_text' => (string) get_post_meta(get_the_ID(), '_tanish_banner_button_text', true),
        'button_url'  => (string) get_post_meta(get_the_ID(), '_tanish_banner_button_url', true),
    ];

    wp_reset_postdata();
}

// El PNG original pesa 1.6 MB. Cloudinary lo sirve recomprimido y
// reducido a 1600 px de ancho (121 KB) sin perdida visible a pantalla.
$tanish_hero_bg = $hero_banner && $hero_banner['image'] !== ''
    ? $hero_banner['image']
    : 'https://res.cloudinary.com/dp1vgjhsq/image/upload/w_1600,q_auto,f_auto/v1790031238/banner-hero_qyfjoh.png';
?>

<!-- HERO -->
<link rel="preload" as="image" href="<?php echo esc_url($tanish_hero_bg); ?>" fetchpriority="high">
<section class="tanish-hero" aria-label="Banner principal">
    <div class="tanish-hero-bg" style="background-image: url('<?php echo esc_url($tanish_hero_bg); ?>');"></div>
    <div class="tanish-hero-overlay"></div>
    <div class="tanish-container">
        <div class="tanish-hero-inner">
            <?php if ($hero_banner) : ?>

                <?php if ($hero_banner['eyebrow'] !== '') : ?>
                    <p class="section-eyebrow"><?php echo esc_html($hero_banner['eyebrow']); ?></p>
                <?php endif; ?>

                <h1 class="tanish-hero-title"><?php echo esc_html($hero_banner['title']); ?></h1>

                <?php if ($hero_banner['description'] !== '') : ?>
                    <p class="tanish-hero-desc"><?php echo esc_html($hero_banner['description']); ?></p>
                <?php endif; ?>

                <div class="tanish-hero-actions">
                    <?php if ($hero_banner['button_text'] !== '' && $hero_banner['button_url'] !== '') : ?>
                        <a href="<?php echo esc_url($hero_banner['button_url']); ?>" class="btn btn-outline-white"><?php echo esc_html($hero_banner['button_text']); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-outline-white">Ver catálogo</a>
                </div>

            <?php else : ?>

                <h1 class="tanish-hero-title">TANISH</h1>
                <p class="tanish-hero-desc">Productos de calidad a precios accesibles. Consulta nuestro catálogo, precios y disponibilidad. Atención directa por WhatsApp para coordinar tu pedido con transparencia y rapidez.</p>
                <div class="tanish-hero-actions">
                    <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-outline-white">Ver catálogo</a>
                    <a href="<?php echo esc_url(tanish_storefront_page_url('nosotros', '/nosotros/')); ?>" class="btn btn-outline-white">Ver nuestra historia</a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</section>
