<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Frontend
{
    public static function register(): void
    {
        add_shortcode('maeda_perfis',[self::class,'render']);
        add_shortcode('testes_perfil',[self::class,'render']);
        add_shortcode('nortume_perfis',[self::class,'render']);
        add_action('init',[self::class,'endpoints']);
        add_filter('woocommerce_account_menu_items',static function ($items) {
            $logout = $items['customer-logout'] ?? null;
            unset($items['customer-logout']);
            $items['meus-testes'] = 'Meus testes';
            $items['meus-pacotes'] = 'Pacotes e convites';
            $items['resultados-equipe'] = 'Resultados dos participantes';
            if ($logout) $items['customer-logout'] = $logout;
            return $items;
        });
        foreach (['meus-testes'=>'tests','meus-pacotes'=>'packages','resultados-equipe'=>'team'] as $endpoint=>$view) {
            add_action('woocommerce_account_' . $endpoint . '_endpoint',static function () use ($view) { echo self::render(['view'=>$view]); });
        }
        add_action('wp_enqueue_scripts',[self::class,'maybe_assets']);
        add_action('template_redirect',[self::class,'private_headers']);
        add_action('template_redirect',[self::class,'account_redirects'], 20);
        add_filter('template_include',static function ($template) {
            return self::is_app_page() ? PMP_PATH . 'templates/app-page.php' : $template;
        }, 1000);
        add_filter('woocommerce_login_redirect',[self::class,'auth_redirect'],10,2);
        add_filter('woocommerce_registration_redirect',[self::class,'auth_redirect'],10,1);
        add_filter('pre_option_woocommerce_enable_myaccount_registration',static function ($value) {
            return (!empty($_POST['pmp_context']) || (did_action('wp') && self::is_app_page())) ? 'yes' : $value;
        });
    }

    public static function is_app_page(): bool
    {
        $page = (int)get_option('pmp_page_id');
        return $page > 0 && isset($GLOBALS['wp_query']) && is_page($page);
    }

    public static function account_redirects(): void
    {
        $previous = (int)get_option('pmp_previous_account_page_id');
        if (!self::is_app_page() && !($previous > 0 && is_page($previous)) && !(function_exists('is_account_page') && is_account_page())) return;
        $endpoint = function_exists('WC') && WC()->query ? WC()->query->get_current_endpoint() : '';
        if (!$endpoint) foreach (['meus-testes','meus-pacotes','resultados-equipe'] as $key) if (get_query_var($key, null) !== null) { $endpoint = $key; break; }
        if ($endpoint === 'lost-password') {
            $key = isset($_GET['key']) && is_string($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $record = !empty($_GET['id']) && is_scalar($_GET['id']) ? get_userdata(absint($_GET['id'])) : null;
            $login = isset($_GET['login']) && is_string($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : ($record ? $record->user_login : '');
            $url = wp_lostpassword_url(Plugin::url(['pmp_view'=>'login']));
            if ($key && $login && !is_wp_error(check_password_reset_key($key, $login))) $url = add_query_arg(['action'=>'rp','key'=>$key,'login'=>$login], wp_login_url(Plugin::url()));
            wp_safe_redirect($url); exit;
        }
        if ($endpoint === 'customer-logout') {
            $nonce = isset($_GET['_wpnonce']) && is_string($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
            wp_safe_redirect(wp_verify_nonce($nonce, 'customer-logout') ? html_entity_decode(wp_logout_url(Plugin::url(['pmp_view'=>'login'])), ENT_QUOTES, 'UTF-8') : Plugin::url()); exit;
        }
        $map = ['orders'=>'orders','view-order'=>'orders','edit-account'=>'account','edit-address'=>'account','meus-testes'=>'tests','meus-pacotes'=>'packages','resultados-equipe'=>'team'];
        if ($endpoint || !self::is_app_page()) {
            $args = ['pmp_view'=>$map[$endpoint] ?? (is_user_logged_in() ? 'dashboard' : 'login')];
            if ($endpoint === 'view-order') $args['pmp_order'] = absint(get_query_var('view-order'));
            if ($endpoint === 'orders') $args['pmp_orders_page'] = max(1,absint(get_query_var('orders')));
            // Preserva convites e links diretos que tenham usado o endereço anterior da conta.
            foreach (['pmp_invite','pmp_report','pmp_attempt'] as $key) if (isset($_GET[$key]) && is_string($_GET[$key])) $args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            wp_safe_redirect(Plugin::url($args)); exit;
        }
    }

    public static function endpoints(): void
    {
        foreach (['meus-testes','meus-pacotes','resultados-equipe'] as $endpoint) {
            add_rewrite_endpoint($endpoint, EP_ROOT | EP_PAGES);
        }
    }

    public static function maybe_assets(): void
    {
        global $post;
        if ((is_object($post) && (has_shortcode($post->post_content,'maeda_perfis') || has_shortcode($post->post_content,'testes_perfil') || has_shortcode($post->post_content,'nortume_perfis'))) ||
            (function_exists('is_account_page') && is_account_page()) || is_page((int) get_option('pmp_page_id'))) {
            self::assets();
        }
    }

    private static function assets(): void
    {
        $deps = [];
        if (self::is_app_page()) { Site::enqueue_assets(); $deps[] = 'nortume-site'; }
        wp_enqueue_style('maeda-perfis',PMP_URL . 'assets/app.css',$deps,PMP_VERSION);
        wp_enqueue_script('maeda-perfis',PMP_URL . 'assets/app.js',[],PMP_VERSION,true);
        wp_script_add_data('maeda-perfis','strategy','defer');
    }

    public static function private_headers(): void
    {
        if (self::is_app_page() || ((int)get_option('pmp_previous_account_page_id') > 0 && is_page((int)get_option('pmp_previous_account_page_id'))) || isset($_GET['pmp_invite']) || (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('meus-testes') || is_wc_endpoint_url('meus-pacotes') || is_wc_endpoint_url('resultados-equipe')))) {
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
            nocache_headers();
            header('Referrer-Policy: no-referrer');
            header('X-Robots-Tag: noindex, nofollow');
        }
    }

    public static function auth_fields(): void
    {
        $token = isset($_GET['pmp_invite']) ? sanitize_text_field(wp_unslash($_GET['pmp_invite'])) : '';
        echo '<input type="hidden" name="pmp_context" value="1">';
        if (preg_match('/^[a-f0-9]{64}$/',$token)) {
            echo '<input type="hidden" name="pmp_invite" value="' . esc_attr($token) . '">';
        }
    }

    public static function auth_redirect($redirect, $user = null): string
    {
        if (empty($_POST['pmp_context'])) return $redirect;
        $token = isset($_POST['pmp_invite']) ? sanitize_text_field(wp_unslash($_POST['pmp_invite'])) : '';
        return Plugin::url(preg_match('/^[a-f0-9]{64}$/',$token) ? ['pmp_invite'=>$token] : []);
    }

    public static function render($attributes = []): string
    {
        self::assets();
        $attributes = shortcode_atts(['view'=>'dashboard'],is_array($attributes) ? $attributes : []);
        $token = isset($_GET['pmp_invite']) ? sanitize_text_field(wp_unslash($_GET['pmp_invite'])) : '';
        $view = isset($_GET['pmp_view']) ? sanitize_key($_GET['pmp_view']) : $attributes['view'];
        $allowed = ['dashboard','catalog','tests','packages','team','login','orders','account'];
        if (!in_array($view,$allowed,true)) $view = 'dashboard';
        $invitation = $token ? Invitations::info($token) : null;
        if (is_wp_error($invitation)) $invitation = ['error'=>$invitation->get_error_message()];
        $config = [
            'api'=>esc_url_raw(rest_url('maeda-perfis/v1/')),
            'ajax_url'=>esc_url_raw(admin_url('admin-ajax.php')),
            'nonce'=>wp_create_nonce('wp_rest'),'logged_in'=>is_user_logged_in(),
            'view'=>$view,'base_url'=>Plugin::url(),'login_url'=>Plugin::url(['pmp_view'=>'login']),
            'account_url'=>Plugin::url(),'shop_url'=>Site::url('shop'),
            'home_url'=>Site::url(),'external_header'=>self::is_app_page(),
            'logout_url'=>html_entity_decode(wp_logout_url(Plugin::url(preg_match('/^[a-f0-9]{64}$/',$token) ? ['pmp_invite'=>$token] : ['pmp_view'=>'login'])), ENT_QUOTES, 'UTF-8'),
            'order_id'=>absint($_GET['pmp_order'] ?? 0),'orders_page'=>max(1,absint($_GET['pmp_orders_page'] ?? 1)),
            'attempt_id'=>absint($_GET['pmp_attempt'] ?? 0),'report_id'=>absint($_GET['pmp_report'] ?? 0),
            'invite_token'=>$token,'invitation'=>$invitation,'site_name'=>get_bloginfo('name'),
            'brand'=>sanitize_text_field(get_option('pmp_brand','Nortume')),
            'catalog'=>Rest::catalog(),
            'notices'=>self::notices(),
        ];
        $id = wp_unique_id('pmp-');
        ob_start();
        echo '<div class="pmp-app" id="' . esc_attr($id) . '" data-pmp-app><script type="application/json" class="pmp-config">' . wp_json_encode($config,JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        echo '<div class="pmp-loading" role="status">Preparando seu espaço de autoconhecimento…</div>';
        if (!is_user_logged_in()) { echo '<template class="pmp-auth-template">'; self::authentication(); echo '</template>'; }
        echo '<noscript>Ative o JavaScript do navegador para acessar a conta, responder aos testes e visualizar os gráficos.</noscript></div>';
        return ob_get_clean();
    }

    private static function notices(): array
    {
        if (!function_exists('wc_get_notices')) return [];
        $messages = [];
        foreach (wc_get_notices() as $type=>$notices) foreach ($notices as $notice) $messages[] = ['type'=>$type,'text'=>html_entity_decode(wp_strip_all_tags($notice['notice']), ENT_QUOTES, 'UTF-8')];
        wc_clear_notices();
        return $messages;
    }

    private static function authentication(): void
    {
        if (!class_exists('WooCommerce')) { echo '<p>Ative o WooCommerce para habilitar o cadastro e a conta.</p>'; return; }
        include PMP_PATH . 'templates/account-auth.php';
    }
}
