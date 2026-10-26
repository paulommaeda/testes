<?php
// Exclusivo do ambiente local de testes. Não é incluído no ZIP do plugin.
require '/wordpress/wp-load.php';
if (!defined('PMP_TESTING') || PMP_TESTING !== true) { http_response_code(404); exit; }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
header('Content-Type: application/json');
if (!is_plugin_active('maeda-perfis/maeda-perfis.php')) {
    $error = activate_plugin('maeda-perfis/maeda-perfis.php');
    if (is_wp_error($error)) { echo wp_json_encode(['error'=>$error->get_error_message()]); exit; }
    \Maeda\Perfis\Plugin::boot();
}
global $wpdb;
echo wp_json_encode(['wp'=>get_bloginfo('version'),'woo'=>defined('WC_VERSION') ? WC_VERSION : null,'modules'=>array_keys(\Maeda\Perfis\Modules::all()),'tables'=>$wpdb->get_col("SHOW TABLES LIKE '%pmp_%'"),'db_error'=>$wpdb->last_error,'page_url'=>\Maeda\Perfis\Plugin::url()]);
