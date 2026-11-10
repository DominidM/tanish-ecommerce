<?php
/**
 * Gateway de pago "Mercado Pago (link)".
 *
 * Redirige el pago al link de Mercado Pago configurado en el admin.
 * A diferencia del plugin oficial de Mercado Pago (que exige credenciales
 * de API y devuelve el estado del pago por webhook), este metodo solo
 * necesita la URL del link: el cliente paga en Mercado Pago y el pedido
 * queda en "Pendiente" hasta que se marque como pagado a mano.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

/**
 * Metodo de pago que redirige a un link de Mercado Pago.
 */
class Tanish_MercadoPago_Link_Gateway extends WC_Payment_Gateway
{
    /** @var string Identificador del gateway. */
    public $id = 'tanish_mercadopago_link';

    /** @var string Titulo visible en el checkout. */
    public $method_title = 'Mercado Pago';

    /** @var string Descripcion visible en el checkout. */
    public $method_description = 'Te llevamos a Mercado Pago para completar el pago con tarjeta, Yape o PagoEfectivo.';

    /**
     * Constructor: registra hooks del gateway.
     */
    public function __construct()
    {
        $this->has_fields         = false;
        $this->method_title       = __('Mercado Pago', 'tanish-inventory');
        $this->method_description = __('Te llevamos a Mercado Pago para completar el pago con tarjeta, Yape o PagoEfectivo.', 'tanish-inventory');

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title', __('Mercado Pago', 'tanish-inventory'));
        $this->description = $this->get_option('description', $this->method_description);
        $this->enabled     = $this->get_option('enabled', 'no');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );
    }

    /**
     * Campos de configuracion en el admin.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'enabled'   => array(
                'title'   => __('Activar/Desactivar', 'tanish-inventory'),
                'type'    => 'checkbox',
                'label'   => __('Activar Mercado Pago', 'tanish-inventory'),
                'default' => 'no',
            ),
            'title'     => array(
                'title'   => __('Título', 'tanish-inventory'),
                'type'    => 'text',
                'default' => __('Mercado Pago', 'tanish-inventory'),
            ),
            'description' => array(
                'title'   => __('Descripción', 'tanish-inventory'),
                'type'    => 'textarea',
                'default' => __('Te llevamos a Mercado Pago para completar el pago con tarjeta, Yape o PagoEfectivo.', 'tanish-inventory'),
            ),
            'link_url'  => array(
                'title'       => __('Link de pago de Mercado Pago', 'tanish-inventory'),
                'type'        => 'text',
                'description' => __('URL del link de pago de Mercado Pago (ej. https://link.mercadopago.com.pe/tanish).', 'tanish-inventory'),
                'default'     => 'https://link.mercadopago.com.pe/tanish',
            ),
            'instructions' => array(
                'title'       => __('Instrucciones para el cliente', 'tanish-inventory'),
                'type'        => 'textarea',
                'description' => __('Texto que se muestra debajo del botón de pago.', 'tanish-inventory'),
                'default'     => __('Ingresa a Mercado Pago y paga el monto total del pedido. Luego confirma el pago para que preparemos tu pedido.', 'tanish-inventory'),
            ),
        );
    }

    /**
     * Pagina de pago: no hay formulario, el gateway redirige al link.
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return array('result' => 'failure');
        }

        $link = trim((string) $this->get_option('link_url', ''));

        if (empty($link)) {
            wc_add_notice(__('El pago con Mercado Pago no está disponible en este momento.', 'tanish-inventory'), 'error');
            return array('result' => 'failure');
        }

        // Guardamos el link en el pedido para tener trazabilidad.
        $order->update_meta_data('_tanish_mercadopago_link', esc_url_raw($link));
        $order->update_meta_data('_tanish_mercadopago_link_at', current_time('mysql'));
        $order->save();

        // El pedido queda pendiente: el pago se confirma manualmente.
        $order->update_status('on-hold', __('Esperando confirmación del pago en Mercado Pago.', 'tanish-inventory'));

        $order->reduce_stock_levels();

        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    /**
     * Mensaje de instrucciones en la pagina de thank-you / pedido recibido.
     */
    public function thankyou_page_order_received($order_id): void
    {
        $order = wc_get_order($order_id);

        if (!$order || $order->get_payment_method() !== $this->id) {
            return;
        }

        $instructions = $this->get_option('instructions', '');
        $link         = $order->get_meta('_tanish_mercadopago_link');

        if (empty($instructions) && empty($link)) {
            return;
        }

        echo '<div class="woocommerce-notice woocommerce-notice--info tanish-mp-instructions">';

        if (!empty($instructions)) {
            echo '<p>' . wp_kses_post($instructions) . '</p>';
        }

        if (!empty($link)) {
            echo '<p><a class="button" href="' . esc_url($link) . '" target="_blank" rel="noopener">'
                . __('Ir a Mercado Pago para pagar', 'tanish-inventory')
                . '</a></p>';
        }

        echo '</div>';
    }
}

/**
 * Registra el gateway en WooCommerce.
 */
function tanish_register_mercadopago_link_gateway($methods): array
{
    if (!is_array($methods)) {
        return $methods;
    }

    $methods[] = 'Tanish_MercadoPago_Link_Gateway';

    return $methods;
}
add_filter('woocommerce_payment_gateways', 'tanish_register_mercadopago_link_gateway');

/**
 * Muestra las instrucciones despues de confirmar el pedido.
 */
add_action(
    'woocommerce_thankyou_tanish_mercadopago_link',
    array('Tanish_MercadoPago_Link_Gateway', 'thankyou_page_order_received')
);
add_action(
    'woocommerce_view_order',
    array('Tanish_MercadoPago_Link_Gateway', 'thankyou_page_order_received')
);
