<?php
$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';
?>

<!-- HERO -->
<?php
// El PNG original pesa 1.6 MB. Cloudinary lo sirve recomprimido y
// reducido a 1600 px de ancho (121 KB) sin perdida visible a pantalla.
$tanish_hero_bg = 'https://res.cloudinary.com/dp1vgjhsq/image/upload/w_1600,q_auto,f_auto/v1790031238/banner-hero_qyfjoh.png';
?>
<link rel="preload" as="image" href="<?php echo esc_url($tanish_hero_bg); ?>" fetchpriority="high">
<section class="tanish-hero" aria-label="Banner principal">
    <div class="tanish-hero-bg" style="background-image: url('<?php echo esc_url($tanish_hero_bg); ?>');"></div>
    <div class="tanish-hero-overlay"></div>
    <div class="tanish-container">
        <div class="tanish-hero-inner">
            <h1 class="tanish-hero-title">TANISH</h1>
            <p class="tanish-hero-desc">Productos de calidad a precios accesibles. Consulta nuestro catálogo, precios y disponibilidad. Atención directa por WhatsApp para coordinar tu pedido con transparencia y rapidez.</p>
            <div class="tanish-hero-actions">
                <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-outline-white">Ver catálogo</a>
                <a href="<?php echo esc_url(tanish_storefront_page_url('nosotros', '/nosotros/')); ?>" class="btn btn-outline-white">Ver nuestra historia</a>
            </div>
        </div>
    </div>
</section>
