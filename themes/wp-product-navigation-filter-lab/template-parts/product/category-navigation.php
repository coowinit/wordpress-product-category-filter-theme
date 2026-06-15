<?php
/**
 * 产品多层级分类导航。
 */

$levels = pfl_get_category_navigation_levels();

if (empty($levels)) {
    return;
}

$level_names = [
    1 => '一级分类',
    2 => '二级分类',
    3 => '三级分类',
    4 => '四级分类',
    5 => '五级分类',
];

$is_product_archive = is_post_type_archive('product');
?>

<nav class="category-navigation" aria-label="产品分类导航">
    <div class="category-navigation__heading">
        <div>
            <h2>产品分类导航</h2>
            <p>祖先路径使用浅色标记，当前分类使用深色标记，并继续显示下一层分类。</p>
        </div>

        <a
            class="category-navigation__all<?php echo $is_product_archive ? ' is-active' : ''; ?>"
            href="<?php echo esc_url(get_post_type_archive_link('product')); ?>"
            <?php if ($is_product_archive) : ?>
                aria-current="page"
            <?php endif; ?>
        >
            全部产品
        </a>
    </div>

    <div class="category-navigation__levels">
        <?php foreach ($levels as $level) : ?>
            <section class="category-level">
                <div class="category-level__name">
                    <span><?php echo esc_html((string) $level['level']); ?></span>
                    <?php
                    echo esc_html(
                        $level_names[$level['level']]
                        ?? '第 ' . $level['level'] . ' 级分类'
                    );
                    ?>
                </div>

                <ul class="category-level__list">
                    <?php foreach ($level['terms'] as $term) : ?>
                        <?php
                        $term_link = get_term_link($term);

                        if (is_wp_error($term_link)) {
                            continue;
                        }

                        $term_id = (int) $term->term_id;
                        $is_current = $term_id === (int) $level['current_id'];
                        $is_path = (
                            ! $is_current
                            && $term_id === (int) $level['active_id']
                        );
                        $has_children = pfl_product_category_has_children($term_id);

                        $classes = ['category-level__link'];

                        if ($is_path) {
                            $classes[] = 'is-path';
                        }

                        if ($is_current) {
                            $classes[] = 'is-current';
                        }

                        if ($has_children) {
                            $classes[] = 'has-children';
                        }
                        ?>

                        <li>
                            <a
                                class="<?php echo esc_attr(implode(' ', $classes)); ?>"
                                href="<?php echo esc_url($term_link); ?>"
                                <?php if ($is_current) : ?>
                                    aria-current="page"
                                <?php endif; ?>
                            >
                                <?php echo esc_html($term->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
</nav>
