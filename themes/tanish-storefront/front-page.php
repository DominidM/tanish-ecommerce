<?php
defined('ABSPATH') || exit;

get_header();
?>

<main>

    <?php
    $page_title = 'Inicio';
    $page_subtitle = 'Bienvenido a TANISH';
    get_template_part('layout/page-hero');
    ?>
    <?php get_template_part('ui/hero-carousel'); ?>
    <?php get_template_part('ui/categories'); ?>
    <?php get_template_part('ui/products'); ?>
    <?php get_template_part('ui/promo-banner'); ?>
    <?php get_template_part('ui/benefits'); ?>
    <?php get_template_part('ui/videos'); ?>
    <?php get_template_part('ui/whatsapp-cta'); ?>

</main>

<?php get_footer(); ?>
