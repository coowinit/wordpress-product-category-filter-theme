<?php
/**
 * 筛选请求模块
 *
 * 读取、清理并验证筛选参数，同时生成 GET 与 AJAX 共用的标准请求结构。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 八、取得分类法允许使用的 term slug 白名单。
 */
function pfl_get_allowed_taxonomy_slugs(string $taxonomy): array
{
    static $cache = [];

    if (array_key_exists($taxonomy, $cache)) {
        return $cache[$taxonomy];
    }

    if (! taxonomy_exists($taxonomy)) {
        $cache[$taxonomy] = [];
        return [];
    }

    $slugs = get_terms(
        [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'fields'     => 'slugs',
        ]
    );

    if (is_wp_error($slugs)) {
        $cache[$taxonomy] = [];
        return [];
    }

    $cache[$taxonomy] = array_values(
        array_unique(
            array_map('sanitize_title', $slugs)
        )
    );

    return $cache[$taxonomy];
}


/**
 * 九、读取并验证 GET 数组参数。
 */
function pfl_get_filter_values(
    string $key,
    ?array $source = null,
    ?array $active_keys = null
): array {
    $schema = pfl_get_product_filter_schema();
    $source = null === $source ? $_GET : $source;
    $active_keys = null === $active_keys ? pfl_get_active_filter_keys() : pfl_sanitize_filter_keys($active_keys);

    if (
        ! isset($schema[$key])
        || 'taxonomy' !== $schema[$key]['type']
        || ! in_array($key, $active_keys, true)
        || ! isset($source[$key])
    ) {
        return [];
    }

    $values = array_map('sanitize_title', (array) wp_unslash($source[$key]));
    $values = array_slice(array_values(array_unique(array_filter($values))), 0, 20);
    $allowed = pfl_get_allowed_taxonomy_slugs($schema[$key]['taxonomy']);

    return array_values(array_intersect($values, $allowed));
}


/**
 * 十、读取并验证 GET 单值参数。
 */
function pfl_get_filter_value(
    string $key,
    ?array $source = null,
    ?array $active_keys = null
): string {
    $source = null === $source ? $_GET : $source;

    if (! isset($source[$key]) || is_array($source[$key])) {
        return '';
    }

    $value = sanitize_key(wp_unslash($source[$key]));

    if ('sort' === $key) {
        return array_key_exists($value, pfl_get_sort_options()) ? $value : '';
    }

    $schema = pfl_get_product_filter_schema();
    $active_keys = null === $active_keys ? pfl_get_active_filter_keys() : pfl_sanitize_filter_keys($active_keys);

    if (
        ! isset($schema[$key])
        || 'range' !== $schema[$key]['type']
        || ! in_array($key, $active_keys, true)
    ) {
        return '';
    }

    return isset($schema[$key]['options'][$value]) ? $value : '';
}


/**
 * 十一、统计当前已经应用的筛选条件数量。
 */
function pfl_get_applied_filter_count(
    ?array $source = null,
    ?array $active_keys = null
): int {
    $active_keys = null === $active_keys ? pfl_get_active_filter_keys() : pfl_sanitize_filter_keys($active_keys);
    $schema = pfl_get_product_filter_schema();
    $count = 0;

    foreach ($active_keys as $key) {
        if (! isset($schema[$key])) {
            continue;
        }

        if ('taxonomy' === $schema[$key]['type']) {
            $count += count(pfl_get_filter_values($key, $source, $active_keys));
        } elseif (pfl_get_filter_value($key, $source, $active_keys)) {
            $count++;
        }
    }

    return $count;
}


function pfl_has_active_product_filters(
    ?array $source = null,
    ?array $active_keys = null
): bool {
    return pfl_get_applied_filter_count($source, $active_keys) > 0;
}


/**
 * 将任意来源（GET 或 AJAX query 字符串）统一整理成筛选请求。
 *
 * 返回值中的 source 已经过白名单校验，可以安全交给查询、Facet 和 URL 函数。
 */
function pfl_get_filter_request(
    array $source,
    ?WP_Term $term = null,
    int $paged = 1
): array {
    $schema = pfl_get_product_filter_schema();
    $active_keys = pfl_get_active_filter_keys($term);
    $clean_source = [];
    $applied_count = 0;

    foreach ($active_keys as $key) {
        if (! isset($schema[$key])) {
            continue;
        }

        if ('taxonomy' === $schema[$key]['type']) {
            $values = pfl_get_filter_values($key, $source, $active_keys);

            if (! empty($values)) {
                $clean_source[$key] = $values;
                $applied_count += count($values);
            }
        } else {
            $value = pfl_get_filter_value($key, $source, $active_keys);

            if ('' !== $value) {
                $clean_source[$key] = $value;
                $applied_count++;
            }
        }
    }

    $sort = pfl_get_filter_value('sort', $source, $active_keys);

    if ('' !== $sort) {
        $clean_source['sort'] = $sort;
    }

    return [
        'source'        => $clean_source,
        'active_keys'   => $active_keys,
        'paged'         => max(1, $paged),
        'applied_count' => $applied_count,
    ];
}




/**
 * 十五、筛选表单 action 地址。
 */
function pfl_get_product_filter_action(): string
{
    $current = pfl_get_current_product_category();

    if ($current) {
        $url = get_term_link($current);

        if (! is_wp_error($url)) {
            return $url;
        }
    }

    $archive_url = get_post_type_archive_link('product');

    return $archive_url ?: home_url('/');
}


/**
 * 十六、获取筛选分页需要保留的、已经通过白名单验证的参数。
 */
function pfl_get_pagination_filter_args(
    ?array $source = null,
    ?array $active_keys = null
): array {
    $source = null === $source ? $_GET : $source;
    $active_keys = null === $active_keys ? pfl_get_active_filter_keys() : pfl_sanitize_filter_keys($active_keys);
    $schema = pfl_get_product_filter_schema();
    $args = [];

    foreach ($active_keys as $key) {
        if (! isset($schema[$key])) { continue; }

        if ('taxonomy' === $schema[$key]['type']) {
            $values = pfl_get_filter_values($key, $source, $active_keys);
            if (! empty($values)) { $args[$key] = $values; }
        } else {
            $value = pfl_get_filter_value($key, $source, $active_keys);
            if ($value) { $args[$key] = $value; }
        }
    }

    $sort = pfl_get_filter_value('sort', $source, $active_keys);
    if ($sort) { $args['sort'] = $sort; }
    return $args;
}



/**
 * 十七、获得产品筛选上下文的基础地址。
 */
function pfl_get_product_context_url(
    string $context = 'archive',
    int $term_id = 0
): string {
    if ('taxonomy' === $context && $term_id > 0) {
        $term = get_term($term_id, 'product_category');

        if ($term instanceof WP_Term) {
            $url = get_term_link($term);

            if (! is_wp_error($url)) {
                return $url;
            }
        }
    }

    $archive_url = get_post_type_archive_link('product');

    return $archive_url ?: home_url('/');
}


/**
 * 生成可以复制、刷新和前进后退的筛选状态 URL。
 */
function pfl_build_product_state_url(
    string $base_url,
    array $source,
    int $paged = 1,
    ?array $active_keys = null
): string {
    $args = pfl_get_pagination_filter_args($source, $active_keys);
    if ($paged > 1) { $args['paged'] = $paged; }
    return empty($args) ? $base_url : add_query_arg($args, $base_url);
}
