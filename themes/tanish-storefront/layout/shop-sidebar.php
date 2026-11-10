<!-- CART SIDEBAR -->
<div class="cart-overlay" id="cart-overlay"></div>
<aside class="cart-sidebar" id="cart-sidebar">
    <div class="cart-sidebar-header">
        <h3>Carrito</h3>
        <button class="cart-close" id="cart-close" aria-label="Cerrar carrito">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <?php tanish_render_cart_drawer_content(); ?>
</aside>
