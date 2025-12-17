<?php
/**
 * REST API Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_REST_API {

    private static $instance = null;
    private $namespace = 'jewe-affiliate/v1';

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Affiliate endpoints
        register_rest_route($this->namespace, '/affiliate', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_current_affiliate'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/stats', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_affiliate_stats'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
                'args' => [
                    'period' => [
                        'default' => '30days',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/commissions', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_affiliate_commissions'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
                'args' => [
                    'page' => ['default' => 1, 'sanitize_callback' => 'absint'],
                    'per_page' => ['default' => 20, 'sanitize_callback' => 'absint'],
                    'status' => ['default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/referral-url', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_referral_url'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
                'args' => [
                    'target' => ['default' => '', 'sanitize_callback' => 'esc_url_raw'],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/team', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_team'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/badges', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_badges'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/insights', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_insights'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/notifications', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_notifications'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'mark_notifications_read'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/payout', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'request_payout'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
                'args' => [
                    'amount' => ['required' => true, 'sanitize_callback' => 'floatval'],
                    'method' => ['default' => 'paypal', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/affiliate/qrcode', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_qr_code'],
                'permission_callback' => [$this, 'check_affiliate_permission'],
                'args' => [
                    'target_url' => ['required' => true, 'sanitize_callback' => 'esc_url_raw'],
                    'name' => ['default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
        ]);

        // Leaderboard (public)
        register_rest_route($this->namespace, '/leaderboard', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_leaderboard'],
                'permission_callback' => '__return_true',
                'args' => [
                    'limit' => ['default' => 10, 'sanitize_callback' => 'absint'],
                    'period' => ['default' => '30days', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
        ]);

        // Admin endpoints
        register_rest_route($this->namespace, '/admin/affiliates', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'admin_get_affiliates'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/admin/affiliate/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'admin_get_affiliate'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'admin_update_affiliate'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/admin/overview', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'admin_get_overview'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);
    }

    /**
     * Check affiliate permission
     */
    public function check_affiliate_permission() {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', __('Devi essere loggato.', 'jewe-affiliate-pro'), ['status' => 401]);
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate) {
            return new WP_Error('rest_not_affiliate', __('Non sei un affiliato.', 'jewe-affiliate-pro'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Check admin permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Get current affiliate
     */
    public function get_current_affiliate($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        $user = get_userdata($affiliate->user_id);
        $tier = JEWE_Affiliate_Gamification::get_tier($affiliate->tier_level);

        return rest_ensure_response([
            'id' => $affiliate->id,
            'code' => $affiliate->affiliate_code,
            'status' => $affiliate->status,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'tier' => [
                'level' => $affiliate->tier_level,
                'name' => $tier ? $tier->name : 'Bronze',
            ],
            'lifetime_earnings' => floatval($affiliate->lifetime_earnings),
            'current_balance' => floatval($affiliate->current_balance),
            'total_referrals' => intval($affiliate->total_referrals),
            'total_clicks' => intval($affiliate->total_clicks),
            'created_at' => $affiliate->created_at,
        ]);
    }

    /**
     * Get affiliate stats
     */
    public function get_affiliate_stats($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        $period = $request->get_param('period');

        $stats = JEWE_Affiliate::get_stats($affiliate->id, $period);
        $kpis = JEWE_Affiliate_Analytics::get_dashboard_kpis($affiliate->id, $period);

        return rest_ensure_response(array_merge($stats, $kpis));
    }

    /**
     * Get affiliate commissions
     */
    public function get_affiliate_commissions($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100);
        $status = $request->get_param('status');

        $commissions = JEWE_Affiliate_Database::get_commissions($affiliate->id, [
            'status' => $status,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]);

        return rest_ensure_response([
            'commissions' => $commissions,
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    /**
     * Get referral URL
     */
    public function get_referral_url($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        $target = $request->get_param('target');

        $url = JEWE_Affiliate::get_referral_url($affiliate->id, $target);

        return rest_ensure_response(['url' => $url]);
    }

    /**
     * Get team
     */
    public function get_team($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $downline = JEWE_Affiliate_MLM::get_downline($affiliate->id);
        $stats = JEWE_Affiliate_MLM::get_stats($affiliate->id);

        // Enhance with user data
        foreach ($downline as &$member) {
            $user = get_userdata($member->user_id);
            $member->name = $user ? $user->display_name : 'Unknown';
        }

        return rest_ensure_response([
            'team' => $downline,
            'stats' => $stats,
        ]);
    }

    /**
     * Get badges
     */
    public function get_badges($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $badges = JEWE_Affiliate_Gamification::get_badges_with_progress($affiliate->id);
        $tier_progress = JEWE_Affiliate_Gamification::get_tier_progress($affiliate->id);

        return rest_ensure_response([
            'badges' => $badges,
            'tier_progress' => $tier_progress,
        ]);
    }

    /**
     * Get AI insights
     */
    public function get_insights($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $insights = JEWE_Affiliate_AI_Insights::get_insights($affiliate->id);
        $score = JEWE_Affiliate_AI_Insights::get_overall_score($affiliate->id);

        return rest_ensure_response([
            'insights' => $insights,
            'overall_score' => $score,
        ]);
    }

    /**
     * Get notifications
     */
    public function get_notifications($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $notifications = JEWE_Affiliate_Notifications::get_notifications($affiliate->id);
        $unread = JEWE_Affiliate_Notifications::get_unread_count($affiliate->id);

        return rest_ensure_response([
            'notifications' => $notifications,
            'unread_count' => $unread,
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function mark_notifications_read($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        $notification_id = $request->get_param('notification_id');

        if ($notification_id) {
            JEWE_Affiliate_Notifications::mark_as_read($notification_id, $affiliate->id);
        } else {
            JEWE_Affiliate_Notifications::mark_all_as_read($affiliate->id);
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Request payout
     */
    public function request_payout($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        $amount = $request->get_param('amount');
        $method = $request->get_param('method');

        // Validate
        $min_payout = floatval(get_option('jewe_affiliate_min_payout', 50));
        if ($amount < $min_payout) {
            return new WP_Error('min_amount', sprintf(__('L\'importo minimo è €%s', 'jewe-affiliate-pro'), $min_payout), ['status' => 400]);
        }

        if ($amount > $affiliate->current_balance) {
            return new WP_Error('insufficient_balance', __('Saldo insufficiente', 'jewe-affiliate-pro'), ['status' => 400]);
        }

        // Create payout request
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'jewe_payouts', [
            'affiliate_id' => $affiliate->id,
            'amount' => $amount,
            'payment_method' => $method,
            'status' => 'pending',
        ]);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Richiesta di pagamento inviata', 'jewe-affiliate-pro'),
        ]);
    }

    /**
     * Create QR code
     */
    public function create_qr_code($request) {
        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        if (get_option('jewe_affiliate_qr_enabled', 'yes') !== 'yes') {
            return new WP_Error('qr_disabled', __('QR codes disabilitati', 'jewe-affiliate-pro'), ['status' => 400]);
        }

        $target_url = $request->get_param('target_url');
        $name = $request->get_param('name');

        // Generate referral URL
        $referral_url = JEWE_Affiliate::get_referral_url($affiliate->id, $target_url);

        // Generate QR code using Google Charts API (simple solution)
        $qr_url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($referral_url);

        // Save to database
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'jewe_qrcodes', [
            'affiliate_id' => $affiliate->id,
            'name' => $name ?: 'QR Code',
            'target_url' => $target_url,
            'qr_image_url' => $qr_url,
        ]);

        return rest_ensure_response([
            'id' => $wpdb->insert_id,
            'qr_url' => $qr_url,
            'referral_url' => $referral_url,
        ]);
    }

    /**
     * Get leaderboard
     */
    public function get_leaderboard($request) {
        $limit = min($request->get_param('limit'), 50);
        $period = $request->get_param('period');

        $leaders = JEWE_Affiliate::get_leaderboard($limit, $period);

        // Enhance with user names (privacy-safe)
        foreach ($leaders as $i => &$leader) {
            $user = get_userdata($leader->user_id);
            $leader->rank = $i + 1;
            $leader->name = $user ? substr($user->display_name, 0, 3) . '***' : 'Anonimo';
            unset($leader->user_id);
        }

        return rest_ensure_response($leaders);
    }

    /**
     * Admin: Get all affiliates
     */
    public function admin_get_affiliates($request) {
        $affiliates = JEWE_Affiliate_Database::get_affiliates([
            'limit' => 100,
        ]);

        foreach ($affiliates as &$affiliate) {
            $user = get_userdata($affiliate->user_id);
            $affiliate->name = $user ? $user->display_name : 'Unknown';
            $affiliate->email = $user ? $user->user_email : '';
        }

        return rest_ensure_response($affiliates);
    }

    /**
     * Admin: Get single affiliate
     */
    public function admin_get_affiliate($request) {
        $id = $request->get_param('id');
        $affiliate = JEWE_Affiliate::get($id);

        if (!$affiliate) {
            return new WP_Error('not_found', __('Affiliato non trovato', 'jewe-affiliate-pro'), ['status' => 404]);
        }

        $user = get_userdata($affiliate->user_id);
        $affiliate->name = $user ? $user->display_name : 'Unknown';
        $affiliate->email = $user ? $user->user_email : '';
        $affiliate->stats = JEWE_Affiliate::get_stats($id, '30days');
        $affiliate->mlm = JEWE_Affiliate_MLM::get_stats($id);

        return rest_ensure_response($affiliate);
    }

    /**
     * Admin: Update affiliate
     */
    public function admin_update_affiliate($request) {
        $id = $request->get_param('id');
        $status = $request->get_param('status');

        if ($status) {
            JEWE_Affiliate::update_status($id, $status);
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Admin: Get overview
     */
    public function admin_get_overview($request) {
        $period = $request->get_param('period') ?: '30days';

        return rest_ensure_response(
            JEWE_Affiliate_Analytics::get_program_overview($period)
        );
    }
}
