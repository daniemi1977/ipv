<?php
/**
 * Auto-Installer for IPV Pro Vendor Server
 *
 * Handles automatic installation and configuration on plugin activation
 *
 * @package IPV_Pro_Vendor
 * @version 1.4.0-optimized
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Pro_Vendor_Auto_Installer {

    /**
     * Run the installation wizard
     */
    public static function install() {
        // Create database tables
        self::create_tables();

        // Set default options
        self::set_default_options();

        // Schedule CRON jobs
        self::schedule_cron();

        // Set installation flag
        update_option('ipv_vendor_installed', true);
        update_option('ipv_vendor_version', '1.4.0-optimized');
        update_option('ipv_vendor_install_date', current_time('mysql'));

        // Redirect to setup wizard
        set_transient('ipv_vendor_show_wizard', true, 60);
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Licenses table
        $sql_licenses = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_licenses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(32) NOT NULL,
            email varchar(255) NOT NULL,
            product_id bigint(20) NULL,
            plan varchar(50) DEFAULT 'trial',
            credits_total int DEFAULT 10,
            credits_remaining int DEFAULT 10,
            credits_reset_date datetime NULL,
            status varchar(20) DEFAULT 'active',
            valid_until datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        // License activations table
        $sql_activations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_license_activations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            site_url varchar(255) NOT NULL,
            activated_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY site_url (site_url)
        ) $charset_collate;";

        // API logs table
        $sql_api_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_api_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NULL,
            endpoint varchar(100) NOT NULL,
            resource_id varchar(100) NULL,
            status_code int NOT NULL,
            response_size int DEFAULT 0,
            attempts int DEFAULT 1,
            cached tinyint(1) DEFAULT 0,
            ip_address varchar(45) NULL,
            user_agent varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY endpoint (endpoint),
            KEY created_at (created_at),
            KEY cached (cached),
            KEY status_code (status_code)
        ) $charset_collate COMMENT='Audit log di tutte le chiamate API';";

        // Security log table
        $sql_security_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_security_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data text NULL,
            ip_address varchar(45) NULL,
            user_agent varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) $charset_collate COMMENT='Log eventi di sicurezza';";

        // Performance stats table
        $sql_performance = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_performance_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            endpoint varchar(100) NOT NULL,
            total_calls int DEFAULT 0,
            cache_hits int DEFAULT 0,
            avg_response_size int DEFAULT 0,
            avg_attempts decimal(4,2) DEFAULT 1.00,
            errors int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_daily_stat (date, endpoint),
            KEY date (date),
            KEY endpoint (endpoint)
        ) $charset_collate COMMENT='Statistiche giornaliere aggregate';";

        // Execute table creation
        dbDelta($sql_licenses);
        dbDelta($sql_activations);
        dbDelta($sql_api_logs);
        dbDelta($sql_security_log);
        dbDelta($sql_performance);

        // Log installation
        error_log('[IPV Vendor] Database tables created successfully');
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'ipv_vendor_youtube_api_key' => '',
            'ipv_vendor_openai_api_key' => '',
            'ipv_vendor_supadata_key' => '',
            'ipv_vendor_supadata_secret' => '',
            'ipv_vendor_rate_limit_enabled' => true,
            'ipv_vendor_rate_limit_max' => 100,
            'ipv_vendor_cache_enabled' => true,
            'ipv_vendor_cache_ttl' => 7 * DAY_IN_SECONDS,
            'ipv_vendor_audit_enabled' => true,
            'ipv_vendor_audit_retention_days' => 90,
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Schedule CRON jobs
     */
    private static function schedule_cron() {
        // Monthly credit reset
        if (!wp_next_scheduled('ipv_vendor_monthly_reset')) {
            wp_schedule_event(strtotime('first day of next month 00:00:00'), 'monthly', 'ipv_vendor_monthly_reset');
        }

        // Daily cleanup old logs
        if (!wp_next_scheduled('ipv_vendor_cleanup_logs')) {
            wp_schedule_event(strtotime('tomorrow 03:00:00'), 'daily', 'ipv_vendor_cleanup_logs');
        }

        // Daily performance stats aggregation
        if (!wp_next_scheduled('ipv_vendor_aggregate_stats')) {
            wp_schedule_event(strtotime('tomorrow 04:00:00'), 'daily', 'ipv_vendor_aggregate_stats');
        }
    }

    /**
     * Check if setup is complete
     */
    public static function is_setup_complete() {
        $youtube_key = get_option('ipv_vendor_youtube_api_key');
        $supadata_key = get_option('ipv_vendor_supadata_key');

        return !empty($youtube_key) && !empty($supadata_key);
    }

    /**
     * Get setup progress
     */
    public static function get_setup_progress() {
        $steps = array(
            'tables' => self::check_tables_exist(),
            'api_keys' => self::is_setup_complete(),
            'woocommerce' => class_exists('WooCommerce'),
            'products' => self::check_products_exist(),
        );

        $completed = count(array_filter($steps));
        $total = count($steps);

        return array(
            'steps' => $steps,
            'completed' => $completed,
            'total' => $total,
            'percentage' => round(($completed / $total) * 100)
        );
    }

    /**
     * Check if all tables exist
     */
    private static function check_tables_exist() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'ipv_licenses',
            $wpdb->prefix . 'ipv_license_activations',
            $wpdb->prefix . 'ipv_api_logs',
            $wpdb->prefix . 'ipv_security_log',
            $wpdb->prefix . 'ipv_performance_stats',
        );

        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if IPV Pro products exist
     */
    private static function check_products_exist() {
        if (!class_exists('WooCommerce')) {
            return false;
        }

        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_ipv_pro_product',
                    'value' => '1',
                )
            ),
            'posts_per_page' => 1,
        );

        $products = new WP_Query($args);
        return $products->have_posts();
    }

    /**
     * Create trial license
     */
    public static function create_trial_license($email) {
        global $wpdb;

        $license_key = self::generate_license_key();

        $result = $wpdb->insert(
            $wpdb->prefix . 'ipv_licenses',
            array(
                'license_key' => $license_key,
                'email' => $email,
                'plan' => 'trial',
                'credits_total' => 10,
                'credits_remaining' => 10,
                'status' => 'active',
                'valid_until' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );

        if ($result) {
            return $license_key;
        }

        return false;
    }

    /**
     * Generate random license key
     */
    private static function generate_license_key() {
        return strtoupper(bin2hex(random_bytes(16)));
    }

    /**
     * Uninstall - cleanup everything
     */
    public static function uninstall() {
        global $wpdb;

        // Drop tables
        $tables = array(
            $wpdb->prefix . 'ipv_licenses',
            $wpdb->prefix . 'ipv_license_activations',
            $wpdb->prefix . 'ipv_api_logs',
            $wpdb->prefix . 'ipv_security_log',
            $wpdb->prefix . 'ipv_performance_stats',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Delete options
        delete_option('ipv_vendor_installed');
        delete_option('ipv_vendor_version');
        delete_option('ipv_vendor_install_date');
        delete_option('ipv_vendor_youtube_api_key');
        delete_option('ipv_vendor_openai_api_key');
        delete_option('ipv_vendor_supadata_key');
        delete_option('ipv_vendor_supadata_secret');

        // Clear scheduled events
        wp_clear_scheduled_hook('ipv_vendor_monthly_reset');
        wp_clear_scheduled_hook('ipv_vendor_cleanup_logs');
        wp_clear_scheduled_hook('ipv_vendor_aggregate_stats');

        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ipv_%' OR option_name LIKE '_transient_timeout_ipv_%'");
    }
}
