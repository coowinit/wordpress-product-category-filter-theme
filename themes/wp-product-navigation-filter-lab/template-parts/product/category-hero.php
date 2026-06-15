<?php
/**
 * 产品分类头部内容。
 */

$term = pfl_get_current_product_category();

if (! $term) {
    return;
}

$content = pfl_get_product_category_content($term);
$top_content = $content['top_content'];

if (! $top_content) {
    $top_content = term_description($term->term_id, 'product_category');
}

$has_image = ! empty($content['image_id']);
?>

<header class="archive-heading product-category-hero<?php echo $has_image ? ' has-image' : ''; ?>">
    <?php if ($has_image) : ?>
        <figure class="product-category-hero__media">
            <?php
            echo wp_get_attachment_image(
                (int) $content['image_id'],
                'large',
                false,
                [
                    'loading' => 'eager',
                    'fetchpriority' => 'high',
                ]
            );
            ?>
        </figure>
    <?php endif; ?>

    <div class="product-category-hero__content">
        <span class="product-category-hero__eyebrow">产品分类</span>

        <h1><?php echo esc_html($term->name); ?></h1>

        <?php if ($top_content) : ?>
            <div class="archive-description product-category-hero__description">
                <?php echo wp_kses_post(wpautop($top_content)); ?>
            </div>
        <?php endif; ?>
    </div>
</header>
