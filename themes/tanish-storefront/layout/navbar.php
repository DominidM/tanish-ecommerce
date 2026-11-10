<nav class="header-nav" id="header-nav" aria-label="Navegación principal">
    <div class="tanish-container header-nav-inner">
        <button class="nav-toggle" id="nav-toggle" type="button"
            aria-expanded="false" aria-controls="header-nav-menu">
            <span class="nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
            <span class="nav-toggle-text">Menú</span>
        </button>

        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'menu',
            'menu_id'        => 'header-nav-menu',
            'fallback_cb'    => 'tanish_storefront_menu_fallback',
        ]);
        ?>
        <span class="nav-underline"></span>
    </div>
</nav>
