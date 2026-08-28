<?php

defined('ABSPATH') || exit;

get_header();

?>

<main class="page-main">

    <div class="tanish-container">

        <?php while (have_posts()) : the_post(); ?>

            <article>

                <h1 class="page-title">
                    <?php the_title(); ?>
                </h1>

                <div>
                    <?php the_content(); ?>
                </div>

            </article>

        <?php endwhile; ?>

    </div>

</main>

<?php get_footer(); ?>