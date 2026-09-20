<?php
defined('ABSPATH') || exit;
?>

<footer class="site-footer">
    <div class="tanish-footer-wrap">
        <div class="tanish-container">
            <?php get_template_part('layout/footer-main'); ?>
        </div>

        <?php get_template_part('layout/footer-sub'); ?>
    </div>
</footer>

<?php wp_footer(); ?>
<?php get_template_part('layout/whatsapp-dock'); ?>
</body>
</html>
