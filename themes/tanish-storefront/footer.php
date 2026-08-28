<?php
defined('ABSPATH') || exit;
?>

<footer class="site-footer">

    <div class="tanish-container">

        <div class="footer-grid">

            <div>

                <div class="footer-brand">
                    TANISH
                </div>

                <p class="footer-text">
                    Plataforma digital para consultar
                    productos, disponibilidad y coordinar
                    pedidos de forma rápida.
                </p>

            </div>

            <div>

                <div class="footer-title">
                    Navegación
                </div>

                <ul class="footer-links">

                    <li>
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            Inicio
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">
                            Tienda
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo esc_url(tanish_storefront_page_url('nosotros', '/nosotros/')); ?>">
                            Nosotros
                        </a>
                    </li>

                </ul>

            </div>

            <div>

                <div class="footer-title">
                    Información
                </div>

                <ul class="footer-links">

                    <li>
                        <a href="<?php echo esc_url(tanish_storefront_page_url('contacto', '/contacto/')); ?>">
                            Contacto
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">
                            Privacidad
                        </a>
                    </li>

                </ul>

            </div>

        </div>

        <div class="footer-bottom">

            © <?php echo esc_html(date('Y')); ?>
            TANISH S.A.C.

        </div>

    </div>

</footer>

<?php
$whatsapp_number = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
if (!empty($whatsapp_number)) :
	$whatsapp_url = 'https://wa.me/' . $whatsapp_number;
?>
<a
    class="tanish-floating-whatsapp"
    href="<?php echo esc_url($whatsapp_url); ?>"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Contactar por WhatsApp"
>
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M20.52 3.48A11.86 11.86 0 0 0 2.88 17.15L2 22l4.95-1.3A11.88 11.88 0 1 0 20.52 3.48Zm-3.25 13.26c-.2.56-1.15.95-1.55.99-.4.04-.9.07-2.88-.61-2.45-1.06-4.05-3.63-4.17-3.8-.12-.17-1.03-1.37-1.03-2.63 0-1.26.66-1.88.9-2.14.22-.24.5-.3.66-.3h.47c.16 0 .38-.06.53.4l.72 1.83c.08.19.02.44-.09.63l-.38.46c-.18.21-.38.47-.17.78.2.31.9 1.48 1.95 2.37 1.35 1.09 2.46 1.44 2.8 1.6.34.16.54.13.73-.08l.63-.75c.19-.23.45-.27.7-.14l1.73.9c.29.15.5.26.57.45.08.2.07.72-.13 1.3Z" fill="currentColor"/>
    </svg>
</a>
<?php endif; ?>
<?php wp_footer(); ?>

</body>
</html>