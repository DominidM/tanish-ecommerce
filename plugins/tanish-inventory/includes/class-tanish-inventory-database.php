<?php
/**
 * Database layer for TANISH Inventory.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

class Tanish_Inventory_Database {
	public function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'tanish_inventory_movements';
	}

	public function create_table(): void {
		global $wpdb;

		$table_name = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			movement_type varchar(32) NOT NULL,
			quantity_change decimal(12,2) NOT NULL,
			stock_before decimal(12,2) NOT NULL,
			stock_after decimal(12,2) NOT NULL,
			reason text NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY movement_type (movement_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public function insert_movement(int $product_id, string $movement_type, float $quantity_change, float $stock_before, float $stock_after, string $reason, int $user_id = 0): int {
		global $wpdb;

		$table_name = $this->get_table_name();

		$result = $wpdb->insert(
			$table_name,
			array(
				'product_id'      => $product_id,
				'movement_type'   => $movement_type,
				'quantity_change' => $quantity_change,
				'stock_before'    => $stock_before,
				'stock_after'     => $stock_after,
				'reason'          => $reason,
				'user_id'         => $user_id,
				'created_at'      => current_time('mysql'),
			),
			array('%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s')
		);

		if (false === $result) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function get_recent_movements(int $product_id = 0, string $movement_type = ''): array {
		global $wpdb;

		$table = $this->get_table_name();
		$query = "SELECT m.*, p.post_title AS product_name, p.ID AS product_id, u.display_name AS user_name FROM {$table} m LEFT JOIN {$wpdb->posts} p ON p.ID = m.product_id LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id WHERE 1=1";
		$params = array();

		if ($product_id > 0) {
			$query .= ' AND m.product_id = %d';
			$params[] = $product_id;
		}

		if ('' !== $movement_type) {
			$query .= ' AND m.movement_type = %s';
			$params[] = $movement_type;
		}

		$query .= ' ORDER BY m.created_at DESC LIMIT 200';

		if (!empty($params)) {
			$query = $wpdb->prepare($query, $params);
		}

		return $wpdb->get_results($query, ARRAY_A);
	}

	public function get_kardex(int $product_id): array {
		global $wpdb;

		$table = $this->get_table_name();
		$query = $wpdb->prepare(
			"SELECT m.*, u.display_name AS user_name FROM {$table} m LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id WHERE m.product_id = %d ORDER BY m.created_at ASC",
			$product_id
		);

		return $wpdb->get_results($query, ARRAY_A);
	}
}
