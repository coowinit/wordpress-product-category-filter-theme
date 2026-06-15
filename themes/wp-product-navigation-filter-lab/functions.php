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
 * 七、读取 GET 数组参数。
 */
function pfl_get_filter_values(string $key): array
{
    if (! isset($_GET[$key])) {
        return [];
    }

    $values = (array) wp_unslash($_GET[$key]);
    $values = array_map('sanitize_title', $values);
    $values = array_values(array_unique(array_filter($values)));

    return array_slice($values, 0, 20);
}


/**
 * 八、读取 GET 单值参数。
 */
function pfl_get_filter_value(string $key): string
{
    if (! isset($_GET[$key])) {
        return '';
    }

    return sanitize_key(wp_unslash($_GET[$key]));
}


/**
 * 九、使用 pre_get_posts 修改产品主查询。
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

    $brands   = pfl_get_filter_values('brand');
    $voltages = pfl_get_filter_values('voltage');
    $features = pfl_get_filter_values('feature');

    $tax_query = (array) $query->get('tax_query');

    if (! isset($tax_query['relation'])) {
        $tax_query['relation'] = 'AND';
    }

    if (! empty($brands)) {
        $tax_query[] = [
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $brands,
            'operator' => 'IN',
        ];
    }

    if (! empty($voltages)) {
        $tax_query[] = [
            'taxonomy' => 'product_voltage',
            'field'    => 'slug',
            'terms'    => $voltages,
            'operator' => 'IN',
        ];
    }

    if (! empty($features)) {
        $tax_query[] = [
            'taxonomy' => 'product_feature',
            'field'    => 'slug',
            'terms'    => $features,
            'operator' => 'AND',
        ];
    }

    if (count($tax_query) > 1) {
        $query->set('tax_query', $tax_query);
    }

    $meta_query = (array) $query->get('meta_query');

    if (! isset($meta_query['relation'])) {
        $meta_query['relation'] = 'AND';
    }

    $price_range = pfl_get_filter_value('price_range');

    $price_conditions = [
        'under-10000' => [
            'key'     => 'product_price',
            'value'   => 10000,
            'type'    => 'NUMERIC',
            'compare' => '<',
        ],
        '10000-30000' => [
            'key'     => 'product_price',
            'value'   => [10000, 30000],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ],
        '30000-50000' => [
            'key'     => 'product_price',
            'value'   => [30000, 50000],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ],
        'over-50000' => [
            'key'     => 'product_price',
            'value'   => 50000,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        ],
    ];

    if (isset($price_conditions[$price_range])) {
        $meta_query[] = $price_conditions[$price_range];
    }

    $power_range = pfl_get_filter_value('power_range');

    $power_conditions = [
        'under-3' => [
            'key'     => 'product_power',
            'value'   => 3,
            'type'    => 'NUMERIC',
            'compare' => '<',
        ],
        '3-5' => [
            'key'     => 'product_power',
            'value'   => [3, 5],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ],
        '5-10' => [
            'key'     => 'product_power',
            'value'   => [5, 10],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ],
        'over-10' => [
            'key'     => 'product_power',
            'value'   => 10,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        ],
    ];

    if (isset($power_conditions[$power_range])) {
        $meta_query[] = $power_conditions[$power_range];
    }

    if (count($meta_query) > 1) {
        $query->set('meta_query', $meta_query);
    }

    $sort = pfl_get_filter_value('sort');

    switch ($sort) {
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
 * 十、获得当前产品分类导航的所有层级。
 */
function pfl_get_category_navigation_levels(): array
{
    $taxonomy = 'product_category';
    $levels   = [];

    $root_terms = get_terms(
        [
            'taxonomy'   => $taxonomy,
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]
    );

    if (is_wp_error($root_terms) || empty($root_terms)) {
        return [];
    }

    if (! is_tax($taxonomy)) {
        return [
            [
                'level'     => 1,
                'active_id' => 0,
                'terms'     => $root_terms,
            ],
        ];
    }

    $current_term = get_queried_object();

    if (
        ! $current_term instanceof WP_Term
        || $current_term->taxonomy !== $taxonomy
    ) {
        return [];
    }

    $ancestor_ids = get_ancestors(
        $current_term->term_id,
        $taxonomy,
        'taxonomy'
    );

    $ancestor_ids = array_map(
        'intval',
        array_reverse($ancestor_ids)
    );

    $path_ids = array_merge(
        $ancestor_ids,
        [(int) $current_term->term_id]
    );

    $levels[] = [
        'level'     => 1,
        'active_id' => $path_ids[0] ?? 0,
        'terms'     => $root_terms,
    ];

    foreach ($path_ids as $index => $path_term_id) {
        $children = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'parent'     => $path_term_id,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        if (is_wp_error($children) || empty($children)) {
            break;
        }

        $levels[] = [
            'level'     => $index + 2,
            'active_id' => $path_ids[$index + 1] ?? 0,
            'terms'     => $children,
        ];
    }

    return $levels;
}


/**
 * 十一、筛选表单 action 地址。
 */
function pfl_get_product_filter_action(): string
{
    if (is_tax('product_category')) {
        $current_term = get_queried_object();

        if ($current_term instanceof WP_Term) {
            $url = get_term_link($current_term);

            if (! is_wp_error($url)) {
                return $url;
            }
        }
    }

    $archive_url = get_post_type_archive_link('product');

    return $archive_url ?: home_url('/');
}


/**
 * 十二、获取筛选分页需要保留的参数。
 */
function pfl_get_pagination_filter_args(): array
{
    $allowed = [
        'brand',
        'voltage',
        'feature',
        'price_range',
        'power_range',
        'sort',
    ];

    $args = [];

    foreach ($allowed as $key) {
        if (! isset($_GET[$key])) {
            continue;
        }

        if (is_array($_GET[$key])) {
            $args[$key] = array_map(
                'sanitize_title',
                (array) wp_unslash($_GET[$key])
            );
        } else {
            $args[$key] = sanitize_key(wp_unslash($_GET[$key]));
        }
    }

    return $args;
}


/**
 * 十三、格式化产品价格。
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
