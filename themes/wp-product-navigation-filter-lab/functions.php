<?php
/**
 * 产品导航筛选实验室主题核心功能。
 *
 * 本主题为了方便学习，将以下内容集中放在 functions.php：
 * 1. 自定义文章类型 product
 * 2. 产品层级分类 product_category
 * 3. 品牌、电压、功能分类法
 * 4. 产品价格、功率、型号自定义字段
 * 5. GET 多条件筛选
 * 6. 演示数据导入器
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

    if (
        is_post_type_archive('product')
        || is_tax('product_category')
        || is_singular('product')
        || is_front_page()
    ) {
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
            'supports'            => [
                'title',
                'editor',
                'excerpt',
                'thumbnail',
            ],
            'taxonomies'          => [
                'product_category',
                'product_brand',
                'product_voltage',
                'product_feature',
            ],
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

    $flat_taxonomies = [
        'product_brand' => [
            'name' => '产品品牌',
            'slug' => 'product-brand',
        ],
        'product_voltage' => [
            'name' => '产品电压',
            'slug' => 'product-voltage',
        ],
        'product_feature' => [
            'name' => '功能特点',
            'slug' => 'product-feature',
        ],
    ];

    foreach ($flat_taxonomies as $taxonomy => $config) {
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
                'show_admin_column' => true,
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
function pfl_register_product_meta(): void
{
    $meta_fields = [
        'product_model' => 'string',
        'product_price' => 'number',
        'product_power' => 'number',
    ];

    foreach ($meta_fields as $meta_key => $type) {
        register_post_meta(
            'product',
            $meta_key,
            [
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
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

    $model = get_post_meta($post->ID, 'product_model', true);
    $price = get_post_meta($post->ID, 'product_price', true);
    $power = get_post_meta($post->ID, 'product_power', true);
    ?>
    <table class="form-table">
        <tr>
            <th>
                <label for="product_model">产品型号</label>
            </th>
            <td>
                <input
                    class="regular-text"
                    id="product_model"
                    name="product_model"
                    type="text"
                    value="<?php echo esc_attr($model); ?>"
                    placeholder="例如：VP-500"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="product_price">产品价格</label>
            </th>
            <td>
                <input
                    id="product_price"
                    name="product_price"
                    type="number"
                    min="0"
                    step="0.01"
                    value="<?php echo esc_attr($price); ?>"
                    placeholder="只填写数字，例如：26800"
                >
                <p class="description">保存纯数字，用于价格范围筛选和排序。</p>
            </td>
        </tr>

        <tr>
            <th>
                <label for="product_power">功率（kW）</label>
            </th>
            <td>
                <input
                    id="product_power"
                    name="product_power"
                    type="number"
                    min="0"
                    step="0.1"
                    value="<?php echo esc_attr($power); ?>"
                    placeholder="例如：5.5"
                >
            </td>
        </tr>
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

    if (
        defined('DOING_AUTOSAVE')
        && DOING_AUTOSAVE
    ) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $text_fields = [
        'product_model',
    ];

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta(
                $post_id,
                $field,
                sanitize_text_field(wp_unslash($_POST[$field]))
            );
        }
    }

    $number_fields = [
        'product_price',
        'product_power',
    ];

    foreach ($number_fields as $field) {
        if (! isset($_POST[$field])) {
            continue;
        }

        $value = wp_unslash($_POST[$field]);

        if ('' === $value) {
            delete_post_meta($post_id, $field);
            continue;
        }

        update_post_meta(
            $post_id,
            $field,
            (float) $value
        );
    }
}
add_action('save_post_product', 'pfl_save_product_meta');


/**
 * 七、筛选配置。
 *
 * 将筛选字段、分类法和运算符集中配置，后续增加筛选项时，
 * 不需要在模板和查询逻辑中重复编写大量判断。
 */
