<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Frontend
{
    private static bool $rendering_auth = false;

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
        add_filter('template_include',static function ($template) {
            return is_page((int) get_option('pmp_page_id')) ? PMP_PATH . 'templates/app-page.php' : $template;
        });
        add_action('woocommerce_login_form',[self::class,'auth_fields']);
        add_action('woocommerce_register_form',[self::class,'auth_fields']);
        add_filter('woocommerce_login_redirect',[self::class,'auth_redirect'],10,2);
        add_filter('woocommerce_registration_redirect',[self::class,'auth_redirect'],10,1);
        add_filter('pre_option_woocommerce_enable_myaccount_registration',static function ($value) {
            return self::$rendering_auth ? 'yes' : $value;
        });
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
        wp_enqueue_style('maeda-perfis',PMP_URL . 'assets/app.css',[],PMP_VERSION);
        wp_enqueue_script('maeda-perfis',PMP_URL . 'assets/app.js',[],PMP_VERSION,true);
        wp_script_add_data('maeda-perfis','strategy','defer');
    }

    public static function private_headers(): void
    {
        if (is_page((int) get_option('pmp_page_id')) || isset($_GET['pmp_invite']) || (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('meus-testes') || is_wc_endpoint_url('meus-pacotes') || is_wc_endpoint_url('resultados-equipe')))) {
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
            nocache_headers();
            header('Referrer-Policy: no-referrer');
            header('X-Robots-Tag: noindex, nofollow');
        }
    }

    public static function auth_fields(): void
    {
        if (!self::$rendering_auth) return;
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
        $allowed = ['dashboard','catalog','tests','packages','team','login'];
        if (!in_array($view,$allowed,true)) $view = 'dashboard';
        $invitation = $token ? Invitations::info($token) : null;
        if (is_wp_error($invitation)) $invitation = ['error'=>$invitation->get_error_message()];
        $config = [
            'api'=>esc_url_raw(rest_url('maeda-perfis/v1/')),
            'ajax_url'=>esc_url_raw(admin_url('admin-ajax.php')),
            'nonce'=>wp_create_nonce('wp_rest'),'logged_in'=>is_user_logged_in(),
            'view'=>$view,'base_url'=>Plugin::url(),'login_url'=>Plugin::url(['pmp_view'=>'login']),
            'account_url'=>function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(Plugin::url()),
            'logout_url'=>wp_logout_url(Plugin::url(preg_match('/^[a-f0-9]{64}$/',$token) ? ['pmp_invite'=>$token] : [])),
            'attempt_id'=>absint($_GET['pmp_attempt'] ?? 0),'report_id'=>absint($_GET['pmp_report'] ?? 0),
            'invite_token'=>$token,'invitation'=>$invitation,'site_name'=>get_bloginfo('name'),
            'brand'=>sanitize_text_field(get_option('pmp_brand','Nortume')),
            'catalog'=>Rest::catalog(),
        ];
        $id = wp_unique_id('pmp-');
        ob_start();
        echo '<div class="pmp-app" id="' . esc_attr($id) . '" data-pmp-app><script type="application/json" class="pmp-config">' . wp_json_encode($config,JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        echo '<div class="pmp-loading" role="status">Preparando seu espaço de autoconhecimento…</div>';
        echo '<noscript>Ative o JavaScript do navegador para responder aos testes e visualizar os gráficos.</noscript></div>';
        if (!is_user_logged_in() && ($token || $view === 'login')) {
            echo '<div class="pmp-auth"><h2>Entre ou crie sua conta</h2><p>Use o mesmo e-mail que recebeu o convite, quando houver.</p>';
            if (class_exists('WooCommerce')) {
                self::$rendering_auth = true;
                echo do_shortcode('[woocommerce_my_account]');
                self::$rendering_auth = false;
            } else {
                echo '<p>O cadastro estará disponível quando o WooCommerce estiver ativo.</p>';
            }
            echo '</div>';
        }
        return ob_get_clean();
    }
}
