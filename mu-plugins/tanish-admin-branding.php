<?php
/**
 * Plugin Name: TANISH - Panel administrativo
 * Description: Identidad visual TANISH en el panel de WordPress: login, barra superior, menú lateral, migaja de pan y dashboard a medida.
 * Version: 1.0.0
 * Author: TANISH
 */

defined('ABSPATH') || exit;

/**
 * Versión de un asset basada en su fecha de modificación.
 *
 * @param string $relative_path Ruta relativa al directorio de este mu-plugin.
 * @return string
 */
function tanish_admin_asset_version(string $relative_path): string
{
	$file = __DIR__ . '/' . ltrim($relative_path, '/\\');

	if (is_readable($file)) {
		$mtime = filemtime($file);

		if (false !== $mtime) {
			return (string) $mtime;
		}
	}

	return (string) wp_get_theme()->get('Version');
}

/**
 * Minutos de caché para los conteos del panel.
 *
 * @return int
 */
function tanish_admin_cache_ttl(): int
{
	return 5 * MINUTE_IN_SECONDS;
}

/**
 * Productos publicados (0 si WooCommerce no está activo).
 *
 * @return int
 */
function tanish_admin_published_products(): int
{
	if (!post_type_exists('product')) {
		return 0;
	}

	$counts = wp_count_posts('product');

	return isset($counts->publish) ? (int) $counts->publish : 0;
}

/* -------------------------------------------------------------------------
 * 1. Carga de assets
 * ---------------------------------------------------------------------- */

add_action('admin_enqueue_scripts', function (): void {
	$relative = 'tanish-admin/assets/admin-branding.css';

	wp_enqueue_style(
		'tanish-admin-branding',
		plugin_dir_url(__FILE__) . $relative,
		array(),
		tanish_admin_asset_version($relative)
	);
});

add_action('login_enqueue_scripts', function (): void {
	$relative = 'tanish-admin/assets/login-branding.css';

	wp_enqueue_style(
		'tanish-login-branding',
		plugin_dir_url(__FILE__) . $relative,
		array(),
		tanish_admin_asset_version($relative)
	);
});

/* -------------------------------------------------------------------------
 * 2. Branding general del admin
 * ---------------------------------------------------------------------- */

add_filter('admin_footer_text', function (): string {
	return 'TANISH · Panel de gestión';
});

add_filter('update_footer', function (): string {
	return sprintf('Versión %s', esc_html(get_bloginfo('version')));
}, 11);

/* -------------------------------------------------------------------------
 * 3. Login de WordPress
 * ---------------------------------------------------------------------- */

add_filter('login_headerurl', function (): string {
	return home_url('/');
});

add_filter('login_headertext', function (): string {
	return 'TANISH';
});

/**
 * WordPress manda a profile.php a quien no tiene edit_posts. El personal de
 * tienda (ventas/almacén) debe aterrizar en el dashboard, que es su panel.
 *
 * @param string $redirect_to          Destino calculado.
 * @param string $requested_redirect_to Destino solicitado.
 * @param mixed  $user                 Usuario autenticado.
 * @return string
 */
function tanish_admin_login_redirect(string $redirect_to, string $requested_redirect_to, $user): string
{
	if (!$user instanceof WP_User) {
		return $redirect_to;
	}

	$is_admin_target = '' === $redirect_to
		|| 'wp-admin/' === $redirect_to
		|| admin_url() === $redirect_to
		|| admin_url('index.php') === $redirect_to;

	if (!$is_admin_target) {
		return $redirect_to;
	}

	if ($user->has_cap('manage_tanish_inventory') || $user->has_cap('edit_shop_orders')) {
		return admin_url('index.php');
	}

	return $redirect_to;
}
add_filter('login_redirect', 'tanish_admin_login_redirect', 10, 3);

/**
 * Bloque de marca sobre la caja de login.
 */
add_action('login_header', function (): void {
	$icon = get_site_icon_url(180);

	echo '<div class="tanish-login-brand">';
	echo '<a class="tanish-login-brand-link" href="' . esc_url(home_url('/')) . '" title="' . esc_attr('TANISH') . '" rel="home">';

	if ($icon) {
		echo '<img class="tanish-login-logo" src="' . esc_url($icon) . '" alt="' . esc_attr('TANISH') . '" width="80" height="80" decoding="async" />';
	} else {
		echo '<span class="tanish-login-wordmark">' . esc_html('TANISH') . '</span>';
	}

	echo '</a>';
	echo '<p class="tanish-login-tagline">' . esc_html('Panel de gestión') . '</p>';
	echo '</div>';
});

/* -------------------------------------------------------------------------
 * 4. Barra superior (admin bar)
 * ---------------------------------------------------------------------- */

/**
 * Conteos de inventario cacheados: publicados, stock bajo (1-5) y agotados.
 *
 * @return array{published:int,low:int,out:int}
 */
function tanish_admin_get_inventory_stats(): array
{
	$defaults = array(
		'published' => 0,
		'low'       => 0,
		'out'       => 0,
	);

	$cached = get_transient('tanish_admin_inventory_stats');

	if (is_array($cached)) {
		return wp_parse_args($cached, $defaults);
	}

	if (!post_type_exists('product')) {
		return $defaults;
	}

	$stats = $defaults;
	$stats['published'] = tanish_admin_published_products();

	$low_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_query'          => array(
				'relation' => 'AND',
				array(
					'key'   => '_manage_stock',
					'value' => 'yes',
				),
				array(
					'key'     => '_stock',
					'value'   => array(1, 5),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	$stats['low'] = (int) $low_query->post_count;
	wp_reset_postdata();

	$out_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_query'          => array(
				array(
					'key'   => '_stock_status',
					'value' => 'outofstock',
				),
			),
		)
	);
	$stats['out'] = (int) $out_query->post_count;
	wp_reset_postdata();

	set_transient('tanish_admin_inventory_stats', $stats, tanish_admin_cache_ttl());

	return $stats;
}

