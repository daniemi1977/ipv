<?php
/**
 * REST API
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_REST_API {

    private string $namespace = 'wcfm-affiliate/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        // Affiliates
        register_rest_route($this->namespace, '/affiliates', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_affiliates'],
                'permission_callback' => [$this, 'admin_permission_check'],
                'args' => $this->get_collection_params(),
            ],
        ]);

        register_rest_route($this->namespace, '/affiliates/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_affiliate'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_affiliate'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_affiliate'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
        ]);

        // Commissions
        register_rest_route($this->namespace, '/commissions', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_commissions'],
                'permission_callback' => [$this, 'admin_permission_check'],
                'args' => $this->get_collection_params(),
            ],
        ]);

        register_rest_route($this->namespace, '/commissions/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_commission'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_commission'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
        ]);

        // Payouts
        register_rest_route($this->namespace, '/payouts', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_payouts'],
                'permission_callback' => [$this, 'admin_permission_check'],
                'args' => $this->get_collection_params(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_payout'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/payouts/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_payout'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_payout'],
                'permission_callback' => [$this, 'admin_permission_check'],
            ],
        ]);

        // Reports
        register_rest_route($this->namespace, '/reports/overview', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_reports_overview'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);

        register_rest_route($this->namespace, '/reports/chart', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_reports_chart'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);

        // My affiliate (for logged-in affiliates)
        register_rest_route($this->namespace, '/me', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_my_affiliate'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);

        register_rest_route($this->namespace, '/me/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_my_stats'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);

        register_rest_route($this->namespace, '/me/referrals', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_my_referrals'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);

        register_rest_route($this->namespace, '/me/payouts', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_my_payouts'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);

        register_rest_route($this->namespace, '/me/request-payout', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'request_my_payout'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);

        // Generate link
        register_rest_route($this->namespace, '/generate-link', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_link'],
            'permission_callback' => [$this, 'affiliate_permission_check'],
        ]);
    }

    public function admin_permission_check(): bool {
        return current_user_can('manage_options');
    }

    public function affiliate_permission_check(): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        return $affiliate && $affiliate->status === 'active';
    }

    private function get_collection_params(): array {
        return [
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default' => 20,
                'sanitize_callback' => 'absint',
            ],
            'status' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'search' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    // Affiliates endpoints
    public function get_affiliates(WP_REST_Request $request): WP_REST_Response {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $status = $request->get_param('status');
        $search = $request->get_param('search');

        $affiliates = wcfm_affiliate_pro()->affiliates->get_affiliates([
            'status' => $status,
            'search' => $search,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => $status]);

        $data = array_map([$this, 'prepare_affiliate_response'], $affiliates);

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    public function get_affiliate(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($request->get_param('id'));

        if (!$affiliate) {
            return new WP_Error('not_found', __('Affiliato non trovato', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_affiliate_response($affiliate), 200);
    }

    public function update_affiliate(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $affiliate_id = $request->get_param('id');
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return new WP_Error('not_found', __('Affiliato non trovato', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        $data = [];
        $params = $request->get_json_params();

        if (isset($params['status'])) {
            $data['status'] = sanitize_text_field($params['status']);
        }

        if (isset($params['payment_email'])) {
            $data['payment_email'] = sanitize_email($params['payment_email']);
        }

        if (isset($params['payment_method'])) {
            $data['payment_method'] = sanitize_text_field($params['payment_method']);
        }

        if (isset($params['website_url'])) {
            $data['website_url'] = esc_url_raw($params['website_url']);
        }

        if (!empty($data)) {
            wcfm_affiliate_pro()->affiliates->update_affiliate($affiliate_id, $data);
        }

        $updated = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);
        return new WP_REST_Response($this->prepare_affiliate_response($updated), 200);
    }

    public function delete_affiliate(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $affiliate_id = $request->get_param('id');
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return new WP_Error('not_found', __('Affiliato non trovato', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        wcfm_affiliate_pro()->affiliates->delete_affiliate($affiliate_id);

        return new WP_REST_Response(['deleted' => true], 200);
    }

    private function prepare_affiliate_response($affiliate): array {
        $user = get_userdata($affiliate->user_id);
        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);

        return [
            'id' => (int) $affiliate->id,
            'user_id' => (int) $affiliate->user_id,
            'display_name' => $user ? $user->display_name : '',
            'email' => $user ? $user->user_email : '',
            'affiliate_code' => $affiliate->affiliate_code,
            'status' => $affiliate->status,
            'payment_email' => $affiliate->payment_email,
            'payment_method' => $affiliate->payment_method,
            'website_url' => $affiliate->website_url,
            'parent_affiliate_id' => (int) $affiliate->parent_affiliate_id,
            'date_created' => $affiliate->date_created,
            'stats' => $stats,
        ];
    }

    // Commissions endpoints
    public function get_commissions(WP_REST_Request $request): WP_REST_Response {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $status = $request->get_param('status');
        $affiliate_id = $request->get_param('affiliate_id');

        $commissions = wcfm_affiliate_pro()->commissions->get_commissions([
            'status' => $status,
            'affiliate_id' => $affiliate_id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->commissions->count_commissions(['status' => $status, 'affiliate_id' => $affiliate_id]);

        $data = array_map([$this, 'prepare_commission_response'], $commissions);

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    public function get_commission(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $commission = wcfm_affiliate_pro()->commissions->get_commission($request->get_param('id'));

        if (!$commission) {
            return new WP_Error('not_found', __('Commissione non trovata', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_commission_response($commission), 200);
    }

    public function update_commission(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $commission_id = $request->get_param('id');
        $commission = wcfm_affiliate_pro()->commissions->get_commission($commission_id);

        if (!$commission) {
            return new WP_Error('not_found', __('Commissione non trovata', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        $params = $request->get_json_params();

        if (isset($params['status'])) {
            $new_status = sanitize_text_field($params['status']);

            if ($new_status === 'approved' && $commission->status === 'pending') {
                wcfm_affiliate_pro()->commissions->approve_commission($commission_id);
            } elseif ($new_status === 'rejected') {
                wcfm_affiliate_pro()->commissions->reject_commission($commission_id);
            }
        }

        $updated = wcfm_affiliate_pro()->commissions->get_commission($commission_id);
        return new WP_REST_Response($this->prepare_commission_response($updated), 200);
    }

    private function prepare_commission_response($commission): array {
        return [
            'id' => (int) $commission->id,
            'affiliate_id' => (int) $commission->affiliate_id,
            'affiliate_name' => $commission->affiliate_name ?? '',
            'order_id' => (int) $commission->order_id,
            'product_id' => (int) $commission->product_id,
            'base_amount' => (float) $commission->base_amount,
            'rate' => (float) $commission->rate,
            'type' => $commission->type,
            'commission_amount' => (float) $commission->commission_amount,
            'status' => $commission->status,
            'date_created' => $commission->date_created,
        ];
    }

    // Payouts endpoints
    public function get_payouts(WP_REST_Request $request): WP_REST_Response {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $status = $request->get_param('status');

        $payouts = wcfm_affiliate_pro()->payouts->get_payouts([
            'status' => $status,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->payouts->count_payouts(['status' => $status]);

        $data = array_map([$this, 'prepare_payout_response'], $payouts);

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    public function get_payout(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $payout = wcfm_affiliate_pro()->payouts->get_payout($request->get_param('id'));

        if (!$payout) {
            return new WP_Error('not_found', __('Pagamento non trovato', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_payout_response($payout), 200);
    }

    public function create_payout(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $params = $request->get_json_params();

        $affiliate_id = isset($params['affiliate_id']) ? intval($params['affiliate_id']) : 0;
        $amount = isset($params['amount']) ? floatval($params['amount']) : 0;
        $method = isset($params['method']) ? sanitize_text_field($params['method']) : '';

        if (!$affiliate_id || !$amount) {
            return new WP_Error('invalid_params', __('Parametri non validi', 'wcfm-affiliate-pro'), ['status' => 400]);
        }

        $result = wcfm_affiliate_pro()->payouts->request_payout($affiliate_id, $amount, $method);

        if (is_wp_error($result)) {
            return $result;
        }

        $payout = wcfm_affiliate_pro()->payouts->get_payout($result);
        return new WP_REST_Response($this->prepare_payout_response($payout), 201);
    }

    public function update_payout(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $payout_id = $request->get_param('id');
        $payout = wcfm_affiliate_pro()->payouts->get_payout($payout_id);

        if (!$payout) {
            return new WP_Error('not_found', __('Pagamento non trovato', 'wcfm-affiliate-pro'), ['status' => 404]);
        }

        $params = $request->get_json_params();

        if (isset($params['status'])) {
            $new_status = sanitize_text_field($params['status']);
            $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
            $notes = isset($params['notes']) ? sanitize_textarea_field($params['notes']) : '';

            if ($new_status === 'completed') {
                wcfm_affiliate_pro()->payouts->complete_payout($payout_id, $transaction_id, $notes);
            } elseif ($new_status === 'failed') {
                wcfm_affiliate_pro()->payouts->fail_payout($payout_id, $notes);
            } elseif ($new_status === 'cancelled') {
                wcfm_affiliate_pro()->payouts->cancel_payout($payout_id);
            }
        }

        $updated = wcfm_affiliate_pro()->payouts->get_payout($payout_id);
        return new WP_REST_Response($this->prepare_payout_response($updated), 200);
    }

    private function prepare_payout_response($payout): array {
        return [
            'id' => (int) $payout->id,
            'affiliate_id' => (int) $payout->affiliate_id,
            'affiliate_name' => $payout->affiliate_name ?? '',
            'amount' => (float) $payout->amount,
            'payment_method' => $payout->payment_method,
            'payment_email' => $payout->payment_email,
            'status' => $payout->status,
            'transaction_id' => $payout->transaction_id,
            'date_created' => $payout->date_created,
            'date_completed' => $payout->date_completed,
        ];
    }

    // Reports endpoints
    public function get_reports_overview(WP_REST_Request $request): WP_REST_Response {
        $period = $request->get_param('period') ?: '30days';
        $stats = wcfm_affiliate_pro()->reports->get_overview($period);

        return new WP_REST_Response($stats, 200);
    }

    public function get_reports_chart(WP_REST_Request $request): WP_REST_Response {
        $period = $request->get_param('period') ?: '30days';
        $metric = $request->get_param('metric') ?: 'commissions';
        $chart = wcfm_affiliate_pro()->reports->get_chart_data($period, $metric);

        return new WP_REST_Response($chart, 200);
    }

    // My affiliate endpoints
    public function get_my_affiliate(): WP_REST_Response {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        return new WP_REST_Response($this->prepare_affiliate_response($affiliate), 200);
    }

    public function get_my_stats(WP_REST_Request $request): WP_REST_Response {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        $period = $request->get_param('period') ?: '30days';
        $stats = wcfm_affiliate_pro()->reports->get_affiliate_stats($affiliate->id, $period);

        return new WP_REST_Response($stats, 200);
    }

    public function get_my_referrals(WP_REST_Request $request): WP_REST_Response {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        $commissions = wcfm_affiliate_pro()->commissions->get_commissions([
            'affiliate_id' => $affiliate->id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->commissions->count_commissions(['affiliate_id' => $affiliate->id]);

        $data = array_map([$this, 'prepare_commission_response'], $commissions);

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    public function get_my_payouts(WP_REST_Request $request): WP_REST_Response {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        $payouts = wcfm_affiliate_pro()->payouts->get_payouts([
            'affiliate_id' => $affiliate->id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        $total = wcfm_affiliate_pro()->payouts->count_payouts(['affiliate_id' => $affiliate->id]);

        $data = array_map([$this, 'prepare_payout_response'], $payouts);

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));

        return $response;
    }

    public function request_my_payout(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        $params = $request->get_json_params();

        $amount = isset($params['amount']) ? floatval($params['amount']) : $affiliate->earnings_balance;
        $method = isset($params['method']) ? sanitize_text_field($params['method']) : $affiliate->payment_method;

        $result = wcfm_affiliate_pro()->payouts->request_payout($affiliate->id, $amount, $method);

        if (is_wp_error($result)) {
            return $result;
        }

        $payout = wcfm_affiliate_pro()->payouts->get_payout($result);
        return new WP_REST_Response($this->prepare_payout_response($payout), 201);
    }

    public function generate_link(WP_REST_Request $request): WP_REST_Response {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());
        $params = $request->get_json_params();

        $url = isset($params['url']) ? esc_url_raw($params['url']) : home_url();
        $campaign = isset($params['campaign']) ? sanitize_text_field($params['campaign']) : '';

        $link = wcfm_affiliate_pro()->referrals->generate_referral_link($affiliate->id, $url, $campaign);

        return new WP_REST_Response(['link' => $link], 200);
    }
}
