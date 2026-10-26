<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Credits
{
    public static function get(int $id, bool $lock = false): ?array
    {
        global $wpdb;
        $table = Database::table('credits');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d" . ($lock ? ' FOR UPDATE' : ''), $id), ARRAY_A);
        return $row ?: null;
    }

    public static function balances(int $buyer): array
    {
        global $wpdb;
        $table = Database::table('credits');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT module_key, status, COUNT(*) AS total FROM $table WHERE buyer_id = %d GROUP BY module_key,status", $buyer), ARRAY_A);
        $balances = [];
        foreach ($rows as $row) {
            $key = $row['module_key'];
            if (!isset($balances[$key])) {
                $balances[$key] = ['available'=>0,'reserved'=>0,'assigned'=>0,'in_progress'=>0,'completed'=>0,'revoked'=>0];
            }
            $balances[$key][$row['status']] = (int) $row['total'];
        }
        return $balances;
    }

    public static function find_available(int $buyer, string $module): ?array
    {
        global $wpdb;
        $table = Database::table('credits');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE buyer_id = %d AND module_key = %s AND status = 'available' ORDER BY id LIMIT 1 FOR UPDATE", $buyer, $module), ARRAY_A) ?: null;
    }

    public static function grant(int $buyer, string $module, int $quantity, ?int $order = null, ?int $item = null): array|\WP_Error
    {
        global $wpdb;
        if (!$buyer || !get_user_by('id', $buyer) || !Modules::get($module) || $quantity < 1 || $quantity > 2000) {
            return Plugin::error('grant', 'Comprador, módulo ou quantidade inválidos.');
        }
        $table = Database::table('credits');
        $ids = [];
        for ($unit = 1; $unit <= $quantity; $unit++) {
            if ($item) {
                $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE order_item_id = %d AND unit_no = %d", $item, $unit));
                if ($existing) {
                    $ids[] = (int) $existing;
                    continue;
                }
            }
            $ok = $wpdb->insert($table, [
                'buyer_id'=>$buyer, 'module_key'=>$module, 'order_id'=>$order, 'order_item_id'=>$item,
                'unit_no'=>$unit, 'status'=>'available', 'created_at'=>Plugin::now(), 'updated_at'=>Plugin::now(),
            ]);
            if ($ok === false) {
                // A unique order-unit key also protects repeated/concurrent gateway callbacks.
                $existing = $item ? $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE order_item_id = %d AND unit_no = %d", $item, $unit)) : 0;
                if (!$existing) {
                    return Plugin::error('grant', 'Não foi possível liberar os créditos.', 500);
                }
                $ids[] = (int) $existing;
            } else {
                $ids[] = (int) $wpdb->insert_id;
            }
        }
        return $ids;
    }

    public static function revoke(int $id, string $reason): bool
    {
        global $wpdb;
        $credit = self::get($id, true);
        if (!$credit || $credit['status'] === 'revoked') {
            return true;
        }
        $ok = $wpdb->update(Database::table('credits'), [
            'previous_status'=>$credit['status'], 'status'=>'revoked', 'revoked_reason'=>sanitize_key($reason), 'updated_at'=>Plugin::now(),
        ], ['id'=>$id]);
        $wpdb->update(Database::table('invites'), ['status'=>'revoked','updated_at'=>Plugin::now()], ['credit_id'=>$id,'status'=>'pending']);
        Database::audit('credit_revoked', $id, ['reason'=>$reason]);
        return $ok !== false;
    }

    public static function assign_locked(array $credit, int $participant, string $disclosure = '')
    {
        global $wpdb;
        $module = Modules::get($credit['module_key']);
        if (!$module) {
            return Plugin::error('module', 'Este módulo não está disponível.');
        }
        $now = Plugin::now();
        $ok = $wpdb->insert(Database::table('attempts'), [
            'credit_id'=>$credit['id'], 'buyer_id'=>$credit['buyer_id'], 'user_id'=>$participant,
            'module_key'=>$module['key'], 'module_version'=>$module['version'],
            'snapshot'=>wp_json_encode($module), 'status'=>'assigned', 'answers'=>'{}', 'result'=>'{}',
            'sharing_accepted_at'=>$now, 'sharing_disclosure'=>$disclosure,
            'created_at'=>$now, 'updated_at'=>$now,
        ]);
        if (!$ok) {
            return Plugin::error('assign', 'Não foi possível liberar esta aplicação.', 500);
        }
        $attempt = (int) $wpdb->insert_id;
        $ok = $wpdb->update(Database::table('credits'), [
            'status'=>'assigned', 'assignee_id'=>$participant, 'attempt_id'=>$attempt, 'updated_at'=>$now,
        ], ['id'=>$credit['id'], 'status'=>$credit['status']]);
        if ($ok !== 1) {
            return Plugin::error('conflict', 'Este crédito acabou de ser utilizado. Atualize a página.', 409);
        }
        Database::audit('credit_assigned', (int) $credit['id'], ['attempt_id'=>$attempt]);
        return ['attempt_id'=>$attempt];
    }

    public static function claim(int $buyer, string $module)
    {
        return Database::transaction(static function () use ($buyer, $module) {
            $credit = self::find_available($buyer, $module);
            if (!$credit) {
                return Plugin::error('balance', 'Você não tem créditos disponíveis neste módulo.', 409);
            }
            return self::assign_locked($credit, $buyer, 'Aplicação adquirida para uso próprio.');
        });
    }

    public static function release_self(int $id,int $user)
    {
        return Database::transaction(static function () use ($id,$user) {
            global $wpdb;
            $candidate = Assessments::get($id);
            if (!$candidate || (int) $candidate['user_id'] !== $user || (int) $candidate['buyer_id'] !== $user) return Plugin::error('access','Aplicação não encontrada.',404);
            $credit = self::get((int) $candidate['credit_id'],true);
            $attempt = Assessments::get($id,true);
            if (!$credit || (int) $credit['attempt_id'] !== $id || $credit['status'] !== 'assigned' || $attempt['status'] !== 'assigned') return Plugin::error('started','Uma aplicação já iniciada não pode ser devolvida ao saldo.',409);
            $a = $wpdb->update(Database::table('attempts'),['status'=>'cancelled','updated_at'=>Plugin::now()],['id'=>$id,'status'=>'assigned']);
            $c = $wpdb->update(Database::table('credits'),['status'=>'available','assignee_id'=>0,'attempt_id'=>0,'updated_at'=>Plugin::now()],['id'=>$credit['id'],'status'=>'assigned']);
            if ($a !== 1 || $c !== 1) return Plugin::error('conflict','Não foi possível devolver o crédito.',409);
            Database::audit('self_application_released',$id);
            return ['released'=>true];
        });
    }
}