/**
 * Pedidos abiertos y ventas del mes en curso, cacheados.
 *
 * @return array{open_orders:int,month_sales:float}
 */
function tanish_admin_get_sales_stats(): array
{
	$defaults = array(
		'open_orders' => 0,
		'month_sales' => 0.0,
	);

	$cached = get_transient('tanish_admin_sales_stats');

	if (is_array($cached)) {
		return wp_parse_args($cached, $defaults);
	}

	if (!function_exists('wc_get_orders')) {
		return $defaults;
	}

	$stats = $defaults;

	$open = wc_get_orders(
		array(
			'status'   => array('pending', 'processing'),
			'limit'    => 1,
			'paginate' => true,
			'return'   => 'ids',
		)
	);

	if (is_object($open) && isset($open->total)) {
		$stats['open_orders'] = (int) $open->total;
	}

	$date_range = current_time('Y-m-01 00:00:00') . '...' . current_time('Y-m-t 23:59:59');

	$orders = wc_get_orders(
		array(
			'status'       => array('processing', 'completed'),
			'limit'        => -1,
			'return'       => 'objects',
			'paginate'     => false,
			'date_created' => $date_range,
		)
	);

	$total = 0.0;

	foreach ($orders as $order) {
		if ($order instanceof WC_Order) {
			$total += (float) $order->get_total();
		}
	}

	$stats['month_sales'] = $total;

	set_transient('tanish_admin_sales_stats', $stats, tanish_admin_cache_ttl());

	return $stats;
}

/**
 * Serie diaria de ventas para el gráfico del dashboard.
 *
 * @param int $days Número de días a mostrar (7-31).
 * @return array<int,array{date:string,label:string,total:float,count:int}>
 */
function tanish_admin_get_sales_series(int $days = 14): array
{
	$days   = max(7, min(31, $days));
	$key    = 'tanish_admin_sales_series_' . $days;
	$cached = get_transient($key);

	if (is_array($cached)) {
		return $cached;
	}

	$tz    = wp_timezone();
	$today = new DateTimeImmutable('today', $tz);
	$start = $today->modify('-' . ($days - 1) . ' days');

	$series = array();

	for ($i = 0; $i < $days; $i++) {
		$day = $start->modify('+' . $i . ' days');

		$series[$day->format('Y-m-d')] = array(
			'date'  => $day->format('Y-m-d'),
			'label' => $day->format('d/m'),
			'total' => 0.0,
			'count' => 0,
		);
	}

	if (function_exists('wc_get_orders')) {
		$orders = wc_get_orders(
			array(
				'status'       => array('processing', 'completed', 'on-hold'),
				'limit'        => -1,
				'return'       => 'objects',
				'date_created' => $start->format('Y-m-d 00:00:00') . '...' . $today->format('Y-m-d 23:59:59'),
			)
		);

		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}

			$created = $order->get_date_created();

			if (!$created) {
				continue;
			}

			$day = $created->date('Y-m-d');

			if (!isset($series[$day])) {
				continue;
			}

			$series[$day]['total'] += (float) $order->get_total();
			$series[$day]['count']++;
		}
	}

	$series = array_values($series);
	set_transient($key, $series, tanish_admin_cache_ttl());

	return $series;
}

/**
 * Últimos pedidos para el dashboard.
 *
 * @param int $limit Número de pedidos.
 * @return array<int,array{id:int,number:string,status:string,label:string,total:float,name:string,date:string}>
 */
function tanish_admin_get_recent_orders(int $limit = 5): array
{
	if (!function_exists('wc_get_orders')) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'limit'   => max(1, $limit),
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'),
		)
	);

	$out = array();

	foreach ($orders as $order) {
		if (!$order instanceof WC_Order) {
			continue;
		}

		$created = $order->get_date_created();

		$out[] = array(
			'id'     => $order->get_id(),
			'number' => $order->get_order_number(),
			'status' => $order->get_status(),
			'label'  => wc_get_order_status_name($order->get_status()),
			'total'  => (float) $order->get_total(),
			'name'   => trim((string) $order->get_formatted_billing_full_name()),
			'date'   => $created ? $created->date_i18n('d/m H:i') : '',
		);
	}

	return $out;
}

/**
 * Productos con stock bajo (1-5) para el dashboard.
 *
 * @param int $limit Número de productos.
 * @return array<int,array{id:int,name:string,stock:int,sku:string,link:string}>
 */
