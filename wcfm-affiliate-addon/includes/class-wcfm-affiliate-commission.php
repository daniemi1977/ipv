<?php
/**
 * Commission calculation engine
 *
 * Gestisce il calcolo, tracking e gestione delle commissioni affiliate.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Commission
 */
class WCFM_Affiliate_Commission {

    /**
     * Commission settings
     */
    private array $settings;

    /**
     * MLM settings
     */
    private array $mlm_settings;

    /**
     * Constructor
     */
    public function __construct() {
        // Usa nomi opzioni unici
        $this->settings = get_option('wcfm_aff_pro_commission', []);
        $this->mlm_settings = get_option('wcfm_aff_pro_mlm', []);

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Admin AJAX - usa nomi azione unici
        add_action('wp_ajax_wcfm_aff_pro_approve_commission', [$this, 'handle_approve_commission']);
        add_action('wp_ajax_wcfm_aff_pro_reject_commission', [$this, 'handle_reject_commission']);
        add_action('wp_ajax_wcfm_aff_pro_bulk_commission_action', [$this, 'handle_bulk_action']);

        // Auto-approval cron - usa nomi unici
        add_action('wcfm_aff_pro_auto_approve_commissions', [$this, 'auto_approve_pending']);
    }

    /**
     * Process order commission
     */
    public function process_order(int $order_id): void {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Check if already processed
        if ($order->get_meta('_wcfm_affiliate_processed')) {
            return;
        }

        // Get affiliate from visit/cookie
        $affiliate_id = $this->get_order_affiliate($order);

        if (!$affiliate_id) {
            return;
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Check self-referral - usa nome opzione unico
        $settings = get_option('wcfm_aff_pro_general', []);
        if (($settings['allow_self_referral'] ?? 'no') === 'no') {
            $customer_id = $order->get_customer_id();
            if ($customer_id && $customer_id == $affiliate->user_id) {
                return;
            }
        }

        // Calculate commission
        $commission_data = $this->calculate_order_commission($order, $affiliate);

        if (empty($commission_data) || $commission_data['total'] <= 0) {
            return;
        }

        // Create referral
        $referral_id = $this->create_referral($affiliate_id, $order, $commission_data);

        if (!$referral_id) {
            return;
        }

        // Create commission records
        foreach ($commission_data['items'] as $item) {
            $this->create_commission($referral_id, $affiliate_id, $order->get_id(), $item);
        }

        // Process MLM commissions if enabled
        if (($this->mlm_settings['enable'] ?? 'no') === 'yes') {
            $this->process_mlm_commissions($referral_id, $affiliate_id, $order, $commission_data['total']);
        }

        // Update affiliate stats
        wcfm_affiliate_pro()->affiliates->update_earnings($affiliate_id, $commission_data['total']);
        wcfm_affiliate_pro()->affiliates->increment_referral_count($affiliate_id);

        // Mark visit as converted
        $this->mark_visit_converted($order);

        // Mark order as processed
        $order->update_meta_data('_wcfm_affiliate_processed', 'yes');
        $order->update_meta_data('_wcfm_affiliate_id', $affiliate_id);
        $order->update_meta_data('_wcfm_affiliate_referral_id', $referral_id);
        $order->update_meta_data('_wcfm_affiliate_commission', $commission_data['total']);
        $order->save();

        // Trigger notification
        do_action('wcfm_affiliate_new_referral', $referral_id, $affiliate_id, $order_id);
    }

    /**
     * Get affiliate for order
     */
    private function get_order_affiliate(\WC_Order $order): int {
        // Check order meta first
        $affiliate_id = $order->get_meta('_wcfm_affiliate_id');

        if ($affiliate_id) {
            return (int) $affiliate_id;
        }

        // Check visit by customer
        $customer_id = $order->get_customer_id();
        $customer_ip = $order->get_customer_ip_address();

        global $wpdb;

        // Find active visit
        $visit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE (customer_id = %d OR ip_address = %s)
             AND converted = 0
             AND expires_at > NOW()
             ORDER BY date_created DESC
             LIMIT 1",
            $customer_id,
            $customer_ip
        ));

        if ($visit) {
            return (int) $visit->affiliate_id;
        }

