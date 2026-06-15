<?php
/**
 * 产品导航筛选实验室主题核心功能。
 *
 * 本主题为了方便学习，将以下内容集中放在 functions.php：
 * 1. 自定义文章类型 product
 * 2. 产品层级分类 product_category
 * 3. 品牌、电压、功能分类法
 * 4. 产品价格、功率、型号自定义字段
 * 5. 配置驱动的动态产品属性与 AJAX 无刷新筛选
 * 6. 分类专属筛选、继承、历史记录与分页状态同步
 * 7. 产品分类内容、Canonical、Robots 与结构化数据
 * 8. 联动筛选计数、不可用选项与缓存失效机制
 * 9. 演示数据导入器
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 一、主题基础设置
 */
function pfl_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    add_theme_support(
        'html5',
        [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]
    );

    register_nav_menus(
        [
            'primary' => __('主导航', 'product-filter-lab'),
        ]
    );
}
add_action('after_setup_theme', 'pfl_theme_setup');


/**
 * 二、加载主题样式和产品页面资源
 */
function pfl_enqueue_assets(): void
{
    wp_enqueue_style(
        'pfl-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );

    $is_product_screen = (
        is_post_type_archive('product')
        || is_tax('product_category')
        || is_singular('product')
        || is_front_page()
    );

    if (! $is_product_screen) {
        return;
    }

    wp_enqueue_style(
        'pfl-product',
        get_theme_file_uri('/assets/css/product.css'),
        ['pfl-style'],
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script(
        'pfl-product-filter',
        get_theme_file_uri('/assets/js/product-filter.js'),
        [],
        wp_get_theme()->get('Version'),
        true
    );

    if (
        is_post_type_archive('product')
        || is_tax('product_category')
    ) {
        wp_localize_script(
            'pfl-product-filter',
            'pflProductFilter',
            [
                'ajaxUrl'  => admin_url('admin-ajax.php'),
                'action'   => 'pfl_filter_products',
                'nonce'    => wp_create_nonce('pfl_filter_products'),
                'debounce' => 320,
                'showPerformance' => (
                    current_user_can('manage_options')
                    && defined('WP_DEBUG')
                    && WP_DEBUG
                ),
                'messages' => [
                    'loading' => '正在更新产品结果与筛选计数……',
                    'error'   => 'AJAX 请求失败，正在切换为普通页面请求。',
                ],
            ]
        );
    }
}
add_action('wp_enqueue_scripts', 'pfl_enqueue_assets');


/**
 * 三、注册产品自定义文章类型
 */
function pfl_register_product_post_type(): void
{
    $labels = [
        'name'               => '产品',
        'singular_name'      => '产品',
        'menu_name'          => '产品中心',
        'add_new'            => '添加产品',
        'add_new_item'       => '添加新产品',
        'edit_item'          => '编辑产品',
        'new_item'           => '新产品',
        'view_item'          => '查看产品',
        'search_items'       => '搜索产品',
        'not_found'          => '没有找到产品',
        'not_found_in_trash' => '回收站中没有产品',
        'all_items'          => '全部产品',
        'archives'           => '产品归档',
    ];

    register_post_type(
        'product',
        [
            'labels'             => $labels,
            'public'             => true,
            'show_in_rest'       => true,
            'has_archive'        => 'products',
            'rewrite'            => [
                'slug'       => 'products',
                'with_front' => false,
            ],
            'menu_icon'           => 'dashicons-products',
            'supports'            => ['title', 'editor', 'excerpt', 'thumbnail'],
            'taxonomies'          => array_values(
                array_filter(
                    array_map(
                        static function (array $filter): string {
                            return 'taxonomy' === ($filter['type'] ?? '')
                                ? (string) ($filter['taxonomy'] ?? '')
                                : '';
                        },
                        pfl_get_product_filter_schema()
                    )
                )
            ),
            'show_in_nav_menus'   => true,
            'exclude_from_search' => false,
        ]
    );
}
add_action('init', 'pfl_register_product_post_type');


/**
 * 四、注册产品分类与筛选分类法
 */
function pfl_register_product_taxonomies(): void
{
    register_taxonomy(
        'product_category',
        ['product'],
        [
            'labels' => [
                'name'              => '产品分类',
                'singular_name'     => '产品分类',
                'search_items'      => '搜索产品分类',
                'all_items'         => '全部产品分类',
                'parent_item'       => '父级产品分类',
                'parent_item_colon' => '父级产品分类：',
                'edit_item'         => '编辑产品分类',
                'update_item'       => '更新产品分类',
                'add_new_item'      => '添加产品分类',
                'new_item_name'     => '新产品分类名称',
                'menu_name'         => '产品分类',
            ],
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => [
                'slug'         => 'product-category',
                'with_front'   => false,
                'hierarchical' => true,
            ],
        ]
    );

    $taxonomies = [
        'product_brand' => ['name' => '产品品牌', 'slug' => 'product-brand'],
        'product_voltage' => ['name' => '产品电压', 'slug' => 'product-voltage'],
        'product_feature' => ['name' => '功能特点', 'slug' => 'product-feature'],
        'product_material' => ['name' => '产品材质', 'slug' => 'product-material'],
        'product_application' => ['name' => '应用行业', 'slug' => 'product-application'],
        'product_automation' => ['name' => '自动化程度', 'slug' => 'product-automation'],
        'product_installation' => ['name' => '安装方式', 'slug' => 'product-installation'],
        'product_output_type' => ['name' => '输出类型', 'slug' => 'product-output-type'],
        'product_protection' => ['name' => '防护等级', 'slug' => 'product-protection'],
        'product_packaging_type' => ['name' => '包装方式', 'slug' => 'product-packaging-type'],
        'product_conveyor_type' => ['name' => '输送方式', 'slug' => 'product-conveyor-type'],
        'product_detection_type' => ['name' => '检测方式', 'slug' => 'product-detection-type'],
    ];

    foreach ($taxonomies as $taxonomy => $config) {
        register_taxonomy(
            $taxonomy,
            ['product'],
            [
                'labels' => [
                    'name'          => $config['name'],
                    'singular_name' => $config['name'],
                    'search_items'  => '搜索' . $config['name'],
                    'all_items'     => '全部' . $config['name'],
                    'edit_item'     => '编辑' . $config['name'],
                    'update_item'   => '更新' . $config['name'],
                    'add_new_item'  => '添加' . $config['name'],
                    'new_item_name' => '新' . $config['name'] . '名称',
                    'menu_name'     => $config['name'],
                ],
                'public'            => true,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => false,
                'show_in_rest'      => true,
                'rewrite'           => [
                    'slug'       => $config['slug'],
                    'with_front' => false,
                ],
            ]
        );
    }
}
add_action('init', 'pfl_register_product_taxonomies');


/**
 * 五、注册产品自定义字段元数据
 */
function pfl_get_product_meta_field_config(): array
{
    return [
        'product_model' => [
            'label' => '产品型号',
            'type' => 'text',
            'placeholder' => '例如：VP-500',
            'unit' => '',
        ],
        'product_price' => [
            'label' => '产品价格',
            'type' => 'number',
            'placeholder' => '例如：26800',
            'step' => '0.01',
            'unit' => '元',
        ],
        'product_power' => [
            'label' => '功率',
            'type' => 'number',
            'placeholder' => '例如：5.5',
            'step' => '0.1',
            'unit' => 'kW',
        ],
        'product_rpm' => [
            'label' => '转速',
            'type' => 'number',
            'placeholder' => '例如：1450',
            'step' => '1',
            'unit' => 'rpm',
        ],
        'product_width' => [
            'label' => '设备宽度',
            'type' => 'number',
            'placeholder' => '例如：800',
            'step' => '1',
            'unit' => 'mm',
        ],
        'product_detection_distance' => [
            'label' => '检测距离',
            'type' => 'number',
            'placeholder' => '例如：120',
            'step' => '0.1',
            'unit' => 'mm',
        ],
        'product_speed' => [
            'label' => '运行速度',
            'type' => 'number',
            'placeholder' => '例如：25',
            'step' => '0.1',
            'unit' => 'm/min',
        ],
    ];
}

function pfl_register_product_meta(): void
{
    foreach (pfl_get_product_meta_field_config() as $meta_key => $field) {
        register_post_meta(
            'product',
            $meta_key,
            [
                'type'              => 'number' === $field['type'] ? 'number' : 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'number' === $field['type']
                    ? static function ($value) {
                        return is_numeric($value) ? (float) $value : 0;
                    }
                    : 'sanitize_text_field',
                'auth_callback'     => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );
    }
}
add_action('init', 'pfl_register_product_meta');


/**
 * 六、产品参数元框
 */
function pfl_add_product_meta_box(): void
{
    add_meta_box(
        'pfl-product-details',
        '产品参数',
        'pfl_render_product_meta_box',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pfl_add_product_meta_box');


function pfl_render_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('pfl_save_product_meta', 'pfl_product_meta_nonce');
    ?>
    <table class="form-table">
        <?php foreach (pfl_get_product_meta_field_config() as $meta_key => $field) : ?>
            <?php $value = get_post_meta($post->ID, $meta_key, true); ?>
            <tr>
                <th>
                    <label for="<?php echo esc_attr($meta_key); ?>">
                        <?php echo esc_html($field['label']); ?>
                    </label>
                </th>
                <td>
                    <input
                        class="<?php echo 'text' === $field['type'] ? 'regular-text' : 'small-text'; ?>"
                        id="<?php echo esc_attr($meta_key); ?>"
                        name="<?php echo esc_attr($meta_key); ?>"
                        type="<?php echo esc_attr($field['type']); ?>"
                        <?php if ('number' === $field['type']) : ?>
                            min="0"
                            step="<?php echo esc_attr($field['step'] ?? '0.1'); ?>"
                        <?php endif; ?>
                        value="<?php echo esc_attr((string) $value); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                    >
                    <?php if (! empty($field['unit'])) : ?>
                        <span><?php echo esc_html($field['unit']); ?></span>
                    <?php endif; ?>
                    <?php if ('product_price' === $meta_key) : ?>
                        <p class="description">保存纯数字，用于范围筛选和数值排序。</p>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}


function pfl_save_product_meta(int $post_id): void
{
    if (
        ! isset($_POST['pfl_product_meta_nonce'])
        || ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['pfl_product_meta_nonce'])),
            'pfl_save_product_meta'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (pfl_get_product_meta_field_config() as $meta_key => $field) {
        if (! isset($_POST[$meta_key])) {
            continue;
        }

        $value = wp_unslash($_POST[$meta_key]);

        if ('' === $value) {
            delete_post_meta($post_id, $meta_key);
            continue;
        }

        $clean = 'number' === $field['type']
            ? (float) $value
            : sanitize_text_field($value);

        update_post_meta($post_id, $meta_key, $clean);
    }
}
add_action('save_post_product', 'pfl_save_product_meta');


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
    $active_keys = pfl_get_active_filter_keys($term);

    $query->set('post_type', 'product');
    $query->set('posts_per_page', 9);
    pfl_apply_product_query_parts($query, $_GET, $active_keys);
}
add_action('pre_get_posts', 'pfl_filter_product_main_query');


/**
 * 十三、一次取得全部产品分类并建立父子索引。
 *
 * 该索引同时用于导航层级、下级判断和面包屑，避免在模板循环中
 * 为每个分类重复执行 get_terms()，消除 N+1 查询。
 */
function pfl_get_product_category_index(): array
{
    static $index = null;

    if (null !== $index) {
        return $index;
    }

    $terms = get_terms(
        [
            'taxonomy'   => 'product_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]
    );

    if (is_wp_error($terms)) {
        $index = [
            'terms'     => [],
            'by_id'     => [],
            'by_parent' => [],
        ];

        return $index;
    }

    $by_id = [];
    $by_parent = [];

    foreach ($terms as $term) {
        $term_id = (int) $term->term_id;
        $parent  = (int) $term->parent;

        $by_id[$term_id] = $term;

        if (! isset($by_parent[$parent])) {
            $by_parent[$parent] = [];
        }

        $by_parent[$parent][] = $term;
    }

    $index = [
        'terms'     => $terms,
        'by_id'     => $by_id,
        'by_parent' => $by_parent,
    ];

    return $index;
}


function pfl_get_current_product_category(): ?WP_Term
{
    if (! is_tax('product_category')) {
        return null;
    }

    $term = get_queried_object();

    return (
        $term instanceof WP_Term
        && 'product_category' === $term->taxonomy
    ) ? $term : null;
}


/**
 * 取得一级分类到当前分类的完整路径。
 */
function pfl_get_current_product_category_path(): array
{
    $current = pfl_get_current_product_category();

    if (! $current) {
        return [];
    }

    $index = pfl_get_product_category_index();
    $path  = [$current];
    $parent_id = (int) $current->parent;
    $guard = 0;

    while ($parent_id && isset($index['by_id'][$parent_id]) && $guard < 50) {
        $parent = $index['by_id'][$parent_id];
        array_unshift($path, $parent);
        $parent_id = (int) $parent->parent;
        $guard++;
    }

    return $path;
}


/**
 * 获得当前产品分类导航的所有层级。
 */
function pfl_get_category_navigation_levels(): array
{
    $index = pfl_get_product_category_index();
    $root_terms = $index['by_parent'][0] ?? [];

    if (empty($root_terms)) {
        return [];
    }

    $current = pfl_get_current_product_category();

    if (! $current) {
        return [
            [
                'level'      => 1,
                'active_id'  => 0,
                'current_id' => 0,
                'terms'      => $root_terms,
            ],
        ];
    }

    $path = pfl_get_current_product_category_path();
    $path_ids = array_map(
        static function (WP_Term $term): int {
            return (int) $term->term_id;
        },
        $path
    );

    $levels = [
        [
            'level'      => 1,
            'active_id'  => $path_ids[0] ?? 0,
            'current_id' => (int) $current->term_id,
            'terms'      => $root_terms,
        ],
    ];

    foreach ($path_ids as $index_number => $path_term_id) {
        $children = $index['by_parent'][$path_term_id] ?? [];

        if (empty($children)) {
            break;
        }

        $levels[] = [
            'level'      => $index_number + 2,
            'active_id'  => $path_ids[$index_number + 1] ?? 0,
            'current_id' => (int) $current->term_id,
            'terms'      => $children,
        ];
    }

    return $levels;
}


function pfl_product_category_has_children(int $term_id): bool
{
    $index = pfl_get_product_category_index();

    return ! empty($index['by_parent'][$term_id]);
}


/**
 * 十四、产品归档面包屑数据。
 */
function pfl_get_product_breadcrumb_items(): array
{
    $archive_url = get_post_type_archive_link('product');

    $items = [
        [
            'label' => '首页',
            'url'   => home_url('/'),
        ],
        [
            'label' => '产品中心',
            'url'   => is_post_type_archive('product') ? '' : ($archive_url ?: ''),
        ],
    ];

    if (is_singular('product')) {
        $primary_term = pfl_get_product_primary_category(get_queried_object_id());

        if ($primary_term) {
            foreach (pfl_get_product_category_path_for_term($primary_term) as $term) {
                $term_url = get_term_link($term);

                $items[] = [
                    'label' => $term->name,
                    'url'   => is_wp_error($term_url) ? '' : $term_url,
                ];
            }
        }

        $items[] = [
            'label' => get_the_title(get_queried_object_id()),
            'url'   => '',
        ];

        return $items;
    }

    $path = pfl_get_current_product_category_path();
    $last_index = count($path) - 1;

    foreach ($path as $position => $term) {
        $url = '';

        if ($position !== $last_index) {
            $term_url = get_term_link($term);
            $url = is_wp_error($term_url) ? '' : $term_url;
        }

        $items[] = [
            'label' => $term->name,
            'url'   => $url,
        ];
    }

    return $items;
}


/**
 * 当前分类的返回上一级链接。
 */
function pfl_get_parent_product_category_data(): array
{
    $current = pfl_get_current_product_category();

    if (! $current) {
        return [];
    }

    if ((int) $current->parent > 0) {
        $index = pfl_get_product_category_index();
        $parent = $index['by_id'][(int) $current->parent] ?? null;

        if ($parent instanceof WP_Term) {
            $url = get_term_link($parent);

            if (! is_wp_error($url)) {
                return [
                    'label' => $parent->name,
                    'url'   => $url,
                ];
            }
        }
    }

    $archive_url = get_post_type_archive_link('product');

    return $archive_url ? [
        'label' => '产品中心',
        'url'   => $archive_url,
    ] : [];
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

    $active_keys = pfl_get_active_filter_keys($term);
    $paged = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
    $query_args = [
        'post_type' => 'product', 'post_status' => 'publish',
        'posts_per_page' => 9, 'paged' => $paged,
        'ignore_sticky_posts' => true,
    ];

    $parts = pfl_get_product_query_parts($source, $active_keys);
    $tax_query = ['relation' => 'AND'];

    if ('taxonomy' === $context && $term_id > 0) {
        $tax_query[] = [
            'taxonomy' => 'product_category', 'field' => 'term_id',
            'terms' => [$term_id], 'include_children' => true,
        ];
    }

    foreach ($parts['tax_clauses'] as $clause) { $tax_query[] = $clause; }
    if (count($tax_query) > 1) { $query_args['tax_query'] = $tax_query; }

    if (! empty($parts['meta_clauses'])) {
        $query_args['meta_query'] = array_merge(['relation' => 'AND'], $parts['meta_clauses']);
    }

    $query_args = array_merge($query_args, $parts['sort_args']);
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
        'appliedCount' => pfl_get_applied_filter_count($source, $active_keys),
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


/**
 * 十七、格式化产品价格。
 */
function pfl_format_product_price(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $price   = get_post_meta($post_id, 'product_price', true);

    if ('' === $price) {
        return '价格面议';
    }

    return '¥' . number_format_i18n((float) $price, 0);
}


/**
 * 十八、后台产品管理列。
 */
function pfl_product_admin_columns(array $columns): array
{
    return [
        'cb'               => $columns['cb'] ?? '<input type="checkbox">',
        'pfl_thumbnail'    => '图片',
        'title'            => '产品名称',
        'pfl_model'        => '型号',
        'pfl_category'     => '产品分类',
        'pfl_brand'        => '品牌',
        'pfl_price'        => '价格',
        'pfl_power'        => '功率',
        'date'             => $columns['date'] ?? '日期',
    ];
}
add_filter('manage_product_posts_columns', 'pfl_product_admin_columns');


function pfl_render_product_admin_column(string $column, int $post_id): void
{
    switch ($column) {
        case 'pfl_thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail(
                    $post_id,
                    [60, 60],
                    ['class' => 'pfl-admin-product-thumbnail']
                );
            } else {
                echo '<span class="pfl-admin-product-placeholder">无图</span>';
            }
            break;

        case 'pfl_model':
            echo esc_html(
                get_post_meta($post_id, 'product_model', true) ?: '—'
            );
            break;

        case 'pfl_category':
            $terms = get_the_term_list(
                $post_id,
                'product_category',
                '',
                '、'
            );
            echo $terms ? wp_kses_post($terms) : '—';
            break;

        case 'pfl_brand':
            $terms = get_the_term_list(
                $post_id,
                'product_brand',
                '',
                '、'
            );
            echo $terms ? wp_kses_post($terms) : '—';
            break;

        case 'pfl_price':
            echo esc_html(pfl_format_product_price($post_id));
            break;

        case 'pfl_power':
            $power = get_post_meta($post_id, 'product_power', true);
            echo esc_html('' !== $power ? $power . ' kW' : '—');
            break;
    }
}
add_action(
    'manage_product_posts_custom_column',
    'pfl_render_product_admin_column',
    10,
    2
);


function pfl_product_sortable_columns(array $columns): array
{
    $columns['pfl_model'] = 'product_model';
    $columns['pfl_price'] = 'product_price';
    $columns['pfl_power'] = 'product_power';

    return $columns;
}
add_filter(
    'manage_edit-product_sortable_columns',
    'pfl_product_sortable_columns'
);


function pfl_product_admin_sorting(WP_Query $query): void
{
    if (
        ! is_admin()
        || ! $query->is_main_query()
        || 'product' !== $query->get('post_type')
    ) {
        return;
    }

    switch ($query->get('orderby')) {
        case 'product_model':
            $query->set('meta_key', 'product_model');
            $query->set('orderby', 'meta_value');
            break;

        case 'product_price':
            $query->set('meta_key', 'product_price');
            $query->set('orderby', 'meta_value_num');
            break;

        case 'product_power':
            $query->set('meta_key', 'product_power');
            $query->set('orderby', 'meta_value_num');
            break;
    }
}
add_action('pre_get_posts', 'pfl_product_admin_sorting');


function pfl_product_admin_column_styles(): void
{
    $screen = get_current_screen();

    if (! $screen || 'edit-product' !== $screen->id) {
        return;
    }
    ?>
    <style>
        .column-pfl_thumbnail { width: 74px; }
        .column-pfl_model { width: 110px; }
        .column-pfl_price,
        .column-pfl_power { width: 100px; }
        .pfl-admin-product-thumbnail {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
        }
        .pfl-admin-product-placeholder {
            display: inline-grid;
            width: 52px;
            height: 52px;
            place-items: center;
            border-radius: 8px;
            color: #646970;
            background: #f0f0f1;
            font-size: 12px;
        }
    </style>
    <?php
}
add_action('admin_head-edit.php', 'pfl_product_admin_column_styles');



/**
 * 十四、产品分类内容与内置 SEO。
 *
 * 本模块用于学习分类图片、分类上下文内容、Canonical、Robots、
 * BreadcrumbList、CollectionPage 与 Product JSON-LD 的实现方式。
 */
function pfl_register_product_category_content_meta(): void
{
    $fields = [
        'pfl_category_image_id' => [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
        ],
        'pfl_category_top_content' => [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ],
        'pfl_category_bottom_content' => [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ],
        'pfl_category_seo_title' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'pfl_category_meta_description' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
        ],
    ];

    foreach ($fields as $meta_key => $field) {
        register_term_meta(
            'product_category',
            $meta_key,
            [
                'type'              => $field['type'],
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => $field['sanitize_callback'],
                'auth_callback'     => static function (): bool {
                    return current_user_can('manage_categories');
                },
            ]
        );
    }
}
add_action('init', 'pfl_register_product_category_content_meta', 12);


function pfl_get_product_category_content(?WP_Term $term = null): array
{
    if (! $term) {
        $term = pfl_get_current_product_category();
    }

    if (! $term) {
        return [
            'image_id'        => 0,
            'top_content'     => '',
            'bottom_content'  => '',
            'seo_title'       => '',
            'meta_description'=> '',
        ];
    }

    return [
        'image_id' => (int) get_term_meta(
            $term->term_id,
            'pfl_category_image_id',
            true
        ),
        'top_content' => (string) get_term_meta(
            $term->term_id,
            'pfl_category_top_content',
            true
        ),
        'bottom_content' => (string) get_term_meta(
            $term->term_id,
            'pfl_category_bottom_content',
            true
        ),
        'seo_title' => (string) get_term_meta(
            $term->term_id,
            'pfl_category_seo_title',
            true
        ),
        'meta_description' => (string) get_term_meta(
            $term->term_id,
            'pfl_category_meta_description',
            true
        ),
    ];
}


function pfl_render_category_image_control(int $image_id = 0): void
{
    $image_url = $image_id > 0
        ? wp_get_attachment_image_url($image_id, 'medium')
        : '';
    ?>
    <div class="pfl-category-image-control">
        <input
            class="pfl-category-image-id"
            name="pfl_category_image_id"
            type="hidden"
            value="<?php echo esc_attr((string) $image_id); ?>"
        >

        <div class="pfl-category-image-preview<?php echo $image_url ? ' has-image' : ''; ?>">
            <?php if ($image_url) : ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="">
            <?php else : ?>
                <span>尚未选择分类图片</span>
            <?php endif; ?>
        </div>

        <p>
            <button class="button pfl-category-image-select" type="button">
                选择分类图片
            </button>
            <button
                class="button-link-delete pfl-category-image-remove"
                type="button"
                <?php echo $image_url ? '' : 'hidden'; ?>
            >
                移除图片
            </button>
        </p>
    </div>
    <?php
}


function pfl_product_category_add_content_fields(): void
{
    wp_nonce_field('pfl_save_category_content', 'pfl_category_content_nonce');
    ?>
    <div class="form-field pfl-category-content-field">
        <label>分类图片</label>
        <?php pfl_render_category_image_control(); ?>
        <p class="description">用于分类页头部视觉区域和 CollectionPage 结构化数据。</p>
    </div>

    <div class="form-field pfl-category-content-field">
        <label for="pfl-category-top-content">分类顶部内容</label>
        <textarea id="pfl-category-top-content" name="pfl_category_top_content" rows="5"></textarea>
        <p class="description">显示在分类标题下方，支持安全 HTML。</p>
    </div>

    <div class="form-field pfl-category-content-field">
        <label for="pfl-category-bottom-content">分类底部内容</label>
        <textarea id="pfl-category-bottom-content" name="pfl_category_bottom_content" rows="7"></textarea>
        <p class="description">显示在产品结果之后，适合补充分类说明、应用范围和选型建议。</p>
    </div>

    <div class="form-field pfl-category-content-field">
        <label for="pfl-category-seo-title">SEO 标题</label>
        <input id="pfl-category-seo-title" name="pfl_category_seo_title" type="text" value="">
        <p class="description">留空时使用 WordPress 默认文档标题。</p>
    </div>

    <div class="form-field pfl-category-content-field">
        <label for="pfl-category-meta-description">Meta Description</label>
        <textarea id="pfl-category-meta-description" name="pfl_category_meta_description" rows="3" maxlength="200"></textarea>
        <p class="description">建议控制在 80～160 个中文字符以内。</p>
    </div>
    <?php
}
add_action(
    'product_category_add_form_fields',
    'pfl_product_category_add_content_fields',
    20
);


function pfl_product_category_edit_content_fields(WP_Term $term): void
{
    $content = pfl_get_product_category_content($term);
    wp_nonce_field('pfl_save_category_content', 'pfl_category_content_nonce');
    ?>
    <tr class="form-field pfl-category-content-field">
        <th scope="row"><label>分类图片</label></th>
        <td>
            <?php pfl_render_category_image_control((int) $content['image_id']); ?>
            <p class="description">用于分类页头部视觉区域和 CollectionPage 结构化数据。</p>
        </td>
    </tr>

    <tr class="form-field pfl-category-content-field">
        <th scope="row"><label for="pfl-category-top-content">分类顶部内容</label></th>
        <td>
            <textarea
                class="large-text"
                id="pfl-category-top-content"
                name="pfl_category_top_content"
                rows="6"
            ><?php echo esc_textarea($content['top_content']); ?></textarea>
            <p class="description">显示在分类标题下方，支持安全 HTML。</p>
        </td>
    </tr>

    <tr class="form-field pfl-category-content-field">
        <th scope="row"><label for="pfl-category-bottom-content">分类底部内容</label></th>
        <td>
            <textarea
                class="large-text"
                id="pfl-category-bottom-content"
                name="pfl_category_bottom_content"
                rows="9"
            ><?php echo esc_textarea($content['bottom_content']); ?></textarea>
            <p class="description">显示在产品结果之后，适合补充分类说明、应用范围和选型建议。</p>
        </td>
    </tr>

    <tr class="form-field pfl-category-content-field">
        <th scope="row"><label for="pfl-category-seo-title">SEO 标题</label></th>
        <td>
            <input
                class="large-text"
                id="pfl-category-seo-title"
                name="pfl_category_seo_title"
                type="text"
                value="<?php echo esc_attr($content['seo_title']); ?>"
            >
            <p class="description">留空时使用 WordPress 默认文档标题。</p>
        </td>
    </tr>

    <tr class="form-field pfl-category-content-field">
        <th scope="row"><label for="pfl-category-meta-description">Meta Description</label></th>
        <td>
            <textarea
                class="large-text"
                id="pfl-category-meta-description"
                name="pfl_category_meta_description"
                rows="4"
                maxlength="200"
            ><?php echo esc_textarea($content['meta_description']); ?></textarea>
            <p class="description">建议控制在 80～160 个中文字符以内。</p>
        </td>
    </tr>
    <?php
}
add_action(
    'product_category_edit_form_fields',
    'pfl_product_category_edit_content_fields',
    20
);


function pfl_save_product_category_content_fields(int $term_id): void
{
    if (
        ! isset($_POST['pfl_category_content_nonce'])
        || ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['pfl_category_content_nonce'])
            ),
            'pfl_save_category_content'
        )
        || ! current_user_can('manage_categories')
    ) {
        return;
    }

    $image_id = isset($_POST['pfl_category_image_id'])
        ? absint(wp_unslash($_POST['pfl_category_image_id']))
        : 0;

    update_term_meta(
        $term_id,
        'pfl_category_image_id',
        $image_id
    );

    $html_fields = [
        'pfl_category_top_content',
        'pfl_category_bottom_content',
    ];

    foreach ($html_fields as $field) {
        $value = isset($_POST[$field])
            ? wp_kses_post(wp_unslash($_POST[$field]))
            : '';

        update_term_meta($term_id, $field, $value);
    }

    $seo_title = isset($_POST['pfl_category_seo_title'])
        ? sanitize_text_field(
            wp_unslash($_POST['pfl_category_seo_title'])
        )
        : '';

    $meta_description = isset($_POST['pfl_category_meta_description'])
        ? sanitize_textarea_field(
            wp_unslash($_POST['pfl_category_meta_description'])
        )
        : '';

    update_term_meta(
        $term_id,
        'pfl_category_seo_title',
        $seo_title
    );

    update_term_meta(
        $term_id,
        'pfl_category_meta_description',
        $meta_description
    );
}
add_action(
    'created_product_category',
    'pfl_save_product_category_content_fields',
    20
);
add_action(
    'edited_product_category',
    'pfl_save_product_category_content_fields',
    20
);


