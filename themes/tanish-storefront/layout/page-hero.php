<?php
$page_title = $page_title ?? get_the_title();
$page_subtitle = $page_subtitle ?? '';
?>

<section class="page-hero">
    <div class="tanish-container">
        <h1 class="page-hero-title"><?php echo esc_html($page_title); ?></h1>
        <?php if ($page_subtitle) : ?>
            <p class="page-hero-subtitle"><?php echo esc_html($page_subtitle); ?></p>
        <?php endif; ?>
    </div>
</section>
