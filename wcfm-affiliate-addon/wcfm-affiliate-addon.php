<?php
/**
 * Plugin Name: WCFM Affiliate Pro (Independent)
 * Plugin URI: https://aiedintorni.it/wcfm-affiliate-pro
 * Description: Sistema di affiliazione avanzato INDIPENDENTE per WCFM. NON sovrascrive tabelle esistenti. Usa prefisso unico 'wcfm_aff_pro_'. Completamente reversibile - disattiva senza perdere dati esistenti.
 * Version: 1.0.0
 * Author: IPV Production
 * Author URI: https://aiedintorni.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wcfm-affiliate-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * WCFM requires at least: 6.5
 *
 * @package WCFM_Affiliate_Pro
 * @version 1.0.0
 *
 * IMPORTANTE - PLUGIN INDIPENDENTE:
 * ================================
 * Questo plugin usa prefissi UNICI per:
 * - Tabelle DB: wplu_wcfm_aff_pro_* (NON tocca wplu_wcfm_affiliate_* o wplu_affiliate_wp_*)
 * - Opzioni WP: wcfm_aff_pro_* (NON tocca wcfmaf_* o affwp_*)
 * - Cookie: wcfm_aff_pro_ref (NON tocca wcfm_affiliate_ref o affwp_ref)
 * - Cron: wcfm_aff_pro_* (hook separati)
 *
 * La DISATTIVAZIONE non elimina dati - puoi riattivare quando vuoi.
 * Solo la DISINSTALLAZIONE (cancella plugin) rimuove tabelle/opzioni.
 *
 * CHANGELOG:
 * 1.0.0 - 2025-12-17
 * - Initial release (Independent version)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WCFM_AFFILIATE_PRO_VERSION', '1.0.0');
define('WCFM_AFFILIATE_PRO_FILE', __FILE__);
define('WCFM_AFFILIATE_PRO_PATH', plugin_dir_path(__FILE__));
define('WCFM_AFFILIATE_PRO_URL', plugin_dir_url(__FILE__));
define('WCFM_AFFILIATE_PRO_BASENAME', plugin_basename(__FILE__));
define('WCFM_AFFILIATE_PRO_MIN_PHP', '8.0');
define('WCFM_AFFILIATE_PRO_MIN_WP', '6.0');
define('WCFM_AFFILIATE_PRO_MIN_WC', '8.0');

/**
 * Main plugin class - Singleton pattern
 *
 * @since 1.0.0
 */
final class WCFM_Affiliate_Pro {

    /**
     * Single instance of the class
     * @var WCFM_Affiliate_Pro|null
     */
    private static ?WCFM_Affiliate_Pro $instance = null;

    /**
     * Plugin components
     */
    public ?WCFM_Affiliate_DB $db = null;
    public ?WCFM_Affiliate_Manager $affiliates = null;
    public ?WCFM_Affiliate_Commission $commissions = null;
    public ?WCFM_Affiliate_Referral $referrals = null;
    public ?WCFM_Affiliate_Payout $payouts = null;
    public ?WCFM_Affiliate_Dashboard $dashboard = null;
    public ?WCFM_Affiliate_Admin $admin = null;
    public ?WCFM_Affiliate_Admin_Network $network = null;
    public ?WCFM_Affiliate_WCFM_Integration $wcfm = null;
    public ?WCFM_Affiliate_Reports $reports = null;
    public ?WCFM_Affiliate_Emails $emails = null;
    public ?WCFM_Affiliate_REST_API $api = null;
    public ?WCFM_Affiliate_Coupons $coupons = null;
    public ?WCFM_Affiliate_Dual_Role $dual_role = null;

    /**
     * Get single instance
     *
     * @return WCFM_Affiliate_Pro
     */
    public static function instance(): WCFM_Affiliate_Pro {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {
        $this->check_requirements();
    }

    /**
     * Check system requirements
     */
    private function check_requirements(): void {
        // Check PHP version
        if (version_compare(PHP_VERSION, WCFM_AFFILIATE_PRO_MIN_PHP, '<')) {
            add_action('admin_notices', [$this, 'php_version_notice']);
            return;
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), WCFM_AFFILIATE_PRO_MIN_WP, '<')) {
            add_action('admin_notices', [$this, 'wp_version_notice']);
            return;
        }

        // Initialize plugin
        add_action('plugins_loaded', [$this, 'init'], 20);

        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin
     */
    public function init(): void {
        // Check WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'wc_missing_notice']);
            return;
        }