        // Check coupon
        $coupons = $order->get_coupon_codes();
        foreach ($coupons as $coupon_code) {
            $affiliate_coupon = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WCFM_Affiliate_DB::$table_coupons . " WHERE coupon_code = %s AND status = 'active'",
                $coupon_code
            ));

            if ($affiliate_coupon) {
                return (int) $affiliate_coupon->affiliate_id;
            }
        }

        return 0;
    }

    /**
     * Calculate order commission
     */
    public function calculate_order_commission(\WC_Order $order, object $affiliate): array {
        $items = [];
        $total = 0;

        $exclude_shipping = ($this->settings['exclude_shipping'] ?? 'yes') === 'yes';
        $exclude_tax = ($this->settings['exclude_tax'] ?? 'yes') === 'yes';
        $exclude_discounts = ($this->settings['exclude_discounts'] ?? 'yes') === 'yes';

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if (!$product) {
                continue;
            }

            // Get base amount
            $base_amount = $item->get_subtotal();

            if (!$exclude_tax) {
                $base_amount += $item->get_subtotal_tax();
            }

            if (!$exclude_discounts) {
                $discount = $item->get_subtotal() - $item->get_total();
                $base_amount -= $discount;
            }

            // Get commission rate and type
            $commission_info = $this->get_commission_rate($affiliate, $product, $order);

            // Calculate commission
            $commission_amount = $this->calculate_commission_amount(
                $base_amount,
                $commission_info['rate'],
                $commission_info['type']
            );

            if ($commission_amount > 0) {
                $vendor_id = $this->get_product_vendor($product->get_id());

                $items[] = [
                    'order_item_id' => $item_id,
                    'product_id' => $product->get_id(),
                    'vendor_id' => $vendor_id,
                    'type' => $commission_info['type'],
                    'rate' => $commission_info['rate'],
                    'base_amount' => $base_amount,
                    'commission_amount' => $commission_amount,
                ];

                $total += $commission_amount;
            }
        }

        // Add shipping commission if not excluded
        if (!$exclude_shipping) {
            $shipping_total = $order->get_shipping_total();
            if (!$exclude_tax) {
                $shipping_total += $order->get_shipping_tax();
            }

            if ($shipping_total > 0) {
                $commission_info = $this->get_commission_rate($affiliate, null, $order);
                $commission_amount = $this->calculate_commission_amount(
                    $shipping_total,
                    $commission_info['rate'],
                    $commission_info['type']
                );

                if ($commission_amount > 0) {
                    $items[] = [
                        'order_item_id' => 0,
                        'product_id' => 0,
                        'vendor_id' => 0,
                        'type' => $commission_info['type'],
                        'rate' => $commission_info['rate'],
                        'base_amount' => $shipping_total,
                        'commission_amount' => $commission_amount,
                        'is_shipping' => true,
                    ];

                    $total += $commission_amount;
                }
            }
        }

        return [
            'items' => $items,
            'total' => round($total, 2),
            'currency' => $order->get_currency(),
        ];
    }

    /**
     * Get commission rate for affiliate/product combination
     */
    public function get_commission_rate(object $affiliate, ?\WC_Product $product, \WC_Order $order): array {
        // Check affiliate custom rate first
        if ($affiliate->custom_commission_rate !== null) {
            return [
                'type' => $affiliate->custom_commission_type ?? $this->settings['type'] ?? 'percentage',
                'rate' => (float) $affiliate->custom_commission_rate,
            ];
        }

        // Check per-product commission
        if ($product && ($this->settings['per_product'] ?? 'no') === 'yes') {
            $product_rate = $product->get_meta('_wcfm_affiliate_commission_rate');
            $product_type = $product->get_meta('_wcfm_affiliate_commission_type');

            if ($product_rate !== '') {
                return [
                    'type' => $product_type ?: $this->settings['type'] ?? 'percentage',
                    'rate' => (float) $product_rate,
                ];
            }
        }

        // Check per-category commission
        if ($product && ($this->settings['per_category'] ?? 'no') === 'yes') {
            $categories = $product->get_category_ids();

            foreach ($categories as $cat_id) {
                $cat_rate = get_term_meta($cat_id, '_wcfm_affiliate_commission_rate', true);
                $cat_type = get_term_meta($cat_id, '_wcfm_affiliate_commission_type', true);

                if ($cat_rate !== '') {
                    return [
                        'type' => $cat_type ?: $this->settings['type'] ?? 'percentage',
                        'rate' => (float) $cat_rate,
                    ];
                }
            }
        }

        // Check per-vendor commission
        if ($product && ($this->settings['per_vendor'] ?? 'no') === 'yes') {
            $vendor_id = $this->get_product_vendor($product->get_id());

            if ($vendor_id) {
                $vendor_rate = get_user_meta($vendor_id, '_wcfm_affiliate_commission_rate', true);
                $vendor_type = get_user_meta($vendor_id, '_wcfm_affiliate_commission_type', true);

                if ($vendor_rate !== '') {
                    return [
                        'type' => $vendor_type ?: $this->settings['type'] ?? 'percentage',
                        'rate' => (float) $vendor_rate,
                    ];
                }
            }
        }

        // Check tier rate
        if ($affiliate->tier_id) {
            global $wpdb;
            $tier = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WCFM_Affiliate_DB::$table_tiers . " WHERE id = %d",
                $affiliate->tier_id
            ));

            if ($tier) {
                return [
                    'type' => $tier->commission_type,
                    'rate' => (float) $tier->commission_rate,
                ];
            }
        }

        // Use tiered rate based on performance
        if (($this->settings['type'] ?? 'percentage') === 'tiered') {
            return $this->get_tiered_rate($affiliate);
        }

        // Default global rate
        return [
            'type' => $this->settings['type'] ?? 'percentage',
            'rate' => (float) ($this->settings['rate'] ?? 10),
        ];
    }

    /**
     * Get tiered commission rate based on affiliate performance
     */
    private function get_tiered_rate(object $affiliate): array {
        global $wpdb;

        $tiers = $wpdb->get_results(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_tiers . "
             WHERE status = 'active'
             ORDER BY minimum_referrals DESC, minimum_earnings DESC"
        );

        foreach ($tiers as $tier) {
            if ($affiliate->referrals_count >= $tier->minimum_referrals &&
                $affiliate->earnings_total >= $tier->minimum_earnings) {
                return [
                    'type' => $tier->commission_type,
                    'rate' => (float) $tier->commission_rate,
                ];
            }
        }

        // Default rate
        return [
            'type' => 'percentage',
            'rate' => (float) ($this->settings['rate'] ?? 10),
        ];
    }

    /**
     * Calculate commission amount
     */
    public function calculate_commission_amount(float $base_amount, float $rate, string $type): float {
        if ($type === 'percentage') {
            return round(($base_amount * $rate) / 100, 2);
        } elseif ($type === 'flat') {
            return $rate;
        }

        return 0;
    }

    /**
     * Create referral record
     */
    private function create_referral(int $affiliate_id, \WC_Order $order, array $commission_data): int {
        global $wpdb;

        $status = 'pending';
        $approval_method = $this->settings['approval_method'] ?? 'manual';

        if ($approval_method === 'auto') {
            $status = 'approved';
        }

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_referrals,
            [
                'affiliate_id' => $affiliate_id,
                'order_id' => $order->get_id(),
                'customer_id' => $order->get_customer_id(),
                'status' => $status,
                'amount' => $commission_data['total'],
                'currency' => $commission_data['currency'],
                'commission_type' => 'sale',
                'context' => 'woocommerce',
                'ip_address' => $order->get_customer_ip_address(),
                'user_agent' => $order->get_customer_user_agent(),
            ],
            ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Create commission record
     */
    private function create_commission(int $referral_id, int $affiliate_id, int $order_id, array $item): int {
        global $wpdb;

        $status = 'pending';
        $approval_method = $this->settings['approval_method'] ?? 'manual';

        if ($approval_method === 'auto') {
            $status = 'approved';
        }

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_commissions,
            [
                'referral_id' => $referral_id,
                'affiliate_id' => $affiliate_id,
                'order_id' => $order_id,
                'order_item_id' => $item['order_item_id'] ?? 0,
                'product_id' => $item['product_id'] ?? 0,
                'vendor_id' => $item['vendor_id'] ?? 0,
                'status' => $status,
                'type' => $item['type'],
                'rate' => $item['rate'],
                'base_amount' => $item['base_amount'],
                'commission_amount' => $item['commission_amount'],
                'mlm_level' => 1,
            ],
            ['%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%f', '%d']
        );

        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Process MLM commissions
     */
    private function process_mlm_commissions(int $referral_id, int $affiliate_id, \WC_Order $order, float $base_commission): void {
        global $wpdb;

        $max_levels = (int) ($this->mlm_settings['levels'] ?? 3);
        $level_rates = $this->mlm_settings['level_rates'] ?? [10, 5, 2];

        // Get affiliate's upline
        $mlm_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_mlm . " WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$mlm_data || !$mlm_data->path) {
            return;
        }

        $upline_ids = array_reverse(explode('/', $mlm_data->path));
        $level = 2; // Start from level 2 (level 1 is the direct affiliate)

        foreach ($upline_ids as $upline_id) {
            if ($level > $max_levels || !isset($level_rates[$level - 1])) {
                break;
            }

            $upline = wcfm_affiliate_pro()->affiliates->get_affiliate((int) $upline_id);

            if (!$upline || $upline->status !== 'active') {
                $level++;
                continue;
            }

            $rate = (float) $level_rates[$level - 1];
            $commission_amount = round(($base_commission * $rate) / 100, 2);

            if ($commission_amount <= 0) {
                $level++;
                continue;
            }

            // Create MLM commission
            $wpdb->insert(
                WCFM_Affiliate_DB::$table_commissions,
                [
                    'referral_id' => $referral_id,
                    'affiliate_id' => $upline->id,
                    'order_id' => $order->get_id(),
                    'status' => 'pending',
                    'type' => 'mlm',
                    'rate' => $rate,
                    'base_amount' => $base_commission,
                    'commission_amount' => $commission_amount,
                    'mlm_level' => $level,
                    'parent_commission_id' => null,
                ],
                ['%d', '%d', '%d', '%s', '%s', '%f', '%f', '%f', '%d', '%d']
            );

            // Update upline earnings
            wcfm_affiliate_pro()->affiliates->update_earnings($upline->id, $commission_amount);

            // Update team earnings
            $wpdb->query($wpdb->prepare(
                "UPDATE " . WCFM_Affiliate_DB::$table_mlm . "
                 SET team_earnings = team_earnings + %f
                 WHERE affiliate_id = %d",
                $commission_amount,
                $upline->id
            ));

            $level++;
        }
    }

    /**
     * Mark visit as converted
     */
    private function mark_visit_converted(\WC_Order $order): void {
        global $wpdb;

        $customer_id = $order->get_customer_id();
        $customer_ip = $order->get_customer_ip_address();

        $wpdb->update(
            WCFM_Affiliate_DB::$table_visits,
            [
                'converted' => 1,
                'conversion_date' => current_time('mysql'),
                'order_id' => $order->get_id(),
            ],
            [
                'customer_id' => $customer_id,
                'ip_address' => $customer_ip,
                'converted' => 0,
            ],
            ['%d', '%s', '%d'],
            ['%d', '%s', '%d']
        );
    }

    /**
     * Get product vendor
     */
    private function get_product_vendor(int $product_id): int {
        // WCFM
        if (function_exists('wcfm_get_vendor_id_by_post')) {
            return (int) wcfm_get_vendor_id_by_post($product_id);
        }

        // Dokan
        $vendor_id = get_post_field('post_author', $product_id);

        return $vendor_id ? (int) $vendor_id : 0;
    }

    /**
     * Handle order refund
     */
    public function handle_refund(int $order_id): void {
        global $wpdb;

        // Get referral
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_referrals . " WHERE order_id = %d",
            $order_id
        ));

        if (!$referral) {
            return;
        }

        // Update referral status
        $wpdb->update(
            WCFM_Affiliate_DB::$table_referrals,
            ['status' => 'rejected'],
            ['id' => $referral->id],
            ['%s'],
            ['%d']
        );

        // Update commissions
        $wpdb->update(
            WCFM_Affiliate_DB::$table_commissions,
            ['status' => 'refunded'],
            ['referral_id' => $referral->id],
            ['%s'],
            ['%d']
        );

        // Get all commissions for this referral
        $commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_commissions . " WHERE referral_id = %d",
            $referral->id
        ));

        // Subtract from affiliate earnings
        foreach ($commissions as $commission) {
            wcfm_affiliate_pro()->affiliates->update_earnings($commission->affiliate_id, $commission->commission_amount, 'subtract');
        }

        do_action('wcfm_affiliate_referral_refunded', $referral->id, $order_id);
    }

    /**
     * Auto-approve pending commissions
     */
    public function auto_approve_pending(): void {
        $approval_method = $this->settings['approval_method'] ?? 'manual';

        if ($approval_method !== 'delay') {
            return;
        }

        $delay_days = (int) ($this->settings['approval_delay'] ?? 30);

        global $wpdb;

        // Get pending commissions older than delay
        $commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_commissions . "
             WHERE status = 'pending'
             AND DATE(date_created) <= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $delay_days
        ));

        foreach ($commissions as $commission) {
            $this->approve_commission($commission->id);
        }

        // Update referrals
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_referrals . "
             WHERE status = 'pending'
             AND DATE(date_created) <= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $delay_days
        ));

        foreach ($referrals as $referral) {
            $wpdb->update(
                WCFM_Affiliate_DB::$table_referrals,
                [
                    'status' => 'approved',
                    'date_modified' => current_time('mysql'),
                ],
                ['id' => $referral->id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }

    /**
     * Approve commission
     */
    public function approve_commission(int $commission_id): bool {
        global $wpdb;

        $result = $wpdb->update(
            WCFM_Affiliate_DB::$table_commissions,
            [
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_at' => current_time('mysql'),
            ],
            ['id' => $commission_id],
            ['%s', '%d', '%s'],
            ['%d']
        );

        if ($result) {
            do_action('wcfm_affiliate_commission_approved', $commission_id);
        }

        return (bool) $result;
    }

    /**
     * Reject commission
     */
    public function reject_commission(int $commission_id, string $notes = ''): bool {
        global $wpdb;

        $commission = $this->get_commission($commission_id);

        if (!$commission) {
            return false;
        }

        $result = $wpdb->update(
            WCFM_Affiliate_DB::$table_commissions,
            [
                'status' => 'rejected',
                'notes' => $notes,
            ],
            ['id' => $commission_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result) {
            // Subtract from affiliate earnings
            wcfm_affiliate_pro()->affiliates->update_earnings($commission->affiliate_id, $commission->commission_amount, 'subtract');

            do_action('wcfm_affiliate_commission_rejected', $commission_id);
        }

        return (bool) $result;
    }

    /**
     * Get commission
     */
    public function get_commission(int $commission_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_commissions . " WHERE id = %d",
            $commission_id
        ));
    }

    /**
     * Get commissions
     */
    public function get_commissions(array $args = []): array {
        global $wpdb;

        $defaults = [
            'affiliate_id' => 0,
            'order_id' => 0,
            'vendor_id' => 0,
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if (!empty($args['affiliate_id'])) {
            $where[] = 'c.affiliate_id = %d';
            $values[] = $args['affiliate_id'];
        }

        if (!empty($args['order_id'])) {
            $where[] = 'c.order_id = %d';
            $values[] = $args['order_id'];
        }

        if (!empty($args['vendor_id'])) {
            $where[] = 'c.vendor_id = %d';
            $values[] = $args['vendor_id'];
        }

        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "c.status IN ($placeholders)";
                $values = array_merge($values, $args['status']);
            } else {
                $where[] = 'c.status = %s';
                $values[] = $args['status'];
            }
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(c.date_created) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(c.date_created) <= %s';
            $values[] = $args['date_to'];
        }

        $sql = "SELECT c.*, a.affiliate_code, u.display_name as affiliate_name
                FROM " . WCFM_Affiliate_DB::$table_commissions . " c
                LEFT JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON c.affiliate_id = a.id
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c." . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $values[] = $args['limit'];
            $values[] = $args['offset'];
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Count commissions
     */
    public function count_commissions(array $args = []): int {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (!empty($args['affiliate_id'])) {
            $where[] = 'affiliate_id = %d';
            $values[] = $args['affiliate_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $sql = "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_commissions . " WHERE " . implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get total commissions amount
     */
    public function get_total_amount(array $args = []): float {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (!empty($args['affiliate_id'])) {
            $where[] = 'affiliate_id = %d';
            $values[] = $args['affiliate_id'];
        }

        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $values = array_merge($values, $args['status']);
            } else {
                $where[] = 'status = %s';
                $values[] = $args['status'];
            }
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(date_created) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(date_created) <= %s';
            $values[] = $args['date_to'];
        }

        $sql = "SELECT SUM(commission_amount) FROM " . WCFM_Affiliate_DB::$table_commissions . " WHERE " . implode(' AND ', $where);

        if (!empty($values)) {
            return (float) $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (float) $wpdb->get_var($sql);
    }

    /**
     * Handle approve commission AJAX
     */
    public function handle_approve_commission(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_commissions')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $commission_id = intval($_POST['commission_id'] ?? 0);

        if ($this->approve_commission($commission_id)) {
            wp_send_json_success(['message' => __('Commissione approvata', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'approvazione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle reject commission AJAX
     */
    public function handle_reject_commission(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_commissions')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $commission_id = intval($_POST['commission_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($this->reject_commission($commission_id, $notes)) {
            wp_send_json_success(['message' => __('Commissione rifiutata', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante il rifiuto', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle bulk action AJAX
     */
    public function handle_bulk_action(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_commissions')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $ids = array_map('intval', $_POST['ids'] ?? []);

        if (empty($ids)) {
            wp_send_json_error(['message' => __('Nessun elemento selezionato', 'wcfm-affiliate-pro')]);
        }

        $success = 0;

        foreach ($ids as $id) {
            if ($action === 'approve') {
                if ($this->approve_commission($id)) {
                    $success++;
                }
            } elseif ($action === 'reject') {
                if ($this->reject_commission($id)) {
                    $success++;
                }
            }
        }

        wp_send_json_success([
            'message' => sprintf(__('%d elementi aggiornati', 'wcfm-affiliate-pro'), $success),
        ]);
    }

    /**
     * Process recurring commission
     */
    public function process_recurring_commission(int $subscription_id, int $order_id): void {
        if (($this->settings['recurring'] ?? 'no') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Get original order affiliate
        $subscription = wcs_get_subscription($subscription_id);

        if (!$subscription) {
            return;
        }

        $parent_order = $subscription->get_parent();

        if (!$parent_order) {
            return;
        }

        $affiliate_id = $parent_order->get_meta('_wcfm_affiliate_id');

        if (!$affiliate_id) {
            return;
        }

        // Check recurring duration
        $recurring_duration = (int) ($this->settings['recurring_duration'] ?? 12);
        $subscription_start = $subscription->get_date('date_created');
        $months_active = (strtotime(current_time('mysql')) - strtotime($subscription_start)) / (30 * DAY_IN_SECONDS);

        if ($months_active > $recurring_duration) {
            return;
        }

        // Calculate recurring commission
        $recurring_rate = (float) ($this->settings['recurring_rate'] ?? 5);
        $order_total = $order->get_total();
        $commission_amount = round(($order_total * $recurring_rate) / 100, 2);

        if ($commission_amount <= 0) {
            return;
        }

        // Create referral
        global $wpdb;

        $referral_id = $wpdb->insert(
            WCFM_Affiliate_DB::$table_referrals,
            [
                'affiliate_id' => $affiliate_id,
                'order_id' => $order_id,
                'customer_id' => $order->get_customer_id(),
                'status' => 'pending',
                'amount' => $commission_amount,
                'currency' => $order->get_currency(),
                'commission_type' => 'recurring',
                'context' => 'woocommerce_subscription',
            ],
            ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s']
        );

        if ($referral_id) {
            $wpdb->insert(
                WCFM_Affiliate_DB::$table_commissions,
                [
                    'referral_id' => $wpdb->insert_id,
                    'affiliate_id' => $affiliate_id,
                    'order_id' => $order_id,
                    'status' => 'pending',
                    'type' => 'percentage',
                    'rate' => $recurring_rate,
                    'base_amount' => $order_total,
                    'commission_amount' => $commission_amount,
                ],
                ['%d', '%d', '%d', '%s', '%s', '%f', '%f', '%f']
            );

            // Update earnings
            wcfm_affiliate_pro()->affiliates->update_earnings($affiliate_id, $commission_amount);
        }
    }
}
