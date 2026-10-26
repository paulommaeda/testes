<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

/** Conta Nortume: interface própria sobre usuários e pedidos nativos. */
final class Account
{
    public static function configure(): void
    {
        if (!class_exists('WooCommerce') || get_option('pmp_account_configured')) return;
        $page = (int) get_option('pmp_page_id');
        if (!$page || get_post_status($page) !== 'publish') return;
        $previous = (int) get_option('woocommerce_myaccount_page_id');
        update_option('pmp_previous_account_page_id', $previous, false);
        update_option('woocommerce_myaccount_page_id', $page);
        update_option('pmp_account_configured', true, false);
        $post = get_post($page);
        if ($post && $post->post_title === 'Meus perfis') wp_update_post(['ID'=>$page,'post_title'=>'Minha conta']);
    }

    public static function restore(): void
    {
        if (!get_option('pmp_account_configured')) return;
        if ((int) get_option('woocommerce_myaccount_page_id') === (int) get_option('pmp_page_id')) {
            update_option('woocommerce_myaccount_page_id', (int) get_option('pmp_previous_account_page_id'));
        }
        delete_option('pmp_account_configured');
        delete_option('pmp_previous_account_page_id');
    }

    public static function profile(int $user)
    {
        $record = get_userdata($user);
        if (!$user || !$record) return Plugin::error('account', 'Conta não encontrada.', 404);
        $billing = [];
        if (class_exists('WC_Customer')) {
            $customer = new \WC_Customer($user);
            $billing = $customer->get_billing();
        }
        return [
            'first_name'=>(string)$record->first_name, 'last_name'=>(string)$record->last_name,
            'display_name'=>$record->display_name, 'email'=>$record->user_email,
            'billing'=>array_intersect_key($billing, array_flip(self::billing_keys())),
        ];
    }

    public static function countries(): array
    {
        return function_exists('WC') && WC()->countries ? WC()->countries->get_allowed_countries() : [];
    }

    public static function save_profile(int $user, array $data)
    {
        $record = get_userdata($user);
        if (!$user || !$record) return Plugin::error('account', 'Conta não encontrada.', 404);
        $allowed = ['first_name','last_name','display_name','email','current_password','new_password','confirm_password'];
        if (array_diff(array_keys($data), $allowed)) return Plugin::error('account', 'A solicitação contém campos não reconhecidos.');
        foreach ($data as $key=>$value) if (!is_string($value) || strlen($value) > 500) return Plugin::error('account', 'Confira os dados informados.');
        $first = sanitize_text_field($data['first_name'] ?? '');
        $last = sanitize_text_field($data['last_name'] ?? '');
        $display = sanitize_text_field($data['display_name'] ?? '');
        $email = trim($data['email'] ?? '');
        if (!$first || !$last || !$display || strlen($first) > 100 || strlen($last) > 100 || strlen($display) > 100) return Plugin::error('account', 'Preencha nome, sobrenome e nome de exibição, com até 100 caracteres cada.');
        if (!is_email($email) || strlen($email) > 100) return Plugin::error('email', 'Informe um e-mail válido.');
        $existing = email_exists($email);
        if ($existing && (int)$existing !== $user) return Plugin::error('email', 'Não foi possível usar este e-mail. Confira o endereço informado.');
        $password = $data['new_password'] ?? '';
        $sensitive = strcasecmp($email, $record->user_email) !== 0 || $password !== '';
        if ($sensitive && !wp_check_password($data['current_password'] ?? '', $record->user_pass, $user)) return Plugin::error('password', 'Informe sua senha atual para alterar o e-mail ou a senha.');
        if ($password !== '' && (strlen($password) < 12 || strlen($password) > 128 || $password !== ($data['confirm_password'] ?? ''))) return Plugin::error('password', 'Use uma nova senha de 12 a 128 caracteres e repita a mesma senha na confirmação.');
        $update = ['ID'=>$user,'first_name'=>$first,'last_name'=>$last,'display_name'=>$display,'user_email'=>$email];
        if ($password !== '') $update['user_pass'] = $password;
        $saved = wp_update_user($update);
        if (is_wp_error($saved)) return Plugin::error('account', 'Não foi possível atualizar os dados da conta. Confira os campos e tente novamente.');
        if (get_current_user_id() === $user) { wp_set_current_user(0); wp_set_current_user($user); }
        return ['message'=>'Dados da conta atualizados.','profile'=>self::profile($user),'refresh_session'=>$password !== ''];
    }

