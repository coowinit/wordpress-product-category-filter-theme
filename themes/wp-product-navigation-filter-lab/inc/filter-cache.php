<?php
/**
 * 筛选缓存模块
 *
 * 管理 Facet transient、缓存版本以及产品和属性变化后的自动失效。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 九、联动筛选计数与缓存。
 *
 * 计数规则：
 * 1. 计算某一筛选组时，暂时排除该组自身条件；
 * 2. 保留产品分类和其他筛选组条件；
 * 3. taxonomy 的 IN 组显示选择该项后的结果数；
 * 4. taxonomy 的 AND 组显示加入该项后同时满足全部条件的结果数；
 * 5. range 组显示切换到该区间后的结果数；
 * 6. 数量为 0 且未被选中的选项自动禁用。
 */

function pfl_get_facet_cache_version(): int
{
    $version = (int) get_option('pfl_facet_cache_version', 1);

    if ($version < 1) {
        $version = 1;
        update_option('pfl_facet_cache_version', $version, false);
    }

    return $version;
}


function pfl_bump_facet_cache_version(): void
{
    update_option(
        'pfl_facet_cache_version',
        pfl_get_facet_cache_version() + 1,
        false
    );
}


function pfl_normalize_facet_source(
    array $source,
    array $active_keys
): array {
    $state = pfl_get_pagination_filter_args($source, $active_keys);
    unset($state['sort']);

    foreach ($state as &$value) {
        if (is_array($value)) {
            $value = array_values(array_unique(array_map('strval', $value)));
            sort($value, SORT_NATURAL);
        }
    }
    unset($value);

    ksort($state);

    return $state;
}


function pfl_get_facet_cache_key(
    array $source,
    int $term_id,
    array $active_keys
): string {
    $payload = [
        'version' => pfl_get_facet_cache_version(),
        'term_id' => $term_id,
        'keys'    => array_values($active_keys),
        'state'   => pfl_normalize_facet_source($source, $active_keys),
    ];

    return 'pfl_facets_' . md5(wp_json_encode($payload));
}




/**
 * 产品、产品属性和产品分类变化后提升缓存版本。
 *
 * 使用版本号而不是遍历删除所有 transient，可以避免昂贵的数据库扫描。
 */
function pfl_clear_facets_after_product_save(int $post_id): void
{
    if (
        wp_is_post_revision($post_id)
        || wp_is_post_autosave($post_id)
        || 'product' !== get_post_type($post_id)
    ) {
        return;
    }

    pfl_bump_facet_cache_version();
}
add_action('save_post_product', 'pfl_clear_facets_after_product_save', 100);


function pfl_clear_facets_after_term_assignment(
    int $object_id,
    $terms,
    array $tt_ids,
    string $taxonomy
): void {
    if ('product' !== get_post_type($object_id)) {
        return;
    }

    $relevant_taxonomies = array_merge(
        ['product_category'],
        array_values(
            array_map(
                static function (array $filter): string {
                    return (string) ($filter['taxonomy'] ?? '');
                },
                pfl_get_taxonomy_filter_config()
            )
        )
    );

    if (in_array($taxonomy, $relevant_taxonomies, true)) {
        pfl_bump_facet_cache_version();
    }
}
add_action(
    'set_object_terms',
    'pfl_clear_facets_after_term_assignment',
    100,
    4
);


function pfl_get_relevant_product_taxonomies(): array
{
    return array_values(
        array_unique(
            array_merge(
                ['product_category'],
                array_map(
                    static function (array $filter): string {
                        return (string) ($filter['taxonomy'] ?? '');
                    },
                    pfl_get_taxonomy_filter_config()
                )
            )
        )
    );
}


function pfl_clear_facets_after_term_change(
    int $term_id,
    int $tt_id = 0,
    string $taxonomy = ''
): void {
    if (in_array($taxonomy, pfl_get_relevant_product_taxonomies(), true)) {
        pfl_bump_facet_cache_version();
    }
}
add_action('created_term', 'pfl_clear_facets_after_term_change', 100, 3);
add_action('edited_term', 'pfl_clear_facets_after_term_change', 100, 3);
add_action('delete_term', 'pfl_clear_facets_after_term_change', 100, 3);


function pfl_clear_facets_after_product_delete(int $post_id): void
{
    if ('product' === get_post_type($post_id)) {
        pfl_bump_facet_cache_version();
    }
}
add_action('before_delete_post', 'pfl_clear_facets_after_product_delete', 100);
add_action('trashed_post', 'pfl_clear_facets_after_product_delete', 100);
add_action('untrashed_post', 'pfl_clear_facets_after_product_delete', 100);