function tanish_admin_get_low_stock_products(int $limit = 5): array
{
	if (!post_type_exists('product')) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => max(1, $limit),
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_key'            => '_stock',
			'orderby'             => 'meta_value_num',
			'order'               => 'ASC',
			'meta_query'          => array(
				'relation' => 'AND',
				array(
					'key'   => '_manage_stock',
					'value' => 'yes',
				),
				array(
					'key'     => '_stock',
					'value'   => array(1, 5),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	$out = array();

	foreach ($query->posts as $product_id) {
		$out[] = array(
			'id'    => (int) $product_id,
			'name'  => get_the_title($product_id),
			'stock' => (int) get_post_meta($product_id, '_stock', true),
			'sku'   => (string) get_post_meta($product_id, '_sku', true),
			'link'  => (string) get_edit_post_link($product_id, 'raw'),
		);
	}

	wp_reset_postdata();

	return $out;
}

/**
 * Productos más vendidos de los últimos 30 días.
 *
 * @param int $limit Número de productos.
 * @return array<int,array{id:int,name:string,qty:int,revenue:float,link:string}>
 */
function tanish_admin_get_top_products(int $limit = 5): array
{
	global $wpdb;

	$table = $wpdb->prefix . 'wc_order_product_lookup';

	if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
		return array();
	}

	$since = wp_date('Y-m-d H:i:s', strtotime('-30 days'));

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT product_id, SUM(product_qty) AS qty, SUM(product_gross_revenue) AS revenue
			FROM {$table}
			WHERE date_created >= %s
			GROUP BY product_id
			ORDER BY qty DESC
			LIMIT %d",
			$since,
			max(1, $limit)
		)
	);

	$out = array();

	foreach ($rows as $row) {
		$product_id = (int) $row->product_id;

		$out[] = array(
			'id'      => $product_id,
			'name'    => get_the_title($product_id),
			'qty'     => (int) $row->qty,
			'revenue' => (float) $row->revenue,
			'link'    => (string) get_edit_post_link($product_id, 'raw'),
		);
	}

	return $out;
}

/**
 * Clase de color para el estado de un pedido.
 */
function tanish_admin_order_status_class(string $status): string
{
	$map = array(
		'completed'  => 'success',
		'processing' => 'info',
		'pending'    => 'warning',
		'on-hold'    => 'warning',
		'cancelled'  => 'danger',
		'refunded'   => 'neutral',
		'failed'     => 'danger',
	);

	return $map[$status] ?? 'neutral';
}

/**
 * Categorías de producto con más artículos.
 *
 * @param int $limit Número de categorías.
 * @return array<int,array{name:string,count:int,link:string}>
 */
function tanish_admin_get_category_stats(int $limit = 6): array
{
	if (!taxonomy_exists('product_cat')) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => max(1, $limit),
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if (is_wp_error($terms) || empty($terms)) {
		return array();
	}

	$out = array();

	foreach ($terms as $term) {
		$link = get_term_link($term);

		$out[] = array(
			'name'  => $term->name,
			'count' => (int) $term->count,
			'link'  => is_wp_error($link) ? '' : (string) $link,
		);
	}

	return $out;
}

/**
 * Pinta el gráfico SVG de ventas diarias.
 *
 * @param array<int,array{date:string,label:string,total:float,count:int}> $series Serie diaria.
 */
function tanish_admin_render_sales_chart(array $series): void
{
	if (empty($series)) {
		return;
	}

	$totals = array_map(static fn($day) => (float) $day['total'], $series);
	$max    = max($totals);
	$max    = $max > 0 ? $max : 1.0;
	$total  = array_sum($totals);
	$orders = array_sum(array_map(static fn($day) => (int) $day['count'], $series));

	$width      = 760;
	$height     = 230;
	$pad_x      = 42;
	$pad_top    = 16;
	$pad_bottom = 38;
	$plot_w     = $width - ($pad_x * 2);
	$plot_h     = $height - $pad_top - $pad_bottom;
	$count      = max(count($series), 1);
	$gap        = $count > 20 ? 3 : 7;
	$bar_w      = max(4.0, ($plot_w - ($gap * ($count - 1))) / $count);

	$currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';

	$compact = static function (float $value): string {
		if ($value >= 1000000) {
			return number_format_i18n($value / 1000000, 1) . 'M';
		}

		if ($value >= 1000) {
			return number_format_i18n($value / 1000, 1) . 'k';
		}

		return number_format_i18n($value, 0);
	};

	echo '<div class="tanish-chart">';
	echo '<div class="tanish-chart-head">';
	echo '<div><h3>' . esc_html('Ventas de los últimos ' . count($series) . ' días') . '</h3>';
	echo '<p class="tanish-chart-sub">' . esc_html(sprintf('%s pedidos · total %s%s', number_format_i18n($orders), $currency, number_format_i18n($total, 2))) . '</p></div>';
	echo '</div>';

	echo '<svg class="tanish-chart-svg" viewBox="0 0 ' . esc_attr((string) $width) . ' ' . esc_attr((string) $height) . '" role="img" aria-label="' . esc_attr('Ventas diarias de los últimos ' . count($series) . ' días') . '" preserveAspectRatio="none">';

	// Rejilla horizontal y etiquetas del eje Y.
	foreach (array(0, 0.25, 0.5, 0.75, 1) as $ratio) {
		$y = $pad_top + ($plot_h * (1 - $ratio));
		echo '<line class="tanish-chart-grid" x1="' . esc_attr((string) $pad_x) . '" y1="' . esc_attr((string) $y) . '" x2="' . esc_attr((string) ($width - $pad_x)) . '" y2="' . esc_attr((string) $y) . '"></line>';
		echo '<text class="tanish-chart-y" x="' . esc_attr((string) ($pad_x - 8)) . '" y="' . esc_attr((string) ($y + 4)) . '" text-anchor="end">' . esc_html($compact($max * $ratio)) . '</text>';
	}

	$label_step = (int) max(1, ceil($count / 7));

	foreach ($series as $index => $day) {
		$x    = $pad_x + ($index * ($bar_w + $gap));
		$barH = $plot_h * ($day['total'] / $max);
		$barH = max($day['total'] > 0 ? 3 : 0, $barH);
		$y    = $pad_top + $plot_h - $barH;

		echo '<g class="tanish-chart-bar">';
		echo '<title>' . esc_html($day['label'] . ': ' . $currency . number_format_i18n((float) $day['total'], 2) . ' (' . (int) $day['count'] . ' pedidos)') . '</title>';
		echo '<rect x="' . esc_attr((string) $x) . '" y="' . esc_attr((string) $y) . '" width="' . esc_attr((string) $bar_w) . '" height="' . esc_attr((string) $barH) . '" rx="4"></rect>';

		if ($index % $label_step === 0) {
			echo '<text class="tanish-chart-x" x="' . esc_attr((string) ($x + ($bar_w / 2))) . '" y="' . esc_attr((string) ($height - 12)) . '" text-anchor="middle">' . esc_html($day['label']) . '</text>';
		}

		echo '</g>';
	}

	echo '</svg>';
	echo '</div>';
}

