<?php
/**
 * Auto-Installer for IPV Production System Pro Client
 *
 * Handles automatic installation and configuration on plugin activation
 *
 * @package IPV_Production_System_Pro
 * @version 10.3.0-optimized
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Prod_Auto_Installer {

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
        update_option('ipv_prod_installed', true);
        update_option('ipv_prod_version', '10.3.0-optimized');
        update_option('ipv_prod_install_date', current_time('mysql'));

        // Redirect to setup wizard
        set_transient('ipv_prod_show_wizard', true, 60);
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Queue table
        $sql_queue = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_prod_queue (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            youtube_url varchar(255) NOT NULL,
            video_id varchar(20) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            priority int DEFAULT 5,
            mode varchar(20) DEFAULT 'standard',
            language varchar(5) DEFAULT 'it',
            metadata longtext NULL,
            error_message text NULL,
            post_id bigint(20) NULL,
            attempts int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            processed_at datetime NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY video_id (video_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // YouTube cache table
        $sql_youtube = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_prod_youtube_cache (
            video_id varchar(20) NOT NULL,
            data longtext NOT NULL,
            cached_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (video_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Performance metrics table
        $sql_metrics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_prod_metrics (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            video_id varchar(20) NOT NULL,
            operation varchar(50) NOT NULL,
            duration_ms int NOT NULL,
            cache_hit tinyint(1) DEFAULT 0,
            error tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY video_id (video_id),
            KEY operation (operation),
            KEY created_at (created_at),
            KEY cache_hit (cache_hit)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_queue);
        dbDelta($sql_youtube);
        dbDelta($sql_metrics);

        // Log installation
        error_log('[IPV Prod] Database tables created successfully');
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'ipv_prod_server_url' => '',
            'ipv_prod_license_key' => '',
            'ipv_prod_license_status' => 'inactive',
            'ipv_prod_cache_enabled' => true,
            'ipv_prod_cache_ttl' => 7 * DAY_IN_SECONDS,
            'ipv_prod_retry_enabled' => true,
            'ipv_prod_retry_max' => 3,
            'ipv_prod_circuit_breaker_enabled' => true,
            'ipv_prod_queue_auto_process' => true,
            'ipv_prod_queue_batch_size' => 5,
            'ipv_prod_youtube_update_interval' => 3600,
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
        // Queue processing (every 5 minutes)
        if (!wp_next_scheduled('ipv_prod_process_queue')) {
            wp_schedule_event(time(), 'ipv_prod_5min', 'ipv_prod_process_queue');
        }

        // YouTube data update (hourly)
        if (!wp_next_scheduled('ipv_prod_youtube_update')) {
            wp_schedule_event(time(), 'hourly', 'ipv_prod_youtube_update');
        }

        // Cleanup old cache and metrics (daily)
        if (!wp_next_scheduled('ipv_prod_cleanup')) {
            wp_schedule_event(strtotime('tomorrow 02:00:00'), 'daily', 'ipv_prod_cleanup');
        }

        // Register custom interval if needed
        add_filter('cron_schedules', function($schedules) {
            if (!isset($schedules['ipv_prod_5min'])) {
                $schedules['ipv_prod_5min'] = array(
                    'interval' => 300,
                    'display' => __('Every 5 minutes', 'ipv-production-system-pro')
                );
            }
            return $schedules;
        });
    }

    /**
     * Check if setup is complete
     */
    public static function is_setup_complete() {
        $server_url = get_option('ipv_prod_server_url');
        $license_key = get_option('ipv_prod_license_key');
        $license_status = get_option('ipv_prod_license_status');

        return !empty($server_url) && !empty($license_key) && $license_status === 'active';
    }

    /**
     * Get setup progress
     */
    public static function get_setup_progress() {
        $steps = array(
            'tables' => self::check_tables_exist(),
            'server_url' => !empty(get_option('ipv_prod_server_url')),
            'license' => !empty(get_option('ipv_prod_license_key')),
            'activated' => get_option('ipv_prod_license_status') === 'active',
            'cron' => self::check_cron_setup(),
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
            $wpdb->prefix . 'ipv_prod_queue',
            $wpdb->prefix . 'ipv_prod_youtube_cache',
            $wpdb->prefix . 'ipv_prod_metrics',
        );

        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if CRON is properly setup
     */
    private static function check_cron_setup() {
        return wp_next_scheduled('ipv_prod_process_queue') !== false;
    }

    /**
     * Test connection to server
     */
    public static function test_server_connection($server_url) {
        $test_url = trailingslashit($server_url) . 'wp-json/ipv-vendor/v1/health';

        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'sslverify' => true,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return array(
                'success' => false,
                'message' => sprintf('Server returned status code %d', $code)
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!$body || !isset($body['status']) || $body['status'] !== 'ok') {
            return array(
                'success' => false,
                'message' => 'Invalid health check response'
            );
        }

        return array(
            'success' => true,
            'message' => 'Server connection successful',
            'version' => $body['version'] ?? 'unknown'
        );
    }

    /**
     * Validate and activate license
     */
    public static function activate_license($server_url, $license_key) {
        $api_url = trailingslashit($server_url) . 'wp-json/ipv-vendor/v1/license/validate';

        $response = wp_remote_post($api_url, array(
            'timeout' => 15,
            'body' => json_encode(array(
                'license_key' => $license_key,
                'site_url' => home_url()
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return array(
                'success' => false,
                'message' => $body['message'] ?? 'License validation failed'
            );
        }

        if (!$body || !isset($body['valid']) || !$body['valid']) {
            return array(
                'success' => false,
                'message' => $body['message'] ?? 'Invalid license'
            );
        }

        // Save license info
        update_option('ipv_prod_server_url', $server_url);
        update_option('ipv_prod_license_key', $license_key);
        update_option('ipv_prod_license_status', 'active');
        update_option('ipv_prod_license_plan', $body['plan'] ?? 'unknown');
        update_option('ipv_prod_license_credits', $body['credits_remaining'] ?? 0);

        return array(
            'success' => true,
            'message' => 'License activated successfully',
            'plan' => $body['plan'] ?? 'unknown',
            'credits' => $body['credits_remaining'] ?? 0
        );
    }

    /**
     * Uninstall - cleanup everything
     */
    public static function uninstall() {
        global $wpdb;

        // Drop tables
        $tables = array(
            $wpdb->prefix . 'ipv_prod_queue',
            $wpdb->prefix . 'ipv_prod_youtube_cache',
            $wpdb->prefix . 'ipv_prod_metrics',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Delete options
        $options = array(
            'ipv_prod_installed',
            'ipv_prod_version',
            'ipv_prod_install_date',
            'ipv_prod_server_url',
            'ipv_prod_license_key',
            'ipv_prod_license_status',
            'ipv_prod_license_plan',
            'ipv_prod_license_credits',
        );

        foreach ($options as $option) {
            delete_option($option);
        }

        // Clear scheduled events
        wp_clear_scheduled_hook('ipv_prod_process_queue');
        wp_clear_scheduled_hook('ipv_prod_youtube_update');
        wp_clear_scheduled_hook('ipv_prod_cleanup');

        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ipv_prod_%' OR option_name LIKE '_transient_timeout_ipv_prod_%'");
    }
}
