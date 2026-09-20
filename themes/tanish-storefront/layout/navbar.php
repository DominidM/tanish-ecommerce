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
        <span class="nav-underline"></span>
    </div>
</nav>
