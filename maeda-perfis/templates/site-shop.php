<?php
defined('ABSPATH') || exit;
use Maeda\Perfis\{Site, Modules, Commerce};
$selected = isset($_GET['nmodule']) && is_string($_GET['nmodule']) ? sanitize_key(wp_unslash($_GET['nmodule'])) : '';
if (!Modules::get($selected)) $selected = '';
$size = isset($_GET['npack']) && is_scalar($_GET['npack']) ? absint($_GET['npack']) : 0;
if (!in_array($size, [1,5,10], true)) $size = 0;
$products = array_values(array_filter(Site::products($selected), static fn($product) => !$size || (! $product->is_type('variable') && Commerce::config($product)['size'] === $size)));
?>
<section class="nt-shop-heading"><div class="nt-wrap"><p class="nt-eyebrow"><span></span> Encontre o seu ponto de partida</p><h1>Uma escolha.<br><em>Novas perspectivas.</em></h1><p>Testes para você. Pacotes para compartilhar descobertas.<br>Cada aplicação pertence a um módulo e gera um relatório individual.</p></div></section>
<section class="nt-wrap nt-shop-section">
    <?php if (function_exists('wc_print_notices')) wc_print_notices(); ?>
    <nav class="nt-module-filters" aria-label="Filtrar por módulo"><a href="<?php echo esc_url(Site::url('shop', $size ? ['npack'=>$size] : [])); ?>" <?php echo !$selected ? 'aria-current="true"' : ''; ?>>Todos os módulos</a><?php foreach (Modules::all() as $key=>$module): ?><a href="<?php echo esc_url(Site::url('shop', array_filter(['nmodule'=>$key,'npack'=>$size]))); ?>" <?php echo $selected === $key ? 'aria-current="true"' : ''; ?>><?php echo esc_html(Site::module_name($key)); ?></a><?php endforeach; ?></nav>
    <div class="nt-shop-toolbar"><p><strong><?php echo count($products); ?></strong> <?php echo count($products) === 1 ? 'opção encontrada' : 'opções encontradas'; ?></p><nav aria-label="Filtrar por aplicações"><span>Aplicações:</span><?php foreach ([0=>'Todas',1=>'1',5=>'5',10=>'10'] as $value=>$label): ?><a href="<?php echo esc_url(Site::url('shop', array_filter(['nmodule'=>$selected,'npack'=>$value]))); ?>" <?php echo $value === $size ? 'aria-current="true"' : ''; ?>><?php echo esc_html($label); ?></a><?php endforeach; ?></nav></div>
    <?php if ($products): ?><div class="nt-shop-grid"><?php foreach ($products as $product) Site::product_card($product); ?></div>
    <?php else: ?><div class="nt-shop-empty"><span class="nt-symbol"><?php echo Site::icon(); ?></span><h2>Seu próximo caminho está sendo preparado.</h2><p>Não há produtos publicados para esta seleção no momento.</p><?php if ($selected || $size) echo Site::button('Ver todas as opções', Site::url('shop'), 'nt-button-outline'); ?><?php if (current_user_can('manage_pmp')): ?><p class="nt-fine">Para disponibilizar os testes, crie os produtos iniciais em Nortume, defina os preços no WooCommerce e publique as opções desejadas.</p><?php endif; ?></div><?php endif; ?>
    <aside class="nt-shop-help"><div><?php echo Site::icon('team'); ?></div><div><h2>Quer aplicar com um grupo?</h2><p>Escolha um pacote do módulo desejado. Após a confirmação do pagamento, envie convites individuais e acompanhe os relatórios das aplicações que você comprou.</p></div><a class="nt-text-link" href="<?php echo esc_url(Site::url() . '#como-funciona'); ?>">Entender o processo <?php echo Site::icon('arrow'); ?></a></aside>
    <p class="nt-catalog-note">Questionários autorais em versão piloto. Os relatórios apoiam reflexão e desenvolvimento, sem diagnóstico, percentis ou previsão de desempenho. Consulte o conteúdo e a referência de cada módulo antes de comprar.</p>
</section>
