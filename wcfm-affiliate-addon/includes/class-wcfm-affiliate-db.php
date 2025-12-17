<?php
/**
 * Database handler class
 *
 * Gestisce la creazione e manutenzione delle tabelle database per il sistema affiliate.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_DB
 */
class WCFM_Affiliate_DB {

    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

    /**
     * Table names
     */
    public static string $table_affiliates;
    public static string $table_referrals;
    public static string $table_commissions;
    public static string $table_payouts;
    public static string $table_payout_items;
    public static string $table_clicks;
    public static string $table_visits;
    public static string $table_coupons;
    public static string $table_notifications;
    public static string $table_settings;
    public static string $table_tiers;
    public static string $table_creatives;
    public static string $table_mlm;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        self::$table_affiliates = $wpdb->prefix . 'wcfm_affiliates';
        self::$table_referrals = $wpdb->prefix . 'wcfm_affiliate_referrals';
        self::$table_commissions = $wpdb->prefix . 'wcfm_affiliate_commissions';
        self::$table_payouts = $wpdb->prefix . 'wcfm_affiliate_payouts';
        self::$table_payout_items = $wpdb->prefix . 'wcfm_affiliate_payout_items';
        self::$table_clicks = $wpdb->prefix . 'wcfm_affiliate_clicks';
        self::$table_visits = $wpdb->prefix . 'wcfm_affiliate_visits';
        self::$table_coupons = $wpdb->prefix . 'wcfm_affiliate_coupons';
        self::$table_notifications = $wpdb->prefix . 'wcfm_affiliate_notifications';
        self::$table_settings = $wpdb->prefix . 'wcfm_affiliate_settings';
        self::$table_tiers = $wpdb->prefix . 'wcfm_affiliate_tiers';
        self::$table_creatives = $wpdb->prefix . 'wcfm_affiliate_creatives';
        self::$table_mlm = $wpdb->prefix . 'wcfm_affiliate_mlm';

