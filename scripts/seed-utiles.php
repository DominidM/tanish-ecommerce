<?php
/**
 * TANISH - Productos de útiles escolares (seed idempotente).
 *
 * Crea o actualiza los 12 productos del catálogo usando solo las APIs
 * oficiales de WordPress y WooCommerce (sin SQL directo).
 *
 * - Busca cada producto por su SKU nuevo. Si no existe, busca el SKU viejo
 *   de abarrotes y lo actualiza en el mismo producto (conserva su ID, su
 *   stock y su historial del módulo tanish-inventory).
 * - Si tampoco existe, lo crea.
 * - Las fotos se leen de la carpeta seed-images/ junto a este script.
 * - Se puede ejecutar varias veces sin duplicar nada.
 *
 * Uso desde el host (Windows, en la raíz del proyecto):
 *   docker cp scripts/seed-utiles.php tanish-wordpress:/tmp/seed-utiles.php
 *   docker cp scripts/seed-images tanish-wordpress:/tmp/seed-images
 *   docker exec tanish-wordpress php /tmp/seed-utiles.php
 *   docker exec tanish-wordpress chown -R www-data:www-data /var/www/html/wp-content/uploads
 */

$wp_load = '/var/www/html/wp-load.php';
if (!file_exists($wp_load)) {
    fwrite(STDERR, "Error: no se encontró wp-load.php. Ejecutar dentro del contenedor tanish-wordpress.\n");
    exit(1);
}
require_once $wp_load;

