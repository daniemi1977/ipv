<?php
/**
 * Database Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Database {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create all plugin tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Affiliates table
        $table_affiliates = $wpdb->prefix . 'jewe_affiliates';
        $sql_affiliates = "CREATE TABLE $table_affiliates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            referrer_id bigint(20) DEFAULT 0,
            affiliate_code varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            tier_level int(11) DEFAULT 1,
            lifetime_earnings decimal(15,2) DEFAULT 0.00,
            current_balance decimal(15,2) DEFAULT 0.00,
            total_referrals int(11) DEFAULT 0,
            total_clicks int(11) DEFAULT 0,
            conversion_rate decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_code (affiliate_code),
            KEY user_id (user_id),
            KEY referrer_id (referrer_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_affiliates);

        // Commissions table
        $table_commissions = $wpdb->prefix . 'jewe_commissions';
        $sql_commissions = "CREATE TABLE $table_commissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) DEFAULT 0,
            commission_type varchar(50) DEFAULT 'sale',
            commission_rate decimal(5,2) DEFAULT 0.00,
            commission_amount decimal(15,2) NOT NULL,
            order_total decimal(15,2) DEFAULT 0.00,
            mlm_level int(11) DEFAULT 1,
            status varchar(20) DEFAULT 'pending',
            paid_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY commission_type (commission_type)
        ) $charset_collate;";
        dbDelta($sql_commissions);

        // Tracking/Visits table
        $table_tracking = $wpdb->prefix . 'jewe_tracking';
        $sql_tracking = "CREATE TABLE $table_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            visitor_ip varchar(45) DEFAULT '',
            visitor_hash varchar(64) DEFAULT '',
            referral_url text,
            landing_page text,
            utm_source varchar(100) DEFAULT '',
            utm_medium varchar(100) DEFAULT '',
            utm_campaign varchar(100) DEFAULT '',
            device_type varchar(20) DEFAULT '',
            browser varchar(50) DEFAULT '',
            country varchar(10) DEFAULT '',
            converted tinyint(1) DEFAULT 0,
            conversion_order_id bigint(20) DEFAULT 0,
            qr_code_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visitor_hash (visitor_hash),
            KEY converted (converted),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_tracking);

        // MLM Relationships table
        $table_mlm = $wpdb->prefix . 'jewe_mlm_tree';
        $sql_mlm = "CREATE TABLE $table_mlm (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            parent_id bigint(20) DEFAULT 0,
            level int(11) DEFAULT 1,
            path varchar(500) DEFAULT '',
            direct_referrals int(11) DEFAULT 0,
            team_size int(11) DEFAULT 0,
            team_earnings decimal(15,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY parent_id (parent_id),
            KEY level (level)
        ) $charset_collate;";
        dbDelta($sql_mlm);

        // Gamification - Badges table
        $table_badges = $wpdb->prefix . 'jewe_badges';
        $sql_badges = "CREATE TABLE $table_badges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            icon varchar(255) DEFAULT '',
            requirement_type varchar(50) NOT NULL,
            requirement_value decimal(15,2) NOT NULL,
            bonus_type varchar(50) DEFAULT '',
            bonus_value decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_badges);

        // Affiliate Badges (earned)
        $table_affiliate_badges = $wpdb->prefix . 'jewe_affiliate_badges';
        $sql_affiliate_badges = "CREATE TABLE $table_affiliate_badges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            badge_id bigint(20) NOT NULL,
            earned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY badge_id (badge_id)
        ) $charset_collate;";
        dbDelta($sql_affiliate_badges);

        // Tiers table
        $table_tiers = $wpdb->prefix . 'jewe_tiers';
        $sql_tiers = "CREATE TABLE $table_tiers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            level int(11) NOT NULL,
            min_earnings decimal(15,2) DEFAULT 0.00,
            min_referrals int(11) DEFAULT 0,
            commission_rate decimal(5,2) NOT NULL,
            mlm_commission_l1 decimal(5,2) DEFAULT 0.00,
            mlm_commission_l2 decimal(5,2) DEFAULT 0.00,
            mlm_commission_l3 decimal(5,2) DEFAULT 0.00,
            perks text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level)
        ) $charset_collate;";
        dbDelta($sql_tiers);

        // QR Codes table
        $table_qrcodes = $wpdb->prefix . 'jewe_qrcodes';
        $sql_qrcodes = "CREATE TABLE $table_qrcodes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            name varchar(100) DEFAULT '',
            target_url text NOT NULL,
            qr_image_url text,
            scans int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id)
        ) $charset_collate;";
        dbDelta($sql_qrcodes);

        // Payouts table
        $table_payouts = $wpdb->prefix . 'jewe_payouts';
        $sql_payouts = "CREATE TABLE $table_payouts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            amount decimal(15,2) NOT NULL,
            payment_method varchar(50) DEFAULT 'paypal',
            payment_details text,
            transaction_id varchar(100) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            requested_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_payouts);

        // Notifications table
        $table_notifications = $wpdb->prefix . 'jewe_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        dbDelta($sql_notifications);

        // AI Insights cache table
        $table_insights = $wpdb->prefix . 'jewe_ai_insights';
        $sql_insights = "CREATE TABLE $table_insights (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            insight_type varchar(50) NOT NULL,
            insight_data longtext NOT NULL,
            score decimal(5,2) DEFAULT 0.00,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY insight_type (insight_type)
        ) $charset_collate;";
        dbDelta($sql_insights);

        update_option('jewe_affiliate_db_version', JEWE_AFFILIATE_VERSION);
    }

    /**
     * Insert default data
     */
    public static function insert_default_data() {
        global $wpdb;

        // Default tiers
        $tiers_table = $wpdb->prefix . 'jewe_tiers';
        $existing_tiers = $wpdb->get_var("SELECT COUNT(*) FROM $tiers_table");

        if ($existing_tiers == 0) {
            $default_tiers = [
                ['Bronze', 1, 0, 0, 5.00, 2.00, 1.00, 0.50, json_encode(['basic_dashboard' => true])],
                ['Silver', 2, 500, 10, 7.00, 3.00, 1.50, 0.75, json_encode(['basic_dashboard' => true, 'custom_links' => true])],
                ['Gold', 3, 2000, 50, 10.00, 4.00, 2.00, 1.00, json_encode(['basic_dashboard' => true, 'custom_links' => true, 'priority_support' => true])],
                ['Platinum', 4, 10000, 200, 15.00, 5.00, 2.50, 1.25, json_encode(['basic_dashboard' => true, 'custom_links' => true, 'priority_support' => true, 'exclusive_offers' => true])],
                ['Diamond', 5, 50000, 1000, 20.00, 7.00, 3.50, 1.75, json_encode(['all_features' => true, 'personal_manager' => true])],
            ];

            foreach ($default_tiers as $tier) {
                $wpdb->insert($tiers_table, [
                    'name' => $tier[0],
                    'level' => $tier[1],
                    'min_earnings' => $tier[2],
                    'min_referrals' => $tier[3],
                    'commission_rate' => $tier[4],
                    'mlm_commission_l1' => $tier[5],
                    'mlm_commission_l2' => $tier[6],
                    'mlm_commission_l3' => $tier[7],
                    'perks' => $tier[8],
                ]);
            }
        }

        // Default badges
        $badges_table = $wpdb->prefix . 'jewe_badges';
        $existing_badges = $wpdb->get_var("SELECT COUNT(*) FROM $badges_table");

        if ($existing_badges == 0) {
            $default_badges = [
                ['Prima Vendita', 'Hai completato la tua prima vendita!', 'dashicons-awards', 'sales_count', 1, 'bonus_commission', 5.00],
                ['10 Vendite', 'Hai raggiunto 10 vendite totali', 'dashicons-star-filled', 'sales_count', 10, 'bonus_commission', 10.00],
                ['50 Vendite', 'Campione delle vendite!', 'dashicons-star-filled', 'sales_count', 50, 'bonus_commission', 25.00],
                ['100 Referral', 'Hai invitato 100 persone', 'dashicons-groups', 'referrals_count', 100, 'commission_boost', 1.00],
                ['€1000 Guadagnati', 'Hai guadagnato €1000 in commissioni', 'dashicons-money-alt', 'earnings_total', 1000, 'bonus_commission', 50.00],
                ['€5000 Guadagnati', 'Elite Affiliate!', 'dashicons-superhero', 'earnings_total', 5000, 'commission_boost', 2.00],
                ['Team Leader', 'Hai costruito un team di 10 affiliati', 'dashicons-networking', 'team_size', 10, 'mlm_boost', 0.50],
                ['Super Team', 'Il tuo team ha 50 membri', 'dashicons-networking', 'team_size', 50, 'mlm_boost', 1.00],
                ['Streak 7 Giorni', 'Vendite per 7 giorni consecutivi', 'dashicons-calendar-alt', 'streak_days', 7, 'bonus_commission', 15.00],
                ['Streak 30 Giorni', 'Un mese di vendite consecutive!', 'dashicons-calendar-alt', 'streak_days', 30, 'bonus_commission', 100.00],
            ];

            foreach ($default_badges as $badge) {
                $wpdb->insert($badges_table, [
                    'name' => $badge[0],
                    'description' => $badge[1],
                    'icon' => $badge[2],
                    'requirement_type' => $badge[3],
                    'requirement_value' => $badge[4],
                    'bonus_type' => $badge[5],
                    'bonus_value' => $badge[6],
                ]);
            }
        }

        // Default options
        $default_options = [
            'jewe_affiliate_cookie_days' => 30,
            'jewe_affiliate_min_payout' => 50,
            'jewe_affiliate_default_commission' => 10,
            'jewe_affiliate_mlm_enabled' => 'yes',
            'jewe_affiliate_mlm_levels' => 3,
            'jewe_affiliate_gamification_enabled' => 'yes',
            'jewe_affiliate_auto_approve' => 'no',
            'jewe_affiliate_lifetime_commissions' => 'yes',
            'jewe_affiliate_qr_enabled' => 'yes',
            'jewe_affiliate_ai_insights_enabled' => 'yes',
        ];

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Get affiliate by user ID
     */
    public static function get_affiliate_by_user($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    }

    /**
     * Get affiliate by code
     */
    public static function get_affiliate_by_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE affiliate_code = %s", $code));
    }

    /**
     * Get all affiliates with pagination
     */
    public static function get_affiliates($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';

        $defaults = [
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = "WHERE 1=1";
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $limit = intval($args['limit']);
        $offset = intval($args['offset']);

        $sql = "SELECT * FROM $table $where ORDER BY $orderby LIMIT $limit OFFSET $offset";

        return $wpdb->get_results($sql);
    }

    /**
     * Get commissions for an affiliate
     */
    public static function get_commissions($affiliate_id, $args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $defaults = [
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 50,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare("WHERE affiliate_id = %d", $affiliate_id);

        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        if (!empty($args['date_from'])) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $args['date_to']);
        }

        $limit = intval($args['limit']);
        $offset = intval($args['offset']);

        $sql = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        return $wpdb->get_results($sql);
    }
}
