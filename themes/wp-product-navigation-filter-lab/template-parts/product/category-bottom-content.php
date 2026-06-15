<?php
/**
 * 产品分类结果下方的补充内容。
 */

$term = pfl_get_current_product_category();

if (! $term) {
    return;
}

$content = pfl_get_product_category_content($term);

if (empty($content['bottom_content'])) {
    return;
}
?>

<section class="product-category-bottom-content" aria-labelledby="product-category-bottom-title">
    <span class="product-category-bottom-content__eyebrow">分类指南</span>

    <h2 id="product-category-bottom-title">
        关于<?php echo esc_html($term->name); ?>
    </h2>

    <div class="product-category-bottom-content__body">
        <?php echo wp_kses_post(wpautop($content['bottom_content'])); ?>
    </div>
</section>
