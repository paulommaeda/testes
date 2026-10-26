<?php
defined('ABSPATH') || exit;
use Maeda\Perfis\Site;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('pmp-page pmp-account-page'); ?>>
<?php wp_body_open(); ?>
<div class="nt-site nt-account-site">
<?php Site::header('account'); ?>
<main id="pmp-page-content" tabindex="-1">
    <?php while (have_posts()) { the_post(); the_content(); } ?>
</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
