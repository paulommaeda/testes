<?php
// Ambiente de testes local apenas. Este diretório não entra no ZIP instalável.
require '/wordpress/wp-load.php';
if (!defined('PMP_TESTING') || PMP_TESTING !== true) { http_response_code(404); exit; }
header('Content-Type: application/json');

use Maeda\Perfis\{Plugin,Modules,Scoring,Credits,Invitations,Assessments,Commerce,Database,Rest};

$checks=[];$mail=[];
add_filter('pre_wp_mail',static function ($return,$args) use (&$mail) {$mail[]=$args;return true;},10,2);
function check($condition,$name) {global $checks;$checks[]=['name'=>$name,'passed'=>(bool) $condition];if(!$condition)throw new RuntimeException($name);}
function value($result) {if(is_wp_error($result))throw new RuntimeException($result->get_error_code().': '.$result->get_error_message());return $result;}
function balance($user,$module,$status) {return (int) (Credits::balances($user)[$module][$status] ?? 0);}
function test_user($login,$name) {$id=username_exists($login);if(!$id)$id=wp_create_user($login,'PmpLocalTest-7349!',$login.'@example.test');wp_update_user(['ID'=>$id,'display_name'=>$name,'first_name'=>explode(' ',$name)[0]]);return (int)$id;}
function latest_token() {global $mail;for($j=count($mail)-1;$j>=0;$j--){if(preg_match('/pmp_invite=([a-f0-9]{64})/',$mail[$j]['message'],$match))return $match[1];}throw new RuntimeException('Token de convite ausente.');}
function completed_answers($module) {$map=['direcao'=>[5,4,5,4,5,5],'influencia'=>[4,3,4,4,4,4],'constancia'=>[4,4,4,3,4,4],'criterio'=>[5,4,4,4,5,5]];$seen=[];$out=[];foreach($module['questions'] as $q){$key=$q['dimension'];$index=$seen[$key]??0;$seen[$key]=$index+1;$target=$map[$key][$index]??4;$out[$q['id']]=!empty($q['reverse'])?6-$target:$target;}return $out;}

