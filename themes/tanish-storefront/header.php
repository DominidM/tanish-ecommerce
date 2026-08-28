<?php
defined('ABSPATH') || exit;
?>
<!doctype html>

<html <?php language_attributes(); ?>>

<head>

    <meta charset="<?php bloginfo('charset'); ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<header class="site-header">

    <div class="tanish-container header-inner">

        <a
            class="brand"
            href="<?php echo esc_url(home_url('/')); ?>"
        >
            TANISH
        </a>

        <nav
            class="main-nav"
            aria-label="Navegación principal"
        >

            <?php

            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => 'tanish_storefront_menu_fallback',
            ]);

            ?>

        </nav>

        <div class="header-actions">

            <?php
            $login_redirect = admin_url('admin.php?page=tanish-inventory');
            if (is_user_logged_in()) :
                if (current_user_can('manage_tanish_inventory')) :
            ?>
                    <a
                        class="btn btn-primary"
                        href="<?php echo esc_url($login_redirect); ?>"
                    >
                        Panel
                    </a>
            <?php
                else :
            ?>
                    <a
                        class="btn btn-secondary"
                        href="<?php echo esc_url(home_url('/')); ?>"
                    >
                        Panel
                    </a>
            <?php
                endif;
            ?>
            <?php
                if (current_user_can('manage_tanish_inventory')) :
            ?>
                    <a
                        class="header-logout"
                        href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"
                    >
                        Cerrar sesión
                    </a>
            <?php
                endif;
            else :
            ?>
                <a
                    class="btn btn-secondary"
                    href="<?php echo esc_url(wp_login_url($login_redirect)); ?>"
                >
                    Iniciar sesión
                </a>
            <?php endif; ?>

        </div>

    </div>

</header>