<?php
// Testes do esquema e da pontuação com resultados esperados independentes.
define('ABSPATH', __DIR__ . '/');
define('PMP_PATH', dirname(__DIR__) . '/maeda-perfis/');
class WP_Error { public function __construct(public $code,public $message,public $data=[]) {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_option($key,$default=false) { return $default; }
require PMP_PATH . 'includes/class-plugin.php';
require PMP_PATH . 'includes/class-modules.php';
require PMP_PATH . 'includes/class-scoring.php';
use Maeda\Perfis\{Modules,Scoring};
$checks=0;
function ensure($condition,$label) {global $checks;if(!$condition){fwrite(STDERR,'FAIL '.$label.PHP_EOL);exit(1);}echo 'PASS '.$label.PHP_EOL;$checks++;}
$all=Modules::all();
ensure(count($all)===4,'Quatro bancos de perguntas válidos');
foreach($all as $module){$answers=[];foreach($module['questions'] as $q)$answers[$q['id']]=!empty($q['reverse'])?2:4;$result=Scoring::calculate($module,$answers);ensure(!is_wp_error($result),'Conclusão de '.$module['key']);foreach($result['dimensions'] as $d)ensure($d['mean']===4.0,'Média de 4 na dimensão '.$d['key']);ensure(count($result['highlights'])===count($result['dimensions']),'Empates preservados em '.$module['key']);}
$bank=$all['comportamento'];$answers=[];
foreach($bank['questions'] as $q)$answers[$q['id']]=4;
$result=Scoring::calculate($bank,$answers);
ensure(abs($result['dimensions'][0]['mean']-3.667)<.001,'Item invertido altera corretamente a média');
ensure(is_wp_error(Scoring::calculate($bank,[])),'Respostas incompletas rejeitadas');
$bad=$answers;$bad[array_key_first($bad)]=0;ensure(is_wp_error(Scoring::calculate($bank,$bad)),'Resposta abaixo da escala rejeitada');
$bad=$answers;$bad[array_key_first($bad)]=6;ensure(is_wp_error(Scoring::calculate($bank,$bad)),'Resposta acima da escala rejeitada');
$bad=$answers;$bad[array_key_first($bad)]='4';ensure(is_wp_error(Scoring::calculate($bank,$bad)),'Texto não é convertido silenciosamente em resposta');
$bad=$answers;$bad['outro']=4;ensure(is_wp_error(Scoring::calculate($bank,$bad)),'Identificador desconhecido rejeitado');
$bad=$bank;$bad['questions'][1]['id']=$bad['questions'][0]['id'];ensure(is_wp_error(Modules::validate($bad)),'IDs duplicados rejeitados');
$bad=$bank;$bad['questions'][0]['dimension']='outra';ensure(is_wp_error(Modules::validate($bad)),'Dimensão desconhecida rejeitada');
$bad=$bank;$bad['questions'][0]['dimension']=[];ensure(is_wp_error(Modules::validate($bad)),'Tipo inválido de dimensão rejeitado sem exceção');
echo $checks.' verificações aprovadas.'.PHP_EOL;
