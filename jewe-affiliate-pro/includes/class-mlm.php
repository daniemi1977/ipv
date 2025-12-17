<?php
/**
 * Multi-Level Marketing (MLM) Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_MLM {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add affiliate to MLM tree
     */
    public static function add_to_tree($affiliate_id, $parent_affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';

        // Get parent's tree info
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE affiliate_id = %d",
            $parent_affiliate_id
        ));

        $level = 1;
        $path = "/$affiliate_id";

        if ($parent) {
            $level = $parent->level + 1;
            $path = $parent->path . "/$affiliate_id";
        }

        // Insert new MLM entry
        $wpdb->insert($table, [
            'affiliate_id' => $affiliate_id,
            'parent_id' => $parent_affiliate_id,
            'level' => $level,
            'path' => $path,
        ]);

        // Update parent's direct referrals
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET direct_referrals = direct_referrals + 1 WHERE affiliate_id = %d",
            $parent_affiliate_id
        ));

        // Update team size for all ancestors
        self::update_ancestors_team_size($affiliate_id);

        return true;
    }

    /**
     * Update team size for all ancestors
     */
    private static function update_ancestors_team_size($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';

        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$current || !$current->parent_id) {
            return;
        }

        // Get all ancestor IDs from path
        $path_ids = array_filter(explode('/', $current->path));

        foreach ($path_ids as $ancestor_id) {
            if ($ancestor_id != $affiliate_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET team_size = team_size + 1 WHERE affiliate_id = %d",
                    $ancestor_id
                ));
            }
        }
    }

    /**
     * Process MLM commissions for an order
     */
    public static function process_mlm_commissions($affiliate_id, $order_id, $order_total) {
        global $wpdb;

        $max_levels = intval(get_option('jewe_affiliate_mlm_levels', 3));
        $mlm_table = $wpdb->prefix . 'jewe_mlm_tree';
        $tiers_table = $wpdb->prefix . 'jewe_tiers';

        // Get affiliate's MLM entry
        $mlm_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $mlm_table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$mlm_entry || !$mlm_entry->parent_id) {
            return;
        }

        // Get ancestor chain (up to max_levels)
        $path_ids = array_filter(explode('/', $mlm_entry->path));
        $path_ids = array_reverse($path_ids);
        array_shift($path_ids); // Remove self

        $level = 1;
        foreach ($path_ids as $ancestor_id) {
            if ($level > $max_levels) {
                break;
            }

            $ancestor = JEWE_Affiliate::get($ancestor_id);
            if (!$ancestor || $ancestor->status !== 'active') {
                $level++;
                continue;
            }

            // Get tier commission rate for this level
            $tier = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tiers_table WHERE level = %d",
                $ancestor->tier_level
            ));

            $commission_rate = 0;
            if ($tier) {
                switch ($level) {
                    case 1:
                        $commission_rate = $tier->mlm_commission_l1;
                        break;
                    case 2:
                        $commission_rate = $tier->mlm_commission_l2;
                        break;
                    case 3:
                        $commission_rate = $tier->mlm_commission_l3;
                        break;
                }
            }

            if ($commission_rate > 0) {
                $commission_amount = ($order_total * $commission_rate) / 100;

                // Create MLM commission
                JEWE_Affiliate_Commission::instance()->create_commission([
                    'affiliate_id' => $ancestor_id,
                    'order_id' => $order_id,
                    'commission_type' => 'mlm_level_' . $level,
                    'commission_rate' => $commission_rate,
                    'commission_amount' => $commission_amount,
                    'order_total' => $order_total,
                    'mlm_level' => $level + 1, // +1 because level 1 is direct
                ]);

                // Update earnings
                $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
                $wpdb->query($wpdb->prepare(
                    "UPDATE $affiliates_table SET
                        lifetime_earnings = lifetime_earnings + %f,
                        current_balance = current_balance + %f
                     WHERE id = %d",
                    $commission_amount,
                    $commission_amount,
                    $ancestor_id
                ));

                // Update team earnings
                $wpdb->query($wpdb->prepare(
                    "UPDATE $mlm_table SET team_earnings = team_earnings + %f WHERE affiliate_id = %d",
                    $commission_amount,
                    $ancestor_id
                ));

                // Notify
                JEWE_Affiliate_Notifications::send($ancestor_id, 'mlm_commission', [
                    'level' => $level,
                    'amount' => $commission_amount,
                    'from_affiliate' => $affiliate_id,
                ]);
            }

            $level++;
        }
    }

    /**
     * Get downline (team members)
     */
    public static function get_downline($affiliate_id, $max_depth = 3) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';

        $downline = [];

        // Get direct referrals
        $direct = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, a.user_id, a.affiliate_code, a.lifetime_earnings, a.status
             FROM $table m
             JOIN $affiliates_table a ON m.affiliate_id = a.id
             WHERE m.parent_id = %d
             ORDER BY m.created_at DESC",
            $affiliate_id
        ));

        foreach ($direct as $member) {
            $member->level_depth = 1;
            $downline[] = $member;

            // Get sub-referrals recursively
            if ($max_depth > 1) {
                $sub_downline = self::get_downline_recursive($member->affiliate_id, 2, $max_depth);
                $downline = array_merge($downline, $sub_downline);
            }
        }

        return $downline;
    }

    /**
     * Recursive helper for getting downline
     */
    private static function get_downline_recursive($affiliate_id, $current_depth, $max_depth) {
        if ($current_depth > $max_depth) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';

        $downline = [];

        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, a.user_id, a.affiliate_code, a.lifetime_earnings, a.status
             FROM $table m
             JOIN $affiliates_table a ON m.affiliate_id = a.id
             WHERE m.parent_id = %d",
            $affiliate_id
        ));

        foreach ($members as $member) {
            $member->level_depth = $current_depth;
            $downline[] = $member;

            $sub_downline = self::get_downline_recursive($member->affiliate_id, $current_depth + 1, $max_depth);
            $downline = array_merge($downline, $sub_downline);
        }

        return $downline;
    }

    /**
     * Get upline (ancestors)
     */
    public static function get_upline($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$entry || !$entry->path) {
            return [];
        }

        $path_ids = array_filter(explode('/', $entry->path));
        array_pop($path_ids); // Remove self

        if (empty($path_ids)) {
            return [];
        }

        $ids_string = implode(',', array_map('intval', $path_ids));

        return $wpdb->get_results(
            "SELECT m.*, a.user_id, a.affiliate_code, a.tier_level
             FROM $table m
             JOIN $affiliates_table a ON m.affiliate_id = a.id
             WHERE m.affiliate_id IN ($ids_string)
             ORDER BY FIELD(m.affiliate_id, $ids_string)"
        );
    }

    /**
     * Get MLM stats
     */
    public static function get_stats($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT
                direct_referrals,
                team_size,
                team_earnings,
                level
             FROM $table
             WHERE affiliate_id = %d",
            $affiliate_id
        ));
    }

    /**
     * Get team performance
     */
    public static function get_team_performance($affiliate_id, $period = '30days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_mlm_tree';
        $commissions_table = $wpdb->prefix . 'jewe_commissions';

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT path FROM $table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$entry) {
            return null;
        }

        // Get all team member IDs
        $team_members = $wpdb->get_col($wpdb->prepare(
            "SELECT affiliate_id FROM $table WHERE path LIKE %s AND affiliate_id != %d",
            $entry->path . '/%',
            $affiliate_id
        ));

        if (empty($team_members)) {
            return [
                'team_size' => 0,
                'team_sales' => 0,
                'team_earnings' => 0,
            ];
        }

        $date_from = date('Y-m-d', strtotime('-30 days'));
        if ($period === '7days') {
            $date_from = date('Y-m-d', strtotime('-7 days'));
        } elseif ($period === '90days') {
            $date_from = date('Y-m-d', strtotime('-90 days'));
        }

        $ids_string = implode(',', array_map('intval', $team_members));

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(DISTINCT affiliate_id) as active_members,
                COUNT(*) as team_sales,
                SUM(commission_amount) as team_earnings
             FROM $commissions_table
             WHERE affiliate_id IN ($ids_string) AND created_at >= '$date_from'"
        );

        return [
            'team_size' => count($team_members),
            'active_members' => intval($stats->active_members ?? 0),
            'team_sales' => intval($stats->team_sales ?? 0),
            'team_earnings' => floatval($stats->team_earnings ?? 0),
        ];
    }
}
