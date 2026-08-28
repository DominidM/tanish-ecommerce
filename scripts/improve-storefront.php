<?php
/**
 * TANISH Storefront Improvement - Visual professional upgrade
 * Idempotent - safe to run multiple times
 * - Disables Coming Soon (woocommerce_coming_soon)
 * - Creates Nosotros / Contacto pages
 * - Improves Inicio with professional hero + sections
 * - Cleans navigation (block theme wp_navigation)
 * - Uses official WP/Woo APIs, no SQL direct
 */

$wp_load_candidates = array(
	'/var/www/html/wp-load.php',
	dirname(__DIR__, 3) . '/wp-load.php',
	__DIR__ . '/../../wp-load.php',
);
$loaded = false;
foreach ($wp_load_candidates as $c) { if (file_exists($c)) { require_once $c; $loaded = true; break; } }
if (!$loaded && file_exists('/var/www/html/wp-load.php')) { require_once '/var/www/html/wp-load.php'; $loaded = true; }
if (!$loaded) { fwrite(STDERR, "wp-load.php not found\n"); exit(1); }

echo "TANISH Storefront Improve\n";
echo "-------------------------\n";

// 2. Coming Soon OFF (confirm)
$before = get_option('woocommerce_coming_soon');
update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');
echo "Coming soon: ".var_export($before, true)." -> ".var_export(get_option('woocommerce_coming_soon'), true)."\n";

// Helper: find or create page
function tanish_get_or_create_page($slug, $title, $content, $status='publish') {
	$page = get_page_by_path($slug, OBJECT, 'page');
	if ($page) {
		// Update if content/title differs? Keep idempotent: update to ensure content is as expected if it's Nosotros/Contacto
		// For Inicio we handle separately
		return $page;
	}
	// Also check via get_posts for draft/trash edge
	$posts = get_posts(array('name'=>$slug,'post_type'=>'page','post_status'=>array('publish','draft','pending','future','private'),'numberposts'=>1));
	if ($posts) return $posts[0];

	$id = wp_insert_post(array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => $status,
		'post_type'    => 'page',
		'comment_status'=>'closed',
		'ping_status'=>'closed',
	), true);
	if (is_wp_error($id)) {
		echo "Error creating $title: ".$id->get_error_message()."\n";
		return null;
	}
	echo "Created page $title (ID $id, slug $slug)\n";
	return get_post($id);
}

// 7. Páginas básicas Nosotros / Contacto
$nosotros_content = <<<HTML
<!-- wp:heading {"level":1} -->
<h1>Nosotros</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>TANISH S.A.C.</strong> es una empresa comprometida con brindar productos de calidad a precios accesibles. Nuestro catálogo está disponible online y la atención es directa por WhatsApp para coordinar cada pedido con transparencia y rapidez.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Trabajamos con stock actualizado y marcas confiables. Cada producto que ves en la tienda está disponible para coordinar su entrega inmediata.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Nuestro compromiso</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Productos seleccionados</li><li>Stock transparente</li><li>Atención personalizada</li><li>Entrega coordinada</li></ul>
<!-- /wp:list -->
HTML;

$contacto_content = <<<HTML
<!-- wp:heading {"level":1} -->
<h1>Contacto</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Coordina tu pedido directamente por WhatsApp. Escríbenos indicando el producto que te interesa y te confirmamos disponibilidad, precio y entrega.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Horario:</strong> Lunes a Sábado, 8:00 - 20:00<br><strong>Canal:</strong> WhatsApp (respuesta rápida)</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>También puedes navegar la <a href="/shop">Tienda</a> y usar el botón <strong>Comprar por WhatsApp</strong> en cada producto para enviar un mensaje precargado con los datos del producto.</p>
<!-- /wp:paragraph -->
HTML;

$nosotros = tanish_get_or_create_page('nosotros', 'Nosotros', $nosotros_content);
$contacto = tanish_get_or_create_page('contacto', 'Contacto', $contacto_content);

if ($nosotros && get_post_status($nosotros->ID) !== 'publish') {
	wp_update_post(array('ID'=>$nosotros->ID,'post_status'=>'publish'));
	echo "Nosotros: published\n";
} else if ($nosotros) {
	echo "Nosotros: exists (ID {$nosotros->ID}, ".get_post_status($nosotros->ID).")\n";
}
if ($contacto && get_post_status($contacto->ID) !== 'publish') {
	wp_update_post(array('ID'=>$contacto->ID,'post_status'=>'publish'));
	echo "Contacto: published\n";
} else if ($contacto) {
	echo "Contacto: exists (ID {$contacto->ID}, ".get_post_status($contacto->ID).")\n";
}

// 4. Portada profesional - Mejorar Inicio
$inicio = get_page_by_path('inicio', OBJECT, 'page');
if (!$inicio) {
	// Try fallback
	$posts = get_posts(array('name'=>'inicio','post_type'=>'page','post_status'=>array('publish','draft'),'numberposts'=>1));
	$inicio = $posts ? $posts[0] : null;
}

$inicio_id = $inicio ? (int)$inicio->ID : 0;
$shop_id = wc_get_page_id('shop');
$shop_url = get_permalink($shop_id) ?: '/shop';

