<?php
$videos = new WP_Query([
    'post_type'      => 'tanish_video',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'meta_key'       => '_tanish_video_active',
    'meta_value'     => '1',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

$has_videos = $videos->have_posts();
$featured_video = null;
$side_videos = [];

if ($has_videos) {
    while ($videos->have_posts()) {
        $videos->the_post();
        $is_featured = get_post_meta(get_the_ID(), '_tanish_video_featured', true);
        $video_url = get_post_meta(get_the_ID(), '_tanish_video_url', true);
        $item = [
            'id'         => get_the_ID(),
            'title'      => get_the_title(),
            'url'        => $video_url,
            'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: '',
            'is_featured' => ($is_featured === '1'),
        ];
        if ($item['is_featured'] && !$featured_video) {
            $featured_video = $item;
        } else {
            $side_videos[] = $item;
        }
    }
    wp_reset_postdata();
    if (!$featured_video && !empty($side_videos)) {
        $featured_video = array_shift($side_videos);
    }
}
?>

<!-- VIDEOS / NOVEDADES -->
<?php if ($has_videos && $featured_video) : ?>
    <section class="home-section home-section--videos tanish-videos" data-reveal="up">
        <div class="tanish-container">
            <div class="section-header">
                <div>
                    <div class="section-eyebrow">Novedades</div>
                    <h2 class="section-title">Mira nuestras novedades</h2>
                    <p class="section-description">Descubre productos, promociones y novedades de TANISH.</p>
                </div>
            </div>
            <div class="videos-layout">
                <div class="video-main">
                    <div class="video-main-player" data-video-url="<?php echo esc_url($featured_video['url']); ?>">
                        <?php if ($featured_video['thumbnail']) : ?>
                            <div class="video-poster">
                                <img src="<?php echo esc_url($featured_video['thumbnail']); ?>" alt="<?php echo esc_attr($featured_video['title']); ?>" loading="lazy">
                                <button class="video-play-btn" aria-label="Reproducir video">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="video-main-title"><?php echo esc_html($featured_video['title']); ?></h3>
                </div>
                <?php if (!empty($side_videos)) : ?>
                    <div class="video-sidebar">
                        <?php foreach ($side_videos as $sv) : ?>
                            <div class="video-sidebar-item" data-video-url="<?php echo esc_url($sv['url']); ?>" tabindex="0" role="button" aria-label="Reproducir <?php echo esc_attr($sv['title']); ?>">
                                <div class="video-sidebar-thumb">
                                    <?php if ($sv['thumbnail']) : ?>
                                        <img src="<?php echo esc_url($sv['thumbnail']); ?>" alt="<?php echo esc_attr($sv['title']); ?>" loading="lazy">
                                    <?php endif; ?>
                                    <button class="video-play-btn video-play-btn--small" aria-label="Reproducir">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    </button>
                                </div>
                                <div class="video-sidebar-info">
                                    <div class="video-sidebar-title"><?php echo esc_html($sv['title']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php elseif (current_user_can('manage_options')) : ?>
    <section class="home-section home-section--videos tanish-videos tanish-videos--admin-hint">
        <div class="tanish-container">
            <div class="admin-video-hint">
                <p>Agrega videos desde <a href="<?php echo esc_url(admin_url('edit.php?post_type=tanish_video')); ?>">Videos TANISH</a>.</p>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php wp_reset_postdata(); ?>
