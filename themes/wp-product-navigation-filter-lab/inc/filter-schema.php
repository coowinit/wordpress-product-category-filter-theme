<?php
/**
 * 筛选配置模块
 *
 * 定义筛选 Schema、分类专属方案、继承规则、后台配置与可用选项。
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 七、筛选配置。
 *
 * 将筛选字段、分类法和运算符集中配置，后续增加筛选项时，
 * 不需要在模板和查询逻辑中重复编写大量判断。
 */
function pfl_get_product_filter_schema(): array
{
    return [
        'brand' => [
            'label' => '产品品牌', 'type' => 'taxonomy',
            'taxonomy' => 'product_brand', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'voltage' => [
            'label' => '产品电压', 'type' => 'taxonomy',
            'taxonomy' => 'product_voltage', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'feature' => [
            'label' => '功能特点', 'type' => 'taxonomy',
            'taxonomy' => 'product_feature', 'operator' => 'AND', 'input' => 'checkbox',
        ],
        'material' => [
            'label' => '产品材质', 'type' => 'taxonomy',
            'taxonomy' => 'product_material', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'application' => [
            'label' => '应用行业', 'type' => 'taxonomy',
            'taxonomy' => 'product_application', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'automation' => [
            'label' => '自动化程度', 'type' => 'taxonomy',
            'taxonomy' => 'product_automation', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'installation' => [
            'label' => '安装方式', 'type' => 'taxonomy',
            'taxonomy' => 'product_installation', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'output_type' => [
            'label' => '输出类型', 'type' => 'taxonomy',
            'taxonomy' => 'product_output_type', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'protection' => [
            'label' => '防护等级', 'type' => 'taxonomy',
            'taxonomy' => 'product_protection', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'packaging_type' => [
            'label' => '包装方式', 'type' => 'taxonomy',
            'taxonomy' => 'product_packaging_type', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'conveyor_type' => [
            'label' => '输送方式', 'type' => 'taxonomy',
            'taxonomy' => 'product_conveyor_type', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'detection_type' => [
            'label' => '检测方式', 'type' => 'taxonomy',
            'taxonomy' => 'product_detection_type', 'operator' => 'IN', 'input' => 'checkbox',
        ],
        'price_range' => [
            'label' => '价格范围', 'type' => 'range', 'meta_key' => 'product_price',
            'options' => [
                'under-10000' => ['label' => '1 万以下', 'value' => 10000, 'compare' => '<'],
                '10000-30000' => ['label' => '1 万～3 万', 'value' => [10000, 30000], 'compare' => 'BETWEEN'],
                '30000-50000' => ['label' => '3 万～5 万', 'value' => [30000, 50000], 'compare' => 'BETWEEN'],
                'over-50000' => ['label' => '5 万以上', 'value' => 50000, 'compare' => '>='],
            ],
        ],
        'power_range' => [
            'label' => '功率范围', 'type' => 'range', 'meta_key' => 'product_power',
            'options' => [
                'under-3' => ['label' => '3kW 以下', 'value' => 3, 'compare' => '<'],
                '3-5' => ['label' => '3～5kW', 'value' => [3, 5], 'compare' => 'BETWEEN'],
                '5-10' => ['label' => '5～10kW', 'value' => [5, 10], 'compare' => 'BETWEEN'],
                'over-10' => ['label' => '10kW 以上', 'value' => 10, 'compare' => '>='],
            ],
        ],
        'rpm_range' => [
            'label' => '转速范围', 'type' => 'range', 'meta_key' => 'product_rpm',
            'options' => [
                'under-1000' => ['label' => '1000rpm 以下', 'value' => 1000, 'compare' => '<'],
                '1000-2000' => ['label' => '1000～2000rpm', 'value' => [1000, 2000], 'compare' => 'BETWEEN'],
                'over-2000' => ['label' => '2000rpm 以上', 'value' => 2000, 'compare' => '>='],
            ],
        ],
        'width_range' => [
            'label' => '设备宽度', 'type' => 'range', 'meta_key' => 'product_width',
            'options' => [
                'under-600' => ['label' => '600mm 以下', 'value' => 600, 'compare' => '<'],
                '600-1000' => ['label' => '600～1000mm', 'value' => [600, 1000], 'compare' => 'BETWEEN'],
                'over-1000' => ['label' => '1000mm 以上', 'value' => 1000, 'compare' => '>='],
            ],
        ],
        'distance_range' => [
            'label' => '检测距离', 'type' => 'range', 'meta_key' => 'product_detection_distance',
            'options' => [
                'under-50' => ['label' => '50mm 以下', 'value' => 50, 'compare' => '<'],
                '50-200' => ['label' => '50～200mm', 'value' => [50, 200], 'compare' => 'BETWEEN'],
                'over-200' => ['label' => '200mm 以上', 'value' => 200, 'compare' => '>='],
            ],
        ],
        'speed_range' => [
            'label' => '运行速度', 'type' => 'range', 'meta_key' => 'product_speed',
            'options' => [
                'under-10' => ['label' => '10m/min 以下', 'value' => 10, 'compare' => '<'],
                '10-30' => ['label' => '10～30m/min', 'value' => [10, 30], 'compare' => 'BETWEEN'],
                'over-30' => ['label' => '30m/min 以上', 'value' => 30, 'compare' => '>='],
            ],
        ],
    ];
}


/**
 * v1.6.0 筛选组界面配置。
 *
 * 查询规则仍由 pfl_get_product_filter_schema() 管理；本函数只负责
 * 展开状态、首屏显示数量和组内搜索等前端表现，避免将业务查询与 UI 混在一起。
 */
function pfl_get_filter_ui_config(string $key, array $filter = []): array
{
    $is_taxonomy = 'taxonomy' === ($filter['type'] ?? '');

    $config = [
        'initial_limit'   => $is_taxonomy ? 3 : 99,
        'searchable'      => $is_taxonomy,
        'search_threshold'=> 4,
        'default_open'    => in_array($key, ['brand', 'voltage'], true),
    ];

    $overrides = [
        'brand'       => ['initial_limit' => 3, 'searchable' => true, 'search_threshold' => 4, 'default_open' => true],
        'application' => ['initial_limit' => 3, 'searchable' => true, 'search_threshold' => 4],
        'feature'     => ['initial_limit' => 4, 'searchable' => true, 'search_threshold' => 5],
        'material'    => ['initial_limit' => 3, 'searchable' => true, 'search_threshold' => 4],
    ];

    if (isset($overrides[$key])) {
        $config = array_merge($config, $overrides[$key]);
    }

    /**
     * 允许子主题或插件调整筛选组 UI 配置。
     *
     * @param array  $config 当前配置。
     * @param string $key    Schema 键。
     * @param array  $filter Schema 定义。
     */
    return apply_filters('pfl_filter_ui_config', $config, $key, $filter);
}

function pfl_get_taxonomy_filter_config(): array
{
    return array_filter(
        pfl_get_product_filter_schema(),
        static function (array $filter): bool {
            return 'taxonomy' === ($filter['type'] ?? '');
        }
    );
}


function pfl_get_range_filter_config(): array
{
    return array_filter(
        pfl_get_product_filter_schema(),
        static function (array $filter): bool {
            return 'range' === ($filter['type'] ?? '');
        }
    );
}


function pfl_get_sort_options(): array
{
    return [
        ''           => '最新发布',
        'price-asc'  => '价格从低到高',
        'price-desc' => '价格从高到低',
        'title'      => '标题排序',
    ];
}

/**
 * 动态筛选方案：默认字段、分类继承与后台 term meta。
 */
function pfl_get_default_filter_keys(): array
{
    return [
        'brand', 'voltage', 'feature', 'material', 'application',
        'price_range', 'power_range',
    ];
}

function pfl_sanitize_filter_keys($keys): array
{
    $schema = pfl_get_product_filter_schema();
    $clean = [];

    foreach ((array) $keys as $key) {
        $key = sanitize_key((string) $key);

        if (isset($schema[$key]) && ! in_array($key, $clean, true)) {
            $clean[] = $key;
        }
    }

    return $clean;
}

function pfl_register_product_category_meta(): void
{
    register_term_meta(
        'product_category',
        'pfl_filter_mode',
        [
            'type' => 'string', 'single' => true, 'show_in_rest' => false,
            'sanitize_callback' => static function ($value): string {
                return 'custom' === sanitize_key((string) $value) ? 'custom' : 'inherit';
            },
            'auth_callback' => static function (): bool {
                return current_user_can('manage_categories');
            },
        ]
    );

    register_term_meta(
        'product_category',
        'pfl_filter_groups',
        [
            'type' => 'array', 'single' => true, 'show_in_rest' => false,
            'sanitize_callback' => 'pfl_sanitize_filter_keys',
            'auth_callback' => static function (): bool {
                return current_user_can('manage_categories');
            },
        ]
    );
}
add_action('init', 'pfl_register_product_category_meta');

function pfl_resolve_filter_profile(?WP_Term $term = null): array
{
    if (! $term) {
        $term = pfl_get_current_product_category();
    }

    if (! $term) {
        return [
            'keys' => pfl_get_default_filter_keys(),
            'source_term' => null,
            'source_label' => '全局默认方案',
            'inherited' => false,
        ];
    }

    $index = pfl_get_product_category_index();
    $cursor = $term;
    $guard = 0;

    while ($cursor instanceof WP_Term && $guard < 50) {
        $mode = get_term_meta($cursor->term_id, 'pfl_filter_mode', true);

        if ('custom' === $mode) {
            return [
                'keys' => pfl_sanitize_filter_keys(
                    get_term_meta($cursor->term_id, 'pfl_filter_groups', true)
                ),
                'source_term' => $cursor,
                'source_label' => (int) $cursor->term_id === (int) $term->term_id
                    ? '当前分类专属方案'
                    : '继承自“' . $cursor->name . '”',
                'inherited' => (int) $cursor->term_id !== (int) $term->term_id,
            ];
        }

        $parent_id = (int) $cursor->parent;
        $cursor = $parent_id > 0 && isset($index['by_id'][$parent_id])
            ? $index['by_id'][$parent_id]
            : null;
        $guard++;
    }

    return [
        'keys' => pfl_get_default_filter_keys(),
        'source_term' => null,
        'source_label' => '继承全局默认方案',
        'inherited' => true,
    ];
}

function pfl_get_active_filter_keys(?WP_Term $term = null): array
{
    $profile = pfl_resolve_filter_profile($term);
    return $profile['keys'];
}

function pfl_get_query_product_category(WP_Query $query): ?WP_Term
{
    if (! $query->is_tax('product_category')) {
        return null;
    }

    $term = $query->get_queried_object();

    if ($term instanceof WP_Term && 'product_category' === $term->taxonomy) {
        return $term;
    }

    $slug = $query->get('product_category');
    $term = $slug ? get_term_by('slug', (string) $slug, 'product_category') : false;

    return $term instanceof WP_Term ? $term : null;
}

/**
 * 分类后台：筛选方案设置与拖拽排序。
 */
function pfl_filter_group_admin_list(array $selected): void
{
    $schema = pfl_get_product_filter_schema();
    $ordered = array_values(array_unique(array_merge($selected, array_keys($schema))));
    ?>
    <ul id="pfl-filter-group-sortable" class="pfl-filter-group-sortable">
        <?php foreach ($ordered as $key) : ?>
            <?php if (! isset($schema[$key])) { continue; } ?>
            <li data-filter-key="<?php echo esc_attr($key); ?>">
                <span class="dashicons dashicons-move" aria-hidden="true"></span>
                <label>
                    <input
                        type="checkbox"
                        name="pfl_filter_groups[]"
                        value="<?php echo esc_attr($key); ?>"
                        <?php checked(in_array($key, $selected, true)); ?>
                    >
                    <strong><?php echo esc_html($schema[$key]['label']); ?></strong>
                    <code><?php echo esc_html($key); ?></code>
                </label>
            </li>
        <?php endforeach; ?>
    </ul>
    <input
        id="pfl-filter-order"
        name="pfl_filter_order"
        type="hidden"
        value="<?php echo esc_attr(implode(',', $ordered)); ?>"
    >
    <?php
}

function pfl_product_category_add_filter_fields(): void
{
    $selected = pfl_get_default_filter_keys();
    wp_nonce_field('pfl_save_category_filters', 'pfl_category_filter_nonce');
    ?>
    <div class="form-field pfl-filter-config-field">
        <label>筛选方案</label>
        <label><input type="radio" name="pfl_filter_mode" value="inherit" checked> 继承父分类或全局默认方案</label><br>
        <label><input type="radio" name="pfl_filter_mode" value="custom"> 使用当前分类专属方案</label>
        <p class="description">切换为专属方案后，可勾选并拖动下方筛选组。</p>
        <?php pfl_filter_group_admin_list($selected); ?>
    </div>
    <?php
}
add_action('product_category_add_form_fields', 'pfl_product_category_add_filter_fields');

function pfl_product_category_edit_filter_fields(WP_Term $term): void
{
    $mode = get_term_meta($term->term_id, 'pfl_filter_mode', true);
    $mode = 'custom' === $mode ? 'custom' : 'inherit';
    $selected = pfl_sanitize_filter_keys(
        get_term_meta($term->term_id, 'pfl_filter_groups', true)
    );

    if (empty($selected)) {
        $selected = pfl_get_default_filter_keys();
    }

    wp_nonce_field('pfl_save_category_filters', 'pfl_category_filter_nonce');
    ?>
    <tr class="form-field pfl-filter-config-field">
        <th scope="row"><label>筛选方案</label></th>
        <td>
            <label><input type="radio" name="pfl_filter_mode" value="inherit" <?php checked($mode, 'inherit'); ?>> 继承父分类或全局默认方案</label><br>
            <label><input type="radio" name="pfl_filter_mode" value="custom" <?php checked($mode, 'custom'); ?>> 使用当前分类专属方案</label>
            <p class="description">专属方案中的勾选状态决定显示哪些筛选组，拖动顺序决定前台排列顺序。</p>
            <?php pfl_filter_group_admin_list($selected); ?>
        </td>
    </tr>
    <?php
}
add_action('product_category_edit_form_fields', 'pfl_product_category_edit_filter_fields');

function pfl_save_product_category_filter_fields(int $term_id): void
{
    if (
        ! isset($_POST['pfl_category_filter_nonce'])
        || ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['pfl_category_filter_nonce'])),
            'pfl_save_category_filters'
        )
        || ! current_user_can('manage_categories')
    ) {
        return;
    }

    $mode = isset($_POST['pfl_filter_mode'])
        && 'custom' === sanitize_key(wp_unslash($_POST['pfl_filter_mode']))
        ? 'custom'
        : 'inherit';

    $selected = isset($_POST['pfl_filter_groups'])
        ? pfl_sanitize_filter_keys(wp_unslash($_POST['pfl_filter_groups']))
        : [];

    $order = isset($_POST['pfl_filter_order'])
        ? pfl_sanitize_filter_keys(
            explode(',', sanitize_text_field(wp_unslash($_POST['pfl_filter_order'])))
        )
        : array_keys(pfl_get_product_filter_schema());

    $selected = array_values(
        array_filter(
            $order,
            static function (string $key) use ($selected): bool {
                return in_array($key, $selected, true);
            }
        )
    );

    update_term_meta($term_id, 'pfl_filter_mode', $mode);
    update_term_meta($term_id, 'pfl_filter_groups', $selected);
}
add_action('created_product_category', 'pfl_save_product_category_filter_fields');
add_action('edited_product_category', 'pfl_save_product_category_filter_fields');

function pfl_product_category_admin_assets(string $hook): void
{
    $screen = get_current_screen();

    if (
        ! $screen
        || 'product_category' !== $screen->taxonomy
        || ! in_array($hook, ['edit-tags.php', 'term.php'], true)
    ) {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');

    $script = <<<'JS'
    jQuery(function ($) {
        const $list = $('#pfl-filter-group-sortable');
        const $order = $('#pfl-filter-order');

        function syncOrder() {
            const keys = $list.children('[data-filter-key]').map(function () {
                return $(this).data('filter-key');
            }).get();
            $order.val(keys.join(','));
        }

        if ($list.length) {
            $list.sortable({
                handle: '.dashicons-move',
                update: syncOrder
            });
            syncOrder();
        }
    });
    JS;

    wp_add_inline_script('jquery-ui-sortable', $script);
}
add_action('admin_enqueue_scripts', 'pfl_product_category_admin_assets');

function pfl_product_category_admin_styles(): void
{
    $screen = get_current_screen();

    if (! $screen || 'product_category' !== $screen->taxonomy) {
        return;
    }
    ?>
    <style>
        .pfl-filter-group-sortable { max-width: 720px; margin: 14px 0 0; }
        .pfl-filter-group-sortable li { display:flex; align-items:center; gap:8px; margin:0 0 6px; padding:9px 12px; border:1px solid #dcdcde; border-radius:6px; background:#fff; }
        .pfl-filter-group-sortable .dashicons-move { cursor:move; color:#646970; }
        .pfl-filter-group-sortable label { display:flex; align-items:center; gap:8px; width:100%; }
        .pfl-filter-group-sortable code { margin-left:auto; }
    </style>
    <?php
}
add_action('admin_head-edit-tags.php', 'pfl_product_category_admin_styles');
add_action('admin_head-term.php', 'pfl_product_category_admin_styles');

function pfl_product_category_columns(array $columns): array
{
    $columns['pfl_filter_profile'] = '筛选方案';
    return $columns;
}
add_filter('manage_edit-product_category_columns', 'pfl_product_category_columns');

function pfl_product_category_column_content(string $content, string $column, int $term_id): string
{
    if ('pfl_filter_profile' !== $column) {
        return $content;
    }

    $mode = get_term_meta($term_id, 'pfl_filter_mode', true);

    if ('custom' !== $mode) {
        return '继承';
    }

    $schema = pfl_get_product_filter_schema();
    $labels = [];

    foreach (pfl_sanitize_filter_keys(get_term_meta($term_id, 'pfl_filter_groups', true)) as $key) {
        if (isset($schema[$key])) {
            $labels[] = $schema[$key]['label'];
        }
    }

    return empty($labels) ? '专属：无筛选组' : esc_html(implode('、', $labels));
}
add_filter('manage_product_category_custom_column', 'pfl_product_category_column_content', 10, 3);

/**
 * 当前分类范围中的产品与可用筛选项。
 */
function pfl_get_context_product_ids(int $term_id = 0): array
{
    static $cache = [];

    if (isset($cache[$term_id])) {
        return $cache[$term_id];
    }

    $args = [
        'post_type' => 'product', 'post_status' => 'publish',
        'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true,
        'orderby' => 'ID', 'order' => 'ASC',
    ];

    if ($term_id > 0) {
        $args['tax_query'] = [[
            'taxonomy' => 'product_category', 'field' => 'term_id',
            'terms' => [$term_id], 'include_children' => true,
        ]];
    }

    $ids = get_posts($args);
    $cache[$term_id] = array_map('intval', $ids);
    return $cache[$term_id];
}

function pfl_get_filter_term_options(string $key, int $term_id = 0): array
{
    static $cache = [];
    $cache_key = $key . ':' . $term_id;

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $schema = pfl_get_product_filter_schema();

    if (! isset($schema[$key]) || 'taxonomy' !== $schema[$key]['type']) {
        return [];
    }

    $taxonomy = $schema[$key]['taxonomy'];

    if ($term_id <= 0) {
        $terms = get_terms([
            'taxonomy' => $taxonomy, 'hide_empty' => true,
            'orderby' => 'name', 'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $cache[$cache_key] = array_map(
            static function (WP_Term $term): array {
                return ['term' => $term, 'count' => (int) $term->count];
            },
            $terms
        );
        return $cache[$cache_key];
    }

    $product_ids = pfl_get_context_product_ids($term_id);

    if (empty($product_ids)) {
        return [];
    }

    $relations = wp_get_object_terms(
        $product_ids,
        $taxonomy,
        ['fields' => 'all_with_object_id']
    );

    if (is_wp_error($relations)) {
        return [];
    }

    $terms = [];
    $counts = [];

    foreach ($relations as $term) {
        $id = (int) $term->term_id;
        $terms[$id] = $term;
        $counts[$id] = ($counts[$id] ?? 0) + 1;
    }

    uasort(
        $terms,
        static function (WP_Term $a, WP_Term $b): int {
            return strnatcasecmp($a->name, $b->name);
        }
    );

    $options = [];
    foreach ($terms as $id => $term) {
        $options[] = ['term' => $term, 'count' => (int) ($counts[$id] ?? 0)];
    }

    $cache[$cache_key] = $options;
    return $options;
}

function pfl_range_filter_has_values(string $key, int $term_id = 0): bool
{
    static $cache = [];
    $cache_key = $key . ':' . $term_id;

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $schema = pfl_get_product_filter_schema();

    if (! isset($schema[$key]) || 'range' !== $schema[$key]['type']) {
        return false;
    }

    $args = [
        'post_type' => 'product', 'post_status' => 'publish',
        'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true,
        'meta_query' => [[
            'key' => $schema[$key]['meta_key'], 'compare' => 'EXISTS',
        ]],
    ];

    if ($term_id > 0) {
        $args['tax_query'] = [[
            'taxonomy' => 'product_category', 'field' => 'term_id',
            'terms' => [$term_id], 'include_children' => true,
        ]];
    }

    $cache[$cache_key] = ! empty(get_posts($args));
    return $cache[$cache_key];
}