        // Check WCFM
        if (!class_exists('WCFM') && !class_exists('WCFMmp')) {
            add_action('admin_notices', [$this, 'wcfm_missing_notice']);
            return;
        }

        // Load text domain
        $this->load_textdomain();

        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        $this->init_components();

        // Register hooks
        $this->init_hooks();

        // Plugin loaded action
        do_action('wcfm_affiliate_pro_loaded');
    }

    /**
     * Load plugin text domain
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'wcfm-affiliate-pro',
            false,
            dirname(WCFM_AFFILIATE_PRO_BASENAME) . '/languages'
        );
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void {
        // Core classes
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-db.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-manager.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-commission.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-referral.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-payout.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-dashboard.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-reports.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-emails.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-coupons.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-dual-role.php';

        // WCFM Integration
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-wcfm-integration.php';

        // Admin
        if (is_admin()) {
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-settings.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-affiliates.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-commissions.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-payouts.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-reports.php';
            require_once WCFM_AFFILIATE_PRO_PATH . 'admin/class-wcfm-affiliate-admin-network.php';
        }

        // Frontend
        require_once WCFM_AFFILIATE_PRO_PATH . 'frontend/class-wcfm-affiliate-frontend.php';
        require_once WCFM_AFFILIATE_PRO_PATH . 'frontend/class-wcfm-affiliate-shortcodes.php';

        // API
        require_once WCFM_AFFILIATE_PRO_PATH . 'api/class-wcfm-affiliate-rest-api.php';
    }

    /**
     * Initialize components
     */
    private function init_components(): void {
        $this->db = new WCFM_Affiliate_DB();
        $this->affiliates = new WCFM_Affiliate_Manager();
        $this->commissions = new WCFM_Affiliate_Commission();
        $this->referrals = new WCFM_Affiliate_Referral();
        $this->payouts = new WCFM_Affiliate_Payout();
        $this->dashboard = new WCFM_Affiliate_Dashboard();
        $this->reports = new WCFM_Affiliate_Reports();
        $this->emails = new WCFM_Affiliate_Emails();
        $this->coupons = new WCFM_Affiliate_Coupons();
        $this->dual_role = new WCFM_Affiliate_Dual_Role();
        $this->wcfm = new WCFM_Affiliate_WCFM_Integration();
        $this->api = new WCFM_Affiliate_REST_API();

        if (is_admin()) {
            $this->admin = new WCFM_Affiliate_Admin();
            $this->network = new WCFM_Affiliate_Admin_Network();
        }
    }

    /**
     * Register plugin hooks
     */
    private function init_hooks(): void {
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers - usa prefisso unico per evitare conflitti
        add_action('wp_ajax_wcfm_aff_pro_action', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_wcfm_aff_pro_action', [$this, 'handle_ajax_nopriv']);

        // Cron jobs - usa prefisso unico per evitare conflitti
        add_action('wcfm_aff_pro_daily_cron', [$this, 'run_daily_tasks']);
        add_action('wcfm_aff_pro_hourly_cron', [$this, 'run_hourly_tasks']);

        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', [$this, 'process_order_commission'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_order_refund'], 10, 1);

        // Plugin action links
        add_filter('plugin_action_links_' . WCFM_AFFILIATE_PRO_BASENAME, [$this, 'plugin_action_links']);
    }

    /**
     * Plugin activation
     */
    public function activate(): void {
        // Check requirements
        if (version_compare(PHP_VERSION, WCFM_AFFILIATE_PRO_MIN_PHP, '<')) {
            deactivate_plugins(WCFM_AFFILIATE_PRO_BASENAME);
            wp_die(
                sprintf(
                    __('WCFM Affiliate Pro richiede PHP %s o superiore.', 'wcfm-affiliate-pro'),
                    WCFM_AFFILIATE_PRO_MIN_PHP
                )
            );
        }

        // Create database tables
        require_once WCFM_AFFILIATE_PRO_PATH . 'includes/class-wcfm-affiliate-db.php';
        WCFM_Affiliate_DB::create_tables();

        // Set default options
        $this->set_default_options();

        // Create affiliate role
        $this->create_affiliate_role();

        // Schedule cron jobs
        $this->schedule_cron_jobs();

        // Create pages
        $this->create_pages();

        // Set activation flag
        set_transient('wcfm_affiliate_pro_activated', true, 30);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     *
     * NOTA: La disattivazione NON elimina dati.
     * Tabelle e opzioni restano intatte per permettere riattivazione.
     * Solo l'eliminazione completa del plugin (uninstall) rimuove i dati.
     */
    public function deactivate(): void {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('wcfm_aff_pro_daily_cron');
        wp_clear_scheduled_hook('wcfm_aff_pro_hourly_cron');

        // Flush rewrite rules
        flush_rewrite_rules();

        // NON eliminiamo tabelle o opzioni - il plugin è reversibile
    }

    /**
     * Set default plugin options
     *
     * Usa prefisso 'wcfm_aff_pro_' per TUTTE le opzioni
     * per evitare conflitti con WCFM Affiliate esistente
     */
    private function set_default_options(): void {
        $defaults = [
            // Usa prefisso UNICO wcfm_aff_pro_ per tutte le opzioni
            'wcfm_aff_pro_general' => [
                'enable' => 'yes',
                'registration_type' => 'approval', // auto, approval, invite
                'cookie_duration' => 30, // days
                'cookie_name' => 'wcfm_aff_pro_ref', // Cookie unico!
                'referral_var' => 'refpro', // Variabile URL unica!
                'credit_last_referrer' => 'yes',
                'require_approval' => 'yes',
                'auto_approve_vendors' => 'no',
                'auto_register_vendors' => 'no', // Auto-register new vendors as affiliates
                'minimum_payout' => 50,
                'payout_methods' => ['paypal', 'bank_transfer'],
                'payout_schedule' => 'monthly',
                'default_status' => 'pending',
                'allow_self_referral' => 'no',
            ],
            'wcfm_aff_pro_commission' => [
                'type' => 'percentage', // percentage, flat, tiered
                'rate' => 10,
                'flat_amount' => 5,
                'per_product' => 'no',
                'per_category' => 'no',
                'per_vendor' => 'no',
                'recurring' => 'no',
                'recurring_rate' => 5,
                'recurring_duration' => 12, // months
                'lifetime_referrals' => 'no',
                'commission_status' => 'pending', // pending, approved
                'approval_method' => 'manual', // manual, auto, delay
                'approval_delay' => 30, // days
                'exclude_shipping' => 'yes',
                'exclude_tax' => 'yes',
                'exclude_discounts' => 'yes',
            ],
            'wcfm_aff_pro_mlm' => [
                'enable' => 'no',
                'levels' => 3,
                'level_rates' => [10, 5, 2],
                'override_bonus' => 'no',
                'override_rate' => 2,
            ],
            'wcfm_aff_pro_notifications' => [
                'admin_new_affiliate' => 'yes',
                'admin_new_referral' => 'yes',
                'admin_payout_request' => 'yes',
                'affiliate_approved' => 'yes',
                'affiliate_rejected' => 'yes',
                'affiliate_new_referral' => 'yes',
                'affiliate_commission_approved' => 'yes',
                'affiliate_payout_sent' => 'yes',
            ],
            'wcfm_aff_pro_pages' => [
                'dashboard' => 0,
                'registration' => 0,
                'login' => 0,
            ],
            'wcfm_aff_pro_design' => [
                'primary_color' => '#00897b',
                'secondary_color' => '#26a69a',
                'dashboard_style' => 'modern',
            ],
        ];

        foreach ($defaults as $option_name => $option_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }

        // Set version con nome opzione unico
        update_option('wcfm_aff_pro_version', WCFM_AFFILIATE_PRO_VERSION);
    }

    /**
     * Create affiliate user role
     *
     * Usa nome ruolo unico per non conflittare con wcfm_affiliate esistente
     */
    private function create_affiliate_role(): void {
        // Add affiliate role con nome UNICO
        add_role(
            'wcfm_aff_pro', // Nome ruolo unico!
            __('Affiliato Pro', 'wcfm-affiliate-pro'),
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'view_aff_pro_dashboard' => true,
                'view_aff_pro_reports' => true,
                'request_aff_pro_payout' => true,
                'manage_aff_pro_links' => true,
                'view_aff_pro_coupons' => true,
            ]
        );

        // Add capabilities to admin - usa nomi unici
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_aff_pro');
            $admin->add_cap('approve_aff_pro');
            $admin->add_cap('manage_aff_pro_commissions');
            $admin->add_cap('manage_aff_pro_payouts');
            $admin->add_cap('view_aff_pro_reports');
            $admin->add_cap('edit_aff_pro_settings');
        }

        // Add capabilities to shop manager
        $shop_manager = get_role('shop_manager');
        if ($shop_manager) {
            $shop_manager->add_cap('manage_aff_pro');
            $shop_manager->add_cap('approve_aff_pro');
            $shop_manager->add_cap('manage_aff_pro_commissions');
            $shop_manager->add_cap('view_aff_pro_reports');
        }
    }

    /**
     * Schedule cron jobs - usa nomi hook unici
     */
    private function schedule_cron_jobs(): void {
        // Daily cron - nome unico
        if (!wp_next_scheduled('wcfm_aff_pro_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'wcfm_aff_pro_daily_cron');
        }

        // Hourly cron - nome unico
        if (!wp_next_scheduled('wcfm_aff_pro_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'wcfm_aff_pro_hourly_cron');
        }
    }

    /**
     * Create required pages
     *
     * Usa slug UNICI per evitare conflitti con pagine esistenti
     * Usa shortcode UNICI per evitare conflitti con altri plugin
     */
    private function create_pages(): void {
        $pages = [
            'affiliate-pro-dashboard' => [  // Slug unico!
                'title' => __('Dashboard Affiliato Pro', 'wcfm-affiliate-pro'),
                'content' => '[wcfm_aff_pro_dashboard]',  // Shortcode unico!
                'key' => 'dashboard',
            ],
            'affiliate-pro-registration' => [  // Slug unico!
                'title' => __('Registrazione Affiliato Pro', 'wcfm-affiliate-pro'),
                'content' => '[wcfm_aff_pro_registration]',  // Shortcode unico!
                'key' => 'registration',
            ],
            'affiliate-pro-login' => [  // Slug unico!
                'title' => __('Login Affiliato Pro', 'wcfm-affiliate-pro'),
                'content' => '[wcfm_aff_pro_login]',  // Shortcode unico!
                'key' => 'login',
            ],
        ];

        $page_options = get_option('wcfm_aff_pro_pages', []);  // Opzione unica!

        foreach ($pages as $slug => $page_data) {
            // Check if page exists
            $page = get_page_by_path($slug);

            if (!$page) {
                // Create page
                $page_id = wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                ]);

                if ($page_id && !is_wp_error($page_id)) {
                    $page_options[$page_data['key']] = $page_id;
                }
            } else {
                $page_options[$page_data['key']] = $page->ID;
            }
        }

        update_option('wcfm_aff_pro_pages', $page_options);  // Opzione unica!
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        // CSS
        wp_enqueue_style(
            'wcfm-affiliate-pro-frontend',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/frontend.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );

        // JS
        wp_enqueue_script(
            'wcfm-affiliate-pro-frontend',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/frontend.js',
            ['jquery'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wcfm-affiliate-pro-frontend', 'wcfm_affiliate_pro', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_pro_nonce'),
            'i18n' => [
                'copied' => __('Link copiato!', 'wcfm-affiliate-pro'),
                'error' => __('Si è verificato un errore', 'wcfm-affiliate-pro'),
                'confirm_delete' => __('Sei sicuro di voler eliminare?', 'wcfm-affiliate-pro'),
                'loading' => __('Caricamento...', 'wcfm-affiliate-pro'),
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(string $hook): void {
        // Only on plugin pages
        if (strpos($hook, 'wcfm-affiliate') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'wcfm-affiliate-pro-admin',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/admin.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // JS
        wp_enqueue_script(
            'wcfm-affiliate-pro-admin',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/admin.js',
            ['jquery', 'chartjs'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wcfm-affiliate-pro-admin', 'wcfm_affiliate_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_admin_nonce'),
            'i18n' => [
                'confirm_approve' => __('Approvare questo affiliato?', 'wcfm-affiliate-pro'),
                'confirm_reject' => __('Rifiutare questo affiliato?', 'wcfm-affiliate-pro'),
                'confirm_delete' => __('Eliminare questo elemento?', 'wcfm-affiliate-pro'),
                'confirm_payout' => __('Confermare il pagamento?', 'wcfm-affiliate-pro'),
                'processing' => __('Elaborazione...', 'wcfm-affiliate-pro'),
                'success' => __('Operazione completata', 'wcfm-affiliate-pro'),
                'error' => __('Si è verificato un errore', 'wcfm-affiliate-pro'),
            ],
        ]);
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $action = isset($_POST['sub_action']) ? sanitize_text_field($_POST['sub_action']) : '';

        $allowed_actions = [
            'get_stats',
            'copy_link',
            'request_payout',
            'update_profile',
            'generate_coupon',
        ];

        if (!in_array($action, $allowed_actions)) {
            wp_send_json_error(['message' => __('Azione non consentita', 'wcfm-affiliate-pro')]);
        }

        do_action('wcfm_affiliate_ajax_' . $action);
    }

    /**
     * Handle AJAX requests for non-logged users
     */
    public function handle_ajax_nopriv(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $action = isset($_POST['sub_action']) ? sanitize_text_field($_POST['sub_action']) : '';

        $allowed_actions = [
            'track_click',
            'register',
        ];

        if (!in_array($action, $allowed_actions)) {
            wp_send_json_error(['message' => __('Azione non consentita', 'wcfm-affiliate-pro')]);
        }

        do_action('wcfm_affiliate_ajax_nopriv_' . $action);
    }

    /**
     * Process order commission
     */
    public function process_order_commission(int $order_id): void {
        if ($this->commissions) {
            $this->commissions->process_order($order_id);
        }
    }

    /**
     * Handle order refund
     */
    public function handle_order_refund(int $order_id): void {
        if ($this->commissions) {
            $this->commissions->handle_refund($order_id);
        }
    }

    /**
     * Run daily tasks
     */
    public function run_daily_tasks(): void {
        // Auto-approve commissions after delay
        if ($this->commissions) {
            $this->commissions->auto_approve_pending();
        }

        // Process scheduled payouts
        if ($this->payouts) {
            $this->payouts->process_scheduled();
        }

        // Clean old tracking data
        if ($this->referrals) {
            $this->referrals->cleanup_old_data();
        }

        // Send summary emails
        if ($this->emails) {
            $this->emails->send_daily_summary();
        }

        do_action('wcfm_affiliate_daily_tasks');
    }

    /**
     * Run hourly tasks
     */
    public function run_hourly_tasks(): void {
        // Update statistics
        if ($this->reports) {
            $this->reports->update_stats();
        }

        do_action('wcfm_affiliate_hourly_tasks');
    }

    /**
     * Plugin action links
     */
    public function plugin_action_links(array $links): array {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wcfm-affiliate-settings') . '">' . __('Impostazioni', 'wcfm-affiliate-pro') . '</a>',
            '<a href="' . admin_url('admin.php?page=wcfm-affiliate-dashboard') . '">' . __('Dashboard', 'wcfm-affiliate-pro') . '</a>',
        ];

        return array_merge($plugin_links, $links);
    }

    /**
     * PHP version notice
     */
    public function php_version_notice(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    __('<strong>WCFM Affiliate Pro</strong> richiede PHP versione %s o superiore. Attualmente stai usando PHP %s.', 'wcfm-affiliate-pro'),
                    WCFM_AFFILIATE_PRO_MIN_PHP,
                    PHP_VERSION
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * WordPress version notice
     */
    public function wp_version_notice(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    __('<strong>WCFM Affiliate Pro</strong> richiede WordPress versione %s o superiore.', 'wcfm-affiliate-pro'),
                    WCFM_AFFILIATE_PRO_MIN_WP
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * WooCommerce missing notice
     */
    public function wc_missing_notice(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                _e('<strong>WCFM Affiliate Pro</strong> richiede WooCommerce attivo per funzionare.', 'wcfm-affiliate-pro');
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * WCFM missing notice
     */
    public function wcfm_missing_notice(): void {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php
                _e('<strong>WCFM Affiliate Pro</strong> funziona meglio con WCFM Marketplace attivo. Alcune funzionalità multivendor potrebbero non essere disponibili.', 'wcfm-affiliate-pro');
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get plugin option
     */
    public static function get_option(string $group, string $key = '', $default = null) {
        $options = get_option($group, []);

        if (empty($key)) {
            return $options;
        }

        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Update plugin option
     */
    public static function update_option(string $group, string $key, $value): bool {
        $options = get_option($group, []);
        $options[$key] = $value;
        return update_option($group, $options);
    }
}

/**
 * Returns the main instance of WCFM_Affiliate_Pro
 *
 * @return WCFM_Affiliate_Pro
 */
function wcfm_affiliate_pro(): WCFM_Affiliate_Pro {
    return WCFM_Affiliate_Pro::instance();
}

// Initialize plugin
wcfm_affiliate_pro();
