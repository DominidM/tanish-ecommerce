<?php
defined('ABSPATH') || exit;

get_header();
?>

<main>

    <?php get_template_part('ui/hero-carousel'); ?>
    <?php get_template_part('ui/categories'); ?>
    <?php get_template_part('ui/products'); ?>
    <?php get_template_part('ui/promo-banner'); ?>
    <?php get_template_part('ui/benefits'); ?>
    <?php get_template_part('ui/videos'); ?>
    <?php get_template_part('ui/whatsapp-cta'); ?>

</main>

<?php get_footer(); ?>
