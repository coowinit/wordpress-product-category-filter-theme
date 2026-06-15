<?php
/**
 * 筛选联动计数模块
 *
 * 计算 IN、AND 和数值区间候选数量，并生成零结果禁用状态。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 返回满足“当前分类 + 除指定组之外其他筛选条件”的产品 ID。
 */
function pfl_get_faceted_base_product_ids(
    array $source,
    int $term_id,
    array $active_keys,
    string $excluded_key
): array {
    $query_keys = array_values(
        array_diff($active_keys, [$excluded_key])
    );

    $parts = pfl_get_product_query_parts($source, $query_keys);

    $query_args = [
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    $tax_query = ['relation' => 'AND'];

    if ($term_id > 0) {
        $tax_query[] = [
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => [$term_id],
            'include_children' => true,
        ];
    }

    foreach ($parts['tax_clauses'] as $clause) {
        $tax_query[] = $clause;
    }

    if (count($tax_query) > 1) {
        $query_args['tax_query'] = $tax_query;
    }

    if (! empty($parts['meta_clauses'])) {
        $query_args['meta_query'] = array_merge(
            ['relation' => 'AND'],
            $parts['meta_clauses']
        );
    }

    return array_map('intval', get_posts($query_args));
}


/**
 * 判断一个数值是否位于某个 range Schema 选项中。
 */
function pfl_numeric_value_matches_filter_option(
    float $number,
    array $option
): bool {
    $compare = (string) ($option['compare'] ?? '=');
    $value   = $option['value'] ?? 0;

    switch ($compare) {
        case '<':
            return $number < (float) $value;

        case '<=':
            return $number <= (float) $value;

        case '>':
            return $number > (float) $value;

        case '>=':
            return $number >= (float) $value;

        case 'BETWEEN':
            if (! is_array($value) || count($value) < 2) {
                return false;
            }

            return (
                $number >= (float) $value[0]
                && $number <= (float) $value[1]
            );

        default:
            return $number === (float) $value;
    }
}


/**
 * 计算当前筛选状态下所有可见筛选组的联动数量。
 *
 * 返回：
 * [
 *   'facets' => [
 *      'brand' => [
 *          'type' => 'taxonomy',
 *          'options' => [
 *              'term-slug' => ['count' => 3, 'disabled' => false]
 *          ]
 *      ]
 *   ],
 *   'meta' => [
 *      'cacheHit' => false,
 *      'elapsedMs' => 12.5
 *   ]
 * ]
 */
function pfl_get_faceted_filter_state(
    array $source,
    ?WP_Term $term = null,
    ?array $active_keys = null
): array {
    static $runtime_cache = [];

    $started     = microtime(true);
    $term_id     = $term ? (int) $term->term_id : 0;
    $active_keys = null === $active_keys
        ? pfl_get_active_filter_keys($term)
        : pfl_sanitize_filter_keys($active_keys);

    $cache_key = pfl_get_facet_cache_key(
        $source,
        $term_id,
        $active_keys
    );

    if (isset($runtime_cache[$cache_key])) {
        return [
            'facets' => $runtime_cache[$cache_key],
            'meta'   => [
                'cacheHit' => true,
                'elapsedMs' => round((microtime(true) - $started) * 1000, 2),
            ],
        ];
    }

    $cached = get_transient($cache_key);

    if (is_array($cached)) {
        $runtime_cache[$cache_key] = $cached;

        return [
            'facets' => $cached,
            'meta'   => [
                'cacheHit' => true,
                'elapsedMs' => round((microtime(true) - $started) * 1000, 2),
            ],
        ];
    }

    $schema = pfl_get_product_filter_schema();
    $facets = [];

    foreach ($active_keys as $key) {
        if (! isset($schema[$key])) {
            continue;
        }

        $filter   = $schema[$key];
        $base_ids = pfl_get_faceted_base_product_ids(
            $source,
            $term_id,
            $active_keys,
            $key
        );

        if ('taxonomy' === $filter['type']) {
            $context_options = pfl_get_filter_term_options($key, $term_id);

            if (empty($context_options)) {
                continue;
            }

            $object_ids_by_slug = [];

            if (! empty($base_ids)) {
                $relations = wp_get_object_terms(
                    $base_ids,
                    $filter['taxonomy'],
                    ['fields' => 'all_with_object_id']
                );

                if (! is_wp_error($relations)) {
                    foreach ($relations as $relation) {
                        $slug = (string) $relation->slug;
                        $object_ids_by_slug[$slug][] = (int) $relation->object_id;
                    }
                }
            }

            foreach ($object_ids_by_slug as &$ids) {
                $ids = array_values(array_unique(array_map('intval', $ids)));
            }
            unset($ids);

            $selected = pfl_get_filter_values(
                $key,
                $source,
                $active_keys
            );

            $facet_options = [];

            foreach ($context_options as $context_option) {
                $option_term = $context_option['term'];
                $slug        = (string) $option_term->slug;
                $count       = 0;

                if ('AND' === strtoupper((string) $filter['operator'])) {
                    $required = $selected;

                    if (! in_array($slug, $required, true)) {
                        $required[] = $slug;
                    }

                    $matching_ids = $base_ids;

                    foreach ($required as $required_slug) {
                        $matching_ids = array_values(
                            array_intersect(
                                $matching_ids,
                                $object_ids_by_slug[$required_slug] ?? []
                            )
                        );

                        if (empty($matching_ids)) {
                            break;
                        }
                    }

                    $count = count($matching_ids);
                } else {
                    $count = count($object_ids_by_slug[$slug] ?? []);
                }

                $is_selected = in_array($slug, $selected, true);

                $facet_options[$slug] = [
                    'count'    => $count,
                    'disabled' => (0 === $count && ! $is_selected),
                ];
            }

            $facets[$key] = [
                'type'    => 'taxonomy',
                'options' => $facet_options,
            ];
        } elseif ('range' === $filter['type']) {
            if (! pfl_range_filter_has_values($key, $term_id)) {
                continue;
            }

            $selected = pfl_get_filter_value(
                $key,
                $source,
                $active_keys
            );

            if (! empty($base_ids)) {
                update_meta_cache('post', $base_ids);
            }

            $facet_options = [];

            foreach ($filter['options'] as $value => $option) {
                $count = 0;

                foreach ($base_ids as $post_id) {
                    $raw_value = get_post_meta(
                        $post_id,
                        $filter['meta_key'],
                        true
                    );

                    if (
                        '' !== $raw_value
                        && is_numeric($raw_value)
                        && pfl_numeric_value_matches_filter_option(
                            (float) $raw_value,
                            $option
                        )
                    ) {
                        $count++;
                    }
                }

                $is_selected = ($selected === $value);

                $facet_options[$value] = [
                    'count'    => $count,
                    'disabled' => (0 === $count && ! $is_selected),
                ];
            }

            $facets[$key] = [
                'type'    => 'range',
                'options' => $facet_options,
            ];
        }
    }

    set_transient(
        $cache_key,
        $facets,
        10 * MINUTE_IN_SECONDS
    );

    $runtime_cache[$cache_key] = $facets;

    return [
        'facets' => $facets,
        'meta'   => [
            'cacheHit' => false,
            'elapsedMs' => round((microtime(true) - $started) * 1000, 2),
        ],
    ];
}
