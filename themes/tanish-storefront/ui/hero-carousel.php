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
<section class="tanish-hero" aria-label="Banner principal">
    <div class="tanish-hero-bg" style="background-image: url('https://res.cloudinary.com/dp1vgjhsq/image/upload/v1790031238/banner-hero_qyfjoh.png');"></div>
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
