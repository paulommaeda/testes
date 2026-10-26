<?php
defined('ABSPATH') || exit;
use Maeda\Perfis\Site;
$route = Site::route();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="nt-site">
<?php Site::header($route); ?>
<main id="nt-main" tabindex="-1">
<?php
if ($route === 'product') {
    echo '<div class="nt-wrap nt-product-page"><div class="nt-breadcrumb"><a href="' . esc_url(Site::url('shop')) . '">Explorar testes</a><span>/</span><span>' . esc_html(get_the_title()) . '</span></div>';
    woocommerce_content();
    echo '</div>';
} elseif ($route === 'shop') {
    // O WooCommerce trata a página de loja como arquivo; o conteúdo continua editável no WordPress.
    $pages = (array) get_option('pmp_site_pages', []);
    $shop = get_post((int) ($pages['shop'] ?? 0));
    if ($shop && !post_password_required($shop)) echo apply_filters('the_content', $shop->post_content);
    else echo Site::render('shop');
} else {
    $commerce = in_array($route, ['cart','checkout','account'], true);
    if ($commerce) {
        $received = function_exists('is_order_received_page') && is_order_received_page();
        $titles = ['cart'=>['SUA ESCOLHA','Um passo para se conhecer melhor.','Confira os módulos e as quantidades antes de continuar.'],'checkout'=>['FINALIZAR COMPRA','Seu próximo passo está aqui.','Revise seus dados e escolha entre os métodos de pagamento disponíveis.'],'account'=>['SEU ESPAÇO','Bem-vindo à sua conta.','Acompanhe pedidos, dados da conta e suas próximas descobertas.']];
        [$eyebrow,$title,$description] = $received ? ['SEU PEDIDO','Acompanhe sua compra.','Confira abaixo o status e os detalhes informados pelo WooCommerce.'] : $titles[$route];
        echo '<div class="nt-wrap nt-commerce-page"><header class="nt-commerce-heading"><p class="nt-kicker">' . esc_html($eyebrow) . '</p><h1>' . esc_html($title) . '</h1><p>' . esc_html($description) . '</p></header><div class="nt-commerce-content">';
    }
    while (have_posts()) { the_post(); the_content(); }
    if ($commerce) echo '</div><p class="nt-commerce-note">Cada aplicação pertence ao módulo escolhido. Após a confirmação do pagamento, acesse seus testes e convites na sua conta.</p></div>';
}
?>
</main>
<?php Site::footer(); ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
