<?php

defined('ABSPATH') || exit;

get_header();

$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$whatsapp = preg_replace(
    '/\D/',
    '',
    (string) get_option('tanish_whatsapp_number', '')
);

$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp
        . '?text='
        . rawurlencode(
            'Hola, deseo información sobre los productos de TANISH.'
        )
    : '';

?>

<main>

    <!-- HERO -->

    <section class="tanish-hero">

        <div class="tanish-container">

            <div class="hero-card">

                <div class="hero-content">

                    <div class="hero-badge">
                        ● Catálogo online disponible
                    </div>

                    <h1 class="hero-title">
                        Compra fácil.
                        <br>
                        Compra en
                        <span>TANISH.</span>
                    </h1>

                    <p class="hero-description">
                        Consulta precios y disponibilidad en línea.
                        Elige tus productos y coordina tu pedido
                        directamente mediante WhatsApp.
                    </p>

                    <div class="hero-actions">

                        <a
                            href="<?php echo esc_url($shop_url); ?>"
                            class="btn btn-primary"
                        >
                            Explorar productos
                        </a>


                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- CATEGORÍAS -->

    <section class="tanish-section">

        <div class="tanish-container">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        Catálogo
                    </div>

                    <h2 class="section-title">
                        Compra por categoría
                    </h2>

                    <p class="section-description">
                        Encuentra rápidamente lo que necesitas.
                    </p>

                </div>

            </div>

            <div class="category-grid">

                <?php

                $categories = get_terms([
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'parent'     => 0,
                    'number'     => 4,
                ]);

                if (!is_wp_error($categories)) :

                    foreach ($categories as $category) :

                        $link = get_term_link($category);

                        if (is_wp_error($link)) {
                            continue;
                        }

                        ?>

                        <a
                            href="<?php echo esc_url($link); ?>"
                            class="category-card"
                        >

                            <div class="category-icon">
                                <?php
                                    echo esc_html(
                                        mb_strtoupper(
                                            mb_substr(
                                                $category->name,
                                                0,
                                                1
                                            )
                                        )
                                    );
                                ?>
                            </div>

                            <div>

                                <div class="category-name">
                                    <?php
                                        echo esc_html(
                                            $category->name
                                        );
                                    ?>
                                </div>

                                <div class="category-count">
                                    <?php
                                        echo esc_html(
                                            $category->count
                                        );
                                    ?>
                                    productos
                                </div>

                            </div>

                        </a>

                        <?php

                    endforeach;

                endif;

                ?>

            </div>

        </div>

    </section>


    <!-- PRODUCTOS -->

    <section class="tanish-section">

        <div class="tanish-container">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        Productos
                    </div>

                    <h2 class="section-title">
                        Productos disponibles
                    </h2>

                    <p class="section-description">
                        Stock actualizado directamente desde
                        nuestro catálogo.
                    </p>

                </div>

                <a
                    href="<?php echo esc_url($shop_url); ?>"
                    class="btn btn-primary"
                >
                    Ver catálogo
                </a>

            </div>

            <div class="tanish-products">

                <?php

                if (class_exists('WooCommerce')) :

                    $products = wc_get_products([
                        'status'  => 'publish',
                        'limit'   => 8,
                        'orderby' => 'date',
                        'order'   => 'DESC',
                    ]);

                    foreach ($products as $product) :

                        $product_url = get_permalink(
                            $product->get_id()
                        );

                        ?>

                        <article class="product-card">

                            <a
                                class="product-image"
                                href="<?php echo esc_url($product_url); ?>"
                            >

                                <?php

                                echo wp_kses_post(
                                    $product->get_image('woocommerce_thumbnail')
                                );

                                ?>

                                <?php if ($product->is_in_stock()) : ?>

                                    <span class="stock-badge">
                                        Disponible
                                    </span>

                                <?php endif; ?>

                            </a>

                            <div class="product-body">

                                <h3 class="product-name">

                                    <a
                                        href="<?php
                                            echo esc_url(
                                                $product_url
                                            );
                                        ?>"
                                    >
                                        <?php
                                            echo esc_html(
                                                $product->get_name()
                                            );
                                        ?>
                                    </a>

                                </h3>

                                <?php if ($product->get_sku()) : ?>

                                    <div class="product-sku">
                                        SKU:
                                        <?php
                                            echo esc_html(
                                                $product->get_sku()
                                            );
                                        ?>
                                    </div>

                                <?php endif; ?>

                                <div class="product-price">
                                    <?php
                                        echo wp_kses_post(
                                            $product->get_price_html()
                                        );
                                    ?>
                                </div>

                                <div class="product-action">

                                    <a
                                        href="<?php
                                            echo esc_url(
                                                $product_url
                                            );
                                        ?>"
                                        class="btn btn-primary"
                                    >
                                        Ver producto
                                    </a>

                                </div>

                            </div>

                        </article>

                        <?php

                    endforeach;

                endif;

                ?>

            </div>

        </div>

    </section>


    <!-- BENEFICIOS -->

    <section class="tanish-section">

        <div class="tanish-container">

            <div class="benefits">

                <div class="benefit">

                    <div class="benefit-number">
                        01
                    </div>

                    <h3>
                        Stock actualizado
                    </h3>

                    <p>
                        Consulta disponibilidad antes
                        de realizar tu pedido.
                    </p>

                </div>

                <div class="benefit">

                    <div class="benefit-number">
                        02
                    </div>

                    <h3>
                        Atención directa
                    </h3>

                    <p>
                        Coordina tu compra directamente
                        mediante WhatsApp.
                    </p>

                </div>

                <div class="benefit">

                    <div class="benefit-number">
                        03
                    </div>

                    <h3>
                        Compra simple
                    </h3>

                    <p>
                        Encuentra tus productos sin procesos
                        innecesariamente complejos.
                    </p>

                </div>

                <div class="benefit">

                    <div class="benefit-number">
                        04
                    </div>

                    <h3>
                        Catálogo online
                    </h3>

                    <p>
                        Accede al catálogo desde computadora,
                        tablet o smartphone.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- CTA WHATSAPP -->

    <?php if ($whatsapp_url) : ?>

        <section>

            <div class="tanish-container">

                <div class="whatsapp-cta">

                    <div>

                        <h2>
                            ¿Encontraste lo que necesitas?
                        </h2>

                        <p>
                            Escríbenos y coordinamos tu pedido
                            directamente.
                        </p>

                    </div>

                    <a
                        href="<?php echo esc_url($whatsapp_url); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-whatsapp"
                    >
                        Hablar por WhatsApp
                    </a>

                </div>

            </div>

        </section>

    <?php endif; ?>

</main>

<?php get_footer(); ?>