$hero_content = <<<HTML
<!-- wp:group {"className":"tanish-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group tanish-hero"><!-- wp:heading {"level":1,"textAlign":"center"} -->
<h1 class="has-text-align-center">TANISH</h1>
<!-- /wp:heading -->

<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="has-text-align-center">Compra fácil y rápida</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Encuentra productos disponibles y coordina tu pedido directamente por WhatsApp. Stock actualizado y atención directa.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{$shop_url}">Ver productos</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/contacto">Comprar por WhatsApp</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tanish-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group tanish-section"><!-- wp:heading {"level":2} -->
<h2 id="categorias">Categorías destacadas</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Explora por categoría y encuentra lo que necesitas.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[product_categories number="6" columns="3" parent="0"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tanish-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group tanish-section"><!-- wp:heading {"level":2} -->
<h2>Productos destacados</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Selección reciente — stock verificado.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[products limit="8" columns="4" orderby="date" order="DESC"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tanish-section tanish-benefits","layout":{"type":"constrained"}} -->
<div class="wp-block-group tanish-section tanish-benefits"><!-- wp:heading {"level":2} -->
<h2>¿Por qué comprar en TANISH?</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Stock actualizado</li><li>Atención directa por WhatsApp</li><li>Compra rápida</li><li>Catálogo online disponible</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tanish-contact","layout":{"type":"constrained"}} -->
<div class="wp-block-group tanish-contact"><!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="has-text-align-center">¿Necesitas ayuda?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Escríbenos por WhatsApp y coordina tu pedido en minutos. Atención directa, sin intermediarios.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contacto">Contactar por WhatsApp</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML;

if ($inicio_id) {
	$current = $inicio->post_content;
	// Only update if content is different (avoid unnecessary revisions)
	if (trim($current) !== trim($hero_content)) {
		wp_update_post(array('ID'=>$inicio_id, 'post_content'=>$hero_content));
		echo "Inicio: updated with professional layout (ID $inicio_id)\n";
	} else {
		echo "Inicio: already professional (ID $inicio_id)\n";
	}
	// Ensure front page still configured
	update_option('show_on_front', 'page');
	update_option('page_on_front', $inicio_id);
	update_option('page_for_posts', 0);
	echo "Front page: ensured page_on_front=$inicio_id\n";
} else {
	echo "Inicio: NOT found — cannot update\n";
}

// 3. Menú principal - Navigation block for Twenty Twenty-Five (wp_navigation)
echo "Menu: updating navigation...\n";
$nav_posts = get_posts(array('post_type'=>'wp_navigation','post_status'=>'publish','numberposts'=>1));
$nav_id = $nav_posts ? $nav_posts[0]->ID : 0;
if (!$nav_id) {
	// Create if not exists
	$nav_id = wp_insert_post(array(
		'post_title'=>'Navegación',
		'post_name'=>'navigation',
		'post_type'=>'wp_navigation',
		'post_status'=>'publish',
		'post_content'=>'',
	));
	echo "Navigation: created ID $nav_id\n";
}

if ($nav_id) {
	$inicio_url = get_permalink($inicio_id) ?: '/';
	$shop_url = get_permalink($shop_id) ?: '/shop';
	$nosotros_url = $nosotros ? get_permalink($nosotros->ID) : '/nosotros';
	$contacto_url = $contacto ? get_permalink($contacto->ID) : '/contacto';
	// Categories anchor on Inicio
	$categorias_url = $inicio_url . '#categorias';

	// Build navigation block content - explicit links only (no page-list)
	$nav_content = '<!-- wp:navigation-link {"label":"Inicio","type":"page","id":'.$inicio_id.',"url":"'.esc_url($inicio_url).'","kind":"post-type"} /-->'."\n";
	$nav_content .= '<!-- wp:navigation-link {"label":"Tienda","type":"page","id":'.$shop_id.',"url":"'.esc_url($shop_url).'","kind":"post-type"} /-->'."\n";
	$nav_content .= '<!-- wp:navigation-link {"label":"Categorías","type":"custom","url":"'.esc_url($categorias_url).'","kind":"custom"} /-->'."\n";
	$nav_content .= '<!-- wp:navigation-link {"label":"Nosotros","type":"page","id":'.($nosotros?$nosotros->ID:0).',"url":"'.esc_url($nosotros_url).'","kind":"post-type"} /-->'."\n";
	$nav_content .= '<!-- wp:navigation-link {"label":"Contacto","type":"page","id":'.($contacto?$contacto->ID:0).',"url":"'.esc_url($contacto_url).'","kind":"post-type"} /-->'."\n";

	wp_update_post(array('ID'=>$nav_id, 'post_content'=>$nav_content));
	echo "Menu: updated (ID $nav_id) -> Inicio, Tienda, Categorías, Nosotros, Contacto (Cart/Checkout/MyAccount removed)\n";
}

// Ensure pages are publish
$shop = get_post($shop_id);
if ($shop && get_post_status($shop_id) !== 'publish') { wp_update_post(array('ID'=>$shop_id,'post_status'=>'publish')); }

echo "Products found: ".count(wc_get_products(array('limit'=>-1,'status'=>'publish')))."\n";
echo "Storefront improve completed\n";
