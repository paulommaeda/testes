<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Mail
{
    public static function send(string $email, string $subject, string $heading, string $body, string $url, string $button): bool
    {
        $site = Site::brand();
        $message = '<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f4f5fa;padding:32px 16px;font-family:Arial,sans-serif;color:#172033">'
            . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:32px">'
            . '<p style="font-size:13px;color:#6550ce;font-weight:bold">' . esc_html($site) . '</p>'
            . '<h1 style="font-size:24px;line-height:1.3">' . esc_html($heading) . '</h1>'
            . '<p style="font-size:16px;line-height:1.7">' . nl2br(esc_html($body)) . '</p>'
            . '<p style="margin:28px 0"><a href="' . esc_url($url) . '" style="display:inline-block;padding:15px 24px;background:#6550ce;color:#fff;border-radius:10px;text-decoration:none;font-weight:bold">' . esc_html($button) . '</a></p>'
            . '<p style="font-size:12px;color:#68758a;line-height:1.6">Se o botão não abrir, copie este endereço para o navegador:<br>' . esc_html($url) . '</p>'
            . '</div></body></html>';
        return wp_mail($email, sanitize_text_field($subject), $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    public static function invitation(array $invite, string $token, array $credit): bool
    {
        $buyer = get_user_by('id', (int) $invite['buyer_id']);
        $module = Modules::get($credit['module_key']);
        $name = $buyer ? $buyer->display_name : 'O responsável pela aplicação';
        return self::send(
            $invite['email'], 'Você recebeu um convite para um teste de perfil', 'Um novo passo para se conhecer',
            $name . ' liberou o teste “' . ($module['title'] ?? $credit['module_key']) . '” para você.' . "\n\n"
            . 'Acesse o link, crie sua conta ou entre com este mesmo e-mail e aceite o convite. Ao concluir, você e a pessoa que comprou esta aplicação poderão consultar o relatório.' . "\n\n"
            . 'Este convite é individual e expira em ' . wp_date('d/m/Y H:i', strtotime($invite['expires_at'] . ' UTC')) . '.',
            Plugin::url(['pmp_invite'=>$token]), 'Acessar meu teste'
        );
    }

    public static function completed(array $attempt): void
    {
        $participant = get_user_by('id', (int) $attempt['user_id']);
        $buyer = get_user_by('id', (int) $attempt['buyer_id']);
        $module = json_decode($attempt['snapshot'], true);
        $url = Plugin::url(['pmp_report'=>(int) $attempt['id']]);
        $sent = $participant ? self::send($participant->user_email, 'Seu relatório de perfil está disponível', 'Seu relatório está pronto',
            'Você concluiu o teste “' . ($module['title'] ?? '') . '”. Entre na sua conta para consultar os gráficos, a leitura das dimensões e os próximos passos.', $url, 'Ver meu relatório') : true;
        if ($buyer && (int) $buyer->ID !== (int) $attempt['user_id']) {
            $sent = self::send($buyer->user_email, 'Um participante concluiu o teste de perfil', 'Uma aplicação foi concluída',
                ($participant ? $participant->display_name : 'Um participante') . ' concluiu o teste “' . ($module['title'] ?? '') . '” que você liberou. O relatório está disponível na sua conta.', $url, 'Consultar resultado') && $sent;
        }
        Database::audit($sent ? 'result_email_sent' : 'result_email_failed', (int) $attempt['id']);
    }
}
