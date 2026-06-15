<?php
/**
 * 筛选查询模块
 *
 * 把标准筛选请求转换成 tax_query、meta_query、排序和 WP_Query 参数。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 十二、根据筛选来源生成通用查询片段。
 *
 * 普通 GET 请求和 AJAX 请求共用此函数，避免两套查询规则逐渐不一致。
 */
function pfl_get_product_query_parts(
    array $source,
    ?array $active_keys = null
): array {
    $schema = pfl_get_product_filter_schema();
    $active_keys = null === $active_keys ? pfl_get_active_filter_keys() : pfl_sanitize_filter_keys($active_keys);
    $tax_clauses = [];
    $meta_clauses = [];

    foreach ($active_keys as $key) {
        if (! isset($schema[$key])) {
            continue;
        }

        $filter = $schema[$key];

        if ('taxonomy' === $filter['type']) {
            $values = pfl_get_filter_values($key, $source, $active_keys);

            if (! empty($values)) {
                $tax_clauses[] = [
                    'taxonomy' => $filter['taxonomy'], 'field' => 'slug',
                    'terms' => $values, 'operator' => $filter['operator'],
                ];
            }
        } elseif ('range' === $filter['type']) {
            $selected = pfl_get_filter_value($key, $source, $active_keys);

            if ($selected && isset($filter['options'][$selected])) {
                $option = $filter['options'][$selected];
                $meta_clauses[] = [
                    'key' => $filter['meta_key'], 'value' => $option['value'],
                    'type' => 'NUMERIC', 'compare' => $option['compare'],
                ];
            }
        }
    }

    $sort = pfl_get_filter_value('sort', $source, $active_keys);
    $sort_args = ['orderby' => 'date', 'order' => 'DESC'];

    if ('price-asc' === $sort || 'price-desc' === $sort) {
        $sort_args = [
            'meta_key' => 'product_price', 'orderby' => 'meta_value_num',
            'order' => 'price-asc' === $sort ? 'ASC' : 'DESC',
        ];
    } elseif ('title' === $sort) {
        $sort_args = ['orderby' => 'title', 'order' => 'ASC'];
    }

    return [
        'tax_clauses' => $tax_clauses,
        'meta_clauses' => $meta_clauses,
        'sort_args' => $sort_args,
    ];
}


/**
 * 根据标准筛选请求生成完整 WP_Query 参数。
 *
 * AJAX 与其他独立查询应优先调用此函数，避免重复拼装 tax_query 和 meta_query。
 */
function pfl_build_product_query_args(
    array $request,
    ?WP_Term $term = null,
    int $posts_per_page = 9
): array {
    $source = isset($request['source']) && is_array($request['source'])
        ? $request['source']
        : [];
    $active_keys = isset($request['active_keys']) && is_array($request['active_keys'])
        ? pfl_sanitize_filter_keys($request['active_keys'])
        : pfl_get_active_filter_keys($term);
    $paged = max(1, (int) ($request['paged'] ?? 1));
    $parts = pfl_get_product_query_parts($source, $active_keys);

    $args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_per_page,
        'paged'               => $paged,
        'ignore_sticky_posts' => true,
    ];

    $tax_query = ['relation' => 'AND'];

    if ($term instanceof WP_Term) {
        $tax_query[] = [
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => [(int) $term->term_id],
            'include_children' => true,
        ];
    }

    foreach ($parts['tax_clauses'] as $clause) {
        $tax_query[] = $clause;
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    if (! empty($parts['meta_clauses'])) {
        $args['meta_query'] = array_merge(
            ['relation' => 'AND'],
            $parts['meta_clauses']
        );
    }

    return array_merge($args, $parts['sort_args']);
}


/**
 * 将筛选片段应用到一个 WP_Query 对象。
 */
function pfl_apply_product_query_parts(
    WP_Query $query,
    array $source,
    ?array $active_keys = null
): void {
    $parts = pfl_get_product_query_parts($source, $active_keys);

    if (! empty($parts['tax_clauses'])) {
        $tax_query = (array) $query->get('tax_query');
        if (! isset($tax_query['relation'])) { $tax_query['relation'] = 'AND'; }
        foreach ($parts['tax_clauses'] as $clause) { $tax_query[] = $clause; }
        $query->set('tax_query', $tax_query);
    }

    if (! empty($parts['meta_clauses'])) {
        $meta_query = (array) $query->get('meta_query');
        if (! isset($meta_query['relation'])) { $meta_query['relation'] = 'AND'; }
        foreach ($parts['meta_clauses'] as $clause) { $meta_query[] = $clause; }
        $query->set('meta_query', $meta_query);
    }

    foreach ($parts['sort_args'] as $key => $value) {
        $query->set($key, $value);
    }
}


/**
 * 使用 pre_get_posts 修改产品主查询。
 *
 * 分类页面原本已经带有 product_category 条件；这里只追加筛选条件。
 */
function pfl_filter_product_main_query(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if (! $query->is_post_type_archive('product') && ! $query->is_tax('product_category')) {
        return;
    }

    $term = pfl_get_query_product_category($query);
    $request = pfl_get_filter_request(
        $_GET,
        $term,
        max(1, (int) $query->get('paged'))
    );

    $query->set('post_type', 'product');
    $query->set('posts_per_page', 9);
    pfl_apply_product_query_parts(
        $query,
        $request['source'],
        $request['active_keys']
    );
}
add_action('pre_get_posts', 'pfl_filter_product_main_query');
