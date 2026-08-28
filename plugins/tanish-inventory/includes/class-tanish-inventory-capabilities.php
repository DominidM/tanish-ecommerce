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
	public const VERSION = '0.3.0';

	public function __construct() {
		add_action('admin_init', array($this, 'ensure_capabilities'));
	}

	public function ensure_capabilities(): void {
		$stored = get_option(self::OPTION_VERSION, '');

		if ($stored === self::VERSION) {
			return;
		}

		$roles = array('administrator', 'shop_manager');

		foreach ($roles as $role_name) {
			$role = get_role($role_name);

			if ($role instanceof WP_Role) {
				$role->add_cap(self::CAPABILITY, true);
			}
		}

		update_option(self::OPTION_VERSION, self::VERSION, false);
	}
}
