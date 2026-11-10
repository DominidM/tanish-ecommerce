<?php
/**
 * Plantilla HTML de correo para TANISH (Contacto, Libro de Reclamaciones, etc.).
 *
 * Los correos se envían en HTML con el logo y la identidad de la marca.
 * Estilos inline (los clientes de correo no interpretan <style> externo).
 */

defined('ABSPATH') || exit;

/** Logo público de TANISH (Cloudinary). */
function tanish_email_logo(): string
{
    return 'https://res.cloudinary.com/dp1vgjhsq/image/upload/v1789516962/logo-tanish_ekdvpd.png';
}

/**
 * Renderiza el HTML del correo.
 *
 * @param string $title   Título del correo.
 * @param array  $fields  Pares etiqueta => valor (se omiten los vacíos).
 * @param string $intro   Párrafo introductorio (opcional).
 * @param string $note    Nota al pie sobre el contenido (opcional).
 * @param string $footer  Texto del pie de página (opcional).
 */
function tanish_email_html(string $title, array $fields, string $intro = '', string $note = '', string $footer = ''): string
{
    $logo   = esc_url(tanish_email_logo());
    $site   = esc_url(home_url('/'));
    $year   = (string) date('Y');

    $rows = '';
    foreach ($fields as $label => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $value = nl2br(esc_html((string) $value));
        $rows .= '
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #E5E7EB;vertical-align:top;width:38%;color:#6B7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">'
                            . esc_html((string) $label) . '</td>
                        <td style="padding:10px 0;border-bottom:1px solid #E5E7EB;vertical-align:top;color:#1F2937;font-size:14px;line-height:1.6;">'
                            . $value . '</td>
                    </tr>';
    }

    $intro_html = $intro !== '' ? '<p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.7;">' . esc_html($intro) . '</p>' : '';
    $note_html  = $note !== ''
        ? '<p style="margin:20px 0 0;padding:14px 16px;background:#ECFDF5;border-left:4px solid #22C55E;border-radius:0 8px 8px 0;color:#166534;font-size:13px;line-height:1.6;">' . esc_html($note) . '</p>'
        : '';
    $footer_html = $footer !== '' ? '<p style="margin:0;color:#9CA3AF;font-size:12px;line-height:1.6;">' . esc_html($footer) . '</p>' : '';

    return '
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9F4;padding:32px 12px;margin:0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden;border:1px solid #E5E7EB;font-family:Arial,Helvetica,sans-serif;">

                    <tr style="background:#0F3B5F;">
                        <td style="padding:20px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:14px;vertical-align:middle;">
                                        <img src="' . $logo . '" width="132" height="44" alt="TANISH" style="display:block;width:132px;height:44px;border:0;background:#FFFFFF;border-radius:8px;padding:6px;">
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <span style="color:#FFFFFF;font-size:20px;font-weight:700;letter-spacing:.06em;">TANISH</span><br>
                                        <span style="color:#93C5FD;font-size:11px;letter-spacing:.08em;text-transform:uppercase;">Compra fácil, rápida y directa</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 28px 12px;">
                            <h1 style="margin:0 0 8px;color:#0F3B5F;font-size:22px;font-weight:700;line-height:1.3;">' . esc_html($title) . '</h1>
                            <div style="width:48px;height:4px;background:#22C55E;border-radius:2px;margin:0 0 20px;"></div>
                            ' . $intro_html . '
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">' . $rows . '</table>
                            ' . $note_html . '
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 28px;background:#F8F9F4;border-top:1px solid #E5E7EB;">
                            ' . $footer_html . '
                            <p style="margin:6px 0 0;color:#9CA3AF;font-size:12px;">
                                © ' . $year . ' TANISH · <a href="' . $site . '" style="color:#0F3B5F;text-decoration:none;">tanish</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>';
}

/**
 * Envía un correo HTML desde TANISH.
 *
 * @param string $to      Destino.
 * @param string $subject Asunto.
 * @param array  $fields  Pares etiqueta => valor.
 * @param array  $args    intro, note, footer, reply_to.
 */
function tanish_send_html_mail(string $to, string $subject, array $fields, array $args = []): bool
{
    $html = tanish_email_html(
        $args['title']   ?? $subject,
        $fields,
        $args['intro']   ?? '',
        $args['note']    ?? '',
        $args['footer']  ?? ''
    );

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    if (!empty($args['reply_to'])) {
        $headers[] = 'Reply-To: ' . $args['reply_to'];
    }

    return (bool) wp_mail($to, $subject, $html, $headers);
}
