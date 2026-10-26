<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Commerce
{
    public static function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [self::class,'product_fields']);
        add_action('woocommerce_admin_process_product_object', [self::class,'save_product']);
        add_action('woocommerce_product_after_variable_attributes', [self::class,'variation_fields'], 10, 3);
        add_action('woocommerce_save_product_variation', [self::class,'save_variation'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [self::class,'snapshot_item'], 10, 4);
        add_filter('woocommerce_checkout_registration_required', [self::class,'registration_required']);
        add_filter('woocommerce_checkout_registration_enabled', [self::class,'registration_enabled']);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [self::class,'require_store_account'], 10, 2);
        add_filter('woocommerce_get_item_data', [self::class,'cart_data'], 10, 2);
        add_action('woocommerce_payment_complete', [self::class,'sync_order']);
        add_action('woocommerce_order_status_changed', static fn($id) => self::sync_order((int) $id), 10, 1);
        add_action('woocommerce_order_refunded', static fn($id) => self::sync_order((int) $id), 10, 1);
        add_action('woocommerce_order_details_after_order_table', [self::class,'order_link']);
    }

    public static function config($product): ?array
    {
        if (!$product) {
            return null;
        }
        $module = (string) $product->get_meta('_pmp_module', true);
        $size = $product->get_meta('_pmp_pack_size', true);
        if (!$module && $product->is_type('variation')) {
            $parent = wc_get_product($product->get_parent_id());
            $module = $parent ? (string) $parent->get_meta('_pmp_module', true) : '';
            if ($size === '' && $parent) {
                $size = $parent->get_meta('_pmp_pack_size', true);
            }
        }
        if (!Modules::get($module)) {
            return null;
        }
        return ['module'=>$module,'size'=>max(1,min(500,(int) ($size ?: 1)))];
    }

    public static function product_fields(): void
    {
        echo '<div class="options_group">';
        $options = [''=>'Não é um teste de perfil'];
        foreach (Modules::all() as $key=>$module) {
            $options[$key] = $module['title'];
        }
        woocommerce_wp_select(['id'=>'_pmp_module','label'=>'Nortume · Módulo','options'=>$options,'description'=>'Cada aplicação deste produto libera somente o módulo selecionado.','desc_tip'=>true]);
        woocommerce_wp_text_input(['id'=>'_pmp_pack_size','label'=>'Aplicações por unidade','type'=>'number','custom_attributes'=>['min'=>1,'max'=>500,'step'=>1],'description'=>'Exemplo: 10 aplicações por pacote. Duas unidades compradas liberam 20 aplicações.','desc_tip'=>true]);
        echo '</div>';
    }

    public static function save_product($product): void
    {
        if (!isset($_POST['_pmp_module'])) {
            return;
        }
        $module = sanitize_key(wp_unslash($_POST['_pmp_module']));
        if ($module && !Modules::get($module)) {
            return;
        }
        $product->update_meta_data('_pmp_module', $module);
        $product->update_meta_data('_pmp_pack_size', max(1,min(500,absint($_POST['_pmp_pack_size'] ?? 1))));
        if ($module) {
            $product->set_virtual(true);
        }
    }

    public static function variation_fields($loop, $variation_data, $variation): void
    {
        $product = wc_get_product($variation->ID);
        $options = [''=>'Herdar módulo do produto'];
        foreach (Modules::all() as $key=>$module) {
            $options[$key] = $module['title'];
        }
        woocommerce_wp_select(['id'=>'pmp_variation_module_'.$loop,'name'=>'pmp_variation_module['.$loop.']','value'=>$product->get_meta('_pmp_module'),'label'=>'Módulo de perfil','options'=>$options,'wrapper_class'=>'form-row form-row-first']);
        woocommerce_wp_text_input(['id'=>'pmp_variation_size_'.$loop,'name'=>'pmp_variation_size['.$loop.']','value'=>$product->get_meta('_pmp_pack_size'),'label'=>'Aplicações por unidade','type'=>'number','custom_attributes'=>['min'=>1,'max'=>500],'wrapper_class'=>'form-row form-row-last']);
    }

    public static function save_variation($id, $loop): void
    {
        if (!isset($_POST['pmp_variation_module'][$loop])) {
            return;
        }
        $product = wc_get_product($id);
        $module = sanitize_key(wp_unslash($_POST['pmp_variation_module'][$loop]));
        $product->update_meta_data('_pmp_module', Modules::get($module) ? $module : '');
        $size = $_POST['pmp_variation_size'][$loop] ?? '';
        $product->update_meta_data('_pmp_pack_size', $size === '' ? '' : max(1,min(500,absint($size))));
        if (self::config($product)) {
            $product->set_virtual(true);
        }
        $product->save();
    }

    public static function snapshot_item($item, $cart_item_key, $values, $order): void
    {
        $config = self::config($values['data'] ?? null);
        if ($config) {
            $item->add_meta_data('_pmp_module', $config['module'], true);
            $item->add_meta_data('_pmp_pack_size', $config['size'], true);
        }
    }

    public static function has_tests(): bool
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        foreach (WC()->cart->get_cart() as $item) {
            if (self::config($item['data'] ?? null)) {
                return true;
            }
        }
        return false;
    }

    public static function registration_required($required): bool
    {
        return self::has_tests() ? true : (bool) $required;
    }

    public static function registration_enabled($enabled): bool
    {
        return self::has_tests() ? true : (bool) $enabled;
    }

    public static function require_store_account($order, $request): void
    {
        $has_tests = false;
        foreach ($order->get_items() as $item) {
            if (self::config($item->get_product()) || $item->get_meta('_pmp_module')) {
                $has_tests = true;
                break;
            }
        }
        if ($has_tests && !(int) $order->get_customer_id()) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('pmp_account_required', 'Crie sua conta ou entre antes de concluir a compra dos testes.', 400);
        }
    }

    public static function cart_data(array $data, array $item): array
    {
        $config = self::config($item['data'] ?? null);
        if ($config) {
            $module = Modules::get($config['module']);
            $data[] = ['key'=>'Módulo','value'=>$module['title']];
            $data[] = ['key'=>'Aplicações por unidade','value'=>(string) $config['size']];
        }
        return $data;
    }

    public static function sync_order(int $id): void
    {
        $order = wc_get_order($id);
        if (!$order) {
            return;
        }
        $buyer = (int) $order->get_customer_id();
        $has_tests = false;
        foreach ($order->get_items() as $item_id=>$item) {
            $key = (string) $item->get_meta('_pmp_module');
            $size = (int) $item->get_meta('_pmp_pack_size');
            if (!$key) {
                $config = self::config($item->get_product());
                if (!$config) {
                    continue;
                }
                $key = $config['module'];
                $size = $config['size'];
                $item->update_meta_data('_pmp_module', $key);
                $item->update_meta_data('_pmp_pack_size', $size);
                $item->save();
            }
            if (!Modules::get($key)) {
                continue;
            }
            $has_tests = true;
            if (!$buyer) {
                if (!$order->get_meta('_pmp_missing_account')) {
                    $order->add_order_note('Nortume: pedido sem conta de comprador. Vincule um cliente e atualize o status para liberar as aplicações.');
                    $order->update_meta_data('_pmp_missing_account', 'yes');
                    $order->save();
                }
                continue;
            }
            $quantity = (int) $item->get_quantity();
            $total = max(0,$quantity * max(1,$size));
            $refunded = abs((int) $order->get_qty_refunded_for_item($item_id));
            $allowed = $order->is_paid() ? max(0,($quantity-$refunded) * max(1,$size)) : 0;
            $result = Database::transaction(static function () use ($buyer,$key,$total,$allowed,$id,$item_id,$order) {
                global $wpdb;
                if ($order->is_paid() && $total > 0) {
                    $granted = Credits::grant($buyer,$key,$total,$id,(int) $item_id);
                    if (is_wp_error($granted)) {
                        return $granted;
                    }
                }
                $table = Database::table('credits');
                $credits = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE order_item_id = %d AND status <> 'revoked' ORDER BY CASE status WHEN 'available' THEN 1 WHEN 'reserved' THEN 2 WHEN 'assigned' THEN 3 WHEN 'in_progress' THEN 4 ELSE 5 END,id DESC FOR UPDATE", $item_id), ARRAY_A);
                $surplus = max(0,count($credits)-$allowed);
                foreach (array_slice($credits,0,$surplus) as $credit) {
                    if (!Credits::revoke((int) $credit['id'], $order->is_paid() ? 'refund' : 'order_status')) {
                        return Plugin::error('refund','Não foi possível reconciliar os créditos.',500);
                    }
                }
                return true;
            });
            if (is_wp_error($result)) {
                $order->add_order_note('Nortume: ' . $result->get_error_message());
            }
            if ($order->get_total_refunded_for_item($item_id) > 0 && !$refunded && !$item->get_meta('_pmp_refund_review')) {
                $order->add_order_note('Nortume: reembolso de valor sem quantidade no item ' . $item_id . '. Revise os créditos no painel do plugin.');
                $item->update_meta_data('_pmp_refund_review','yes');
                $item->save();
            }
        }
        if ($has_tests && $buyer && $order->is_paid() && !$order->get_meta('_pmp_release_email')) {
            $user = get_user_by('id',$buyer);
            if ($user && Mail::send($user->user_email,'Seus testes de perfil foram liberados','Sua próxima descoberta começa aqui',
                'O pagamento do pedido #' . $order->get_order_number() . ' foi confirmado. Entre na sua conta para fazer os testes ou convidar participantes com os créditos de cada módulo.',Plugin::url(['pmp_view'=>'packages']),'Acessar meus pacotes')) {
                $order->update_meta_data('_pmp_release_email','yes');
                $order->save();
            }
        }
    }

    public static function order_link($order): void
    {
        if ((int) $order->get_customer_id() !== get_current_user_id()) {
            return;
        }
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_pmp_module')) {
                echo '<p><a class="button" href="' . esc_url(Plugin::url(['pmp_view'=>'packages'])) . '">Acessar meus testes e pacotes</a></p>';
                break;
            }
        }
    }
}
