<?php
/**
 * Service layer for TANISH Inventory business rules.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

class Tanish_Inventory_Service {
	private Tanish_Inventory_Database $database;

	public function __construct(Tanish_Inventory_Database $database) {
		$this->database = $database;
	}

	public function get_products(): array {
		if (!class_exists('WooCommerce')) {
			return array();
		}

		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => -1,
				'orderby'  => 'title',
				'order'    => 'ASC',
			)
		);

		return is_array($products) ? $products : array();
	}

	public function get_dashboard_summary(): array {
		$products = $this->get_products();
		$total = 0;
		$in_stock = 0;
		$out_of_stock = 0;
		$low_stock = 0;

		foreach ($products as $product) {
			if (!is_a($product, 'WC_Product')) {
				continue;
			}

			$total++;
			$stock = (float) $product->get_stock_quantity();

			if ($stock > 0) {
				$in_stock++;
			}

			if ($stock <= 0) {
				$out_of_stock++;
			}

			if ($stock > 0 && $stock <= 5) {
				$low_stock++;
			}
		}

		return array(
			'total_products' => $total,
			'products_in_stock' => $in_stock,
			'products_out_of_stock' => $out_of_stock,
			'products_low_stock' => $low_stock,
		);
	}

	public function apply_movement(int $product_id, string $movement_type, float $quantity, string $reason, int $user_id = 0): WP_Error|array {
		$product = wc_get_product($product_id);

		if (!$product instanceof WC_Product) {
			return new WP_Error('invalid_product', 'El producto seleccionado no es válido.');
		}

		if (!in_array($movement_type, array('entry', 'exit', 'adjustment'), true)) {
			return new WP_Error('invalid_movement', 'Tipo de movimiento no válido.');
		}

		$quantity = (float) $quantity;

		if ('adjustment' === $movement_type) {
			if ($quantity < 0) {
				return new WP_Error('quantity', 'La cantidad no puede ser negativa.');
			}
		} elseif ($quantity <= 0) {
			return new WP_Error('quantity', 'La cantidad debe ser mayor que cero.');
		}

		if (!$product->get_manage_stock()) {
			return new WP_Error('stock_not_managed', 'El producto no tiene activada la gestión de stock.');
		}

		if (empty(trim($reason))) {
			return new WP_Error('missing_reason', 'El motivo es obligatorio.');
		}

		$stock_before = (float) $product->get_stock_quantity();
		$stock_after = $stock_before;

		if ('entry' === $movement_type) {
			$stock_after = $stock_before + $quantity;
		} elseif ('exit' === $movement_type) {
			if ($quantity > $stock_before) {
				return new WP_Error('insufficient_stock', 'La salida supera el stock disponible del producto.');
			}
			$stock_after = $stock_before - $quantity;
		} elseif ('adjustment' === $movement_type) {
			$stock_after = $quantity;
		}

		if ($stock_after < 0) {
			return new WP_Error('insufficient_stock', 'El stock resultante no puede ser negativo.');
		}

		$product->set_stock_quantity($stock_after);

		if (!$product->save()) {
			return new WP_Error('save_failed', 'No se pudo guardar el stock del producto.');
		}

		$quantity_change = $stock_after - $stock_before;
		$movement_id = $this->database->insert_movement(
			$product_id,
			$movement_type,
			$quantity_change,
			$stock_before,
			$stock_after,
			sanitize_textarea_field($reason),
			$user_id
		);

		if (0 === $movement_id) {
			// Revierte el stock para no dejarlo desincronizado con el historial.
			$product->set_stock_quantity($stock_before);
			$product->save();

			return new WP_Error('movement_failed', 'No se pudo registrar el movimiento en el historial.');
		}

		return array(
			'product_id' => $product_id,
			'stock_before' => $stock_before,
			'stock_after' => $stock_after,
			'movement_id' => $movement_id,
		);
	}
}
