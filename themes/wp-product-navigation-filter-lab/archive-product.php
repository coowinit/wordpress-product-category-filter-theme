<?php
/**
 * 全部产品归档页。
 */

get_header();

get_template_part(
    'template-parts/product/breadcrumbs'
);
?>

<header class="archive-heading">
    <h1>全部产品</h1>

    <div class="archive-description">
        这里显示全部产品。分类导航负责进入具体目录，多条件筛选负责缩小当前产品结果。
    </div>
</header>

<?php
get_template_part(
    'template-parts/product/category-navigation'
);

get_template_part(
    'template-parts/product/filter-form'
);

get_template_part(
    'template-parts/product/results',
    null,
    [
        'query'    => $GLOBALS['wp_query'],
        'context'  => 'archive',
        'base_url' => pfl_get_product_filter_action(),
        'source'   => $_GET,
        'active_keys' => pfl_get_active_filter_keys(),
    ]
);

get_footer();
