<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Modules
{
    public static function all(): array
    {
        $modules = [];
        foreach (glob(PMP_PATH . 'data/*.json') ?: [] as $file) {
            $module = json_decode(file_get_contents($file), true);
            if (is_array($module) && !is_wp_error(self::validate($module))) {
                $modules[$module['key']] = $module;
            }
        }
        foreach ((array) get_option('pmp_modules', []) as $key => $module) {
            if (is_array($module) && !is_wp_error(self::validate($module)) && $module['key'] === $key) {
                $modules[$key] = $module;
            }
        }
        $ordered = [];
        foreach (['comportamento','lideranca','carreira','servico'] as $key) {
            if (isset($modules[$key])) $ordered[$key] = $modules[$key];
        }
        return $ordered + $modules;
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function validate(array $module)
    {
        foreach (['key','version','title','description','dimensions','questions','scale'] as $field) {
            if (!isset($module[$field])) {
                return Plugin::error('module', 'Campo obrigatório ausente: ' . $field);
            }
        }
        if (!is_string($module['key']) || !preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $module['key']) ||
            !is_string($module['version']) || !preg_match('/^[0-9][0-9A-Za-z.\-]{0,31}$/', $module['version'])) {
            return Plugin::error('module', 'Identificador ou versão inválidos.');
        }
        foreach (['title','description'] as $field) {
            if (!is_string($module[$field]) || $module[$field] === '' || strlen($module[$field]) > 3000) {
                return Plugin::error('module', 'Título ou descrição inválidos.');
            }
        }
        if (!is_array($module['dimensions']) || count($module['dimensions']) < 2 || count($module['dimensions']) > 8 ||
            !is_array($module['questions']) || count($module['questions']) < 8 || count($module['questions']) > 120) {
            return Plugin::error('module', 'Use de 2 a 8 dimensões e de 8 a 120 perguntas.');
        }
        if (!is_array($module['scale']) || array_map('intval', array_keys($module['scale'])) !== [1,2,3,4,5]) {
            return Plugin::error('module', 'A escala deve conter cinco opções de 1 a 5.');
        }
        foreach ($module['scale'] as $label) {
            if (!is_string($label) || $label === '' || strlen($label) > 100) {
                return Plugin::error('module', 'Rótulo de resposta inválido.');
            }
        }
        $dimensions = [];
        foreach ($module['dimensions'] as $dimension) {
            if (!is_array($dimension) || empty($dimension['key']) || !is_string($dimension['key']) || !preg_match('/^[a-z][a-z0-9_]{0,40}$/', $dimension['key']) || isset($dimensions[$dimension['key']])) {
                return Plugin::error('module', 'As dimensões devem ter identificadores únicos.');
            }
            foreach (['label','short_label','description','action'] as $field) {
                if (!isset($dimension[$field]) || !is_string($dimension[$field]) || $dimension[$field] === '' || strlen($dimension[$field]) > 2000) {
                    return Plugin::error('module', 'Dimensão sem textos completos.');
                }
            }
            $dimensions[$dimension['key']] = 0;
        }
        $ids = [];
        foreach ($module['questions'] as $question) {
            if (!is_array($question) || empty($question['id']) || !is_string($question['id']) || !preg_match('/^[a-zA-Z0-9_-]{1,40}$/', $question['id']) || isset($ids[$question['id']]) || !is_string($question['dimension'] ?? null) || !isset($dimensions[$question['dimension']])) {
                return Plugin::error('module', 'Pergunta com identificador ou dimensão inválidos.');
            }
            if (!isset($question['prompt']) || !is_string($question['prompt']) || $question['prompt'] === '' || strlen($question['prompt']) > 1000 || (isset($question['reverse']) && !is_bool($question['reverse']))) {
                return Plugin::error('module', 'Texto ou inversão de pergunta inválidos.');
            }
            $ids[$question['id']] = true;
            $dimensions[$question['dimension']]++;
        }
        if (min($dimensions) < 3) {
            return Plugin::error('module', 'Cada dimensão precisa de pelo menos três perguntas.');
        }
        return true;
    }

    public static function public_data(array $module, bool $questions = false): array
    {
        $result = array_intersect_key($module, array_flip(['key','version','title','description','icon','color','minutes','pilot','reference','instructions']));
        $result['question_count'] = count($module['questions']);
        $result['dimensions'] = array_map(static fn($d) => array_intersect_key($d, array_flip(['key','label','short_label','description'])), $module['dimensions']);
        if ($questions) {
            $result['scale'] = $module['scale'];
            $result['questions'] = array_map(static fn($q) => ['id' => $q['id'], 'prompt' => $q['prompt']], $module['questions']);
        }
        return $result;
    }
}