function pfl_product_category_content_admin_assets(string $hook): void
{
    $screen = get_current_screen();

    if (
        ! $screen
        || 'product_category' !== $screen->taxonomy
        || ! in_array($hook, ['edit-tags.php', 'term.php'], true)
    ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery');

    $script = <<<'JS'
    jQuery(function ($) {
        $(document).on('click', '.pfl-category-image-select', function (event) {
            event.preventDefault();

            const $control = $(this).closest('.pfl-category-image-control');
            const frame = wp.media({
                title: '选择产品分类图片',
                button: { text: '使用这张图片' },
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const previewUrl = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                $control.find('.pfl-category-image-id').val(attachment.id);
                $control.find('.pfl-category-image-preview')
                    .addClass('has-image')
                    .html($('<img>', { src: previewUrl, alt: '' }));
                $control.find('.pfl-category-image-remove').prop('hidden', false);
            });

            frame.open();
        });

        $(document).on('click', '.pfl-category-image-remove', function (event) {
            event.preventDefault();

            const $control = $(this).closest('.pfl-category-image-control');
            $control.find('.pfl-category-image-id').val('');
            $control.find('.pfl-category-image-preview')
                .removeClass('has-image')
                .html('<span>尚未选择分类图片</span>');
            $(this).prop('hidden', true);
        });
    });
    JS;

    wp_add_inline_script('jquery', $script);
}
add_action(
    'admin_enqueue_scripts',
    'pfl_product_category_content_admin_assets',
    20
);


function pfl_product_category_content_admin_styles(): void
{
    $screen = get_current_screen();

    if (! $screen || 'product_category' !== $screen->taxonomy) {
        return;
    }
    ?>
    <style>
        .pfl-category-content-field { border-top: 1px solid #dcdcde; padding-top: 18px; }
        .pfl-category-image-preview { display:grid; width:260px; max-width:100%; aspect-ratio:16/10; place-items:center; overflow:hidden; border:1px dashed #a7aaad; border-radius:8px; color:#646970; background:#f6f7f7; }
        .pfl-category-image-preview img { width:100%; height:100%; object-fit:cover; }
        .pfl-category-image-remove[hidden] { display:none; }
    </style>
    <?php
}
add_action(
    'admin_head-edit-tags.php',
    'pfl_product_category_content_admin_styles',
    20
);
add_action(
    'admin_head-term.php',
    'pfl_product_category_content_admin_styles',
    20
);


function pfl_product_category_content_columns(array $columns): array
{
    $columns['pfl_category_image'] = '分类图片';
    $columns['pfl_category_seo'] = 'SEO 内容';

    return $columns;
}
add_filter(
    'manage_edit-product_category_columns',
    'pfl_product_category_content_columns',
    20
);


function pfl_product_category_content_column(
    string $content,
    string $column,
    int $term_id
): string {
    if ('pfl_category_image' === $column) {
        $image_id = (int) get_term_meta(
            $term_id,
            'pfl_category_image_id',
            true
        );

        if (! $image_id) {
            return '—';
        }

        return (string) wp_get_attachment_image(
            $image_id,
            [56, 40],
            false,
            [
                'style' => 'width:56px;height:40px;object-fit:cover;border-radius:4px;',
            ]
        );
    }

    if ('pfl_category_seo' === $column) {
        $term = get_term($term_id, 'product_category');

        if (! $term instanceof WP_Term) {
            return '—';
        }

        $content_data = pfl_get_product_category_content($term);
        $status = [];

        if ($content_data['seo_title']) {
            $status[] = '标题';
        }

        if ($content_data['meta_description']) {
            $status[] = '描述';
        }

        if ($content_data['bottom_content']) {
            $status[] = '底部内容';
        }

        return empty($status)
            ? '未设置'
            : esc_html(implode(' / ', $status));
    }

    return $content;
}
add_filter(
    'manage_product_category_custom_column',
    'pfl_product_category_content_column',
    20,
    3
);


function pfl_builtin_seo_enabled(): bool
{
    $seo_plugin_active = (
        defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION')
    );

    return (bool) apply_filters(
        'pfl_enable_builtin_seo',
        ! $seo_plugin_active
    );
}


function pfl_get_product_modifier_keys(): array
{
    return array_values(
        array_unique(
            array_merge(
                array_keys(pfl_get_product_filter_schema()),
                ['sort']
            )
        )
    );
}


function pfl_product_request_has_modifiers(?array $source = null): bool
{
    $source = null === $source ? $_GET : $source;

    foreach (pfl_get_product_modifier_keys() as $key) {
        if (! array_key_exists($key, $source)) {
            continue;
        }

        $value = $source[$key];

        if (is_array($value)) {
            if (! empty(array_filter($value, 'strlen'))) {
                return true;
            }
        } elseif ('' !== trim((string) $value)) {
            return true;
        }
    }

    return false;
}


function pfl_get_product_canonical_url(): string
{
    if (
        ! is_post_type_archive('product')
        && ! is_tax('product_category')
    ) {
        return '';
    }

    $base_url = pfl_get_product_filter_action();

    if (pfl_product_request_has_modifiers()) {
        return $base_url;
    }

    $paged = max(1, (int) get_query_var('paged'));

    if ($paged > 1) {
        return remove_query_arg(
            pfl_get_product_modifier_keys(),
            get_pagenum_link($paged)
        );
    }

    return $base_url;
}


function pfl_get_product_archive_meta_description(): string
{
    if (is_tax('product_category')) {
        $term = pfl_get_current_product_category();
        $content = pfl_get_product_category_content($term);

        if ($content['meta_description']) {
            return $content['meta_description'];
        }

        $fallback = $content['top_content'];

        if (! $fallback && $term) {
            $fallback = term_description($term->term_id, 'product_category');
        }

        $fallback = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                wp_strip_all_tags((string) $fallback)
            )
        );

        if ($fallback) {
            return wp_html_excerpt($fallback, 160, '…');
        }

        return $term
            ? sprintf('浏览%s分类下的产品、规格和筛选条件。', $term->name)
            : '';
    }

    if (is_post_type_archive('product')) {
        return sprintf(
            '浏览%s的全部产品目录，并按分类、品牌、属性和数值范围进行筛选。',
            get_bloginfo('name')
        );
    }

    return '';
}


function pfl_get_product_single_meta_description(int $post_id): string
{
    $excerpt = get_the_excerpt($post_id);

    if (! $excerpt) {
        $post = get_post($post_id);
        $excerpt = $post instanceof WP_Post
            ? $post->post_content
            : '';
    }

    $excerpt = trim(
        preg_replace(
            '/\s+/u',
            ' ',
            wp_strip_all_tags((string) $excerpt)
        )
    );

    return $excerpt
        ? wp_html_excerpt($excerpt, 160, '…')
        : '';
}


function pfl_product_document_title(string $title): string
{
    if (! pfl_builtin_seo_enabled() || ! is_tax('product_category')) {
        return $title;
    }

    $content = pfl_get_product_category_content();

    return $content['seo_title'] ?: $title;
}
add_filter(
    'pre_get_document_title',
    'pfl_product_document_title',
    20
);


function pfl_output_product_archive_meta(): void
{
    if (! pfl_builtin_seo_enabled()) {
        return;
    }

    if (is_singular('product')) {
        $description = pfl_get_product_single_meta_description(
            get_queried_object_id()
        );
        $canonical = '';
    } elseif (
        is_post_type_archive('product')
        || is_tax('product_category')
    ) {
        $description = pfl_get_product_archive_meta_description();
        $canonical = pfl_get_product_canonical_url();
    } else {
        return;
    }

    if ($description) {
        echo '<meta name="description" content="'
            . esc_attr($description)
            . '">' . "\n";
    }

    // WordPress 核心已经为单篇产品输出 rel=canonical，归档页由主题补充。
    if ($canonical) {
        echo '<link rel="canonical" href="'
            . esc_url($canonical)
            . '">' . "\n";
    }
}
add_action(
    'wp_head',
    'pfl_output_product_archive_meta',
    5
);


function pfl_product_filter_robots(array $robots): array
{
    if (
        (
            is_post_type_archive('product')
            || is_tax('product_category')
        )
        && pfl_product_request_has_modifiers()
    ) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
}
add_filter('wp_robots', 'pfl_product_filter_robots');


function pfl_get_product_primary_category(int $post_id): ?WP_Term
{
    $terms = get_the_terms($post_id, 'product_category');

    if (empty($terms) || is_wp_error($terms)) {
        return null;
    }

    usort(
        $terms,
        static function (WP_Term $left, WP_Term $right): int {
            $left_depth = count(
                get_ancestors(
                    $left->term_id,
                    'product_category',
                    'taxonomy'
                )
            );
            $right_depth = count(
                get_ancestors(
                    $right->term_id,
                    'product_category',
                    'taxonomy'
                )
            );

            return $right_depth <=> $left_depth;
        }
    );

    return $terms[0] instanceof WP_Term ? $terms[0] : null;
}


function pfl_get_product_category_path_for_term(WP_Term $term): array
{
    $ancestor_ids = array_reverse(
        get_ancestors(
            $term->term_id,
            'product_category',
            'taxonomy'
        )
    );

    $path = [];

    foreach ($ancestor_ids as $ancestor_id) {
        $ancestor = get_term(
            (int) $ancestor_id,
            'product_category'
        );

        if ($ancestor instanceof WP_Term) {
            $path[] = $ancestor;
        }
    }

    $path[] = $term;

    return $path;
}


function pfl_get_breadcrumb_schema(): array
{
    $items = pfl_get_product_breadcrumb_items();

    if (empty($items)) {
        return [];
    }

    if (is_singular('product')) {
        $current_url = get_permalink(get_queried_object_id());
    } else {
        $current_url = pfl_get_product_canonical_url();
    }

    $list = [];

    foreach ($items as $index => $item) {
        $url = ! empty($item['url'])
            ? $item['url']
            : $current_url;

        $list[] = [
            '@type'    => 'ListItem',
            'position' => $index + 1,
            'name'     => $item['label'],
            'item'     => $url,
        ];
    }

    return [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}


function pfl_get_product_schema(int $post_id): array
{
    $post = get_post($post_id);

    if (! $post instanceof WP_Post) {
        return [];
    }

    $description = has_excerpt($post_id)
        ? get_the_excerpt($post_id)
        : wp_strip_all_tags($post->post_content);

    $schema = [
        '@type'       => 'Product',
        'name'        => get_the_title($post_id),
        'url'         => get_permalink($post_id),
        'description' => wp_html_excerpt($description, 300, '…'),
    ];

    $image_url = get_the_post_thumbnail_url($post_id, 'full');

    if ($image_url) {
        $schema['image'] = [$image_url];
    }

    $model = (string) get_post_meta(
        $post_id,
        'product_model',
        true
    );

    if ($model) {
        $schema['sku'] = $model;
        $schema['model'] = $model;
    }

    $brand_terms = get_the_terms($post_id, 'product_brand');

    if (! empty($brand_terms) && ! is_wp_error($brand_terms)) {
        $schema['brand'] = [
            '@type' => 'Brand',
            'name'  => $brand_terms[0]->name,
        ];
    }

    $category = pfl_get_product_primary_category($post_id);

    if ($category) {
        $schema['category'] = $category->name;
    }

    $price = get_post_meta($post_id, 'product_price', true);

    if ('' !== $price && is_numeric($price)) {
        $schema['offers'] = [
            '@type'         => 'Offer',
            'url'           => get_permalink($post_id),
            'priceCurrency' => 'CNY',
            'price'         => number_format((float) $price, 2, '.', ''),
        ];
    }

    $additional_properties = [];
    $property_taxonomies = [
        'product_voltage'   => '产品电压',
        'product_material'  => '产品材质',
        'product_automation'=> '自动化程度',
        'product_protection'=> '防护等级',
    ];

    foreach ($property_taxonomies as $taxonomy => $label) {
        $terms = get_the_terms($post_id, $taxonomy);

        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        $additional_properties[] = [
            '@type' => 'PropertyValue',
            'name'  => $label,
            'value' => implode('、', wp_list_pluck($terms, 'name')),
        ];
    }

    $power = get_post_meta($post_id, 'product_power', true);

    if ('' !== $power) {
        $additional_properties[] = [
            '@type' => 'PropertyValue',
            'name'  => '功率',
            'value' => $power . ' kW',
        ];
    }

    if (! empty($additional_properties)) {
        $schema['additionalProperty'] = $additional_properties;
    }

    return $schema;
}


function pfl_get_collection_schema(): array
{
    global $wp_query;

    $name = is_tax('product_category')
        ? single_term_title('', false)
        : '全部产品';

    $schema = [
        '@type'       => 'CollectionPage',
        'name'        => $name,
        'url'         => pfl_get_product_canonical_url(),
        'description' => pfl_get_product_archive_meta_description(),
    ];

    if (is_tax('product_category')) {
        $content = pfl_get_product_category_content();

        if ($content['image_id']) {
            $image_url = wp_get_attachment_image_url(
                $content['image_id'],
                'full'
            );

            if ($image_url) {
                $schema['primaryImageOfPage'] = [
                    '@type'      => 'ImageObject',
                    'contentUrl' => $image_url,
                ];
            }
        }
    }

    $list_items = [];

    if ($wp_query instanceof WP_Query) {
        foreach ($wp_query->posts as $index => $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }

            $list_items[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => get_the_title($post),
                'url'      => get_permalink($post),
            ];
        }

        $schema['mainEntity'] = [
            '@type'           => 'ItemList',
            'numberOfItems'    => count($list_items),
            'itemListElement' => $list_items,
        ];
    }

    return $schema;
}


function pfl_output_product_structured_data(): void
{
    if (
        ! pfl_builtin_seo_enabled()
        || (
            ! is_singular('product')
            && ! is_post_type_archive('product')
            && ! is_tax('product_category')
        )
    ) {
        return;
    }

    $graph = [];
    $breadcrumbs = pfl_get_breadcrumb_schema();

    if ($breadcrumbs) {
        $graph[] = $breadcrumbs;
    }

    if (is_singular('product')) {
        $entity = pfl_get_product_schema(get_queried_object_id());
    } elseif (pfl_product_request_has_modifiers()) {
        // 筛选结果页使用 noindex，并指向干净归档 Canonical。
        // 为避免 ItemList 与 Canonical 页面内容不一致，此处不输出 CollectionPage。
        $entity = [];
    } else {
        $entity = pfl_get_collection_schema();
    }

    if ($entity) {
        $graph[] = $entity;
    }

    if (empty($graph)) {
        return;
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph'   => $graph,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
        . '</script>' . "\n";
}
add_action(
    'wp_head',
    'pfl_output_product_structured_data',
    30
);


function pfl_import_demo_category_image(
    int $term_id,
    string $filename,
    string $title
): int {
    $existing = (int) get_term_meta(
        $term_id,
        'pfl_category_image_id',
        true
    );

    if ($existing && get_post($existing)) {
        return $existing;
    }

    $source = get_theme_file_path('/assets/images/' . $filename);

    if (! file_exists($source)) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $temporary = wp_tempnam($filename);

    if (! $temporary || ! copy($source, $temporary)) {
        return 0;
    }

    $file_array = [
        'name'     => $filename,
        'tmp_name' => $temporary,
    ];

    $attachment_id = media_handle_sideload(
        $file_array,
        0,
        $title
    );

    if (is_wp_error($attachment_id)) {
        @unlink($temporary);
        return 0;
    }

    update_post_meta(
        $attachment_id,
        '_wp_attachment_image_alt',
        $title
    );

    update_term_meta(
        $term_id,
        'pfl_category_image_id',
        (int) $attachment_id
    );

    return (int) $attachment_id;
}


/**
 * 十四、演示数据导入器。
 */
function pfl_add_demo_data_page(): void
{
    add_theme_page(
        '产品主题演示数据',
        '产品主题演示数据',
        'manage_options',
        'pfl-demo-data',
        'pfl_render_demo_data_page'
    );
}
add_action('admin_menu', 'pfl_add_demo_data_page');


function pfl_render_demo_data_page(): void
{
    if (! current_user_can('manage_options')) { return; }
    $message = '';

    if (isset($_POST['pfl_import_demo']) && check_admin_referer('pfl_import_demo_data')) {
        $result = pfl_import_demo_data();
        $message = sprintf(
            '导入完成：处理了 %1$d 个分类或属性项，创建或更新了 %2$d 个产品，并导入了 %3$d 张分类图片。',
            (int) $result['terms'],
            (int) $result['products'],
            (int) $result['images']
        );
    }
    ?>
    <div class="wrap">
        <h1>产品导航筛选实验室：v1.4.0 演示数据</h1>
        <?php if ($message) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div><?php endif; ?>
        <p>点击按钮将补充动态产品属性、分类专属筛选方案、分类图片、分类上下文内容、SEO 字段和演示产品。已有同名产品会被更新，不会重复创建。</p>
        <form method="post">
            <?php wp_nonce_field('pfl_import_demo_data'); ?>
            <p><button class="button button-primary button-hero" name="pfl_import_demo" type="submit" value="1">导入或更新 v1.4.0 演示数据</button></p>
        </form>
        <hr>
        <h2>建议测试页面</h2>
        <p><a class="button" href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" target="_blank">打开产品归档页</a></p>
        <ol>
            <li>访问“工业设备”“包装设备”，检查分类图片、顶部内容和底部内容。</li>
            <li>访问“真空包装机”，确认其继承“包装设备”筛选方案。</li>
            <li>查看页面源代码，检查 Meta Description、Canonical 和 JSON-LD。</li>
            <li>添加筛选参数后，确认页面输出 noindex、follow，并将 Canonical 指向干净分类地址。</li>
            <li>编辑产品分类，测试图片、SEO 标题、Meta Description 与筛选方案设置。</li>
        </ol>
    </div>
    <?php
}


function pfl_ensure_term(
    string $name,
    string $taxonomy,
    int $parent = 0
): int {
    $existing = term_exists($name, $taxonomy, $parent);

    if (is_array($existing)) {
        return (int) $existing['term_id'];
    }

    if (is_int($existing)) {
        return $existing;
    }

    $created = wp_insert_term(
        $name,
        $taxonomy,
        [
            'parent' => $parent,
        ]
    );

    if (is_wp_error($created)) {
        return 0;
    }

    return (int) $created['term_id'];
}


function pfl_import_demo_data(): array
{
    $term_count = 0;
    $product_count = 0;
    $image_count = 0;

    $industrial = pfl_ensure_term('工业设备', 'product_category');
    $commercial = pfl_ensure_term('商用设备', 'product_category');
    $parts = pfl_ensure_term('零部件', 'product_category');
    $packaging = pfl_ensure_term('包装设备', 'product_category', $industrial);
    $conveying = pfl_ensure_term('输送设备', 'product_category', $industrial);
    $cleaning = pfl_ensure_term('清洗设备', 'product_category', $industrial);
    $vacuum = pfl_ensure_term('真空包装机', 'product_category', $packaging);
    $sealer = pfl_ensure_term('自动封口机', 'product_category', $packaging);
    $shrink = pfl_ensure_term('热收缩机', 'product_category', $packaging);
    $kitchen = pfl_ensure_term('厨房设备', 'product_category', $commercial);
    $sanitary = pfl_ensure_term('商用清洁设备', 'product_category', $commercial);
    $motors = pfl_ensure_term('电机', 'product_category', $parts);
    $sensors = pfl_ensure_term('传感器', 'product_category', $parts);

    $category_ids = array_filter([$industrial, $commercial, $parts, $packaging, $conveying, $cleaning, $vacuum, $sealer, $shrink, $kitchen, $sanitary, $motors, $sensors]);
    $term_count += count($category_ids);

    $attribute_terms = [
        'product_brand' => ['华东机械', '蓝海设备', '启航工业', '新锐科技'],
        'product_voltage' => ['220V', '380V', '定制电压'],
        'product_feature' => ['全自动', '触屏控制', '不锈钢机身', '支持定制', '节能型'],
        'product_material' => ['304不锈钢', '碳钢', '铝合金', '工程塑料'],
        'product_application' => ['食品加工', '医药制造', '电子制造', '仓储物流', '餐饮服务'],
        'product_automation' => ['手动', '半自动', '全自动'],
        'product_installation' => ['固定式', '移动式', '卧式', '立式', '法兰式'],
        'product_output_type' => ['NPN', 'PNP', '模拟量', '继电器输出'],
        'product_protection' => ['IP54', 'IP65', 'IP67'],
        'product_packaging_type' => ['真空包装', '连续封口', '热收缩包装'],
        'product_conveyor_type' => ['皮带式', '滚筒式', '链板式'],
        'product_detection_type' => ['光电检测', '接近检测', '压力检测', '温度检测'],
    ];

    foreach ($attribute_terms as $taxonomy => $names) {
        foreach ($names as $name) {
            if (pfl_ensure_term($name, $taxonomy)) { $term_count++; }
        }
    }

    $profiles = [
        $industrial => ['brand', 'voltage', 'application', 'material', 'price_range', 'power_range'],
        $commercial => ['brand', 'voltage', 'application', 'material', 'automation', 'price_range', 'power_range'],
        $packaging => ['brand', 'voltage', 'automation', 'packaging_type', 'material', 'feature', 'price_range', 'power_range'],
        $conveying => ['brand', 'voltage', 'conveyor_type', 'installation', 'material', 'width_range', 'speed_range', 'power_range', 'price_range'],
        $motors => ['brand', 'voltage', 'installation', 'protection', 'power_range', 'rpm_range', 'price_range'],
        $sensors => ['brand', 'voltage', 'detection_type', 'output_type', 'protection', 'distance_range', 'price_range'],
    ];

    foreach ($category_ids as $category_id) {
        if (isset($profiles[$category_id])) {
            update_term_meta($category_id, 'pfl_filter_mode', 'custom');
            update_term_meta($category_id, 'pfl_filter_groups', $profiles[$category_id]);
        } else {
            update_term_meta($category_id, 'pfl_filter_mode', 'inherit');
        }
    }

    $category_content = [
        $industrial => [
            'top' => '<p>工业设备分类汇总包装、输送、清洗等生产线设备，可继续通过下级分类和动态属性筛选定位产品。</p>',
            'bottom' => '<h2>工业设备选型说明</h2><p>选型时应综合考虑生产节拍、供电条件、设备材质、安装空间、维护方式和后续产能扩展。建议先确定设备用途与工艺范围，再结合功率、尺寸和自动化程度进行筛选。</p>',
            'title' => '工业设备产品目录与参数筛选',
            'description' => '浏览工业设备产品目录，按品牌、电压、材质、应用行业、价格和功率筛选包装设备、输送设备及清洗设备。',
            'image' => 'category-industrial.jpg',
        ],
        $commercial => [
            'top' => '<p>商用设备面向餐饮、清洁和服务场景，重点关注稳定性、卫生要求、操作效率与日常维护成本。</p>',
            'bottom' => '<h2>商用设备应用建议</h2><p>商用环境通常具有高频使用、集中作业和快速周转特点。选型时可重点比较设备容量、自动化程度、清洁便利性、能耗和售后维护条件。</p>',
            'title' => '商用设备产品目录与选型筛选',
            'description' => '查看商用设备产品，并按品牌、电压、材质、自动化程度、价格和功率快速筛选。',
            'image' => 'category-commercial.jpg',
        ],
        $parts => [
            'top' => '<p>零部件分类收录电机、传感器等关键组件，可按照安装方式、防护等级、输出类型和数值参数筛选。</p>',
            'bottom' => '<h2>工业零部件匹配原则</h2><p>零部件替换应核对接口、尺寸、电压、负载、信号类型和环境防护要求。仅凭外观或单一参数选型，可能导致兼容性和稳定性问题。</p>',
            'title' => '工业零部件目录与参数匹配',
            'description' => '浏览工业电机、传感器等零部件，按品牌、电压、安装方式、防护等级、转速和检测距离筛选。',
            'image' => 'category-parts.jpg',
        ],
        $packaging => [
            'top' => '<p>包装设备包含真空包装、连续封口和热收缩等设备，可按自动化程度、包装方式、材质、功率和价格组合筛选。</p>',
            'bottom' => '<h2>包装设备如何选择</h2><p>需要先明确包装材料、产品尺寸、单件重量、目标速度和密封要求。连续生产场景通常更关注自动化与运行速度，小批量场景则可优先考虑设备尺寸与操作便利性。</p>',
            'title' => '包装设备产品目录与多条件筛选',
            'description' => '查看真空包装机、封口机和热收缩包装机，按品牌、电压、自动化程度、包装方式、材质、功率和价格筛选。',
            'image' => 'category-packaging.jpg',
        ],
        $vacuum => [
            'top' => '<p>真空包装机通过抽除包装内部空气延长保存时间，适合食品、医药及部分工业制品包装。</p>',
            'bottom' => '<h2>真空包装机选型要点</h2><p>重点比较真空室尺寸、封口长度、真空泵能力、生产节拍和设备材质。连续式设备适合较高产能，台式和单室设备更适合中小批量应用。</p>',
            'title' => '真空包装机型号、价格与参数筛选',
            'description' => '浏览真空包装机型号，按品牌、电压、自动化程度、材质、功能、功率和价格进行筛选。',
        ],
        $conveying => [
            'top' => '<p>输送设备用于生产线物料转运，可按照皮带、滚筒或链板结构，以及安装方式、宽度和运行速度筛选。</p>',
            'bottom' => '<h2>输送设备选型要点</h2><p>应结合物料形态、单位重量、输送距离、转弯需求、清洁要求和上下游设备高度确定结构。食品场景通常更重视不锈钢材质与清洁便利性。</p>',
            'title' => '输送设备类型、宽度与速度筛选',
            'description' => '查看皮带输送机、滚筒输送机和链板输送机，按输送方式、安装方式、宽度、速度、功率和价格筛选。',
        ],
        $motors => [
            'top' => '<p>电机分类支持按品牌、电压、安装方式、防护等级、功率和转速进行组合筛选。</p>',
            'bottom' => '<h2>电机参数匹配</h2><p>电机选型需要同时核对额定功率、转速、供电电压、安装结构、防护等级和负载特性，并为启动和持续运行预留合理余量。</p>',
            'title' => '工业电机功率、转速与安装方式筛选',
            'description' => '浏览工业电机产品，按品牌、电压、安装方式、防护等级、功率、转速和价格筛选。',
        ],
        $sensors => [
            'top' => '<p>传感器分类支持按检测方式、输出类型、防护等级、检测距离、电压和品牌筛选。</p>',
            'bottom' => '<h2>传感器选型要点</h2><p>应根据被测对象、检测距离、安装空间、环境干扰、输出接口和控制系统输入要求进行匹配，并确认防护等级是否适应现场环境。</p>',
            'title' => '工业传感器检测方式与输出类型筛选',
            'description' => '浏览光电、接近和压力传感器，按品牌、电压、检测方式、输出类型、防护等级、检测距离和价格筛选。',
        ],
    ];

    foreach ($category_content as $category_id => $content_item) {
        update_term_meta($category_id, 'pfl_category_top_content', $content_item['top']);
        update_term_meta($category_id, 'pfl_category_bottom_content', $content_item['bottom']);
        update_term_meta($category_id, 'pfl_category_seo_title', $content_item['title']);
        update_term_meta($category_id, 'pfl_category_meta_description', $content_item['description']);

        if (! empty($content_item['image'])) {
            $before_image = (int) get_term_meta($category_id, 'pfl_category_image_id', true);
            $attachment_id = pfl_import_demo_category_image(
                $category_id,
                $content_item['image'],
                get_term_field('name', $category_id, 'product_category') . '分类图片'
            );

            if ((! $before_image || ! get_post($before_image)) && $attachment_id) {
                $image_count++;
            }
        }
    }

    $products = [
        ['title'=>'双室真空包装机','category'=>$vacuum,'model'=>'VP-600D','price'=>46800,'power'=>5.5,'width'=>720,'speed'=>12,'tax'=>['product_brand'=>['华东机械'],'product_voltage'=>['380V'],'product_feature'=>['全自动','触屏控制','不锈钢机身'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工'],'product_automation'=>['全自动'],'product_packaging_type'=>['真空包装']]],
        ['title'=>'台式真空包装机','category'=>$vacuum,'model'=>'VP-320','price'=>8800,'power'=>1.2,'width'=>380,'speed'=>6,'tax'=>['product_brand'=>['蓝海设备'],'product_voltage'=>['220V'],'product_feature'=>['节能型','不锈钢机身'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工'],'product_automation'=>['手动'],'product_packaging_type'=>['真空包装']]],
        ['title'=>'连续式真空包装机','category'=>$vacuum,'model'=>'VP-1000C','price'=>69800,'power'=>11,'width'=>1050,'speed'=>35,'tax'=>['product_brand'=>['启航工业'],'product_voltage'=>['380V'],'product_feature'=>['全自动','触屏控制','支持定制'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工','医药制造'],'product_automation'=>['全自动'],'product_packaging_type'=>['真空包装']]],
        ['title'=>'自动连续封口机','category'=>$sealer,'model'=>'FS-980','price'=>12800,'power'=>2.2,'width'=>520,'speed'=>18,'tax'=>['product_brand'=>['新锐科技'],'product_voltage'=>['220V'],'product_feature'=>['全自动','节能型'],'product_material'=>['铝合金'],'product_application'=>['食品加工'],'product_automation'=>['全自动'],'product_packaging_type'=>['连续封口']]],
        ['title'=>'重型自动封口机','category'=>$sealer,'model'=>'FS-1500','price'=>35800,'power'=>6.5,'width'=>880,'speed'=>28,'tax'=>['product_brand'=>['华东机械'],'product_voltage'=>['380V'],'product_feature'=>['触屏控制','支持定制'],'product_material'=>['碳钢'],'product_application'=>['仓储物流'],'product_automation'=>['全自动'],'product_packaging_type'=>['连续封口']]],
        ['title'=>'热收缩包装机','category'=>$shrink,'model'=>'BS-4525','price'=>28600,'power'=>9.5,'width'=>760,'speed'=>22,'tax'=>['product_brand'=>['蓝海设备'],'product_voltage'=>['380V'],'product_feature'=>['全自动','不锈钢机身'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工'],'product_automation'=>['全自动'],'product_packaging_type'=>['热收缩包装']]],
        ['title'=>'皮带输送机','category'=>$conveying,'model'=>'CV-800','price'=>19800,'power'=>4,'width'=>800,'speed'=>25,'tax'=>['product_brand'=>['启航工业'],'product_voltage'=>['380V'],'product_feature'=>['支持定制','节能型'],'product_material'=>['碳钢'],'product_application'=>['仓储物流'],'product_installation'=>['固定式'],'product_conveyor_type'=>['皮带式']]],
        ['title'=>'滚筒输送机','category'=>$conveying,'model'=>'RC-1200','price'=>23800,'power'=>3.5,'width'=>1200,'speed'=>15,'tax'=>['product_brand'=>['华东机械'],'product_voltage'=>['定制电压'],'product_feature'=>['支持定制'],'product_material'=>['碳钢'],'product_application'=>['仓储物流'],'product_installation'=>['固定式'],'product_conveyor_type'=>['滚筒式']]],
        ['title'=>'移动链板输送机','category'=>$conveying,'model'=>'LC-650','price'=>31800,'power'=>5,'width'=>650,'speed'=>32,'tax'=>['product_brand'=>['新锐科技'],'product_voltage'=>['380V'],'product_feature'=>['支持定制'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工'],'product_installation'=>['移动式'],'product_conveyor_type'=>['链板式']]],
        ['title'=>'工业高压清洗机','category'=>$cleaning,'model'=>'CL-500','price'=>32800,'power'=>7.5,'tax'=>['product_brand'=>['新锐科技'],'product_voltage'=>['380V'],'product_feature'=>['节能型','不锈钢机身'],'product_material'=>['304不锈钢'],'product_application'=>['食品加工'],'product_automation'=>['半自动']]],
        ['title'=>'商用洗碗机','category'=>$kitchen,'model'=>'DW-860','price'=>52800,'power'=>12,'tax'=>['product_brand'=>['蓝海设备'],'product_voltage'=>['380V'],'product_feature'=>['全自动','触屏控制','节能型'],'product_material'=>['304不锈钢'],'product_application'=>['餐饮服务'],'product_automation'=>['全自动']]],
        ['title'=>'工业伺服电机','category'=>$motors,'model'=>'SM-3K','price'=>6800,'power'=>3,'rpm'=>1450,'tax'=>['product_brand'=>['启航工业'],'product_voltage'=>['220V'],'product_feature'=>['节能型'],'product_application'=>['电子制造'],'product_installation'=>['法兰式'],'product_protection'=>['IP65']]],
        ['title'=>'立式防水电机','category'=>$motors,'model'=>'VM-7K','price'=>12600,'power'=>7.5,'rpm'=>2900,'tax'=>['product_brand'=>['华东机械'],'product_voltage'=>['380V'],'product_feature'=>['节能型'],'product_application'=>['食品加工'],'product_installation'=>['立式'],'product_protection'=>['IP67']]],
        ['title'=>'智能光电传感器','category'=>$sensors,'model'=>'PS-220','price'=>2600,'power'=>0.2,'distance'=>120,'tax'=>['product_brand'=>['新锐科技'],'product_voltage'=>['定制电压'],'product_feature'=>['支持定制'],'product_application'=>['电子制造'],'product_detection_type'=>['光电检测'],'product_output_type'=>['NPN','PNP'],'product_protection'=>['IP67']]],
        ['title'=>'工业接近传感器','category'=>$sensors,'model'=>'NS-50','price'=>980,'power'=>0.1,'distance'=>35,'tax'=>['product_brand'=>['蓝海设备'],'product_voltage'=>['定制电压'],'product_application'=>['电子制造'],'product_detection_type'=>['接近检测'],'product_output_type'=>['PNP'],'product_protection'=>['IP65']]],
        ['title'=>'模拟量压力传感器','category'=>$sensors,'model'=>'PT-300','price'=>4200,'power'=>0.3,'distance'=>260,'tax'=>['product_brand'=>['启航工业'],'product_voltage'=>['220V'],'product_application'=>['医药制造'],'product_detection_type'=>['压力检测'],'product_output_type'=>['模拟量'],'product_protection'=>['IP67']]],
    ];

    foreach ($products as $item) {
        $existing = get_page_by_title($item['title'], OBJECT, 'product');
        $post_id = $existing instanceof WP_Post ? (int) $existing->ID : 0;

        if (! $post_id) {
            $created = wp_insert_post([
                'post_type'=>'product','post_status'=>'publish','post_title'=>$item['title'],
                'post_excerpt'=>'用于演示动态分类属性与 AJAX 多条件筛选的示例产品。',
                'post_content'=>'<p>该产品用于学习分类专属筛选方案、属性继承、taxonomy 与数值范围查询。</p>',
            ], true);
            if (is_wp_error($created)) { continue; }
            $post_id = (int) $created;
        }

        wp_set_object_terms($post_id, [(int) $item['category']], 'product_category');

        foreach ($attribute_terms as $taxonomy => $unused) {
            wp_set_object_terms($post_id, $item['tax'][$taxonomy] ?? [], $taxonomy, false);
        }

        update_post_meta($post_id, 'product_model', $item['model']);
        update_post_meta($post_id, 'product_price', $item['price']);
        update_post_meta($post_id, 'product_power', $item['power']);

        $numeric_map = [
            'rpm' => 'product_rpm', 'width' => 'product_width',
            'distance' => 'product_detection_distance', 'speed' => 'product_speed',
        ];

        foreach ($numeric_map as $item_key => $meta_key) {
            if (isset($item[$item_key])) {
                update_post_meta($post_id, $meta_key, (float) $item[$item_key]);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }

        $product_count++;
    }

    flush_rewrite_rules(false);
    return [
        'terms' => $term_count,
        'products' => $product_count,
        'images' => $image_count,
    ];
}


/**
 * 十五、激活主题后提示导入演示数据。
 */
function pfl_demo_data_admin_notice(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    if (get_option('pfl_demo_notice_dismissed')) {
        return;
    }

    $screen = get_current_screen();

    if ($screen && 'appearance_page_pfl-demo-data' === $screen->id) {
        return;
    }

    $url = admin_url('themes.php?page=pfl-demo-data');
    ?>
    <div class="notice notice-info">
        <p>
            <strong>产品导航筛选实验室：</strong>
            主题已启用。请前往
            <a href="<?php echo esc_url($url); ?>">外观 → 产品主题演示数据</a>
            一键导入学习所需的分类与产品。
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'pfl_demo_data_admin_notice');


/**
 * 十六、主题切换时刷新固定链接规则。
 */
function pfl_after_switch_theme(): void
{
    pfl_register_product_post_type();
    pfl_register_product_taxonomies();
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'pfl_after_switch_theme');
