<?php
defined('ABSPATH') || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" role="banner">
    <div class="header-top">
        <div class="tanish-container header-top-inner">

            <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
                TANISH
            </a>

            <div class="header-search">
                <form class="header-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="search" name="s" placeholder="Buscar productos..." value="<?php echo get_search_query(); ?>" aria-label="Buscar productos">
                    <button type="submit" aria-label="Buscar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="header-actions">
                <?php
                $login_redirect = admin_url('admin.php?page=tanish-inventory');
                if (is_user_logged_in()) :
                    if (current_user_can('manage_tanish_inventory')) :
                ?>
                    <a class="btn btn-primary" href="<?php echo esc_url($login_redirect); ?>">
                        Panel
                    </a>
                <?php
                    endif;
                    if (current_user_can('manage_tanish_inventory')) :
                ?>
                    <a class="header-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
                        Salir
                    </a>
                <?php
                    endif;
                else :
                ?>
                    <a class="btn btn-secondary" href="<?php echo esc_url(wp_login_url($login_redirect)); ?>">
                        Iniciar sesión
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <nav class="header-nav" aria-label="Navegación principal">
        <div class="tanish-container header-nav-inner">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'menu',
                'fallback_cb'    => 'tanish_storefront_menu_fallback',
            ]);
            ?>
        </div>
    </nav>
</header>
