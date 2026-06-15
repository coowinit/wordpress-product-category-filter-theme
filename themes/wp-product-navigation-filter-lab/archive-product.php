<?php
/**
 * 全部产品归档页。
 */

get_header();
?>

<header class="archive-heading">
    <h1>全部产品</h1>

    <div class="archive-description">
        这里显示全部产品。第一行导航是产品一级分类，多条件筛选会继续缩小产品范围。
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

<section class="product-results">
    <div class="product-results__header">
        <strong>
            共找到 <?php echo esc_html((string) $GLOBALS['wp_query']->found_posts); ?> 个产品
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
                    'total'    => $GLOBALS['wp_query']->max_num_pages,
                    'current'  => max(1, get_query_var('paged')),
                    'type'     => 'list',
                    'add_args' => pfl_get_pagination_filter_args(),
                    'prev_text' => '上一页',
                    'next_text' => '下一页',
                ]
            )
        );
        ?>
    <?php else : ?>
        <div class="empty-products">
            没有符合当前筛选条件的产品，请减少条件后重试。
        </div>
    <?php endif; ?>
</section>

<?php
get_footer();
