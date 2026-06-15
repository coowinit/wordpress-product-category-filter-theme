<?php
/**
 * 动态产品筛选表单。
 *
 * 分类页面根据 term meta 选择筛选组；未设置时向上继承，最终回退全局默认方案。
 */

$current_term = pfl_get_current_product_category();
$term_id = $current_term ? (int) $current_term->term_id : 0;
$profile = pfl_resolve_filter_profile($current_term);
$active_filter_keys = $profile['keys'];
$schema = pfl_get_product_filter_schema();
$sort_options = pfl_get_sort_options();
$current_sort = pfl_get_filter_value('sort', null, $active_filter_keys);
$applied_count = pfl_get_applied_filter_count(null, $active_filter_keys);
$context = $current_term ? 'taxonomy' : 'archive';
$base_action = pfl_get_product_filter_action();
$form_action = $base_action . '#product-results';
$visible_group_count = 0;
$facet_state = pfl_get_faceted_filter_state(
    $_GET,
    $current_term,
    $active_filter_keys
);
$facets = $facet_state['facets'];
?>

<section id="product-filter-panel" class="product-filter-panel">
    <header class="product-filter-panel__header">
        <div>
            <div class="product-filter-panel__title-row">
                <h2>动态产品属性筛选</h2>
                <span class="ajax-filter-badge" aria-hidden="true">AJAX 自动更新</span>
                <span class="facet-count-badge" aria-hidden="true">联动计数</span>
                <span class="filter-profile-badge">
                    <?php echo esc_html($profile['source_label']); ?>
                </span>
            </div>
            <p>当前分类只显示与其业务相关的筛选维度；子分类可继承父级方案。</p>
        </div>

        <strong class="product-filter-panel__count">
            <span id="productFilterResultCount"><?php echo esc_html((string) $GLOBALS['wp_query']->found_posts); ?></span>
            <small>个当前结果</small>
        </strong>
    </header>

    <form
        id="productFilterForm"
        class="product-filter-form"
        method="get"
        action="<?php echo esc_url($form_action); ?>"
        data-ajax-context="<?php echo esc_attr($context); ?>"
        data-term-id="<?php echo esc_attr((string) $term_id); ?>"
        data-base-url="<?php echo esc_url($base_action); ?>"
    >
        <div id="filterStateBar" class="filter-state-bar" data-applied-count="<?php echo esc_attr((string) $applied_count); ?>">
            <div class="filter-state-bar__message">
                <strong id="filterStateTitle">
                    <?php echo $applied_count > 0
                        ? esc_html('当前已应用 ' . $applied_count . ' 个筛选条件')
                        : '当前未应用筛选条件'; ?>
                </strong>
                <span id="filterStateHint">结果、地址栏和筛选项数量保持同步；数量表示该选项在其他当前条件下可匹配的产品数。</span>
                <span
                    id="filterPerformance"
                    class="filter-performance"
                    hidden
                    aria-live="polite"
                ></span>
            </div>
            <div id="selectedFilterTags" class="selected-filter-tags"></div>
            <button id="clearAllFilters" type="button">清空全部</button>
        </div>

        <?php foreach ($active_filter_keys as $key) : ?>
            <?php
            if (! isset($schema[$key])) { continue; }
            $filter = $schema[$key];
            ?>

            <?php if ('taxonomy' === $filter['type']) : ?>
                <?php
                $options = pfl_get_filter_term_options($key, $term_id);
                if (empty($options)) { continue; }
                $selected_values = pfl_get_filter_values($key, null, $active_filter_keys);
                $visible_group_count++;
                ?>
                <section class="filter-row" data-filter-group="<?php echo esc_attr($key); ?>">
                    <div class="filter-row__heading">
                        <strong><?php echo esc_html($filter['label']); ?></strong>
                        <button class="filter-group-clear" type="button" data-clear-filter-group="<?php echo esc_attr($key); ?>" <?php echo empty($selected_values) ? 'hidden' : ''; ?>>清空本组</button>
                    </div>
                    <div class="filter-options" role="group" aria-label="<?php echo esc_attr($filter['label']); ?>">
                        <?php foreach ($options as $option) : ?>
                            <?php
                            $term = $option['term'];
                            $input_id = 'filter-' . $key . '-' . $term->term_id;
                            $is_selected = in_array($term->slug, $selected_values, true);
                            $facet_option = $facets[$key]['options'][$term->slug] ?? [
                                'count' => 0,
                                'disabled' => ! $is_selected,
                            ];
                            $is_disabled = (
                                ! $is_selected
                                && ! empty($facet_option['disabled'])
                            );
                            ?>
                            <label
                                class="filter-option<?php
                                echo $is_selected ? ' is-selected' : '';
                                echo $is_disabled ? ' is-disabled' : '';
                                ?>"
                                for="<?php echo esc_attr($input_id); ?>"
                                <?php if ($is_disabled) : ?>
                                    aria-disabled="true"
                                <?php endif; ?>
                            >
                                <input
                                    id="<?php echo esc_attr($input_id); ?>"
                                    name="<?php echo esc_attr($key); ?>[]"
                                    type="checkbox"
                                    value="<?php echo esc_attr($term->slug); ?>"
                                    data-filter-key="<?php echo esc_attr($key); ?>"
                                    data-option-value="<?php echo esc_attr($term->slug); ?>"
                                    data-filter-label="<?php echo esc_attr($filter['label']); ?>"
                                    data-option-label="<?php echo esc_attr($term->name); ?>"
                                    <?php checked($is_selected); ?>
                                    <?php disabled($is_disabled); ?>
                                >
                                <span><?php echo esc_html($term->name); ?></span>
                                <small
                                    class="filter-option__count"
                                    data-filter-count
                                ><?php echo esc_html((string) $facet_option['count']); ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

            <?php elseif ('range' === $filter['type'] && pfl_range_filter_has_values($key, $term_id)) : ?>
                <?php
                $selected_value = pfl_get_filter_value($key, null, $active_filter_keys);
                $visible_group_count++;
                ?>
                <section class="filter-row" data-filter-group="<?php echo esc_attr($key); ?>">
                    <div class="filter-row__heading">
                        <strong><?php echo esc_html($filter['label']); ?></strong>
                        <button class="filter-group-clear" type="button" data-clear-filter-group="<?php echo esc_attr($key); ?>" <?php echo $selected_value ? '' : 'hidden'; ?>>清空本组</button>
                    </div>
                    <div class="filter-options" role="radiogroup" aria-label="<?php echo esc_attr($filter['label']); ?>">
                        <?php foreach ($filter['options'] as $value => $option) : ?>
                            <?php
                            $input_id = $key . '-' . $value;
                            $is_selected = $selected_value === $value;
                            $facet_option = $facets[$key]['options'][$value] ?? [
                                'count' => 0,
                                'disabled' => ! $is_selected,
                            ];
                            $is_disabled = (
                                ! $is_selected
                                && ! empty($facet_option['disabled'])
                            );
                            ?>
                            <label
                                class="filter-option<?php
                                echo $is_selected ? ' is-selected' : '';
                                echo $is_disabled ? ' is-disabled' : '';
                                ?>"
                                for="<?php echo esc_attr($input_id); ?>"
                                <?php if ($is_disabled) : ?>
                                    aria-disabled="true"
                                <?php endif; ?>
                            >
                                <input
                                    id="<?php echo esc_attr($input_id); ?>"
                                    name="<?php echo esc_attr($key); ?>"
                                    type="radio"
                                    value="<?php echo esc_attr($value); ?>"
                                    data-filter-key="<?php echo esc_attr($key); ?>"
                                    data-option-value="<?php echo esc_attr($value); ?>"
                                    data-filter-label="<?php echo esc_attr($filter['label']); ?>"
                                    data-option-label="<?php echo esc_attr($option['label']); ?>"
                                    <?php checked($is_selected); ?>
                                    <?php disabled($is_disabled); ?>
                                >
                                <span><?php echo esc_html($option['label']); ?></span>
                                <small
                                    class="filter-option__count"
                                    data-filter-count
                                ><?php echo esc_html((string) $facet_option['count']); ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (0 === $visible_group_count) : ?>
            <p class="filter-empty-profile">当前分类的筛选方案没有可用选项。可在后台编辑产品属性或调整分类筛选方案。</p>
        <?php endif; ?>

        <div class="filter-toolbar">
            <label class="filter-sort"><span>排序：</span><select name="sort" data-filter-sort>
                <?php foreach ($sort_options as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_sort, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select></label>
            <div class="filter-toolbar__actions">
                <a class="filter-reset-link" href="<?php echo esc_url($base_action); ?>" data-filter-reset>重置筛选</a>
                <button id="applyFilterButton" class="filter-submit-button" type="submit">查看筛选结果</button>
            </div>
        </div>

        <noscript><p class="filter-noscript-note">当前浏览器未启用 JavaScript，请选择条件后点击“查看筛选结果”。</p></noscript>
    </form>
    <div id="productFilterLiveRegion" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></div>
</section>
