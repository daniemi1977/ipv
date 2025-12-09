<?php
/**
 * Plugin Name: IPV Pro Vendor System
 * Plugin URI: https://ipv-production-system.com
 * Description: Sistema completo per vendere IPV Pro Plugin via WooCommerce con API Gateway integrato
 * Version: 1.3.10
 * Author: IPV Team
 * Author URI: https://ipv-production-system.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Text Domain: ipv-pro-vendor
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'IPV_VENDOR_VERSION', '1.3.10' );
define( 'IPV_VENDOR_FILE', __FILE__ );
define( 'IPV_VENDOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_VENDOR_URL', plugin_dir_url( __FILE__ ) );

// Load Auto-Configurator (must be loaded before autoloader)
require_once IPV_VENDOR_DIR . 'includes/class-auto-configurator.php';

// Check WooCommerce
add_action( 'admin_init', 'ipv_vendor_check_woocommerce' );
function ipv_vendor_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p><strong>IPV Pro Vendor</strong> richiede WooCommerce attivo!</p></div>';
        });
        deactivate_plugins( plugin_basename( __FILE__ ) );
        return;
    }
}

// Autoloader
spl_autoload_register( function( $class ) {
    if ( strpos( $class, 'IPV_Vendor_' ) === 0 ) {
        $file = strtolower( str_replace( ['IPV_Vendor_', '_'], ['', '-'], $class ) );
        $path = IPV_VENDOR_DIR . 'includes/class-' . $file . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
});

// Load core
require_once IPV_VENDOR_DIR . 'includes/class-vendor-core.php';

// Init
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        IPV_Vendor_Core::instance();
    }
});

// Activation
register_activation_hook( __FILE__, function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'IPV Pro Vendor richiede WooCommerce. Installa e attiva WooCommerce prima di attivare questo plugin.' );
    }

    // v1.3.3 - Auto-configurazione sistema
    require_once IPV_VENDOR_DIR . 'includes/class-auto-configurator.php';
    $auto_config = IPV_Vendor_Auto_Configurator::instance();
    $auto_config->activate();

    // Core activation
    require_once IPV_VENDOR_DIR . 'includes/class-vendor-core.php';
    IPV_Vendor_Core::activate();
});

// Deactivation
register_deactivation_hook( __FILE__, function() {
    IPV_Vendor_Core::deactivate();
});

// Admin notices for auto-configuration
add_action( 'admin_notices', function() {
    IPV_Vendor_Auto_Configurator::instance()->show_activation_notice();
});

// Admin Status Page
require_once IPV_VENDOR_DIR . 'includes/class-admin-status-page.php';
add_action( 'plugins_loaded', function() {
    if ( is_admin() ) {
        IPV_Vendor_Admin_Status_Page::instance()->init();
    }
});

// Periodic health check (ogni 12 ore)
add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'ipv_vendor_health_check' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'ipv_vendor_health_check' );
    }
});

add_action( 'ipv_vendor_health_check', function() {
    IPV_Vendor_Auto_Configurator::instance()->health_check();
});
