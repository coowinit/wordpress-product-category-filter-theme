<?php
/**
 * 主题页头。
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-header__inner">
        <div class="site-branding">
            <p class="site-branding__title">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php bloginfo('name'); ?>
                </a>
            </p>

            <?php if (get_bloginfo('description')) : ?>
                <p class="site-branding__desc">
                    <?php bloginfo('description'); ?>
                </p>
            <?php endif; ?>
        </div>

        <nav class="site-nav" aria-label="主导航">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(
                    [
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ]
                );
            } else {
                ?>
                <ul>
                    <li>
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            首页
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>">
                            产品中心
                        </a>
                    </li>
                </ul>
                <?php
            }
            ?>
        </nav>
    </div>
</header>

<main class="site-main">
