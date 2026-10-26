<?php
// Ambiente local sintético, excluído do pacote distribuído.
require '/wordpress/wp-load.php';
if (!defined('PMP_TESTING') || PMP_TESTING !== true) { http_response_code(404); exit; }
header('Content-Type: application/json');
use Maeda\Perfis\{Site, Modules, Commerce, Assessments, Database};
$checks=[];
function site_check($condition, $name) { global $checks; $checks[]=['name'=>$name,'passed'=>(bool)$condition]; if (!$condition) throw new RuntimeException($name); }
function site_value($value) { if (is_wp_error($value)) throw new RuntimeException($value->get_error_message()); return $value; }
try {
    global $wpdb;
    update_option('woocommerce_coming_soon', 'no');
    $before=[]; foreach(['show_on_front','page_on_front','woocommerce_shop_page_id','woocommerce_cart_page_id','woocommerce_checkout_page_id'] as $key) $before[$key]=get_option($key,'');
    $pages=site_value(Site::install());
    site_check(count($pages)===5 && (int)get_option('page_on_front')===$pages['home'],'Instala cinco páginas e seleciona a home');
    site_check((int)get_option('woocommerce_checkout_page_id')===$pages['checkout'] && has_shortcode(get_post($pages['checkout'])->post_content,'woocommerce_checkout'),'Checkout usa página e shortcode oficiais do WooCommerce');
    $home=get_post($pages['home']); $edited=$home->post_content.'<p id="site-editor-check">Conteúdo editado pelo proprietário.</p>';
    wp_update_post(['ID'=>$pages['home'],'post_content'=>$edited]);
    site_check(site_value(Site::install())===$pages && get_post($pages['home'])->post_content===$edited,'Reinstalação idempotente preserva edições nas páginas');
    Site::disable();
    foreach($before as $key=>$value)site_check((string)get_option($key,'')===(string)$value,'Restaura seleção anterior: '.$key);
    site_value(Site::install());
    $other=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Outra home do proprietário','post_content'=>'Outro conteúdo']);
    update_option('page_on_front',$other);
    Site::disable();
    site_check((int)get_option('page_on_front')===$other && get_option('show_on_front')==='page','Restauração respeita mudança posterior da home pelo proprietário');
    site_value(Site::install());
    wp_trash_post($pages['launch']);$newpages=site_value(Site::install());
    site_check($newpages['launch']!==$pages['launch'] && get_post_status($newpages['launch'])==='publish','Recupera página ausente sem duplicar as demais');
    $pages=$newpages;
    $products=[];
    foreach(Modules::all() as $key=>$module)foreach([1,5,10] as $size){
        $product=new WC_Product_Simple();$product->set_name('Nortume '.Site::module_name($key).' · '.$size.($size===1?' aplicação':' aplicações'));
        $product->set_status('publish');$product->set_virtual(true);$product->set_regular_price((string)(['comportamento'=>39,'lideranca'=>49,'carreira'=>39,'servico'=>29][$key]*$size));
        $product->set_description($module['description'].' Cada aplicação gera um relatório individual.');$product->set_short_description($module['description']);
        $product->update_meta_data('_pmp_module',$key);$product->update_meta_data('_pmp_pack_size',$size);$product->save();
        $products[$key][$size]=['id'=>$product->get_id(),'url'=>$product->get_permalink()];
    }
    site_check(count(Site::products('servico'))===3,'Loja consulta produtos reais do módulo selecionado');
    $variable=new WC_Product_Variable();$variable->set_name('Nortume Perfil · opções de pacote');$variable->set_status('publish');$variable->set_virtual(true);
    $attribute=new WC_Product_Attribute();$attribute->set_name('Aplicações');$attribute->set_options(['5 aplicações','15 aplicações']);$attribute->set_visible(true);$attribute->set_variation(true);$variable->set_attributes([$attribute]);
    $variable->update_meta_data('_pmp_module','comportamento');$variable->update_meta_data('_pmp_pack_size',1);$variable->save();
    foreach([5=>159,15=>399] as $size=>$price){$variation=new WC_Product_Variation();$variation->set_parent_id($variable->get_id());$variation->set_status('publish');$variation->set_virtual(true);$variation->set_regular_price((string)$price);$variation->set_attributes(['aplicacoes'=>$size.' aplicações']);$variation->update_meta_data('_pmp_pack_size',$size);$variation->save();}
    WC_Product_Variable::sync($variable->get_id());
    site_check(Commerce::config(wc_get_product($variation->get_id()))['size']===15,'Variação conserva quantidade própria de créditos');
    update_option('woocommerce_bacs_settings',['enabled'=>'yes','title'=>'Transferência de teste','description'=>'Método local de teste, sem transação financeira.','instructions'=>'Pedido de teste. Nenhum pagamento foi processado.']);
    $buyer=(int)username_exists('comprador');
    $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.Database::table('attempts')." WHERE user_id=%d AND status='completed' ORDER BY id LIMIT 1",$buyer),ARRAY_A);
    $legacy=json_decode($row['result'],true);unset($legacy['narrative']);$wpdb->update(Database::table('attempts'),['result'=>wp_json_encode($legacy)],['id'=>$row['id']]);
    site_check(is_wp_error(Assessments::report((int)$row['id'],(int)username_exists('terceiro'))) && !isset(json_decode(Assessments::get((int)$row['id'])['result'],true)['narrative']),'Conta sem acesso não gera nem recebe leitura de relatório antigo');
    $module=json_decode($row['snapshot'],true);$override=$module;$override['version']='99.0.0';$override['dimensions'][0]['description']='Conteúdo alterado depois do teste';update_option('pmp_modules',[$module['key']=>$override]);
    $enriched=site_value(Assessments::report((int)$row['id'],$buyer));delete_option('pmp_modules');
    site_check($enriched['result']['module_version']===$legacy['module_version'] && !str_contains(wp_json_encode($enriched['result']['narrative']),'Conteúdo alterado depois do teste'),'Relatório antigo usa instrumento original para construir a leitura');
    site_check(isset(json_decode(Assessments::get((int)$row['id'])['result'],true)['narrative']) && site_value(Assessments::report((int)$row['id'],$buyer))['result']===$enriched['result'],'Leitura antiga é gravada uma vez e preservada nas consultas seguintes');
    echo wp_json_encode(['success'=>true,'checks'=>$checks,'fixture'=>['pages'=>$pages,'urls'=>array_map(static fn($key)=>Site::url($key),array_combine(array_keys($pages),array_keys($pages))),'products'=>$products,'variable_url'=>$variable->get_permalink(),'legacy_report_id'=>(int)$row['id']]]);
} catch(Throwable $error) {http_response_code(500);echo wp_json_encode(['success'=>false,'checks'=>$checks,'error'=>$error->getMessage()]);}
