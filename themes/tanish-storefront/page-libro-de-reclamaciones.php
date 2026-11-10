<?php
/**
 * Template Name: Libro de Reclamaciones
 * Description: Formulario del Libro de Reclamaciones de TANISH (campos exigidos por INDECOPI) con notificación por correo.
 */

defined('ABSPATH') || exit;

$submitted = false;
$errors    = [];
$claim_ref = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanish_reclamo_nonce'])) {
    if (!wp_verify_nonce($_POST['tanish_reclamo_nonce'], 'tanish_reclamo_form')) {
        $errors[] = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        // ── Datos del consumidor ──
        $nombres     = sanitize_text_field($_POST['nombres'] ?? '');
        $apellidos   = sanitize_text_field($_POST['apellidos'] ?? '');
        $doc_tipo    = sanitize_text_field($_POST['doc_tipo'] ?? '');
        $doc_numero  = sanitize_text_field($_POST['doc_numero'] ?? '');
        $telefono    = sanitize_text_field($_POST['telefono'] ?? '');
        $correo      = sanitize_email($_POST['correo'] ?? '');
        $direccion   = sanitize_textarea_field($_POST['direccion'] ?? '');
        $departamento = sanitize_text_field($_POST['departamento'] ?? '');

        // ── Datos exclusivos del reclamo ──
        $tipo_tramite     = sanitize_text_field($_POST['tipo_tramite'] ?? '');      // Reclamo / Queja
        $fecha_compra     = sanitize_text_field($_POST['fecha_compra'] ?? '');
        $pedido           = sanitize_text_field($_POST['pedido'] ?? '');
        $producto         = sanitize_text_field($_POST['producto'] ?? '');
        $monto            = sanitize_text_field($_POST['monto'] ?? '');
        $canal            = sanitize_text_field($_POST['canal'] ?? '');
        $medio_respuesta  = sanitize_text_field($_POST['medio_respuesta'] ?? '');
        $detalle          = sanitize_textarea_field($_POST['detalle'] ?? '');
        $solucion         = sanitize_textarea_field($_POST['solucion'] ?? '');

        // ── Validación ──
        if (empty($nombres))      $errors[] = 'Los nombres son obligatorios.';
        if (empty($apellidos))    $errors[] = 'Los apellidos son obligatorios.';
        if (empty($doc_numero))   $errors[] = 'El número de documento es obligatorio.';
        if (empty($correo) || !is_email($correo)) $errors[] = 'El correo electrónico no es válido.';
        if (empty($telefono))     $errors[] = 'El teléfono es obligatorio.';
        if (empty($tipo_tramite)) $errors[] = 'Indica si es un Reclamo o una Queja.';
        if (empty($producto))     $errors[] = 'Indica el producto o servicio relacionado.';
        if (empty($detalle))      $errors[] = 'Describe el motivo del reclamo.';
        if (empty($solucion))     $errors[] = 'Indica qué solución solicitas.';
        if (!isset($_POST['declaracion'])) $errors[] = 'Debes confirmar la declaración de veracidad.';

        // ── Anti-spam: 1 envío cada 90 s por IP + bloqueo de duplicados ──
        $ip_key  = 'tanish_rl_reclamo_' . md5(tanish_client_ip());
        $dup_key = 'tanish_dup_reclamo_' . md5($correo . '|' . $producto . '|' . $detalle);

        if (get_transient($ip_key)) {
            $errors[] = 'Espera un momento antes de enviar otro reclamo.';
        } elseif (get_transient($dup_key)) {
            $errors[] = 'Ya registramos este reclamo hace un instante. Revisa tu correo.';
        }

        if (empty($errors)) {
            set_transient($ip_key, 1, 90);
            set_transient($dup_key, 1, 10 * MINUTE_IN_SECONDS);

            // Número correlativo del libro (por año).
            $option_key = 'tanish_reclamo_counter_' . date('Y');
            $next       = ((int) get_option($option_key, 0)) + 1;
            update_option($option_key, $next, false);
            $claim_ref = sprintf('%s-%04d', date('Y'), $next);

            $admin_email = get_option('admin_email', '');
            $subject     = "[TANISH][{$claim_ref}] Libro de Reclamaciones - {$tipo_tramite}: {$nombres} {$apellidos}";

            $fields = [
                // Consumidor
                'Nombres'            => $nombres,
                'Apellidos'          => $apellidos,
                'Documento'          => $doc_tipo . ' ' . $doc_numero,
                'Teléfono'           => $telefono,
                'Correo'             => $correo,
                'Dirección'          => $direccion,
                'Departamento'       => $departamento,
                // Reclamo (exclusivo)
                'N° de referencia'   => $claim_ref,
                'Tipo de trámite'    => $tipo_tramite,
                'Fecha de compra'    => $fecha_compra,
                'N° de pedido'       => $pedido,
                'Producto/Servicio'  => $producto,
                'Monto reclamado'    => $monto !== '' ? 'S/ ' . $monto : '',
                'Canal de compra'    => $canal,
                'Medio de respuesta' => $medio_respuesta,
                'Detalle'            => $detalle,
                'Solución solicitada'=> $solucion,
            ];

            // Guardar en la base de datos (visible en el panel admin).
            tanish_save_form_entry(
                'reclamo',
                $fields,
                $claim_ref . ' · ' . $tipo_tramite . ' · ' . $nombres . ' ' . $apellidos
            );

            $sent = tanish_send_html_mail($admin_email, $subject, $fields, [
                'title'    => 'Libro de Reclamaciones',
                'intro'    => 'Se ha registrado una nueva entrada en el Libro de Reclamaciones de TANISH.',
                'note'     => 'Referencia ' . $claim_ref . ' — responde a este correo para comunicarte directamente con el consumidor.',
                'footer'   => 'Entrada registrada desde el Libro de Reclamaciones virtual de la web de TANISH.',
                'reply_to' => $correo,
            ]);

            // Copia al consumidor (constancia de registro).
            $copy_fields = $fields;
            unset($copy_fields['Nombres'], $copy_fields['Apellidos']);
            tanish_send_html_mail($correo, "[TANISH] Constancia de reclamo {$claim_ref}", $copy_fields, [
                'title'  => 'Constancia de reclamo',
                'intro'  => 'Hola ' . $nombres . ', hemos recibido tu reclamo. Guarda este número de referencia para dar seguimiento.',
                'note'   => 'N° de referencia: ' . $claim_ref . '. Te responderemos por ' . strtolower($medio_respuesta ?: 'correo') . ' a la brevedad.',
                'footer' => 'TANISH se compromete a atender tu reclamo dentro de los plazos legales.',
            ]);

            // Patrón PRG: redirigir al inicio con un toast de confirmación
            // (evita el reenvío al refrescar).
            tanish_form_success_redirect('Reclamo registrado · Referencia ' . $claim_ref);
        }
    }
}

