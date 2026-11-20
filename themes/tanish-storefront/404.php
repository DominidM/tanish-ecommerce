<?php
/**
 * Vista 404 personalizada.
 *
 * @package Tanish_Storefront
 */

defined('ABSPATH') || exit;

get_header();

$tanish_shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');

$tanish_whatsapp = preg_replace('/\D/', '', (string) get_option('tanish_whatsapp_number', ''));
$tanish_whatsapp_url = $tanish_whatsapp
    ? 'https://wa.me/' . $tanish_whatsapp . '?text=' . rawurlencode('Hola, estaba buscando un producto en la web de TANISH y no lo encuentro.')
    : '';

$tanish_categories = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'parent'     => 0,
    'number'     => 6,
]);
?>

<main id="main" class="page-main tanish-404" role="main">

    <section class="tanish-404-hero">
        <div class="tanish-container">
            <div class="tanish-404-inner">

                <div class="tanish-404-code" aria-hidden="true">404</div>

                <div class="section-eyebrow">Página no encontrada</div>

                <h1 class="section-title">No encontramos lo que buscabas</h1>

                <p class="section-description">
                    El enlace puede estar roto o el producto ya no estar disponible.
                    Busca en el catálogo o vuelve al inicio: seguro encuentras lo que necesitas.
                </p>

                <form role="search" method="get" class="tanish-404-search" action="<?php echo esc_url(home_url('/')); ?>">
                    <label class="screen-reader-text" for="tanish-404-search-input">Buscar productos</label>
                    <input
                        type="search"
                        id="tanish-404-search-input"
                        name="s"
                        placeholder="Buscar productos..."
                        value="<?php echo esc_attr(get_search_query()); ?>"
                    >
                    <input type="hidden" name="post_type" value="product">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>

                <div class="tanish-404-actions">
                    <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Volver al inicio</a>
                    <a class="btn btn-secondary" href="<?php echo esc_url($tanish_shop_url); ?>">Ver catálogo</a>
                    <?php if ($tanish_whatsapp_url) : ?>
                        <a class="btn btn-secondary" href="<?php echo esc_url($tanish_whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">
                            Consultar por WhatsApp
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (is_search()) : ?>
                    <p class="tanish-404-hint">
                        No hay resultados para «<?php echo esc_html(get_search_query()); ?>».
                    </p>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <?php if (!is_wp_error($tanish_categories) && !empty($tanish_categories)) : ?>
        <section class="home-section home-section--categories tanish-404-categories">
            <div class="tanish-container">
                <div class="section-header">
                    <div>
                        <div class="section-eyebrow">Explora</div>
                        <h2 class="section-title">Compra por categoría</h2>
                        <p class="section-description">Estas son las categorías disponibles en TANISH.</p>
                    </div>
                </div>

                <div class="category-grid">
                    <?php foreach ($tanish_categories as $tanish_category) : ?>
                        <?php
                        $tanish_link = get_term_link($tanish_category);
                        if (is_wp_error($tanish_link)) {
                            continue;
                        }
                        $tanish_thumb_id = get_term_meta($tanish_category->term_id, 'thumbnail_id', true);
                        ?>
                        <a href="<?php echo esc_url($tanish_link); ?>" class="category-card">
                            <div class="category-image">
                                <?php if (!empty($tanish_thumb_id)) : ?>
                                    <?php echo wp_get_attachment_image($tanish_thumb_id, 'woocommerce_thumbnail', false, [
                                        'class'   => 'category-img',
                                        'loading' => 'lazy',
                                    ]); ?>
                                <?php else : ?>
                                    <div class="category-placeholder">
                                        <span><?php echo esc_html(mb_strtoupper(mb_substr($tanish_category->name, 0, 1))); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="category-text">
                                <div class="category-name"><?php echo esc_html($tanish_category->name); ?></div>
                                <div class="category-count"><?php echo esc_html($tanish_category->count); ?> productos</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<style>
.tanish-404-hero {
    padding: clamp(48px, 8vw, 96px) 0;
    background:
        radial-gradient(circle at 15% 0%, rgba(98, 208, 162, .16), transparent 45%),
        radial-gradient(circle at 85% 20%, rgba(125, 211, 252, .14), transparent 40%),
        #f7f9f8;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
}
.tanish-404-inner {
    max-width: 760px;
    margin: 0 auto;
    text-align: center;
}
.tanish-404-code {
    font-size: clamp(88px, 18vw, 180px);
    font-weight: 900;
    line-height: .9;
    letter-spacing: -.06em;
    color: transparent;
    -webkit-text-stroke: 2px rgba(0, 0, 0, .12);
    margin-bottom: 8px;
    user-select: none;
}
.tanish-404-inner .section-title {
    margin: 10px 0 14px;
}
.tanish-404-inner .section-description {
    max-width: 560px;
    margin: 0 auto 26px;
}
.tanish-404-search {
    display: flex;
    gap: 10px;
    max-width: 520px;
    margin: 0 auto 22px;
}
.tanish-404-search input[type="search"] {
    flex: 1 1 auto;
    min-width: 0;
    padding: 12px 16px;
    border: 1px solid rgba(0, 0, 0, .14);
    border-radius: 10px;
    font: inherit;
    background: #fff;
}
.tanish-404-search input[type="search"]:focus {
    outline: 2px solid rgba(98, 208, 162, .55);
    outline-offset: 1px;
}
.tanish-404-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}
.tanish-404-hint {
    margin-top: 18px;
    font-size: 14px;
    color: rgba(0, 0, 0, .55);
}
.tanish-404-categories {
    padding-top: clamp(36px, 6vw, 64px);
}
@media (max-width: 560px) {
    .tanish-404-search {
        flex-direction: column;
    }
    .tanish-404-search .btn {
        width: 100%;
    }
}
</style>

<?php get_footer(); ?>
