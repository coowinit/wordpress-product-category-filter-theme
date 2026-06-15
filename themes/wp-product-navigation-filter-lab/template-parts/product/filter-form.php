<?php
/**
 * 产品多条件筛选表单。
 */

$filter_groups = [
    [
        'key'      => 'brand',
        'label'    => '产品品牌',
        'taxonomy' => 'product_brand',
        'mode'     => 'multi',
    ],
    [
        'key'      => 'voltage',
        'label'    => '产品电压',
        'taxonomy' => 'product_voltage',
        'mode'     => 'multi',
    ],
    [
        'key'      => 'feature',
        'label'    => '功能特点',
        'taxonomy' => 'product_feature',
        'mode'     => 'multi',
    ],
];

$price_ranges = [
    'under-10000' => '1 万以下',
    '10000-30000' => '1 万～3 万',
    '30000-50000' => '3 万～5 万',
    'over-50000'  => '5 万以上',
];

$power_ranges = [
    'under-3' => '3kW 以下',
    '3-5'     => '3～5kW',
    '5-10'    => '5～10kW',
    'over-10' => '10kW 以上',
];

$current_price = pfl_get_filter_value('price_range');
$current_power = pfl_get_filter_value('power_range');
$current_sort  = pfl_get_filter_value('sort');
?>

<section class="product-filter-panel">
    <header class="product-filter-panel__header">
        <div>
            <h2>产品多条件筛选</h2>
            <p>
                各筛选维度彼此独立；品牌和电压组内是“或”，功能特点组内是“同时具备”。
            </p>
        </div>

        <strong class="product-filter-panel__count">
            <?php echo esc_html((string) $GLOBALS['wp_query']->found_posts); ?>
            <small>个匹配产品</small>
        </strong>
    </header>

    <form
        id="productFilterForm"
        class="product-filter-form"
        method="get"
        action="<?php echo esc_url(pfl_get_product_filter_action()); ?>"
    >
        <div id="selectedFilterBar" class="selected-filter-bar" hidden>
            <strong>已选条件</strong>
            <div id="selectedFilterTags" class="selected-filter-tags"></div>
            <button id="clearAllFilters" type="button">清空全部</button>
        </div>

        <?php foreach ($filter_groups as $group) : ?>
            <?php
            $terms = get_terms(
                [
                    'taxonomy'   => $group['taxonomy'],
                    'hide_empty' => false,
                    'orderby'    => 'name',
                ]
            );

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $selected_values = pfl_get_filter_values($group['key']);
            ?>

            <fieldset class="filter-row">
                <legend><?php echo esc_html($group['label']); ?></legend>

                <div class="filter-options">
                    <?php foreach ($terms as $term) : ?>
                        <?php
                        $input_id = 'filter-' . $group['key'] . '-' . $term->term_id;
                        ?>

                        <label
                            class="filter-option<?php
                            echo in_array(
                                $term->slug,
                                $selected_values,
                                true
                            ) ? ' is-selected' : '';
                            ?>"
                            for="<?php echo esc_attr($input_id); ?>"
                        >
                            <input
                                id="<?php echo esc_attr($input_id); ?>"
                                name="<?php echo esc_attr($group['key']); ?>[]"
                                type="checkbox"
                                value="<?php echo esc_attr($term->slug); ?>"
                                data-filter-label="<?php echo esc_attr($group['label']); ?>"
                                data-option-label="<?php echo esc_attr($term->name); ?>"
                                <?php
                                checked(
                                    in_array(
                                        $term->slug,
                                        $selected_values,
                                        true
                                    )
                                );
                                ?>
                            >
                            <span><?php echo esc_html($term->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <fieldset class="filter-row">
            <legend>价格范围</legend>

            <div class="filter-options">
                <?php foreach ($price_ranges as $value => $label) : ?>
                    <?php $input_id = 'price-' . $value; ?>

                    <label
                        class="filter-option<?php
                        echo $current_price === $value ? ' is-selected' : '';
                        ?>"
                        for="<?php echo esc_attr($input_id); ?>"
                    >
                        <input
                            id="<?php echo esc_attr($input_id); ?>"
                            name="price_range"
                            type="radio"
                            value="<?php echo esc_attr($value); ?>"
                            data-filter-label="价格范围"
                            data-option-label="<?php echo esc_attr($label); ?>"
                            <?php checked($current_price, $value); ?>
                        >
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="filter-row">
            <legend>功率范围</legend>

            <div class="filter-options">
                <?php foreach ($power_ranges as $value => $label) : ?>
                    <?php $input_id = 'power-' . $value; ?>

                    <label
                        class="filter-option<?php
                        echo $current_power === $value ? ' is-selected' : '';
                        ?>"
                        for="<?php echo esc_attr($input_id); ?>"
                    >
                        <input
                            id="<?php echo esc_attr($input_id); ?>"
                            name="power_range"
                            type="radio"
                            value="<?php echo esc_attr($value); ?>"
                            data-filter-label="功率范围"
                            data-option-label="<?php echo esc_attr($label); ?>"
                            <?php checked($current_power, $value); ?>
                        >
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="filter-toolbar">
            <label>
                排序：
                <select name="sort">
                    <option value="" <?php selected($current_sort, ''); ?>>
                        最新发布
                    </option>
                    <option value="price-asc" <?php selected($current_sort, 'price-asc'); ?>>
                        价格从低到高
                    </option>
                    <option value="price-desc" <?php selected($current_sort, 'price-desc'); ?>>
                        价格从高到低
                    </option>
                    <option value="title" <?php selected($current_sort, 'title'); ?>>
                        标题排序
                    </option>
                </select>
            </label>

            <div class="filter-toolbar__actions">
                <a class="filter-reset-link" href="<?php echo esc_url(pfl_get_product_filter_action()); ?>">
                    重置筛选
                </a>

                <button class="filter-submit-button" type="submit">
                    查看筛选结果
                </button>
            </div>
        </div>
    </form>
</section>
