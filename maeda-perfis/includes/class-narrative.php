<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

/** Interpretação determinística: usa o instrumento e as respostas da aplicação. */
final class Narrative
{
    public const VERSION = '1.0.0';

    public static function build(array $result, array $module, array $answers = []): array
    {
        $dimensions = $result['dimensions'];
        $ordered = $dimensions;
        usort($ordered, static fn($a, $b) => $b['mean'] <=> $a['mean']);
        $top = $ordered[0];
        $bottom = $ordered[count($ordered) - 1];
        $spread = round($top['mean'] - $bottom['mean'], 3);
        $leaders = array_values(array_filter($dimensions, static fn($d) => $d['mean'] === $top['mean']));
        $lowest = array_values(array_filter($dimensions, static fn($d) => $d['mean'] === $bottom['mean']));
        $interest = in_array($result['module_key'], ['carreira', 'servico'], true);
        $guides = json_decode(file_get_contents(PMP_PATH . 'content/report-guides.json'), true) ?: [];
        $guide = $guides[$result['module_key']] ?? [];
        $original_file = PMP_PATH . 'data/' . basename($result['module_key']) . '.json';
        $original = is_file($original_file) ? json_decode(file_get_contents($original_file), true) : [];
        if (array_map(static fn($d) => [$d['key'],$d['label'],$d['description'],$d['action']], $module['dimensions'] ?? []) !== array_map(static fn($d) => [$d['key'],$d['label'],$d['description'],$d['action']], $original['dimensions'] ?? [])) $guide = [];
        $summary = [$guide['intro'] ?? 'Este resumo reúne as médias das dimensões do questionário e propõe temas para conversar sobre suas experiências. Considere o contexto em que respondeu e os exemplos que consegue reconhecer no cotidiano.'];
        $names = self::join(array_column($leaders, 'label'));
        $value = self::number($top['mean']);
        if ($spread === 0.0) {
            $summary[] = 'Todas as dimensões ficaram com a mesma média (' . $value . ' de 5). Por isso, este conjunto de respostas não destaca uma área sobre as demais. ' . ($interest ? 'Isso pode representar interesses amplos, moderados ou pouco presentes, conforme o valor da média; não define uma única direção de carreira ou serviço.' : 'A igualdade das médias não demonstra equilíbrio de habilidades: apenas indica a mesma pontuação média entre os temas avaliados.');
        } elseif ($spread <= 0.35) {
            $summary[] = 'As médias estão próximas: a diferença entre a maior e a menor é de ' . self::number($spread) . ' ponto na escala de 1 a 5. ' . $names . ($leaders && count($leaders) > 1 ? ' aparecem' : ' aparece') . ' numericamente à frente, com ' . $value . ', mas essa pequena distância não sustenta uma leitura de predominância marcada. Os exemplos concretos de cada dimensão serão mais úteis do que um rótulo de perfil.';
        } else {
            $summary[] = ($leaders && count($leaders) > 1 ? 'As maiores médias deste conjunto aparecem em ' : 'A maior média deste conjunto aparece em ') . $names . ', com ' . $value . ' de 5. ' . (count($leaders) > 1 ? 'O empate foi preservado e não há uma vencedora entre essas dimensões. ' : '') . ($interest ? 'Esses temas são pontos de partida para experiências que você pode querer conhecer melhor. A pontuação expressa interesse declarado, sem comprovar experiência, desempenho ou aptidão.' : 'Esses temas receberam maior concordância relativa nas suas respostas. A leitura descreve como você se percebe neste momento e precisa ser relacionada ao que de fato acontece nas suas situações de vida.');
        }
        if ($top['mean'] < 2.5) {
            $summary[] = $interest ? 'Mesmo a maior média está abaixo de 2,5. Ser a área de maior pontuação relativa não significa interesse elevado. Pode ser mais útil ampliar o contato com atividades e conhecer outras possibilidades antes de concentrar escolhas em um único caminho.' : 'Mesmo a maior média está abaixo de 2,5. Assim, a área numericamente mais alta não deve ser descrita como uma característica intensa. Reflita sobre quanto as situações do questionário fazem parte da sua rotina e sobre quais comportamentos você gostaria de experimentar com apoio.';
        } elseif ($bottom['mean'] >= 3.5) {
            $summary[] = $interest ? 'Todas as dimensões alcançaram médias a partir de 3,5. Há interesse declarado em vários tipos de atividade, inclusive na área de menor média relativa. Para escolher uma primeira experiência, considere curiosidade, oportunidade de acesso e disponibilidade, sem tratar as demais opções como incompatíveis.' : 'Todas as dimensões alcançaram médias a partir de 3,5. Você demonstrou concordância com diferentes conjuntos de comportamentos. A dimensão de menor média relativa também teve respostas predominantemente próximas da concordância e não deve ser tratada como uma fragilidade.';
        } elseif ($spread > 0.35) {
            $summary[] = (count($lowest) === 1 ? $bottom['label'] . ' ficou com ' . self::number($bottom['mean']) . ' de 5, a menor média relativa deste conjunto. ' : self::join(array_column($lowest, 'label')) . ' compartilham a menor média relativa deste conjunto, com ' . self::number($bottom['mean']) . ' de 5. ') . ($interest ? 'Isso sugere menor interesse declarado em comparação com os outros temas, sem excluir a possibilidade de interesse futuro. Compare a pontuação com o contato que você já teve com essas atividades.' : 'Esse resultado oferece uma pergunta para reflexão, sem demonstrar uma deficiência. Considere se você teve oportunidade, necessidade ou liberdade para expressar esses comportamentos no contexto usado ao responder.');
        }
        $details = [];
        foreach ($dimensions as $dimension) {
            $mean = (float) $dimension['mean'];
            $band = $mean < 2.5 ? 'low' : ($mean < 3.5 ? 'middle' : 'high');
            $copy = $guide['dimensions'][$dimension['key']] ?? [];
            // Um módulo editado conserva sua própria semântica; não recebe textos de outro instrumento.
            if (($copy['label'] ?? '') !== $dimension['label']) $copy = [];
            $texts = $copy[$band] ?? [];
            $reading = $texts['reading'] ?? self::fallback($dimension, $band, $interest);
            $nuance = self::variation($dimension['key'], $module, $answers, $interest);
            $details[] = [
                'key' => $dimension['key'], 'label' => $dimension['label'], 'mean' => $mean,
                'band' => $band, 'band_label' => self::band_label($band, $interest),
                'reading' => $reading, 'nuance' => $nuance,
                'opportunity' => $texts['opportunity'] ?? 'Relacione essa leitura com uma situação recente. Observe o que você fez, as condições disponíveis e o resultado que gostaria de construir em uma próxima experiência.',
                'attention' => $texts['attention'] ?? 'A média resume diferentes respostas e não explica sozinha suas motivações. Antes de decidir o que mudar, procure exemplos e converse com alguém que conheça o contexto.',
                'reflection' => $copy['reflection'] ?? 'Em quais situações essa descrição faz sentido para você, e em quais ela não representa sua experiência?',
                'action' => $dimension['action'],
            ];
        }
        $summary[] = $guide['context'] ?? 'Use este relatório como roteiro de reflexão. Uma média não explica sozinha seus motivos nem substitui a observação de experiências reais.';
        $combination = self::combination($ordered, $spread, $guide, $interest);
        return [
            'version' => self::VERSION, 'summary' => $summary,
            'combination_title' => $interest ? 'Como transformar interesses em experiências' : 'Como suas preferências podem atuar juntas',
            'combination' => $combination, 'dimensions' => $details,
            'conversation' => $guide['conversation'] ?? 'Compartilhe um exemplo que confirma a leitura e outro que a coloca em dúvida. Combine um próximo passo pequeno, observável e adequado ao contexto.',
            'plan' => [
                ['when' => 'Nos próximos 7 dias', 'title' => 'Reconheça exemplos reais', 'text' => 'Escolha duas dimensões que chamaram sua atenção. Registre uma situação em que cada uma apareceu e outra em que o resultado não descreve bem você. Anote também as condições que influenciaram a experiência.'],
                ['when' => 'Em até 14 dias', 'title' => $interest ? 'Experimente uma atividade' : 'Teste um ajuste pequeno', 'text' => $spread === 0.0 ? 'Como as médias empataram, escolha livremente uma das experiências sugeridas nas dimensões abaixo. Defina o que fará, em qual situação e como perceberá o efeito da experiência.' : $top['action']],
                ['when' => 'Em até 30 dias', 'title' => 'Reveja o que você aprendeu', 'text' => 'Converse com alguém que acompanhou a experiência. Compare a expectativa com o que aconteceu, registre o que fez sentido e escolha um próximo passo. A avaliação desta experiência importa mais do que tentar aumentar todas as médias.'],
            ],
            'method' => 'Textos produzidos por regras explícitas a partir das médias e da variação das respostas, sem comparação com outras pessoas. Faixas descritivas da escala: abaixo de 2,5; de 2,5 até menos de 3,5; de 3,5 a 5. Esses limites e a indicação de médias próximas (diferença até 0,35) são escolhas editoriais, sem significado diagnóstico ou corte validado. A versão desta leitura é ' . self::VERSION . '.',
        ];
    }