/**
 * Añade el nodo TANISH y sus accesos a la barra superior.
 *
 * @param WP_Admin_Bar $wp_admin_bar Barra de administración.
 */
function tanish_admin_bar_menu(WP_Admin_Bar $wp_admin_bar): void
{
	if (!is_admin()) {
		return;
	}

	$wp_admin_bar->remove_node('wp-logo');

	$wp_admin_bar->add_node(
		array(
			'id'    => 'tanish-brand',
			'title' => '<span class="ab-icon dashicons dashicons-store" aria-hidden="true"></span><span class="ab-label">TANISH</span>',
			'href'  => admin_url(),
			'meta'  => array(
				'class' => 'tanish-adminbar-brand',
			),
		)
	);

	$wp_admin_bar->add_node(
		array(
			'id'     => 'tanish-view-shop',
			'parent' => 'tanish-brand',
			'title'  => esc_html('Ver tienda'),
			'href'   => home_url('/'),
			'meta'   => array(
				'target' => '_blank',
				'rel'    => 'noopener noreferrer',
			),
		)
	);

	if (current_user_can('manage_tanish_inventory')) {
		$wp_admin_bar->add_node(
			array(
				'id'     => 'tanish-inventory',
				'parent' => 'tanish-brand',
				'title'  => esc_html('Inventario'),
				'href'   => admin_url('admin.php?page=tanish-inventory'),
			)
		);
	}

	if (current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders')) {
		$wp_admin_bar->add_node(
			array(
				'id'     => 'tanish-orders',
				'parent' => 'tanish-brand',
				'title'  => esc_html('Pedidos'),
				'href'   => admin_url('admin.php?page=wc-orders'),
			)
		);
	}

	if (current_user_can('edit_products')) {
		$wp_admin_bar->add_node(
			array(
				'id'     => 'tanish-products',
				'parent' => 'tanish-brand',
				'title'  => esc_html('Productos'),
				'href'   => admin_url('edit.php?post_type=product'),
			)
		);
	}

	if (current_user_can('manage_woocommerce')) {
		$wp_admin_bar->add_node(
			array(
				'id'     => 'tanish-whatsapp',
				'parent' => 'tanish-brand',
				'title'  => esc_html('WhatsApp'),
				'href'   => admin_url('admin.php?page=tanish-whatsapp'),
			)
		);
	}

	$wp_admin_bar->add_node(
		array(
			'id'     => 'tanish-logout',
			'parent' => 'tanish-brand',
			'title'  => esc_html('Salir'),
			'href'   => wp_logout_url(),
		)
	);

	if (!current_user_can('manage_tanish_inventory')) {
		return;
	}

	$inventory = tanish_admin_get_inventory_stats();

	$alerts = array(
		'tanish-stock-low' => array(
			'count' => $inventory['low'],
			'label' => 'Stock bajo',
			'class' => 'tanish-adminbar-alert tanish-adminbar-alert--low',
		),
		'tanish-stock-out' => array(
			'count' => $inventory['out'],
			'label' => 'Agotados',
			'class' => 'tanish-adminbar-alert tanish-adminbar-alert--out',
		),
	);

	foreach ($alerts as $node_id => $alert) {
		$wp_admin_bar->add_node(
			array(
				'id'    => $node_id,
				'title' => esc_html(sprintf('%s: %s', $alert['label'], number_format_i18n($alert['count']))),
				'href'  => admin_url('admin.php?page=tanish-inventory'),
				'meta'  => array(
					'class' => $alert['class'],
				),
			)
		);
	}
}
add_action('admin_bar_menu', 'tanish_admin_bar_menu', 31);

/* -------------------------------------------------------------------------
 * 5. Menú lateral
 * ---------------------------------------------------------------------- */

/**
 * Reordena el menú de primer nivel y oculta secciones que no aplican a la tienda.
 */
