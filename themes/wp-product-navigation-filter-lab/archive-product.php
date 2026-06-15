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
?>

<section id="product-results" class="product-results" tabindex="-1">
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
