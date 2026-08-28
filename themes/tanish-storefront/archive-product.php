<?php

defined('ABSPATH') || exit;

get_header();

?>

<main id="main" class="site-main tanish-shop-main" role="main">

    <div class="tanish-container">

        <div class="tanish-shop-layout">

            <aside class="shop-sidebar" aria-label="Filtros del catálogo">

                <?php if (is_active_sidebar('shop-sidebar')) : ?>

                    <?php dynamic_sidebar('shop-sidebar'); ?>

                <?php else : ?>

                    <div class="widget">
                        <h2 class="widget-title">Buscar</h2>
                        <?php get_product_search_form(); ?>
                    </div>

                    <?php

                    $categories = get_terms([
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'parent'     => 0,
                        'number'     => 8,
                    ]);

                    if (!is_wp_error($categories) && !empty($categories)) :
                    ?>

                        <div class="widget">
                            <h2 class="widget-title">Categorías</h2>
                            <ul class="shop-category-list">
                                <?php foreach ($categories as $category) :
                                    $link = get_term_link($category);
                                    if (is_wp_error($link)) {
                                        continue;
                                    }
                                ?>
                                    <li>
                                        <a href="<?php echo esc_url($link); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </aside>

            <div class="shop-content">

                <?php woocommerce_content(); ?>

            </div>

        </div>

    </div>

</main>

<?php get_footer(); ?>
