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
                </ul>
            </aside>
        </div>
    </article>

    <?php
endwhile;

get_footer();
