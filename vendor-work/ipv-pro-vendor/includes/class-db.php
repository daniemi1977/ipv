<?php
/**
 * Database Migration - v1.6.1
 *
 * Adds credits_monthly, credits_extra, credits_used_month, site_unlock_at, order_id columns
 * Creates ipv_credit_ledger, ipv_rate_limits, ipv_audit_log, ipv_golden_prompts tables
 *
 * @package IPV_Pro_Vendor
 * @since 1.6.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_DB {

    const DB_VERSION = '1.6.1';

    /**
     * Run database migrations
     */
    public static function migrate() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Create base licenses table if not exists
        self::create_licenses_table();

        // Add new columns to ipv_licenses table
        self::add_license_columns();

        // Create ledger table
        self::create_ledger_table();

        // Create rate limits table (v1.5.0)
        self::create_rate_limits_table();

        // Create audit log table (v1.5.0)
        self::create_audit_log_table();

        // Create golden prompts table (v1.6.0)
        self::create_golden_prompts_table();

        // Update version
        update_option( 'ipv_vendor_db_version', self::DB_VERSION );
    }

    /**
     * Create licenses table if not exists
     */
    private static function create_licenses_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_licenses';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(100) UNIQUE NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            variant_slug VARCHAR(50) NOT NULL,
            credits_total INT UNSIGNED NOT NULL DEFAULT 10,
            credits_monthly INT UNSIGNED NOT NULL DEFAULT 0,
            credits_extra INT UNSIGNED NOT NULL DEFAULT 0,
            credits_remaining INT UNSIGNED NOT NULL DEFAULT 10,
            credits_used_month INT UNSIGNED NOT NULL DEFAULT 0,
            credits_reset_date DATE NOT NULL,
            activation_limit INT UNSIGNED DEFAULT 1,
            activation_count INT UNSIGNED DEFAULT 0,
            site_url VARCHAR(255) NULL,
            site_unlock_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_license_key (license_key),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_product_id (product_id),
            INDEX idx_email (email)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Add new columns to ipv_licenses table
     */
    private static function add_license_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_licenses';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
            return; // Table will be created by create_licenses_table
        }

        // Check existing columns
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}" );

        // v1.0.16 - Add domain column if missing (REGOLA: 1 dominio = 1 licenza)
        if ( ! in_array( 'domain', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN domain VARCHAR(255) NULL AFTER license_key" );
            error_log( '[IPV Vendor DB] Added column: domain' );
        }

        // Add order_id if missing (v1.5.0)
        if ( ! in_array( 'order_id', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER license_key" );
        }

        // Add product_id if missing
        if ( ! in_array( 'product_id', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER order_id" );
        }

        // Add credits_monthly (reset every month)
        if ( ! in_array( 'credits_monthly', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN credits_monthly INT NOT NULL DEFAULT 0 AFTER credits_total" );
        }

        // Add credits_extra (persistent, purchased separately)
        if ( ! in_array( 'credits_extra', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN credits_extra INT NOT NULL DEFAULT 0 AFTER credits_monthly" );
        }

        // Add credits_used_month (resets with monthly credits)
        if ( ! in_array( 'credits_used_month', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN credits_used_month INT NOT NULL DEFAULT 0 AFTER credits_extra" );
        }

        // Add site_url if missing
        if ( ! in_array( 'site_url', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN site_url VARCHAR(255) NULL" );
        }

        // Add site_unlock_at (for cooldown tracking)
        if ( ! in_array( 'site_unlock_at', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN site_unlock_at DATETIME NULL AFTER site_url" );
        }

        // v1.0.16 - Add UNIQUE KEY on domain (enforces 1 domain = 1 license)
        // Solo se non esistono duplicati
        $duplicates = $wpdb->get_var(
            "SELECT COUNT(*) FROM (
                SELECT domain FROM {$table_name} 
                WHERE domain IS NOT NULL AND domain != '' 
                GROUP BY domain HAVING COUNT(*) > 1
            ) as dups"
        );
        
        if ( $duplicates == 0 ) {
            // Check if UNIQUE KEY already exists
            $indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_name} WHERE Key_name = 'uniq_domain'" );
            
            if ( empty( $indexes ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY uniq_domain (domain)" );
                if ( $result !== false ) {
                    error_log( '[IPV Vendor DB] Added UNIQUE KEY: uniq_domain' );
                }
            }
        } else {
            error_log( '[IPV Vendor DB] Cannot add UNIQUE KEY - duplicates found: ' . $duplicates );
        }

        // Migrate existing data: credits_total -> credits_monthly
        $wpdb->query( "UPDATE {$table_name} SET credits_monthly = credits_total WHERE credits_monthly = 0 AND credits_total > 0" );
    }

    /**
     * Create credit ledger table
     */
    private static function create_ledger_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_credit_ledger';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(64) NOT NULL,
            type VARCHAR(32) NOT NULL COMMENT 'grant_monthly, grant_extra, consume, adjust',
            amount INT NOT NULL COMMENT 'Positive for grants, negative for consumption',
            balance_after INT NOT NULL COMMENT 'Balance after this transaction',
            ref_type VARCHAR(32) NULL COMMENT 'order, admin, cron, api',
            ref_id VARCHAR(64) NULL COMMENT 'Order ID, admin user ID, etc',
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_license (license_key),
            INDEX idx_type (type),
            INDEX idx_created (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create rate limits table (v1.5.0)
     */
    private static function create_rate_limits_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_rate_limits';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier varchar(191) NOT NULL,
            endpoint varchar(100) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 1,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            KEY identifier_endpoint (identifier, endpoint),
            KEY window_start (window_start)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create audit log table (v1.5.0)
     */
    private static function create_audit_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_audit_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
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
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create golden prompts table (v1.6.0)
     */
    private static function create_golden_prompts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_golden_prompts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            config_json LONGTEXT NULL,
            golden_prompt LONGTEXT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_license (license_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Get database version
     */
    public static function get_version() {
        return get_option( 'ipv_vendor_db_version', '1.0.0' );
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration() {
        return version_compare( self::get_version(), self::DB_VERSION, '<' );
    }

    /**
     * Run migration if needed (call on admin_init)
     */
    public static function maybe_migrate() {
        if ( self::needs_migration() ) {
            self::migrate();
        }
    }
}
