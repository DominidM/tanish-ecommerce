<?php
/**
 * Template Name: Contacto
 * Description: Página "Contacto" de TANISH
 */

defined('ABSPATH') || exit;

get_header();

$submitted = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanish_contact_nonce'])) {
    if (!wp_verify_nonce($_POST['tanish_contact_nonce'], 'tanish_contact_form')) {
        $errors[] = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        $nombres    = sanitize_text_field($_POST['nombres'] ?? '');
        $apellidos  = sanitize_text_field($_POST['apellidos'] ?? '');
        $telefono   = sanitize_text_field($_POST['telefono'] ?? '');
        $correo     = sanitize_email($_POST['correo'] ?? '');
        $tipo       = sanitize_text_field($_POST['tipo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (empty($nombres))   $errors[] = 'El nombre es obligatorio.';
        if (empty($apellidos)) $errors[] = 'Los apellidos son obligatorios.';
        if (empty($correo) || !is_email($correo)) $errors[] = 'El correo electrónico no es válido.';
        if (empty($descripcion)) $errors[] = 'La descripción es obligatoria.';

        if (empty($errors)) {
            $admin_email = get_option('admin_email', '');
            $subject = '[TANISH] Nuevo mensaje de contacto - ' . $tipo;
            $message = "Nombre: {$nombres} {$apellidos}\n";
            $message .= "Teléfono: {$telefono}\n";
            $message .= "Correo: {$correo}\n";
            $message .= "Tipo: {$tipo}\n\n";
            $message .= "Descripción:\n{$descripcion}\n";
            $headers = ['Content-Type: text/plain; charset=UTF-8', "Reply-To: {$correo}"];

            $sent = wp_mail($admin_email, $subject, $message, $headers);
            $submitted = true;
        }
    }
}
?>

<main class="page-main">

    <?php get_template_part('layout/page-hero'); ?>

    <section class="contacto-section">
        <div class="tanish-container">
            <div class="contacto-grid">

                <div class="contacto-info">
                    <h2 class="contacto-title">Escribenos</h2>
                    <p class="contacto-text">
                        Completa el formulario y nos pondremos en contacto contigo lo antes posible. También puedes escribirnos directamente por WhatsApp.
                    </p>

                    <div class="contacto-detail">
                        <div class="contacto-detail-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <div class="contacto-detail-label">Teléfono</div>
                            <div class="contacto-detail-value">+51 999 999 999</div>
                        </div>
                    </div>

                    <div class="contacto-detail">
                        <div class="contacto-detail-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <div class="contacto-detail-label">Correo</div>
                            <div class="contacto-detail-value">contacto@tanish.com</div>
                        </div>
                    </div>

                    <div class="contacto-detail">
                        <div class="contacto-detail-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div class="contacto-detail-label">Horario</div>
                            <div class="contacto-detail-value">Lun - Sáb: 9:00 AM - 6:00 PM</div>
                        </div>
                    </div>
                </div>

                <div class="contacto-form-wrap">
                    <?php if ($submitted) : ?>
                        <div class="contacto-success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <h3>Mensaje enviado</h3>
                            <p>Gracias por contactarnos. Te responderemos pronto.</p>
                        </div>
                    <?php else : ?>
                        <?php if (!empty($errors)) : ?>
                            <div class="contacto-errors">
                                <?php foreach ($errors as $err) : ?>
                                    <p><?php echo esc_html($err); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form class="contacto-form" method="post" action="">
                            <?php wp_nonce_field('tanish_contact_form', 'tanish_contact_nonce'); ?>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="nombres">Nombres *</label>
                                    <input type="text" id="nombres" name="nombres" required placeholder="Tus nombres" value="<?php echo esc_attr($_POST['nombres'] ?? ''); ?>">
                                </div>
                                <div class="contacto-field">
                                    <label for="apellidos">Apellidos *</label>
                                    <input type="text" id="apellidos" name="apellidos" required placeholder="Tus apellidos" value="<?php echo esc_attr($_POST['apellidos'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="telefono">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" placeholder="+51 999 999 999" value="<?php echo esc_attr($_POST['telefono'] ?? ''); ?>">
                                </div>
                                <div class="contacto-field">
                                    <label for="correo">Correo electrónico *</label>
                                    <input type="email" id="correo" name="correo" required placeholder="correo@ejemplo.com" value="<?php echo esc_attr($_POST['correo'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-field">
                                <label for="tipo">Tipo de consulta *</label>
                                <select id="tipo" name="tipo" required>
                                    <option value="">Selecciona una opción</option>
                                    <option value="Consulta general" <?php selected($_POST['tipo'] ?? '', 'Consulta general'); ?>>Consulta general</option>
                                    <option value="Pedido" <?php selected($_POST['tipo'] ?? '', 'Pedido'); ?>>Pedido</option>
                                    <option value="Disponibilidad" <?php selected($_POST['tipo'] ?? '', 'Disponibilidad'); ?>>Disponibilidad</option>
                                    <option value="Precios" <?php selected($_POST['tipo'] ?? '', 'Precios'); ?>>Precios</option>
                                    <option value="Otro" <?php selected($_POST['tipo'] ?? '', 'Otro'); ?>>Otro</option>
                                </select>
                            </div>

                            <div class="contacto-field">
                                <label for="descripcion">Descripción *</label>
                                <textarea id="descripcion" name="descripcion" rows="5" required placeholder="Cuéntanos en qué podemos ayudarte..."><?php echo esc_textarea($_POST['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary contacto-submit">
                                Enviar mensaje
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
