<?php

/**
 * Plugin Name: TANISH Inventory
 * Plugin URI: https://github.com/DominidM/tanish-ecommerce
 * Description: Gestión interna de inventario integrada con WooCommerce para TANISH S.A.C.
 * Version: 0.2.1
 * Author: Juan Dominid Muñoz Eslava
 * Text Domain: tanish-inventory
 */

defined('ABSPATH') || exit;

// Plugin version.
define('TANISH_INVENTORY_VERSION', '0.2.1');
define('TANISH_INVENTORY_FILE', __FILE__);
define('TANISH_INVENTORY_PATH', plugin_dir_path(__FILE__));
define('TANISH_INVENTORY_URL', plugin_dir_url(__FILE__));

/**
 * Enqueue storefront styles (sobrio, profesional).
 */
function tanish_inventory_enqueue_assets() {
	wp_enqueue_style(
		'tanish-storefront',
		TANISH_INVENTORY_URL . 'assets/tanish-storefront.css',
		array(),
		TANISH_INVENTORY_VERSION
	);
}
add_action('wp_enqueue_scripts', 'tanish_inventory_enqueue_assets');

/**
 * Show admin notice if WooCommerce is not active.
 */
function tanish_inventory_woocommerce_missing_notice() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong>TANISH Inventory</strong> requiere <strong>WooCommerce</strong> activo para las funciones comerciales (WhatsApp). Instala y activa WooCommerce para habilitar la integración.
		</p>
	</div>
	<?php
}

/**
 * Initialize plugin after all plugins loaded (to check WooCommerce).
 */
function tanish_inventory_init() {
	// Dependency check: WooCommerce must be active.
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'tanish_inventory_woocommerce_missing_notice');
		return;
	}

	// Load WhatsApp integration (simple products initial support).
	require_once TANISH_INVENTORY_PATH . 'includes/class-tanish-whatsapp.php';

	if (class_exists('Tanish_WhatsApp')) {
		new Tanish_WhatsApp();
	}
}
add_action('plugins_loaded', 'tanish_inventory_init');
