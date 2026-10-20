<?php
/**
 * Template Name: Nosotros
 * Description: Página "Nosotros" de TANISH
 */

defined('ABSPATH') || exit;

get_header();

// Imagen de "Nosotros": la imagen destacada de la página.
$tanish_page_id = get_queried_object_id();

// Imagen de "Nuestra Historia": una imagen en Medios con el título "historia".
$tanish_historia = get_posts(array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'title'          => 'historia',
    'posts_per_page' => 1,
    'fields'         => 'ids',
));
$tanish_historia_id = !empty($tanish_historia) ? (int) $tanish_historia[0] : 0;
?>

<main class="page-main">

    <?php get_template_part('layout/page-hero'); ?>

    <!-- SECCIÓN 1: Descripción + Imagen -->
    <section class="nosotros-section">
        <div class="tanish-container">
            <div class="nosotros-grid">
                <div class="nosotros-content">
                    <h2 class="nosotros-title">Nosotros</h2>
                    <p class="nosotros-text">
                        En <strong>TANISH S.A.C.</strong> creemos que tener los útiles correctos hace la diferencia, ya sea para el colegio, la universidad, la oficina o tus manualidades.
                    </p>
                    <p class="nosotros-text">
                        Ofrecemos cuadernos, lápices, lapiceros, papel crepé y mucho más, de marcas confiables y a precios accesibles. Revisa nuestro catálogo online y coordina tu pedido directamente por WhatsApp: te atendemos de forma rápida, clara y personalizada.
                    </p>
                </div>

                <?php if (has_post_thumbnail($tanish_page_id)) : ?>
                    <div class="nosotros-image">
                        <?php echo get_the_post_thumbnail($tanish_page_id, 'large', array('loading' => 'lazy')); ?>
                    </div>
                <?php else : ?>
                    <div class="nosotros-image-placeholder">
                        <span>Próximamente</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 2: Quiénes Somos -->
    <section class="nosotros-section">
        <div class="tanish-container">
            <div class="nosotros-content">
                <h2 class="nosotros-title">Quiénes Somos</h2>
                <p class="nosotros-text">
                    Somos una tienda especializada en útiles escolares y de oficina. Trabajamos con stock actualizado, así que lo que ves en la tienda está disponible para coordinar su entrega inmediata.
                </p>

                <div class="nosotros-mv-row">
                    <div class="nosotros-mv-card">
                        <h3 class="nosotros-subtitle">Misión</h3>
                        <p class="nosotros-text">Facilitar a estudiantes, familias y empresas el acceso a útiles de calidad a precios justos, con una atención cercana y pedidos fáciles de coordinar.</p>
                    </div>
                    <div class="nosotros-mv-card">
                        <h3 class="nosotros-subtitle">Visión</h3>
                        <p class="nosotros-text">Ser la tienda de útiles de confianza en el Perú, reconocida por su variedad, su stock siempre disponible y su atención personalizada.</p>
                    </div>
                    <div class="nosotros-mv-card">
                        <h3 class="nosotros-subtitle">Valores</h3>
                        <ul class="nosotros-list">
                            <li>Calidad</li>
                            <li>Transparencia</li>
                            <li>Atención personalizada</li>
                            <li>Compromiso con el cliente</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 3: Nuestra Historia + Imagen -->
    <section class="nosotros-section">
        <div class="tanish-container">
            <div class="nosotros-grid nosotros-grid--reverse">
                <div class="nosotros-content">
                    <h2 class="nosotros-title">Nuestra Historia</h2>
                    <p class="nosotros-text">
                        Nacimos con una idea simple: que comprar útiles sea fácil, rápido y sin sorpresas. Por eso armamos un catálogo online con stock real y una atención directa por WhatsApp.
                    </p>
                    <p class="nosotros-text">
                        Hoy seguimos creciendo con la misma dedicación del primer día, coordinando cada pedido con transparencia para que tengas todo listo a tiempo, sea inicio de clases o un proyecto de último minuto.
                    </p>
                </div>

                <?php if ($tanish_historia_id) : ?>
                    <div class="nosotros-image">
                        <?php echo wp_get_attachment_image($tanish_historia_id, 'large', false, array('loading' => 'lazy')); ?>
                    </div>
                <?php else : ?>
                    <div class="nosotros-image-placeholder">
                        <span>Próximamente</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 4: Beneficios -->
    <?php get_template_part('ui/benefits'); ?>

</main>

<?php get_footer(); ?>