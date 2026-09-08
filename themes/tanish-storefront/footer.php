<?php
defined('ABSPATH') || exit;

$whatsapp_number = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$admin_email     = (string) get_option('admin_email', '');
$privacy_url     = (string) get_privacy_policy_url();
$libro_page      = get_page_by_path('libro-de-reclamaciones');
$libro_url       = $libro_page ? get_permalink($libro_page->ID) : '';
$terms_page      = get_page_by_path('terminos-y-condiciones');
$terms_url       = $terms_page ? get_permalink($terms_page->ID) : '';
?>

<footer class="site-footer">
    <div class="tanish-footer-wrap">
        <div class="tanish-container">
            <div class="footer-columns">

                <!-- Col 1: Brand -->
                <div class="footer-col footer-col--brand">
                    <div class="footer-brand">TANISH</div>
                    <p class="footer-desc">
                        Plataforma digital para consultar productos, disponibilidad y coordinar pedidos de forma rápida.
                    </p>
                </div>

                <!-- Col 2: Navegación -->
                <div class="footer-col">
                    <div class="footer-col-title">Navegación</div>
                    <ul class="footer-links">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a></li>
                        <li><a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">Tienda</a></li>
                        <li><a href="<?php echo esc_url(tanish_storefront_page_url('nosotros', '/nosotros/')); ?>">Nosotros</a></li>
                        <li><a href="<?php echo esc_url(tanish_storefront_page_url('contacto', '/contacto/')); ?>">Contacto</a></li>
                    </ul>
                </div>

                <!-- Col 3: Contacto -->
                <div class="footer-col">
                    <div class="footer-col-title">Contacto</div>
                    <ul class="footer-links">
                        <?php if (!empty($whatsapp_number)) : ?>
                            <li>
                                <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg class="footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    WhatsApp
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($admin_email)) : ?>
                            <li>
                                <a href="mailto:<?php echo esc_attr($admin_email); ?>">
                                    <svg class="footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    <?php echo esc_html($admin_email); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Col 4: Legal -->
                <div class="footer-col">
                    <div class="footer-col-title">Legal</div>
                    <ul class="footer-links">
                        <?php if (!empty($terms_url)) : ?>
                            <li><a href="<?php echo esc_url($terms_url); ?>">Términos y Condiciones</a></li>
                        <?php endif; ?>
                        <?php if (!empty($privacy_url)) : ?>
                            <li><a href="<?php echo esc_url($privacy_url); ?>">Política de Privacidad</a></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($libro_url)) : ?>
                        <a href="<?php echo esc_url($libro_url); ?>" class="footer-libro" aria-label="Libro de Reclamaciones">
                            <img
                                src="https://res.cloudinary.com/dxuk9bogw/image/upload/v1776155530/7f85d794-58b5-47d0-850d-d06179563fb2.png"
                                alt="Libro de Reclamaciones"
                                width="140"
                                height="auto"
                                loading="lazy"
                            >
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Bottom bar -->
        <div class="footer-bottom-bar">
            <div class="tanish-container footer-bottom-inner">
                <p class="footer-copyright">
                    &copy; <?php echo esc_html(date('Y')); ?> TANISH S.A.C. Todos los derechos reservados.
                </p>
                <a
                    href="https://solvegrades.com/services/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tanish-footer-sg"
                    aria-label="Desarrollado por SolveGrades"
                >
                    <span class="footer-sg-text">Desarrollado por</span>
                    <img
                        src="https://res.cloudinary.com/dp1vgjhsq/image/upload/v1778834655/WhatsApp_Image_2026-05-15_at_3.21.36_AM-removebg-preview_wtgmkr.png"
                        alt="SolveGrades"
                        width="70"
                        height="auto"
                        loading="lazy"
                    >
                </a>
            </div>
        </div>
    </div>
</footer>

<?php
if (!empty($whatsapp_number)) :
    $whatsapp_url = 'https://wa.me/' . $whatsapp_number;
?>
<div class="tanish-whatsapp-dock">
    <a
        class="whatsapp-label"
        href="<?php echo esc_url($whatsapp_url); ?>"
        target="_blank"
        rel="noopener noreferrer"
    >
        Escríbenos
    </a>
    <a
        class="whatsapp-circle"
        href="<?php echo esc_url($whatsapp_url); ?>"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" fill="currentColor"/>
        </svg>
    </a>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
