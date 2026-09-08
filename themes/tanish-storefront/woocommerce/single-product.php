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

	<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">
		<?php
		do_action('woocommerce_before_shop_loop');
		woocommerce_breadcrumb();
		?>
	</nav>

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
		 * Tabs + Related products (outside the grid, full width)
		 */
		woocommerce_output_product_data_tabs();
		woocommerce_output_related_products();
		?>

	<?php endwhile; ?>

</div>

<?php
do_action('woocommerce_after_main_content');
get_footer('shop');
