<?php
/**
 * Admin class
 *
 * Gestisce la parte amministrativa del plugin.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Admin
 */
class WCFM_Affiliate_Admin {

    /**
     * Admin pages
     */
    private array $pages = [];

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
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notices']);

        // Screen options
        add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);
    }

    /**
     * Register admin menus
     */
    public function register_menus(): void {
        // Main menu
        add_menu_page(
            __('WCFM Affiliate Pro', 'wcfm-affiliate-pro'),
            __('Affiliate Pro', 'wcfm-affiliate-pro'),
            'manage_affiliates',
            'wcfm-affiliate',
            [$this, 'render_dashboard_page'],
            'dashicons-groups',
            58
        );

        // Dashboard
        add_submenu_page(
            'wcfm-affiliate',
            __('Dashboard', 'wcfm-affiliate-pro'),
            __('Dashboard', 'wcfm-affiliate-pro'),
            'manage_affiliates',
            'wcfm-affiliate',
            [$this, 'render_dashboard_page']
        );

        // Affiliates
        $this->pages['affiliates'] = add_submenu_page(
            'wcfm-affiliate',
            __('Affiliati', 'wcfm-affiliate-pro'),
            __('Affiliati', 'wcfm-affiliate-pro'),
            'manage_affiliates',
            'wcfm-affiliate-affiliates',
            [$this, 'render_affiliates_page']
        );

        // Referrals
        $this->pages['referrals'] = add_submenu_page(
            'wcfm-affiliate',
            __('Referral', 'wcfm-affiliate-pro'),
            __('Referral', 'wcfm-affiliate-pro'),
            'manage_affiliates',
            'wcfm-affiliate-referrals',
            [$this, 'render_referrals_page']
        );

        // Commissions
        $this->pages['commissions'] = add_submenu_page(
            'wcfm-affiliate',
            __('Commissioni', 'wcfm-affiliate-pro'),
            __('Commissioni', 'wcfm-affiliate-pro'),
            'manage_commissions',
            'wcfm-affiliate-commissions',
            [$this, 'render_commissions_page']
        );

        // Payouts
        $this->pages['payouts'] = add_submenu_page(
            'wcfm-affiliate',
            __('Pagamenti', 'wcfm-affiliate-pro'),
            __('Pagamenti', 'wcfm-affiliate-pro'),
            'manage_payouts',
            'wcfm-affiliate-payouts',
            [$this, 'render_payouts_page']
        );

        // Tiers
        add_submenu_page(
            'wcfm-affiliate',
            __('Livelli', 'wcfm-affiliate-pro'),
            __('Livelli', 'wcfm-affiliate-pro'),
            'edit_affiliate_settings',
            'wcfm-affiliate-tiers',
            [$this, 'render_tiers_page']
        );

        // Creatives
        add_submenu_page(
            'wcfm-affiliate',
            __('Materiali', 'wcfm-affiliate-pro'),
            __('Materiali', 'wcfm-affiliate-pro'),
            'edit_affiliate_settings',
            'wcfm-affiliate-creatives',
            [$this, 'render_creatives_page']
        );

        // Reports
        add_submenu_page(
            'wcfm-affiliate',
            __('Report', 'wcfm-affiliate-pro'),
            __('Report', 'wcfm-affiliate-pro'),
            'view_affiliate_reports',
            'wcfm-affiliate-reports',
            [$this, 'render_reports_page']
        );

        // Settings
        add_submenu_page(
            'wcfm-affiliate',
            __('Impostazioni', 'wcfm-affiliate-pro'),
            __('Impostazioni', 'wcfm-affiliate-pro'),
            'edit_affiliate_settings',
            'wcfm-affiliate-settings',
            [$this, 'render_settings_page']
        );

        // Add screen options
        add_action('load-' . $this->pages['affiliates'], [$this, 'affiliates_screen_options']);
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        // General settings
        register_setting('wcfm_affiliate_general', 'wcfm_affiliate_general', [
            'sanitize_callback' => [$this, 'sanitize_general_settings'],
        ]);

        // Commission settings
        register_setting('wcfm_affiliate_commission', 'wcfm_affiliate_commission', [
            'sanitize_callback' => [$this, 'sanitize_commission_settings'],
        ]);

        // MLM settings
        register_setting('wcfm_affiliate_mlm', 'wcfm_affiliate_mlm', [
            'sanitize_callback' => [$this, 'sanitize_mlm_settings'],
        ]);

        // Notification settings
        register_setting('wcfm_affiliate_notifications', 'wcfm_affiliate_notifications');

        // Design settings
        register_setting('wcfm_affiliate_design', 'wcfm_affiliate_design');

        // Payment gateway settings
        register_setting('wcfm_affiliate_payments', 'wcfm_affiliate_paypal_client_id');
        register_setting('wcfm_affiliate_payments', 'wcfm_affiliate_paypal_secret');
        register_setting('wcfm_affiliate_payments', 'wcfm_affiliate_stripe_secret_key');
    }

    /**
     * Sanitize general settings
     */
    public function sanitize_general_settings(array $input): array {
        $output = [];

        $output['enable'] = isset($input['enable']) ? 'yes' : 'no';
        $output['registration_type'] = sanitize_text_field($input['registration_type'] ?? 'approval');
        $output['cookie_duration'] = absint($input['cookie_duration'] ?? 30);
        $output['cookie_name'] = sanitize_key($input['cookie_name'] ?? 'wcfm_affiliate_ref');
        $output['referral_var'] = sanitize_key($input['referral_var'] ?? 'ref');
        $output['credit_last_referrer'] = isset($input['credit_last_referrer']) ? 'yes' : 'no';
        $output['require_approval'] = isset($input['require_approval']) ? 'yes' : 'no';
        $output['auto_approve_vendors'] = isset($input['auto_approve_vendors']) ? 'yes' : 'no';
        $output['minimum_payout'] = floatval($input['minimum_payout'] ?? 50);
        $output['payout_methods'] = array_map('sanitize_text_field', $input['payout_methods'] ?? ['paypal']);
        $output['payout_schedule'] = sanitize_text_field($input['payout_schedule'] ?? 'monthly');
        $output['allow_self_referral'] = isset($input['allow_self_referral']) ? 'yes' : 'no';

        return $output;
    }

    /**
     * Sanitize commission settings
     */
    public function sanitize_commission_settings(array $input): array {
        $output = [];

        $output['type'] = sanitize_text_field($input['type'] ?? 'percentage');
        $output['rate'] = floatval($input['rate'] ?? 10);
        $output['flat_amount'] = floatval($input['flat_amount'] ?? 5);
        $output['per_product'] = isset($input['per_product']) ? 'yes' : 'no';
        $output['per_category'] = isset($input['per_category']) ? 'yes' : 'no';
        $output['per_vendor'] = isset($input['per_vendor']) ? 'yes' : 'no';
        $output['recurring'] = isset($input['recurring']) ? 'yes' : 'no';
        $output['recurring_rate'] = floatval($input['recurring_rate'] ?? 5);
        $output['recurring_duration'] = absint($input['recurring_duration'] ?? 12);
        $output['lifetime_referrals'] = isset($input['lifetime_referrals']) ? 'yes' : 'no';
        $output['approval_method'] = sanitize_text_field($input['approval_method'] ?? 'manual');
        $output['approval_delay'] = absint($input['approval_delay'] ?? 30);
        $output['exclude_shipping'] = isset($input['exclude_shipping']) ? 'yes' : 'no';
        $output['exclude_tax'] = isset($input['exclude_tax']) ? 'yes' : 'no';
        $output['exclude_discounts'] = isset($input['exclude_discounts']) ? 'yes' : 'no';

        return $output;
    }

    /**
     * Sanitize MLM settings
     */
    public function sanitize_mlm_settings(array $input): array {
        $output = [];

        $output['enable'] = isset($input['enable']) ? 'yes' : 'no';
        $output['levels'] = min(10, absint($input['levels'] ?? 3));
        $output['level_rates'] = array_map('floatval', $input['level_rates'] ?? [10, 5, 2]);
        $output['override_bonus'] = isset($input['override_bonus']) ? 'yes' : 'no';
        $output['override_rate'] = floatval($input['override_rate'] ?? 2);

        return $output;
    }

    /**
     * Admin notices
     */
    public function admin_notices(): void {
        // Activation notice
        if (get_transient('wcfm_affiliate_pro_activated')) {
            delete_transient('wcfm_affiliate_pro_activated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('WCFM Affiliate Pro', 'wcfm-affiliate-pro'); ?></strong>
                    <?php _e('attivato con successo!', 'wcfm-affiliate-pro'); ?>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-settings'); ?>">
                        <?php _e('Configura le impostazioni', 'wcfm-affiliate-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        }

        // Pending affiliates notice
        $pending_count = wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'pending']);
        if ($pending_count > 0):
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php printf(
                        _n(
                            'Hai %d richiesta di affiliazione in attesa di approvazione.',
                            'Hai %d richieste di affiliazione in attesa di approvazione.',
                            $pending_count,
                            'wcfm-affiliate-pro'
                        ),
                        $pending_count
                    ); ?>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&status=pending'); ?>">
                        <?php _e('Visualizza', 'wcfm-affiliate-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        endif;
    }

    /**
     * Screen options for affiliates page
     */
    public function affiliates_screen_options(): void {
        add_screen_option('per_page', [
            'label' => __('Affiliati per pagina', 'wcfm-affiliate-pro'),
            'default' => 20,
            'option' => 'wcfm_affiliates_per_page',
        ]);
    }

    /**
     * Set screen option
     */
    public function set_screen_option($status, $option, $value) {
        if ('wcfm_affiliates_per_page' === $option) {
            return $value;
        }
        return $status;
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page(): void {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap wcfm-affiliate-admin">
            <h1><?php _e('Dashboard Affiliate Pro', 'wcfm-affiliate-pro'); ?></h1>

            <div class="wcfm-affiliate-admin-stats">
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['total_affiliates']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Affiliati Totali', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['active_affiliates']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Affiliati Attivi', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['pending_affiliates']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('In Attesa', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['total_commissions']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Commissioni Totali', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['pending_payouts']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Pagamenti in Attesa', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['paid_out']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Totale Pagato', 'wcfm-affiliate-pro'); ?></span>
                </div>
            </div>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Andamento Commissioni (30 giorni)', 'wcfm-affiliate-pro'); ?></h3>
                        <canvas id="wcfm-affiliate-admin-chart" height="300"></canvas>
                    </div>
                </div>
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Top Affiliati', 'wcfm-affiliate-pro'); ?></h3>
                        <?php $this->render_top_affiliates(); ?>
                    </div>
                </div>
            </div>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Richieste Recenti', 'wcfm-affiliate-pro'); ?></h3>
                        <?php $this->render_recent_pending(); ?>
                    </div>
                </div>
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Pagamenti in Attesa', 'wcfm-affiliate-pro'); ?></h3>
                        <?php $this->render_pending_payouts(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get dashboard stats
     */
    private function get_dashboard_stats(): array {
        global $wpdb;

        return [
            'total_affiliates' => wcfm_affiliate_pro()->affiliates->count_affiliates(),
            'active_affiliates' => wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'active']),
            'pending_affiliates' => wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'pending']),
            'total_commissions' => wcfm_affiliate_pro()->commissions->get_total_amount(),
            'pending_payouts' => (float) $wpdb->get_var(
                "SELECT SUM(amount) FROM " . WCFM_Affiliate_DB::$table_payouts . " WHERE status = 'pending'"
            ),
            'paid_out' => (float) $wpdb->get_var(
                "SELECT SUM(amount) FROM " . WCFM_Affiliate_DB::$table_payouts . " WHERE status = 'completed'"
            ),
        ];
    }

    /**
     * Render top affiliates
     */
    private function render_top_affiliates(): void {
        global $wpdb;

        $affiliates = $wpdb->get_results(
            "SELECT a.*, u.display_name
             FROM " . WCFM_Affiliate_DB::$table_affiliates . " a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.status = 'active'
             ORDER BY a.earnings_total DESC
             LIMIT 5"
        );

        if (empty($affiliates)):
            echo '<p>' . __('Nessun affiliato ancora', 'wcfm-affiliate-pro') . '</p>';
            return;
        endif;
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Referral', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Guadagni', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($affiliates as $affiliate): ?>
                    <tr>
                        <td><?php echo esc_html($affiliate->display_name); ?></td>
                        <td><?php echo number_format($affiliate->referrals_count); ?></td>
                        <td><?php echo wc_price($affiliate->earnings_total); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render recent pending affiliates
     */
    private function render_recent_pending(): void {
        $affiliates = wcfm_affiliate_pro()->affiliates->get_affiliates([
            'status' => 'pending',
            'limit' => 5,
        ]);

        if (empty($affiliates)):
            echo '<p>' . __('Nessuna richiesta in attesa', 'wcfm-affiliate-pro') . '</p>';
            return;
        endif;
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Nome', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Email', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($affiliates as $affiliate): ?>
                    <tr>
                        <td><?php echo esc_html($affiliate->display_name); ?></td>
                        <td><?php echo esc_html($affiliate->user_email); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($affiliate->date_created)); ?></td>
                        <td>
                            <button class="button button-small wcfm-affiliate-approve-btn"
                                    data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Approva', 'wcfm-affiliate-pro'); ?>
                            </button>
                            <button class="button button-small wcfm-affiliate-reject-btn"
                                    data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Rifiuta', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render pending payouts
     */
    private function render_pending_payouts(): void {
        $payouts = wcfm_affiliate_pro()->payouts->get_payouts([
            'status' => 'pending',
            'limit' => 5,
        ]);

        if (empty($payouts)):
            echo '<p>' . __('Nessun pagamento in attesa', 'wcfm-affiliate-pro') . '</p>';
            return;
        endif;
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Metodo', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td><?php echo esc_html($payout->affiliate_name); ?></td>
                        <td><?php echo wc_price($payout->amount); ?></td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts&action=process&id=' . $payout->id); ?>"
                               class="button button-small">
                                <?php _e('Elabora', 'wcfm-affiliate-pro'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render affiliates page
     */
    public function render_affiliates_page(): void {
        require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-affiliates.php';
        $admin_affiliates = new WCFM_Affiliate_Admin_Affiliates();
        $admin_affiliates->render();
    }

    /**
     * Render referrals page
     */
    public function render_referrals_page(): void {
        global $wpdb;

        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        $where = ['1=1'];
        $values = [];

        if (!empty($_GET['affiliate_id'])) {
            $where[] = 'r.affiliate_id = %d';
            $values[] = intval($_GET['affiliate_id']);
        }

        if (!empty($_GET['status'])) {
            $where[] = 'r.status = %s';
            $values[] = sanitize_text_field($_GET['status']);
        }

        $sql = "SELECT r.*, a.affiliate_code, u.display_name as affiliate_name
                FROM " . WCFM_Affiliate_DB::$table_referrals . " r
                LEFT JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON r.affiliate_id = a.id
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.date_created DESC
                LIMIT %d OFFSET %d";

        $values[] = $per_page;
        $values[] = $offset;

        $referrals = $wpdb->get_results($wpdb->prepare($sql, $values));

        // Count total
        $count_sql = "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_referrals . " r WHERE " . implode(' AND ', array_slice($where, 0, -2));
        $total = !empty(array_slice($values, 0, -2)) ?
            $wpdb->get_var($wpdb->prepare($count_sql, array_slice($values, 0, -2))) :
            $wpdb->get_var($count_sql);
        ?>
        <div class="wrap">
            <h1><?php _e('Referral', 'wcfm-affiliate-pro'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Ordine', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo esc_html($referral->id); ?></td>
                            <td><?php echo esc_html($referral->affiliate_name); ?></td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $referral->order_id . '&action=edit'); ?>">
                                    #<?php echo esc_html($referral->order_id); ?>
                                </a>
                            </td>
                            <td><?php echo wc_price($referral->amount); ?></td>
                            <td>
                                <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($referral->status); ?>">
                                    <?php echo esc_html(ucfirst($referral->status)); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($referral->date_created)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1):
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                ]);
            endif;
            ?>
        </div>
        <?php
    }

    /**
     * Render commissions page
     */
    public function render_commissions_page(): void {
        require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-commissions.php';
        $admin_commissions = new WCFM_Affiliate_Admin_Commissions();
        $admin_commissions->render();
    }

    /**
     * Render payouts page
     */
    public function render_payouts_page(): void {
        require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-payouts.php';
        $admin_payouts = new WCFM_Affiliate_Admin_Payouts();
        $admin_payouts->render();
    }

    /**
     * Render tiers page
     */
    public function render_tiers_page(): void {
        global $wpdb;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wcfm_affiliate_tier_nonce'])) {
            if (wp_verify_nonce($_POST['wcfm_affiliate_tier_nonce'], 'wcfm_affiliate_save_tier')) {
                $this->save_tier($_POST);
            }
        }

        // Handle delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete(WCFM_Affiliate_DB::$table_tiers, ['id' => intval($_GET['id'])], ['%d']);
        }

        $tiers = $wpdb->get_results("SELECT * FROM " . WCFM_Affiliate_DB::$table_tiers . " ORDER BY priority ASC");
        ?>
        <div class="wrap">
            <h1><?php _e('Livelli Affiliato', 'wcfm-affiliate-pro'); ?></h1>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Livelli Esistenti', 'wcfm-affiliate-pro'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Nome', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Commissione', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Requisiti', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tiers as $tier): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($tier->name); ?></strong>
                                            <?php if ($tier->is_default): ?>
                                                <span class="description">(<?php _e('Default', 'wcfm-affiliate-pro'); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($tier->commission_type === 'percentage') {
                                                echo esc_html($tier->commission_rate) . '%';
                                            } else {
                                                echo wc_price($tier->commission_rate);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $reqs = [];
                                            if ($tier->minimum_referrals > 0) {
                                                $reqs[] = sprintf(__('%d referral', 'wcfm-affiliate-pro'), $tier->minimum_referrals);
                                            }
                                            if ($tier->minimum_earnings > 0) {
                                                $reqs[] = sprintf(__('%s guadagni', 'wcfm-affiliate-pro'), wc_price($tier->minimum_earnings));
                                            }
                                            echo $reqs ? implode(', ', $reqs) : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo $tier->status === 'active' ? 'approved' : 'rejected'; ?>">
                                                <?php echo esc_html(ucfirst($tier->status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo add_query_arg(['edit' => $tier->id]); ?>"><?php _e('Modifica', 'wcfm-affiliate-pro'); ?></a>
                                            <?php if (!$tier->is_default): ?>
                                                |
                                                <a href="<?php echo add_query_arg(['action' => 'delete', 'id' => $tier->id]); ?>"
                                                   onclick="return confirm('<?php _e('Eliminare questo livello?', 'wcfm-affiliate-pro'); ?>')">
                                                    <?php _e('Elimina', 'wcfm-affiliate-pro'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <?php
                        $edit_tier = null;
                        if (isset($_GET['edit'])) {
                            $edit_tier = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM " . WCFM_Affiliate_DB::$table_tiers . " WHERE id = %d",
                                intval($_GET['edit'])
                            ));
                        }
                        ?>
                        <h3><?php echo $edit_tier ? __('Modifica Livello', 'wcfm-affiliate-pro') : __('Aggiungi Nuovo Livello', 'wcfm-affiliate-pro'); ?></h3>
                        <form method="post">
                            <?php wp_nonce_field('wcfm_affiliate_save_tier', 'wcfm_affiliate_tier_nonce'); ?>
                            <input type="hidden" name="tier_id" value="<?php echo esc_attr($edit_tier->id ?? ''); ?>">

                            <table class="form-table">
                                <tr>
                                    <th><label for="name"><?php _e('Nome', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="text" name="name" id="name" class="regular-text" required
                                               value="<?php echo esc_attr($edit_tier->name ?? ''); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="commission_type"><?php _e('Tipo Commissione', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td>
                                        <select name="commission_type" id="commission_type">
                                            <option value="percentage" <?php selected($edit_tier->commission_type ?? '', 'percentage'); ?>>
                                                <?php _e('Percentuale', 'wcfm-affiliate-pro'); ?>
                                            </option>
                                            <option value="flat" <?php selected($edit_tier->commission_type ?? '', 'flat'); ?>>
                                                <?php _e('Importo Fisso', 'wcfm-affiliate-pro'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="commission_rate"><?php _e('Tasso Commissione', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="number" name="commission_rate" id="commission_rate" step="0.01" min="0"
                                               value="<?php echo esc_attr($edit_tier->commission_rate ?? 10); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="minimum_referrals"><?php _e('Referral Minimi', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="number" name="minimum_referrals" id="minimum_referrals" min="0"
                                               value="<?php echo esc_attr($edit_tier->minimum_referrals ?? 0); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="minimum_earnings"><?php _e('Guadagni Minimi', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="number" name="minimum_earnings" id="minimum_earnings" step="0.01" min="0"
                                               value="<?php echo esc_attr($edit_tier->minimum_earnings ?? 0); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="status"><?php _e('Stato', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td>
                                        <select name="status" id="status">
                                            <option value="active" <?php selected($edit_tier->status ?? '', 'active'); ?>>
                                                <?php _e('Attivo', 'wcfm-affiliate-pro'); ?>
                                            </option>
                                            <option value="inactive" <?php selected($edit_tier->status ?? '', 'inactive'); ?>>
                                                <?php _e('Inattivo', 'wcfm-affiliate-pro'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </table>

                            <?php submit_button($edit_tier ? __('Aggiorna Livello', 'wcfm-affiliate-pro') : __('Aggiungi Livello', 'wcfm-affiliate-pro')); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save tier
     */
    private function save_tier(array $data): void {
        global $wpdb;

        $tier_data = [
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['name']),
            'commission_type' => sanitize_text_field($data['commission_type']),
            'commission_rate' => floatval($data['commission_rate']),
            'minimum_referrals' => intval($data['minimum_referrals']),
            'minimum_earnings' => floatval($data['minimum_earnings']),
            'status' => sanitize_text_field($data['status']),
        ];

        if (!empty($data['tier_id'])) {
            $wpdb->update(
                WCFM_Affiliate_DB::$table_tiers,
                $tier_data,
                ['id' => intval($data['tier_id'])]
            );
        } else {
            $wpdb->insert(WCFM_Affiliate_DB::$table_tiers, $tier_data);
        }
    }

    /**
     * Render creatives page
     */
    public function render_creatives_page(): void {
        global $wpdb;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wcfm_affiliate_creative_nonce'])) {
            if (wp_verify_nonce($_POST['wcfm_affiliate_creative_nonce'], 'wcfm_affiliate_save_creative')) {
                $this->save_creative($_POST);
            }
        }

        // Handle delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete(WCFM_Affiliate_DB::$table_creatives, ['id' => intval($_GET['id'])], ['%d']);
        }

        $creatives = $wpdb->get_results("SELECT * FROM " . WCFM_Affiliate_DB::$table_creatives . " ORDER BY date_created DESC");
        ?>
        <div class="wrap">
            <h1><?php _e('Materiali Promozionali', 'wcfm-affiliate-pro'); ?></h1>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col-full">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Aggiungi Nuovo Materiale', 'wcfm-affiliate-pro'); ?></h3>
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('wcfm_affiliate_save_creative', 'wcfm_affiliate_creative_nonce'); ?>

                            <table class="form-table">
                                <tr>
                                    <th><label for="creative_name"><?php _e('Nome', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="text" name="name" id="creative_name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="creative_type"><?php _e('Tipo', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td>
                                        <select name="type" id="creative_type">
                                            <option value="banner"><?php _e('Banner', 'wcfm-affiliate-pro'); ?></option>
                                            <option value="text_link"><?php _e('Link Testuale', 'wcfm-affiliate-pro'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="creative_url"><?php _e('URL Destinazione', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="url" name="url" id="creative_url" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="creative_image"><?php _e('Immagine Banner', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td>
                                        <input type="text" name="image_url" id="creative_image" class="regular-text">
                                        <button type="button" class="button" id="upload_creative_image">
                                            <?php _e('Carica Immagine', 'wcfm-affiliate-pro'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="creative_text"><?php _e('Testo Link', 'wcfm-affiliate-pro'); ?></label></th>
                                    <td><input type="text" name="text" id="creative_text" class="regular-text"></td>
                                </tr>
                            </table>

                            <?php submit_button(__('Aggiungi Materiale', 'wcfm-affiliate-pro')); ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="wcfm-affiliate-creatives-list">
                <h3><?php _e('Materiali Esistenti', 'wcfm-affiliate-pro'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Anteprima', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Nome', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Tipo', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Click', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($creatives as $creative): ?>
                            <tr>
                                <td>
                                    <?php if ($creative->image_url): ?>
                                        <img src="<?php echo esc_url($creative->image_url); ?>" style="max-width: 100px; max-height: 60px;">
                                    <?php else: ?>
                                        <span><?php echo esc_html($creative->text ?: '-'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($creative->name); ?></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $creative->type))); ?></td>
                                <td><?php echo number_format($creative->clicks); ?></td>
                                <td>
                                    <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo $creative->status === 'active' ? 'approved' : 'rejected'; ?>">
                                        <?php echo esc_html(ucfirst($creative->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo add_query_arg(['action' => 'delete', 'id' => $creative->id]); ?>"
                                       onclick="return confirm('<?php _e('Eliminare questo materiale?', 'wcfm-affiliate-pro'); ?>')">
                                        <?php _e('Elimina', 'wcfm-affiliate-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Save creative
     */
    private function save_creative(array $data): void {
        global $wpdb;

        $wpdb->insert(
            WCFM_Affiliate_DB::$table_creatives,
            [
                'name' => sanitize_text_field($data['name']),
                'type' => sanitize_text_field($data['type']),
                'url' => sanitize_url($data['url']),
                'image_url' => sanitize_url($data['image_url'] ?? ''),
                'text' => sanitize_text_field($data['text'] ?? ''),
                'status' => 'active',
            ]
        );
    }

    /**
     * Render reports page
     */
    public function render_reports_page(): void {
        require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-reports.php';
        $admin_reports = new WCFM_Affiliate_Admin_Reports();
        $admin_reports->render();
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-settings.php';
        $admin_settings = new WCFM_Affiliate_Admin_Settings();
        $admin_settings->render();
    }
}
