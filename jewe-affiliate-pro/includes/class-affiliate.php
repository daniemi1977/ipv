<?php
/**
 * Affiliate Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate {

    private static $instance = null;
    private $affiliate_id;
    private $user_id;
    private $data;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get affiliate by ID
     */
    public static function get($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
    }

    /**
     * Get affiliate by user ID
     */
    public static function get_by_user($user_id) {
        return JEWE_Affiliate_Database::get_affiliate_by_user($user_id);
    }

    /**
     * Get affiliate by code
     */
    public static function get_by_code($code) {
        return JEWE_Affiliate_Database::get_affiliate_by_code($code);
    }

    /**
     * Create new affiliate
     */
    public static function create($user_id, $referrer_code = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';

        // Check if user already is an affiliate
        $existing = self::get_by_user($user_id);
        if ($existing) {
            return new WP_Error('affiliate_exists', __('User is already an affiliate', 'jewe-affiliate-pro'));
        }

        // Generate unique code
        $code = self::generate_unique_code($user_id);

        // Get referrer ID if code provided
        $referrer_id = 0;
        if (!empty($referrer_code)) {
            $referrer = self::get_by_code($referrer_code);
            if ($referrer) {
                $referrer_id = $referrer->id;
            }
        }

        // Auto approve or pending?
        $auto_approve = get_option('jewe_affiliate_auto_approve', 'no');
        $status = ($auto_approve === 'yes') ? 'active' : 'pending';

        $result = $wpdb->insert($table, [
            'user_id' => $user_id,
            'referrer_id' => $referrer_id,
            'affiliate_code' => $code,
            'status' => $status,
            'tier_level' => 1,
        ]);

        if ($result) {
            $affiliate_id = $wpdb->insert_id;

            // Create MLM entry
            if ($referrer_id > 0) {
                JEWE_Affiliate_MLM::add_to_tree($affiliate_id, $referrer_id);
            }

            // Send notification
            JEWE_Affiliate_Notifications::send($affiliate_id, 'welcome', [
                'status' => $status,
                'code' => $code,
            ]);

            do_action('jewe_affiliate_created', $affiliate_id, $user_id);

            return $affiliate_id;
        }

        return new WP_Error('creation_failed', __('Failed to create affiliate', 'jewe-affiliate-pro'));
    }

    /**
     * Generate unique affiliate code
     */
    public static function generate_unique_code($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';

        $user = get_userdata($user_id);
        $base = $user ? sanitize_title($user->display_name) : 'aff';

        $code = $base . rand(100, 999);

        // Ensure uniqueness
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE affiliate_code = %s",
            $code
        ));

        while ($exists > 0) {
            $code = $base . rand(1000, 9999);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE affiliate_code = %s",
                $code
            ));
        }

        return $code;
    }

    /**
     * Update affiliate status
     */
    public static function update_status($affiliate_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';

        $valid_statuses = ['pending', 'active', 'suspended', 'rejected'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        $result = $wpdb->update(
            $table,
            ['status' => $status],
            ['id' => $affiliate_id]
        );

        if ($result !== false) {
            JEWE_Affiliate_Notifications::send($affiliate_id, 'status_changed', ['status' => $status]);
            do_action('jewe_affiliate_status_changed', $affiliate_id, $status);
        }

        return $result !== false;
    }

    /**
     * Update affiliate tier
     */
    public static function update_tier($affiliate_id) {
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
        $tiers_table = $wpdb->prefix . 'jewe_tiers';

        $affiliate = self::get($affiliate_id);
        if (!$affiliate) {
            return false;
        }

        // Get appropriate tier based on earnings and referrals
        $new_tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tiers_table
             WHERE min_earnings <= %f AND min_referrals <= %d
             ORDER BY level DESC LIMIT 1",
            $affiliate->lifetime_earnings,
            $affiliate->total_referrals
        ));

        if ($new_tier && $new_tier->level > $affiliate->tier_level) {
            $wpdb->update(
                $affiliates_table,
                ['tier_level' => $new_tier->level],
                ['id' => $affiliate_id]
            );

            // Notify about tier upgrade
            JEWE_Affiliate_Notifications::send($affiliate_id, 'tier_upgrade', [
                'tier_name' => $new_tier->name,
                'tier_level' => $new_tier->level,
            ]);

            do_action('jewe_affiliate_tier_upgraded', $affiliate_id, $new_tier);

            return $new_tier;
        }

        return false;
    }

    /**
     * Get affiliate stats
     */
    public static function get_stats($affiliate_id, $period = '30days') {
        global $wpdb;

        $commissions_table = $wpdb->prefix . 'jewe_commissions';
        $tracking_table = $wpdb->prefix . 'jewe_tracking';

        // Date range
        switch ($period) {
            case '7days':
                $date_from = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $date_from = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $date_from = date('Y-m-d', strtotime('-90 days'));
                break;
            case 'year':
                $date_from = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
            default:
                $date_from = '2000-01-01';
                break;
        }

        // Earnings
        $earnings = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending,
                SUM(commission_amount) as total,
                COUNT(*) as total_commissions
             FROM $commissions_table
             WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate_id,
            $date_from
        ));

        // Clicks and conversions
        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_clicks,
                SUM(converted) as conversions
             FROM $tracking_table
             WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate_id,
            $date_from
        ));

        // Conversion rate
        $conversion_rate = $tracking->total_clicks > 0
            ? round(($tracking->conversions / $tracking->total_clicks) * 100, 2)
            : 0;

        return [
            'earnings_paid' => floatval($earnings->paid ?? 0),
            'earnings_pending' => floatval($earnings->pending ?? 0),
            'earnings_total' => floatval($earnings->total ?? 0),
            'total_commissions' => intval($earnings->total_commissions ?? 0),
            'total_clicks' => intval($tracking->total_clicks ?? 0),
            'conversions' => intval($tracking->conversions ?? 0),
            'conversion_rate' => $conversion_rate,
            'period' => $period,
        ];
    }

    /**
     * Get affiliate referral URL
     */
    public static function get_referral_url($affiliate_id, $target_url = '') {
        $affiliate = self::get($affiliate_id);
        if (!$affiliate) {
            return '';
        }

        if (empty($target_url)) {
            $target_url = home_url();
        }

        $ref_param = apply_filters('jewe_affiliate_ref_param', 'ref');

        return add_query_arg($ref_param, $affiliate->affiliate_code, $target_url);
    }

    /**
     * Get top affiliates
     */
    public static function get_leaderboard($limit = 10, $period = '30days') {
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
        $commissions_table = $wpdb->prefix . 'jewe_commissions';

        switch ($period) {
            case '7days':
                $date_from = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $date_from = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'month':
                $date_from = date('Y-m-01');
                break;
            case 'all':
            default:
                $date_from = '2000-01-01';
                break;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                a.id,
                a.user_id,
                a.affiliate_code,
                a.tier_level,
                SUM(c.commission_amount) as period_earnings,
                COUNT(c.id) as period_sales
             FROM $affiliates_table a
             LEFT JOIN $commissions_table c ON a.id = c.affiliate_id AND c.created_at >= %s
             WHERE a.status = 'active'
             GROUP BY a.id
             ORDER BY period_earnings DESC
             LIMIT %d",
            $date_from,
            $limit
        ));
    }
}
