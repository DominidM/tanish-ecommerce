<?php
$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';

$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');
?>

<!-- CTA WHATSAPP -->
<?php if ($whatsapp_url) : ?>
    <section class="home-section home-section--cta">
        <div class="tanish-container">
            <div class="whatsapp-cta" data-reveal="up">
                <div>
                    <h2>¿Necesitas ayuda con tu pedido?</h2>
                    <p>Consulta disponibilidad directamente con nosotros.</p>
                </div>
                <div class="whatsapp-cta-actions">
                    <a href="<?php echo esc_url($shop_url); ?>" class="btn btn-outline-white">Ver nuestro catálogo</a>
                    <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark">
                        Hablar por WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
