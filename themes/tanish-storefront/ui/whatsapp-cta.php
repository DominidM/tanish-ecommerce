<?php
$whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$whatsapp_url = $whatsapp
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hola, deseo información sobre los productos de TANISH.')
    : '';
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
                <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">
                    Hablar por WhatsApp
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>
