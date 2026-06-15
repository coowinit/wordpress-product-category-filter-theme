<?php
/**
 * 产品结果区域。
 *
 * @var WP_Query $args['query']
 * @var string   $args['context']
 * @var string   $args['base_url']
 * @var array    $args['source']
 */

$query = (
    isset($args['query'])
    && $args['query'] instanceof WP_Query
)
    ? $args['query']
    : $GLOBALS['wp_query'];

$context = isset($args['context'])
    ? sanitize_key((string) $args['context'])
    : 'archive';

$base_url = isset($args['base_url'])
    ? (string) $args['base_url']
    : pfl_get_product_filter_action();

$source = (
    isset($args['source'])
    && is_array($args['source'])
)
    ? $args['source']
    : $_GET;

$found_posts  = (int) $query->found_posts;
$current_page = max(1, (int) $query->get('paged'));
$summary      = 'taxonomy' === $context
    ? sprintf('当前分类共找到 %d 个产品', $found_posts)
    : sprintf('共找到 %d 个产品', $found_posts);
?>

<section
    id="product-results"
    class="product-results"
    tabindex="-1"
    aria-busy="false"
    data-current-page="<?php echo esc_attr((string) $current_page); ?>"
>
    <div class="product-results__loading" aria-hidden="true">
        <span class="product-results__spinner"></span>
        <span>正在更新产品结果</span>
    </div>

    <div class="product-results__header">
        <strong data-product-result-summary>
            <?php echo esc_html($summary); ?>
        </strong>
    </div>

    <?php if ($query->have_posts()) : ?>
        <div class="product-grid">
            <?php while ($query->have_posts()) : ?>
                <?php
                $query->the_post();

                get_template_part(
                    'template-parts/product/card'
                );
                ?>
            <?php endwhile; ?>
        </div>

        <?php
        // 分页 HTML 由主题内部函数生成，链接和 data-page 均已转义。
        echo pfl_get_product_pagination_html(
            $query,
            $base_url,
            $source
        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    <?php else : ?>
        <?php
        get_template_part(
            'template-parts/product/no-results',
            null,
            [
                'source'   => $source,
                'base_url' => $base_url,
            ]
        );
        ?>
    <?php endif; ?>
</section>
