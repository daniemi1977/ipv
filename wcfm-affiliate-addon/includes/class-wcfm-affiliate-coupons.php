<?php
/**
 * Coupon Management
 *
 * Gestisce i coupon affiliato per il tracking delle vendite.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Coupons
 */
class WCFM_Affiliate_Coupons {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Track coupon usage
        add_action('woocommerce_applied_coupon', [$this, 'track_coupon_applied']);
        add_action('woocommerce_order_status_completed', [$this, 'update_coupon_stats'], 20);

        // AJAX handlers
        add_action('wp_ajax_wcfm_affiliate_create_coupon', [$this, 'create_coupon_ajax']);
        add_action('wp_ajax_wcfm_affiliate_get_coupons', [$this, 'get_coupons_ajax']);
    }

    /**
     * Create affiliate coupon
     */
    public function create_coupon(int $affiliate_id, array $args = []): int|WP_Error {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            return new WP_Error('invalid_affiliate', __('Affiliato non valido', 'wcfm-affiliate-pro'));
        }

        $defaults = [
            'code' => strtoupper($affiliate->affiliate_code . '-' . wp_generate_password(4, false)),
            'discount_type' => 'percent',
            'amount' => 10,
            'description' => sprintf(__('Coupon affiliato per %s', 'wcfm-affiliate-pro'), $affiliate->affiliate_code),
            'individual_use' => 'yes',
            'usage_limit' => 0,
            'usage_limit_per_user' => 1,
            'expiry_date' => '',
            'minimum_amount' => '',
            'maximum_amount' => '',
            'product_ids' => [],
            'exclude_product_ids' => [],
            'product_categories' => [],
            'exclude_product_categories' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        // Check if code already exists
        if ($this->coupon_code_exists($args['code'])) {
            return new WP_Error('coupon_exists', __('Il codice coupon esiste già', 'wcfm-affiliate-pro'));
        }

        // Create WooCommerce coupon
        $coupon = new WC_Coupon();
        $coupon->set_code($args['code']);
        $coupon->set_discount_type($args['discount_type']);
        $coupon->set_amount($args['amount']);
        $coupon->set_description($args['description']);
        $coupon->set_individual_use($args['individual_use'] === 'yes');
        $coupon->set_usage_limit($args['usage_limit']);
        $coupon->set_usage_limit_per_user($args['usage_limit_per_user']);

        if (!empty($args['expiry_date'])) {
            $coupon->set_date_expires(strtotime($args['expiry_date']));
        }

        if (!empty($args['minimum_amount'])) {
            $coupon->set_minimum_amount($args['minimum_amount']);
        }

        if (!empty($args['maximum_amount'])) {
            $coupon->set_maximum_amount($args['maximum_amount']);
        }

        if (!empty($args['product_ids'])) {
            $coupon->set_product_ids($args['product_ids']);
        }

        if (!empty($args['exclude_product_ids'])) {
            $coupon->set_excluded_product_ids($args['exclude_product_ids']);
        }

        if (!empty($args['product_categories'])) {
            $coupon->set_product_categories($args['product_categories']);
        }

        if (!empty($args['exclude_product_categories'])) {
            $coupon->set_excluded_product_categories($args['exclude_product_categories']);
        }

        $coupon_id = $coupon->save();

        if (!$coupon_id) {
            return new WP_Error('create_failed', __('Impossibile creare il coupon', 'wcfm-affiliate-pro'));
        }

        // Save affiliate coupon relationship
        global $wpdb;

        $wpdb->insert(
            WCFM_Affiliate_DB::$table_coupons,
            [
                'affiliate_id' => $affiliate_id,
                'coupon_id' => $coupon_id,
                'coupon_code' => $args['code'],
                'status' => 'active',
            ],
            ['%d', '%d', '%s', '%s']
        );

        // Save affiliate ID to coupon meta
        update_post_meta($coupon_id, '_wcfm_affiliate_id', $affiliate_id);

        do_action('wcfm_affiliate_coupon_created', $coupon_id, $affiliate_id);

        return $coupon_id;
    }

    /**
     * Check if coupon code exists
     */
    public function coupon_code_exists(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_coupon' AND post_title = %s",
            $code
        ));
    }

    /**
     * Get affiliate coupons
     */
    public function get_affiliate_coupons(int $affiliate_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT ac.*, c.post_status as coupon_status
             FROM " . WCFM_Affiliate_DB::$table_coupons . " ac
             LEFT JOIN {$wpdb->posts} c ON ac.coupon_id = c.ID
             WHERE ac.affiliate_id = %d
             ORDER BY ac.date_created DESC",
            $affiliate_id
        ));
    }

    /**
     * Get coupon by code
     */
    public function get_coupon_by_code(string $code): ?object {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_coupons . " WHERE coupon_code = %s",
            $code
        ));
    }

    /**
     * Get affiliate by coupon
     */
    public function get_affiliate_by_coupon(string $code): ?object {
        $coupon_data = $this->get_coupon_by_code($code);

        if (!$coupon_data) {
            return null;
        }

        return wcfm_affiliate_pro()->affiliates->get_affiliate($coupon_data->affiliate_id);
    }

    /**
     * Track coupon applied
     */
    public function track_coupon_applied(string $coupon_code): void {
        $affiliate_coupon = $this->get_coupon_by_code($coupon_code);

        if (!$affiliate_coupon) {
            return;
        }

        // Store in session for order processing
        WC()->session->set('wcfm_affiliate_coupon', [
            'affiliate_id' => $affiliate_coupon->affiliate_id,
            'coupon_code' => $coupon_code,
        ]);
    }

    /**
     * Update coupon stats after order completed
     */
    public function update_coupon_stats(int $order_id): void {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $coupons = $order->get_coupon_codes();

        foreach ($coupons as $coupon_code) {
            $affiliate_coupon = $this->get_coupon_by_code($coupon_code);

            if (!$affiliate_coupon) {
                continue;
            }

            global $wpdb;

            // Update usage count and revenue
            $wpdb->query($wpdb->prepare(
                "UPDATE " . WCFM_Affiliate_DB::$table_coupons . "
                 SET uses_count = uses_count + 1,
                     revenue_generated = revenue_generated + %f
                 WHERE id = %d",
                $order->get_total(),
                $affiliate_coupon->id
            ));

            // Update commission earned (will be calculated by commission class)
            $commission = $order->get_meta('_wcfm_affiliate_commission');
            if ($commission) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE " . WCFM_Affiliate_DB::$table_coupons . "
                     SET commission_earned = commission_earned + %f
                     WHERE id = %d",
                    $commission,
                    $affiliate_coupon->id
                ));
            }
        }
    }

    /**
     * Deactivate coupon
     */
    public function deactivate_coupon(int $coupon_id): bool {
        global $wpdb;

        // Update coupon status in WooCommerce
        wp_update_post([
            'ID' => $coupon_id,
            'post_status' => 'draft',
        ]);

        // Update in our table
        return (bool) $wpdb->update(
            WCFM_Affiliate_DB::$table_coupons,
            ['status' => 'inactive'],
            ['coupon_id' => $coupon_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Delete coupon
     */
    public function delete_coupon(int $coupon_id): bool {
        global $wpdb;

        // Delete WooCommerce coupon
        wp_delete_post($coupon_id, true);

        // Delete from our table
        return (bool) $wpdb->delete(
            WCFM_Affiliate_DB::$table_coupons,
            ['coupon_id' => $coupon_id],
            ['%d']
        );
    }

    /**
     * Create coupon via AJAX
     */
    public function create_coupon_ajax(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_send_json_error(['message' => __('Non sei un affiliato attivo', 'wcfm-affiliate-pro')]);
        }

        // Check coupon limit
        $existing_coupons = $this->get_affiliate_coupons($affiliate->id);
        $max_coupons = apply_filters('wcfm_affiliate_max_coupons', 5);

        if (count($existing_coupons) >= $max_coupons) {
            wp_send_json_error([
                'message' => sprintf(__('Hai raggiunto il limite massimo di %d coupon', 'wcfm-affiliate-pro'), $max_coupons),
            ]);
        }

        $args = [
            'code' => sanitize_text_field($_POST['code'] ?? ''),
            'discount_type' => sanitize_text_field($_POST['discount_type'] ?? 'percent'),
            'amount' => floatval($_POST['amount'] ?? 10),
        ];

        // Generate code if not provided
        if (empty($args['code'])) {
            $args['code'] = strtoupper($affiliate->affiliate_code . '-' . wp_generate_password(4, false));
        }

        $result = $this->create_coupon($affiliate->id, $args);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Coupon creato con successo', 'wcfm-affiliate-pro'),
            'coupon_id' => $result,
            'coupon_code' => $args['code'],
        ]);
    }

    /**
     * Get coupons via AJAX
     */
    public function get_coupons_ajax(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $coupons = $this->get_affiliate_coupons($affiliate->id);

        $formatted = [];
        foreach ($coupons as $coupon) {
            $wc_coupon = new WC_Coupon($coupon->coupon_id);

            $formatted[] = [
                'id' => $coupon->id,
                'coupon_id' => $coupon->coupon_id,
                'code' => $coupon->coupon_code,
                'discount_type' => $wc_coupon->get_discount_type(),
                'amount' => $wc_coupon->get_amount(),
                'usage_count' => $coupon->uses_count,
                'revenue' => wc_price($coupon->revenue_generated),
                'commission' => wc_price($coupon->commission_earned),
                'status' => $coupon->status,
                'expiry_date' => $wc_coupon->get_date_expires() ? $wc_coupon->get_date_expires()->date('Y-m-d') : '',
            ];
        }

        wp_send_json_success($formatted);
    }

    /**
     * Get coupon stats
     */
    public function get_coupon_stats(int $affiliate_id): array {
        global $wpdb;

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_coupons,
                    SUM(uses_count) as total_uses,
                    SUM(revenue_generated) as total_revenue,
                    SUM(commission_earned) as total_commission
             FROM " . WCFM_Affiliate_DB::$table_coupons . "
             WHERE affiliate_id = %d AND status = 'active'",
            $affiliate_id
        ));

        return [
            'total_coupons' => (int) ($stats->total_coupons ?? 0),
            'total_uses' => (int) ($stats->total_uses ?? 0),
            'total_revenue' => (float) ($stats->total_revenue ?? 0),
            'total_commission' => (float) ($stats->total_commission ?? 0),
        ];
    }
}
