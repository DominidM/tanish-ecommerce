<?php
/**
 * Pasarela de pago "Yape (simulado)".
 *
 * Pasarela ficticia y totalmente offline para pruebas: no realiza ningún
 * cargo real ni llama a servicios externos.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

/**
 * Método de pago Yape simulado.
 */
class Tanish_Yape_Gateway extends WC_Payment_Gateway {

	/** @var string Identificador del gateway. */
	public $id = 'tanish_yape';

	/**
	 * Constructor: registra hooks del gateway.
	 */
	public function __construct() {
		$this->id                 = 'tanish_yape';
		$this->has_fields         = false;
		$this->method_title       = __('Yape (simulado)', 'tanish-inventory');
		$this->method_description = __('Pago ficticio con Yape para pruebas. No se realiza ningún cargo real.', 'tanish-inventory');
		$this->supports           = array('products');

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option('title', __('Yape (simulado)', 'tanish-inventory'));
		$this->description = $this->get_option('description', __('Pago ficticio con Yape para pruebas. No se realiza ningún cargo real.', 'tanish-inventory'));
		$this->enabled     = $this->get_option('enabled', 'yes');

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array($this, 'process_admin_options')
		);
		add_action(
			'woocommerce_thankyou_' . $this->id,
			array($this, 'thankyou_page_order_received')
		);
		add_action(
			'woocommerce_view_order',
			array($this, 'thankyou_page_order_received')
		);
	}

	/**
	 * Campos de configuración en el admin.
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __('Activar/Desactivar', 'tanish-inventory'),
				'type'    => 'checkbox',
				'label'   => __('Activar Yape (simulado)', 'tanish-inventory'),
				'default' => 'yes',
			),
			'title'       => array(
				'title'   => __('Título', 'tanish-inventory'),
				'type'    => 'text',
				'default' => __('Yape (simulado)', 'tanish-inventory'),
			),
			'description' => array(
				'title'   => __('Descripción', 'tanish-inventory'),
				'type'    => 'textarea',
				'default' => __('Pago ficticio con Yape para pruebas. No se realiza ningún cargo real.', 'tanish-inventory'),
			),
		);
	}

	/**
	 * Procesa el pago simulado: deja el pedido en espera y descuenta stock.
	 *
	 * @param int $order_id ID del pedido.
	 * @return array
	 */
	public function process_payment($order_id) {
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order) {
			return array('result' => 'failure');
		}

		$order->update_meta_data('_tanish_yape_simulated', 'yes');
		$order->save();

		// El pedido queda a la espera de confirmación manual.
		$order->update_status('on-hold', __('Pago Yape simulado', 'tanish-inventory'));

		$this->maybe_reduce_stock($order);

		if (WC()->cart) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url($order),
		);
	}

	/**
	 * Reduce el stock del pedido una sola vez.
	 *
	 * El cambio a "on-hold" ya puede haber descontado el stock vía
	 * wc_maybe_reduce_stock_levels(), así que comprobamos la bandera interna.
	 *
	 * @param WC_Order $order Pedido.
	 */
	private function maybe_reduce_stock(WC_Order $order): void {
		$data_store    = $order->get_data_store();
		$stock_reduced = false;

		if ($data_store && method_exists($data_store, 'get_stock_reduced')) {
			$stock_reduced = (bool) $data_store->get_stock_reduced($order);
		}

		if ($stock_reduced) {
			return;
		}

		if (function_exists('wc_reduce_stock_levels')) {
			wc_reduce_stock_levels($order);
		} elseif (method_exists($order, 'reduce_order_stock')) {
			$order->reduce_order_stock();
		}

		if ($data_store && method_exists($data_store, 'set_stock_reduced')) {
			$data_store->set_stock_reduced($order, true);
		}
	}

	/**
	 * Instrucciones ficticias en la página de pedido recibido / detalle.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public function thankyou_page_order_received($order_id): void {
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order || $order->get_payment_method() !== $this->id) {
			return;
		}

		$message_type = '';
		$message_text = '';

		if (isset($_POST['tanish_yape_mark_paid'])) {
			$nonce = isset($_POST['tanish_yape_nonce']) ? sanitize_text_field(wp_unslash($_POST['tanish_yape_nonce'])) : '';

			if (current_user_can('manage_woocommerce') && wp_verify_nonce($nonce, 'tanish_yape_mark_paid')) {
				$order->update_status('processing', __('Pago Yape simulado confirmado manualmente.', 'tanish-inventory'));

				if (!$order->get_date_paid()) {
					$order->set_date_paid(time());
					$order->save();
				}

				$message_type = 'success';
				$message_text = __('Pedido marcado como pagado (simulación).', 'tanish-inventory');
			} else {
				$message_type = 'error';
				$message_text = __('No tienes permisos para marcar este pedido como pagado.', 'tanish-inventory');
			}
		}

		echo '<div class="woocommerce-notice woocommerce-notice--info tanish-yape-instructions">';

		if (!empty($message_text)) {
			echo '<div class="woocommerce-notice woocommerce-notice--' . esc_attr($message_type) . '">'
				. esc_html($message_text)
				. '</div>';
		}

		echo '<p><strong>' . esc_html__('Instrucciones de pago Yape (simulado)', 'tanish-inventory') . '</strong></p>';
		echo '<p>' . esc_html__('Número Yape: 999 999 999', 'tanish-inventory') . '</p>';
		echo '<p>' . esc_html__('Pago simulado: no se ha cobrado nada.', 'tanish-inventory') . '</p>';

		if (current_user_can('manage_woocommerce') && !$order->is_paid()) {
			echo '<form method="post" action="">';
			wp_nonce_field('tanish_yape_mark_paid', 'tanish_yape_nonce');
			echo '<button type="submit" name="tanish_yape_mark_paid" value="1" class="button">'
				. esc_html__('Marcar como pagado (simulado)', 'tanish-inventory')
				. '</button>';
			echo '</form>';
		}

		echo '</div>';
	}
}

/**
 * Registra el gateway en WooCommerce.
 *
 * @param array $methods Gateways registrados.
 * @return array
 */
function tanish_register_yape_gateway($methods) {
	if (!is_array($methods)) {
		return $methods;
	}

	$methods[] = 'Tanish_Yape_Gateway';

	return $methods;
}
add_filter('woocommerce_payment_gateways', 'tanish_register_yape_gateway');
