<?php
defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('pmp-page'); ?>>
<?php wp_body_open(); ?>
<main id="pmp-page-content">
    <?php while (have_posts()) { the_post(); the_content(); } ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