function tanish_admin_reorder_menu(): void
{
	global $menu;

	// La web es una tienda: Entradas y Comentarios no se usan.
	remove_menu_page('edit.php');
	remove_menu_page('edit-comments.php');

	if (!current_user_can('manage_options')) {
		$restricted = array(
			'themes.php',
			'plugins.php',
			'users.php',
			'tools.php',
			'options-general.php',
			'update-core.php',
		);

		foreach ($restricted as $slug) {
			remove_menu_page($slug);
		}
	}

	if (!is_array($menu)) {
		return;
	}

	$desired_order = array(
		'index.php',
		'woocommerce',
		'edit.php?post_type=product',
		'tanish-inventory',
		'edit.php?post_type=page',
		'themes.php',
		'plugins.php',
		'users.php',
		'tools.php',
		'options-general.php',
	);

	$ordered = array();
	$used    = array();

	foreach ($desired_order as $slug) {
		foreach ($menu as $key => $item) {
			if (!is_array($item) || !isset($item[2]) || isset($used[$key])) {
				continue;
			}

			if ($slug === $item[2]) {
				$ordered[$key] = $item;
				$used[$key]    = true;
				break;
			}
		}
	}

	// Los elementos no listados (plugins de terceros, separadores…) van al final.
	foreach ($menu as $key => $item) {
		if (!isset($used[$key])) {
			$ordered[$key] = $item;
		}
	}

	// WordPress reordena el menú por clave al imprimirlo: reindexamos para que
	// se respete el orden deseado.
	$menu = array_values($ordered);
}
add_action('admin_menu', 'tanish_admin_reorder_menu', 999);

/* -------------------------------------------------------------------------
 * 6. Migaja de pan
 * ---------------------------------------------------------------------- */

/**
 * Devuelve las partes de la migaja para la pantalla actual.
 *
 * @return array{section:string,current:string}
 */
function tanish_admin_breadcrumb_parts(): array
{
	$screen = get_current_screen();

	if (!$screen instanceof WP_Screen) {
		return array(
			'section' => '',
			'current' => '',
		);
	}

	$map = array(
		'toplevel_page_tanish-inventory'                   => array('Inventario', 'Dashboard'),
		'tanish-inventory_page_tanish-inventory-entry'     => array('Inventario', 'Nueva entrada'),
		'tanish-inventory_page_tanish-inventory-exit'      => array('Inventario', 'Nueva salida'),
		'tanish-inventory_page_tanish-inventory-adjustment' => array('Inventario', 'Ajuste de stock'),
		'tanish-inventory_page_tanish-inventory-movements' => array('Inventario', 'Movimientos'),
		'tanish-inventory_page_tanish-inventory-kardex'    => array('Inventario', 'Kardex'),
		'edit-product'                                     => array('Catálogo', 'Productos'),
		'edit-page'                                        => array('Contenido', 'Páginas'),
		'woocommerce_page_wc-orders'                       => array('Ventas', 'Pedidos'),
		'edit-shop_order'                                  => array('Ventas', 'Pedidos'),
		'woocommerce_page_wc-settings'                     => array('WooCommerce', 'Ajustes'),
		'options-general'                                  => array('Ajustes', 'Generales'),
		'edit-comments'                                    => array('Comentarios', 'Comentarios'),
	);

	if (isset($map[$screen->id])) {
		return array(
			'section' => $map[$screen->id][0],
			'current' => $map[$screen->id][1],
		);
	}

	if ('product' === $screen->id) {
		$is_new = 'add' === $screen->action;

		return array(
			'section' => 'Catálogo',
			'current' => $is_new ? 'Nuevo producto' : 'Editar producto',
		);
	}

	// Fallback: título de la página/menú si WordPress lo conoce.
	$title = isset($GLOBALS['title']) ? trim(wp_strip_all_tags((string) $GLOBALS['title'])) : '';

	if ('' !== $title) {
		return array(
			'section' => '',
			'current' => $title,
		);
	}

	return array(
		'section' => '',
		'current' => '',
	);
}

/**
 * Pinta la migaja dentro de #wpbody-content, antes de los avisos.
 */
function tanish_admin_render_breadcrumb(): void
{
	if (!is_admin() || is_network_admin()) {
		return;
	}

	$screen = get_current_screen();

	if (!$screen instanceof WP_Screen || 'dashboard' === $screen->id) {
		return;
	}

	$parts = tanish_admin_breadcrumb_parts();

	echo '<nav class="tanish-breadcrumb" aria-label="' . esc_attr('Ruta de navegación') . '">';
	echo '<ol class="tanish-breadcrumb-list">';
	echo '<li class="tanish-breadcrumb-item tanish-breadcrumb-home"><a href="' . esc_url(admin_url()) . '">' . esc_html('TANISH') . '</a></li>';

	if ('' !== $parts['section']) {
		echo '<li class="tanish-breadcrumb-item tanish-breadcrumb-section"><span>' . esc_html($parts['section']) . '</span></li>';
	}

	if ('' !== $parts['current']) {
		echo '<li class="tanish-breadcrumb-item tanish-breadcrumb-current" aria-current="page">' . esc_html($parts['current']) . '</li>';
	}

	echo '</ol>';
	echo '</nav>';
}

/**
 * in_admin_header se ejecuta antes de #wpbody-content. Diferimos el render a
 * admin_notices (prioridad muy baja) para que la migaja quede dentro del
 * contenido y por encima de los avisos.
 */
add_action('in_admin_header', function (): void {
	if (is_network_admin()) {
		return;
	}

	// -10000 queda antes del contenedor que WooCommerce usa para reubicar
	// avisos, de modo que la migaja nunca acaba dentro de un bloque oculto.
	add_action('admin_notices', 'tanish_admin_render_breadcrumb', -10000);
}, 100);

/**
 * Bloque de bienvenida de marca en el dashboard.
 */
