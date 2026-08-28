<?php
/**
 * Admin setup and rendering for TANISH Inventory.
 *
 * @package Tanish_Inventory
 */

defined('ABSPATH') || exit;

class Tanish_Inventory_Admin {
	private Tanish_Inventory_Database $database;
	private Tanish_Inventory_Service $service;

	public function __construct(Tanish_Inventory_Database $database, Tanish_Inventory_Service $service) {
		$this->database = $database;
		$this->service = $service;

		add_action('admin_menu', array($this, 'register_menu'));
		add_action('admin_init', array($this, 'handle_form_submission'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
	}

	public function enqueue_assets($hook) {
		if ($hook && false === strpos($hook, 'tanish-inventory')) {
			return;
		}

		wp_enqueue_style(
			'tanish-inventory-admin',
			TANISH_INVENTORY_URL . 'assets/admin.css',
			array(),
			TANISH_INVENTORY_VERSION
		);
	}

	public function register_menu(): void {
		add_menu_page(
			'TANISH Inventory',
			'TANISH Inventory',
			'manage_tanish_inventory',
			'tanish-inventory',
			array($this, 'render_dashboard'),
			'dashicons-clipboard',
			26
		);

		add_submenu_page(
			'tanish-inventory',
			'Dashboard',
			'Dashboard',
			'manage_tanish_inventory',
			'tanish-inventory',
			array($this, 'render_dashboard')
		);

		add_submenu_page(
			'tanish-inventory',
			'Movimientos',
			'Movimientos',
			'manage_tanish_inventory',
			'tanish-inventory-movements',
			array($this, 'render_movements')
		);

		add_submenu_page(
			'tanish-inventory',
			'Nueva entrada',
			'Nueva entrada',
			'manage_tanish_inventory',
			'tanish-inventory-entry',
			array($this, 'render_entry_form')
		);

		add_submenu_page(
			'tanish-inventory',
			'Nueva salida',
			'Nueva salida',
			'manage_tanish_inventory',
			'tanish-inventory-exit',
			array($this, 'render_exit_form')
		);

		add_submenu_page(
			'tanish-inventory',
			'Ajuste de stock',
			'Ajuste de stock',
			'manage_tanish_inventory',
			'tanish-inventory-adjustment',
			array($this, 'render_adjustment_form')
		);

		add_submenu_page(
			'tanish-inventory',
			'Kardex',
			'Kardex',
			'manage_tanish_inventory',
			'tanish-inventory-kardex',
			array($this, 'render_kardex')
		);
	}

	public function handle_form_submission(): void {
		if (!is_admin()) {
			return;
		}

		if (!isset($_POST['tanish_inventory_action'])) {
			return;
		}

		$action = sanitize_key(wp_unslash($_POST['tanish_inventory_action']));
		$current_page = sanitize_key(wp_unslash($_POST['tanish_inventory_page'] ?? ''));

		if (!in_array($action, array('entry', 'exit', 'adjustment'), true)) {
			return;
		}

		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('No tienes permisos para realizar esta acción.');
		}

		$nonce_action = 'tanish_inventory_' . $action;
		check_admin_referer($nonce_action);

		$product_id = absint(wp_unslash($_POST['product_id'] ?? 0));
		$reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
		$user_id = get_current_user_id();

		if ($product_id <= 0 || empty($reason)) {
			wp_safe_redirect(add_query_arg('error', 'invalid', admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		$quantity = 0.0;
		if ('adjustment' === $action) {
			$quantity = (float) wp_unslash($_POST['new_stock'] ?? 0);
		} else {
			$quantity = (float) wp_unslash($_POST['quantity'] ?? 0);
		}

		if ($quantity <= 0 && 'adjustment' !== $action) {
			wp_safe_redirect(add_query_arg('error', 'quantity', admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		if ('exit' === $action) {
			$stock_now = (float) wc_get_product($product_id)->get_stock_quantity();
			if ($quantity > $stock_now) {
				wp_safe_redirect(add_query_arg('error', 'insufficient_stock', admin_url('admin.php?page=' . $current_page)));
				exit;
			}
		}

		if ('adjustment' === $action) {
			$product = wc_get_product($product_id);
			$stock_before = (float) $product->get_stock_quantity();
			$quantity = (float) $quantity;
			$delta = $quantity - $stock_before;
			$result = $this->service->apply_movement($product_id, 'adjustment', $quantity, $reason, $user_id);
			if (is_wp_error($result)) {
				wp_safe_redirect(add_query_arg('error', rawurlencode($result->get_error_message()), admin_url('admin.php?page=' . $current_page)));
				exit;
			}
			wp_safe_redirect(add_query_arg('status', 'success', admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		$result = $this->service->apply_movement($product_id, $action, $quantity, $reason, $user_id);

		if (is_wp_error($result)) {
			wp_safe_redirect(add_query_arg('error', rawurlencode($result->get_error_message()), admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		wp_safe_redirect(add_query_arg('status', 'success', admin_url('admin.php?page=' . $current_page)));
		exit;
	}

	public function render_dashboard(): void {
		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('Lo siento, no tienes permisos para acceder a esta página.');
		}

		$summary = $this->service->get_dashboard_summary();
		$current_user = wp_get_current_user();
		$dashboard_url = admin_url('admin.php?page=tanish-inventory');
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">TANISH Inventory</h1>
			<p>Resumen del control interno de stock.</p>
			<div class="notice notice-info inline">
				<p><strong>Usuario:</strong> <?php echo esc_html($current_user->user_login); ?> | <strong>Roles:</strong> <?php echo esc_html(implode(', ', $current_user->roles)); ?> | <strong>manage_tanish_inventory:</strong> <?php echo current_user_can('manage_tanish_inventory') ? 'true' : 'false'; ?></p>
			</div>

			<div class="tanish-inventory-cards">
				<div class="tanish-inventory-card">
					<span>Total de productos</span>
					<strong><?php echo esc_html((string) $summary['total_products']); ?></strong>
				</div>
				<div class="tanish-inventory-card success">
					<span>Productos con stock</span>
					<strong><?php echo esc_html((string) $summary['products_in_stock']); ?></strong>
				</div>
				<div class="tanish-inventory-card danger">
					<span>Productos agotados</span>
					<strong><?php echo esc_html((string) $summary['products_out_of_stock']); ?></strong>
				</div>
				<div class="tanish-inventory-card warning">
					<span>Productos con stock bajo</span>
					<strong><?php echo esc_html((string) $summary['products_low_stock']); ?></strong>
				</div>
			</div>

			<div class="tanish-inventory-actions">
				<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-entry')); ?>">Nueva entrada</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-exit')); ?>">Nueva salida</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-adjustment')); ?>">Ajustar stock</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-movements')); ?>">Ver movimientos</a>
			</div>
		</div>
		<?php
	}

	public function render_entry_form(): void {
		$this->render_movement_form('entry', 'Registrar entrada', 'Stock actual', 'Cantidad a ingresar', '+' );
	}

	public function render_exit_form(): void {
		$this->render_movement_form('exit', 'Registrar salida', 'Stock actual', 'Cantidad a descontar', '-');
	}

	public function render_adjustment_form(): void {
		$this->render_adjustment_movement_form();
	}

	private function render_movement_form(string $type, string $title, string $current_stock_label, string $qty_label, string $operator): void {
		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('Lo siento, no tienes permisos para acceder a esta página.');
		}

		$product_id = absint($_GET['product_id'] ?? 0);
		$product = $product_id ? wc_get_product($product_id) : null;
		$current_stock = $product && $product instanceof WC_Product ? (float) $product->get_stock_quantity() : 0;

		$this->render_form_header($type, $title, $product_id, $current_stock, $current_stock_label, $qty_label, $operator);
	}

	private function render_adjustment_movement_form(): void {
		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('Lo siento, no tienes permisos para acceder a esta página.');
		}

		$product_id = absint($_GET['product_id'] ?? 0);
		$product = $product_id ? wc_get_product($product_id) : null;
		$current_stock = $product && $product instanceof WC_Product ? (float) $product->get_stock_quantity() : 0;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Ajuste de stock</h1>
			<?php $this->render_status_messages(); ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-adjustment')); ?>">
				<?php wp_nonce_field('tanish_inventory_adjustment'); ?>
				<input type="hidden" name="tanish_inventory_action" value="adjustment" />
				<input type="hidden" name="tanish_inventory_page" value="tanish-inventory-adjustment" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="product_id">Producto</label></th>
						<td>
							<select id="product_id" name="product_id" required>
								<option value="">Selecciona un producto</option>
								<?php foreach ($this->service->get_products() as $item) { ?>
									<option value="<?php echo esc_attr((string) $item->get_id()); ?>" <?php selected($product_id, $item->get_id()); ?>><?php echo esc_html($item->get_name()); ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Stock actual</th>
						<td><strong><?php echo esc_html((string) $current_stock); ?></strong></td>
					</tr>
					<tr>
						<th scope="row"><label for="new_stock">Nuevo stock</label></th>
						<td><input type="number" id="new_stock" name="new_stock" min="0" step="1" value="<?php echo esc_attr((string) $current_stock); ?>" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="reason">Motivo</label></th>
						<td><textarea id="reason" name="reason" rows="3" required placeholder="Regularización por conteo físico"></textarea></td>
					</tr>
				</table>
				<?php submit_button('Guardar ajuste'); ?>
			</form>
		</div>
		<?php
	}

	private function render_form_header(string $type, string $title, int $product_id, float $current_stock, string $current_stock_label, string $qty_label, string $operator): void {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
			<?php $this->render_status_messages(); ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-' . $type)); ?>">
				<?php wp_nonce_field('tanish_inventory_' . $type); ?>
				<input type="hidden" name="tanish_inventory_action" value="<?php echo esc_attr($type); ?>" />
				<input type="hidden" name="tanish_inventory_page" value="tanish-inventory-<?php echo esc_attr($type); ?>" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="product_id">Producto</label></th>
						<td>
							<select id="product_id" name="product_id" required>
								<option value="">Selecciona un producto</option>
								<?php foreach ($this->service->get_products() as $item) { ?>
									<option value="<?php echo esc_attr((string) $item->get_id()); ?>" <?php selected($product_id, $item->get_id()); ?>><?php echo esc_html($item->get_name()); ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html($current_stock_label); ?></th>
						<td><strong><?php echo esc_html((string) $current_stock); ?></strong></td>
					</tr>
					<tr>
						<th scope="row"><label for="quantity"><?php echo esc_html($qty_label); ?></label></th>
						<td><input type="number" id="quantity" name="quantity" min="1" step="1" value="1" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="reason">Motivo</label></th>
						<td><textarea id="reason" name="reason" rows="3" required placeholder="Reposición de mercadería"></textarea></td>
					</tr>
				</table>
				<?php submit_button('Registrar ' . strtolower($title)); ?>
			</form>
		</div>
		<?php
	}

	private function render_status_messages(): void {
		if (isset($_GET['status']) && 'success' === sanitize_key(wp_unslash($_GET['status']))) {
			echo '<div class="notice notice-success is-dismissible"><p>Movimiento registrado correctamente.</p></div>';
		}

		if (isset($_GET['error'])) {
			$error_message = sanitize_text_field(wp_unslash($_GET['error']));
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($error_message)) . '</p></div>';
		}
	}

	public function render_movements(): void {
		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('Lo siento, no tienes permisos para acceder a esta página.');
		}

		$product_id = absint($_GET['product_id'] ?? 0);
		$type = sanitize_key(wp_unslash($_GET['movement_type'] ?? ''));
		$movements = $this->database->get_recent_movements($product_id, $type);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Movimientos</h1>
			<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="alignleft">
				<input type="hidden" name="page" value="tanish-inventory-movements" />
				<select name="product_id">
					<option value="0">Todos los productos</option>
					<?php foreach ($this->service->get_products() as $item) { ?>
						<option value="<?php echo esc_attr((string) $item->get_id()); ?>" <?php selected($product_id, $item->get_id()); ?>><?php echo esc_html($item->get_name()); ?></option>
					<?php } ?>
				</select>
				<select name="movement_type">
					<option value="">Todos los tipos</option>
					<option value="entry" <?php selected($type, 'entry'); ?>>Entrada</option>
					<option value="exit" <?php selected($type, 'exit'); ?>>Salida</option>
					<option value="adjustment" <?php selected($type, 'adjustment'); ?>>Ajuste</option>
				</select>
				<?php submit_button('Filtrar', 'small', false); ?>
			</form>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>Fecha</th>
						<th>Producto</th>
						<th>SKU</th>
						<th>Tipo</th>
						<th>Cantidad</th>
						<th>Stock anterior</th>
						<th>Stock posterior</th>
						<th>Motivo</th>
						<th>Usuario</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($movements)) : ?>
						<tr><td colspan="9">No hay movimientos registrados.</td></tr>
					<?php else : ?>
						<?php foreach ($movements as $movement) : ?>
							<?php $product = wc_get_product((int) $movement['product_id']); ?>
							<tr>
								<td><?php echo esc_html(mysql2date('d/m/Y H:i', $movement['created_at'])); ?></td>
								<td><?php echo esc_html($movement['product_name'] ?: __('Producto', 'tanish-inventory')); ?></td>
								<td><?php echo esc_html($product instanceof WC_Product ? $product->get_sku() : ''); ?></td>
								<td><?php echo esc_html($movement['movement_type']); ?></td>
								<td><?php echo esc_html((string) $movement['quantity_change']); ?></td>
								<td><?php echo esc_html((string) $movement['stock_before']); ?></td>
								<td><?php echo esc_html((string) $movement['stock_after']); ?></td>
								<td><?php echo esc_html($movement['reason']); ?></td>
								<td><?php echo esc_html($movement['user_name'] ?: 'Sistema'); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_kardex(): void {
		if (!current_user_can('manage_tanish_inventory')) {
			wp_die('Lo siento, no tienes permisos para acceder a esta página.');
		}

		$product_id = absint($_GET['product_id'] ?? 0);
		$kardex = array();
		if ($product_id > 0) {
			$kardex = $this->database->get_kardex($product_id);
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Kardex</h1>
			<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="alignleft">
				<input type="hidden" name="page" value="tanish-inventory-kardex" />
				<select name="product_id">
					<option value="0">Selecciona un producto</option>
					<?php foreach ($this->service->get_products() as $item) { ?>
						<option value="<?php echo esc_attr((string) $item->get_id()); ?>" <?php selected($product_id, $item->get_id()); ?>><?php echo esc_html($item->get_name()); ?></option>
					<?php } ?>
				</select>
				<?php submit_button('Ver kardex', 'small', false); ?>
			</form>
			<?php if ($product_id > 0 && !empty($kardex)) : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Tipo</th>
							<th>Entrada</th>
							<th>Salida</th>
							<th>Ajuste</th>
							<th>Stock resultante</th>
							<th>Motivo</th>
							<th>Usuario</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($kardex as $row) : ?>
							<tr>
								<td><?php echo esc_html(mysql2date('d/m/Y H:i', $row['created_at'])); ?></td>
								<td><?php echo esc_html($row['movement_type']); ?></td>
								<td><?php echo esc_html('entry' === $row['movement_type'] ? (string) $row['quantity_change'] : '-'); ?></td>
								<td><?php echo esc_html('exit' === $row['movement_type'] ? (string) abs((float) $row['quantity_change']) : '-'); ?></td>
								<td><?php echo esc_html('adjustment' === $row['movement_type'] ? (string) $row['quantity_change'] : '-'); ?></td>
								<td><?php echo esc_html((string) $row['stock_after']); ?></td>
								<td><?php echo esc_html($row['reason']); ?></td>
								<td><?php echo esc_html(get_userdata((int) $row['user_id'])->display_name ?? 'Sistema'); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php elseif ($product_id > 0) : ?>
				<p>No hay movimientos para este producto.</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
