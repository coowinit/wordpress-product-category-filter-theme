<?php
/**
 * 产品分类归档页。
 *
 * 页面结构：
 * 1. 面包屑和返回上一级
 * 2. 当前分类标题
 * 3. 多层级分类导航
 * 4. 多条件筛选表单
 * 5. 当前分类主循环
 */

get_header();

$current_term = get_queried_object();

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
?>

<section id="product-results" class="product-results" tabindex="-1">
    <div class="product-results__header">
        <strong>
            当前分类共找到
            <?php echo esc_html((string) $GLOBALS['wp_query']->found_posts); ?>
            个产品
        </strong>
    </div>

    <?php if (have_posts()) : ?>
        <div class="product-grid">
            <?php while (have_posts()) : ?>
                <?php
                the_post();

                get_template_part(
                    'template-parts/product/card'
                );
                ?>
            <?php endwhile; ?>
        </div>

        <?php
        echo wp_kses_post(
            paginate_links(
                [
                    'total'     => $GLOBALS['wp_query']->max_num_pages,
                    'current'   => max(1, get_query_var('paged')),
                    'type'      => 'list',
                    'add_args'  => pfl_get_pagination_filter_args(),
                    'prev_text' => '上一页',
                    'next_text' => '下一页',
                ]
            )
        );
        ?>
    <?php else : ?>
        <?php
        get_template_part(
            'template-parts/product/no-results'
        );
        ?>
    <?php endif; ?>
</section>

<?php
get_footer();
