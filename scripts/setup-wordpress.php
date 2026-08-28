<?php
/**
 * TANISH WordPress Setup - Idempotent storefront configuration.
 *
 * Usage (inside container):
 *   php /tmp/setup-wordpress.php
 *
 * Or via host:
 *   docker cp scripts/setup-wordpress.php tanish-wordpress:/tmp/setup-wordpress.php
 *   docker exec tanish-wordpress php /tmp/setup-wordpress.php
 *
 * No direct SQL, only official WordPress APIs.
 */

// Load WordPress context idempotently.
$wp_load_candidates = array(
	'/var/www/html/wp-load.php',
	dirname(__DIR__, 3) . '/wp-load.php',
	dirname(__DIR__, 2) . '/wp-load.php',
	__DIR__ . '/../../wp-load.php',
);

$wp_loaded = false;
foreach ($wp_load_candidates as $candidate) {
	if (file_exists($candidate)) {
		require_once $candidate;
		$wp_loaded = true;
		break;
	}
}

if (!$wp_loaded) {
	// Fallback: try relative to script location inside container /tmp
	$fallback = '/var/www/html/wp-load.php';
	if (file_exists($fallback)) {
		require_once $fallback;
		$wp_loaded = true;
	}
}

if (!$wp_loaded) {
	fwrite(STDERR, "Error: wp-load.php not found. Run inside WordPress context.\n");
	exit(1);
}

echo "TANISH WordPress Setup\n";
echo "----------------------\n";

// Helper: find page by slug idempotently (including trashed? we only care publish/draft for Inicio)
function tanish_find_page_by_slug($slug) {
	$page = get_page_by_path($slug, OBJECT, 'page');
	if ($page) {
		return $page;
	}
	// Fallback search via get_posts for any status (to avoid duplicates with draft/trash edge)
	$posts = get_posts(array(
		'name'           => $slug,
		'post_type'      => 'page',
		'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
		'numberposts'    => 1,
		'suppress_filters' => false,
	));
	return $posts ? $posts[0] : null;
}

// 3. Crear / Configurar página Inicio
$inicio_slug = 'inicio';
$inicio_title = 'Inicio';
$existing_inicio = tanish_find_page_by_slug($inicio_slug);

// Commercial Gutenberg-compatible content (HTML + WooCommerce shortcodes)
$inicio_content = <<<HTML
<!-- wp:heading {"level":1,"textAlign":"center"} -->
<h1 class="has-text-align-center">TANISH</h1>
<!-- /wp:heading -->

<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="has-text-align-center">Compra fácil y rápida</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Encuentra productos disponibles y coordina tu pedido directamente por WhatsApp.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button" href="/shop">Ver productos</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>Categorías</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[product_categories number="6" columns="3" parent="0"]
<!-- /wp:shortcode -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>Productos disponibles</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[products limit="8" columns="4" orderby="date" order="DESC"]
<!-- /wp:shortcode -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>¿Por qué comprar en TANISH?</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Stock actualizado</li><li>Atención directa por WhatsApp</li><li>Compra rápida</li><li>Catálogo disponible online</li></ul>
<!-- /wp:list -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>¿Necesitas ayuda?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Contáctanos directamente por WhatsApp.</p>
<!-- /wp:paragraph -->
HTML;

$inicio_status = 'publish';
$inicio_data = array(
	'post_title'   => $inicio_title,
	'post_name'    => $inicio_slug,
	'post_content' => $inicio_content,
	'post_status'  => $inicio_status,
	'post_type'    => 'page',
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
);

$inicio_id = null;
$inicio_action = '';

if ($existing_inicio) {
	$inicio_id = (int) $existing_inicio->ID;
	// Update existing page (idempotent) - preserve ID
	$inicio_data['ID'] = $inicio_id;
	$updated = wp_update_post($inicio_data, true);
	if (is_wp_error($updated)) {
		echo "Inicio: error updating - " . $updated->get_error_message() . "\n";
	} else {
		$inicio_action = 'updated';
		echo "Inicio: updated (ID {$inicio_id})\n";
	}
} else {
	$created = wp_insert_post($inicio_data, true);
	if (is_wp_error($created)) {
		echo "Inicio: error creating - " . $created->get_error_message() . "\n";
	} else {
		$inicio_id = (int) $created;
		$inicio_action = 'created';
		echo "Inicio: created (ID {$inicio_id})\n";
	}
}

// Ensure we have ID for front page config
if (!$inicio_id && $existing_inicio) {
	$inicio_id = (int) $existing_inicio->ID;
}

// 4. Configurar portada estática
if ($inicio_id) {
	update_option('show_on_front', 'page');
	update_option('page_on_front', $inicio_id);
	// No blog page for this project
	update_option('page_for_posts', 0);
	echo "Front page: configured (page_on_front={$inicio_id})\n";
} else {
	echo "Front page: skipped (Inicio not found)\n";
}

// 5. Limpiar contenido de ejemplo - idempotent, move to trash if exists and not already trashed
function tanish_trash_if_exists($slug_variants, $post_type, $label) {
	$found = null;
	foreach ((array) $slug_variants as $slug) {
		$p = get_page_by_path($slug, OBJECT, $post_type);
		if ($p) {
			$found = $p;
			break;
		}
		// Also search via get_posts for any status (covers draft/trash naming)
		$posts = get_posts(array(
			'name'           => $slug,
			'post_type'      => $post_type,
			'post_status'    => array('publish', 'draft', 'pending', 'future', 'private', 'trash'),
			'numberposts'    => 1,
		));
		if ($posts) {
			$found = $posts[0];
			break;
		}
	}
	if (!$found) {
		echo "{$label}: already absent\n";
		return 'absent';
	}

	$status = get_post_status($found->ID);
	if ($status === 'trash') {
		echo "{$label}: already trashed (ID {$found->ID})\n";
		return 'already trashed';
	}

	// Do not trash required WooCommerce pages - caller ensures we don't pass them
	$trashed = wp_trash_post($found->ID);
	if ($trashed) {
		echo "{$label}: trashed (ID {$found->ID})\n";
		return 'trashed';
	} else {
		echo "{$label}: already absent / error\n";
		return 'error';
	}
}

// Sample Page - Spanish and English variants
tanish_trash_if_exists(array('sample-page', 'pagina-ejemplo', 'página-de-ejemplo'), 'page', 'Sample page');

// Hello World - handle both languages
tanish_trash_if_exists(array('hello-world', 'hola-mundo'), 'post', 'Hello World');

// 6. Título y descripción
update_option('blogname', 'TANISH');
update_option('blogdescription', 'Compra fácil, rápida y directa.');
echo "Blog name: TANISH\n";
echo "Blog description: Compra fácil, rápida y directa.\n";

// Ensure siteurl/home untouched (just report)
$siteurl = get_option('siteurl');
$home = get_option('home');
// No modification per spec

// 9. WooCommerce products count
$product_count = 0;
if (function_exists('wc_get_products')) {
	$products = wc_get_products(array('limit' => -1, 'status' => 'publish'));
	$product_count = count($products);
} else {
	// Fallback via WP_Query
	$q = new WP_Query(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids'));
	$product_count = $q->found_posts;
}
echo "WooCommerce products found: {$product_count}\n";

echo "Setup completed successfully\n";