        // Check for updates
        $this->maybe_upgrade();
    }

    /**
     * Create all tables
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Affiliates table
        $sql_affiliates = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_code VARCHAR(50) NOT NULL,
            status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending',
            parent_affiliate_id BIGINT(20) UNSIGNED DEFAULT NULL,
            tier_id BIGINT(20) UNSIGNED DEFAULT NULL,
            vendor_id BIGINT(20) UNSIGNED DEFAULT NULL,
            payment_email VARCHAR(255) DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT 'paypal',
            payment_details TEXT DEFAULT NULL,
            earnings_balance DECIMAL(12,2) DEFAULT 0.00,
            earnings_paid DECIMAL(12,2) DEFAULT 0.00,
            earnings_total DECIMAL(12,2) DEFAULT 0.00,
            referrals_count INT(11) DEFAULT 0,
            visits_count INT(11) DEFAULT 0,
            conversion_rate DECIMAL(5,2) DEFAULT 0.00,
            custom_commission_type VARCHAR(20) DEFAULT NULL,
            custom_commission_rate DECIMAL(10,2) DEFAULT NULL,
            website_url VARCHAR(255) DEFAULT NULL,
            promotional_methods TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            rejection_reason TEXT DEFAULT NULL,
            approved_by BIGINT(20) UNSIGNED DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            last_login DATETIME DEFAULT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_code (affiliate_code),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY parent_affiliate_id (parent_affiliate_id),
            KEY vendor_id (vendor_id),
            KEY tier_id (tier_id),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_affiliates);

        // Referrals table
        $sql_referrals = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_referrals (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED DEFAULT NULL,
            product_id BIGINT(20) UNSIGNED DEFAULT NULL,
            customer_id BIGINT(20) UNSIGNED DEFAULT NULL,
            visit_id BIGINT(20) UNSIGNED DEFAULT NULL,
            vendor_id BIGINT(20) UNSIGNED DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'EUR',
            commission_type VARCHAR(50) DEFAULT 'sale',
            context VARCHAR(50) DEFAULT 'woocommerce',
            campaign VARCHAR(100) DEFAULT NULL,
            reference VARCHAR(100) DEFAULT NULL,
            custom_data LONGTEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            payout_id BIGINT(20) UNSIGNED DEFAULT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY vendor_id (vendor_id),
            KEY status (status),
            KEY payout_id (payout_id),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_referrals);

        // Commissions table (for detailed commission tracking)
        $sql_commissions = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_commissions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referral_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            order_item_id BIGINT(20) UNSIGNED DEFAULT NULL,
            product_id BIGINT(20) UNSIGNED DEFAULT NULL,
            vendor_id BIGINT(20) UNSIGNED DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected', 'paid', 'refunded') DEFAULT 'pending',
            type VARCHAR(50) DEFAULT 'percentage',
            rate DECIMAL(10,4) NOT NULL DEFAULT 0.00,
            base_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'EUR',
            mlm_level INT(3) DEFAULT 1,
            parent_commission_id BIGINT(20) UNSIGNED DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            approved_by BIGINT(20) UNSIGNED DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referral_id (referral_id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY vendor_id (vendor_id),
            KEY status (status),
            KEY mlm_level (mlm_level),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_commissions);

        // Payouts table
        $sql_payouts = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_payouts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'EUR',
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'paypal',
            payment_email VARCHAR(255) DEFAULT NULL,
            payment_details TEXT DEFAULT NULL,
            transaction_id VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            admin_notes TEXT DEFAULT NULL,
            processed_by BIGINT(20) UNSIGNED DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status),
            KEY payment_method (payment_method),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_payouts);

        // Payout items table
        $sql_payout_items = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_payout_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            payout_id BIGINT(20) UNSIGNED NOT NULL,
            referral_id BIGINT(20) UNSIGNED NOT NULL,
            commission_id BIGINT(20) UNSIGNED DEFAULT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY payout_id (payout_id),
            KEY referral_id (referral_id),
            KEY commission_id (commission_id)
        ) $charset_collate;";

        dbDelta($sql_payout_items);

        // Clicks table
        $sql_clicks = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_clicks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            visit_id BIGINT(20) UNSIGNED DEFAULT NULL,
            url VARCHAR(500) NOT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            campaign VARCHAR(100) DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT DEFAULT NULL,
            device_type VARCHAR(20) DEFAULT NULL,
            browser VARCHAR(50) DEFAULT NULL,
            os VARCHAR(50) DEFAULT NULL,
            country VARCHAR(2) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            converted TINYINT(1) DEFAULT 0,
            conversion_date DATETIME DEFAULT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visit_id (visit_id),
            KEY ip_address (ip_address),
            KEY converted (converted),
            KEY campaign (campaign),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_clicks);

        // Visits table (sessions)
        $sql_visits = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_visits (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            customer_id BIGINT(20) UNSIGNED DEFAULT NULL,
            session_id VARCHAR(100) DEFAULT NULL,
            landing_url VARCHAR(500) NOT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            campaign VARCHAR(100) DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT DEFAULT NULL,
            device_type VARCHAR(20) DEFAULT NULL,
            country VARCHAR(2) DEFAULT NULL,
            pages_viewed INT(5) DEFAULT 1,
            converted TINYINT(1) DEFAULT 0,
            conversion_date DATETIME DEFAULT NULL,
            order_id BIGINT(20) UNSIGNED DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY customer_id (customer_id),
            KEY session_id (session_id),
            KEY ip_address (ip_address),
            KEY converted (converted),
            KEY expires_at (expires_at),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_visits);

        // Coupons table
        $sql_coupons = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_coupons (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            coupon_id BIGINT(20) UNSIGNED NOT NULL,
            coupon_code VARCHAR(100) NOT NULL,
            status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
            uses_count INT(11) DEFAULT 0,
            revenue_generated DECIMAL(12,2) DEFAULT 0.00,
            commission_earned DECIMAL(12,2) DEFAULT 0.00,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY coupon_id (coupon_id),
            KEY coupon_code (coupon_code),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_coupons);

        // Notifications table
        $sql_notifications = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_notifications (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data LONGTEXT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY date_created (date_created)
        ) $charset_collate;";

        dbDelta($sql_notifications);

        // Affiliate tiers table
        $sql_tiers = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_tiers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            commission_type VARCHAR(20) DEFAULT 'percentage',
            commission_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            flat_rate DECIMAL(10,2) DEFAULT NULL,
            minimum_referrals INT(11) DEFAULT 0,
            minimum_earnings DECIMAL(12,2) DEFAULT 0.00,
            bonus_rate DECIMAL(10,2) DEFAULT 0.00,
            badge_image VARCHAR(255) DEFAULT NULL,
            color VARCHAR(20) DEFAULT NULL,
            priority INT(5) DEFAULT 0,
            is_default TINYINT(1) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY is_default (is_default),
            KEY priority (priority)
        ) $charset_collate;";

        dbDelta($sql_tiers);

        // Creatives table (banners, links, etc.)
        $sql_creatives = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_creatives (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            type ENUM('banner', 'text_link', 'email', 'social') DEFAULT 'banner',
            url VARCHAR(500) NOT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            text TEXT DEFAULT NULL,
            width INT(5) DEFAULT NULL,
            height INT(5) DEFAULT NULL,
            vendor_id BIGINT(20) UNSIGNED DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            impressions INT(11) DEFAULT 0,
            clicks INT(11) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY vendor_id (vendor_id),
            KEY status (status),
            KEY category (category)
        ) $charset_collate;";

        dbDelta($sql_creatives);

        // MLM structure table
        $sql_mlm = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wcfm_affiliate_mlm (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            parent_id BIGINT(20) UNSIGNED NOT NULL,
            level INT(3) NOT NULL DEFAULT 1,
            path VARCHAR(500) DEFAULT NULL,
            direct_downlines INT(11) DEFAULT 0,
            total_downlines INT(11) DEFAULT 0,
            team_earnings DECIMAL(12,2) DEFAULT 0.00,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_id (affiliate_id),
            KEY parent_id (parent_id),
            KEY level (level)
        ) $charset_collate;";

        dbDelta($sql_mlm);

        // Update db version
        update_option('wcfm_affiliate_db_version', self::DB_VERSION);

        // Create default tier
        self::create_default_tier();
    }

    /**
     * Create default tier
     */
    private static function create_default_tier(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'wcfm_affiliate_tiers';

        // Check if default tier exists
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_default = 1");

        if (!$exists) {
            $wpdb->insert($table, [
                'name' => __('Standard', 'wcfm-affiliate-pro'),
                'slug' => 'standard',
                'description' => __('Livello affiliato standard', 'wcfm-affiliate-pro'),
                'commission_type' => 'percentage',
                'commission_rate' => 10.00,
                'minimum_referrals' => 0,
                'minimum_earnings' => 0.00,
                'is_default' => 1,
                'status' => 'active',
                'priority' => 0,
                'color' => '#00897b',
            ], ['%s', '%s', '%s', '%s', '%f', '%d', '%f', '%d', '%s', '%d', '%s']);
        }
    }

    /**
     * Maybe upgrade database
     */
    private function maybe_upgrade(): void {
        $current_version = get_option('wcfm_affiliate_db_version', '0');

        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::create_tables();
        }
    }

    /**
     * Drop all tables (for uninstall)
     */
    public static function drop_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'wcfm_affiliates',
            $wpdb->prefix . 'wcfm_affiliate_referrals',
            $wpdb->prefix . 'wcfm_affiliate_commissions',
            $wpdb->prefix . 'wcfm_affiliate_payouts',
            $wpdb->prefix . 'wcfm_affiliate_payout_items',
            $wpdb->prefix . 'wcfm_affiliate_clicks',
            $wpdb->prefix . 'wcfm_affiliate_visits',
            $wpdb->prefix . 'wcfm_affiliate_coupons',
            $wpdb->prefix . 'wcfm_affiliate_notifications',
            $wpdb->prefix . 'wcfm_affiliate_tiers',
            $wpdb->prefix . 'wcfm_affiliate_creatives',
            $wpdb->prefix . 'wcfm_affiliate_mlm',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('wcfm_affiliate_db_version');
    }

    /**
     * Get table name
     */
    public static function get_table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'wcfm_affiliate_' . $name;
    }

    /**
     * Insert row
     */
    public static function insert(string $table, array $data, array $format = null): int {
        global $wpdb;

        $table_name = self::get_table($table);
        $wpdb->insert($table_name, $data, $format);

        return $wpdb->insert_id;
    }

    /**
     * Update row
     */
    public static function update(string $table, array $data, array $where, array $format = null, array $where_format = null): int {
        global $wpdb;

        $table_name = self::get_table($table);
        return $wpdb->update($table_name, $data, $where, $format, $where_format);
    }

    /**
     * Delete row
     */
    public static function delete(string $table, array $where, array $where_format = null): int {
        global $wpdb;

        $table_name = self::get_table($table);
        return $wpdb->delete($table_name, $where, $where_format);
    }

    /**
     * Get row
     */
    public static function get_row(string $table, array $where): ?object {
        global $wpdb;

        $table_name = self::get_table($table);

        $conditions = [];
        $values = [];

        foreach ($where as $key => $value) {
            $conditions[] = "$key = %s";
            $values[] = $value;
        }

        $sql = "SELECT * FROM $table_name WHERE " . implode(' AND ', $conditions);

        return $wpdb->get_row($wpdb->prepare($sql, $values));
    }

    /**
     * Get rows
     */
    public static function get_rows(string $table, array $args = []): array {
        global $wpdb;

        $table_name = self::get_table($table);

        $defaults = [
            'where' => [],
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM $table_name";
        $values = [];

        if (!empty($args['where'])) {
            $conditions = [];
            foreach ($args['where'] as $key => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $conditions[] = "$key IN ($placeholders)";
                    $values = array_merge($values, $value);
                } else {
                    $conditions[] = "$key = %s";
                    $values[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $values[] = $args['limit'];

            if ($args['offset'] > 0) {
                $sql .= " OFFSET %d";
                $values[] = $args['offset'];
            }
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Count rows
     */
    public static function count(string $table, array $where = []): int {
        global $wpdb;

        $table_name = self::get_table($table);

        $sql = "SELECT COUNT(*) FROM $table_name";
        $values = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "$key = %s";
                $values[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Run custom query
     */
    public static function query(string $sql, array $values = []): mixed {
        global $wpdb;

        if (!empty($values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $values));
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get single value
     */
    public static function get_var(string $sql, array $values = []): mixed {
        global $wpdb;

        if (!empty($values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return $wpdb->get_var($sql);
    }
}
