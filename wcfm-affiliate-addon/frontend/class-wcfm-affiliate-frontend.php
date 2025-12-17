<?php
/**
 * Frontend Handler
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_wcfm_affiliate_dashboard_action', [$this, 'handle_ajax']);
        add_action('wp_ajax_wcfm_affiliate_generate_link', [$this, 'ajax_generate_link']);
        add_action('wp_ajax_wcfm_affiliate_request_payout', [$this, 'ajax_request_payout']);
        add_action('wp_ajax_wcfm_affiliate_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_wcfm_affiliate_get_stats', [$this, 'ajax_get_stats']);
        add_action('template_redirect', [$this, 'handle_registration']);
    }

    public function enqueue_scripts(): void {
        if (!$this->is_affiliate_page()) {
            return;
        }

        wp_enqueue_style(
            'wcfm-affiliate-frontend',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/frontend.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );

        // Chart.js for stats
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_enqueue_script(
            'wcfm-affiliate-frontend',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/frontend.js',
            ['jquery', 'chartjs'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        wp_localize_script('wcfm-affiliate-frontend', 'wcfm_affiliate_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_frontend_nonce'),
            'i18n' => [
                'copied' => __('Copiato!', 'wcfm-affiliate-pro'),
                'copy_failed' => __('Copia fallita', 'wcfm-affiliate-pro'),
                'confirm_payout' => __('Sei sicuro di voler richiedere il pagamento?', 'wcfm-affiliate-pro'),
                'loading' => __('Caricamento...', 'wcfm-affiliate-pro'),
                'error' => __('Si è verificato un errore', 'wcfm-affiliate-pro'),
                'success' => __('Operazione completata', 'wcfm-affiliate-pro'),
            ],
        ]);
    }

    private function is_affiliate_page(): bool {
        $dashboard_page = get_option('wcfm_affiliate_dashboard_page');
        $registration_page = get_option('wcfm_affiliate_registration_page');

        return is_page($dashboard_page) || is_page($registration_page);
    }

    public function handle_registration(): void {
        if (!isset($_POST['wcfm_affiliate_register'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wcfm_affiliate_nonce'] ?? '', 'wcfm_affiliate_register')) {
            return;
        }

        $errors = [];

        // Validate fields
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $website = esc_url_raw($_POST['website'] ?? '');
        $payment_email = sanitize_email($_POST['payment_email'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');

        if (empty($first_name)) {
            $errors[] = __('Il nome è obbligatorio.', 'wcfm-affiliate-pro');
        }

        if (empty($email) || !is_email($email)) {
            $errors[] = __('Inserisci un indirizzo email valido.', 'wcfm-affiliate-pro');
        }

        if (email_exists($email)) {
            $errors[] = __('Questo indirizzo email è già registrato.', 'wcfm-affiliate-pro');
        }

        if (empty($username)) {
            $errors[] = __('Il nome utente è obbligatorio.', 'wcfm-affiliate-pro');
        }

        if (username_exists($username)) {
            $errors[] = __('Questo nome utente è già in uso.', 'wcfm-affiliate-pro');
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = __('La password deve essere di almeno 6 caratteri.', 'wcfm-affiliate-pro');
        }

        if (!empty($errors)) {
            WC()->session->set('wcfm_affiliate_registration_errors', $errors);
            return;
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            WC()->session->set('wcfm_affiliate_registration_errors', [$user_id->get_error_message()]);
            return;
        }

        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ]);

        // Create affiliate
        $affiliate_data = [
            'user_id' => $user_id,
            'payment_email' => $payment_email ?: $email,
            'payment_method' => $payment_method,
            'website_url' => $website,
        ];

        // Check for referrer
        $referrer_code = isset($_COOKIE['wcfm_affiliate_ref']) ? sanitize_text_field($_COOKIE['wcfm_affiliate_ref']) : '';
        if ($referrer_code) {
            $referrer = wcfm_affiliate_pro()->affiliates->get_affiliate_by_code($referrer_code);
            if ($referrer) {
                $affiliate_data['parent_affiliate_id'] = $referrer->id;
            }
        }

        $affiliate_id = wcfm_affiliate_pro()->affiliates->create_affiliate($affiliate_data);

        if ($affiliate_id) {
            // Send notification emails
            wcfm_affiliate_pro()->emails->send_registration_notification($affiliate_id);
            wcfm_affiliate_pro()->emails->send_registration_admin_notification($affiliate_id);

            // Log in the user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to dashboard
            $dashboard_page = get_option('wcfm_affiliate_dashboard_page');
            wp_redirect(get_permalink($dashboard_page));
            exit;
        }

        WC()->session->set('wcfm_affiliate_registration_errors', [__('Errore durante la registrazione.', 'wcfm-affiliate-pro')]);
    }

    public function handle_ajax(): void {
        check_ajax_referer('wcfm_affiliate_frontend_nonce', 'nonce');

        $action = sanitize_text_field($_POST['dashboard_action'] ?? '');

        switch ($action) {
            case 'get_referrals':
                $this->ajax_get_referrals();
                break;
            case 'get_payouts':
                $this->ajax_get_payouts();
                break;
            default:
                wp_send_json_error(['message' => __('Azione non valida', 'wcfm-affiliate-pro')]);
        }
    }

    public function ajax_generate_link(): void {
        check_ajax_referer('wcfm_affiliate_frontend_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $url = esc_url_raw($_POST['url'] ?? home_url());
        $campaign = sanitize_text_field($_POST['campaign'] ?? '');

        $link = wcfm_affiliate_pro()->referrals->generate_referral_link($affiliate->id, $url, $campaign);

        wp_send_json_success(['link' => $link]);
    }

    public function ajax_request_payout(): void {
        check_ajax_referer('wcfm_affiliate_frontend_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $amount = floatval($_POST['amount'] ?? 0);
        $method = sanitize_text_field($_POST['method'] ?? $affiliate->payment_method);

        if ($amount <= 0) {
            $amount = $affiliate->earnings_balance;
        }

        $result = wcfm_affiliate_pro()->payouts->request_payout($affiliate->id, $amount, $method);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Richiesta di pagamento inviata con successo', 'wcfm-affiliate-pro'),
            'payout_id' => $result,
        ]);
    }

    public function ajax_save_settings(): void {
        check_ajax_referer('wcfm_affiliate_frontend_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $data = [];

        if (isset($_POST['payment_email'])) {
            $data['payment_email'] = sanitize_email($_POST['payment_email']);
        }

        if (isset($_POST['payment_method'])) {
            $data['payment_method'] = sanitize_text_field($_POST['payment_method']);
        }

        if (isset($_POST['website_url'])) {
            $data['website_url'] = esc_url_raw($_POST['website_url']);
        }

        // Bank details
        if ($_POST['payment_method'] === 'bank_transfer') {
            $bank_details = [
                'account_name' => sanitize_text_field($_POST['bank_account_name'] ?? ''),
                'iban' => sanitize_text_field($_POST['bank_iban'] ?? ''),
                'bic_swift' => sanitize_text_field($_POST['bank_bic'] ?? ''),
                'bank_name' => sanitize_text_field($_POST['bank_name'] ?? ''),
            ];
            $data['payment_details'] = json_encode($bank_details);
        }

        $result = wcfm_affiliate_pro()->affiliates->update_affiliate($affiliate->id, $data);

        if ($result) {
            wp_send_json_success(['message' => __('Impostazioni salvate', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante il salvataggio', 'wcfm-affiliate-pro')]);
        }
    }

    public function ajax_get_stats(): void {
        check_ajax_referer('wcfm_affiliate_frontend_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $stats = wcfm_affiliate_pro()->reports->get_affiliate_stats($affiliate->id, $period);
        $chart_data = wcfm_affiliate_pro()->reports->get_affiliate_chart_data($affiliate->id, $period);

        wp_send_json_success([
            'stats' => $stats,
            'chart' => $chart_data,
        ]);
    }

    private function ajax_get_referrals(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = 10;

        $referrals = wcfm_affiliate_pro()->commissions->get_commissions([
            'affiliate_id' => $affiliate->id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->commissions->count_commissions(['affiliate_id' => $affiliate->id]);

        $html = '';
        if (!empty($referrals)) {
            foreach ($referrals as $referral) {
                $html .= sprintf(
                    '<tr>
                        <td>#%d</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td><span class="wcfm-affiliate-status wcfm-affiliate-status-%s">%s</span></td>
                    </tr>',
                    $referral->order_id,
                    wc_price($referral->commission_amount),
                    date_i18n(get_option('date_format'), strtotime($referral->date_created)),
                    esc_attr($referral->status),
                    esc_html(ucfirst($referral->status))
                );
            }
        }

        wp_send_json_success([
            'html' => $html,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }

    private function ajax_get_payouts(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = 10;

        $payouts = wcfm_affiliate_pro()->payouts->get_payouts([
            'affiliate_id' => $affiliate->id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->payouts->count_payouts(['affiliate_id' => $affiliate->id]);

        $html = '';
        if (!empty($payouts)) {
            foreach ($payouts as $payout) {
                $html .= sprintf(
                    '<tr>
                        <td>#%d</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td><span class="wcfm-affiliate-status wcfm-affiliate-status-%s">%s</span></td>
                        <td>%s</td>
                    </tr>',
                    $payout->id,
                    wc_price($payout->amount),
                    esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))),
                    esc_attr($payout->status === 'completed' ? 'approved' : $payout->status),
                    esc_html(ucfirst($payout->status)),
                    date_i18n(get_option('date_format'), strtotime($payout->date_created))
                );
            }
        }

        wp_send_json_success([
            'html' => $html,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }
}
