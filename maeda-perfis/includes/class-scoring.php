<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Scoring
{
    public static function validate_answers(array $module, array $answers, bool $complete = false)
    {
        $ids = array_column($module['questions'], 'id');
        foreach ($answers as $key => $value) {
            if (!in_array((string) $key, $ids, true) || !is_int($value) || $value < 1 || $value > 5) {
                return Plugin::error('answer', 'Uma resposta está inválida. Recarregue a aplicação e tente novamente.');
            }
        }
        if ($complete && count($answers) !== count($ids)) {
            return Plugin::error('incomplete', 'Responda a todas as perguntas antes de concluir.');
        }
        return true;
    }

    public static function calculate(array $module, array $answers)
    {
        $valid = self::validate_answers($module, $answers, true);
        if (is_wp_error($valid)) {
            return $valid;
        }
        $buckets = [];
        foreach ($module['dimensions'] as $dimension) {
            $buckets[$dimension['key']] = [];
        }
        foreach ($module['questions'] as $question) {
            $value = $answers[$question['id']];
            $buckets[$question['dimension']][] = !empty($question['reverse']) ? 6 - $value : $value;
        }
        $dimensions = [];
        foreach ($module['dimensions'] as $dimension) {
            $values = $buckets[$dimension['key']];
            $dimensions[] = array_merge($dimension, [
                'mean' => round(array_sum($values) / count($values), 3),
                'answered' => count($values),
            ]);
        }
        $maximum = max(array_column($dimensions, 'mean'));
        $highlights = array_values(array_map(static fn($d) => $d['key'], array_filter($dimensions, static fn($d) => $d['mean'] === $maximum)));
        return [
            'module_key' => $module['key'], 'module_version' => $module['version'],
            'title' => $module['title'], 'pilot' => true,
            'reference' => $module['reference'] ?? 'Instrumento autoral',
            'dimensions' => $dimensions, 'highlights' => $highlights,
            'question_count' => count($answers), 'scale_min' => 1, 'scale_max' => 5,
            'interpretation' => 'As médias descrevem suas respostas neste questionário. Não são percentis, diagnóstico, previsão de desempenho ou medida de aptidão. Use o relatório para refletir sobre experiências e planejar próximos passos.',
        ];
    }
}
