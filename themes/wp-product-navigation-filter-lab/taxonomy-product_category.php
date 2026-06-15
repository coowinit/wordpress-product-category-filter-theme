<?php
/**
 * 产品分类归档页。
 *
 * 页面结构：
 * 1. 面包屑和返回上一级
 * 2. 分类图片、标题与顶部内容
 * 3. 多层级分类导航
 * 4. 动态多条件筛选
 * 5. AJAX/GET 共用的产品结果区
 * 6. 分类底部补充内容
 */

get_header();

get_template_part(
    'template-parts/product/breadcrumbs'
);

get_template_part(
    'template-parts/product/category-hero'
);

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
        'query'       => $GLOBALS['wp_query'],
        'context'     => 'taxonomy',
        'base_url'    => pfl_get_product_filter_action(),
        'source'      => $_GET,
        'active_keys' => pfl_get_active_filter_keys(),
    ]
);

get_template_part(
    'template-parts/product/category-bottom-content'
);

get_footer();
