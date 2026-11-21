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

		$css_path = TANISH_INVENTORY_PATH . 'assets/admin.css';

		wp_enqueue_style(
			'tanish-inventory-admin',
			TANISH_INVENTORY_URL . 'assets/admin.css',
			array(),
			file_exists($css_path) ? (string) filemtime($css_path) : TANISH_INVENTORY_VERSION
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
			'Registrar entrada',
			'Registrar entrada',
			'manage_tanish_inventory',
			'tanish-inventory-entry',
			array($this, 'render_entry_form')
		);

		add_submenu_page(
			'tanish-inventory',
			'Registrar salida',
			'Registrar salida',
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

		$product = wc_get_product($product_id);

		if (!$product instanceof WC_Product) {
			wp_safe_redirect(add_query_arg('error', 'invalid_product', admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		$quantity = 0.0;
		if ('adjustment' === $action) {
			$quantity = (float) wp_unslash($_POST['new_stock'] ?? 0);
		} else {
			$quantity = (float) wp_unslash($_POST['quantity'] ?? 0);
		}

		if ('adjustment' === $action) {
			if ($quantity < 0) {
				wp_safe_redirect(add_query_arg('error', 'quantity', admin_url('admin.php?page=' . $current_page)));
				exit;
			}
		} elseif ($quantity <= 0) {
			wp_safe_redirect(add_query_arg('error', 'quantity', admin_url('admin.php?page=' . $current_page)));
			exit;
		}

		if ('exit' === $action && $product->get_manage_stock()) {
			$stock_now = (float) $product->get_stock_quantity();
			if ($quantity > $stock_now) {
				wp_safe_redirect(add_query_arg('error', 'insufficient_stock', admin_url('admin.php?page=' . $current_page)));
				exit;
			}
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
			<p class="tanish-page-subtitle">Resumen del control interno de stock.</p>
			<div class="notice notice-info inline">
				<p><strong>Usuario:</strong> <?php echo esc_html($current_user->user_login); ?> | <strong>Roles:</strong> <?php echo esc_html(implode(', ', $current_user->roles)); ?> | <strong>manage_tanish_inventory:</strong> <?php echo current_user_can('manage_tanish_inventory') ? 'true' : 'false'; ?></p>
			</div>

			<div class="tanish-inventory-cards">
				<div class="tanish-inventory-card">
					<span class="tanish-card-icon dashicons dashicons-products" aria-hidden="true"></span>
					<div class="tanish-card-body">
						<span>Total de productos</span>
						<strong><?php echo esc_html((string) $summary['total_products']); ?></strong>
					</div>
				</div>
				<div class="tanish-inventory-card success">
					<span class="tanish-card-icon dashicons dashicons-chart-bar" aria-hidden="true"></span>
					<div class="tanish-card-body">
						<span>Productos con stock</span>
						<strong><?php echo esc_html((string) $summary['products_in_stock']); ?></strong>
					</div>
				</div>
				<div class="tanish-inventory-card danger">
					<span class="tanish-card-icon dashicons dashicons-dismiss" aria-hidden="true"></span>
					<div class="tanish-card-body">
						<span>Productos agotados</span>
						<strong><?php echo esc_html((string) $summary['products_out_of_stock']); ?></strong>
					</div>
				</div>
				<div class="tanish-inventory-card warning">
					<span class="tanish-card-icon dashicons dashicons-warning" aria-hidden="true"></span>
					<div class="tanish-card-body">
						<span>Productos con stock bajo</span>
						<strong><?php echo esc_html((string) $summary['products_low_stock']); ?></strong>
					</div>
				</div>
			</div>

			<div class="tanish-inventory-actions">
				<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-entry')); ?>"><span class="dashicons dashicons-plus" aria-hidden="true"></span>Nueva entrada</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-exit')); ?>"><span class="dashicons dashicons-minus" aria-hidden="true"></span>Nueva salida</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-adjustment')); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Ajustar stock</a>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tanish-inventory-movements')); ?>"><span class="dashicons dashicons-list-view" aria-hidden="true"></span>Ver movimientos</a>
			</div>

			<?php $this->render_dashboard_charts($summary); ?>
		</div>
		<?php
	}

	/**
	 * Gráficos del dashboard: movimientos por día y distribución del stock.
	 *
	 * @param array $summary Resumen del servicio.
	 */
	private function render_dashboard_charts(array $summary): void {
		$series = $this->database->get_daily_movements(14);

		echo '<div class="tanish-inv-charts">';
		$this->render_movements_chart($series);
		$this->render_stock_donut($summary);
		echo '</div>';
	}

	/**
	 * Gráfico de barras de entradas y salidas por día.
	 *
	 * @param array $series Serie diaria.
	 */
	private function render_movements_chart(array $series): void {
		$has_movements = false;
		$max           = 1.0;

		foreach ($series as $day) {
			if ($day['entries'] > 0 || $day['exits'] > 0) {
				$has_movements = true;
			}

			$max = max($max, (float) $day['entries'], (float) $day['exits']);
		}

		$width      = 760;
		$height     = 240;
		$pad_x      = 44;
		$pad_top    = 18;
		$pad_bottom = 40;
		$plot_w     = $width - ($pad_x * 2);
		$plot_h     = $height - $pad_top - $pad_bottom;
		$count      = max(count($series), 1);
		$group_w    = $plot_w / $count;
		$bar_w      = max(4.0, min(14.0, $group_w * 0.30));
		$label_step = (int) max(1, ceil($count / 7));

		$compact = static function (float $value): string {
			if ($value >= 1000) {
				return number_format_i18n($value / 1000, 1) . 'k';
			}

			return number_format_i18n($value, 0);
		};

		echo '<div class="tanish-inv-panel tanish-inv-panel--chart">';
		echo '<div class="tanish-inv-panel-head">';
		echo '<div><h2>' . esc_html('Movimientos de los últimos 14 días') . '</h2>';
		echo '<p class="tanish-inv-panel-sub">' . esc_html('Unidades ingresadas y retiradas por día.') . '</p></div>';
		echo '<div class="tanish-inv-legend"><span class="tanish-inv-legend-item"><i class="dot dot--entry"></i>' . esc_html('Entradas') . '</span>';
		echo '<span class="tanish-inv-legend-item"><i class="dot dot--exit"></i>' . esc_html('Salidas') . '</span></div>';
		echo '</div>';

		echo '<svg class="tanish-inv-chart" viewBox="0 0 ' . esc_attr((string) $width) . ' ' . esc_attr((string) $height) . '" role="img" aria-label="' . esc_attr('Entradas y salidas de los últimos 14 días') . '" preserveAspectRatio="none">';

		foreach (array(0, 0.25, 0.5, 0.75, 1) as $ratio) {
			$y = $pad_top + ($plot_h * (1 - $ratio));
			echo '<line class="tanish-inv-grid" x1="' . esc_attr((string) $pad_x) . '" y1="' . esc_attr((string) $y) . '" x2="' . esc_attr((string) ($width - $pad_x)) . '" y2="' . esc_attr((string) $y) . '"></line>';
			echo '<text class="tanish-inv-axis" x="' . esc_attr((string) ($pad_x - 8)) . '" y="' . esc_attr((string) ($y + 4)) . '" text-anchor="end">' . esc_html($compact($max * $ratio)) . '</text>';
		}

		foreach ($series as $index => $day) {
			$group_x = $pad_x + ($index * $group_w);
			$entry_h = $plot_h * ((float) $day['entries'] / $max);
			$exit_h  = $plot_h * ((float) $day['exits'] / $max);
			$entry_h = $day['entries'] > 0 ? max(3.0, $entry_h) : 0.0;
			$exit_h  = $day['exits'] > 0 ? max(3.0, $exit_h) : 0.0;

			$entry_x = $group_x + ($group_w / 2) - $bar_w - 1;
			$exit_x  = $group_x + ($group_w / 2) + 1;

			echo '<g>';
			echo '<title>' . esc_html($day['label'] . ': ' . number_format_i18n((float) $day['entries'], 2) . ' entradas / ' . number_format_i18n((float) $day['exits'], 2) . ' salidas') . '</title>';
			echo '<rect class="tanish-inv-bar tanish-inv-bar--entry" x="' . esc_attr((string) $entry_x) . '" y="' . esc_attr((string) ($pad_top + $plot_h - $entry_h)) . '" width="' . esc_attr((string) $bar_w) . '" height="' . esc_attr((string) $entry_h) . '" rx="3"></rect>';
			echo '<rect class="tanish-inv-bar tanish-inv-bar--exit" x="' . esc_attr((string) $exit_x) . '" y="' . esc_attr((string) ($pad_top + $plot_h - $exit_h)) . '" width="' . esc_attr((string) $bar_w) . '" height="' . esc_attr((string) $exit_h) . '" rx="3"></rect>';

			if ($index % $label_step === 0) {
				echo '<text class="tanish-inv-axis" x="' . esc_attr((string) ($group_x + ($group_w / 2))) . '" y="' . esc_attr((string) ($height - 12)) . '" text-anchor="middle">' . esc_html($day['label']) . '</text>';
			}

			echo '</g>';
		}

		echo '</svg>';

		if (!$has_movements) {
			echo '<p class="tanish-inv-empty-chart">' . esc_html('Todavía no hay movimientos en este periodo.') . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Dona de distribución del stock.
	 *
	 * @param array $summary Resumen del servicio.
	 */
	private function render_stock_donut(array $summary): void {
		$in_stock = (int) $summary['products_in_stock'];
		$low      = (int) $summary['products_low_stock'];
		$out      = (int) $summary['products_out_of_stock'];

		$healthy = max(0, $in_stock - $low);
		$total   = max(1, $healthy + $low + $out);

		$p_healthy = ($healthy / $total) * 100;
		$p_low     = $p_healthy + (($low / $total) * 100);

		$gradient = sprintf(
			'conic-gradient(#22C55E 0 %.2f%%, #FBBF24 %.2f%% %.2f%%, #EF4444 %.2f%% 100%%)',
			$p_healthy,
			$p_healthy,
			$p_low,
			$p_low
		);

		echo '<div class="tanish-inv-panel tanish-inv-panel--donut">';
		echo '<div class="tanish-inv-panel-head"><div><h2>' . esc_html('Distribución del stock') . '</h2>';
		echo '<p class="tanish-inv-panel-sub">' . esc_html('Estado actual del catálogo.') . '</p></div></div>';

		echo '<div class="tanish-donut-wrap">';
		echo '<div class="tanish-donut" style="background:' . esc_attr($gradient) . '" role="img" aria-label="' . esc_attr('Distribución del stock: ' . $healthy . ' disponibles, ' . $low . ' con stock bajo, ' . $out . ' agotados') . '">';
		echo '<div class="tanish-donut-center"><strong>' . esc_html(number_format_i18n($total)) . '</strong><span>' . esc_html('productos') . '</span></div>';
		echo '</div>';

		echo '<ul class="tanish-donut-legend">';
		echo '<li><span class="dot dot--healthy"></span>' . esc_html('Disponible') . '<strong>' . esc_html(number_format_i18n($healthy)) . '</strong></li>';
		echo '<li><span class="dot dot--low"></span>' . esc_html('Stock bajo') . '<strong>' . esc_html(number_format_i18n($low)) . '</strong></li>';
		echo '<li><span class="dot dot--out"></span>' . esc_html('Agotado') . '<strong>' . esc_html(number_format_i18n($out)) . '</strong></li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';
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
			<p class="tanish-page-subtitle">Fija el stock real del producto tras un conteo físico.</p>
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
						<td><strong class="tanish-stock-chip"><?php echo esc_html((string) $current_stock); ?></strong></td>
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
			<p class="tanish-page-subtitle"><?php echo esc_html('entry' === $type ? 'Registra el ingreso de unidades al inventario.' : 'Registra el descuento de unidades del inventario.'); ?></p>
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
						<td><strong class="tanish-stock-chip"><?php echo esc_html((string) $current_stock); ?></strong></td>
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
			$error_code = sanitize_text_field(wp_unslash($_GET['error']));
			$known_errors = array(
				'invalid'            => 'Los datos del formulario no son válidos.',
				'invalid_product'    => 'El producto seleccionado no existe o no es válido.',
				'quantity'           => 'La cantidad no es válida: debe ser mayor que cero, o cero o más para un ajuste.',
				'insufficient_stock' => 'La salida supera el stock disponible del producto.',
			);
			$error_message = $known_errors[$error_code] ?? urldecode($error_code);
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
		}
	}

	/**
	 * Render a presentational badge for a movement type.
	 *
	 * @param string $type Raw movement type.
	 */
	private function render_movement_badge(string $type): void {
		$labels = array(
			'entry'      => 'Entrada',
			'exit'       => 'Salida',
			'adjustment' => 'Ajuste',
		);

		$class = array_key_exists($type, $labels) ? $type : 'neutral';
		$label = $labels[$type] ?? ucfirst($type);

		printf(
			'<span class="tanish-badge tanish-badge--%1$s">%2$s</span>',
			esc_attr($class),
			esc_html($label)
		);
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
			<p class="tanish-page-subtitle">Historial de entradas, salidas y ajustes de inventario.</p>
			<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="alignleft tanish-filter-bar">
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
			<div class="tanish-table-wrap">
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
							<tr><td colspan="9" class="tanish-empty-state">No hay movimientos registrados.</td></tr>
						<?php else : ?>
							<?php foreach ($movements as $movement) : ?>
								<?php $product = wc_get_product((int) $movement['product_id']); ?>
								<tr>
									<td><?php echo esc_html(mysql2date('d/m/Y H:i', $movement['created_at'])); ?></td>
									<td><?php echo esc_html($movement['product_name'] ?: __('Producto', 'tanish-inventory')); ?></td>
									<td><?php echo esc_html($product instanceof WC_Product ? $product->get_sku() : ''); ?></td>
									<td><?php $this->render_movement_badge((string) $movement['movement_type']); ?></td>
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
			<p class="tanish-page-subtitle">Movimientos cronológicos y stock resultante por producto.</p>
			<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="alignleft tanish-filter-bar">
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
				<div class="tanish-table-wrap">
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
									<td><?php $this->render_movement_badge((string) $row['movement_type']); ?></td>
									<td><?php echo esc_html('entry' === $row['movement_type'] ? (string) $row['quantity_change'] : '-'); ?></td>
									<td><?php echo esc_html('exit' === $row['movement_type'] ? (string) abs((float) $row['quantity_change']) : '-'); ?></td>
									<td><?php echo esc_html('adjustment' === $row['movement_type'] ? (string) $row['quantity_change'] : '-'); ?></td>
									<td class="tanish-stock-value"><?php echo esc_html((string) $row['stock_after']); ?></td>
									<td><?php echo esc_html($row['reason']); ?></td>
									<td><?php echo esc_html(get_userdata((int) $row['user_id'])->display_name ?? 'Sistema'); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php elseif ($product_id > 0) : ?>
				<p class="tanish-empty-state">No hay movimientos para este producto.</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
