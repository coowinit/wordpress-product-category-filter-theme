<?php
/**
 * 单个产品详情模板。
 */

get_header();

while (have_posts()) :
    the_post();

    $model = get_post_meta(get_the_ID(), 'product_model', true);
    $power = get_post_meta(get_the_ID(), 'product_power', true);

    $categories = get_the_terms(get_the_ID(), 'product_category');
    $brands     = get_the_terms(get_the_ID(), 'product_brand');
    $voltages   = get_the_terms(get_the_ID(), 'product_voltage');
    ?>

    <article <?php post_class('single-product'); ?>>
        <h1><?php the_title(); ?></h1>

        <div class="single-product__details">
            <div class="single-product__content">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php endif; ?>

                <?php the_content(); ?>
            </div>

            <aside>
                <ul class="product-specs">
                    <li>
                        <span>型号</span>
                        <strong><?php echo esc_html($model ?: '未填写'); ?></strong>
                    </li>
                    <li>
                        <span>价格</span>
                        <strong><?php echo esc_html(pfl_format_product_price()); ?></strong>
                    </li>
                    <li>
                        <span>功率</span>
                        <strong>
                            <?php echo esc_html($power ? $power . ' kW' : '未填写'); ?>
                        </strong>
                    </li>
                    <li>
                        <span>分类</span>
                        <strong>
                            <?php
                            echo esc_html(
                                ! empty($categories) && ! is_wp_error($categories)
                                    ? $categories[0]->name
                                    : '未分类'
                            );
                            ?>
                        </strong>
                    </li>
                    <li>
                        <span>品牌</span>
                        <strong>
                            <?php
                            echo esc_html(
                                ! empty($brands) && ! is_wp_error($brands)
                                    ? $brands[0]->name
                                    : '未设置'
                            );
                            ?>
                        </strong>
                    </li>
                    <li>
                        <span>电压</span>
                        <strong>
                            <?php
                            echo esc_html(
                                ! empty($voltages) && ! is_wp_error($voltages)
                                    ? $voltages[0]->name
                                    : '未设置'
                            );
                            ?>
                        </strong>
                    </li>

                    <?php
                    $extra_attributes = [
                        'product_material' => '材质',
                        'product_application' => '应用行业',
                        'product_automation' => '自动化程度',
                        'product_installation' => '安装方式',
                        'product_detection_type' => '检测方式',
                        'product_output_type' => '输出类型',
                        'product_protection' => '防护等级',
                    ];
                    foreach ($extra_attributes as $taxonomy => $label) :
                        $attribute_terms = get_the_terms(get_the_ID(), $taxonomy);
                        if (empty($attribute_terms) || is_wp_error($attribute_terms)) { continue; }
                    ?>
                        <li>
                            <span><?php echo esc_html($label); ?></span>
                            <strong><?php echo esc_html(implode('、', wp_list_pluck($attribute_terms, 'name'))); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </article>

    <?php
endwhile;

get_footer();
