<?php
/**
 * Plugin Name: TANISH Inventory
 * Plugin URI: https://github.com/DominidM/tanish-ecommerce
 * Description: Gestión interna de inventario integrada con WooCommerce para TANISH S.A.C.
 * Version: 0.3.0
 * Author: Juan Dominid Muñoz Eslava
 * Text Domain: tanish-inventory
 */

defined('ABSPATH') || exit;

define('TANISH_INVENTORY_VERSION', '0.3.0');
define('TANISH_INVENTORY_FILE', __FILE__);
define('TANISH_INVENTORY_PATH', plugin_dir_path(__FILE__));
define('TANISH_INVENTORY_URL', plugin_dir_url(__FILE__));

function tanish_inventory_require_files(): void {
	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-whatsapp.php';	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-inventory-capabilities.php';	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-inventory-database.php';	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-inventory-service.php';	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-inventory-admin.php';
}

function tanish_inventory_enqueue_assets() {
	wp_enqueue_style(
		'tanish-storefront',
		TANISH_INVENTORY_URL . 'assets/tanish-storefront.css',
		array(),
		TANISH_INVENTORY_VERSION
	);
}
add_action('wp_enqueue_scripts', 'tanish_inventory_enqueue_assets');

function tanish_inventory_woocommerce_missing_notice() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong>TANISH Inventory</strong> requiere <strong>WooCommerce</strong> activo para las funciones comerciales y de inventario.
		</p>
	</div>
	<?php
}

function tanish_inventory_bootstrap() {
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'tanish_inventory_woocommerce_missing_notice');
		return;
	}

	tanish_inventory_require_files();

	$database = new Tanish_Inventory_Database();
	$database->create_table();

	new Tanish_Inventory_Capabilities();
	$service = new Tanish_Inventory_Service($database);
	new Tanish_Inventory_Admin($database, $service);

	if (class_exists('Tanish_WhatsApp')) {
		new Tanish_WhatsApp();
	}
}
add_action('plugins_loaded', 'tanish_inventory_bootstrap');
