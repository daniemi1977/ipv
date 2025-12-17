<?php
/**
 * Commission Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Commission {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', [$this, 'process_order_commission']);
        add_action('woocommerce_order_status_processing', [$this, 'process_order_commission']);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_refund']);
    }

    /**
     * Process commission for order
     */
    public function process_order_commission($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if commission already processed
        if ($order->get_meta('_jewe_commission_processed')) {
            return;
        }

        // Get affiliate from cookie or order meta
        $affiliate_id = $this->get_affiliate_for_order($order);
        if (!$affiliate_id) {
            return;
        }

        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Get commission rate
        $tier = $this->get_tier_commission_rate($affiliate->tier_level);
        $commission_rate = $tier ? $tier->commission_rate : get_option('jewe_affiliate_default_commission', 10);

        // Calculate commission
        $order_total = floatval($order->get_total()) - floatval($order->get_total_tax());
        $commission_amount = ($order_total * $commission_rate) / 100;

        // Create commission record
        $commission_id = $this->create_commission([
            'affiliate_id' => $affiliate_id,
            'order_id' => $order_id,
            'commission_type' => 'sale',
            'commission_rate' => $commission_rate,
            'commission_amount' => $commission_amount,
            'order_total' => $order_total,
            'mlm_level' => 1,
        ]);

        if ($commission_id) {
            // Update affiliate stats
            $this->update_affiliate_earnings($affiliate_id, $commission_amount);

            // Mark order as processed
            $order->update_meta_data('_jewe_commission_processed', true);
            $order->update_meta_data('_jewe_affiliate_id', $affiliate_id);
            $order->update_meta_data('_jewe_commission_id', $commission_id);
            $order->save();

            // Process MLM commissions
            if (get_option('jewe_affiliate_mlm_enabled', 'yes') === 'yes') {
                JEWE_Affiliate_MLM::process_mlm_commissions($affiliate_id, $order_id, $order_total);
            }

            // Check for tier upgrade
            JEWE_Affiliate::update_tier($affiliate_id);

            // Check for badges
            JEWE_Affiliate_Gamification::check_badges($affiliate_id);

            // Send notification
            JEWE_Affiliate_Notifications::send($affiliate_id, 'new_commission', [
                'order_id' => $order_id,
                'amount' => $commission_amount,
            ]);

            // Mark tracking as converted
            $this->mark_tracking_converted($affiliate_id, $order_id);

            do_action('jewe_commission_created', $commission_id, $affiliate_id, $order_id);
        }
    }

    /**
     * Get affiliate for order
     */
    private function get_affiliate_for_order($order) {
        // Check order meta first
        $affiliate_id = $order->get_meta('_jewe_affiliate_id');
        if ($affiliate_id) {
            return $affiliate_id;
        }

        // Check cookie
        $ref_cookie = isset($_COOKIE['jewe_affiliate_ref']) ? sanitize_text_field($_COOKIE['jewe_affiliate_ref']) : '';
        if ($ref_cookie) {
            $affiliate = JEWE_Affiliate::get_by_code($ref_cookie);
            if ($affiliate) {
                return $affiliate->id;
            }
        }

        // Check lifetime commission (customer linked to affiliate)
        if (get_option('jewe_affiliate_lifetime_commissions', 'yes') === 'yes') {
            $customer_id = $order->get_customer_id();
            if ($customer_id) {
                $linked_affiliate = get_user_meta($customer_id, '_jewe_linked_affiliate', true);
                if ($linked_affiliate) {
                    return $linked_affiliate;
                }
            }
        }

        return false;
    }

    /**
     * Get tier commission rate
     */
    private function get_tier_commission_rate($tier_level) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tiers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE level = %d", $tier_level));
    }

    /**
     * Create commission record
     */
    public function create_commission($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $result = $wpdb->insert($table, [
            'affiliate_id' => $data['affiliate_id'],
            'order_id' => $data['order_id'],
            'product_id' => $data['product_id'] ?? 0,
            'commission_type' => $data['commission_type'],
            'commission_rate' => $data['commission_rate'],
            'commission_amount' => $data['commission_amount'],
            'order_total' => $data['order_total'],
            'mlm_level' => $data['mlm_level'] ?? 1,
            'status' => 'pending',
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update affiliate earnings
     */
    private function update_affiliate_earnings($affiliate_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_affiliates';

        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET
                lifetime_earnings = lifetime_earnings + %f,
                current_balance = current_balance + %f
             WHERE id = %d",
            $amount,
            $amount,
            $affiliate_id
        ));
    }

    /**
     * Mark tracking as converted
     */
    private function mark_tracking_converted($affiliate_id, $order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $visitor_hash = isset($_COOKIE['jewe_visitor_hash']) ? sanitize_text_field($_COOKIE['jewe_visitor_hash']) : '';

        if ($visitor_hash) {
            $wpdb->update(
                $table,
                [
                    'converted' => 1,
                    'conversion_order_id' => $order_id,
                ],
                [
                    'affiliate_id' => $affiliate_id,
                    'visitor_hash' => $visitor_hash,
                    'converted' => 0,
                ]
            );
        }
    }

    /**
     * Handle refund
     */
    public function handle_refund($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d AND status = 'pending'",
            $order_id
        ));

        if ($commission) {
            // Update commission status
            $wpdb->update(
                $table,
                ['status' => 'refunded'],
                ['id' => $commission->id]
            );

            // Deduct from affiliate balance
            $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
            $wpdb->query($wpdb->prepare(
                "UPDATE $affiliates_table SET
                    current_balance = current_balance - %f
                 WHERE id = %d AND current_balance >= %f",
                $commission->commission_amount,
                $commission->affiliate_id,
                $commission->commission_amount
            ));

            // Notify affiliate
            JEWE_Affiliate_Notifications::send($commission->affiliate_id, 'commission_refunded', [
                'order_id' => $order_id,
                'amount' => $commission->commission_amount,
            ]);
        }
    }

    /**
     * Approve commission (pay)
     */
    public static function approve_commission($commission_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        return $wpdb->update(
            $table,
            [
                'status' => 'paid',
                'paid_at' => current_time('mysql'),
            ],
            ['id' => $commission_id, 'status' => 'pending']
        );
    }

    /**
     * Get commissions summary
     */
    public static function get_summary($affiliate_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = $affiliate_id ? $wpdb->prepare("WHERE affiliate_id = %d", $affiliate_id) : "";

        return $wpdb->get_row(
            "SELECT
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount,
                SUM(commission_amount) as total_amount,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(*) as total_count
             FROM $table $where"
        );
    }

    /**
     * Calculate tiered commission
     */
    public static function calculate_tiered_commission($affiliate_id, $order_total, $product_id = 0) {
        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate) {
            return 0;
        }

        // Get tier rates
        global $wpdb;
        $tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jewe_tiers WHERE level = %d",
            $affiliate->tier_level
        ));

        $base_rate = $tier ? $tier->commission_rate : get_option('jewe_affiliate_default_commission', 10);

        // Check for product-specific rate
        if ($product_id) {
            $product_rate = get_post_meta($product_id, '_jewe_affiliate_commission_rate', true);
            if ($product_rate !== '') {
                $base_rate = floatval($product_rate);
            }
        }

        // Apply any badge bonuses
        $bonus = JEWE_Affiliate_Gamification::get_commission_bonus($affiliate_id);
        $final_rate = $base_rate + $bonus;

        return ($order_total * $final_rate) / 100;
    }
}
