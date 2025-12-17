<?php
/**
 * Public Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Public {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_jewe_register_affiliate', [$this, 'ajax_register_affiliate']);
        add_action('wp_ajax_nopriv_jewe_register_affiliate', [$this, 'ajax_register_affiliate']);
        add_action('wp_ajax_jewe_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_jewe_request_payout', [$this, 'ajax_request_payout']);

        // Add affiliate dashboard endpoint
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_dashboard_template']);
    }

    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'jewe-affiliate-public',
            JEWE_AFFILIATE_PLUGIN_URL . 'assets/css/public.css',
            [],
            JEWE_AFFILIATE_VERSION
        );

        wp_enqueue_script(
            'jewe-affiliate-public',
            JEWE_AFFILIATE_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            JEWE_AFFILIATE_VERSION,
            true
        );

        wp_localize_script('jewe-affiliate-public', 'jeweAffiliate', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('jewe-affiliate/v1/'),
            'nonce' => wp_create_nonce('jewe_public_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'is_logged_in' => is_user_logged_in(),
            'strings' => [
                'loading' => __('Caricamento...', 'jewe-affiliate-pro'),
                'error' => __('Si è verificato un errore', 'jewe-affiliate-pro'),
                'copied' => __('Copiato negli appunti!', 'jewe-affiliate-pro'),
                'confirm_payout' => __('Confermi la richiesta di pagamento?', 'jewe-affiliate-pro'),
            ],
        ]);
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^affiliate-dashboard/?$', 'index.php?jewe_affiliate_dashboard=1', 'top');
        add_rewrite_rule('^affiliate-register/?$', 'index.php?jewe_affiliate_register=1', 'top');
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'jewe_affiliate_dashboard';
        $vars[] = 'jewe_affiliate_register';
        return $vars;
    }

    /**
     * Handle dashboard template
     */
    public function handle_dashboard_template() {
        if (get_query_var('jewe_affiliate_dashboard')) {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/affiliate-dashboard/')));
                exit;
            }

            $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
            if (!$affiliate) {
                wp_redirect(home_url('/affiliate-register/'));
                exit;
            }

            include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/public/dashboard.php';
            exit;
        }

        if (get_query_var('jewe_affiliate_register')) {
            if (is_user_logged_in()) {
                $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
                if ($affiliate) {
                    wp_redirect(home_url('/affiliate-dashboard/'));
                    exit;
                }
            }

            include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/public/register.php';
            exit;
        }
    }

    /**
     * AJAX: Register affiliate
     */
    public function ajax_register_affiliate() {
        check_ajax_referer('jewe_public_nonce', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            // Create user if not logged in
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

            if (!$email || !$password) {
                wp_send_json_error(['message' => __('Email e password richieste', 'jewe-affiliate-pro')]);
            }

            if (email_exists($email)) {
                wp_send_json_error(['message' => __('Email già registrata', 'jewe-affiliate-pro')]);
            }

            $user_id = wp_create_user($email, $password, $email);

            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => $user_id->get_error_message()]);
            }

            if ($name) {
                wp_update_user(['ID' => $user_id, 'display_name' => $name]);
            }

            // Log in the user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }

        // Get referrer code from cookie
        $referrer_code = isset($_COOKIE['jewe_affiliate_ref']) ? sanitize_text_field($_COOKIE['jewe_affiliate_ref']) : '';

        // Create affiliate
        $result = JEWE_Affiliate::create($user_id, $referrer_code);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Registrazione completata!', 'jewe-affiliate-pro'),
            'redirect' => home_url('/affiliate-dashboard/'),
        ]);
    }

    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('jewe_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Devi essere loggato', 'jewe-affiliate-pro')]);
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'jewe-affiliate-pro')]);
        }

        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : 'overview';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';

        $data = [];

        switch ($section) {
            case 'overview':
                $data['stats'] = JEWE_Affiliate::get_stats($affiliate->id, $period);
                $data['kpis'] = JEWE_Affiliate_Analytics::get_dashboard_kpis($affiliate->id, $period);
                $data['insights'] = JEWE_Affiliate_AI_Insights::get_insights($affiliate->id);
                $data['score'] = JEWE_Affiliate_AI_Insights::get_overall_score($affiliate->id);
                break;

            case 'commissions':
                $data['commissions'] = JEWE_Affiliate_Database::get_commissions($affiliate->id, [
                    'limit' => 50,
                ]);
                $data['summary'] = JEWE_Affiliate_Commission::get_summary($affiliate->id);
                break;

            case 'traffic':
                $data['stats'] = JEWE_Affiliate_Tracking::get_stats($affiliate->id, $period);
                $data['by_source'] = JEWE_Affiliate_Tracking::get_traffic_by_source($affiliate->id, $period);
                $data['by_device'] = JEWE_Affiliate_Tracking::get_traffic_by_device($affiliate->id, $period);
                break;

            case 'team':
                $data['downline'] = JEWE_Affiliate_MLM::get_downline($affiliate->id);
                $data['stats'] = JEWE_Affiliate_MLM::get_stats($affiliate->id);
                $data['performance'] = JEWE_Affiliate_MLM::get_team_performance($affiliate->id, $period);
                break;

            case 'badges':
                $data['badges'] = JEWE_Affiliate_Gamification::get_badges_with_progress($affiliate->id);
                $data['tier_progress'] = JEWE_Affiliate_Gamification::get_tier_progress($affiliate->id);
                break;
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX: Request payout
     */
    public function ajax_request_payout() {
        check_ajax_referer('jewe_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Devi essere loggato', 'jewe-affiliate-pro')]);
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'jewe-affiliate-pro')]);
        }

        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'paypal';
        $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';

        // Validate
        $min_payout = floatval(get_option('jewe_affiliate_min_payout', 50));

        if ($amount < $min_payout) {
            wp_send_json_error(['message' => sprintf(__('Importo minimo: €%s', 'jewe-affiliate-pro'), $min_payout)]);
        }

        if ($amount > $affiliate->current_balance) {
            wp_send_json_error(['message' => __('Saldo insufficiente', 'jewe-affiliate-pro')]);
        }

        // Create payout request
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'jewe_payouts', [
            'affiliate_id' => $affiliate->id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_details' => $details,
            'status' => 'pending',
        ]);

        if ($result) {
            wp_send_json_success([
                'message' => __('Richiesta di pagamento inviata!', 'jewe-affiliate-pro'),
            ]);
        }

        wp_send_json_error(['message' => __('Errore durante la richiesta', 'jewe-affiliate-pro')]);
    }
}
