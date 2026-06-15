<?php
/**
 * 产品多条件筛选表单。
 */

$taxonomy_filters = pfl_get_taxonomy_filter_config();
$range_filters    = pfl_get_range_filter_config();
$sort_options     = pfl_get_sort_options();
$current_sort     = pfl_get_filter_value('sort');
$applied_count    = pfl_get_applied_filter_count();
$form_action      = pfl_get_product_filter_action() . '#product-results';
?>

<section id="product-filter-panel" class="product-filter-panel">
    <header class="product-filter-panel__header">
        <div>
            <h2>产品多条件筛选</h2>
            <p>
                各筛选维度彼此独立；品牌和电压组内是“或”，功能特点组内是“同时具备”。
            </p>
        </div>

        <strong class="product-filter-panel__count">
            <?php echo esc_html((string) $GLOBALS['wp_query']->found_posts); ?>
            <small>个当前结果</small>
        </strong>
    </header>

    <form
        id="productFilterForm"
        class="product-filter-form"
        method="get"
        action="<?php echo esc_url($form_action); ?>"
    >
        <div
            id="filterStateBar"
            class="filter-state-bar"
            data-applied-count="<?php echo esc_attr((string) $applied_count); ?>"
        >
            <div class="filter-state-bar__message">
                <strong id="filterStateTitle">
                    <?php if ($applied_count > 0) : ?>
                        当前已应用 <?php echo esc_html((string) $applied_count); ?> 个筛选条件
                    <?php else : ?>
                        当前未应用筛选条件
                    <?php endif; ?>
                </strong>

                <span id="filterStateHint">
                    页面中的产品数量与列表已经按照当前 URL 参数查询。
                </span>
            </div>

            <div id="selectedFilterTags" class="selected-filter-tags"></div>

            <button id="clearAllFilters" type="button">
                清空全部
            </button>
        </div>

        <?php foreach ($taxonomy_filters as $key => $filter) : ?>
            <?php
            $terms = get_terms(
                [
                    'taxonomy'   => $filter['taxonomy'],
                    'hide_empty' => false,
                    'orderby'    => 'name',
                ]
            );

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $selected_values = pfl_get_filter_values($key);
            ?>

            <section class="filter-row" data-filter-group="<?php echo esc_attr($key); ?>">
                <div class="filter-row__heading">
                    <strong><?php echo esc_html($filter['label']); ?></strong>

                    <button
                        class="filter-group-clear"
                        type="button"
                        data-clear-filter-group="<?php echo esc_attr($key); ?>"
                        <?php echo empty($selected_values) ? 'hidden' : ''; ?>
                    >
                        清空本组
                    </button>
                </div>

                <div class="filter-options" role="group" aria-label="<?php echo esc_attr($filter['label']); ?>">
                    <?php foreach ($terms as $term) : ?>
                        <?php
                        $input_id = 'filter-' . $key . '-' . $term->term_id;
                        $is_selected = in_array(
                            $term->slug,
                            $selected_values,
                            true
                        );
                        ?>

                        <label
                            class="filter-option<?php echo $is_selected ? ' is-selected' : ''; ?>"
                            for="<?php echo esc_attr($input_id); ?>"
                        >
                            <input
                                id="<?php echo esc_attr($input_id); ?>"
                                name="<?php echo esc_attr($key); ?>[]"
                                type="checkbox"
                                value="<?php echo esc_attr($term->slug); ?>"
                                data-filter-label="<?php echo esc_attr($filter['label']); ?>"
                                data-option-label="<?php echo esc_attr($term->name); ?>"
                                <?php checked($is_selected); ?>
                            >
                            <span><?php echo esc_html($term->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php foreach ($range_filters as $key => $filter) : ?>
            <?php $selected_value = pfl_get_filter_value($key); ?>

            <section class="filter-row" data-filter-group="<?php echo esc_attr($key); ?>">
                <div class="filter-row__heading">
                    <strong><?php echo esc_html($filter['label']); ?></strong>

                    <button
                        class="filter-group-clear"
                        type="button"
                        data-clear-filter-group="<?php echo esc_attr($key); ?>"
                        <?php echo $selected_value ? '' : 'hidden'; ?>
                    >
                        清空本组
                    </button>
                </div>

                <div class="filter-options" role="radiogroup" aria-label="<?php echo esc_attr($filter['label']); ?>">
                    <?php foreach ($filter['options'] as $value => $option) : ?>
                        <?php
                        $input_id = $key . '-' . $value;
                        $is_selected = $selected_value === $value;
                        ?>

                        <label
                            class="filter-option<?php echo $is_selected ? ' is-selected' : ''; ?>"
                            for="<?php echo esc_attr($input_id); ?>"
                        >
                            <input
                                id="<?php echo esc_attr($input_id); ?>"
                                name="<?php echo esc_attr($key); ?>"
                                type="radio"
                                value="<?php echo esc_attr($value); ?>"
                                data-filter-label="<?php echo esc_attr($filter['label']); ?>"
                                data-option-label="<?php echo esc_attr($option['label']); ?>"
                                <?php checked($is_selected); ?>
                            >
                            <span><?php echo esc_html($option['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="filter-toolbar">
            <label class="filter-sort">
                <span>排序：</span>
                <select name="sort" data-filter-sort>
                    <?php foreach ($sort_options as $value => $label) : ?>
                        <option
                            value="<?php echo esc_attr($value); ?>"
                            <?php selected($current_sort, $value); ?>
                        >
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="filter-toolbar__actions">
                <a class="filter-reset-link" href="<?php echo esc_url(pfl_get_product_filter_action()); ?>">
                    重置筛选
                </a>

                <button id="applyFilterButton" class="filter-submit-button" type="submit">
                    查看筛选结果
                </button>
            </div>
        </div>
    </form>
</section>