function pfl_get_taxonomy_filter_config(): array
{
    return [
        'brand' => [
            'label'    => '产品品牌',
            'taxonomy' => 'product_brand',
            'operator' => 'IN',
        ],
        'voltage' => [
            'label'    => '产品电压',
            'taxonomy' => 'product_voltage',
            'operator' => 'IN',
        ],
        'feature' => [
            'label'    => '功能特点',
            'taxonomy' => 'product_feature',
            'operator' => 'AND',
        ],
    ];
}


function pfl_get_range_filter_config(): array
{
    return [
        'price_range' => [
            'label'    => '价格范围',
            'meta_key' => 'product_price',
            'options'  => [
                'under-10000' => [
                    'label'   => '1 万以下',
                    'value'   => 10000,
                    'compare' => '<',
                ],
                '10000-30000' => [
                    'label'   => '1 万～3 万',
                    'value'   => [10000, 30000],
                    'compare' => 'BETWEEN',
                ],
                '30000-50000' => [
                    'label'   => '3 万～5 万',
                    'value'   => [30000, 50000],
                    'compare' => 'BETWEEN',
                ],
                'over-50000' => [
                    'label'   => '5 万以上',
                    'value'   => 50000,
                    'compare' => '>=',
                ],
            ],
        ],
        'power_range' => [
            'label'    => '功率范围',
            'meta_key' => 'product_power',
            'options'  => [
                'under-3' => [
                    'label'   => '3kW 以下',
                    'value'   => 3,
                    'compare' => '<',
                ],
                '3-5' => [
                    'label'   => '3～5kW',
                    'value'   => [3, 5],
                    'compare' => 'BETWEEN',
                ],
                '5-10' => [
                    'label'   => '5～10kW',
                    'value'   => [5, 10],
                    'compare' => 'BETWEEN',
                ],
                'over-10' => [
                    'label'   => '10kW 以上',
                    'value'   => 10,
                    'compare' => '>=',
                ],
            ],
        ],
    ];
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
function pfl_get_filter_values(string $key): array
{
    $config = pfl_get_taxonomy_filter_config();

    if (! isset($config[$key]) || ! isset($_GET[$key])) {
        return [];
    }

    $values = (array) wp_unslash($_GET[$key]);
    $values = array_map('sanitize_title', $values);
    $values = array_values(array_unique(array_filter($values)));
    $values = array_slice($values, 0, 20);

    $allowed = pfl_get_allowed_taxonomy_slugs(
        $config[$key]['taxonomy']
    );

    return array_values(array_intersect($values, $allowed));
}


/**
 * 十、读取并验证 GET 单值参数。
 */
function pfl_get_filter_value(string $key): string
{
    if (! isset($_GET[$key]) || is_array($_GET[$key])) {
        return '';
    }

    $value = sanitize_key(wp_unslash($_GET[$key]));
    $range_config = pfl_get_range_filter_config();

    if (isset($range_config[$key])) {
        return isset($range_config[$key]['options'][$value])
            ? $value
            : '';
    }

    if ('sort' === $key) {
        $sort_options = pfl_get_sort_options();

        return array_key_exists($value, $sort_options)
            ? $value
            : '';
    }

    return '';
}


/**
 * 十一、统计当前已经应用的筛选条件数量。
 */
function pfl_get_applied_filter_count(): int
{
    $count = 0;

    foreach (array_keys(pfl_get_taxonomy_filter_config()) as $key) {
        $count += count(pfl_get_filter_values($key));
    }

    foreach (array_keys(pfl_get_range_filter_config()) as $key) {
        if (pfl_get_filter_value($key)) {
            $count++;
        }
    }

    return $count;
}


function pfl_has_active_product_filters(): bool
{
    return pfl_get_applied_filter_count() > 0;
}


/**
 * 十二、使用 pre_get_posts 修改产品主查询。
 *
 * 分类页面原本已经包含 product_category 条件。
 * 这里继续追加品牌、电压、功能、价格、功率和排序条件。
 */
function pfl_filter_product_main_query(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if (
        ! $query->is_post_type_archive('product')
        && ! $query->is_tax('product_category')
    ) {
        return;
    }

    $query->set('post_type', 'product');
    $query->set('posts_per_page', 9);

    $tax_query = (array) $query->get('tax_query');
    $has_tax_filters = false;

    foreach (pfl_get_taxonomy_filter_config() as $key => $filter) {
        $values = pfl_get_filter_values($key);

        if (empty($values)) {
            continue;
        }

        $tax_query[] = [
            'taxonomy' => $filter['taxonomy'],
            'field'    => 'slug',
            'terms'    => $values,
            'operator' => $filter['operator'],
        ];

        $has_tax_filters = true;
    }

    if ($has_tax_filters) {
        if (! isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }

        $query->set('tax_query', $tax_query);
    }

    $meta_query = (array) $query->get('meta_query');
    $has_meta_filters = false;

    foreach (pfl_get_range_filter_config() as $key => $filter) {
        $selected = pfl_get_filter_value($key);

        if (! $selected || ! isset($filter['options'][$selected])) {
            continue;
        }

        $option = $filter['options'][$selected];

        $meta_query[] = [
            'key'     => $filter['meta_key'],
            'value'   => $option['value'],
            'type'    => 'NUMERIC',
            'compare' => $option['compare'],
        ];

        $has_meta_filters = true;
    }

    if ($has_meta_filters) {
        if (! isset($meta_query['relation'])) {
            $meta_query['relation'] = 'AND';
        }

        $query->set('meta_query', $meta_query);
    }

    switch (pfl_get_filter_value('sort')) {
        case 'price-asc':
            $query->set('meta_key', 'product_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
            break;

        case 'price-desc':
            $query->set('meta_key', 'product_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
            break;

        case 'title':
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
            break;

        default:
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
            break;
    }
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
function pfl_get_pagination_filter_args(): array
{
    $args = [];

    foreach (array_keys(pfl_get_taxonomy_filter_config()) as $key) {
        $values = pfl_get_filter_values($key);

        if (! empty($values)) {
            $args[$key] = $values;
        }
    }

    foreach (array_keys(pfl_get_range_filter_config()) as $key) {
        $value = pfl_get_filter_value($key);

        if ($value) {
            $args[$key] = $value;
        }
    }

    $sort = pfl_get_filter_value('sort');

    if ($sort) {
        $args['sort'] = $sort;
    }

    return $args;
}


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
    if (! current_user_can('manage_options')) {
        return;
    }

    $message = '';

    if (
        isset($_POST['pfl_import_demo'])
        && check_admin_referer('pfl_import_demo_data')
    ) {
        $result  = pfl_import_demo_data();
        $message = sprintf(
            '导入完成：创建或更新了 %1$d 个分类项，新增了 %2$d 个产品。',
            (int) $result['terms'],
            (int) $result['products']
        );
    }
    ?>
    <div class="wrap">
        <h1>产品导航筛选实验室：演示数据</h1>

        <?php if ($message) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <p>
            点击下面按钮，将创建多层级产品分类、品牌、电压、功能特点及 12 条演示产品。
            已存在的同名分类不会重复创建，已有演示产品也不会重复插入。
        </p>

        <form method="post">
            <?php wp_nonce_field('pfl_import_demo_data'); ?>

            <p>
                <button
                    class="button button-primary button-hero"
                    name="pfl_import_demo"
                    type="submit"
                    value="1"
                >
                    一键导入演示数据
                </button>
            </p>
        </form>

        <hr>

        <h2>导入后查看</h2>
        <p>
            <a
                class="button"
                href="<?php echo esc_url(get_post_type_archive_link('product')); ?>"
                target="_blank"
            >
                打开产品归档页
            </a>
        </p>

        <h2>学习重点</h2>
        <ol>
            <li><code>functions.php</code> 中注册产品类型与分类法。</li>
            <li><code>pfl_get_category_navigation_levels()</code> 生成逐级导航数据。</li>
            <li><code>pfl_filter_product_main_query()</code> 修改主查询。</li>
            <li><code>template-parts/product/filter-form.php</code> 输出 GET 筛选表单。</li>
            <li><code>taxonomy-product_category.php</code> 组合分类导航、筛选器和主循环。</li>
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
    $term_count    = 0;
    $product_count = 0;

    $industrial = pfl_ensure_term('工业设备', 'product_category');
    $commercial = pfl_ensure_term('商用设备', 'product_category');
    $parts      = pfl_ensure_term('零部件', 'product_category');

    $packaging = pfl_ensure_term('包装设备', 'product_category', $industrial);
    $conveying = pfl_ensure_term('输送设备', 'product_category', $industrial);
    $cleaning  = pfl_ensure_term('清洗设备', 'product_category', $industrial);

    $vacuum = pfl_ensure_term('真空包装机', 'product_category', $packaging);
    $sealer = pfl_ensure_term('自动封口机', 'product_category', $packaging);
    $shrink = pfl_ensure_term('热收缩机', 'product_category', $packaging);

    $kitchen = pfl_ensure_term('厨房设备', 'product_category', $commercial);
    $sanitary = pfl_ensure_term('商用清洁设备', 'product_category', $commercial);

    $motors = pfl_ensure_term('电机', 'product_category', $parts);
    $sensors = pfl_ensure_term('传感器', 'product_category', $parts);

    $term_ids = array_filter(
        [
            $industrial,
            $commercial,
            $parts,
            $packaging,
            $conveying,
            $cleaning,
            $vacuum,
            $sealer,
            $shrink,
            $kitchen,
            $sanitary,
            $motors,
            $sensors,
        ]
    );

    $term_count += count($term_ids);

    $brand_names = ['华东机械', '蓝海设备', '启航工业', '新锐科技'];
    $voltage_names = ['220V', '380V', '定制电压'];
    $feature_names = ['全自动', '触屏控制', '不锈钢机身', '支持定制', '节能型'];

    foreach ($brand_names as $name) {
        if (pfl_ensure_term($name, 'product_brand')) {
            $term_count++;
        }
    }

    foreach ($voltage_names as $name) {
        if (pfl_ensure_term($name, 'product_voltage')) {
            $term_count++;
        }
    }

    foreach ($feature_names as $name) {
        if (pfl_ensure_term($name, 'product_feature')) {
            $term_count++;
        }
    }

    $products = [
        [
            'title'    => '双室真空包装机',
            'category' => $vacuum,
            'brand'    => '华东机械',
            'voltage'  => '380V',
            'features' => ['全自动', '触屏控制', '不锈钢机身'],
            'model'    => 'VP-600D',
            'price'    => 46800,
            'power'    => 5.5,
        ],
        [
            'title'    => '台式真空包装机',
            'category' => $vacuum,
            'brand'    => '蓝海设备',
            'voltage'  => '220V',
            'features' => ['节能型', '不锈钢机身'],
            'model'    => 'VP-320',
            'price'    => 8800,
            'power'    => 1.2,
        ],
        [
            'title'    => '连续式真空包装机',
            'category' => $vacuum,
            'brand'    => '启航工业',
            'voltage'  => '380V',
            'features' => ['全自动', '触屏控制', '支持定制'],
            'model'    => 'VP-1000C',
            'price'    => 69800,
            'power'    => 11,
        ],
        [
            'title'    => '自动连续封口机',
            'category' => $sealer,
            'brand'    => '新锐科技',
            'voltage'  => '220V',
            'features' => ['全自动', '节能型'],
            'model'    => 'FS-980',
            'price'    => 12800,
            'power'    => 2.2,
        ],
        [
            'title'    => '重型自动封口机',
            'category' => $sealer,
            'brand'    => '华东机械',
            'voltage'  => '380V',
            'features' => ['触屏控制', '支持定制'],
            'model'    => 'FS-1500',
            'price'    => 35800,
            'power'    => 6.5,
        ],
        [
            'title'    => '热收缩包装机',
            'category' => $shrink,
            'brand'    => '蓝海设备',
            'voltage'  => '380V',
            'features' => ['全自动', '不锈钢机身'],
            'model'    => 'BS-4525',
            'price'    => 28600,
            'power'    => 9.5,
        ],
        [
            'title'    => '皮带输送机',
            'category' => $conveying,
            'brand'    => '启航工业',
            'voltage'  => '380V',
            'features' => ['支持定制', '节能型'],
            'model'    => 'CV-800',
            'price'    => 19800,
            'power'    => 4,
        ],
        [
            'title'    => '滚筒输送机',
            'category' => $conveying,
            'brand'    => '华东机械',
            'voltage'  => '定制电压',
            'features' => ['支持定制'],
            'model'    => 'RC-1200',
            'price'    => 23800,
            'power'    => 3.5,
        ],
        [
            'title'    => '工业高压清洗机',
            'category' => $cleaning,
            'brand'    => '新锐科技',
            'voltage'  => '380V',
            'features' => ['节能型', '不锈钢机身'],
            'model'    => 'CL-500',
            'price'    => 32800,
            'power'    => 7.5,
        ],
        [
            'title'    => '商用洗碗机',
            'category' => $kitchen,
            'brand'    => '蓝海设备',
            'voltage'  => '380V',
            'features' => ['全自动', '触屏控制', '节能型'],
            'model'    => 'DW-860',
            'price'    => 52800,
            'power'    => 12,
        ],
        [
            'title'    => '工业伺服电机',
            'category' => $motors,
            'brand'    => '启航工业',
            'voltage'  => '220V',
            'features' => ['节能型'],
            'model'    => 'SM-3K',
            'price'    => 6800,
            'power'    => 3,
        ],
        [
            'title'    => '智能光电传感器',
            'category' => $sensors,
            'brand'    => '新锐科技',
            'voltage'  => '定制电压',
            'features' => ['支持定制'],
            'model'    => 'PS-220',
            'price'    => 2600,
            'power'    => 0.2,
        ],
    ];

    foreach ($products as $item) {
        $existing = get_page_by_title(
            $item['title'],
            OBJECT,
            'product'
        );

        if ($existing instanceof WP_Post) {
            continue;
        }

        $post_id = wp_insert_post(
            [
                'post_type'    => 'product',
                'post_status'  => 'publish',
                'post_title'   => $item['title'],
                'post_excerpt' => '用于演示层级分类导航与多条件筛选的示例产品。',
                'post_content' => sprintf(
                    '<p>%1$s 是本主题自动创建的演示产品。你可以在后台修改其分类、品牌、电压、功能、价格和功率，然后观察前台筛选结果如何变化。</p>',
                    esc_html($item['title'])
                ),
            ],
            true
        );

        if (is_wp_error($post_id)) {
            continue;
        }

        wp_set_object_terms(
            $post_id,
            [(int) $item['category']],
            'product_category'
        );

        wp_set_object_terms(
            $post_id,
            [$item['brand']],
            'product_brand'
        );

        wp_set_object_terms(
            $post_id,
            [$item['voltage']],
            'product_voltage'
        );

        wp_set_object_terms(
            $post_id,
            $item['features'],
            'product_feature'
        );

        update_post_meta($post_id, 'product_model', $item['model']);
        update_post_meta($post_id, 'product_price', $item['price']);
        update_post_meta($post_id, 'product_power', $item['power']);

        $product_count++;
    }

    flush_rewrite_rules(false);

    return [
        'terms'    => $term_count,
        'products' => $product_count,
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
