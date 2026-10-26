<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Privacy
{
    public static function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters',static function ($exporters) {
            $exporters['maeda-perfis'] = ['exporter_friendly_name'=>'Nortume','callback'=>[self::class,'export']];
            return $exporters;
        });
        add_filter('wp_privacy_personal_data_erasers',static function ($erasers) {
            $erasers['maeda-perfis'] = ['eraser_friendly_name'=>'Nortume','callback'=>[self::class,'erase']];
            return $erasers;
        });
        add_action('admin_init',static function () {
            if (function_exists('wp_add_privacy_policy_content')) {
                wp_add_privacy_policy_content('Nortume','<h2>Testes de perfil</h2><p>Armazenamos as respostas, a versão do questionário, o relatório, as datas de aplicação e o vínculo com a compra. Nas aplicações por convite, o participante é informado antes do aceite de que o relatório final será compartilhado com quem adquiriu aquela aplicação. O comprador não tem acesso às respostas individuais nem a outros testes do participante. Os e-mails de convite e conclusão contêm links para a conta, e o site registra eventos de liberação e acesso operacional. Defina aqui os responsáveis pelo tratamento, a finalidade, os prazos de retenção, os canais para exercício de direitos e o fluxo específico para menores, quando aplicável.</p>');
            }
        });
    }

    public static function export(string $email,int $page = 1): array
    {
        global $wpdb;
        $user = get_user_by('email',$email);
        if (!$user) return ['data'=>[],'done'=>true];
        $table = Database::table('attempts');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY id LIMIT 50 OFFSET %d",$user->ID,(max(1,$page)-1)*50),ARRAY_A);
        $data = [];
        foreach ($rows as $row) {
            $data[] = ['group_id'=>'maeda-perfis','group_label'=>'Testes de perfil','item_id'=>'pmp-attempt-' . $row['id'],
                'data'=>[
                    ['name'=>'Módulo e versão','value'=>$row['module_key'] . ' ' . $row['module_version']],
                    ['name'=>'Situação','value'=>$row['status']],['name'=>'Data','value'=>$row['created_at']],
                    ['name'=>'Respostas','value'=>$row['answers']],['name'=>'Relatório','value'=>$row['result']],
                    ['name'=>'Informação sobre compartilhamento','value'=>$row['sharing_disclosure']],
                ]];
        }
        return ['data'=>$data,'done'=>count($rows)<50];
    }

    public static function erase(string $email,int $page = 1): array
    {
        global $wpdb;
        $user = get_user_by('email',$email);
        $removed = false;
        $done = true;
        if ($user) {
            $table = Database::table('attempts');
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id,credit_id,status FROM $table WHERE user_id = %d LIMIT 50",$user->ID),ARRAY_A);
            $done = count($rows)<50;
            foreach ($rows as $row) {
                $result = Database::transaction(static function () use ($row) {
                    global $wpdb;
                    $credit = Credits::get((int) $row['credit_id'],true);
                    if ($credit && (int) $credit['attempt_id'] === (int) $row['id']) {
                        $data = ['assignee_id'=>0,'attempt_id'=>0,'updated_at'=>Plugin::now()];
                        if ($credit['status'] === 'assigned') $data['status'] = 'available';
                        if ($wpdb->update(Database::table('credits'),$data,['id'=>$credit['id']]) === false) return Plugin::error('erase','Falha ao remover o vínculo.',500);
                    }
                    if ($wpdb->delete(Database::table('attempts'),['id'=>$row['id']]) === false) return Plugin::error('erase','Falha ao remover a aplicação.',500);
                    return true;
                });
                if (is_wp_error($result)) return ['items_removed'=>$removed,'items_retained'=>true,'messages'=>['Não foi possível remover uma aplicação. Tente novamente.'],'done'=>false];
                $removed = true;
            }
        }
        $invites = Database::table('invites');
        $pending = $wpdb->get_results($wpdb->prepare("SELECT id,buyer_id FROM $invites WHERE email = %s AND status = 'pending'",strtolower($email)),ARRAY_A);
        foreach ($pending as $invite) Invitations::cancel((int) $invite['id'],(int) $invite['buyer_id']);
        $deleted = $wpdb->delete($invites,['email'=>strtolower($email)]);
        $retained = false;
        if ($user) {
            $credits = Database::table('credits');
            $retained = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $credits WHERE buyer_id = %d",$user->ID)) > 0;
        }
        return ['items_removed'=>$removed || (bool) $deleted,'items_retained'=>$retained,
            'messages'=>$retained ? ['O histórico de créditos e compras permanece para conciliação. Aplicações respondidas por outras pessoas não foram removidas por este pedido. Revise a retenção comercial conforme a política do site.'] : [],'done'=>$done];
    }
}
