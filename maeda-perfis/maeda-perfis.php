<?php
/**
 * Plugin Name: Nortume
 * Description: Testes de perfil, pacotes por módulo, convites e dashboards integrados ao WooCommerce.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author: Paulo Maeda
 * Author URI: https://paulomaeda.com
 * Plugin URI: https://github.com/paulommaeda/testes
 * Update URI: https://github.com/paulommaeda/testes
 * Text Domain: maeda-perfis
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('PMP_VERSION', '0.2.0');
define('PMP_FILE', __FILE__);
define('PMP_PATH', plugin_dir_path(__FILE__));
define('PMP_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function ($class) {
    $prefix = 'Maeda\\Perfis\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $name = substr($class, strlen($prefix));
    $file = PMP_PATH . 'includes/class-' . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name)) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, ['Maeda\\Perfis\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Maeda\\Perfis\\Plugin', 'deactivate']);

add_action('before_woocommerce_init', static function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PMP_FILE, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', PMP_FILE, true);
    }
});
add_action('plugins_loaded', ['Maeda\\Perfis\\Plugin', 'boot']);
