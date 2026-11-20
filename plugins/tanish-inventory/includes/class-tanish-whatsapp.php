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
}
