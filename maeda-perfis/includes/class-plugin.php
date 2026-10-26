<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function boot(): void
    {
        if (get_option('pmp_db_version') !== PMP_VERSION) {
            Database::install();
        }
        Updater::register();
        Admin::register();
        Frontend::register();
        Rest::register();
        Privacy::register();
        Site::register();
        if (get_option('pmp_brand') === 'Maeda Perfis') update_option('pmp_brand', 'Nortume', false);
        add_action('pmp_expire_invitations', [Invitations::class, 'expire']);
        if (class_exists('WooCommerce')) {
            Commerce::register();
        } else {
            add_action('admin_notices', static function () {
                echo '<div class="notice notice-warning"><p>Nortume: instale e ative o WooCommerce para vender os testes e usar a área de conta.</p></div>';
            });
        }
        if (!wp_next_scheduled('pmp_expire_invitations')) {
            wp_schedule_event(time() + 300, 'hourly', 'pmp_expire_invitations');
        }
    }

    public static function activate(): void
    {
        Database::install();
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_pmp');
        }
        if (!get_option('pmp_page_id') || !get_post((int) get_option('pmp_page_id'))) {
            $id = wp_insert_post([
                'post_type' => 'page', 'post_status' => 'publish',
                'post_title' => 'Meus perfis', 'post_name' => 'meus-perfis',
                'post_content' => '[maeda_perfis]',
            ]);
            if ($id && !is_wp_error($id)) {
                update_option('pmp_page_id', $id, false);
            }
        }
        Frontend::endpoints();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        if (get_option('pmp_site_enabled')) Site::disable();
        wp_clear_scheduled_hook('pmp_expire_invitations');
        flush_rewrite_rules();
    }

    public static function url(array $args = []): string
    {
        $page = (int) get_option('pmp_page_id');
        $url = $page && get_post_status($page) === 'publish' ? get_permalink($page) : home_url('/');
        return $args ? add_query_arg($args, $url) : $url;
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function error(string $code, string $message, int $status = 400): \WP_Error
    {
        return new \WP_Error('pmp_' . $code, $message, ['status' => $status]);
    }
}
