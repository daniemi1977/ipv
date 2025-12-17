<?php
/**
 * Plugin Name: JEWE Affiliate Pro
 * Plugin URI: https://agatabuy.com/plugins/jewe-affiliate-pro
 * Description: Plugin affiliate avanzato con analytics, multi-level marketing, gamification e AI insights. Compatibile con WCFM.
 * Version: 1.0.0
 * Author: Agatabuy
 * Author URI: https://agatabuy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jewe-affiliate-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('JEWE_AFFILIATE_VERSION', '1.0.0');
define('JEWE_AFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JEWE_AFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JEWE_AFFILIATE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class JEWE_Affiliate_Pro {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        // Core classes
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-database.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-affiliate.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-commission.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-tracking.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-mlm.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-gamification.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-ai-insights.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-notifications.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-reports.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-integration.php';

        // Admin classes
        if (is_admin()) {
            require_once JEWE_AFFILIATE_PLUGIN_DIR . 'admin/class-admin.php';
            require_once JEWE_AFFILIATE_PLUGIN_DIR . 'admin/class-settings.php';
        }

        // Public classes
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'public/class-public.php';
        require_once JEWE_AFFILIATE_PLUGIN_DIR . 'public/class-shortcodes.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('plugins_loaded', [$this, 'init']);
        add_action('init', [$this, 'load_textdomain']);
    }

    public function init() {
        // Initialize components
        JEWE_Affiliate_Database::instance();
        JEWE_Affiliate_Tracking::instance();
        JEWE_Affiliate_Commission::instance();
        JEWE_Affiliate_MLM::instance();
        JEWE_Affiliate_Gamification::instance();
        JEWE_Affiliate_Analytics::instance();
        JEWE_Affiliate_AI_Insights::instance();
        JEWE_Affiliate_Notifications::instance();
        JEWE_Affiliate_REST_API::instance();
        JEWE_Affiliate_Reports::instance();
        JEWE_Affiliate_WCFM_Integration::instance();

        if (is_admin()) {
            JEWE_Affiliate_Admin::instance();
            JEWE_Affiliate_Settings::instance();
        }

        JEWE_Affiliate_Public::instance();
        JEWE_Affiliate_Shortcodes::instance();
    }

    public function load_textdomain() {
        load_plugin_textdomain('jewe-affiliate-pro', false, dirname(JEWE_AFFILIATE_PLUGIN_BASENAME) . '/languages');
    }

    public function activate() {
        JEWE_Affiliate_Database::create_tables();
        JEWE_Affiliate_Database::insert_default_data();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function jewe_affiliate_pro() {
    return JEWE_Affiliate_Pro::instance();
}

// Start the plugin
jewe_affiliate_pro();
