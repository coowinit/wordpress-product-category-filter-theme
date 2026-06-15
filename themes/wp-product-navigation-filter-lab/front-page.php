<?php
/**
 * 主题学习首页。
 */

get_header();

$product_archive = get_post_type_archive_link('product');
?>

<section class="hero">
    <h1>用一个主题掌握产品分类导航与多条件筛选</h1>

    <p>
        本主题把自定义文章类型、层级分类法、产品属性、GET 筛选、
        pre_get_posts、tax_query、meta_query、参数验证、面包屑、后台管理列和分页保留参数连接成一个完整示例。
    </p>

    <div class="hero__actions">
        <a class="button button--light" href="<?php echo esc_url($product_archive); ?>">
            查看产品归档页
        </a>

        <?php if (current_user_can('manage_options')) : ?>
            <a
                class="button button--outline"
                href="<?php echo esc_url(admin_url('themes.php?page=pfl-demo-data')); ?>"
            >
                导入演示数据
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="learning-section">
    <h2>三个核心学习入口</h2>

    <div class="learning-cards">
        <article class="learning-card">
            <h3>1. 层级分类导航</h3>
            <p>
                查看 functions.php 中的 pfl_get_category_navigation_levels()，
                理解当前分类、祖先路径和直接子分类如何组合。
            </p>
        </article>

        <article class="learning-card">
            <h3>2. 多条件筛选</h3>
            <p>
                查看 filter-form.php 和 URL 查询参数，
                理解多个 taxonomy 条件与数值 meta 条件如何提交。
            </p>
        </article>

        <article class="learning-card">
            <h3>3. 主查询修改</h3>
            <p>
                查看 pre_get_posts 中的 pfl_filter_product_main_query()，
                理解为什么分类模板不需要重新写 WP_Query。
            </p>
        </article>
    </div>
</section>

<?php
get_footer();
