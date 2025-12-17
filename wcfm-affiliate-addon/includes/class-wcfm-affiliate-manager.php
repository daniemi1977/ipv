<?php
/**
 * Affiliate Manager class
 *
 * Gestisce la registrazione, approvazione e gestione degli affiliati.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Manager
 */
class WCFM_Affiliate_Manager {

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
        // Registration hooks
        add_action('user_register', [$this, 'check_affiliate_registration'], 10, 1);
        add_action('wp_ajax_wcfm_affiliate_register', [$this, 'handle_registration']);
        add_action('wp_ajax_nopriv_wcfm_affiliate_register', [$this, 'handle_registration']);

        // Profile hooks
        add_action('wp_ajax_wcfm_affiliate_update_profile', [$this, 'handle_profile_update']);

        // Admin hooks
        add_action('wp_ajax_wcfm_affiliate_approve', [$this, 'handle_approval']);
        add_action('wp_ajax_wcfm_affiliate_reject', [$this, 'handle_rejection']);
        add_action('wp_ajax_wcfm_affiliate_suspend', [$this, 'handle_suspension']);
        add_action('wp_ajax_wcfm_affiliate_delete', [$this, 'handle_deletion']);

        // Login tracking
        add_action('wp_login', [$this, 'track_login'], 10, 2);
    }

    /**
     * Check if user should become affiliate on registration
     */
    public function check_affiliate_registration(int $user_id): void {
        if (isset($_POST['become_affiliate']) && $_POST['become_affiliate'] === 'yes') {
            $this->register_affiliate($user_id, [
                'website_url' => sanitize_url($_POST['affiliate_website'] ?? ''),
                'promotional_methods' => sanitize_textarea_field($_POST['affiliate_methods'] ?? ''),
            ]);
        }
    }

    /**
     * Handle AJAX registration
     */
    public function handle_registration(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $website_url = sanitize_url($_POST['website_url'] ?? '');
        $promotional_methods = sanitize_textarea_field($_POST['promotional_methods'] ?? '');
        $payment_email = sanitize_email($_POST['payment_email'] ?? $email);
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');

        // Validation
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('Email non valida', 'wcfm-affiliate-pro')]);
        }

        if (email_exists($email)) {
            // User exists, check if already affiliate
            $user = get_user_by('email', $email);
            if ($this->is_affiliate($user->ID)) {
                wp_send_json_error(['message' => __('Sei già registrato come affiliato', 'wcfm-affiliate-pro')]);
            }
            // Register existing user as affiliate
            $result = $this->register_affiliate($user->ID, [
                'website_url' => $website_url,
                'promotional_methods' => $promotional_methods,
                'payment_email' => $payment_email,
                'payment_method' => $payment_method,
            ]);
        } else {
            // Create new user
            if (empty($password)) {
                $password = wp_generate_password(12, true);
            }

            $user_id = wp_create_user(sanitize_user($email), $password, $email);

            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => $user_id->get_error_message()]);
            }

            // Update user meta
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => trim("$first_name $last_name") ?: $email,
            ]);

            // Register as affiliate
            $result = $this->register_affiliate($user_id, [
                'website_url' => $website_url,
                'promotional_methods' => $promotional_methods,
                'payment_email' => $payment_email,
                'payment_method' => $payment_method,
            ]);

            // Send welcome email
            wp_new_user_notification($user_id, null, 'user');
        }

        if ($result) {
            $settings = get_option('wcfm_aff_pro_general', []);
            $require_approval = $settings['require_approval'] ?? 'yes';

            if ($require_approval === 'yes') {
                $message = __('Registrazione completata! La tua richiesta è in attesa di approvazione.', 'wcfm-affiliate-pro');
            } else {
                $message = __('Registrazione completata! Puoi accedere alla tua dashboard.', 'wcfm-affiliate-pro');
            }

            wp_send_json_success([
                'message' => $message,
                'redirect' => $this->get_dashboard_url(),
            ]);
        } else {
            wp_send_json_error(['message' => __('Errore durante la registrazione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Register user as affiliate
     */
    public function register_affiliate(int $user_id, array $data = []): int|bool {
        global $wpdb;

        // Check if already affiliate
        if ($this->is_affiliate($user_id)) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $settings = get_option('wcfm_aff_pro_general', []);
        $registration_type = $settings['registration_type'] ?? 'approval';

        // Determine status
        $status = 'pending';
        if ($registration_type === 'auto') {
            $status = 'active';
        }

        // Check if user is a vendor and auto-approve is enabled
        if (($settings['auto_approve_vendors'] ?? 'no') === 'yes') {
            if ($this->is_vendor($user_id)) {
                $status = 'active';
            }
        }

        // Generate unique affiliate code
        $affiliate_code = $this->generate_affiliate_code($user_id);

        // Get default tier
        $default_tier = $this->get_default_tier();

        // Check for parent affiliate (MLM)
        $parent_affiliate_id = null;
        if (isset($_COOKIE['wcfm_affiliate_ref'])) {
            $parent = $this->get_affiliate_by_code($_COOKIE['wcfm_affiliate_ref']);
            if ($parent && $parent->status === 'active') {
                $parent_affiliate_id = $parent->id;
            }
        }

        // Insert affiliate record
        $insert_data = [
            'user_id' => $user_id,
            'affiliate_code' => $affiliate_code,
            'status' => $status,
            'parent_affiliate_id' => $parent_affiliate_id,
            'tier_id' => $default_tier ? $default_tier->id : null,
            'payment_email' => $data['payment_email'] ?? $user->user_email,
            'payment_method' => $data['payment_method'] ?? 'paypal',
            'website_url' => $data['website_url'] ?? '',
            'promotional_methods' => $data['promotional_methods'] ?? '',
        ];

        // Check if user is vendor
        if ($this->is_vendor($user_id)) {
            $insert_data['vendor_id'] = $user_id;
        }

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_affiliates,
            $insert_data,
            ['%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        if (!$result) {
            return false;
        }

        $affiliate_id = $wpdb->insert_id;

        // Add affiliate role
        $user->add_role('wcfm_affiliate');

        // Create MLM record if parent exists
        if ($parent_affiliate_id) {
            $this->create_mlm_relationship($affiliate_id, $parent_affiliate_id);
        }

        // Send notifications
        if ($status === 'pending') {
            do_action('wcfm_affiliate_pending_registration', $affiliate_id, $user_id);
        } else {
            do_action('wcfm_affiliate_approved', $affiliate_id, $user_id);
        }

        // Admin notification
        do_action('wcfm_affiliate_new_registration', $affiliate_id, $user_id);

        return $affiliate_id;
    }

    /**
     * Generate unique affiliate code
     */
    public function generate_affiliate_code(int $user_id): string {
        $user = get_userdata($user_id);
        $base = sanitize_title($user->display_name ?: $user->user_login);
        $code = substr($base, 0, 10);

        // Make unique
        $counter = 0;
        $original_code = $code;

        while ($this->affiliate_code_exists($code)) {
            $counter++;
            $code = $original_code . $counter;
        }

        return $code;
    }

    /**
     * Check if affiliate code exists
     */
    public function affiliate_code_exists(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE affiliate_code = %s",
            $code
        ));
    }

    /**
     * Check if user is affiliate
     */
    public function is_affiliate(int $user_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Check if user is vendor
     */
    public function is_vendor(int $user_id): bool {
        // WCFM vendor check
        if (function_exists('wcfm_is_vendor')) {
            return wcfm_is_vendor($user_id);
        }

        // Dokan vendor check
        if (function_exists('dokan_is_user_seller')) {
            return dokan_is_user_seller($user_id);
        }

        // WC Vendors check
        $user = get_userdata($user_id);
        if ($user && in_array('vendor', $user->roles)) {
            return true;
        }

        return false;
    }

    /**
     * Get affiliate by user ID
     */
    public function get_affiliate_by_user(int $user_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Get affiliate by ID
     */
    public function get_affiliate(int $affiliate_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE id = %d",
            $affiliate_id
        ));
    }

    /**
     * Get affiliate by code
     */
    public function get_affiliate_by_code(string $code): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE affiliate_code = %s",
            $code
        ));
    }

    /**
     * Get affiliates
     */
    public function get_affiliates(array $args = []): array {
        global $wpdb;

        $defaults = [
            'status' => '',
            'search' => '',
            'vendor_id' => 0,
            'tier_id' => 0,
            'parent_id' => 0,
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'a.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['vendor_id'])) {
            $where[] = 'a.vendor_id = %d';
            $values[] = $args['vendor_id'];
        }

        if (!empty($args['tier_id'])) {
            $where[] = 'a.tier_id = %d';
            $values[] = $args['tier_id'];
        }

        if (!empty($args['parent_id'])) {
            $where[] = 'a.parent_affiliate_id = %d';
            $values[] = $args['parent_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(u.user_email LIKE %s OR u.display_name LIKE %s OR a.affiliate_code LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        $sql = "SELECT a.*, u.user_email, u.display_name, u.user_login
                FROM " . WCFM_Affiliate_DB::$table_affiliates . " a
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a." . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

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
     * Count affiliates
     */
    public function count_affiliates(array $args = []): int {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $sql = "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE " . implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Update affiliate
     */
    public function update_affiliate(int $affiliate_id, array $data): bool {
        global $wpdb;

        $data['date_modified'] = current_time('mysql');

        $result = $wpdb->update(
            WCFM_Affiliate_DB::$table_affiliates,
            $data,
            ['id' => $affiliate_id]
        );

        return $result !== false;
    }

    /**
     * Approve affiliate
     */
    public function approve_affiliate(int $affiliate_id, int $approved_by = 0): bool {
        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return false;
        }

        $result = $this->update_affiliate($affiliate_id, [
            'status' => 'active',
            'approved_by' => $approved_by ?: get_current_user_id(),
            'approved_at' => current_time('mysql'),
        ]);

        if ($result) {
            do_action('wcfm_affiliate_approved', $affiliate_id, $affiliate->user_id);
        }

        return $result;
    }

    /**
     * Reject affiliate
     */
    public function reject_affiliate(int $affiliate_id, string $reason = ''): bool {
        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return false;
        }

        $result = $this->update_affiliate($affiliate_id, [
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        if ($result) {
            do_action('wcfm_affiliate_rejected', $affiliate_id, $affiliate->user_id, $reason);
        }

        return $result;
    }

    /**
     * Suspend affiliate
     */
    public function suspend_affiliate(int $affiliate_id, string $reason = ''): bool {
        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return false;
        }

        $result = $this->update_affiliate($affiliate_id, [
            'status' => 'suspended',
            'notes' => $reason,
        ]);

        if ($result) {
            do_action('wcfm_affiliate_suspended', $affiliate_id, $affiliate->user_id, $reason);
        }

        return $result;
    }

    /**
     * Delete affiliate
     */
    public function delete_affiliate(int $affiliate_id): bool {
        global $wpdb;

        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return false;
        }

        // Remove role from user
        $user = get_userdata($affiliate->user_id);
        if ($user) {
            $user->remove_role('wcfm_affiliate');
        }

        // Delete affiliate record
        $result = $wpdb->delete(
            WCFM_Affiliate_DB::$table_affiliates,
            ['id' => $affiliate_id],
            ['%d']
        );

        if ($result) {
            // Delete related data
            $wpdb->delete(WCFM_Affiliate_DB::$table_mlm, ['affiliate_id' => $affiliate_id], ['%d']);
            $wpdb->delete(WCFM_Affiliate_DB::$table_coupons, ['affiliate_id' => $affiliate_id], ['%d']);

            do_action('wcfm_affiliate_deleted', $affiliate_id, $affiliate->user_id);
        }

        return (bool) $result;
    }

    /**
     * Handle approval AJAX
     */
    public function handle_approval(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('approve_affiliates')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);

        if ($this->approve_affiliate($affiliate_id)) {
            wp_send_json_success(['message' => __('Affiliato approvato', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'approvazione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle rejection AJAX
     */
    public function handle_rejection(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('approve_affiliates')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if ($this->reject_affiliate($affiliate_id, $reason)) {
            wp_send_json_success(['message' => __('Affiliato rifiutato', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante il rifiuto', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle suspension AJAX
     */
    public function handle_suspension(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_affiliates')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if ($this->suspend_affiliate($affiliate_id, $reason)) {
            wp_send_json_success(['message' => __('Affiliato sospeso', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante la sospensione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle deletion AJAX
     */
    public function handle_deletion(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_affiliates')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);

        if ($this->delete_affiliate($affiliate_id)) {
            wp_send_json_success(['message' => __('Affiliato eliminato', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'eliminazione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle profile update AJAX
     */
    public function handle_profile_update(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = $this->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $data = [
            'payment_email' => sanitize_email($_POST['payment_email'] ?? $affiliate->payment_email),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? $affiliate->payment_method),
            'website_url' => sanitize_url($_POST['website_url'] ?? $affiliate->website_url),
            'promotional_methods' => sanitize_textarea_field($_POST['promotional_methods'] ?? $affiliate->promotional_methods),
        ];

        // Payment details (JSON)
        if (!empty($_POST['payment_details'])) {
            $data['payment_details'] = wp_json_encode(array_map('sanitize_text_field', $_POST['payment_details']));
        }

        if ($this->update_affiliate($affiliate->id, $data)) {
            wp_send_json_success(['message' => __('Profilo aggiornato', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'aggiornamento', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Track login
     */
    public function track_login(string $user_login, \WP_User $user): void {
        $affiliate = $this->get_affiliate_by_user($user->ID);

        if ($affiliate) {
            $this->update_affiliate($affiliate->id, [
                'last_login' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Get default tier
     */
    public function get_default_tier(): ?object {
        global $wpdb;
        return $wpdb->get_row(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_tiers . " WHERE is_default = 1 AND status = 'active'"
        );
    }

    /**
     * Create MLM relationship
     */
    private function create_mlm_relationship(int $affiliate_id, int $parent_id): void {
        global $wpdb;

        // Get parent's MLM data
        $parent_mlm = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_mlm . " WHERE affiliate_id = %d",
            $parent_id
        ));

        $level = 1;
        $path = (string) $parent_id;

        if ($parent_mlm) {
            $level = $parent_mlm->level + 1;
            $path = $parent_mlm->path . '/' . $parent_id;
        }

        // Insert MLM record
        $wpdb->insert(
            WCFM_Affiliate_DB::$table_mlm,
            [
                'affiliate_id' => $affiliate_id,
                'parent_id' => $parent_id,
                'level' => $level,
                'path' => $path,
            ],
            ['%d', '%d', '%d', '%s']
        );

        // Update parent's downline count
        $this->update_mlm_downlines($parent_id);
    }

    /**
     * Update MLM downline counts
     */
    private function update_mlm_downlines(int $affiliate_id): void {
        global $wpdb;

        // Count direct downlines
        $direct = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_mlm . " WHERE parent_id = %d",
            $affiliate_id
        ));

        // Count total downlines (recursive)
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_mlm . " WHERE path LIKE %s",
            '%/' . $affiliate_id . '/%'
        ));

        $wpdb->update(
            WCFM_Affiliate_DB::$table_mlm,
            [
                'direct_downlines' => $direct,
                'total_downlines' => $total,
            ],
            ['affiliate_id' => $affiliate_id],
            ['%d', '%d'],
            ['%d']
        );

        // Recursively update parents
        $mlm = $wpdb->get_row($wpdb->prepare(
            "SELECT parent_id FROM " . WCFM_Affiliate_DB::$table_mlm . " WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if ($mlm && $mlm->parent_id) {
            $this->update_mlm_downlines($mlm->parent_id);
        }
    }

    /**
     * Get affiliate's downlines
     */
    public function get_downlines(int $affiliate_id, int $level = 0): array {
        global $wpdb;

        $where = ['m.parent_id = %d'];
        $values = [$affiliate_id];

        if ($level > 0) {
            $where[] = 'm.level <= %d';
            $values[] = $level;
        }

        $sql = $wpdb->prepare(
            "SELECT a.*, m.level, m.path, u.user_email, u.display_name
             FROM " . WCFM_Affiliate_DB::$table_mlm . " m
             JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON m.affiliate_id = a.id
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE " . implode(' AND ', $where) . "
             ORDER BY m.level ASC, a.date_created DESC",
            $values
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get affiliate stats
     */
    public function get_affiliate_stats(int $affiliate_id): array {
        global $wpdb;

        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return [];
        }

        // Get referrals count by status
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_referrals . "
             WHERE affiliate_id = %d
             GROUP BY status",
            $affiliate_id
        ), OBJECT_K);

        // Get clicks/visits for conversion rate
        $visits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . " WHERE affiliate_id = %d",
            $affiliate_id
        ));

        $conversions = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . " WHERE affiliate_id = %d AND converted = 1",
            $affiliate_id
        ));

        return [
            'earnings_balance' => (float) $affiliate->earnings_balance,
            'earnings_paid' => (float) $affiliate->earnings_paid,
            'earnings_total' => (float) $affiliate->earnings_total,
            'referrals_pending' => isset($referrals['pending']) ? (int) $referrals['pending']->count : 0,
            'referrals_approved' => isset($referrals['approved']) ? (int) $referrals['approved']->count : 0,
            'referrals_paid' => isset($referrals['paid']) ? (int) $referrals['paid']->count : 0,
            'referrals_total' => (int) $affiliate->referrals_count,
            'visits' => $visits,
            'conversions' => $conversions,
            'conversion_rate' => $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0,
        ];
    }

    /**
     * Get dashboard URL
     */
    public function get_dashboard_url(): string {
        $pages = get_option('wcfm_aff_pro_pages', []);
        $page_id = $pages['dashboard'] ?? 0;

        if ($page_id) {
            return get_permalink($page_id);
        }

        return home_url('/affiliate-dashboard/');
    }

    /**
     * Update affiliate earnings
     */
    public function update_earnings(int $affiliate_id, float $amount, string $type = 'add'): bool {
        global $wpdb;

        $affiliate = $this->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return false;
        }

        if ($type === 'add') {
            $new_balance = $affiliate->earnings_balance + $amount;
            $new_total = $affiliate->earnings_total + $amount;
        } elseif ($type === 'subtract') {
            $new_balance = max(0, $affiliate->earnings_balance - $amount);
            $new_total = $affiliate->earnings_total; // Total doesn't change
        } elseif ($type === 'paid') {
            $new_balance = max(0, $affiliate->earnings_balance - $amount);
            $new_paid = $affiliate->earnings_paid + $amount;
            return $this->update_affiliate($affiliate_id, [
                'earnings_balance' => $new_balance,
                'earnings_paid' => $new_paid,
            ]);
        } else {
            return false;
        }

        return $this->update_affiliate($affiliate_id, [
            'earnings_balance' => $new_balance,
            'earnings_total' => $new_total,
        ]);
    }

    /**
     * Increment referral count
     */
    public function increment_referral_count(int $affiliate_id): bool {
        global $wpdb;

        return (bool) $wpdb->query($wpdb->prepare(
            "UPDATE " . WCFM_Affiliate_DB::$table_affiliates . " SET referrals_count = referrals_count + 1 WHERE id = %d",
            $affiliate_id
        ));
    }

    /**
     * Increment visit count
     */
    public function increment_visit_count(int $affiliate_id): bool {
        global $wpdb;

        return (bool) $wpdb->query($wpdb->prepare(
            "UPDATE " . WCFM_Affiliate_DB::$table_affiliates . " SET visits_count = visits_count + 1 WHERE id = %d",
            $affiliate_id
        ));
    }
}
