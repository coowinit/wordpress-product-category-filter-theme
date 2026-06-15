<?php
/**
 * 筛选 AJAX 模块
 *
 * 负责分页 HTML、结果模板渲染、Nonce 校验和 JSON 响应。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 生成带 data-page 的产品分页，供普通链接和 AJAX 共用。
 */
function pfl_get_product_pagination_html(
    WP_Query $query,
    string $base_url,
    array $source,
    ?array $active_keys = null
): string {
    $total = max(1, (int) $query->max_num_pages);
    $current = max(1, (int) $query->get('paged'));

    if ($total <= 1) { return ''; }

    $pages = [];
    if ($total <= 7) {
        $pages = range(1, $total);
    } else {
        $pages[] = 1;
        $start = max(2, $current - 1);
        $end = min($total - 1, $current + 1);
        if ($start > 2) { $pages[] = 'dots'; }
        for ($page = $start; $page <= $end; $page++) { $pages[] = $page; }
        if ($end < $total - 1) { $pages[] = 'dots'; }
        $pages[] = $total;
    }

    ob_start();
    ?>
    <nav class="product-pagination" aria-label="产品分页">
        <ul class="page-numbers">
            <?php if ($current > 1) : ?>
                <li><a class="page-numbers prev" href="<?php echo esc_url(pfl_build_product_state_url($base_url, $source, $current - 1, $active_keys)); ?>" data-product-page="<?php echo esc_attr((string) ($current - 1)); ?>">上一页</a></li>
            <?php endif; ?>

            <?php foreach ($pages as $page) : ?>
                <?php if ('dots' === $page) : ?>
                    <li><span class="page-numbers dots" aria-hidden="true">…</span></li>
                <?php elseif ((int) $page === $current) : ?>
                    <li><span class="page-numbers current" aria-current="page"><?php echo esc_html((string) $page); ?></span></li>
                <?php else : ?>
                    <li><a class="page-numbers" href="<?php echo esc_url(pfl_build_product_state_url($base_url, $source, (int) $page, $active_keys)); ?>" data-product-page="<?php echo esc_attr((string) $page); ?>"><?php echo esc_html((string) $page); ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current < $total) : ?>
                <li><a class="page-numbers next" href="<?php echo esc_url(pfl_build_product_state_url($base_url, $source, $current + 1, $active_keys)); ?>" data-product-page="<?php echo esc_attr((string) ($current + 1)); ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
    return (string) ob_get_clean();
}


/**
 * 渲染产品结果区，普通页面和 AJAX 返回共用同一模板。
 */
function pfl_render_product_results_html(
    WP_Query $query,
    string $context,
    string $base_url,
    array $source,
    ?array $active_keys = null
): string {
    ob_start();
    get_template_part(
        'template-parts/product/results',
        null,
        [
            'query' => $query, 'context' => $context,
            'base_url' => $base_url, 'source' => $source,
            'active_keys' => $active_keys,
        ]
    );
    wp_reset_postdata();
    return (string) ob_get_clean();
}


/**
 * AJAX：根据当前筛选条件返回产品列表、数量和分页。
 */
function pfl_ajax_filter_products(): void
{
    check_ajax_referer('pfl_filter_products', 'nonce');

    $query_string = isset($_POST['query']) ? (string) wp_unslash($_POST['query']) : '';
    $source = [];
    parse_str($query_string, $source);

    $context = isset($_POST['context']) ? sanitize_key(wp_unslash($_POST['context'])) : 'archive';
    if (! in_array($context, ['archive', 'taxonomy'], true)) { $context = 'archive'; }

    $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
    $term = null;

    if ('taxonomy' === $context) {
        $candidate = get_term($term_id, 'product_category');
        if ($candidate instanceof WP_Term) {
            $term = $candidate;
        } else {
            $context = 'archive';
            $term_id = 0;
        }
    }

    $paged = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
    $request = pfl_get_filter_request($source, $term, $paged);
    $source = $request['source'];
    $active_keys = $request['active_keys'];
    $query_args = pfl_build_product_query_args($request, $term, 9);
    $product_query = new WP_Query($query_args);
    $base_url = pfl_get_product_context_url($context, $term_id);
    $state_url = pfl_build_product_state_url($base_url, $source, $paged, $active_keys);
    $facet_state = pfl_get_faceted_filter_state(
        $source,
        $term,
        $active_keys
    );

    nocache_headers();
    wp_send_json_success([
        'html' => pfl_render_product_results_html($product_query, $context, $base_url, $source, $active_keys),
        'foundPosts' => (int) $product_query->found_posts,
        'maxPages' => (int) $product_query->max_num_pages,
        'currentPage' => $paged,
        'appliedCount' => (int) $request['applied_count'],
        'facets' => $facet_state['facets'],
        'facetMeta' => $facet_state['meta'],
        'url' => $state_url,
        'announcement' => sprintf(
            '筛选完成，共找到 %d 个产品，筛选项数量已经同步更新。',
            (int) $product_query->found_posts
        ),
    ]);
}
add_action('wp_ajax_pfl_filter_products', 'pfl_ajax_filter_products');
add_action('wp_ajax_nopriv_pfl_filter_products', 'pfl_ajax_filter_products');
