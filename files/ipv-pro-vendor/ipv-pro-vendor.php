<?php
/**
 * Plugin Name: IPV Pro Vendor
 * Plugin URI: https://example.com/ipv-pro
 * Description: Sistema di gestione licenze e crediti per IPV Pro
 * Version: 1.0.0
 * Author: IPV Team
 * Author URI: https://example.com
 * Text Domain: ipv-pro-vendor
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package IPV_Pro_Vendor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'IPV_VENDOR_VERSION', '1.0.0' );
define( 'IPV_VENDOR_FILE', __FILE__ );
define( 'IPV_VENDOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'IPV_VENDOR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main IPV Pro Vendor Class
 */
final class IPV_Pro_Vendor {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', [ $this, 'load_plugin' ], 0 );
        add_action( 'init', [ $this, 'load_textdomain' ] );
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }

    /**
     * Load plugin classes
     */
    public function load_plugin() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'woocommerce_notice' ] );
            return;
        }

        $this->includes();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once IPV_VENDOR_PATH . 'includes/class-plans-manager.php';
        require_once IPV_VENDOR_PATH . 'includes/class-credits-manager.php';
        require_once IPV_VENDOR_PATH . 'includes/class-customer-portal.php';
        require_once IPV_VENDOR_PATH . 'includes/class-upgrade-manager.php';
        require_once IPV_VENDOR_PATH . 'includes/class-email-notifications.php';
        require_once IPV_VENDOR_PATH . 'includes/class-woocommerce-integration.php';
        require_once IPV_VENDOR_PATH . 'includes/class-landing-page.php';

        // API endpoints
        if ( file_exists( IPV_VENDOR_PATH . 'api/endpoints/class-wallet-endpoints.php' ) ) {
            require_once IPV_VENDOR_PATH . 'api/endpoints/class-wallet-endpoints.php';
        }
        if ( file_exists( IPV_VENDOR_PATH . 'api/endpoints/class-upgrade-endpoints.php' ) ) {
            require_once IPV_VENDOR_PATH . 'api/endpoints/class-upgrade-endpoints.php';
        }
    }

    /**
     * Load textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ipv-pro-vendor',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * WooCommerce not active notice
     */
    public function woocommerce_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'IPV Pro Vendor', 'ipv-pro-vendor' ); ?></strong>
                <?php esc_html_e( 'richiede WooCommerce per funzionare.', 'ipv-pro-vendor' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook( 'ipv_daily_credits_check' );
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Licenses table
        $table_licenses = $wpdb->prefix . 'ipv_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            variant_slug varchar(100) NOT NULL DEFAULT 'trial',
            status varchar(50) NOT NULL DEFAULT 'active',
            activation_limit int(11) NOT NULL DEFAULT 1,
            activation_count int(11) NOT NULL DEFAULT 0,
            credits_total int(11) NOT NULL DEFAULT 0,
            credits_remaining int(11) NOT NULL DEFAULT 0,
            credits_monthly int(11) NOT NULL DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        // Credit ledger table
        $table_ledger = $wpdb->prefix . 'ipv_credit_ledger';
        $sql_ledger = "CREATE TABLE IF NOT EXISTS $table_ledger (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            amount int(11) NOT NULL,
            balance_after int(11) NOT NULL,
            ref_type varchar(100) DEFAULT NULL,
            ref_id varchar(255) DEFAULT NULL,
            note text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_key (license_key),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Activations table
        $table_activations = $wpdb->prefix . 'ipv_activations';
        $sql_activations = "CREATE TABLE IF NOT EXISTS $table_activations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            site_url varchar(255) NOT NULL,
            site_name varchar(255) DEFAULT NULL,
            instance_id varchar(100) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'active',
            activated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_check datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY license_key (license_key),
            KEY site_url (site_url),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_licenses );
        dbDelta( $sql_ledger );
        dbDelta( $sql_activations );
    }
}

/**
 * Get IPV Pro Vendor instance
 */
function IPV_Pro_Vendor() {
    return IPV_Pro_Vendor::instance();
}

// Initialize plugin
IPV_Pro_Vendor();
