<?php
// Somente no ambiente descartável de desenvolvimento. Não distribuído no plugin.
require '/wordpress/wp-load.php';
if (!defined('PMP_TESTING') || PMP_TESTING !== true) { http_response_code(404); exit; }
header('Content-Type: application/json');
use Maeda\Perfis\{Account,Plugin,Commerce};
$checks=[];
function account_check($condition,$name){global $checks;$checks[]=['name'=>$name,'passed'=>(bool)$condition];if(!$condition)throw new RuntimeException($name);}
function account_value($value){if(is_wp_error($value))throw new RuntimeException($value->get_error_message());return $value;}
function account_user($login,$first,$last){$id=username_exists($login);if(!$id)$id=wp_create_user($login,'NortumeConta-7349!',$login.'@example.test');wp_update_user(['ID'=>$id,'first_name'=>$first,'last_name'=>$last,'display_name'=>$first.' '.$last,'user_email'=>$login.'@example.test','user_pass'=>'NortumeConta-7349!','role'=>'customer']);return (int)$id;}
try{
 $buyer=account_user('conta_nortume','Paulo','Teste');$other=account_user('conta_outra','Outra','Pessoa');wp_set_current_user($buyer);
 foreach([$buyer,$other] as $synthetic)foreach(wc_get_orders(['type'=>'shop_order','customer_id'=>$synthetic,'limit'=>-1]) as $old)$old->delete(true);
 account_check((int)get_option('woocommerce_myaccount_page_id')===(int)get_option('pmp_page_id'),'Conta do WooCommerce aponta para a interface Nortume');
 account_check(get_the_title((int)get_option('pmp_page_id'))==='Minha conta','Título padrão da página passa a ser Minha conta');
 $product=new WC_Product_Simple();$product->set_name('Nortume Perfil · 5 aplicações');$product->set_status('publish');$product->set_virtual(true);$product->set_regular_price('150');$product->update_meta_data('_pmp_module','comportamento');$product->update_meta_data('_pmp_pack_size',5);$product->save();
 $create=function($customer,$status,$qty=1)use($product){$order=wc_create_order(['customer_id'=>$customer]);$id=$order->add_product($product,$qty);$item=$order->get_item($id);$item->add_meta_data('_pmp_module','comportamento');$item->add_meta_data('_pmp_pack_size',5);$item->save();$order->set_billing_first_name('Nome na compra');$order->set_billing_last_name('Original');$order->set_billing_email('faturamento.original@example.test');$order->set_billing_address_1('Rua da Compra, 10');$order->set_billing_city('Curitiba');$order->set_billing_state('PR');$order->set_billing_postcode('80010-000');$order->set_billing_country('BR');$order->calculate_totals();$order->save();$order->update_status($status);return $order;};
 $complete=$create($buyer,'completed',2);$pending=$create($buyer,'pending');$failed=$create($buyer,'failed');$foreign=$create($other,'completed');
 $complete->add_order_note('Mensagem ao cliente de teste.',true);$complete->add_order_note('ANOTACAO_INTERNA_NAO_EXIBIR',false);
 for($i=0;$i<10;$i++)$create($buyer,'on-hold');
 $list=Account::orders($buyer);$next=Account::orders($buyer,2);
 account_check($list['total']===13&&count($list['items'])===10&&$list['has_more']&&count($next['items'])===3&&!$next['has_more'],'Lista os pedidos do comprador com paginação real');
 account_check(!in_array($foreign->get_id(),array_column(array_merge($list['items'],$next['items']),'id'),true),'Listagem não inclui pedidos de outra conta');
 account_check(Account::orders(0)['total']===0,'Usuário zero não consulta pedidos de visitantes');
 account_check(is_wp_error(Account::order($foreign->get_id(),$buyer))&&is_wp_error(Account::order($complete->get_id(),$other)),'Detalhes de pedido exigem vínculo com o comprador');
 $detail=account_value(Account::order($complete->get_id(),$buyer));
 account_check($detail['items'][0]['applications']===10&&$detail['items'][0]['module']==='Comportamento e comunicação','Pedido mostra aplicações e módulo efetivamente comprados');
 account_check(count($detail['notes'])===1&&!str_contains(wp_json_encode($detail),'ANOTACAO_INTERNA_NAO_EXIBIR'),'Exibe apenas notas destinadas ao cliente');
 account_check(!isset($detail['order_key'],$detail['customer_id'],$detail['meta_data']),'Resposta não expõe metadados internos do pedido');
 account_check(!empty(account_value(Account::order($pending->get_id(),$buyer))['pay_url']),'Pedido pendente oferece pagamento pelo checkout nativo');
 account_check(is_wp_error(Account::cancel_order($complete->get_id(),$buyer))&&is_wp_error(Account::cancel_order($foreign->get_id(),$buyer)),'Cancelamento bloqueia pedido pago e pedido de outra conta');
 $cancel=account_value(Account::cancel_order($failed->get_id(),$buyer));
 account_check($cancel['order']['status']==='cancelled'&&wc_get_order($failed->get_id())->has_status('cancelled'),'Cancelamento permitido atualiza o pedido nativo');
 $profile=['first_name'=>'Paulo','last_name'=>'Conta','display_name'=>'Paulo Conta','email'=>'conta_nortume@example.test'];
 account_check(is_wp_error(Account::save_profile($buyer,$profile+['role'=>'administrator']))&&!user_can($buyer,'manage_options'),'Perfil não aceita alteração de permissões');
 account_check(is_wp_error(Account::save_profile($buyer,array_merge($profile,['first_name'=>[]]))),'Perfil rejeita campos malformados');
 account_check(is_wp_error(Account::save_profile($buyer,array_merge($profile,['email'=>'invalido']))),'Perfil rejeita e-mail inválido');
 account_check(is_wp_error(Account::save_profile($buyer,array_merge($profile,['email'=>'conta_outra@example.test','current_password'=>'NortumeConta-7349!']))),'Perfil rejeita e-mail já usado em outra conta');
 account_check(is_wp_error(Account::save_profile($buyer,array_merge($profile,['email'=>'conta_atualizada@example.test']))),'Troca de e-mail exige senha atual');
 $saved=account_value(Account::save_profile($buyer,$profile));
 account_check($saved['profile']['display_name']==='Paulo Conta'&&get_userdata($buyer)->last_name==='Conta','Dados pessoais são salvos no usuário do WordPress');
 $changed=account_value(Account::save_profile($buyer,array_merge($profile,['email'=>'conta_atualizada@example.test','current_password'=>'NortumeConta-7349!'])));
 account_check($changed['profile']['email']==='conta_atualizada@example.test'&&wc_get_order($complete->get_id())->get_billing_email()==='faturamento.original@example.test','Atualizar e-mail da conta não altera compras anteriores');
 $profile['email']='conta_atualizada@example.test';
 account_check(is_wp_error(Account::save_profile($buyer,$profile+['current_password'=>'errada','new_password'=>'NortumeSenhaNova-123!','confirm_password'=>'NortumeSenhaNova-123!'])),'Troca de senha rejeita senha atual incorreta');
 account_check(is_wp_error(Account::save_profile($buyer,$profile+['current_password'=>'NortumeConta-7349!','new_password'=>'curta','confirm_password'=>'curta'])),'Troca de senha valida o tamanho mínimo');
 account_value(Account::save_profile($buyer,$profile+['current_password'=>'NortumeConta-7349!','new_password'=>'NortumeSenhaNova-123!','confirm_password'=>'NortumeSenhaNova-123!']));
 account_check(wp_check_password('NortumeSenhaNova-123!',get_userdata($buyer)->user_pass,$buyer)&&!wp_check_password('NortumeConta-7349!',get_userdata($buyer)->user_pass,$buyer),'Nova senha é processada pela autenticação do WordPress');
 wp_update_user(['ID'=>$buyer,'user_pass'=>'NortumeConta-7349!']);
 $billing=['first_name'=>'Paulo','last_name'=>'Conta','company'=>'Empresa de teste','phone'=>'41999999999','email'=>'financeiro@example.test','country'=>'BR','state'=>'PR','postcode'=>'80010-000','city'=>'Curitiba','address_1'=>'Rua da Conta, 20','address_2'=>'Sala 2'];
 account_check(is_wp_error(Account::save_billing($buyer,$billing+['is_vat_exempt'=>'yes'])),'Endereço aceita somente campos permitidos');
 account_check(is_wp_error(Account::save_billing($buyer,array_merge($billing,['state'=>'XX']))),'Endereço valida estado do país');
 account_value(Account::save_billing($buyer,$billing));$customer=new WC_Customer($buyer);
 account_check($customer->get_billing_address_1()==='Rua da Conta, 20'&&$customer->get_billing_email()==='financeiro@example.test','Endereço é salvo no cadastro de cliente do WooCommerce');
 account_check(get_userdata($buyer)->user_email==='conta_atualizada@example.test'&&wc_get_order($complete->get_id())->get_billing_address_1()==='Rua da Compra, 10','Endereço novo não altera o login nem o histórico de pedidos');
 // A seleção anterior é recuperada e pode voltar a ser usada ao reativar o plugin.
 $canonical=(int)get_option('pmp_page_id');$previous=(int)get_option('pmp_previous_account_page_id');Account::restore();
 account_check((int)get_option('woocommerce_myaccount_page_id')===$previous,'Desativação restaura a página de conta anterior');
 Account::configure();account_check((int)get_option('woocommerce_myaccount_page_id')===$canonical,'Reativação volta a selecionar a conta Nortume');
 echo wp_json_encode(['success'=>true,'checks'=>$checks,'fixture'=>['login'=>'conta_nortume','password'=>'NortumeConta-7349!','buyer'=>$buyer,'other'=>$other,'complete_id'=>$complete->get_id(),'pending_id'=>$pending->get_id(),'foreign_id'=>$foreign->get_id(),'account_url'=>Plugin::url(),'old_account_url'=>$previous?get_permalink($previous):'','email'=>'conta_atualizada@example.test']]);
}catch(Throwable $error){http_response_code(500);echo wp_json_encode(['success'=>false,'checks'=>$checks,'error'=>$error->getMessage()]);}
