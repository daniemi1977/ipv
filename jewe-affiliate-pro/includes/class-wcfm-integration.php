<?php
/**
 * WCFM Integration Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_WCFM_Integration {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Check if WCFM is active
        if (!$this->is_wcfm_active()) {
            return;
        }

        // Sync hooks
        add_action('wcfm_affiliate_registration', [$this, 'sync_affiliate_from_wcfm'], 10, 2);
        add_action('wcfm_affiliate_status_update', [$this, 'sync_status_from_wcfm'], 10, 2);

        // Commission hooks
        add_action('wcfm_affiliate_commission_processed', [$this, 'sync_commission_from_wcfm'], 10, 4);

        // Add JEWE data to WCFM dashboard
        add_filter('wcfm_affiliate_dashboard_stats', [$this, 'add_jewe_stats'], 10, 2);

        // Migration action
        add_action('admin_init', [$this, 'maybe_migrate_wcfm_data']);
    }

    /**
     * Check if WCFM is active
     */
    private function is_wcfm_active() {
        return class_exists('WCFMa') || class_exists('WCFM_Affiliate');
    }

    /**
     * Sync affiliate from WCFM
     */
    public function sync_affiliate_from_wcfm($user_id, $wcfm_affiliate_data) {
        // Check if already exists in JEWE
        $existing = JEWE_Affiliate::get_by_user($user_id);
        if ($existing) {
            return;
        }

        // Get referrer code if available
        $referrer_code = '';
        if (!empty($wcfm_affiliate_data['affiliate_referrer'])) {
            $referrer = JEWE_Affiliate::get($wcfm_affiliate_data['affiliate_referrer']);
            if ($referrer) {
                $referrer_code = $referrer->affiliate_code;
            }
        }

        // Create JEWE affiliate
        JEWE_Affiliate::create($user_id, $referrer_code);
    }

    /**
     * Sync status from WCFM
     */
    public function sync_status_from_wcfm($affiliate_id, $status) {
        // Map WCFM status to JEWE status
        $status_map = [
            'approved' => 'active',
            'active' => 'active',
            'pending' => 'pending',
            'rejected' => 'rejected',
            'disabled' => 'suspended',
        ];

        $jewe_status = $status_map[$status] ?? 'pending';

        // Find JEWE affiliate by WCFM affiliate ID
        $affiliate = $this->get_jewe_affiliate_by_wcfm_id($affiliate_id);
        if ($affiliate) {
            JEWE_Affiliate::update_status($affiliate->id, $jewe_status);
        }
    }

    /**
     * Sync commission from WCFM
     */
    public function sync_commission_from_wcfm($affiliate_id, $order_id, $commission, $commission_data) {
        // Find JEWE affiliate
        $affiliate = $this->get_jewe_affiliate_by_wcfm_id($affiliate_id);
        if (!$affiliate) {
            return;
        }

        // Check if commission already exists
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}jewe_commissions
             WHERE affiliate_id = %d AND order_id = %d AND commission_type = 'wcfm_sync'",
            $affiliate->id,
            $order_id
        ));

        if ($exists) {
            return;
        }

        // Create commission record
        JEWE_Affiliate_Commission::instance()->create_commission([
            'affiliate_id' => $affiliate->id,
            'order_id' => $order_id,
            'product_id' => $commission_data['product_id'] ?? 0,
            'commission_type' => 'wcfm_sync',
            'commission_rate' => $commission_data['rate'] ?? 0,
            'commission_amount' => $commission,
            'order_total' => $commission_data['order_total'] ?? 0,
        ]);
    }

    /**
     * Get JEWE affiliate by WCFM affiliate ID
     */
    private function get_jewe_affiliate_by_wcfm_id($wcfm_affiliate_id) {
        global $wpdb;

        // WCFM stores affiliate_id as user meta
        $user_id = get_user_meta($wcfm_affiliate_id, 'wcfm_affiliate_user_id', true);

        if (!$user_id) {
            // Try direct lookup
            $user_id = $wcfm_affiliate_id;
        }

        return JEWE_Affiliate::get_by_user($user_id);
    }

    /**
     * Add JEWE stats to WCFM dashboard
     */
    public function add_jewe_stats($stats, $affiliate_id) {
        $affiliate = $this->get_jewe_affiliate_by_wcfm_id($affiliate_id);
        if (!$affiliate) {
            return $stats;
        }

        $jewe_stats = JEWE_Affiliate::get_stats($affiliate->id, '30days');

        $stats['jewe_tier'] = JEWE_Affiliate_Gamification::get_tier($affiliate->tier_level);
        $stats['jewe_insights_score'] = JEWE_Affiliate_AI_Insights::get_overall_score($affiliate->id);
        $stats['jewe_team_size'] = JEWE_Affiliate_MLM::get_stats($affiliate->id)->team_size ?? 0;

        return $stats;
    }

    /**
     * Maybe migrate WCFM data
     */
    public function maybe_migrate_wcfm_data() {
        // Check if migration needed
        if (get_option('jewe_wcfm_migrated')) {
            return;
        }

        // Only run on specific admin action
        if (!isset($_GET['jewe_migrate_wcfm']) || !current_user_can('manage_options')) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'jewe_migrate_wcfm')) {
            return;
        }

        $this->migrate_wcfm_data();
        update_option('jewe_wcfm_migrated', true);

        wp_redirect(admin_url('admin.php?page=jewe-affiliate&migrated=1'));
        exit;
    }

    /**
     * Migrate WCFM affiliate data
     */
    public function migrate_wcfm_data() {
        global $wpdb;

        // Get WCFM affiliates
        $wcfm_affiliates = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wcfm_affiliate_orders
             GROUP BY affiliate_id"
        );

        foreach ($wcfm_affiliates as $wcfm_affiliate) {
            // Check if already migrated
            $existing = JEWE_Affiliate::get_by_user($wcfm_affiliate->affiliate_id);
            if ($existing) {
                continue;
            }

            // Get WCFM affiliate data
            $affiliate_data = $this->get_wcfm_affiliate_data($wcfm_affiliate->affiliate_id);

            // Create JEWE affiliate
            $referrer_code = '';
            if (!empty($affiliate_data['referrer_id'])) {
                $referrer = JEWE_Affiliate::get($affiliate_data['referrer_id']);
                if ($referrer) {
                    $referrer_code = $referrer->affiliate_code;
                }
            }

            $affiliate_id = JEWE_Affiliate::create($wcfm_affiliate->affiliate_id, $referrer_code);

            if (!is_wp_error($affiliate_id)) {
                // Migrate commissions
                $this->migrate_wcfm_commissions($wcfm_affiliate->affiliate_id, $affiliate_id);

                // Update stats
                $this->update_migrated_stats($affiliate_id);
            }
        }

        return true;
    }

    /**
     * Get WCFM affiliate data
     */
    private function get_wcfm_affiliate_data($affiliate_id) {
        global $wpdb;

        $data = [
            'referrer_id' => 0,
            'status' => 'active',
            'total_earnings' => 0,
        ];

        // Try to get from user meta
        $user_meta = get_user_meta($affiliate_id);

        if (!empty($user_meta['wcfm_affiliate_referrer'][0])) {
            $data['referrer_id'] = intval($user_meta['wcfm_affiliate_referrer'][0]);
        }

        // Get total earnings from WCFM
        $earnings = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(commission_amount) FROM {$wpdb->prefix}wcfm_affiliate_orders
             WHERE affiliate_id = %d AND commission_status = 'paid'",
            $affiliate_id
        ));

        $data['total_earnings'] = floatval($earnings);

        return $data;
    }

    /**
     * Migrate WCFM commissions
     */
    private function migrate_wcfm_commissions($wcfm_affiliate_id, $jewe_affiliate_id) {
        global $wpdb;

        $wcfm_commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfm_affiliate_orders WHERE affiliate_id = %d",
            $wcfm_affiliate_id
        ));

        foreach ($wcfm_commissions as $commission) {
            // Map status
            $status_map = [
                'paid' => 'paid',
                'pending' => 'pending',
                'cancelled' => 'refunded',
            ];

            $status = $status_map[$commission->commission_status] ?? 'pending';

            $wpdb->insert($wpdb->prefix . 'jewe_commissions', [
                'affiliate_id' => $jewe_affiliate_id,
                'order_id' => $commission->order_id,
                'product_id' => $commission->product_id,
                'commission_type' => 'wcfm_migrated',
                'commission_rate' => 0,
                'commission_amount' => $commission->commission_amount,
                'order_total' => $commission->item_total,
                'status' => $status,
                'created_at' => $commission->created,
            ]);
        }
    }

    /**
     * Update stats after migration
     */
    private function update_migrated_stats($affiliate_id) {
        global $wpdb;
        $commissions_table = $wpdb->prefix . 'jewe_commissions';
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';

        // Calculate totals
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(commission_amount) as total_earnings,
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending
             FROM $commissions_table WHERE affiliate_id = %d",
            $affiliate_id
        ));

        // Update affiliate
        $wpdb->update(
            $affiliates_table,
            [
                'lifetime_earnings' => floatval($totals->total_earnings ?? 0),
                'current_balance' => floatval($totals->pending ?? 0),
            ],
            ['id' => $affiliate_id]
        );
    }

    /**
     * Get migration URL
     */
    public static function get_migration_url() {
        return wp_nonce_url(
            admin_url('admin.php?page=jewe-affiliate&jewe_migrate_wcfm=1'),
            'jewe_migrate_wcfm'
        );
    }

    /**
     * Check if WCFM data exists for migration
     */
    public static function has_wcfm_data_to_migrate() {
        if (get_option('jewe_wcfm_migrated')) {
            return false;
        }

        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_affiliate_orders");

        return $count > 0;
    }
}