    private static function billing_keys(): array
    {
        return ['first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone'];
    }

    public static function save_billing(int $user, array $data)
    {
        if (!$user || !get_userdata($user) || !class_exists('WC_Customer')) return Plugin::error('account', 'Conta não encontrada.', 404);
        if (array_diff(array_keys($data), self::billing_keys())) return Plugin::error('billing', 'O endereço contém campos não reconhecidos.');
        $clean = [];
        foreach ($data as $key=>$value) {
            if (!is_string($value) || strlen($value) > 300) return Plugin::error('billing', 'Confira os campos do endereço.');
            $clean[$key] = sanitize_text_field($value);
        }
        $country = strtoupper($clean['country'] ?? '');
        if (!$country || !array_key_exists($country, self::countries())) return Plugin::error('billing', 'Selecione um país disponível para faturamento.');
        $clean['country'] = $country;
        $fields = WC()->countries->get_address_fields($country, 'billing_');
        foreach ($fields as $key=>$field) {
            $key = preg_replace('/^billing_/', '', $key);
            if (in_array($key, self::billing_keys(), true) && !empty($field['required']) && empty($clean[$key])) return Plugin::error('billing', 'Preencha os campos obrigatórios do endereço de faturamento.');
        }
        if (!empty($clean['email']) && !is_email($clean['email'])) return Plugin::error('billing', 'Informe um e-mail de faturamento válido.');
        if (!empty($clean['phone']) && !\WC_Validation::is_phone($clean['phone'])) return Plugin::error('billing', 'Confira o telefone informado.');
        if (!empty($clean['postcode']) && !\WC_Validation::is_postcode($clean['postcode'], $country)) return Plugin::error('billing', 'Confira o código postal informado.');
        $states = WC()->countries->get_states($country);
        if (is_array($states) && $states && !empty($clean['state'])) {
            $clean['state'] = strtoupper($clean['state']);
            if (!array_key_exists($clean['state'], $states)) return Plugin::error('billing', 'Informe a sigla do estado, como PR ou SP.');
        }
        try {
            $customer = new \WC_Customer($user);
            $props = [];
            foreach ($clean as $key=>$value) $props['billing_' . $key] = $value;
            $valid = $customer->set_props($props);
            if (is_wp_error($valid)) return Plugin::error('billing', 'Confira os dados do endereço de faturamento.');
            $customer->save();
            do_action('woocommerce_customer_save_address', $user, 'billing', $fields);
        } catch (\Exception $error) { return Plugin::error('billing', 'Não foi possível salvar o endereço. Confira os dados e tente novamente.'); }
        return ['message'=>'Endereço de faturamento atualizado.','profile'=>self::profile($user)];
    }

    public static function orders(int $user, int $page = 1): array
    {
        $page = max(1, min(100000, $page));
        $empty = ['items'=>[],'page'=>$page,'total'=>0,'has_more'=>false];
        if (!$user || !function_exists('wc_get_orders')) return $empty;
        $query = wc_get_orders(['type'=>'shop_order','customer_id'=>$user,'status'=>array_keys(wc_get_order_statuses()),'limit'=>10,'page'=>$page,'paginate'=>true,'orderby'=>'date','order'=>'DESC']);
        $orders = [];
        foreach ($query->orders as $order) if ($order instanceof \WC_Order && (int)$order->get_customer_id() === $user) $orders[] = self::order_summary($order);
        return ['items'=>$orders,'page'=>$page,'total'=>(int)$query->total,'has_more'=>$page < (int)$query->max_num_pages];
    }

