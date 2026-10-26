<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

if (!get_option('pmp_delete_data')) return;

global $wpdb;
foreach (['credits','invites','attempts','events'] as $suffix) {
    $table = $wpdb->prefix . 'pmp_' . $suffix;
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
}
foreach (['pmp_db_version','pmp_modules','pmp_brand','pmp_invite_days','pmp_delete_data','pmp_page_id'] as $option) delete_option($option);
delete_transient('pmp_github_release');
wp_clear_scheduled_hook('pmp_expire_invitations');
$role = get_role('administrator');
if ($role) $role->remove_cap('manage_pmp');
