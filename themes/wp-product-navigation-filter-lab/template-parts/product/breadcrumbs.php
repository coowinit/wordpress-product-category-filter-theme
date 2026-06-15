<?php
/**
 * 产品归档与产品分类面包屑。
 */

$items = pfl_get_product_breadcrumb_items();
$parent = pfl_get_parent_product_category_data();

if (empty($items)) {
    return;
}
?>

<div class="product-navigation-context">
    <nav class="product-breadcrumb" aria-label="面包屑导航">
        <ol>
            <?php foreach ($items as $index => $item) : ?>
                <?php $is_last = $index === count($items) - 1; ?>

                <li<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
                    <?php if (! empty($item['url']) && ! $is_last) : ?>
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['label']); ?>
                        </a>
                    <?php else : ?>
                        <span><?php echo esc_html($item['label']); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <?php if (! empty($parent['url'])) : ?>
        <a class="product-parent-link" href="<?php echo esc_url($parent['url']); ?>">
            <span aria-hidden="true">←</span>
            返回<?php echo esc_html($parent['label']); ?>
        </a>
    <?php endif; ?>
</div>
