<?php
/**
 * Template Name: Nosotros
 * Description: Página "Nosotros" de TANISH
 */

defined('ABSPATH') || exit;

get_header();
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
                        <strong>TANISH S.A.C.</strong> es una empresa comprometida con brindar productos de calidad a precios accesibles. Nuestro catálogo está disponible online y la atención es directa por WhatsApp para coordinar cada pedido con transparencia y rapidez.
                    </p>
                    <p class="nosotros-text">
                        Trabajamos con stock actualizado y marcas confiables. Cada producto que ves en la tienda está disponible para coordinar su entrega inmediata.
                    </p>
                </div>
                <div class="nosotros-image-placeholder">
                    <span>Próximamente</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 2: Quiénes Somos + Imagen -->
    <section class="nosotros-section">
        <div class="tanish-container">
            <div class="nosotros-content">
                <h2 class="nosotros-title">Quiénes Somos</h2>
                <p class="nosotros-text">
                    TANISH es una empresa comprometida con brindar productos de calidad a precios accesibles. Nos especializamos en ofrecer un catálogo completo con stock actualizado y marcas confiables.
                </p>

                <div class="nosotros-mv-row">
                    <div class="nosotros-mv-card">
                        <h3 class="nosotros-subtitle">Misión</h3>
                        <p class="nosotros-text">Ofrecer productos de alta calidad con atención personalizada, facilitando a nuestros clientes la adquisición de lo que necesitan de forma rápida y transparente.</p>
                    </div>
                    <div class="nosotros-mv-card">
                        <h3 class="nosotros-subtitle">Visión</h3>
                        <p class="nosotros-text">Ser la empresa de comercialización líder en el mercado peruano, reconocida por nuestra atención personalizada, stock actualizado y compromiso con el cliente.</p>
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
                        Nacimos con la visión de ofrecer productos accesibles y de calidad para el mercado peruano. Desde nuestros inicios nos hemos enfocado en brindar un catálogo completo con stock actualizado y marcas confiables.
                    </p>
                    <p class="nosotros-text">
                        Hoy atendemos a clientes de todo el Perú con la misma pasión y dedicación con la que comenzamos, coordinando cada pedido con transparencia y rapidez a través de WhatsApp.
                    </p>
                </div>
                <div class="nosotros-image-placeholder">
                    <span>Próximamente</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 4: Beneficios -->
    <?php get_template_part('ui/benefits'); ?>

</main>

<?php get_footer(); ?>
