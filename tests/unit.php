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
require PMP_PATH . 'includes/class-narrative.php';
use Maeda\Perfis\{Modules,Scoring};
$checks=0;
function ensure($condition,$label) {global $checks;if(!$condition)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;$checks++;}
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

function target_answers($module,$targets) {
    $answers=[];
    foreach($module['questions'] as $q) {
        $target=$targets[$q['dimension']] ?? 3;
        $answers[$q['id']]=!empty($q['reverse'])?6-$target:$target;
    }
    return $answers;
}
$flat=Scoring::calculate($bank,target_answers($bank,[]));
ensure(str_contains(implode(' ',$flat['narrative']['summary']),'Todas as dimensões ficaram com a mesma média'),'Resumo preserva perfil sem uma dimensão dominante');
ensure($flat['narrative']['dimensions'][0]['band']==='middle','Média 3 recebe leitura intermediária');
$low=Scoring::calculate($bank,target_answers($bank,['direcao'=>2,'influencia'=>1,'constancia'=>1,'criterio'=>1]));
ensure(str_contains(implode(' ',$low['narrative']['summary']),'Mesmo a maior média está abaixo de 2,5'),'Maior pontuação baixa não vira característica intensa');
ensure($low['narrative']['dimensions'][0]['band']==='low','Média baixa recebe leitura de menor concordância');
$high=Scoring::calculate($bank,target_answers($bank,['direcao'=>5,'influencia'=>4,'constancia'=>4,'criterio'=>4]));
ensure(str_contains(implode(' ',$high['narrative']['summary']),'não deve ser tratada como uma fragilidade'),'Menor média alta não é descrita como fragilidade');
ensure($high['narrative']['dimensions'][0]['band']==='high','Média alta recebe leitura de maior concordância');
ensure($low['narrative']['dimensions'][0]['reading']!==$high['narrative']['dimensions'][0]['reading'],'Leituras mudam para resultados diferentes');
$tie=Scoring::calculate($bank,target_answers($bank,['direcao'=>5,'criterio'=>5,'influencia'=>2,'constancia'=>2]));
$summary=implode(' ',$tie['narrative']['summary']);
ensure(str_contains($summary,'O empate foi preservado')&&str_contains($summary,'Direção e iniciativa e Critério e precisão'),'Empate no topo não é arbitrariamente ordenado');
$secondary=Scoring::calculate($bank,target_answers($bank,['direcao'=>5,'influencia'=>4,'constancia'=>1,'criterio'=>4]));
ensure(str_contains(implode(' ',$secondary['narrative']['combination']),'Expressão e conexão e Critério e precisão compartilham'),'Empate na posição seguinte não elege uma preferência secundária arbitrária');
$lower_tie=Scoring::calculate($bank,target_answers($bank,['direcao'=>5,'influencia'=>1,'constancia'=>1,'criterio'=>1]));
ensure(str_contains(implode(' ',$lower_tie['narrative']['summary']),'Expressão e conexão, Constância e apoio e Critério e precisão compartilham a menor média'),'Menores médias empatadas também são preservadas na síntese');
$nearanswers=target_answers($bank,[]);$nearanswers['com_01']=4;
$near=Scoring::calculate($bank,$nearanswers);
ensure(str_contains(implode(' ',$near['narrative']['summary']),'As médias estão próximas'),'Diferenças pequenas recebem qualificação no resumo');
$mixed=target_answers($bank,[]);$position=0;
foreach($bank['questions'] as $q){if($q['dimension']==='direcao'){$target=$position++%2?5:1;$mixed[$q['id']]=!empty($q['reverse'])?6-$target:$target;}}
$mixedresult=Scoring::calculate($bank,$mixed);
ensure(str_contains($mixedresult['narrative']['dimensions'][0]['nuance'],'respostas bastante diferentes'),'Média intermediária com respostas divergentes é qualificada');
$custom=$bank;$custom['dimensions'][0]['description']='Sua reflexão sobre processos autorais de desenho.';
$customresult=Scoring::calculate($custom,target_answers($custom,[]));
ensure(str_contains($customresult['narrative']['dimensions'][0]['reading'],'processos autorais de desenho'),'Instrumento editado não recebe semântica de outro questionário');
foreach($all as $module){
    $narrative=Scoring::calculate($module,target_answers($module,[]))['narrative'];
    ensure(count($narrative['dimensions'])===count($module['dimensions']),'Leitura detalhada cobre todas as dimensões: '.$module['key']);
    ensure(count($narrative['plan'])===3,'Plano de experiências completo: '.$module['key']);
    $text=json_encode($narrative,JSON_UNESCAPED_UNICODE);
    ensure(strlen($text)>7000,'Relatório com conteúdo desenvolvido: '.$module['key']);
    ensure(!str_contains($text,$module['questions'][0]['id']),'Leitura não expõe identificadores ou respostas de itens: '.$module['key']);
    if(in_array($module['key'],['carreira','servico'],true))ensure($narrative['dimensions'][0]['band_label']==='Interesse intermediário','Interesses não são descritos como traços: '.$module['key']);
}
echo $checks.' verificações aprovadas.'.PHP_EOL;
