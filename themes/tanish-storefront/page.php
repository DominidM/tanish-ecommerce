<?php

defined('ABSPATH') || exit;

get_header();

?>

<main class="page-main">

    <?php get_template_part('layout/page-hero'); ?>

    <div class="tanish-container">

        <?php while (have_posts()) : the_post(); ?>

            <article>
                <div>
                    <?php the_content(); ?>
                </div>
            </article>

        <?php endwhile; ?>

    </div>

</main>

<?php get_footer(); ?>