if (!function_exists('wc_get_product_id_by_sku')) {
    fwrite(STDERR, "Error: WooCommerce no está activo.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Ejecutar como administrador para que los hooks del módulo de inventario
// registren un usuario válido.
$admins = get_users(array('role' => 'administrator', 'number' => 1));
if (!empty($admins)) {
    wp_set_current_user($admins[0]->ID);
}

$images_dir = __DIR__ . '/seed-images';

// price y stock solo se usan si el producto hay que CREARLO desde cero.
// Si ya existe (caso normal), se conservan los que tiene.
$products = array(
    array(
        'sku' => 'TAN-LAP-001', 'old_sku' => 'TAN-GAL-001',
        'name' => 'Lapicero Azul Punta Fina',
        'image' => '02-lapicero-azul', 'alt' => 'Lapicero azul punta fina 0.7 mm',
        'category' => 'Escritura',
        'short' => 'Lapicero azul punta fina 0.7 mm.',
        'long' => 'Lapicero de tinta azul, punta fina de 0.7 mm, escritura suave y uniforme, ideal para uso diario en clases u oficina.',
        'tags' => array('lapicero', 'azul', 'escritura'),
        'price' => '3.20', 'stock' => 35,
    ),
    array(
        'sku' => 'TAN-CUA-001', 'old_sku' => 'TAN-SNA-001',
        'name' => 'Cuaderno Universitario 100 hojas',
        'image' => '01-cuaderno-universitario', 'alt' => 'Cuaderno universitario de 100 hojas',
        'category' => 'Cuadernos',
        'short' => 'Cuaderno con 100 hojas cuadriculadas, ideal para uso universitario y de oficina.',
        'long' => 'Cuaderno universitario de 100 hojas, cuadriculado, ideal para uso escolar y de oficina.',
        'tags' => array('cuaderno', 'universitario', 'oficina'),
        'price' => '2.80', 'stock' => 26,
    ),
    array(
        'sku' => 'TAN-BOR-001', 'old_sku' => 'TAN-CHO-001',
        'name' => 'Borrador Blanco Grande',
        'image' => '03-borrador-blanco', 'alt' => 'Borrador blanco grande de goma',
        'category' => 'Escritura',
        'short' => 'Borrador blanco grande, no mancha.',
        'long' => 'Borrador blanco de goma suave, no mancha el papel, tamaño grande, ideal para corregir lápiz sin dejar residuos.',
        'tags' => array('borrador', 'blanco', 'escritura'),
        'price' => '4.00', 'stock' => 15,
    ),
    array(
        'sku' => 'TAN-LPZ-001', 'old_sku' => 'TAN-FID-001',
        'name' => 'Lápiz HB N°2',
        'image' => '04-lapiz-hb', 'alt' => 'Lápiz de grafito HB N°2',
        'category' => 'Escritura',
        'short' => 'Lápiz de grafito HB N°2.',
        'long' => 'Lápiz de grafito HB N°2, mina resistente, ideal para escritura y dibujo escolar.',
        'tags' => array('lapiz', 'grafito', 'escritura'),
        'price' => '3.80', 'stock' => 28,
    ),
    array(
        'sku' => 'TAN-TAJ-001', 'old_sku' => 'TAN-AGU-001',
        'name' => 'Tajador Metálico',
        'image' => '05-tajador-metalico', 'alt' => 'Tajador metálico de un hueco',
        'category' => 'Escritura',
        'short' => 'Tajador metálico de un hueco.',
        'long' => 'Tajador metálico de un hueco, cuchilla de acero para un afilado preciso y duradero.',
        'tags' => array('tajador', 'metalico', 'escritura'),
        'price' => '2.00', 'stock' => 48,
    ),
    array(
        'sku' => 'TAN-FOL-001', 'old_sku' => 'TAN-GAS-001',
        'name' => 'Folder Manila A4',
        'image' => '06-folder-manila', 'alt' => 'Folder manila tamaño A4',
        'category' => 'Archivadores',
        'short' => 'Folder manila tamaño A4.',
        'long' => 'Folder manila tamaño A4, resistente, ideal para archivar documentos y tareas escolares.',
        'tags' => array('folder', 'manila', 'archivador'),
        'price' => '3.50', 'stock' => 40,
    ),
    array(
        'sku' => 'TAN-RES-001', 'old_sku' => 'TAN-JUG-001',
        'name' => 'Resaltador Fluorescente',
        'image' => '07-resaltador', 'alt' => 'Resaltador fluorescente punta biselada',
        'category' => 'Escritura',
        'short' => 'Resaltador fluorescente punta biselada.',
        'long' => 'Resaltador de tinta fluorescente, punta biselada, secado rápido, ideal para subrayar apuntes.',
        'tags' => array('resaltador', 'fluorescente', 'escritura'),
        'price' => '6.50', 'stock' => 22,
    ),
    array(
        'sku' => 'TAN-COR-001', 'old_sku' => 'TAN-ACE-001',
        'name' => 'Corrector Líquido',
        'image' => '08-corrector', 'alt' => 'Corrector líquido de secado rápido',
        'category' => 'Escritura',
        'short' => 'Corrector líquido de secado rápido.',
        'long' => 'Corrector líquido de secado rápido, con aplicador de punta fina para mayor precisión.',
        'tags' => array('corrector', 'liquido', 'escritura'),
        'price' => '9.90', 'stock' => 18,
    ),
    array(
        'sku' => 'TAN-REG-001', 'old_sku' => 'TAN-LEC-001',
        'name' => 'Regla Plástica 30 cm',
        'image' => '09-regla-plastica', 'alt' => 'Regla plástica transparente 30 cm',
        'category' => 'Geometría',
        'short' => 'Regla plástica transparente 30 cm.',
        'long' => 'Regla plástica transparente de 30 cm, graduada en centímetros y pulgadas.',
        'tags' => array('regla', 'plastica', 'geometria'),
        'price' => '4.20', 'stock' => 36,
    ),
    array(
        'sku' => 'TAN-TIJ-001', 'old_sku' => 'TAN-AVN-001',
        'name' => 'Tijera Escolar Punta Roma',
        'image' => '10-tijera-escolar', 'alt' => 'Tijera escolar de punta roma',
        'category' => 'Útiles varios',
        'short' => 'Tijera escolar punta roma.',
        'long' => 'Tijera escolar de punta roma, mango ergonómico, segura para uso de niños y adolescentes.',
        'tags' => array('tijera', 'escolar', 'utiles'),
        'price' => '5.90', 'stock' => 20,
    ),
    array(
        'sku' => 'TAN-GOM-001', 'old_sku' => 'TAN-ARR-001',
        'name' => 'Goma en Barra',
        'image' => '11-goma-barra', 'alt' => 'Goma en barra adhesiva',
        'category' => 'Útiles varios',
        'short' => 'Goma en barra adhesiva.',
        'long' => 'Goma en barra adhesiva, de secado rápido y sin residuos, ideal para trabajos manuales.',
        'tags' => array('goma', 'barra', 'utiles'),
        'price' => '5.50', 'stock' => 29,
    ),
    array(
        'sku' => 'TAN-MOC-001', 'old_sku' => 'TAN-AZU-001',
        'name' => 'Mochila Escolar',
        'image' => '12-mochila-escolar', 'alt' => 'Mochila escolar con compartimentos',
        'category' => 'Mochilas',
        'short' => 'Mochila escolar resistente.',
        'long' => 'Mochila escolar resistente, con compartimentos internos y correas ajustables, ideal para uso diario.',
        'tags' => array('mochila', 'escolar', 'utiles'),
        'price' => '4.80', 'stock' => 24,
    ),
);

function tanish_seed_category($name) {
    $term = term_exists($name, 'product_cat');
    if (!$term) {
        $term = wp_insert_term($name, 'product_cat');
    }
    if (is_wp_error($term)) {
        return 0;
    }
    return (int) (is_array($term) ? $term['term_id'] : $term);
}

function tanish_seed_image($product_id, $base, $alt, $dir) {
    // Si el producto ya tiene su foto (mismo nombre base), no se vuelve a subir.
    $thumb_id = get_post_thumbnail_id($product_id);
    if ($thumb_id) {
        $file = get_attached_file($thumb_id);
        if ($file && strpos(basename($file), $base) === 0) {
            return 'imagen ya existente';
        }
    }

    $matches = glob($dir . '/' . $base . '.*');
    if (empty($matches)) {
        return 'FALTA la foto ' . $base . '.* en seed-images/';
    }

    $source = $matches[0];
    $tmp = wp_tempnam(basename($source));
    if (!$tmp || !copy($source, $tmp)) {
        return 'error copiando la foto';
    }

    $file_array = array('name' => basename($source), 'tmp_name' => $tmp);
    $att_id = media_handle_sideload($file_array, $product_id, $alt);
    if (is_wp_error($att_id)) {
        @unlink($tmp);
        return 'error subiendo la foto: ' . $att_id->get_error_message();
    }

    update_post_meta($att_id, '_wp_attachment_image_alt', $alt);
    set_post_thumbnail($product_id, $att_id);
    return 'imagen cargada';
}

echo "TANISH - Productos de útiles\n";
echo "----------------------------\n";

$ok = 0;
foreach ($products as $p) {
    try {
        $mode = 'actualizado';
        $id = wc_get_product_id_by_sku($p['sku']);
        if (!$id && !empty($p['old_sku'])) {
            $id = wc_get_product_id_by_sku($p['old_sku']);
        }

        if ($id) {
            $product = wc_get_product($id);
            if (!$product || !$product->is_type('simple')) {
                echo "[OMITIDO] {$p['sku']}: el producto existente no es de tipo simple\n";
                continue;
            }
        } else {
            $product = new WC_Product_Simple();
            $mode = 'creado';
        }

        $product->set_name($p['name']);
        $product->set_slug(sanitize_title($p['name']));
        $product->set_sku($p['sku']);
        $product->set_description($p['long']);
        $product->set_short_description($p['short']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_category_ids(array(tanish_seed_category($p['category'])));

        if ($mode === 'creado') {
            $product->set_regular_price($p['price']);
            $product->set_manage_stock(true);
            $product->set_stock_quantity($p['stock']);
            $product->set_stock_status('instock');
        }

        $product_id = $product->save();
        wp_set_object_terms($product_id, $p['tags'], 'product_tag');

        $img = tanish_seed_image($product_id, $p['image'], $p['alt'], $images_dir);
        echo "[OK] {$p['sku']} {$p['name']} ({$mode}; {$img})\n";
        $ok++;
    } catch (Exception $e) {
        echo "[ERROR] {$p['sku']}: " . $e->getMessage() . "\n";
    }
}

echo "----------------------------\n";
echo "Listo: {$ok} de " . count($products) . " productos procesados.\n";
echo "Recuerda ejecutar el chown de uploads (ver instrucciones al inicio del archivo).\n";