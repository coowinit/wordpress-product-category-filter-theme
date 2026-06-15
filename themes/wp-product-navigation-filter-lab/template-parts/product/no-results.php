<?php
/**
 * 产品无结果状态。
 *
 * @var array  $args['source']
 * @var string $args['base_url']
 */

$source = (
    isset($args['source'])
    && is_array($args['source'])
)
    ? $args['source']
    : $_GET;

$reset_url = isset($args['base_url'])
    ? (string) $args['base_url']
    : pfl_get_product_filter_action();

$has_filters = pfl_has_active_product_filters($source);
$archive_url = get_post_type_archive_link('product');
?>

<div class="empty-products empty-products--enhanced">
    <span class="empty-products__icon" aria-hidden="true">⌕</span>

    <h2>
        <?php echo $has_filters ? '没有找到符合条件的产品' : '当前目录暂时没有产品'; ?>
    </h2>

    <p>
        <?php if ($has_filters) : ?>
            当前条件组合可能过于严格。可以先清空筛选，再逐项增加条件观察结果变化。
        <?php else : ?>
            请在后台添加产品，或进入其他产品分类继续查看。
        <?php endif; ?>
    </p>

    <div class="empty-products__actions">
        <?php if ($has_filters) : ?>
            <a
                class="empty-products__primary"
                href="<?php echo esc_url($reset_url); ?>"
                data-filter-reset
            >
                清空当前筛选
            </a>
        <?php endif; ?>

        <?php if ($archive_url && $archive_url !== $reset_url) : ?>
            <a class="empty-products__secondary" href="<?php echo esc_url($archive_url); ?>">
                查看全部产品
            </a>
        <?php endif; ?>
    </div>
</div>
