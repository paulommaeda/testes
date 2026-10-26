<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Invitations
{
    public static function create(int $buyer, string $module, string $email)
    {
        $email = strtolower(trim(sanitize_email($email)));
        if (!is_email($email) || !Modules::get($module)) {
            return Plugin::error('invite', 'Informe um e-mail válido e selecione um módulo.');
        }
        $user = get_user_by('id', $buyer);
        if ($user && strtolower($user->user_email) === $email) {
            return Plugin::error('invite_self', 'Para responder com sua conta, use a opção Fazer meu teste.');
        }
        $token = bin2hex(random_bytes(32));
        $data = Database::transaction(static function () use ($buyer, $module, $email, $token) {
            global $wpdb;
            $rate = self::rate_limit($buyer);
            if (is_wp_error($rate)) {
                return $rate;
            }
            $credit = Credits::find_available($buyer, $module);
            if (!$credit) {
                return Plugin::error('balance', 'Não há créditos disponíveis para este módulo.', 409);
            }
            $now = Plugin::now();
            $invite = [
                'credit_id'=>$credit['id'], 'buyer_id'=>$buyer, 'email'=>$email,
                'token_hash'=>hash('sha256', $token), 'status'=>'pending',
                'expires_at'=>gmdate('Y-m-d H:i:s', time() + max(1, min(90, (int) get_option('pmp_invite_days', 7))) * DAY_IN_SECONDS),
                'accepted_by'=>0, 'accepted_at'=>null, 'mail_status'=>'pending',
                'last_sent_at'=>$now, 'updated_at'=>$now,
            ];
            $table = Database::table('invites');
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE credit_id = %d", $credit['id']));
            if ($existing) {
                $ok = $wpdb->update($table, $invite, ['id'=>$existing]);
                $invite['id'] = (int) $existing;
            } else {
                $invite['created_at'] = $now;
                $ok = $wpdb->insert($table, $invite);
                $invite['id'] = (int) $wpdb->insert_id;
            }
            if ($ok === false || $wpdb->update(Database::table('credits'), ['status'=>'reserved','updated_at'=>$now], ['id'=>$credit['id'],'status'=>'available']) !== 1) {
                return Plugin::error('conflict', 'O crédito foi utilizado em outra operação. Atualize a página.', 409);
            }
            Database::audit('invite_created', $invite['id'], ['credit_id'=>(int) $credit['id']]);
            return ['invite'=>$invite,'credit'=>$credit];
        });
        if (is_wp_error($data)) {
            return $data;
        }
        $sent = Mail::invitation($data['invite'], $token, $data['credit']);
        self::mail_status((int) $data['invite']['id'], $sent);
        return ['id'=>(int) $data['invite']['id'],'sent'=>$sent,'message'=>$sent ? 'Convite enviado. Um crédito ficou reservado.' : 'O convite foi criado, mas o e-mail não foi enviado. Confira o envio de e-mails do WordPress e use Reenviar.'];
    }

    private static function rate_limit(int $buyer)
    {
        global $wpdb;
        $events = Database::table('events');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $events WHERE actor_id = %d AND event_key IN ('invite_created','invite_resent') AND created_at > %s", $buyer, gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)));
        return $count >= 100 ? Plugin::error('rate', 'Você chegou ao limite de 100 envios por hora. Aguarde para enviar novos convites.', 429) : true;
    }

    private static function mail_status(int $id, bool $sent): void
    {
        global $wpdb;
        $wpdb->update(Database::table('invites'), ['mail_status'=>$sent ? 'sent' : 'failed'], ['id'=>$id]);
        Database::audit($sent ? 'invite_email_sent' : 'invite_email_failed', $id);
    }

    public static function lookup(string $token): ?array
    {
        global $wpdb;
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $table = Database::table('invites');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token_hash = %s", hash('sha256', $token)), ARRAY_A) ?: null;
    }

    public static function info(string $token)
    {
        $invite = self::lookup($token);
        if (!$invite) {
            return Plugin::error('invite', 'Este convite não foi encontrado ou foi substituído por um novo link.', 404);
        }
        $credit = Credits::get((int) $invite['credit_id']);
        if (!$credit) {
            return Plugin::error('invite', 'Esta aplicação não está mais disponível.', 404);
        }
        $module = Modules::get($credit['module_key']);
        $buyer = get_user_by('id', (int) $invite['buyer_id']);
        $user = wp_get_current_user();
        [$local,$domain] = explode('@', $invite['email'], 2);
        return [
            'id'=>(int) $invite['id'], 'buyer_name'=>$buyer ? $buyer->display_name : 'Responsável pela aplicação',
            'email_hint'=>substr($local,0,1) . '***@' . $domain,
            'module'=>$module ? Modules::public_data($module) : null,
            'status'=>($invite['status'] === 'pending' && $invite['expires_at'] < Plugin::now()) ? 'expired' : $invite['status'],
            'expires_at'=>$invite['expires_at'],
            'email_matches'=>$user->exists() && strtolower($user->user_email) === $invite['email'],
            'attempt_id'=>$invite['status'] === 'accepted' && (int) $invite['accepted_by'] === (int) $user->ID ? (int) $credit['attempt_id'] : null,
        ];
    }

    public static function accept(string $token, int $participant, bool $consent)
    {
        $candidate = self::lookup($token);
        $user = get_user_by('id', $participant);
        if (!$candidate || !$user) {
            return Plugin::error('invite', 'Convite inválido.', 404);
        }
        if (strtolower($user->user_email) !== $candidate['email']) {
            return Plugin::error('email', 'Entre com uma conta que use o mesmo e-mail que recebeu o convite.', 403);
        }
        if (!$consent) {
            return Plugin::error('consent', 'Confirme que está ciente do compartilhamento do relatório para aceitar.');
        }
        return Database::transaction(static function () use ($candidate, $participant, $token) {
            global $wpdb;
            $credit = Credits::get((int) $candidate['credit_id'], true);
            $table = Database::table('invites');
            $invite = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d FOR UPDATE", $candidate['id']), ARRAY_A);
            if (!$invite || !hash_equals($invite['token_hash'], hash('sha256', $token))) {
                return Plugin::error('invite', 'O link foi substituído. Use o convite mais recente.', 409);
            }
            if ($invite['status'] === 'accepted' && (int) $invite['accepted_by'] === $participant && $credit && (int) $credit['assignee_id'] === $participant && $credit['status'] !== 'revoked') {
                return ['attempt_id'=>(int) $credit['attempt_id']];
            }
            if ($invite['status'] !== 'pending' || $invite['expires_at'] < Plugin::now() || !$credit || $credit['status'] !== 'reserved') {
                return Plugin::error('invite', 'Este convite expirou ou não está mais disponível.', 410);
            }
            $buyer = get_user_by('id', (int) $invite['buyer_id']);
            $disclosure = 'Aceito realizar esta aplicação e estou ciente de que seu relatório final ficará disponível para mim e para ' . ($buyer ? $buyer->display_name : 'o responsável pela compra') . ', que a adquiriu. As respostas individuais não serão exibidas ao comprador.';
            $result = Credits::assign_locked($credit, $participant, $disclosure);
            if (is_wp_error($result)) {
                return $result;
            }
            if ($wpdb->update($table, ['status'=>'accepted','accepted_by'=>$participant,'accepted_at'=>Plugin::now(),'updated_at'=>Plugin::now()], ['id'=>$invite['id'],'status'=>'pending']) !== 1) {
                return Plugin::error('conflict', 'O convite foi alterado durante o aceite. Tente novamente.', 409);
            }
            Database::audit('invite_accepted', (int) $invite['id']);
            return $result;
        });
    }

    public static function resend(int $id, int $buyer)
    {
        global $wpdb;
        $table = Database::table('invites');
        $candidate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND buyer_id = %d", $id, $buyer), ARRAY_A);
        if (!$candidate) {
            return Plugin::error('invite', 'Convite não encontrado.', 404);
        }
        $token = bin2hex(random_bytes(32));
        $data = Database::transaction(static function () use ($candidate, $buyer, $token, $table) {
            global $wpdb;
            $credit = Credits::get((int) $candidate['credit_id'], true);
            $invite = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d FOR UPDATE", $candidate['id']), ARRAY_A);
            if (!$credit || $credit['status'] !== 'reserved' || !$invite || $invite['status'] !== 'pending') {
                return Plugin::error('invite', 'Somente convites pendentes podem ser reenviados.', 409);
            }
            if ($invite['last_sent_at'] && strtotime($invite['last_sent_at'] . ' UTC') > time() - 60) {
                return Plugin::error('rate', 'Aguarde um minuto entre os reenvios.', 429);
            }
            $rate = self::rate_limit($buyer);
            if (is_wp_error($rate)) {
                return $rate;
            }
            $invite['token_hash'] = hash('sha256', $token);
            $invite['expires_at'] = gmdate('Y-m-d H:i:s', time() + max(1,min(90,(int) get_option('pmp_invite_days',7))) * DAY_IN_SECONDS);
            $invite['last_sent_at'] = Plugin::now();
            $invite['updated_at'] = Plugin::now();
            $invite['mail_status'] = 'pending';
            if ($wpdb->update($table, $invite, ['id'=>$invite['id']]) === false) {
                return Plugin::error('invite', 'Não foi possível reenviar o convite.', 500);
            }
            Database::audit('invite_resent', (int) $invite['id']);
            return ['invite'=>$invite,'credit'=>$credit];
        });
        if (is_wp_error($data)) {
            return $data;
        }
        $sent = Mail::invitation($data['invite'], $token, $data['credit']);
        self::mail_status($id, $sent);
        return ['sent'=>$sent,'message'=>$sent ? 'Novo convite enviado. O link anterior deixou de funcionar.' : 'Não foi possível enviar o e-mail. Confira a configuração de envio do site.'];
    }

    public static function cancel(int $id, int $buyer, bool $expired = false)
    {
        global $wpdb;
        $table = Database::table('invites');
        $candidate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND buyer_id = %d", $id, $buyer), ARRAY_A);
        if (!$candidate) {
            return Plugin::error('invite', 'Convite não encontrado.', 404);
        }
        return Database::transaction(static function () use ($candidate, $expired, $table) {
            global $wpdb;
            $credit = Credits::get((int) $candidate['credit_id'], true);
            $invite = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d FOR UPDATE", $candidate['id']), ARRAY_A);
            if (!$invite || !in_array($invite['status'], ['pending','accepted'], true) || !$credit || !in_array($credit['status'], ['reserved','assigned'], true)) {
                return Plugin::error('invite', 'Uma aplicação iniciada não pode ser cancelada ou transferida.', 409);
            }
            if ($expired && ($invite['status'] !== 'pending' || $invite['expires_at'] >= Plugin::now())) {
                return true;
            }
            if ((int) $credit['attempt_id']) {
                $wpdb->update(Database::table('attempts'), ['status'=>'cancelled','updated_at'=>Plugin::now()], ['id'=>$credit['attempt_id'],'status'=>'assigned']);
            }
            $ok = $wpdb->update(Database::table('credits'), ['status'=>'available','assignee_id'=>0,'attempt_id'=>0,'updated_at'=>Plugin::now()], ['id'=>$credit['id'],'status'=>$credit['status']]);
            $changed = $wpdb->update($table, ['status'=>$expired ? 'expired' : 'cancelled','updated_at'=>Plugin::now()], ['id'=>$invite['id']]);
            if ($ok !== 1 || $changed === false) {
                return Plugin::error('conflict', 'Não foi possível cancelar esta aplicação.', 409);
            }
            Database::audit($expired ? 'invite_expired' : 'invite_cancelled', (int) $invite['id']);
            return ['message'=>'Aplicação cancelada. O crédito voltou ao saldo disponível.'];
        });
    }

    public static function expire(): void
    {
        global $wpdb;
        $table = Database::table('invites');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id,buyer_id FROM $table WHERE status = 'pending' AND expires_at < %s LIMIT 200", Plugin::now()), ARRAY_A);
        foreach ($rows as $row) {
            self::cancel((int) $row['id'], (int) $row['buyer_id'], true);
        }
    }
}
