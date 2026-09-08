<?php
/**
 * Result Count - Spanish override
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<p class="woocommerce-result-count" role="status" aria-relevant="all" <?php echo (empty($orderedby) || 1 === intval($total)) ? '' : 'data-is-sorted-by="true"'; ?>>
	<?php
	if (1 === intval($total)) {
		echo 'Mostrando el único resultado';
	} elseif ($total <= $per_page || -1 === $per_page) {
		printf('Mostrando todos los %d resultados', $total);
	} else {
		$first = ($per_page * $current) - $per_page + 1;
		$last = min($total, $per_page * $current);
		printf('Mostrando %1$d–%2$d de %3$d resultados', $first, $last, $total);
	}
	?>
</p>
