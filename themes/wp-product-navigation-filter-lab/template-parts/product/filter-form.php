<?php
/**
 * 动态产品筛选表单。
 *
 * v1.6.0 在原有动态属性与联动计数基础上增加：
 * - 移动端筛选抽屉；
 * - 筛选组折叠；
 * - 组内搜索；
 * - “显示更多”；
 * - 已选项置顶；
 * - 桌面端吸顶摘要。
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
$facet_state = pfl_get_faceted_filter_state($_GET, $current_term, $active_filter_keys);
$facets = $facet_state['facets'];
$result_count = isset($GLOBALS['wp_query']) ? (int) $GLOBALS['wp_query']->found_posts : 0;
?>

<button
    id="productFilterDrawerOpen"
    class="product-filter-mobile-trigger"
    type="button"
    aria-controls="product-filter-panel"
    aria-expanded="false"
>
    <span class="product-filter-mobile-trigger__icon" aria-hidden="true">☷</span>
    <span>筛选产品</span>
    <strong><span id="mobileFilterAppliedCount"><?php echo esc_html((string) $applied_count); ?></span> 项</strong>
</button>

<div id="productFilterDrawerBackdrop" class="product-filter-drawer-backdrop" hidden></div>

<section
    id="product-filter-panel"
    class="product-filter-panel"
    aria-label="产品筛选"
    data-filter-drawer
>
    <header class="product-filter-panel__header">
        <div>
            <div class="product-filter-panel__title-row">
                <h2>动态产品属性筛选</h2>
                <span class="ajax-filter-badge" aria-hidden="true">AJAX 自动更新</span>
                <span class="facet-count-badge" aria-hidden="true">联动计数</span>
                <span class="filter-profile-badge"><?php echo esc_html($profile['source_label']); ?></span>
            </div>
            <p>筛选组支持折叠、搜索和更多展开；移动端可在抽屉中完成筛选。</p>
        </div>

        <strong class="product-filter-panel__count">
            <span id="productFilterResultCount"><?php echo esc_html((string) $result_count); ?></span>
            <small>个当前结果</small>
        </strong>

        <button
            id="productFilterDrawerClose"
            class="product-filter-drawer-close"
            type="button"
            aria-label="关闭产品筛选"
        >×</button>
    </header>

    <form
        id="productFilterForm"
        class="product-filter-form"
        method="get"
        action="<?php echo esc_url($form_action); ?>"
        data-ajax-context="<?php echo esc_attr($context); ?>"
        data-term-id="<?php echo esc_attr((string) $term_id); ?>"
        data-base-url="<?php echo esc_url($base_action); ?>"
        data-storage-key="<?php echo esc_attr($context . '_' . $term_id); ?>"
    >
        <div
            id="filterAjaxError"
            class="filter-ajax-error"
            role="alert"
            hidden
        >
            <div>
                <strong>筛选请求没有成功</strong>
                <span id="filterAjaxErrorMessage">可重新请求，或使用普通页面方式打开当前条件。</span>
            </div>
            <div class="filter-ajax-error__actions">
                <button id="filterAjaxRetry" type="button">重新请求</button>
                <a id="filterAjaxFallback" href="<?php echo esc_url($form_action); ?>">普通页面打开</a>
            </div>
        </div>

        <div id="filterStateBar" class="filter-state-bar" data-applied-count="<?php echo esc_attr((string) $applied_count); ?>">
            <div class="filter-state-bar__message">
                <strong id="filterStateTitle">
                    <?php echo $applied_count > 0
                        ? esc_html('当前已应用 ' . $applied_count . ' 个筛选条件')
                        : '当前未应用筛选条件'; ?>
                </strong>
                <span id="filterStateHint">结果、地址栏和筛选项数量保持同步；界面展开状态单独保存在浏览器中。</span>
                <span id="filterPerformance" class="filter-performance" hidden aria-live="polite"></span>
            </div>
            <div id="selectedFilterTags" class="selected-filter-tags"></div>
            <button id="clearAllFilters" type="button">清空全部</button>
        </div>

        <div class="filter-groups" data-filter-groups>
        <?php foreach ($active_filter_keys as $key) : ?>
            <?php
            if (! isset($schema[$key])) {
                continue;
            }

            $filter = $schema[$key];
            $ui = pfl_get_filter_ui_config($key, $filter);
            $group_position = $visible_group_count;
            ?>

            <?php if ('taxonomy' === $filter['type']) : ?>
                <?php
                $options = pfl_get_filter_term_options($key, $term_id);
                if (empty($options)) {
                    continue;
                }

                $selected_values = pfl_get_filter_values($key, null, $active_filter_keys);
                $visible_group_count++;
                $group_id = 'filter-group-body-' . $key;
                $default_open = ! empty($selected_values) || ! empty($ui['default_open']) || $group_position < 2;
                $is_searchable = ! empty($ui['searchable']) && count($options) >= (int) $ui['search_threshold'];
                $initial_limit = max(1, (int) $ui['initial_limit']);
                ?>
                <section
                    class="filter-row<?php echo $default_open ? ' is-open' : ''; ?>"
                    data-filter-group="<?php echo esc_attr($key); ?>"
                    data-default-open="<?php echo $default_open ? '1' : '0'; ?>"
                    data-initial-limit="<?php echo esc_attr((string) $initial_limit); ?>"
                    data-searchable="<?php echo $is_searchable ? '1' : '0'; ?>"
                >
                    <div class="filter-row__heading">
                        <button
                            class="filter-group-toggle"
                            type="button"
                            aria-expanded="<?php echo $default_open ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($group_id); ?>"
                        >
                            <span class="filter-group-toggle__label"><?php echo esc_html($filter['label']); ?></span>
                            <span class="filter-group-toggle__meta">
                                <small data-group-selected-count><?php echo empty($selected_values) ? '未选择' : esc_html('已选 ' . count($selected_values) . ' 项'); ?></small>
                                <span class="filter-group-toggle__chevron" aria-hidden="true"></span>
                            </span>
                        </button>
                        <button class="filter-group-clear" type="button" data-clear-filter-group="<?php echo esc_attr($key); ?>" <?php echo empty($selected_values) ? 'hidden' : ''; ?>>清空本组</button>
                    </div>

                    <div id="<?php echo esc_attr($group_id); ?>" class="filter-row__body">
                        <?php if ($is_searchable) : ?>
                            <label class="filter-option-search">
                                <span class="screen-reader-text">搜索<?php echo esc_html($filter['label']); ?></span>
                                <input
                                    type="search"
                                    value=""
                                    placeholder="搜索<?php echo esc_attr($filter['label']); ?>……"
                                    autocomplete="off"
                                    data-filter-option-search
                                >
                                <button type="button" data-clear-option-search aria-label="清空搜索">×</button>
                            </label>
                        <?php endif; ?>

                        <div class="filter-options" role="group" aria-label="<?php echo esc_attr($filter['label']); ?>" data-filter-options>
                            <?php foreach ($options as $option_index => $option) : ?>
                                <?php
                                $term = $option['term'];
                                $input_id = 'filter-' . $key . '-' . $term->term_id;
                                $is_selected = in_array($term->slug, $selected_values, true);
                                $facet_option = $facets[$key]['options'][$term->slug] ?? ['count' => 0, 'disabled' => ! $is_selected];
                                $is_disabled = ! $is_selected && ! empty($facet_option['disabled']);
                                ?>
                                <label
                                    class="filter-option<?php echo $is_selected ? ' is-selected' : ''; ?><?php echo $is_disabled ? ' is-disabled' : ''; ?>"
                                    for="<?php echo esc_attr($input_id); ?>"
                                    data-filter-option
                                    data-option-name="<?php echo esc_attr(wp_strip_all_tags($term->name)); ?>"
                                    data-original-index="<?php echo esc_attr((string) $option_index); ?>"
                                    <?php if ($is_disabled) : ?>aria-disabled="true"<?php endif; ?>
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
                                    <small class="filter-option__count" data-filter-count><?php echo esc_html((string) $facet_option['count']); ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <p class="filter-option-search-empty" data-filter-search-empty hidden>没有找到匹配选项。</p>
                        <button class="filter-options-more" type="button" data-filter-options-more hidden>显示更多</button>
                    </div>
                </section>

            <?php elseif ('range' === $filter['type'] && pfl_range_filter_has_values($key, $term_id)) : ?>
                <?php
                $selected_value = pfl_get_filter_value($key, null, $active_filter_keys);
                $visible_group_count++;
                $group_id = 'filter-group-body-' . $key;
                $default_open = ! empty($selected_value) || ! empty($ui['default_open']) || $group_position < 2;
                ?>
                <section
                    class="filter-row<?php echo $default_open ? ' is-open' : ''; ?>"
                    data-filter-group="<?php echo esc_attr($key); ?>"
                    data-default-open="<?php echo $default_open ? '1' : '0'; ?>"
                    data-initial-limit="99"
                    data-searchable="0"
                >
                    <div class="filter-row__heading">
                        <button
                            class="filter-group-toggle"
                            type="button"
                            aria-expanded="<?php echo $default_open ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($group_id); ?>"
                        >
                            <span class="filter-group-toggle__label"><?php echo esc_html($filter['label']); ?></span>
                            <span class="filter-group-toggle__meta">
                                <small data-group-selected-count><?php echo $selected_value ? '已选 1 项' : '未选择'; ?></small>
                                <span class="filter-group-toggle__chevron" aria-hidden="true"></span>
                            </span>
                        </button>
                        <button class="filter-group-clear" type="button" data-clear-filter-group="<?php echo esc_attr($key); ?>" <?php echo $selected_value ? '' : 'hidden'; ?>>清空本组</button>
                    </div>

                    <div id="<?php echo esc_attr($group_id); ?>" class="filter-row__body">
                        <div class="filter-options" role="radiogroup" aria-label="<?php echo esc_attr($filter['label']); ?>" data-filter-options>
                            <?php foreach ($filter['options'] as $option_index => $option) : ?>
                                <?php
                                $value = (string) $option_index;
                                $input_id = $key . '-' . $value;
                                $is_selected = $selected_value === $value;
                                $facet_option = $facets[$key]['options'][$value] ?? ['count' => 0, 'disabled' => ! $is_selected];
                                $is_disabled = ! $is_selected && ! empty($facet_option['disabled']);
                                ?>
                                <label
                                    class="filter-option<?php echo $is_selected ? ' is-selected' : ''; ?><?php echo $is_disabled ? ' is-disabled' : ''; ?>"
                                    for="<?php echo esc_attr($input_id); ?>"
                                    data-filter-option
                                    data-option-name="<?php echo esc_attr(wp_strip_all_tags($option['label'])); ?>"
                                    data-original-index="<?php echo esc_attr((string) array_search($value, array_keys($filter['options']), true)); ?>"
                                    <?php if ($is_disabled) : ?>aria-disabled="true"<?php endif; ?>
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
                                    <small class="filter-option__count" data-filter-count><?php echo esc_html((string) $facet_option['count']); ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

        <?php if (0 === $visible_group_count) : ?>
            <p class="filter-empty-profile">当前分类的筛选方案没有可用选项。可在后台编辑产品属性或调整分类筛选方案。</p>
        <?php endif; ?>

        <div class="filter-toolbar">
            <label class="filter-sort">
                <span>排序：</span>
                <select name="sort" data-filter-sort>
                    <?php foreach ($sort_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_sort, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="filter-toolbar__actions">
                <a class="filter-reset-link" href="<?php echo esc_url($base_action); ?>" data-filter-reset>重置筛选</a>
                <button id="applyFilterButton" class="filter-submit-button" type="submit">查看筛选结果</button>
            </div>
        </div>

        <div class="filter-mobile-actions" aria-label="移动端筛选操作">
            <button id="mobileClearAllFilters" type="button">清空条件</button>
            <button id="mobileViewResults" type="button">
                查看 <strong id="mobileFilterResultCount"><?php echo esc_html((string) $result_count); ?></strong> 个产品
            </button>
        </div>

        <noscript><p class="filter-noscript-note">当前浏览器未启用 JavaScript，请选择条件后点击“查看筛选结果”。</p></noscript>
    </form>
    <div id="productFilterLiveRegion" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></div>
</section>

<div id="filterStickySummary" class="filter-sticky-summary" aria-label="筛选摘要">
    <span>
        已筛选 <strong id="stickyFilterAppliedCount"><?php echo esc_html((string) $applied_count); ?></strong> 项，
        共 <strong id="stickyFilterResultCount"><?php echo esc_html((string) $result_count); ?></strong> 个产品
    </span>
    <div>
        <button id="stickyModifyFilters" type="button">修改筛选</button>
        <button id="stickyClearFilters" type="button">清空</button>
    </div>
</div>
