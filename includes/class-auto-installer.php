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

        // Licenses table - SCHEMA COMPLETO v1.6.1
        $sql_licenses = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_licenses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(100) NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            product_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            email varchar(255) NOT NULL,
            domain varchar(255) NULL,
            status varchar(20) DEFAULT 'active',
            variant_slug varchar(50) NOT NULL DEFAULT 'starter',
            credits_total int UNSIGNED NOT NULL DEFAULT 10,
            credits_monthly int UNSIGNED NOT NULL DEFAULT 0,
            credits_extra int UNSIGNED NOT NULL DEFAULT 0,
            credits_remaining int UNSIGNED NOT NULL DEFAULT 10,
            credits_used_month int UNSIGNED NOT NULL DEFAULT 0,
            credits_reset_date date NOT NULL,
            activation_limit int UNSIGNED DEFAULT 1,
            activation_count int UNSIGNED DEFAULT 0,
            site_url varchar(255) NULL,
            site_unlock_at datetime NULL,
            expires_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY email (email),
            KEY status (status),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY variant_slug (variant_slug)
        ) $charset_collate;";

        // License activations table
        $sql_activations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_activations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            site_url varchar(255) NOT NULL,
            site_name varchar(255) NULL,
            ip_address varchar(45) NULL,
            activated_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_checked_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY site_url (site_url),
            KEY is_active (is_active)
        ) $charset_collate;";

        // API logs table
        $sql_api_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_api_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NULL,
            endpoint varchar(100) NOT NULL,
            video_id varchar(50) NULL,
            method varchar(10) NOT NULL DEFAULT 'GET',
            status_code int UNSIGNED NULL,
            response_time int UNSIGNED NULL,
            credits_used int UNSIGNED DEFAULT 0,
            cached tinyint(1) DEFAULT 0,
            ip_address varchar(45) NULL,
            user_agent varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY endpoint (endpoint),
            KEY created_at (created_at),
            KEY video_id (video_id),
            KEY status_code (status_code)
        ) $charset_collate;";

        // Rate limits table (v1.5.0)
        $sql_rate_limits = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_rate_limits (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier varchar(191) NOT NULL,
            endpoint varchar(100) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 1,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            KEY identifier_endpoint (identifier, endpoint),
            KEY window_start (window_start)
        ) $charset_collate;";

        // Audit log table (v1.5.0)
        $sql_audit_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_audit_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_action varchar(50) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT 0,
            user_email varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id varchar(100) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY object_type_id (object_type, object_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Credit ledger table (v1.4.1)
        $sql_credit_ledger = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_credit_ledger (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(64) NOT NULL,
            type varchar(32) NOT NULL,
            amount int NOT NULL,
            balance_after int NOT NULL,
            ref_type varchar(32) NULL,
            ref_id varchar(64) NULL,
            note text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license (license_key),
            KEY idx_type (type),
            KEY idx_created (created_at)
        ) $charset_collate;";

        // Transcript cache table
        $sql_transcript_cache = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_transcript_cache (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            video_id varchar(50) NOT NULL,
            mode varchar(20) NOT NULL,
            lang varchar(10) NOT NULL,
            transcript mediumtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_video (video_id, mode, lang),
            KEY created_at (created_at),
            KEY video_id (video_id)
        ) $charset_collate;";

        // Golden prompts table (v1.6.0)
        $sql_golden_prompts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_golden_prompts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            config_json longtext NULL,
            golden_prompt longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_license (license_id)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_licenses);
        dbDelta($sql_activations);
        dbDelta($sql_api_logs);
        dbDelta($sql_rate_limits);
        dbDelta($sql_audit_log);
        dbDelta($sql_credit_ledger);
        dbDelta($sql_transcript_cache);
        dbDelta($sql_golden_prompts);

        // Database upgrade system - version tracking
        $current_db_version = get_option('ipv_vendor_db_version', '0');
        $target_db_version = '1.9.2';

        if (version_compare($current_db_version, '1.9.0', '<')) {
            self::upgrade_to_1_9_0();
        }

        // Update DB version
        update_option('ipv_vendor_db_version', $target_db_version);

        // Log installation
        error_log('[IPV Vendor] Database tables created/updated successfully (v' . $target_db_version . ')');
    }

    /**
     * Upgrade to v1.9.0 - Add domain column
     */
    private static function upgrade_to_1_9_0() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ipv_licenses';
        
        // Check if column exists using multiple methods
        $column_exists = false;
        
        // Method 1: Direct query
        $wpdb->suppress_errors();
        $test = $wpdb->get_var("SELECT domain FROM {$table_name} LIMIT 1");
        if ($wpdb->last_error === '') {
            $column_exists = true;
        }
        $wpdb->show_errors();
        
        // Method 2: SHOW COLUMNS (fallback)
        if (!$column_exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}`");
            foreach ($columns as $column) {
                if ($column->Field === 'domain') {
                    $column_exists = true;
                    break;
                }
            }
        }
        
        // Add column if not exists
        if (!$column_exists) {
            $wpdb->suppress_errors();
            $result = $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `domain` varchar(255) NULL AFTER `email`");
            $wpdb->show_errors();
            
            if ($result !== false) {
                error_log('[IPV Vendor] ✅ Upgrade 1.9.0: Added domain column');
                
                // Verify it was added
                $verify = $wpdb->get_var("SELECT domain FROM {$table_name} LIMIT 1");
                if ($wpdb->last_error !== '') {
                    error_log('[IPV Vendor] ⚠️ Domain column added but verification failed: ' . $wpdb->last_error);
                }
            } else {
                error_log('[IPV Vendor] ❌ Upgrade 1.9.0 FAILED: Could not add domain column');
                error_log('[IPV Vendor] SQL Error: ' . $wpdb->last_error);
                
                // Set admin notice
                set_transient('ipv_vendor_upgrade_failed', [
                    'version' => '1.9.0',
                    'error' => $wpdb->last_error,
                    'table' => $table_name
                ], 3600);
            }
        } else {
            error_log('[IPV Vendor] ✓ Upgrade 1.9.0: Domain column already exists');
        }
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
     * Repair database - adds missing columns and tables
     * Can be called from admin or WP-CLI
     */
    public static function repair_database() {
        global $wpdb;

        $results = [];

        // 1. Add missing columns to ipv_licenses
        $table = $wpdb->prefix . 'ipv_licenses';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
            
            $missing_columns = [
                'order_id' => "BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER license_key",
                'product_id' => "BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER order_id",
                'user_id' => "BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER product_id",
                'variant_slug' => "VARCHAR(50) NOT NULL DEFAULT 'starter' AFTER status",
                'credits_monthly' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_total",
                'credits_extra' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_monthly",
                'credits_used_month' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_extra",
                'activation_limit' => "INT UNSIGNED DEFAULT 1",
                'activation_count' => "INT UNSIGNED DEFAULT 0",
                'site_url' => "VARCHAR(255) NULL",
                'site_unlock_at' => "DATETIME NULL",
                'expires_at' => "DATETIME NULL",
            ];

            foreach ($missing_columns as $col => $definition) {
                if (!in_array($col, $columns)) {
                    $sql = "ALTER TABLE {$table} ADD COLUMN {$col} {$definition}";
                    $wpdb->query($sql);
                    $results[] = "Added column: {$col}";
                }
            }
        } else {
            // Table doesn't exist, create it
            self::create_tables();
            $results[] = "Created all tables";
        }

        // 2. Create missing tables
        $tables_to_check = [
            'ipv_rate_limits',
            'ipv_audit_log', 
            'ipv_credit_ledger',
            'ipv_activations',
            'ipv_golden_prompts',
            'ipv_transcript_cache',
        ];

        foreach ($tables_to_check as $tbl) {
            $full_table = $wpdb->prefix . $tbl;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") !== $full_table) {
                // Table doesn't exist, create all tables
                self::create_tables();
                $results[] = "Created table: {$tbl}";
                break; // create_tables creates all at once
            }
        }

        // 3. Update DB version
        update_option('ipv_vendor_db_version', '1.6.1');
        $results[] = "Updated DB version to 1.6.1";

        return $results;
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