add_action('in_admin_header', function (): void {
	if (is_network_admin() || !is_admin()) {
		return;
	}

	$screen = get_current_screen();

	if (!$screen instanceof WP_Screen || 'dashboard' !== $screen->id) {
		return;
	}

	$user = wp_get_current_user();
	$name = '' !== $user->display_name ? $user->display_name : $user->user_login;

	echo '<div class="tanish-welcome">';
	echo '<span class="tanish-welcome-mark" aria-hidden="true">T</span>';
	echo '<p class="tanish-welcome-text">' . esc_html(sprintf('Hola, %s · TANISH', $name)) . '</p>';
	echo '<span class="tanish-welcome-date">' . esc_html(date_i18n('j \d\e F \d\e Y')) . '</span>';
	echo '</div>';
}, 20);

/* -------------------------------------------------------------------------
 * 7. Dashboard a medida
 * ---------------------------------------------------------------------- */

/**
 * Pinta una tarjeta del resumen.
 *
 * @param string $icon     Clase de Dashicon.
 * @param string $label    Etiqueta visible.
 * @param string $value    Valor ya escapado.
 * @param string $modifier Modificador de color.
 * @param string $href     Enlace opcional.
 */
function tanish_admin_render_stat_card(string $icon, string $label, string $value, string $modifier = '', string $href = ''): void
{
	$classes = 'tanish-dash-card';

	if ('' !== $modifier) {
		$classes .= ' tanish-dash-card--' . sanitize_html_class($modifier);
	}

	if ('' !== $href) {
		echo '<a class="' . esc_attr($classes) . '" href="' . esc_url($href) . '">';
	} else {
		echo '<div class="' . esc_attr($classes) . '">';
	}

	echo '<span class="tanish-dash-icon dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span>';
	echo '<span class="tanish-dash-label">' . esc_html($label) . '</span>';
	echo '<strong class="tanish-dash-value">' . wp_kses_post($value) . '</strong>';

	if ('' !== $href) {
		echo '</a>';
	} else {
		echo '</div>';
	}
}

/**
 * Contenido del widget "Resumen TANISH".
 */
