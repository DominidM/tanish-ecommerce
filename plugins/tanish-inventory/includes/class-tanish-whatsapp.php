<?php
/**
 * WhatsApp integration for TANISH Inventory
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

/**
 * Class Tanish_WhatsApp
 *
 * Handles WhatsApp purchase integration for simple products.
 * - Settings page under WooCommerce > TANISH WhatsApp
 * - Button "Comprar por WhatsApp" on single product page
 * - Respects WooCommerce stock
 * - Removes traditional add-to-cart on single product
 * - Catalog "Ver producto" replacement via hooks
 */
class Tanish_WhatsApp {

	const OPTION_NAME = 'tanish_whatsapp_number';
	const OPTION_GROUP = 'tanish_whatsapp_settings';
	const CAPABILITY = 'manage_woocommerce';
	const MENU_SLUG = 'tanish-whatsapp';

	/**
	 * Constructor - registers hooks.
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));

		// Single product: button and removal of add-to-cart.
		// Use wp hook to ensure WooCommerce has registered its actions.
		add_action('wp', array($this, 'maybe_remove_add_to_cart'));
		add_action('woocommerce_single_product_summary', array($this, 'render_whatsapp_button'), 30);

		// Catalog - replace add to cart with "Ver producto".
		add_filter('woocommerce_loop_add_to_cart_link', array($this, 'filter_loop_add_to_cart'), 10, 2);
	}

	/**
	 * Add submenu page under WooCommerce.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			'TANISH WhatsApp',
			'TANISH WhatsApp',
			self::CAPABILITY,
			self::MENU_SLUG,
			array($this, 'render_settings_page')
		);
	}

	/**
	 * Register Settings API.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => array($this, 'sanitize_whatsapp_number'),
				'default'           => '',
			)
		);

		add_settings_section(
			'tanish_whatsapp_main',
			'Configuración de WhatsApp',
			'__return_false',
			self::MENU_SLUG
		);

		add_settings_field(
			self::OPTION_NAME,
			'Número de WhatsApp',
			array($this, 'render_number_field'),
			self::MENU_SLUG,
			'tanish_whatsapp_main'
		);
	}

	/**
	 * Sanitize WhatsApp number: only digits, includes country code.
	 *
	 * @param string $value Raw value.
	 * @return string Sanitized value.
	 */
	public function sanitize_whatsapp_number($value) {
		$value = sanitize_text_field($value);
		// Keep only numbers.
		$value = preg_replace('/\D/', '', $value);
		// Trim and ensure not empty.
		$value = trim($value);
		return $value;
	}

	/**
	 * Render number field.
	 */
	public function render_number_field() {
		$number = get_option(self::OPTION_NAME, '');
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>"
			value="<?php echo esc_attr($number); ?>"
			class="regular-text"
			placeholder="51987654321"
			pattern="[0-9]*"
			inputmode="numeric"
		/>
		<p class="description">
			Ingresa el número en formato internacional sin + ni espacios. Ejemplo para Perú: 51987654321
		</p>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if (!current_user_can(self::CAPABILITY)) {
			return;
		}
		?>
		<div class="wrap">
			<h1>TANISH WhatsApp</h1>
			<p>Configura el número de WhatsApp para el canal comercial inicial. WooCommerce seguirá gestionando productos, precios y stock.</p>
			<form action="options.php" method="post">
				<?php
				settings_fields(self::OPTION_GROUP);
				do_settings_sections(self::MENU_SLUG);
				submit_button('Guardar cambios');
				?>
			</form>
			<hr />
			<p><strong>Nota:</strong> Soporte inicial optimizado para <em>productos simples</em> de WooCommerce. Productos variables, bundles o externos se evaluarán en versiones futuras.</p>
		</div>
		<?php
	}

	/**
	 * Remove traditional add-to-cart on single product page.
	 * Hooked to wp to ensure WooCommerce has added its action.
	 * Can be reactivated easily by removing this hook in future version.
	 */
	public function maybe_remove_add_to_cart() {
		if (is_admin()) {
			return;
		}
		if (!function_exists('is_product') || !is_product()) {
			return;
		}
		// Remove core WooCommerce add to cart template on single product.
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
	}

	/**
	 * Render "Comprar por WhatsApp" button on single product summary.
	 * Respects stock and simple product type, and requires configured number.
	 */
	public function render_whatsapp_button() {
		global $product;

		if (!is_a($product, 'WC_Product')) {
			return;
		}

		// Compatibility: initial support only for simple products.
		if (!$product->is_type('simple')) {
			return;
		}

		// Respect WooCommerce stock: if out of stock, do not show.
		if (!$product->is_in_stock()) {
			return;
		}

		// Also respect purchasable check.
		if (!$product->is_purchasable()) {
			return;
		}

		$whatsapp_number = get_option(self::OPTION_NAME, '');
		$whatsapp_number = preg_replace('/\D/', '', $whatsapp_number);

		// If no number configured, do not generate invalid link.
		if (empty($whatsapp_number)) {
			return;
		}

		$product_name = $product->get_name();
		$sku          = $product->get_sku();
		$price_html   = $product->get_price_html();
		// Strip HTML to get readable price. Fallback to "Consultar".
		$price_text = !empty($price_html) ? wp_strip_all_tags($price_html) : 'Consultar';
		$permalink  = get_permalink($product->get_id());

		// Build message dynamically.
		$message_lines = array();
		$message_lines[] = 'Hola, deseo comprar este producto de TANISH.';
		$message_lines[] = '';
		$message_lines[] = 'Producto: ' . $product_name;
		if (!empty($sku)) {
			$message_lines[] = 'SKU: ' . $sku;
		}
		$message_lines[] = 'Precio: ' . $price_text;
		$message_lines[] = 'Enlace: ' . $permalink;
		$message_lines[] = '';
		$message_lines[] = '¿Podrían confirmarme disponibilidad y coordinar el pedido?';

		$message = implode("\n", $message_lines);
		$encoded_message = rawurlencode($message);

		$whatsapp_url = 'https://wa.me/' . $whatsapp_number . '?text=' . $encoded_message;

		// Use WooCommerce button classes for visual consistency.
		echo '<div class="tanish-whatsapp-wrapper" style="margin: 1em 0;">';
		echo '<a href="' . esc_url($whatsapp_url) . '" class="button alt tanish-whatsapp-button" target="_blank" rel="noopener noreferrer">';
		echo esc_html('Comprar por WhatsApp');
		echo '</a>';
		echo '</div>';
	}

	/**
	 * Filter catalog loop add-to-cart link to show "Ver producto".
	 * Cleanly via hook, easy to revert.
	 *
	 * @param string     $html    Original HTML.
	 * @param WC_Product $product Product object.
	 * @return string Modified HTML.
	 */
	public function filter_loop_add_to_cart($html, $product) {
		if (!is_a($product, 'WC_Product')) {
			return $html;
		}

		// Only modify if we are in shop loop; keep simple and light.
		// Replace with "Ver producto" linking to single product.
		$permalink = get_permalink($product->get_id());
		if (empty($permalink)) {
			return $html;
		}

		// For now, apply to all products in loop. Could restrict to simple if needed.
		// Keeping it clean and reversible.
		$text = esc_html('Ver producto');
		$url  = esc_url($permalink);

		return '<a href="' . $url . '" class="button">' . $text . '</a>';
	}
}
