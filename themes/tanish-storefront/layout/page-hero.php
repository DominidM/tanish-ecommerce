<?php
if (is_front_page()) {
    $page_title = 'Inicio';
    $page_subtitle = 'Productos para tu día a día. Consulta precios y disponibilidad en nuestro catálogo.';
} elseif (is_shop()) {
    $page_title = 'Tienda';
    $page_subtitle = 'Descubre nuestra amplia variedad de productos para el hogar y el día a día. Precios actualizados y disponibilidad al instante.';
} elseif (is_page()) {
    $page_title = get_the_title();
    $page_subtitle = get_the_excerpt();
} elseif (is_product()) {
    $page_title = get_the_title();
    $page_subtitle = '';
} else {
    $page_title = get_the_title();
    $page_subtitle = '';
}
?>

<section class="page-hero">
    <div class="tanish-container">
        <h1 class="page-hero-title"><?php echo esc_html($page_title); ?></h1>
        <?php if ($page_subtitle) : ?>
            <p class="page-hero-subtitle"><?php echo esc_html($page_subtitle); ?></p>
        <?php endif; ?>
    </div>
</section>
