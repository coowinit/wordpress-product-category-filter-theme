<?php
/**
 * 产品列表卡片。
 */

$model = get_post_meta(get_the_ID(), 'product_model', true);
$power = get_post_meta(get_the_ID(), 'product_power', true);
$brand_terms = get_the_terms(get_the_ID(), 'product_brand');

$brand_name = (
    ! empty($brand_terms)
    && ! is_wp_error($brand_terms)
)
    ? $brand_terms[0]->name
    : '未设置品牌';

$title = get_the_title();
$initial = function_exists('mb_substr')
    ? mb_substr($title, 0, 1)
    : substr($title, 0, 1);
?>

<article <?php post_class('product-card'); ?>>
    <a href="<?php the_permalink(); ?>">
        <div class="product-card__media">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium_large'); ?>
            <?php else : ?>
                <?php echo esc_html($initial); ?>
            <?php endif; ?>
        </div>

        <div class="product-card__body">
            <h2 class="product-card__title">
                <?php the_title(); ?>
            </h2>

            <div class="product-card__meta">
                <span>品牌：<?php echo esc_html($brand_name); ?></span>
                <span>型号：<?php echo esc_html($model ?: '未填写'); ?></span>
                <span>
                    功率：
                    <?php echo esc_html($power ? $power . ' kW' : '未填写'); ?>
                </span>
            </div>

            <div class="product-card__price">
                <?php echo esc_html(pfl_format_product_price()); ?>
            </div>
        </div>
    </a>
</article>