    private static function combination(array $ordered, float $spread, array $guide, bool $interest): array
    {
        if ($spread <= 0.35 || count($ordered) < 2) {
            return [$interest ? 'Sem uma separação nítida entre as médias, explore os temas em paralelo. Você pode comparar duas experiências curtas e observar em qual delas teve mais curiosidade, vontade de continuar e condições de participar.' : 'As médias próximas convidam a olhar situações, em vez de fixar um tipo. Observe se você muda sua forma de agir conforme a tarefa, as pessoas envolvidas ou a pressão do momento. Essa alternância só pode ser compreendida com exemplos.'];
        }
        $first = $ordered[0]; $second = $ordered[1];
        // Havendo empate no topo, a síntese já preserva todas as dimensões empatadas.
        $ties = array_filter($ordered, static fn($d) => $d['mean'] === $first['mean']);
        if (count($ties) > 1) return ['Há mais de uma dimensão na maior média. Explore as leituras dessas áreas sem estabelecer uma ordem entre elas. Escolha um contexto concreto para observar como os temas se aproximam ou exigem escolhas diferentes.'];
        $following = array_values(array_filter($ordered, static fn($d) => $d['mean'] === $second['mean']));
        if (count($following) > 1) {
            return [$first['label'] . ' tem a maior média (' . self::number($first['mean']) . '). Na sequência, ' . self::join(array_column($following, 'label')) . ' compartilham a média de ' . self::number($second['mean']) . '. O empate nessas áreas também foi preservado; não há motivo para selecionar somente uma delas como preferência secundária.', $interest ? 'Compare experiências que envolvam esses temas e observe o que desperta curiosidade. Essa ordem não determina uma profissão nem demonstra aptidão.' : 'Observe como esses temas aparecem em uma mesma situação. Procure exemplos de convergência e de tensão entre eles, sem transformar a ordem das médias em um tipo fixo de personalidade.'];
        }
        $text = $guide['connections'][$first['key']] ?? '';
        $out = [$first['label'] . ' (' . self::number($first['mean']) . ') e ' . $second['label'] . ' (' . self::number($second['mean']) . ') são as duas maiores médias numéricas. ' . ($first['mean'] < 2.5 ? 'Ambas permanecem em valores baixos da escala; esta ordem não deve ser lida como uma combinação forte de preferências.' : ($text ?: 'Considere uma situação em que ambos os temas estão presentes e observe como você responde às diferentes demandas.'))];
        $out[] = $interest ? 'Você não precisa converter essa ordem em uma profissão ou função fixa. Procure atividades que permitam conhecer mais de um desses temas e separe o interesse de hoje das habilidades que ainda pretende desenvolver.' : 'Uma combinação de médias não revela automaticamente como você resolve conflitos ou age sob pressão. Use as perguntas de cada dimensão para explorar quando essas preferências ajudam, quando entram em tensão e quais acordos tornam a colaboração mais clara.';
        return $out;
    }

