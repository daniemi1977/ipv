<?php
/**
 * Gamification Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Gamification {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check and award badges for affiliate
     */
    public static function check_badges($affiliate_id) {
        if (get_option('jewe_affiliate_gamification_enabled', 'yes') !== 'yes') {
            return;
        }

        global $wpdb;
        $badges_table = $wpdb->prefix . 'jewe_badges';
        $earned_table = $wpdb->prefix . 'jewe_affiliate_badges';

        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate) {
            return;
        }

        // Get all badges
        $badges = $wpdb->get_results("SELECT * FROM $badges_table");

        // Get already earned badges
        $earned = $wpdb->get_col($wpdb->prepare(
            "SELECT badge_id FROM $earned_table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        // Get affiliate stats for badge checks
        $stats = self::get_badge_stats($affiliate_id);

        foreach ($badges as $badge) {
            // Skip if already earned
            if (in_array($badge->id, $earned)) {
                continue;
            }

            // Check if requirement met
            $met = self::check_badge_requirement($badge, $stats);

            if ($met) {
                // Award badge
                $wpdb->insert($earned_table, [
                    'affiliate_id' => $affiliate_id,
                    'badge_id' => $badge->id,
                ]);

                // Apply bonus if any
                self::apply_badge_bonus($affiliate_id, $badge);

                // Notify affiliate
                JEWE_Affiliate_Notifications::send($affiliate_id, 'badge_earned', [
                    'badge_name' => $badge->name,
                    'badge_description' => $badge->description,
                    'badge_icon' => $badge->icon,
                ]);

                do_action('jewe_badge_earned', $affiliate_id, $badge);
            }
        }
    }

    /**
     * Get stats for badge checking
     */
    private static function get_badge_stats($affiliate_id) {
        global $wpdb;

        $affiliate = JEWE_Affiliate::get($affiliate_id);
        $mlm_stats = JEWE_Affiliate_MLM::get_stats($affiliate_id);

        // Get sales count
        $commissions_table = $wpdb->prefix . 'jewe_commissions';
        $sales_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $commissions_table WHERE affiliate_id = %d AND commission_type = 'sale'",
            $affiliate_id
        ));

        // Get streak days
        $streak_days = self::calculate_streak($affiliate_id);

        return [
            'sales_count' => intval($sales_count),
            'referrals_count' => intval($affiliate->total_referrals),
            'earnings_total' => floatval($affiliate->lifetime_earnings),
            'team_size' => intval($mlm_stats->team_size ?? 0),
            'streak_days' => $streak_days,
            'clicks' => intval($affiliate->total_clicks),
        ];
    }

    /**
     * Check if badge requirement is met
     */
    private static function check_badge_requirement($badge, $stats) {
        $type = $badge->requirement_type;
        $value = floatval($badge->requirement_value);

        if (!isset($stats[$type])) {
            return false;
        }

        return $stats[$type] >= $value;
    }

    /**
     * Apply badge bonus
     */
    private static function apply_badge_bonus($affiliate_id, $badge) {
        global $wpdb;

        if (empty($badge->bonus_type) || $badge->bonus_value <= 0) {
            return;
        }

        switch ($badge->bonus_type) {
            case 'bonus_commission':
                // Add one-time bonus to balance
                $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
                $wpdb->query($wpdb->prepare(
                    "UPDATE $affiliates_table SET
                        current_balance = current_balance + %f,
                        lifetime_earnings = lifetime_earnings + %f
                     WHERE id = %d",
                    $badge->bonus_value,
                    $badge->bonus_value,
                    $affiliate_id
                ));

                // Create commission record for tracking
                $commissions_table = $wpdb->prefix . 'jewe_commissions';
                $wpdb->insert($commissions_table, [
                    'affiliate_id' => $affiliate_id,
                    'order_id' => 0,
                    'commission_type' => 'badge_bonus',
                    'commission_amount' => $badge->bonus_value,
                    'status' => 'pending',
                ]);
                break;

            case 'commission_boost':
            case 'mlm_boost':
                // Store as user meta for future calculations
                $current_boosts = get_user_meta($affiliate_id, '_jewe_commission_boosts', true);
                if (!is_array($current_boosts)) {
                    $current_boosts = [];
                }
                $current_boosts[] = [
                    'badge_id' => $badge->id,
                    'type' => $badge->bonus_type,
                    'value' => $badge->bonus_value,
                ];
                update_user_meta($affiliate_id, '_jewe_commission_boosts', $current_boosts);
                break;
        }
    }

    /**
     * Calculate streak days
     */
    private static function calculate_streak($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        // Get dates with sales, ordered descending
        $dates = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(created_at) as sale_date
             FROM $table
             WHERE affiliate_id = %d AND commission_type = 'sale'
             ORDER BY sale_date DESC",
            $affiliate_id
        ));

        if (empty($dates)) {
            return 0;
        }

        // Check if today or yesterday has a sale
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($dates[0] !== $today && $dates[0] !== $yesterday) {
            return 0; // Streak broken
        }

        // Count consecutive days
        $streak = 1;
        $current_date = new DateTime($dates[0]);

        for ($i = 1; $i < count($dates); $i++) {
            $expected = clone $current_date;
            $expected->modify('-1 day');

            if ($dates[$i] === $expected->format('Y-m-d')) {
                $streak++;
                $current_date = $expected;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get commission bonus from badges
     */
    public static function get_commission_bonus($affiliate_id) {
        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate) {
            return 0;
        }

        $boosts = get_user_meta($affiliate->user_id, '_jewe_commission_boosts', true);
        if (!is_array($boosts)) {
            return 0;
        }

        $bonus = 0;
        foreach ($boosts as $boost) {
            if ($boost['type'] === 'commission_boost') {
                $bonus += floatval($boost['value']);
            }
        }

        return $bonus;
    }

    /**
     * Get affiliate badges
     */
    public static function get_affiliate_badges($affiliate_id) {
        global $wpdb;
        $badges_table = $wpdb->prefix . 'jewe_badges';
        $earned_table = $wpdb->prefix . 'jewe_affiliate_badges';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, e.earned_at
             FROM $badges_table b
             JOIN $earned_table e ON b.id = e.badge_id
             WHERE e.affiliate_id = %d
             ORDER BY e.earned_at DESC",
            $affiliate_id
        ));
    }

    /**
     * Get all available badges with progress
     */
    public static function get_badges_with_progress($affiliate_id) {
        global $wpdb;
        $badges_table = $wpdb->prefix . 'jewe_badges';
        $earned_table = $wpdb->prefix . 'jewe_affiliate_badges';

        $badges = $wpdb->get_results("SELECT * FROM $badges_table ORDER BY requirement_value ASC");
        $earned = $wpdb->get_col($wpdb->prepare(
            "SELECT badge_id FROM $earned_table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        $stats = self::get_badge_stats($affiliate_id);

        foreach ($badges as &$badge) {
            $badge->earned = in_array($badge->id, $earned);
            $badge->current_value = $stats[$badge->requirement_type] ?? 0;
            $badge->progress = min(100, round(($badge->current_value / $badge->requirement_value) * 100));
        }

        return $badges;
    }

    /**
     * Get tier info
     */
    public static function get_tier($level) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tiers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE level = %d", $level));
    }

    /**
     * Get all tiers
     */
    public static function get_all_tiers() {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tiers';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY level ASC");
    }

    /**
     * Get affiliate tier progress
     */
    public static function get_tier_progress($affiliate_id) {
        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate) {
            return null;
        }

        $current_tier = self::get_tier($affiliate->tier_level);
        $next_tier = self::get_tier($affiliate->tier_level + 1);

        if (!$next_tier) {
            return [
                'current_tier' => $current_tier,
                'next_tier' => null,
                'earnings_progress' => 100,
                'referrals_progress' => 100,
                'is_max_tier' => true,
            ];
        }

        $earnings_needed = $next_tier->min_earnings - $affiliate->lifetime_earnings;
        $referrals_needed = $next_tier->min_referrals - $affiliate->total_referrals;

        $earnings_progress = $next_tier->min_earnings > 0
            ? min(100, round(($affiliate->lifetime_earnings / $next_tier->min_earnings) * 100))
            : 100;

        $referrals_progress = $next_tier->min_referrals > 0
            ? min(100, round(($affiliate->total_referrals / $next_tier->min_referrals) * 100))
            : 100;

        return [
            'current_tier' => $current_tier,
            'next_tier' => $next_tier,
            'earnings_progress' => $earnings_progress,
            'referrals_progress' => $referrals_progress,
            'earnings_needed' => max(0, $earnings_needed),
            'referrals_needed' => max(0, $referrals_needed),
            'is_max_tier' => false,
        ];
    }
}