try {
  global $wpdb;
  $buyer=test_user('comprador','Paulo Maeda');$participant=test_user('participante','Ana Oliveira');$outsider=test_user('terceiro','Carlos Souza');$buyer2=test_user('comprador2','Marina Costa');
  wp_set_current_user($buyer);
  check(count(Modules::all())===4,'Quatro módulos carregados');
  $bank=Modules::get('comportamento');
  foreach(Modules::all() as $m)check(Modules::validate($m)===true,'Esquema válido: '.$m['key']);
  check(is_wp_error(Scoring::calculate($bank,[])),'Conclusão bloqueada com respostas ausentes');
  $answers=completed_answers($bank);$score=value(Scoring::calculate($bank,$answers));
  check(abs($score['dimensions'][0]['mean']-4.667)<.001,'Pontuação com itens invertidos e média conhecida');
  $bad=$answers;$bad[array_key_first($bad)]=6;check(is_wp_error(Scoring::calculate($bank,$bad)),'Resposta fora da escala rejeitada');
  $bad=$answers;$bad['estranho']=3;check(is_wp_error(Scoring::calculate($bank,$bad)),'Pergunta desconhecida rejeitada');
  $bad=$answers;$bad[array_key_first($bad)]='4';check(is_wp_error(Scoring::calculate($bank,$bad)),'Coerção de texto em resposta rejeitada');

  $product=new WC_Product_Simple();$product->set_name('Pacote comportamento · 3 aplicações');$product->set_status('publish');$product->set_virtual(true);$product->set_regular_price('29.90');$product->update_meta_data('_pmp_module','comportamento');$product->update_meta_data('_pmp_pack_size',3);$product->save();
  $order=wc_create_order(['customer_id'=>$buyer]);$item_id=$order->add_product($product,2);$order->calculate_totals();$order->save();Commerce::sync_order($order->get_id());
  check(balance($buyer,'comportamento','available')===0,'Pedido pendente não libera aplicações');
  $order->payment_complete();Commerce::sync_order($order->get_id());Commerce::sync_order($order->get_id());
  check(balance($buyer,'comportamento','available')===6,'Pagamento libera seis aplicações sem duplicar callbacks');
  $product->update_meta_data('_pmp_pack_size',20);$product->save();Commerce::sync_order($order->get_id());
  check(balance($buyer,'comportamento','available')===6,'Pacote comprado preserva configuração do pedido');

  $self=value(Credits::claim($buyer,'comportamento'));$self_id=$self['attempt_id'];
  check(balance($buyer,'comportamento','available')===5&&balance($buyer,'comportamento','assigned')===1,'Uso próprio reserva uma única aplicação');
  value(Credits::release_self($self_id,$buyer));check(balance($buyer,'comportamento','available')===6,'Uso próprio não iniciado pode voltar ao saldo');
  $self=value(Credits::claim($buyer,'comportamento'));$self_id=$self['attempt_id'];
  check(is_wp_error(Assessments::start($self_id,$buyer,false)),'Início exige ciência das informações');
  value(Assessments::start($self_id,$buyer,true));value(Assessments::start($self_id,$buyer,true));
  check(balance($buyer,'comportamento','in_progress')===1,'Repetir início não consome outra aplicação');
  check(is_wp_error(Credits::release_self($self_id,$buyer)),'Teste iniciado não pode ser transferido');
  $one=[array_key_first($answers)=>$answers[array_key_first($answers)]];
  $save=value(Assessments::save($self_id,$buyer,$one,0));
  check($save['revision']===1,'Respostas parciais salvas com revisão');
  check(is_wp_error(Assessments::save($self_id,$buyer,$one,0)),'Salvamento de aba antiga bloqueado');
  check(is_wp_error(Assessments::save($self_id,$outsider,$answers,1)),'Outra conta não pode alterar respostas');
  $done=value(Assessments::save($self_id,$buyer,$answers,1,true));
  check($done['completed']&&balance($buyer,'comportamento','completed')===1,'Conclusão produz relatório e fecha aplicação');
  value(Assessments::save($self_id,$buyer,$answers,0,true));
  check(balance($buyer,'comportamento','completed')===1,'Conclusão repetida é idempotente');

  $inv=value(Invitations::create($buyer,'comportamento','participante@example.test'));$token=latest_token();
  check($inv['sent']&&balance($buyer,'comportamento','reserved')===1,'Convite por e-mail reserva crédito');
  $before=balance($buyer,'comportamento','reserved');Invitations::info($token);
  check(balance($buyer,'comportamento','reserved')===$before,'Abrir link não aceita convite nem consome crédito');
  check(is_wp_error(Invitations::accept($token,$outsider,true)),'Link vinculado ao e-mail impede outra conta');
  check(is_wp_error(Invitations::accept($token,$participant,false)),'Aceite exige ciência do compartilhamento');
  wp_set_current_user($participant);$accepted=value(Invitations::accept($token,$participant,true));$participant_id=$accepted['attempt_id'];
  check(value(Invitations::accept($token,$participant,true))['attempt_id']===$participant_id,'Aceite repetido não duplica aplicação');
  check(is_wp_error(Assessments::application($participant_id,$buyer)),'Comprador não acessa respostas do participante');
  value(Assessments::start($participant_id,$participant,true));value(Assessments::save($participant_id,$participant,$answers,0,true));
  $report=value(Assessments::report($participant_id,$buyer));
  check(!isset($report['answers'])&&count($report['result']['dimensions'])===4,'Comprador recebe somente relatório das dimensões');
  check(is_wp_error(Assessments::report($participant_id,$buyer2)),'Comprador diferente não acessa relatório');
  check(!is_wp_error(Assessments::report($participant_id,$participant)),'Participante acessa seu próprio relatório');
  $attempt=Assessments::get($participant_id);$snapshot=json_decode($attempt['snapshot'],true);
  $override=$bank;$override['version']='0.2.0';$override['title']='Título novo de teste';update_option('pmp_modules',['comportamento'=>$override],false);
  check(value(Assessments::report($participant_id,$buyer))['result']['module_version']==='0.1.0'&&$snapshot['title']===$bank['title'],'Resultado mantém versão original após edição do módulo');
  delete_option('pmp_modules');

  wp_set_current_user($buyer);value(Credits::grant($buyer,'servico',6));
  $cancel=value(Invitations::create($buyer,'servico','terceiro@example.test'));$cancel_token=latest_token();value(Invitations::cancel($cancel['id'],$buyer));
  check(balance($buyer,'servico','available')===6&&is_wp_error(Invitations::accept($cancel_token,$outsider,true)),'Cancelamento devolve saldo e invalida convite');
  $exp=value(Invitations::create($buyer,'servico','terceiro@example.test'));$wpdb->update(Database::table('invites'),['expires_at'=>gmdate('Y-m-d H:i:s',time()-10)],['id'=>$exp['id']]);Invitations::expire();
  check(balance($buyer,'servico','available')===6,'Expiração devolve crédito reservado');
  $again=value(Invitations::create($buyer,'servico','terceiro@example.test'));$old_token=latest_token();
  check(is_wp_error(Invitations::resend($again['id'],$buyer)),'Reenvio respeita intervalo mínimo');
  $wpdb->update(Database::table('invites'),['last_sent_at'=>gmdate('Y-m-d H:i:s',time()-120)],['id'=>$again['id']]);value(Invitations::resend($again['id'],$buyer));$new_token=latest_token();
  check($old_token!==$new_token&&is_wp_error(Invitations::accept($old_token,$outsider,true)),'Reenvio substitui token anterior');
  $accepted2=value(Invitations::accept($new_token,$outsider,true));value(Invitations::cancel($again['id'],$buyer));
  check(balance($buyer,'servico','available')===6&&Assessments::get($accepted2['attempt_id'])['status']==='cancelled','Aplicação aceita não iniciada pode ser cancelada');

  $refund=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>'29.90','line_items'=>[$item_id=>['qty'=>1,'refund_total'=>'29.90','refund_tax'=>[]]],'refund_payment'=>false,'restock_items'=>false]);value($refund);Commerce::sync_order($order->get_id());
  check(balance($buyer,'comportamento','revoked')===3&&balance($buyer,'comportamento','completed')===2,'Reembolso parcial revoga primeiro créditos não utilizados');
  $order=wc_get_order($order->get_id());$order->update_status('refunded');Commerce::sync_order($order->get_id());
  check(balance($buyer,'comportamento','revoked')===6,'Reembolso total bloqueia créditos restantes');
  check(is_wp_error(Assessments::report($participant_id,$buyer))&&!is_wp_error(Assessments::report($participant_id,$participant)),'Reembolso bloqueia comprador e preserva relatório do participante');

  $request=new WP_REST_Request('GET','/maeda-perfis/v1/state');wp_set_current_user(0);check(is_wp_error(Rest::permission($request)),'REST exige autenticação');
  wp_set_current_user($buyer);check(is_wp_error(Rest::permission($request)),'REST exige nonce contra CSRF');
  $request->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));check(Rest::permission($request)===true,'REST aceita sessão e nonce válidos');
  $badbank=$bank;$badbank['questions'][0]['dimension']='inexistente';check(is_wp_error(Modules::validate($badbank)),'Editor rejeita dimensão de pergunta inexistente');

  // Dados sintéticos para inspecionar a interface no mesmo ambiente.
  value(Credits::grant($buyer,'comportamento',12));value(Credits::grant($buyer,'lideranca',5));value(Credits::grant($buyer,'carreira',4));
  $visual=value(Credits::claim($buyer,'comportamento'));value(Assessments::start($visual['attempt_id'],$buyer,true));value(Assessments::save($visual['attempt_id'],$buyer,$answers,0,true));
  $pending=value(Credits::claim($buyer,'lideranca'));
  $qa=value(Invitations::create($buyer,'servico','participante@example.test'));$qa_token=latest_token();
  $new=value(Invitations::create($buyer,'servico','novaparticipante@example.test'));$new_token=latest_token();
  $reports=['comportamento'=>$visual['attempt_id']];
  foreach(['carreira','servico','lideranca'] as $key){$application=value(Credits::claim($buyer,$key));$module=Modules::get($key);$all=[];foreach($module['questions'] as $q)$all[$q['id']]=!empty($q['reverse'])?2:4;value(Assessments::start($application['attempt_id'],$buyer,true));value(Assessments::save($application['attempt_id'],$buyer,$all,0,true));$reports[$key]=$application['attempt_id'];check(count(value(Assessments::report($application['attempt_id'],$buyer))['result']['dimensions'])===count($module['dimensions']),'Relatório completo: '.$key);}
  update_option('woocommerce_enable_myaccount_registration','no');
  $fixture=['buyer'=>$buyer,'participant'=>$participant,'outsider'=>$outsider,'buyer2'=>$buyer2,'report_id'=>$visual['attempt_id'],'reports'=>$reports,'pending_id'=>$pending['attempt_id'],'invite_token'=>$qa_token,'invite_id'=>$qa['id'],'new_invite_token'=>$new_token,'password'=>'PmpLocalTest-7349!'];
  echo wp_json_encode(['success'=>true,'checks'=>$checks,'versions'=>['wp'=>get_bloginfo('version'),'woo'=>WC_VERSION,'php'=>PHP_VERSION],'fixture'=>$fixture]);
} catch(Throwable $error) {
  global $wpdb;http_response_code(500);echo wp_json_encode(['success'=>false,'checks'=>$checks,'error'=>$error->getMessage(),'db_error'=>$wpdb->last_error]);
}
