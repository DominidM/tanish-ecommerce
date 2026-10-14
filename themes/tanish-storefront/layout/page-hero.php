<?php
if (is_front_page()) {
    $page_title    = 'Inicio';
    $page_subtitle = 'Útiles escolares y de oficina para tu día a día. Consulta precios y disponibilidad en nuestro catálogo.';
    $banner_key    = 'inicio';
} elseif (function_exists('is_shop') && is_shop()) {
    $page_title    = 'Tienda';
    $page_subtitle = 'Cuadernos, lápices, lapiceros, papel crepé y mucho más. Precios actualizados y stock disponible.';
    $banner_key    = 'tienda';
} elseif (is_page('nosotros')) {
    $page_title    = 'Nosotros';
    $page_subtitle = 'Conoce más sobre TANISH, nuestra misión, visión y valores.';
    $banner_key    = 'nosotros';
} elseif (is_page('contacto')) {
    $page_title    = 'Contacto';
    $page_subtitle = 'Estamos aquí para ayudarte. Escríbenos por WhatsApp o completa el formulario.';
    $banner_key    = 'contacto';
} elseif (function_exists('is_product') && is_product()) {
    $page_title    = get_the_title();
    $page_subtitle = '';
    $banner_key    = '';
} elseif (is_page()) {
    $page_title    = get_the_title();
    $page_subtitle = get_the_excerpt();
    $banner_key    = get_post_field('post_name', get_queried_object_id());
} else {
    $page_title    = get_the_title();
    $page_subtitle = '';
    $banner_key    = '';
}

// Busca en Medios una imagen con el título "banner-<página>" (ej: banner-nosotros).
$banner_url = '';
if ($banner_key) {
    $banner = get_posts(array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'title'          => 'banner-' . $banner_key,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));
    if (!empty($banner)) {
        $banner_url = wp_get_attachment_image_url((int) $banner[0], 'full');
    }
}
?>

<section class="page-hero<?php echo $banner_url ? ' page-hero--image' : ''; ?>"
    <?php if ($banner_url) : ?>style="background-image: url('<?php echo esc_url($banner_url); ?>');"<?php endif; ?>>
    <div class="tanish-container">
        <h1 class="page-hero-title"><?php echo esc_html($page_title); ?></h1>
        <?php if ($page_subtitle) : ?>
            <p class="page-hero-subtitle"><?php echo esc_html($page_subtitle); ?></p>
        <?php endif; ?>
    </div>
</section>