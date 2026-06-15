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
?>

<nav class="category-navigation" aria-label="产品分类导航">
    <div class="category-navigation__heading">
        <div>
            <h2>产品分类导航</h2>
            <p>沿当前分类路径逐级展开，同时保留已经经过的上级导航。</p>
        </div>

        <a
            class="category-navigation__all"
            href="<?php echo esc_url(get_post_type_archive_link('product')); ?>"
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

                        $is_active = (
                            (int) $term->term_id
                            ===
                            (int) $level['active_id']
                        );

                        $children = get_terms(
                            [
                                'taxonomy'   => 'product_category',
                                'parent'     => $term->term_id,
                                'hide_empty' => false,
                                'number'     => 1,
                                'fields'     => 'ids',
                            ]
                        );

                        $has_children = (
                            ! is_wp_error($children)
                            && ! empty($children)
                        );
                        ?>

                        <li>
                            <a
                                class="category-level__link<?php
                                echo $is_active ? ' is-active' : '';
                                echo $has_children ? ' has-children' : '';
                                ?>"
                                href="<?php echo esc_url($term_link); ?>"
                                <?php if ($is_active) : ?>
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