    private static function variation(string $key, array $module, array $answers, bool $interest): string
    {
        $values = [];
        foreach ($module['questions'] ?? [] as $question) {
            if ($question['dimension'] === $key && isset($answers[$question['id']]) && is_int($answers[$question['id']])) {
                $value = $answers[$question['id']];
                $values[] = !empty($question['reverse']) ? 6 - $value : $value;
            }
        }
        if (count($values) < 3) return '';
        $range = max($values) - min($values);
        if ($range >= 3) return $interest ? 'Houve respostas bastante diferentes dentro desta dimensão. Algumas atividades podem interessar mais do que outras; a média não representa necessariamente o mesmo interesse por todas elas.' : 'Houve respostas bastante diferentes dentro desta dimensão, considerando os itens invertidos. A média reúne comportamentos que você reconhece em intensidades distintas; procure entender quais situações explicam essa diferença.';
        if ($range === 0) return 'As respostas desta dimensão ficaram no mesmo ponto da escala, considerando os itens invertidos. Isso indica uniformidade neste conjunto de respostas, sem demonstrar estabilidade do perfil em outros momentos.';
        return 'As respostas variaram dentro desta dimensão. Relacione a média aos exemplos do seu cotidiano para compreender as diferenças entre situações.';
    }

    private static function fallback(array $dimension, string $band, bool $interest): string
    {
        $lead = $interest ? ['low'=>'Você declarou menor interesse médio nas atividades desta dimensão.','middle'=>'Você declarou interesse médio intermediário nas atividades desta dimensão.','high'=>'Você declarou maior interesse médio nas atividades desta dimensão.'] : ['low'=>'Suas respostas tenderam à discordância com as afirmações desta dimensão.','middle'=>'Suas respostas ficaram em uma região intermediária da escala nesta dimensão.','high'=>'Suas respostas tenderam à concordância com as afirmações desta dimensão.'];
        return $lead[$band] . ' ' . $dimension['description'] . ' Relacione o resultado ao contexto da aplicação, sem transformá-lo em uma característica fixa.';
    }

    private static function band_label(string $band, bool $interest): string
    {
        return ($interest ? ['low'=>'Menor interesse declarado','middle'=>'Interesse intermediário','high'=>'Maior interesse declarado'] : ['low'=>'Menor concordância','middle'=>'Concordância intermediária','high'=>'Maior concordância'])[$band];
    }

    private static function number(float $value): string { return number_format($value, 2, ',', '.'); }
    private static function join(array $values): string
    {
        if (count($values) < 2) return (string) ($values[0] ?? '');
        $last = array_pop($values);
        return implode(', ', $values) . ' e ' . $last;
    }
}