get_header();
?>

<main class="page-main">

    <?php get_template_part('layout/page-hero'); ?>

    <section class="contacto-section">
        <div class="tanish-container">

            <?php if ($submitted) : ?>
                <div class="contacto-grid">
                    <div class="contacto-info">
                        <h2 class="contacto-title">Reclamo registrado</h2>
                        <p class="contacto-text">
                            Tu entrada quedó guardada en nuestro Libro de Reclamaciones. Enviamos una
                            constancia a tu correo con el número de referencia.
                        </p>
                        <div class="contacto-detail">
                            <div class="contacto-detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                            <div>
                                <div class="contacto-detail-label">Referencia</div>
                                <div class="contacto-detail-value"><?php echo esc_html($claim_ref); ?></div>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Volver al inicio</a>
                    </div>
                </div>

            <?php else : ?>
                <div class="contacto-grid">

                    <div class="contacto-info">
                        <h2 class="contacto-title">Libro de Reclamaciones</h2>
                        <p class="contacto-text">
                            Registra aquí tu reclamo o queja. Recibirás un correo con tu número de
                            referencia y te responderemos dentro de los plazos de ley.
                        </p>

                        <div class="reclamo-note">
                            <strong>¿Reclamo o queja?</strong>
                            <ul>
                                <li><strong>Reclamo:</strong> disconformidad con un producto o servicio (no conforme, defectuoso, etc.).</li>
                                <li><strong>Queja:</strong> molestia o malestar respecto de la atención recibida.</li>
                            </ul>
                        </div>

                        <div class="contacto-detail">
                            <div class="contacto-detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <div>
                                <div class="contacto-detail-label">Plazo de respuesta</div>
                                <div class="contacto-detail-value">Máximo 15 días hábiles</div>
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
                    </div>

                    <div class="contacto-form-wrap">
                        <?php if (!empty($errors)) : ?>
                            <div class="contacto-errors">
                                <?php foreach ($errors as $err) : ?>
                                    <p><?php echo esc_html($err); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form class="contacto-form" method="post" action="">
                            <?php wp_nonce_field('tanish_reclamo_form', 'tanish_reclamo_nonce'); ?>

                            <h3 class="reclamo-section-title">1. Datos del consumidor</h3>

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
                                    <label for="doc_tipo">Tipo de documento *</label>
                                    <select id="doc_tipo" name="doc_tipo" required>
                                        <option value="DNI" <?php selected($_POST['doc_tipo'] ?? 'DNI', 'DNI'); ?>>DNI</option>
                                        <option value="CE" <?php selected($_POST['doc_tipo'] ?? '', 'CE'); ?>>Carné de extranjería</option>
                                        <option value="Pasaporte" <?php selected($_POST['doc_tipo'] ?? '', 'Pasaporte'); ?>>Pasaporte</option>
                                        <option value="RUC" <?php selected($_POST['doc_tipo'] ?? '', 'RUC'); ?>>RUC</option>
                                    </select>
                                </div>
                                <div class="contacto-field">
                                    <label for="doc_numero">N° de documento *</label>
                                    <input type="text" id="doc_numero" name="doc_numero" required placeholder="00000000" value="<?php echo esc_attr($_POST['doc_numero'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="telefono">Teléfono *</label>
                                    <input type="tel" id="telefono" name="telefono" required placeholder="+51 999 999 999" value="<?php echo esc_attr($_POST['telefono'] ?? ''); ?>">
                                </div>
                                <div class="contacto-field">
                                    <label for="correo">Correo electrónico *</label>
                                    <input type="email" id="correo" name="correo" required placeholder="correo@ejemplo.com" value="<?php echo esc_attr($_POST['correo'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-field">
                                <label for="direccion">Dirección completa</label>
                                <input type="text" id="direccion" name="direccion" placeholder="Calle, número, distrito" value="<?php echo esc_attr($_POST['direccion'] ?? ''); ?>">
                            </div>

                            <div class="contacto-field">
                                <label for="departamento">Departamento / Región</label>
                                <input type="text" id="departamento" name="departamento" placeholder="Ej.: Lima, Arequipa, Cusco..." value="<?php echo esc_attr($_POST['departamento'] ?? ''); ?>">
                            </div>

                            <h3 class="reclamo-section-title">2. Datos del reclamo</h3>

                            <div class="contacto-field">
                                <label for="tipo_tramite">Tipo de trámite *</label>
                                <div class="reclamo-radio-row">
                                    <label class="reclamo-radio">
                                        <input type="radio" name="tipo_tramite" value="Reclamo" required <?php checked($_POST['tipo_tramite'] ?? '', 'Reclamo'); ?>>
                                        <span>Reclamo</span>
                                    </label>
                                    <label class="reclamo-radio">
                                        <input type="radio" name="tipo_tramite" value="Queja" <?php checked($_POST['tipo_tramite'] ?? '', 'Queja'); ?>>
                                        <span>Queja</span>
                                    </label>
                                </div>
                            </div>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="fecha_compra">Fecha de compra</label>
                                    <input type="date" id="fecha_compra" name="fecha_compra" value="<?php echo esc_attr($_POST['fecha_compra'] ?? ''); ?>">
                                </div>
                                <div class="contacto-field">
                                    <label for="pedido">N° de pedido (si tienes)</label>
                                    <input type="text" id="pedido" name="pedido" placeholder="Ej.: #1234" value="<?php echo esc_attr($_POST['pedido'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="producto">Producto o servicio *</label>
                                    <input type="text" id="producto" name="producto" required placeholder="Nombre del producto" value="<?php echo esc_attr($_POST['producto'] ?? ''); ?>">
                                </div>
                                <div class="contacto-field">
                                    <label for="monto">Monto reclamado (S/)</label>
                                    <input type="number" id="monto" name="monto" min="0" step="0.01" placeholder="0.00" value="<?php echo esc_attr($_POST['monto'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="contacto-form-row">
                                <div class="contacto-field">
                                    <label for="canal">Canal de compra</label>
                                    <select id="canal" name="canal">
                                        <option value="">Selecciona una opción</option>
                                        <option value="Tienda en línea" <?php selected($_POST['canal'] ?? '', 'Tienda en línea'); ?>>Tienda en línea</option>
                                        <option value="WhatsApp" <?php selected($_POST['canal'] ?? '', 'WhatsApp'); ?>>WhatsApp</option>
                                        <option value="Tienda física" <?php selected($_POST['canal'] ?? '', 'Tienda física'); ?>>Tienda física</option>
                                        <option value="Otro" <?php selected($_POST['canal'] ?? '', 'Otro'); ?>>Otro</option>
                                    </select>
                                </div>
                                <div class="contacto-field">
                                    <label for="medio_respuesta">Medio de respuesta *</label>
                                    <select id="medio_respuesta" name="medio_respuesta" required>
                                        <option value="Correo electrónico" <?php selected($_POST['medio_respuesta'] ?? '', 'Correo electrónico'); ?>>Correo electrónico</option>
                                        <option value="Teléfono" <?php selected($_POST['medio_respuesta'] ?? '', 'Teléfono'); ?>>Teléfono</option>
                                        <option value="WhatsApp" <?php selected($_POST['medio_respuesta'] ?? '', 'WhatsApp'); ?>>WhatsApp</option>
                                    </select>
                                </div>
                            </div>

                            <div class="contacto-field">
                                <label for="detalle">Detalle del reclamo *</label>
                                <textarea id="detalle" name="detalle" rows="5" required placeholder="Describe qué ocurrió, cuándo y cómo..."><?php echo esc_textarea($_POST['detalle'] ?? ''); ?></textarea>
                            </div>

                            <div class="contacto-field">
                                <label for="solucion">Solución que solicitas *</label>
                                <textarea id="solucion" name="solucion" rows="3" required placeholder="Ej.: reposición, devolución del dinero, cambio..."><?php echo esc_textarea($_POST['solucion'] ?? ''); ?></textarea>
                            </div>

                            <label class="reclamo-check">
                                <input type="checkbox" name="declaracion" value="1" required <?php checked($_POST['declaracion'] ?? '', '1'); ?>>
                                <span>Declaro bajo protesta de verdad que los datos expuestos son veraces. *</span>
                            </label>

                            <button type="submit" class="btn btn-primary contacto-submit">
                                Registrar reclamo
                            </button>
                        </form>
                        <script>
                        document.querySelectorAll('.contacto-form').forEach(function (form) {
                            form.addEventListener('submit', function () {
                                var btn = form.querySelector('button[type="submit"]');
                                if (btn) {
                                    btn.disabled = true;
                                    btn.textContent = 'Enviando...';
                                }
                            });
                        });
                        </script>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php get_footer(); ?>
