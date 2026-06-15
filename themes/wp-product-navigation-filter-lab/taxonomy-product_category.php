<?php
/**
 * 产品分类归档页。
 *
 * 页面结构：
 * 1. 面包屑和返回上一级
 * 2. 当前分类标题
 * 3. 多层级分类导航
 * 4. 多条件筛选表单
 * 5. AJAX/GET 共用的产品结果区
 */

get_header();

get_template_part(
    'template-parts/product/breadcrumbs'
);
?>

<header class="archive-heading">
    <h1><?php single_term_title(); ?></h1>

    <div class="archive-description">
        <?php if (term_description()) : ?>
            <?php echo wp_kses_post(term_description()); ?>
        <?php else : ?>
            当前页面是产品分类归档。分类导航决定产品目录范围，多条件筛选继续缩小结果。
        <?php endif; ?>
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
        'context'  => 'taxonomy',
        'base_url' => pfl_get_product_filter_action(),
        'source'   => $_GET,
    ]
);

get_footer();
