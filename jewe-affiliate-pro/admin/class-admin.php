<?php
/**
 * Admin Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Admin {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices', [$this, 'admin_notices']);

        // AJAX handlers
        add_action('wp_ajax_jewe_approve_affiliate', [$this, 'ajax_approve_affiliate']);
        add_action('wp_ajax_jewe_reject_affiliate', [$this, 'ajax_reject_affiliate']);
        add_action('wp_ajax_jewe_process_payout', [$this, 'ajax_process_payout']);
        add_action('wp_ajax_jewe_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('JEWE Affiliate Pro', 'jewe-affiliate-pro'),
            __('Affiliati', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate',
            [$this, 'render_dashboard'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Dashboard', 'jewe-affiliate-pro'),
            __('Dashboard', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Affiliati', 'jewe-affiliate-pro'),
            __('Affiliati', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate-list',
            [$this, 'render_affiliates_list']
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Commissioni', 'jewe-affiliate-pro'),
            __('Commissioni', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate-commissions',
            [$this, 'render_commissions']
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Pagamenti', 'jewe-affiliate-pro'),
            __('Pagamenti', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate-payouts',
            [$this, 'render_payouts']
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Report', 'jewe-affiliate-pro'),
            __('Report', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate-reports',
            [$this, 'render_reports']
        );

        add_submenu_page(
            'jewe-affiliate',
            __('Impostazioni', 'jewe-affiliate-pro'),
            __('Impostazioni', 'jewe-affiliate-pro'),
            'manage_options',
            'jewe-affiliate-settings',
            [$this, 'render_settings']
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'jewe-affiliate') === false) {
            return;
        }

        wp_enqueue_style(
            'jewe-affiliate-admin',
            JEWE_AFFILIATE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            JEWE_AFFILIATE_VERSION
        );

        wp_enqueue_script(
            'jewe-affiliate-admin',
            JEWE_AFFILIATE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            JEWE_AFFILIATE_VERSION,
            true
        );

        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        wp_localize_script('jewe-affiliate-admin', 'jeweAffiliateAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jewe_admin_nonce'),
            'strings' => [
                'confirm_approve' => __('Confermi l\'approvazione?', 'jewe-affiliate-pro'),
                'confirm_reject' => __('Confermi il rifiuto?', 'jewe-affiliate-pro'),
                'confirm_payout' => __('Confermi il pagamento?', 'jewe-affiliate-pro'),
                'success' => __('Operazione completata', 'jewe-affiliate-pro'),
                'error' => __('Si è verificato un errore', 'jewe-affiliate-pro'),
            ],
        ]);
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Migration notice
        if (JEWE_Affiliate_WCFM_Integration::has_wcfm_data_to_migrate()) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('JEWE Affiliate Pro:', 'jewe-affiliate-pro'); ?></strong>
                    <?php _e('Abbiamo rilevato dati WCFM Affiliate esistenti.', 'jewe-affiliate-pro'); ?>
                    <a href="<?php echo esc_url(JEWE_Affiliate_WCFM_Integration::get_migration_url()); ?>" class="button button-primary">
                        <?php _e('Migra Dati', 'jewe-affiliate-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        }

        // Migration success notice
        if (isset($_GET['migrated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Migrazione completata con successo!', 'jewe-affiliate-pro'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $overview = JEWE_Affiliate_Analytics::get_program_overview('30days');
        $pending_affiliates = JEWE_Affiliate_Database::get_affiliates(['status' => 'pending', 'limit' => 5]);
        $recent_commissions = $this->get_recent_commissions(10);
        $top_affiliates = JEWE_Affiliate::get_leaderboard(5, '30days');

        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Render affiliates list
     */
    public function render_affiliates_list() {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;

        $affiliates = JEWE_Affiliate_Database::get_affiliates([
            'status' => $status,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        // Enhance with user data
        foreach ($affiliates as &$affiliate) {
            $user = get_userdata($affiliate->user_id);
            $affiliate->name = $user ? $user->display_name : 'N/A';
            $affiliate->email = $user ? $user->user_email : 'N/A';
            $affiliate->tier = JEWE_Affiliate_Gamification::get_tier($affiliate->tier_level);
        }

        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/admin/affiliates-list.php';
    }

    /**
     * Render commissions
     */
    public function render_commissions() {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE 1=1";
        if ($status) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }

        $commissions = $wpdb->get_results(
            "SELECT c.*, a.affiliate_code, a.user_id
             FROM $table c
             JOIN {$wpdb->prefix}jewe_affiliates a ON c.affiliate_id = a.id
             $where
             ORDER BY c.created_at DESC
             LIMIT 50"
        );

        // Enhance
        foreach ($commissions as &$commission) {
            $user = get_userdata($commission->user_id);
            $commission->affiliate_name = $user ? $user->display_name : 'N/A';
        }

        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/admin/commissions.php';
    }

    /**
     * Render payouts
     */
    public function render_payouts() {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_payouts';

        $payouts = $wpdb->get_results(
            "SELECT p.*, a.affiliate_code, a.user_id
             FROM $table p
             JOIN {$wpdb->prefix}jewe_affiliates a ON p.affiliate_id = a.id
             ORDER BY p.requested_at DESC
             LIMIT 50"
        );

        foreach ($payouts as &$payout) {
            $user = get_userdata($payout->user_id);
            $payout->affiliate_name = $user ? $user->display_name : 'N/A';
            $payout->affiliate_email = $user ? $user->user_email : 'N/A';
        }

        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/admin/payouts.php';
    }

    /**
     * Render reports
     */
    public function render_reports() {
        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/admin/reports.php';
    }

    /**
     * Render settings
     */
    public function render_settings() {
        JEWE_Affiliate_Settings::instance()->render();
    }

    /**
     * Get recent commissions
     */
    private function get_recent_commissions($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, a.affiliate_code, a.user_id
             FROM $table c
             JOIN {$wpdb->prefix}jewe_affiliates a ON c.affiliate_id = a.id
             ORDER BY c.created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * AJAX: Approve affiliate
     */
    public function ajax_approve_affiliate() {
        check_ajax_referer('jewe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
        }

        $affiliate_id = isset($_POST['affiliate_id']) ? intval($_POST['affiliate_id']) : 0;

        if (JEWE_Affiliate::update_status($affiliate_id, 'active')) {
            wp_send_json_success(['message' => __('Affiliato approvato', 'jewe-affiliate-pro')]);
        }

        wp_send_json_error(['message' => __('Errore durante l\'approvazione', 'jewe-affiliate-pro')]);
    }

    /**
     * AJAX: Reject affiliate
     */
    public function ajax_reject_affiliate() {
        check_ajax_referer('jewe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
        }

        $affiliate_id = isset($_POST['affiliate_id']) ? intval($_POST['affiliate_id']) : 0;

        if (JEWE_Affiliate::update_status($affiliate_id, 'rejected')) {
            wp_send_json_success(['message' => __('Affiliato rifiutato', 'jewe-affiliate-pro')]);
        }

        wp_send_json_error(['message' => __('Errore durante il rifiuto', 'jewe-affiliate-pro')]);
    }

    /**
     * AJAX: Process payout
     */
    public function ajax_process_payout() {
        check_ajax_referer('jewe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
        }

        $payout_id = isset($_POST['payout_id']) ? intval($_POST['payout_id']) : 0;
        $action = isset($_POST['payout_action']) ? sanitize_text_field($_POST['payout_action']) : '';

        global $wpdb;
        $table = $wpdb->prefix . 'jewe_payouts';

        $payout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $payout_id));

        if (!$payout) {
            wp_send_json_error(['message' => __('Pagamento non trovato', 'jewe-affiliate-pro')]);
        }

        if ($action === 'approve') {
            // Update payout status
            $wpdb->update(
                $table,
                [
                    'status' => 'paid',
                    'processed_at' => current_time('mysql'),
                ],
                ['id' => $payout_id]
            );

            // Deduct from affiliate balance
            $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
            $wpdb->query($wpdb->prepare(
                "UPDATE $affiliates_table SET current_balance = current_balance - %f WHERE id = %d",
                $payout->amount,
                $payout->affiliate_id
            ));

            // Mark related commissions as paid
            $commissions_table = $wpdb->prefix . 'jewe_commissions';
            $wpdb->update(
                $commissions_table,
                ['status' => 'paid', 'paid_at' => current_time('mysql')],
                ['affiliate_id' => $payout->affiliate_id, 'status' => 'pending']
            );

            // Notify affiliate
            JEWE_Affiliate_Notifications::send($payout->affiliate_id, 'payout_processed', [
                'amount' => $payout->amount,
            ]);

            wp_send_json_success(['message' => __('Pagamento elaborato', 'jewe-affiliate-pro')]);
        } elseif ($action === 'reject') {
            $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';

            $wpdb->update(
                $table,
                ['status' => 'rejected'],
                ['id' => $payout_id]
            );

            JEWE_Affiliate_Notifications::send($payout->affiliate_id, 'payout_rejected', [
                'amount' => $payout->amount,
                'reason' => $reason,
            ]);

            wp_send_json_success(['message' => __('Pagamento rifiutato', 'jewe-affiliate-pro')]);
        }

        wp_send_json_error(['message' => __('Azione non valida', 'jewe-affiliate-pro')]);
    }

    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('jewe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';

        $data = [
            'overview' => JEWE_Affiliate_Analytics::get_program_overview($period),
            'chart_data' => $this->get_chart_data($period),
        ];

        wp_send_json_success($data);
    }

    /**
     * Get chart data
     */
    private function get_chart_data($period) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $days = $period === '7days' ? 7 : ($period === '90days' ? 90 : 30);
        $date_from = date('Y-m-d', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(created_at) as date,
                SUM(commission_amount) as commissions,
                SUM(order_total) as revenue,
                COUNT(*) as sales
             FROM $table
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $date_from
        ));

        $labels = [];
        $commissions = [];
        $revenue = [];
        $sales = [];

        // Fill in missing days
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($date));

            $found = false;
            foreach ($results as $row) {
                if ($row->date === $date) {
                    $commissions[] = floatval($row->commissions);
                    $revenue[] = floatval($row->revenue);
                    $sales[] = intval($row->sales);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $commissions[] = 0;
                $revenue[] = 0;
                $sales[] = 0;
            }
        }

        return [
            'labels' => $labels,
            'commissions' => $commissions,
            'revenue' => $revenue,
            'sales' => $sales,
        ];
    }
}
