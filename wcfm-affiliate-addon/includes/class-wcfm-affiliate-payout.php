<?php
/**
 * Payout management system
 *
 * Gestisce richieste di pagamento, elaborazione e tracking dei payout.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Payout
 */
class WCFM_Affiliate_Payout {

    /**
     * Settings
     */
    private array $settings;

    /**
     * Payment methods
     */
    private array $payment_methods = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('wcfm_affiliate_general', []);
        $this->register_payment_methods();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Affiliate payout request
        add_action('wp_ajax_wcfm_affiliate_request_payout', [$this, 'handle_payout_request']);

        // Admin payout processing
        add_action('wp_ajax_wcfm_affiliate_process_payout', [$this, 'handle_process_payout']);
        add_action('wp_ajax_wcfm_affiliate_cancel_payout', [$this, 'handle_cancel_payout']);
        add_action('wp_ajax_wcfm_affiliate_bulk_payout', [$this, 'handle_bulk_payout']);

        // Scheduled payouts
        add_action('wcfm_affiliate_process_scheduled_payouts', [$this, 'process_scheduled']);
    }

    /**
     * Register payment methods
     */
    private function register_payment_methods(): void {
        $this->payment_methods = [
            'paypal' => [
                'name' => __('PayPal', 'wcfm-affiliate-pro'),
                'icon' => 'paypal',
                'fields' => [
                    'email' => [
                        'label' => __('Email PayPal', 'wcfm-affiliate-pro'),
                        'type' => 'email',
                        'required' => true,
                    ],
                ],
                'supports_mass_pay' => true,
            ],
            'bank_transfer' => [
                'name' => __('Bonifico Bancario', 'wcfm-affiliate-pro'),
                'icon' => 'bank',
                'fields' => [
                    'account_name' => [
                        'label' => __('Intestatario Conto', 'wcfm-affiliate-pro'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'iban' => [
                        'label' => __('IBAN', 'wcfm-affiliate-pro'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'bic_swift' => [
                        'label' => __('BIC/SWIFT', 'wcfm-affiliate-pro'),
                        'type' => 'text',
                        'required' => false,
                    ],
                    'bank_name' => [
                        'label' => __('Nome Banca', 'wcfm-affiliate-pro'),
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'supports_mass_pay' => false,
            ],
            'stripe' => [
                'name' => __('Stripe', 'wcfm-affiliate-pro'),
                'icon' => 'stripe',
                'fields' => [
                    'account_id' => [
                        'label' => __('ID Account Stripe', 'wcfm-affiliate-pro'),
                        'type' => 'text',
                        'required' => true,
                    ],
                ],
                'supports_mass_pay' => true,
            ],
            'store_credit' => [
                'name' => __('Credito Negozio', 'wcfm-affiliate-pro'),
                'icon' => 'credit',
                'fields' => [],
                'supports_mass_pay' => true,
            ],
        ];

        $this->payment_methods = apply_filters('wcfm_affiliate_payment_methods', $this->payment_methods);
    }

    /**
     * Get payment methods
     */
    public function get_payment_methods(): array {
        $enabled = $this->settings['payout_methods'] ?? ['paypal', 'bank_transfer'];

        return array_filter($this->payment_methods, function($key) use ($enabled) {
            return in_array($key, $enabled);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Handle payout request
     */
    public function handle_payout_request(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_send_json_error(['message' => __('Non sei un affiliato attivo', 'wcfm-affiliate-pro')]);
        }

        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');

        // Validate minimum payout
        $minimum = floatval($this->settings['minimum_payout'] ?? 50);

        if ($amount < $minimum) {
            wp_send_json_error([
                'message' => sprintf(__('Il pagamento minimo è %s', 'wcfm-affiliate-pro'), wc_price($minimum)),
            ]);
        }

        // Check available balance
        if ($amount > $affiliate->earnings_balance) {
            wp_send_json_error([
                'message' => __('Saldo insufficiente', 'wcfm-affiliate-pro'),
            ]);
        }

        // Validate payment method
        $available_methods = $this->get_payment_methods();
        if (!isset($available_methods[$payment_method])) {
            wp_send_json_error([
                'message' => __('Metodo di pagamento non valido', 'wcfm-affiliate-pro'),
            ]);
        }

        // Check for pending payout
        if ($this->has_pending_payout($affiliate->id)) {
            wp_send_json_error([
                'message' => __('Hai già una richiesta di pagamento in sospeso', 'wcfm-affiliate-pro'),
            ]);
        }

        // Create payout request
        $payout_id = $this->create_payout($affiliate, $amount, $payment_method);

        if ($payout_id) {
            wp_send_json_success([
                'message' => __('Richiesta di pagamento inviata con successo', 'wcfm-affiliate-pro'),
                'payout_id' => $payout_id,
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Errore durante la creazione della richiesta', 'wcfm-affiliate-pro'),
            ]);
        }
    }

    /**
     * Create payout request
     */
    public function create_payout(object $affiliate, float $amount, string $payment_method): int {
        global $wpdb;

        // Get payment details
        $payment_details = $affiliate->payment_details ? json_decode($affiliate->payment_details, true) : [];

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_payouts,
            [
                'affiliate_id' => $affiliate->id,
                'amount' => $amount,
                'currency' => get_woocommerce_currency(),
                'status' => 'pending',
                'payment_method' => $payment_method,
                'payment_email' => $affiliate->payment_email,
                'payment_details' => wp_json_encode($payment_details),
            ],
            ['%d', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$result) {
            return 0;
        }

        $payout_id = $wpdb->insert_id;

        // Get approved commissions and link to payout
        $commissions = wcfm_affiliate_pro()->commissions->get_commissions([
            'affiliate_id' => $affiliate->id,
            'status' => 'approved',
            'limit' => 0,
        ]);

        $total_linked = 0;

        foreach ($commissions as $commission) {
            if ($total_linked >= $amount) {
                break;
            }

            $link_amount = min($commission->commission_amount, $amount - $total_linked);

            $wpdb->insert(
                WCFM_Affiliate_DB::$table_payout_items,
                [
                    'payout_id' => $payout_id,
                    'referral_id' => $commission->referral_id,
                    'commission_id' => $commission->id,
                    'amount' => $link_amount,
                ],
                ['%d', '%d', '%d', '%f']
            );

            $total_linked += $link_amount;
        }

        // Update affiliate balance (hold the amount)
        wcfm_affiliate_pro()->affiliates->update_earnings($affiliate->id, $amount, 'subtract');

        // Trigger notification
        do_action('wcfm_affiliate_payout_request', $payout_id, $affiliate->id);

        return $payout_id;
    }

    /**
     * Check if affiliate has pending payout
     */
    public function has_pending_payout(int $affiliate_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_payouts . "
             WHERE affiliate_id = %d AND status IN ('pending', 'processing')",
            $affiliate_id
        ));
    }

    /**
     * Get payout
     */
    public function get_payout(int $payout_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_payouts . " WHERE id = %d",
            $payout_id
        ));
    }

    /**
     * Get payouts
     */
    public function get_payouts(array $args = []): array {
        global $wpdb;

        $defaults = [
            'affiliate_id' => 0,
            'status' => '',
            'payment_method' => '',
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
            $where[] = 'p.affiliate_id = %d';
            $values[] = $args['affiliate_id'];
        }

        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "p.status IN ($placeholders)";
                $values = array_merge($values, $args['status']);
            } else {
                $where[] = 'p.status = %s';
                $values[] = $args['status'];
            }
        }

        if (!empty($args['payment_method'])) {
            $where[] = 'p.payment_method = %s';
            $values[] = $args['payment_method'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(p.date_created) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(p.date_created) <= %s';
            $values[] = $args['date_to'];
        }

        $sql = "SELECT p.*, a.affiliate_code, u.display_name as affiliate_name, u.user_email
                FROM " . WCFM_Affiliate_DB::$table_payouts . " p
                LEFT JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON p.affiliate_id = a.id
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p." . esc_sql($args['orderby']) . " " . esc_sql($args['order']);

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
     * Count payouts
     */
    public function count_payouts(array $args = []): int {
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

        $sql = "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_payouts . " WHERE " . implode(' AND ', $where);

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Process payout
     */
    public function process_payout(int $payout_id, array $data = []): bool {
        global $wpdb;

        $payout = $this->get_payout($payout_id);

        if (!$payout || $payout->status !== 'pending') {
            return false;
        }

        // Update to processing
        $wpdb->update(
            WCFM_Affiliate_DB::$table_payouts,
            ['status' => 'processing'],
            ['id' => $payout_id],
            ['%s'],
            ['%d']
        );

        // Process based on payment method
        $result = $this->execute_payment($payout, $data);

        if ($result['success']) {
            // Update payout
            $wpdb->update(
                WCFM_Affiliate_DB::$table_payouts,
                [
                    'status' => 'completed',
                    'transaction_id' => $result['transaction_id'] ?? '',
                    'processed_by' => get_current_user_id(),
                    'processed_at' => current_time('mysql'),
                    'admin_notes' => $data['notes'] ?? '',
                ],
                ['id' => $payout_id],
                ['%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );

            // Update affiliate paid earnings
            wcfm_affiliate_pro()->affiliates->update_earnings($payout->affiliate_id, $payout->amount, 'paid');

            // Update linked commissions
            $wpdb->update(
                WCFM_Affiliate_DB::$table_commissions,
                ['status' => 'paid'],
                ['referral_id' => $wpdb->get_col($wpdb->prepare(
                    "SELECT referral_id FROM " . WCFM_Affiliate_DB::$table_payout_items . " WHERE payout_id = %d",
                    $payout_id
                ))],
                ['%s'],
                ['%d']
            );

            // Update referrals
            $wpdb->query($wpdb->prepare(
                "UPDATE " . WCFM_Affiliate_DB::$table_referrals . " r
                 INNER JOIN " . WCFM_Affiliate_DB::$table_payout_items . " pi ON r.id = pi.referral_id
                 SET r.status = 'paid', r.payout_id = %d
                 WHERE pi.payout_id = %d",
                $payout_id,
                $payout_id
            ));

            // Trigger notification
            do_action('wcfm_affiliate_payout_completed', $payout_id, $payout->affiliate_id);

            return true;
        } else {
            // Mark as failed
            $wpdb->update(
                WCFM_Affiliate_DB::$table_payouts,
                [
                    'status' => 'failed',
                    'admin_notes' => $result['error'] ?? __('Errore durante l\'elaborazione', 'wcfm-affiliate-pro'),
                ],
                ['id' => $payout_id],
                ['%s', '%s'],
                ['%d']
            );

            // Restore affiliate balance
            wcfm_affiliate_pro()->affiliates->update_earnings($payout->affiliate_id, $payout->amount, 'add');

            do_action('wcfm_affiliate_payout_failed', $payout_id, $payout->affiliate_id, $result['error'] ?? '');

            return false;
        }
    }

    /**
     * Execute payment
     */
    private function execute_payment(object $payout, array $data = []): array {
        $method = $payout->payment_method;

        switch ($method) {
            case 'paypal':
                return $this->process_paypal_payout($payout, $data);

            case 'stripe':
                return $this->process_stripe_payout($payout, $data);

            case 'bank_transfer':
                // Manual processing - just mark as completed
                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'] ?? 'MANUAL-' . time(),
                ];

            case 'store_credit':
                return $this->process_store_credit($payout);

            default:
                return apply_filters("wcfm_affiliate_process_{$method}_payout", [
                    'success' => false,
                    'error' => __('Metodo di pagamento non supportato', 'wcfm-affiliate-pro'),
                ], $payout, $data);
        }
    }

    /**
     * Process PayPal payout
     */
    private function process_paypal_payout(object $payout, array $data): array {
        // Check if PayPal API is configured
        $paypal_client_id = get_option('wcfm_affiliate_paypal_client_id');
        $paypal_secret = get_option('wcfm_affiliate_paypal_secret');

        if (!$paypal_client_id || !$paypal_secret) {
            // Manual PayPal transfer
            return [
                'success' => true,
                'transaction_id' => $data['transaction_id'] ?? 'PAYPAL-MANUAL-' . time(),
            ];
        }

        // PayPal API integration (Payouts API)
        $access_token = $this->get_paypal_access_token($paypal_client_id, $paypal_secret);

        if (!$access_token) {
            return [
                'success' => false,
                'error' => __('Impossibile autenticarsi con PayPal', 'wcfm-affiliate-pro'),
            ];
        }

        $payout_data = [
            'sender_batch_header' => [
                'sender_batch_id' => 'WCFM-' . $payout->id . '-' . time(),
                'email_subject' => __('Pagamento commissioni affiliate', 'wcfm-affiliate-pro'),
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($payout->amount, 2, '.', ''),
                        'currency' => $payout->currency,
                    ],
                    'receiver' => $payout->payment_email,
                    'note' => sprintf(__('Pagamento commissioni #%d', 'wcfm-affiliate-pro'), $payout->id),
                    'sender_item_id' => 'ITEM-' . $payout->id,
                ],
            ],
        ];

        $response = wp_remote_post('https://api-m.paypal.com/v1/payments/payouts', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'body' => wp_json_encode($payout_data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['batch_header']['payout_batch_id'])) {
            return [
                'success' => true,
                'transaction_id' => $body['batch_header']['payout_batch_id'],
            ];
        }

        return [
            'success' => false,
            'error' => $body['message'] ?? __('Errore PayPal sconosciuto', 'wcfm-affiliate-pro'),
        ];
    }

    /**
     * Get PayPal access token
     */
    private function get_paypal_access_token(string $client_id, string $secret): ?string {
        $response = wp_remote_post('https://api-m.paypal.com/v1/oauth2/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['access_token'] ?? null;
    }

    /**
     * Process Stripe payout
     */
    private function process_stripe_payout(object $payout, array $data): array {
        $stripe_secret = get_option('wcfm_affiliate_stripe_secret_key');

        if (!$stripe_secret) {
            return [
                'success' => false,
                'error' => __('Stripe non configurato', 'wcfm-affiliate-pro'),
            ];
        }

        $payment_details = json_decode($payout->payment_details, true);
        $stripe_account = $payment_details['account_id'] ?? '';

        if (!$stripe_account) {
            return [
                'success' => false,
                'error' => __('Account Stripe non configurato per l\'affiliato', 'wcfm-affiliate-pro'),
            ];
        }

        // Create Stripe transfer
        $response = wp_remote_post('https://api.stripe.com/v1/transfers', [
            'headers' => [
                'Authorization' => 'Bearer ' . $stripe_secret,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'amount' => intval($payout->amount * 100), // Stripe uses cents
                'currency' => strtolower($payout->currency),
                'destination' => $stripe_account,
                'description' => sprintf(__('Pagamento commissioni #%d', 'wcfm-affiliate-pro'), $payout->id),
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['id'])) {
            return [
                'success' => true,
                'transaction_id' => $body['id'],
            ];
        }

        return [
            'success' => false,
            'error' => $body['error']['message'] ?? __('Errore Stripe sconosciuto', 'wcfm-affiliate-pro'),
        ];
    }

    /**
     * Process store credit
     */
    private function process_store_credit(object $payout): array {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($payout->affiliate_id);

        if (!$affiliate) {
            return [
                'success' => false,
                'error' => __('Affiliato non trovato', 'wcfm-affiliate-pro'),
            ];
        }

        // Add WooCommerce store credit (using WooCommerce Smart Coupons or similar)
        $coupon_code = 'AFFILIATE-CREDIT-' . $payout->id . '-' . wp_generate_password(6, false);

        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount($payout->amount);
        $coupon->set_usage_limit(1);
        $coupon->set_email_restrictions([$affiliate->payment_email]);
        $coupon->set_description(sprintf(__('Credito commissioni affiliate #%d', 'wcfm-affiliate-pro'), $payout->id));
        $coupon->save();

        if ($coupon->get_id()) {
            // Send email with coupon code
            do_action('wcfm_affiliate_store_credit_created', $coupon_code, $payout->amount, $affiliate);

            return [
                'success' => true,
                'transaction_id' => 'CREDIT-' . $coupon->get_id(),
            ];
        }

        return [
            'success' => false,
            'error' => __('Impossibile creare il credito negozio', 'wcfm-affiliate-pro'),
        ];
    }

    /**
     * Cancel payout
     */
    public function cancel_payout(int $payout_id, string $reason = ''): bool {
        global $wpdb;

        $payout = $this->get_payout($payout_id);

        if (!$payout || !in_array($payout->status, ['pending', 'processing'])) {
            return false;
        }

        // Update payout status
        $wpdb->update(
            WCFM_Affiliate_DB::$table_payouts,
            [
                'status' => 'cancelled',
                'admin_notes' => $reason,
            ],
            ['id' => $payout_id],
            ['%s', '%s'],
            ['%d']
        );

        // Restore affiliate balance
        wcfm_affiliate_pro()->affiliates->update_earnings($payout->affiliate_id, $payout->amount, 'add');

        do_action('wcfm_affiliate_payout_cancelled', $payout_id, $payout->affiliate_id, $reason);

        return true;
    }

    /**
     * Handle process payout AJAX
     */
    public function handle_process_payout(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_payouts')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $payout_id = intval($_POST['payout_id'] ?? 0);
        $transaction_id = sanitize_text_field($_POST['transaction_id'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($this->process_payout($payout_id, [
            'transaction_id' => $transaction_id,
            'notes' => $notes,
        ])) {
            wp_send_json_success(['message' => __('Pagamento elaborato con successo', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'elaborazione', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle cancel payout AJAX
     */
    public function handle_cancel_payout(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_payouts')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $payout_id = intval($_POST['payout_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if ($this->cancel_payout($payout_id, $reason)) {
            wp_send_json_success(['message' => __('Pagamento annullato', 'wcfm-affiliate-pro')]);
        } else {
            wp_send_json_error(['message' => __('Errore durante l\'annullamento', 'wcfm-affiliate-pro')]);
        }
    }

    /**
     * Handle bulk payout AJAX
     */
    public function handle_bulk_payout(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_payouts')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');

        if (empty($ids)) {
            wp_send_json_error(['message' => __('Nessun elemento selezionato', 'wcfm-affiliate-pro')]);
        }

        $success = 0;

        foreach ($ids as $id) {
            if ($action === 'process') {
                if ($this->process_payout($id)) {
                    $success++;
                }
            } elseif ($action === 'cancel') {
                if ($this->cancel_payout($id)) {
                    $success++;
                }
            }
        }

        wp_send_json_success([
            'message' => sprintf(__('%d pagamenti elaborati', 'wcfm-affiliate-pro'), $success),
        ]);
    }

    /**
     * Process scheduled payouts
     */
    public function process_scheduled(): void {
        $schedule = $this->settings['payout_schedule'] ?? 'monthly';
        $minimum = floatval($this->settings['minimum_payout'] ?? 50);

        // Get affiliates with balance >= minimum
        global $wpdb;

        $affiliates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_affiliates . "
             WHERE status = 'active' AND earnings_balance >= %f",
            $minimum
        ));

        foreach ($affiliates as $affiliate) {
            // Check if has pending payout
            if ($this->has_pending_payout($affiliate->id)) {
                continue;
            }

            // Create automatic payout request
            $this->create_payout($affiliate, $affiliate->earnings_balance, $affiliate->payment_method);
        }
    }

    /**
     * Get payout stats
     */
    public function get_stats(string $period = '30days'): array {
        global $wpdb;

        $date_condition = '';
        switch ($period) {
            case '7days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case '90days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
                break;
            case 'year':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                break;
        }

        // Total payouts
        $total = $wpdb->get_row(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_payouts . "
             WHERE status = 'completed' $date_condition"
        );

        // Pending
        $pending = $wpdb->get_row(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_payouts . "
             WHERE status = 'pending'"
        );

        // By method
        $by_method = $wpdb->get_results(
            "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_payouts . "
             WHERE status = 'completed' $date_condition
             GROUP BY payment_method"
        );

        return [
            'total_count' => (int) ($total->count ?? 0),
            'total_amount' => (float) ($total->total ?? 0),
            'pending_count' => (int) ($pending->count ?? 0),
            'pending_amount' => (float) ($pending->total ?? 0),
            'by_method' => $by_method,
        ];
    }

    /**
     * Export payouts to CSV
     */
    public function export_csv(array $args = []): string {
        $payouts = $this->get_payouts(array_merge($args, ['limit' => 0]));

        $csv = "ID,Affiliato,Email,Importo,Valuta,Metodo,Stato,Transazione,Data\n";

        foreach ($payouts as $payout) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $payout->id,
                $payout->affiliate_name,
                $payout->payment_email,
                $payout->amount,
                $payout->currency,
                $payout->payment_method,
                $payout->status,
                $payout->transaction_id,
                $payout->date_created
            );
        }

        return $csv;
    }
}
