<?php
/**
 * Capability management for TANISH Inventory.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

class Tanish_Inventory_Capabilities {
	public const CAPABILITY = 'manage_tanish_inventory';
	public const OPTION_VERSION = 'tanish_inventory_capability_version';
	public const VERSION = '0.4.0';

	public const ROLE_ALMACEN = 'almacen';
	public const ROLE_VENTAS = 'ventas';

	public function __construct() {
		// Se ejecuta en init para que los roles existan también fuera del admin.
		add_action('init', array($this, 'ensure_capabilities'));
		add_action('admin_init', array($this, 'ensure_capabilities'));
	}

	public function ensure_capabilities(): void {
		$stored = get_option(self::OPTION_VERSION, '');

		if ($stored === self::VERSION) {
			return;
		}

		$this->ensure_role(
			self::ROLE_ALMACEN,
			'Almacén',
			array('read', 'manage_tanish_inventory', 'edit_products'),
			array('manage_options', 'manage_woocommerce')
		);

		$this->ensure_role(
			self::ROLE_VENTAS,
			'Ventas',
			array('read', 'edit_products', 'edit_shop_orders', 'read_private_shop_orders'),
			array('manage_options', 'manage_woocommerce')
		);

		$roles = array('administrator', 'shop_manager');

		foreach ($roles as $role_name) {
			$role = get_role($role_name);

			if ($role instanceof WP_Role) {
				$role->add_cap(self::CAPABILITY, true);
			}
		}

		update_option(self::OPTION_VERSION, self::VERSION, false);
	}

	/**
	 * Crea el rol si no existe y sincroniza sus capabilities.
	 *
	 * @param string   $slug   Slug del rol.
	 * @param string   $name   Nombre visible del rol.
	 * @param string[] $caps   Capabilities que debe tener.
	 * @param string[] $remove Capabilities que no debe tener.
	 */
	private function ensure_role(string $slug, string $name, array $caps, array $remove = array()): void {
		$role = get_role($slug);

		if (!$role instanceof WP_Role) {
			add_role($slug, $name, array_fill_keys($caps, true));
			$role = get_role($slug);
		}

		if (!$role instanceof WP_Role) {
			return;
		}

		foreach ($caps as $cap) {
			$role->add_cap($cap, true);
		}

		foreach ($remove as $cap) {
			$role->remove_cap($cap);
		}
	}
}
