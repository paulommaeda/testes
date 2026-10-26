<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Rest
{
    public static function register(): void
    {
        add_action('wp_ajax_pmp_refresh_nonce',static function () {
            if (!is_user_logged_in()) wp_send_json_error(['message'=>'Entre novamente na sua conta.'],401);
            nocache_headers();
            wp_send_json_success(['nonce'=>wp_create_nonce('wp_rest')]);
        });
        add_action('rest_api_init', static function () {
            $namespace = 'maeda-perfis/v1';
            register_rest_route($namespace, '/catalog', ['methods'=>'GET','callback'=>[self::class,'catalog'],'permission_callback'=>'__return_true']);
            $routes = [
                '/state'=>['GET',static fn($r) => self::state()],
                '/records'=>['GET',static fn($r) => self::records(get_current_user_id(), sanitize_key($r['kind'] ?? 'tests'), max(1,(int) ($r['page'] ?? 1)))],
                '/claim'=>['POST',static fn($r) => Credits::claim(get_current_user_id(),sanitize_key($r['module'] ?? ''))],
                '/invites'=>['POST',static fn($r) => Invitations::create(get_current_user_id(),sanitize_key($r['module'] ?? ''),(string) ($r['email'] ?? ''))],
                '/invites/accept'=>['POST',static fn($r) => Invitations::accept((string) ($r['token'] ?? ''),get_current_user_id(),$r['consent'] === true)],
                '/invites/(?P<id>\d+)/resend'=>['POST',static fn($r) => Invitations::resend((int) $r['id'],get_current_user_id())],
                '/invites/(?P<id>\d+)/cancel'=>['POST',static fn($r) => Invitations::cancel((int) $r['id'],get_current_user_id())],
                '/attempts/(?P<id>\d+)'=>['GET',static fn($r) => Assessments::application((int) $r['id'],get_current_user_id())],
                '/attempts/(?P<id>\d+)/start'=>['POST',static fn($r) => Assessments::start((int) $r['id'],get_current_user_id(),$r['acknowledged'] === true)],
                '/attempts/(?P<id>\d+)/release'=>['POST',static fn($r) => Credits::release_self((int) $r['id'],get_current_user_id())],
                '/attempts/(?P<id>\d+)/answers'=>['POST',static fn($r) => self::save($r,false)],
                '/attempts/(?P<id>\d+)/complete'=>['POST',static fn($r) => self::save($r,true)],
                '/reports/(?P<id>\d+)'=>['GET',static fn($r) => Assessments::report((int) $r['id'],get_current_user_id())],
            ];
            foreach ($routes as $path=>$route) {
                register_rest_route($namespace,$path,['methods'=>$route[0],'callback'=>$route[1],'permission_callback'=>[self::class,'permission']]);
            }
        });
        add_filter('rest_post_dispatch', static function ($response,$server,$request) {
            if (strpos($request->get_route(),'/maeda-perfis/v1/') === 0 && strpos($request->get_route(),'/catalog') === false) {
                $response = rest_ensure_response($response);
                $response->header('Cache-Control','no-store, private, max-age=0');
                $response->header('Vary','Cookie');
            }
            return $response;
        },10,3);
    }

    public static function permission($request)
    {
        if (!is_user_logged_in()) {
            return Plugin::error('login','Entre na sua conta para continuar.',401);
        }
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'),'wp_rest')) {
            return Plugin::error('nonce','Sua sessão expirou. Atualize a página para continuar.',403);
        }
        return true;
    }

    private static function save($request, bool $complete)
    {
        $answers = $request->get_param('answers');
        $revision = $request->get_param('revision');
        if (!is_array($answers) || !is_int($revision) || $revision < 0 || count($answers) > 120) {
            return Plugin::error('answers','Formato de respostas inválido.');
        }
        return Assessments::save((int) $request['id'],get_current_user_id(),$answers,$revision,$complete);
    }

    public static function catalog(): array
    {
        $modules = array_values(array_map([Modules::class,'public_data'],Modules::all()));
        $products = [];
        if (function_exists('wc_get_product')) {
            $ids = get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','meta_query'=>[['key'=>'_pmp_module','compare'=>'EXISTS']],'orderby'=>'menu_order title','order'=>'ASC']);
            foreach ($ids as $id) {
                $product = wc_get_product($id);
                $config = Commerce::config($product);
                if (!$config || !$product->is_purchasable() || !$product->is_in_stock()) {
                    continue;
                }
                $products[] = ['id'=>$id,'module'=>$config['module'],'size'=>$config['size'],'title'=>$product->get_name(),
                    'price'=>html_entity_decode(wp_strip_all_tags($product->get_price_html()),ENT_QUOTES,'UTF-8'),'url'=>get_permalink($id)];
            }
        }
        return ['modules'=>$modules,'products'=>$products];
    }

    public static function state(): array
    {
        global $wpdb;
        Invitations::expire();
        $user = wp_get_current_user();
        $attempts = Database::table('attempts');
        $credits = Database::table('credits');
        $stats = [
            'completed'=>(int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $attempts WHERE user_id = %d AND status = 'completed'",$user->ID)),
            'pending'=>(int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $attempts a INNER JOIN $credits c ON c.id = a.credit_id AND c.attempt_id = a.id WHERE a.user_id = %d AND a.status IN ('assigned','in_progress') AND c.status <> 'revoked'",$user->ID)),
            'funded_completed'=>(int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $attempts a INNER JOIN $credits c ON c.id = a.credit_id WHERE a.buyer_id = %d AND a.status = 'completed' AND c.status <> 'revoked'",$user->ID)),
        ];
        return array_merge(self::catalog(),[
            'user'=>['name'=>$user->display_name,'first_name'=>$user->first_name ?: $user->display_name,'email'=>$user->user_email],
            'balances'=>Credits::balances((int) $user->ID),'stats'=>$stats,
            'tests'=>self::records((int) $user->ID,'tests'),
            'invites'=>self::records((int) $user->ID,'invites'),
            'funded'=>self::records((int) $user->ID,'funded'),
        ]);
    }

    public static function records(int $user, string $kind, int $page = 1): array
    {
        global $wpdb;
        $limit = 20;
        $page = max(1,min(100000,$page));
        $offset = ($page-1)*$limit;
        $a = Database::table('attempts');
        $c = Database::table('credits');
        $i = Database::table('invites');
        if ($kind === 'invites') {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $i WHERE buyer_id = %d",$user));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT i.id,i.email,i.status,i.mail_status,i.expires_at,i.created_at,c.module_key,c.status AS credit_status,c.attempt_id FROM $i i INNER JOIN $c c ON c.id = i.credit_id WHERE i.buyer_id = %d ORDER BY i.id DESC LIMIT %d OFFSET %d",$user,$limit,$offset),ARRAY_A);
        } else {
            $column = $kind === 'funded' ? 'buyer_id' : 'user_id';
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $a WHERE $column = %d AND status <> 'cancelled'",$user));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT a.id,a.user_id,a.buyer_id,a.module_key,a.module_version,a.status,a.created_at,a.started_at,a.completed_at,c.status AS credit_status FROM $a a INNER JOIN $c c ON c.id = a.credit_id WHERE a.$column = %d AND a.status <> 'cancelled' ORDER BY a.id DESC LIMIT %d OFFSET %d",$user,$limit,$offset),ARRAY_A);
            foreach ($rows as &$row) {
                $row['participant_name'] = Assessments::user_name((int) $row['user_id']);
                $row['buyer_name'] = Assessments::user_name((int) $row['buyer_id']);
                $row['is_own'] = (int) $row['user_id'] === $user;
                $row['report_available'] = $row['status'] === 'completed' && ($row['is_own'] || $row['credit_status'] !== 'revoked');
                unset($row['user_id'],$row['buyer_id']);
            }
            unset($row);
        }
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        return ['items'=>$rows,'page'=>$page,'total'=>$total,'has_more'=>$page*$limit < $total];
    }
}