function tanish_admin_render_dashboard_widget(): void
{
	$user = wp_get_current_user();
	$name = '' !== $user->display_name ? $user->display_name : $user->user_login;

	$can_inventory = current_user_can('manage_tanish_inventory');
	$can_products  = current_user_can('edit_products');
	$can_orders    = current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders');

	echo '<div class="tanish-dash">';
	echo '<p class="tanish-dash-intro">' . esc_html(sprintf('Hola, %s. Este es el centro de control de TANISH.', $name)) . '</p>';

	if (!$can_inventory && !$can_orders) {
		echo '<p class="tanish-dash-empty">' . esc_html('Bienvenido al panel. Usa los accesos disponibles según tu rol.') . '</p>';
		echo '<div class="tanish-dash-actions">';

		if ($can_products) {
			echo '<a class="button button-primary tanish-dash-action" href="' . esc_url(admin_url('edit.php?post_type=product')) . '">' . esc_html('Ver productos') . '</a>';
		}

		echo '<a class="button tanish-dash-action" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html('Ver tienda') . '</a>';
		echo '</div>';
		echo '</div>';

		return;
	}

	$inventory = $can_inventory ? tanish_admin_get_inventory_stats() : null;
	$sales     = $can_orders ? tanish_admin_get_sales_stats() : null;

	echo '<div class="tanish-dash-grid">';

	if ($can_products) {
		tanish_admin_render_stat_card(
			'dashicons-products',
			'Productos publicados',
			esc_html(number_format_i18n($inventory['published'] ?? tanish_admin_published_products())),
			'neutral',
			admin_url('edit.php?post_type=product')
		);
	}

	if (is_array($inventory)) {
		tanish_admin_render_stat_card(
			'dashicons-warning',
			'Stock bajo (1-5)',
			esc_html(number_format_i18n($inventory['low'])),
			'low',
			admin_url('admin.php?page=tanish-inventory')
		);

		tanish_admin_render_stat_card(
			'dashicons-dismiss',
			'Agotados',
			esc_html(number_format_i18n($inventory['out'])),
			'out',
			admin_url('admin.php?page=tanish-inventory')
		);
	}

	if (is_array($sales)) {
		tanish_admin_render_stat_card(
			'dashicons-clipboard',
			'Pedidos pendientes y en proceso',
			esc_html(number_format_i18n($sales['open_orders'])),
			'info',
			admin_url('admin.php?page=wc-orders')
		);

		$sales_value = function_exists('wc_price')
			? wc_price($sales['month_sales'])
			: esc_html(get_woocommerce_currency_symbol() . number_format_i18n($sales['month_sales'], 2));

		tanish_admin_render_stat_card(
			'dashicons-chart-area',
			'Ventas del mes',
			$sales_value,
			'accent',
			admin_url('admin.php?page=wc-orders')
		);
	}

	echo '</div>';

	echo '<div class="tanish-dash-actions">';

	if ($can_inventory) {
		echo '<a class="button button-primary tanish-dash-action" href="' . esc_url(admin_url('admin.php?page=tanish-inventory-entry')) . '">' . esc_html('Nueva entrada') . '</a>';
		echo '<a class="button tanish-dash-action" href="' . esc_url(admin_url('admin.php?page=tanish-inventory-exit')) . '">' . esc_html('Nueva salida') . '</a>';
		echo '<a class="button tanish-dash-action" href="' . esc_url(admin_url('admin.php?page=tanish-inventory-adjustment')) . '">' . esc_html('Ajuste de stock') . '</a>';
		echo '<a class="button tanish-dash-action" href="' . esc_url(admin_url('admin.php?page=tanish-inventory-kardex')) . '">' . esc_html('Ver Kardex') . '</a>';
	}

	if ($can_orders) {
		echo '<a class="button tanish-dash-action" href="' . esc_url(admin_url('admin.php?page=wc-orders')) . '">' . esc_html('Ver pedidos') . '</a>';
	}

	if ($can_products) {
		echo '<a class="button tanish-dash-action" href="' . esc_url(admin_url('post-new.php?post_type=product')) . '">' . esc_html('Añadir producto') . '</a>';
	}

	echo '</div>';

	if ($can_orders) {
		echo '<div class="tanish-dash-section">';
		tanish_admin_render_sales_chart(tanish_admin_get_sales_series(14));
		echo '</div>';
	}

	echo '<div class="tanish-dash-panels">';

	if ($can_orders) {
		$recent_orders = tanish_admin_get_recent_orders(5);

		echo '<div class="tanish-panel">';
		echo '<div class="tanish-panel-head"><h3>' . esc_html('Últimos pedidos') . '</h3>';
		echo '<a class="tanish-panel-link" href="' . esc_url(admin_url('admin.php?page=wc-orders')) . '">' . esc_html('Ver todos') . '</a></div>';

		if (empty($recent_orders)) {
			echo '<p class="tanish-panel-empty">' . esc_html('Todavía no hay pedidos.') . '</p>';
		} else {
			echo '<table class="tanish-table"><thead><tr>';
			echo '<th>' . esc_html('Pedido') . '</th>';
			echo '<th>' . esc_html('Cliente') . '</th>';
			echo '<th>' . esc_html('Estado') . '</th>';
			echo '<th class="tanish-table-num">' . esc_html('Total') . '</th>';
			echo '</tr></thead><tbody>';

			foreach ($recent_orders as $order) {
				$total_html = function_exists('wc_price')
					? wc_price($order['total'])
					: esc_html(number_format_i18n($order['total'], 2));

				echo '<tr>';
				echo '<td><a href="' . esc_url(admin_url('admin.php?page=wc-orders&action=edit&id=' . $order['id'])) . '">#' . esc_html($order['number']) . '</a>';
				echo '<span class="tanish-table-meta">' . esc_html($order['date']) . '</span></td>';
				echo '<td>' . esc_html('' !== $order['name'] ? $order['name'] : '—') . '</td>';
				echo '<td><span class="tanish-status tanish-status--' . esc_attr(tanish_admin_order_status_class($order['status'])) . '">' . esc_html($order['label']) . '</span></td>';
				echo '<td class="tanish-table-num">' . wp_kses_post($total_html) . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		echo '</div>';
	}

	if ($can_inventory) {
		$low_stock = tanish_admin_get_low_stock_products(5);

		echo '<div class="tanish-panel">';
		echo '<div class="tanish-panel-head"><h3>' . esc_html('Stock bajo') . '</h3>';
		echo '<a class="tanish-panel-link" href="' . esc_url(admin_url('admin.php?page=tanish-inventory')) . '">' . esc_html('Ver inventario') . '</a></div>';

		if (empty($low_stock)) {
			echo '<p class="tanish-panel-empty">' . esc_html('Todo el stock está en orden.') . '</p>';
		} else {
			echo '<ul class="tanish-stock-list">';

			foreach ($low_stock as $product) {
				echo '<li>';
				echo '<a href="' . esc_url($product['link']) . '">' . esc_html($product['name']) . '</a>';
				echo '<span class="tanish-stock-badge">' . esc_html(sprintf('%d uds.', $product['stock'])) . '</span>';
				echo '</li>';
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	echo '</div>';

	if ($can_orders) {
		$top_products = tanish_admin_get_top_products(5);

		if (!empty($top_products)) {
			echo '<div class="tanish-panel tanish-panel--wide">';
			echo '<div class="tanish-panel-head"><h3>' . esc_html('Más vendidos (últimos 30 días)') . '</h3></div>';
			echo '<table class="tanish-table"><thead><tr>';
			echo '<th>' . esc_html('Producto') . '</th>';
			echo '<th class="tanish-table-num">' . esc_html('Unidades') . '</th>';
			echo '<th class="tanish-table-num">' . esc_html('Ingresos') . '</th>';
			echo '</tr></thead><tbody>';

			foreach ($top_products as $product) {
				$revenue_html = function_exists('wc_price')
					? wc_price($product['revenue'])
					: esc_html(number_format_i18n($product['revenue'], 2));

				echo '<tr>';
				echo '<td><a href="' . esc_url($product['link']) . '">' . esc_html($product['name']) . '</a></td>';
				echo '<td class="tanish-table-num">' . esc_html(number_format_i18n($product['qty'])) . '</td>';
				echo '<td class="tanish-table-num">' . wp_kses_post($revenue_html) . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table></div>';
		}
	}

	$categories = tanish_admin_get_category_stats(6);

	if (!empty($categories)) {
		$max_cat = max(array_column($categories, 'count'));
		$max_cat = $max_cat > 0 ? $max_cat : 1;

		echo '<div class="tanish-panel tanish-panel--wide">';
		echo '<div class="tanish-panel-head"><h3>' . esc_html('Catálogo por categoría') . '</h3>';
		echo '<a class="tanish-panel-link" href="' . esc_url(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')) . '">' . esc_html('Ver categorías') . '</a></div>';
		echo '<div class="tanish-cat-bars">';

		foreach ($categories as $category) {
			$pct = max(4, (int) round(($category['count'] / $max_cat) * 100));

			echo '<div class="tanish-cat-row">';

			if ('' !== $category['link']) {
				echo '<a class="tanish-cat-name" href="' . esc_url($category['link']) . '">' . esc_html($category['name']) . '</a>';
			} else {
				echo '<span class="tanish-cat-name">' . esc_html($category['name']) . '</span>';
			}

			echo '<span class="tanish-cat-track"><span class="tanish-cat-fill" style="width:' . esc_attr((string) $pct) . '%"></span></span>';
			echo '<span class="tanish-cat-count">' . esc_html(number_format_i18n($category['count'])) . '</span>';
			echo '</div>';
		}

		echo '</div></div>';
	}

	echo '</div>';
}

/**
 * Cabecera, KPIs y acciones de la lista de productos.
 */
function tanish_admin_products_header(): void
{
	$screen = get_current_screen();

	if (!$screen || 'edit-product' !== $screen->id || !current_user_can('edit_products')) {
		return;
	}

	$inventory = tanish_admin_get_inventory_stats();
	$counts    = wp_count_posts('product');
	$drafts    = isset($counts->draft) ? (int) $counts->draft : 0;
	$pending   = isset($counts->pending) ? (int) $counts->pending : 0;

	$cards = array(
		array(
			'label'  => 'Publicados',
			'value'  => (int) $inventory['published'],
			'class'  => 'neutral',
			'url'    => admin_url('edit.php?post_type=product&post_status=publish'),
		),
		array(
			'label'  => 'Stock bajo (1-5)',
			'value'  => (int) $inventory['low'],
			'class'  => 'low',
			'url'    => admin_url('admin.php?page=tanish-inventory'),
		),
		array(
			'label'  => 'Agotados',
			'value'  => (int) $inventory['out'],
			'class'  => 'out',
			'url'    => admin_url('edit.php?post_type=product&stock_status=outofstock'),
		),
		array(
			'label'  => 'Borradores',
			'value'  => $drafts + $pending,
			'class'  => 'muted',
			'url'    => admin_url('edit.php?post_type=product&post_status=draft'),
		),
	);

	echo '<div class="tanish-prod-head">';
	echo '<div class="tanish-prod-head-main">';
	echo '<h1 class="tanish-prod-title">' . esc_html('Catálogo de productos') . '</h1>';
	echo '<p class="tanish-prod-sub">' . esc_html('Gestiona precios, stock y categorías del catálogo de TANISH.') . '</p>';
	echo '</div>';
	echo '<div class="tanish-prod-actions">';
	echo '<a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=product')) . '">' . esc_html('Añadir producto') . '</a>';
	echo '<a class="button" href="' . esc_url(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')) . '">' . esc_html('Categorías') . '</a>';
	echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=product&page=product_importer')) . '">' . esc_html('Importar') . '</a>';
	echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=product&page=product_exporter')) . '">' . esc_html('Exportar') . '</a>';
	echo '</div>';
	echo '</div>';

	echo '<div class="tanish-prod-kpis">';

	foreach ($cards as $card) {
		echo '<a class="tanish-prod-kpi tanish-prod-kpi--' . esc_attr($card['class']) . '" href="' . esc_url($card['url']) . '">';
		echo '<span class="tanish-prod-kpi-label">' . esc_html($card['label']) . '</span>';
		echo '<strong class="tanish-prod-kpi-value">' . esc_html(number_format_i18n($card['value'])) . '</strong>';
		echo '</a>';
	}

	echo '</div>';
}
add_action('admin_notices', 'tanish_admin_products_header');

/**
 * Configura el dashboard: quita widgets por defecto y añade el resumen TANISH.
 */
function tanish_admin_dashboard_setup(): void
{
	remove_action('welcome_panel', 'wp_welcome_panel');

	$removed_widgets = array(
		'dashboard_activity',
		'dashboard_right_now',
		'dashboard_quick_press',
		'dashboard_primary',
		'dashboard_secondary',
		'dashboard_site_health',
		'network_dashboard_right_now',
	);

	$contexts   = array('normal', 'side', 'column3', 'column4');
	$priorities = array('high', 'core', 'default', 'low');

	foreach ($removed_widgets as $widget_id) {
		foreach ($contexts as $context) {
			remove_meta_box($widget_id, 'dashboard', $context);
		}
	}

	global $wp_meta_boxes;

	if (isset($wp_meta_boxes['dashboard'])) {
		foreach ($contexts as $context) {
			foreach ($priorities as $priority) {
				if (empty($wp_meta_boxes['dashboard'][$context][$priority])) {
					continue;
				}

				foreach ($removed_widgets as $widget_id) {
					unset($wp_meta_boxes['dashboard'][$context][$priority][$widget_id]);
				}
			}
		}
	}

	wp_add_dashboard_widget('tanish_resumen', 'Resumen TANISH', 'tanish_admin_render_dashboard_widget');

	// Fuerza el widget en la columna principal y en primera posición.
	if (isset($wp_meta_boxes['dashboard']['normal']['core']['tanish_resumen'])) {
		$widget = $wp_meta_boxes['dashboard']['normal']['core']['tanish_resumen'];
		unset($wp_meta_boxes['dashboard']['normal']['core']['tanish_resumen']);
		$wp_meta_boxes['dashboard']['normal']['core'] = array('tanish_resumen' => $widget) + $wp_meta_boxes['dashboard']['normal']['core'];
	}
}
add_action('wp_dashboard_setup', 'tanish_admin_dashboard_setup');
