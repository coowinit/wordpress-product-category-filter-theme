<?php
/**
 * 最终兜底模板。
 */

get_header();
?>

<header class="archive-heading">
    <h1><?php bloginfo('name'); ?></h1>
</header>

<?php if (have_posts()) : ?>
    <div class="product-grid">
        <?php while (have_posts()) : ?>
            <?php the_post(); ?>

            <article <?php post_class('product-card'); ?>>
                <div class="product-card__body">
                    <h2 class="product-card__title">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>

                    <?php the_excerpt(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(); ?>
<?php else : ?>
    <div class="empty-products">当前没有内容。</div>
<?php endif; ?>

<?php
get_footer();
