<?php
/**
 * Template Name: Acceso
 * Description: Pagina de inicio de sesion de TANISH con el estilo de la tienda.
 *
 * El formulario se procesa aqui mismo con wp_signon() (la misma API que usa
 * el nucleo de WordPress), de modo que un error de credenciales se muestra
 * en esta misma pagina y no devuelve al wp-login.php original.
 *
 * @package Tanish_Storefront
 */

defined('ABSPATH') || exit;

$tanish_login_redirect = admin_url('admin.php?page=tanish-inventory');
$tanish_errors         = array();

// Destino elegido al llegar a la pagina (por ejemplo /acceso?redirect_to=...).
$tanish_requested = isset($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : '';
$tanish_redirect  = $tanish_requested ? $tanish_requested : $tanish_login_redirect;

// Solo se permiten redirecciones internas a este sitio.
$tanish_host     = wp_parse_url(home_url(), PHP_URL_HOST);
$tanish_req_host = wp_parse_url($tanish_redirect, PHP_URL_HOST);
if ($tanish_req_host && $tanish_req_host !== $tanish_host) {
    $tanish_redirect = $tanish_login_redirect;
}

// Si el visitante ya esta autenticado, se le manda directo al panel.
if (is_user_logged_in()) {
    wp_safe_redirect($tanish_redirect);
    exit;
}

// ── Procesado del formulario ──
if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['tanish_login_nonce'])) {
    if (!wp_verify_nonce($_POST['tanish_login_nonce'], 'tanish_login_form')) {
        $tanish_errors[] = 'La sesion expiro. Vuelve a intentarlo.';
    } else {
        $tanish_user = wp_signon(
            array(
                'user_login'    => isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '',
                'user_password' => isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '',
                'remember'      => !empty($_POST['rememberme']),
            ),
            is_ssl() ? 'secure_auth' : ''
        );

        if (is_wp_error($tanish_user)) {
            $tanish_errors[] = $tanish_user->get_error_message();
        } else {
            // El destino se vuelve a validar por si el POST lo altero.
            $tanish_target = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : $tanish_login_redirect;
            $tanish_target_host = wp_parse_url($tanish_target, PHP_URL_HOST);
            if ($tanish_target_host && $tanish_target_host !== $tanish_host) {
                $tanish_target = $tanish_login_redirect;
            }
            wp_safe_redirect($tanish_target);
            exit;
        }
    }
}

// Traducciones de los errores mas habituales de wp_signon().
$tanish_error_texts = array(
    'empty_username'     => 'Escribe tu usuario o correo electrónico.',
    'empty_password'     => 'Escribe tu contraseña.',
    'incorrect_password' => 'El usuario o la contraseña no son correctos.',
    'invalid_username'   => 'Ese usuario no existe.',
    'invalid_email'      => 'Ese correo no está registrado.',
    'login_empty'        => 'Completa ambos campos para entrar.',
);

$tanish_error = '';
if (empty($tanish_errors) && isset($_GET['login_error'])) {
    $tanish_error = sanitize_text_field(wp_unslash($_GET['login_error']));
    if (isset($tanish_error_texts[$tanish_error])) {
        $tanish_errors[] = $tanish_error_texts[$tanish_error];
    }
}

get_header();
?>

<main class="page-main">

    <?php get_template_part('layout/page-hero'); ?>

    <section class="acceso-section">
        <div class="tanish-container">
            <div class="acceso-wrap">

                <div class="acceso-card">

                    <div class="acceso-head">
                        <div class="acceso-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h2 class="acceso-title">Iniciar sesión</h2>
                        <p class="acceso-subtitle">Entra para gestionar el inventario y los pedidos de TANISH.</p>
                    </div>

                    <?php if (!empty($tanish_errors)) : ?>
                        <div class="acceso-error" role="alert">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>
                                <?php
                                foreach ($tanish_errors as $tanish_err) {
                                    echo esc_html($tanish_err);
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form class="acceso-form" method="post" action="<?php echo esc_url(get_permalink()); ?>">
                        <?php wp_nonce_field('tanish_login_form', 'tanish_login_nonce'); ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($tanish_redirect); ?>">

                        <div class="acceso-field">
                            <label for="user_login">Usuario o correo electrónico</label>
                            <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr($_POST['log'] ?? ''); ?>" autocomplete="username" autofocus required>
                        </div>

                        <div class="acceso-field">
                            <label for="user_pass">Contraseña</label>
                            <input type="password" name="pwd" id="user_pass" class="input" autocomplete="current-password" required>
                        </div>

                        <div class="acceso-field acceso-field--remember">
                            <label for="rememberme">
                                <input type="checkbox" name="rememberme" id="rememberme" value="forever" <?php checked(!empty($_POST['rememberme'])); ?>>
                                Mantener la sesión iniciada
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary acceso-submit" name="wp-submit" id="wp-submit">
                            Iniciar sesión
                        </button>
                    </form>

                    <p class="acceso-foot">
                        ¿Necesitas ayuda? Escríbenos por
                        <a href="<?php echo esc_url(home_url('/contacto/')); ?>">WhatsApp</a>.
                    </p>

                </div>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
