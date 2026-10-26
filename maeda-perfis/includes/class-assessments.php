<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Assessments
{
    public static function get(int $id, bool $lock = false): ?array
    {
        global $wpdb;
        $table = Database::table('attempts');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d" . ($lock ? ' FOR UPDATE' : ''), $id), ARRAY_A) ?: null;
    }

    public static function application(int $id, int $user)
    {
        $attempt = self::get($id);
        if (!$attempt || (int) $attempt['user_id'] !== $user) {
            return Plugin::error('access', 'Aplicação não encontrada.', 404);
        }
        $credit = Credits::get((int) $attempt['credit_id']);
        if (!$credit || (int) $credit['attempt_id'] !== $id || $credit['status'] === 'revoked' || $attempt['status'] === 'cancelled') {
            return Plugin::error('access', 'Esta aplicação não está mais disponível.', 403);
        }
        $module = json_decode($attempt['snapshot'], true);
        return [
            'id'=>$id, 'status'=>$attempt['status'], 'revision'=>(int) $attempt['revision'],
            'answers'=>json_decode($attempt['answers'], true) ?: (object) [],
            'module'=>Modules::public_data($module, true),
            'shared'=>(int) $attempt['buyer_id'] !== $user,
            'buyer_name'=>self::user_name((int) $attempt['buyer_id']),
        ];
    }

    public static function start(int $id, int $user, bool $acknowledged)
    {
        if (!$acknowledged) {
            return Plugin::error('acknowledgement', 'Confirme a leitura das informações para iniciar.');
        }
        return Database::transaction(static function () use ($id, $user) {
            global $wpdb;
            $candidate = self::get($id);
            if (!$candidate || (int) $candidate['user_id'] !== $user) {
                return Plugin::error('access', 'Aplicação não encontrada.', 404);
            }
            $credit = Credits::get((int) $candidate['credit_id'], true);
            $attempt = self::get($id, true);
            if (!$credit || (int) $credit['attempt_id'] !== $id || !in_array($credit['status'], ['assigned','in_progress','completed'], true) || $attempt['status'] === 'cancelled') {
                return Plugin::error('access', 'Esta aplicação não está disponível.', 403);
            }
            if ($attempt['status'] !== 'assigned') {
                return ['attempt_id'=>$id,'status'=>$attempt['status']];
            }
            $now = Plugin::now();
            $a = $wpdb->update(Database::table('attempts'), ['status'=>'in_progress','started_at'=>$now,'updated_at'=>$now], ['id'=>$id,'status'=>'assigned']);
            $c = $wpdb->update(Database::table('credits'), ['status'=>'in_progress','updated_at'=>$now], ['id'=>$credit['id'],'status'=>'assigned']);
            if ($a !== 1 || $c !== 1) {
                return Plugin::error('conflict', 'A aplicação foi alterada. Atualize a página.', 409);
            }
            Database::audit('attempt_started', $id);
            return ['attempt_id'=>$id,'status'=>'in_progress'];
        });
    }

    public static function save(int $id, int $user, array $answers, int $revision, bool $complete = false)
    {
        $completed_now = false;
        $outcome = Database::transaction(static function () use ($id, $user, $answers, $revision, $complete, &$completed_now) {
            global $wpdb;
            $candidate = self::get($id);
            if (!$candidate || (int) $candidate['user_id'] !== $user) {
                return Plugin::error('access', 'Aplicação não encontrada.', 404);
            }
            $credit = Credits::get((int) $candidate['credit_id'], true);
            $attempt = self::get($id, true);
            if (!$credit || (int) $credit['attempt_id'] !== $id || $credit['status'] === 'revoked' || $attempt['status'] === 'cancelled') {
                return Plugin::error('access', 'Esta aplicação foi cancelada ou revogada.', 403);
            }
            if ($attempt['status'] === 'completed') {
                return $complete ? ['id'=>$id,'completed'=>true,'revision'=>(int) $attempt['revision']] : Plugin::error('completed', 'Este teste já foi concluído e suas respostas estão fechadas.', 409);
            }
            if ($attempt['status'] !== 'in_progress' || $credit['status'] !== 'in_progress') {
                return Plugin::error('start', 'Inicie o teste antes de responder.', 409);
            }
            if ((int) $attempt['revision'] !== $revision) {
                return Plugin::error('revision', 'Há respostas mais recentes salvas em outra aba. Recarregue a aplicação para continuar.', 409);
            }
            $module = json_decode($attempt['snapshot'], true);
            $valid = Scoring::validate_answers($module, $answers, $complete);
            if (is_wp_error($valid)) {
                return $valid;
            }
            $data = ['answers'=>wp_json_encode($answers ?: (object) []),'revision'=>$revision+1,'updated_at'=>Plugin::now()];
            if ($complete) {
                $result = Scoring::calculate($module, $answers);
                if (is_wp_error($result)) {
                    return $result;
                }
                $data['result'] = wp_json_encode($result);
                $data['status'] = 'completed';
                $data['completed_at'] = Plugin::now();
            }
            $changed = $wpdb->update(Database::table('attempts'), $data, ['id'=>$id,'revision'=>$revision,'status'=>'in_progress']);
            if ($changed !== 1) {
                return Plugin::error('revision', 'Não foi possível salvar. Atualize a aplicação.', 409);
            }
            if ($complete) {
                if ($wpdb->update(Database::table('credits'), ['status'=>'completed','updated_at'=>Plugin::now()], ['id'=>$credit['id'],'status'=>'in_progress']) !== 1) {
                    return Plugin::error('conflict', 'Não foi possível concluir esta aplicação.', 409);
                }
                $completed_now = true;
                Database::audit('attempt_completed', $id, ['module_version'=>$attempt['module_version']]);
            }
            return ['id'=>$id,'completed'=>$complete,'revision'=>$revision+1,'saved_at'=>Plugin::now()];
        });
        if ($completed_now && !is_wp_error($outcome)) {
            Mail::completed(self::get($id));
        }
        return $outcome;
    }

    public static function report(int $id, int $user)
    {
        $attempt = self::get($id);
        if (!$attempt || $attempt['status'] !== 'completed') {
            return Plugin::error('report', 'Relatório não encontrado.', 404);
        }
        $credit = Credits::get((int) $attempt['credit_id']);
        $participant = (int) $attempt['user_id'] === $user;
        $buyer = (int) $attempt['buyer_id'] === $user && $credit && $credit['status'] !== 'revoked';
        if (!$participant && !$buyer && !user_can($user, 'manage_pmp')) {
            return Plugin::error('access', 'Relatório não encontrado.', 404);
        }
        return [
            'id'=>$id,'participant_name'=>self::user_name((int) $attempt['user_id']),
            'buyer_name'=>self::user_name((int) $attempt['buyer_id']),
            'completed_at'=>$attempt['completed_at'], 'started_at'=>$attempt['started_at'],
            'is_own'=>$participant, 'result'=>json_decode($attempt['result'], true),
        ];
    }

    public static function user_name(int $id): string
    {
        $user = get_user_by('id', $id);
        return $user ? $user->display_name : 'Conta removida';
    }
}