    private static function owned_order(int $id, int $user)
    {
        if ($id < 1) return Plugin::error('order', 'Pedido não encontrado.', 404);
        $order = function_exists('wc_get_order') ? wc_get_order($id) : false;
        if (!$user || !($order instanceof \WC_Order) || (int)$order->get_customer_id() !== $user || $order->get_status() === 'checkout-draft') return Plugin::error('order', 'Pedido não encontrado.', 404);
        return $order;
    }

    private static function order_summary($order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = ['name'=>$item->get_name(),'quantity'=>(int)$item->get_quantity(),'module'=>(string)$item->get_meta('_pmp_module'),'applications'=>(int)$item->get_meta('_pmp_pack_size') * (int)$item->get_quantity()];
        }
        $actions = function_exists('wc_get_account_orders_actions') ? wc_get_account_orders_actions($order) : [];
        $status = $order->get_status();
        $labels = ['pending'=>'Aguardando pagamento','processing'=>'Processando','on-hold'=>'Aguardando confirmação','completed'=>'Concluído','cancelled'=>'Cancelado','refunded'=>'Reembolsado','failed'=>'Pagamento não aprovado'];
        return ['id'=>$order->get_id(),'number'=>(string)$order->get_order_number(),'date'=>$order->get_date_created() ? $order->get_date_created()->date_i18n('d/m/Y') : '',
            'status'=>$status,'status_label'=>$labels[$status] ?? wc_get_order_status_name($status),
            'total'=>self::text($order->get_formatted_order_total()),'items'=>$items,
            'pay_url'=>isset($actions['pay']) && $order->needs_payment() ? esc_url_raw($order->get_checkout_payment_url()) : '',
            'can_cancel'=>isset($actions['cancel']) && $order->has_status(apply_filters('woocommerce_valid_order_statuses_for_cancel', ['pending','failed'], $order)),
        ];
    }

    public static function order(int $id, int $user)
    {
        $order = self::owned_order($id, $user);
        if (is_wp_error($order)) return $order;
        $summary = self::order_summary($order);
        $items = [];
        foreach ($order->get_items() as $item) {
            $config = (string)$item->get_meta('_pmp_module');
            $module = Modules::get($config);
            $meta = [];
            foreach ($item->get_formatted_meta_data('_', true) as $entry) $meta[] = ['label'=>self::text($entry->display_key),'value'=>self::text($entry->display_value)];
            $items[] = ['name'=>$item->get_name(),'quantity'=>(int)$item->get_quantity(),'total'=>self::text($order->get_formatted_line_subtotal($item)),
                'module'=>$module['title'] ?? $config,'applications'=>(int)$item->get_meta('_pmp_pack_size') * (int)$item->get_quantity(),'meta'=>$meta];
        }
        $totals = [];
        foreach ($order->get_order_item_totals() as $row) $totals[] = ['label'=>self::text($row['label']),'value'=>self::text($row['value'])];
        $notes = [];
        foreach ($order->get_customer_order_notes() as $note) $notes[] = ['date'=>wp_date('d/m/Y H:i', strtotime($note->comment_date_gmt . ' UTC')),'text'=>self::text($note->comment_content)];
        return array_merge($summary, ['items'=>$items,'totals'=>$totals,'payment_method'=>$order->get_payment_method_title(),
            'billing_address'=>self::text($order->get_formatted_billing_address()),'shipping_address'=>self::text($order->get_formatted_shipping_address()),
            'email'=>$order->get_billing_email(),'phone'=>$order->get_billing_phone(),'notes'=>$notes]);
    }

    public static function cancel_order(int $id, int $user)
    {
        $order = self::owned_order($id, $user);
        if (is_wp_error($order)) return $order;
        if (!self::order_summary($order)['can_cancel']) return Plugin::error('order', 'Este pedido não pode mais ser cancelado pela conta. Atualize a página para conferir seu status.', 409);
        $order->update_status('cancelled', 'Pedido cancelado pelo próprio cliente na conta Nortume.');
        do_action('woocommerce_cancelled_order', $id);
        return ['message'=>'Pedido cancelado.','order'=>self::order($id, $user)];
    }

    private static function text(string $html): string
    {
        return trim(html_entity_decode(wp_strip_all_tags(str_ireplace(['<br>','<br/>','<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    }
}
