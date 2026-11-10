<?php
/**
 * Template Name: Acceso
 * Description: Pagina de inicio de sesion de TANISH con el estilo de la tienda.
 *
 * El formulario se envia a wp-login.php (el motor de autenticacion de
 * WordPress) y, al completar el acceso, redirige al panel de inventario
 * o a la pagina de origen.
 *
 * @package Tanish_Storefront
 */

defined('ABSPATH') || exit;

$tanish_login_redirect = admin_url('admin.php?page=tanish-inventory');

// Si el visitante ya esta autenticado, se le manda directo al panel.
if (is_user_logged_in()) {
    wp_safe_redirect($tanish_login_redirect);
    exit;
}

// Destino elegido al llegar a la pagina (por ejemplo /ingresar?redirect_to=...).
$tanish_requested = isset($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : '';
$tanish_redirect  = $tanish_requested ? $tanish_requested : $tanish_login_redirect;

// Solo se permiten redirecciones internas a este sitio.
$tanish_host     = wp_parse_url(home_url(), PHP_URL_HOST);
$tanish_req_host = wp_parse_url($tanish_redirect, PHP_URL_HOST);
if ($tanish_req_host && $tanish_req_host !== $tanish_host) {
    $tanish_redirect = $tanish_login_redirect;
}

// Errores que devuelve wp-login.php y los vuelve a pasar en la URL.
$tanish_error = '';
if (isset($_GET['login_error'])) {
    $tanish_error = sanitize_text_field(wp_unslash($_GET['login_error']));
}

$tanish_messages = array(
    'empty_username'  => 'Escribe tu usuario o correo electrónico.',
    'empty_password'  => 'Escribe tu contraseña.',
    'incorrect_password' => 'El usuario o la contraseña no son correctos.',
    'invalid_username' => 'Ese usuario no existe.',
    'invalid_email'   => 'Ese correo no está registrado.',
    'login_empty'     => 'Completa ambos campos para entrar.',
);

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

                    <?php if ($tanish_error) : ?>
                        <div class="acceso-error" role="alert">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>
                                <?php
                                echo esc_html(
                                    isset($tanish_messages[$tanish_error])
                                        ? $tanish_messages[$tanish_error]
                                        : 'No pudimos iniciar tu sesión. Intenta de nuevo.'
                                );
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form class="acceso-form" method="post" action="<?php echo esc_url(tanish_storefront_login_form_action()); ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($tanish_redirect); ?>">
                        <input type="hidden" name="testcookie" value="1">

                        <div class="acceso-field">
                            <label for="user_login">Usuario o correo electrónico</label>
                            <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr($_POST['log'] ?? ''); ?>" autocomplete="username" autofocus required>
                        </div>

                        <div class="acceso-field">
                            <label for="user_pass">Contraseña</label>
                            <input type="password" name="pwd" id="user_pass" class="input" autocomplete="current-password" required>
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
