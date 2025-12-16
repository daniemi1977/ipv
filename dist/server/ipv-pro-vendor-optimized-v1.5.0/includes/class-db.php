<?php
/**
 * Database Migration - v1.4.1
 *
 * Adds credits_monthly, credits_extra, credits_used_month, site_unlock_at columns
 * Creates ipv_credit_ledger table for audit trail
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_DB {

    /**
     * Run database migrations
     */
    public static function migrate() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Add new columns to ipv_licenses table
        self::add_license_columns();

        // Create ledger table
        self::create_ledger_table();

        // Create rate limits table (v1.5.0)
        IPV_Vendor_Rate_Limiter::create_table();

        // Create audit log table (v1.5.0)
        IPV_Vendor_Audit_Log::create_table();

        // Update version
        update_option( 'ipv_vendor_db_version', '1.5.0' );
    }

    /**
     * Add new columns to ipv_licenses table
     */
    private static function add_license_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_licenses';

        // Check if columns exist
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}" );

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

        // Add site_unlock_at (for cooldown tracking)
        if ( ! in_array( 'site_unlock_at', $columns ) ) {
            // Check if site_url exists first
            if ( in_array( 'site_url', $columns ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN site_unlock_at DATETIME NULL AFTER site_url" );
            } else {
                // If site_url doesn't exist, add it first then add site_unlock_at
                $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN site_url VARCHAR(255) NULL" );
                $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN site_unlock_at DATETIME NULL AFTER site_url" );
            }
        }

        // Migrate existing data: credits_total -> credits_monthly
        $wpdb->query( "UPDATE {$table_name} SET credits_monthly = credits_total WHERE credits_monthly = 0" );
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
     * Get database version
     */
    public static function get_version() {
        return get_option( 'ipv_vendor_db_version', '1.4.0' );
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration() {
        return version_compare( self::get_version(), '1.4.1', '<' );
    }
}
