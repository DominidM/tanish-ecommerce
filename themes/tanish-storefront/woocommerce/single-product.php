<?php
/**
 * Single Product Template
 *
 * Custom layout: 2-column grid (image left, summary right), no sidebar.
 */

defined('ABSPATH') || exit;

get_header('shop');

do_action('woocommerce_before_main_content');
?>

<div class="tanish-container tanish-product-page">

	<?php
	/**
	 * Avisos de WooCommerce (carrito, errores...) fuera del breadcrumb.
	 * Este hook tambien lo usan otros plugins; imprime los notices en su
	 * prioridad 10 sin ensuciar el HTML del breadcrumb.
	 */
	do_action('woocommerce_before_single_product');
	?>

	<?php
	// woocommerce_breadcrumb() ya imprime su propio <nav class="woocommerce-breadcrumb">.
	woocommerce_breadcrumb();
	?>

	<?php while (have_posts()) : ?>
		<?php the_post(); ?>

		<div id="product-<?php the_ID(); ?>" <?php wc_product_class('tanish-product-grid', $product); ?>>

			<div class="tanish-product-gallery">
				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary
				 * Outputs: featured image + gallery
				 */
				do_action('woocommerce_before_single_product_summary');
				?>
			</div>

			<div class="tanish-product-summary">
				<?php
				/**
				 * Hook: woocommerce_single_product_summary
				 * Outputs: title, rating, price, excerpt, add-to-cart, meta
				 */
				do_action('woocommerce_single_product_summary');
				?>
			</div>

		</div>

		<?php
		/**
		 * Tabs, upsells y productos relacionados (fuera de la rejilla).
		 * WooCommerce engancha aqui:
		 *   - woocommerce_output_product_data_tabs (10)
		 *   - woocommerce_upsell_display             (15)
		 *   - woocommerce_output_related_products    (20)
		 */
		do_action('woocommerce_after_single_product_summary');
		?>

	<?php endwhile; ?>

</div>

<?php
do_action('woocommerce_after_single_product');
do_action('woocommerce_after_main_content');
get_footer('shop');
