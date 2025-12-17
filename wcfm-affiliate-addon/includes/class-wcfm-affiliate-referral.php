<?php
/**
 * Referral link system with tracking
 *
 * Gestisce la generazione di link referral, tracking dei click e delle visite.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Referral
 */
class WCFM_Affiliate_Referral {

    /**
     * Settings
     */
    private array $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('wcfm_affiliate_general', []);

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Track referral visits
        add_action('template_redirect', [$this, 'track_referral_visit'], 1);

        // Set cookie
        add_action('init', [$this, 'set_referral_cookie'], 5);

        // Track clicks via AJAX
        add_action('wp_ajax_wcfm_affiliate_track_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_wcfm_affiliate_track_click', [$this, 'track_click']);

        // Generate referral link AJAX
        add_action('wp_ajax_wcfm_affiliate_generate_link', [$this, 'generate_link_ajax']);

        // Shortcode for referral link
        add_shortcode('wcfm_affiliate_link', [$this, 'referral_link_shortcode']);
    }

    /**
     * Track referral visit
     */
    public function track_referral_visit(): void {
        if (is_admin()) {
            return;
        }

        $referral_var = $this->settings['referral_var'] ?? 'ref';

        // Check for referral code in URL
        if (!isset($_GET[$referral_var]) || empty($_GET[$referral_var])) {
            return;
        }

        $affiliate_code = sanitize_text_field($_GET[$referral_var]);

        // Get affiliate
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_code($affiliate_code);

        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Check if already tracked in this session
        if (isset($_SESSION['wcfm_affiliate_tracked']) && $_SESSION['wcfm_affiliate_tracked'] === $affiliate->id) {
            return;
        }

        // Create visit record
        $visit_id = $this->create_visit($affiliate);

        if ($visit_id) {
            // Create click record
            $this->create_click($affiliate->id, $visit_id);

            // Update affiliate visit count
            wcfm_affiliate_pro()->affiliates->increment_visit_count($affiliate->id);

            // Set session flag
            if (!session_id()) {
                session_start();
            }
            $_SESSION['wcfm_affiliate_tracked'] = $affiliate->id;
            $_SESSION['wcfm_affiliate_visit_id'] = $visit_id;
        }
    }

    /**
     * Set referral cookie
     */
    public function set_referral_cookie(): void {
        if (is_admin()) {
            return;
        }

        $referral_var = $this->settings['referral_var'] ?? 'ref';
        $cookie_name = $this->settings['cookie_name'] ?? 'wcfm_affiliate_ref';
        $cookie_duration = (int) ($this->settings['cookie_duration'] ?? 30);
        $credit_last = ($this->settings['credit_last_referrer'] ?? 'yes') === 'yes';

        // Check for referral code in URL
        if (!isset($_GET[$referral_var]) || empty($_GET[$referral_var])) {
            return;
        }

        $affiliate_code = sanitize_text_field($_GET[$referral_var]);

        // Validate affiliate
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_code($affiliate_code);

        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Check existing cookie
        if (!$credit_last && isset($_COOKIE[$cookie_name])) {
            return; // First referrer gets credit
        }

        // Set cookie
        $expiry = time() + ($cookie_duration * DAY_IN_SECONDS);

        setcookie(
            $cookie_name,
            $affiliate_code,
            [
                'expires' => $expiry,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[$cookie_name] = $affiliate_code;

        // Also store affiliate ID for faster lookups
        setcookie(
            $cookie_name . '_id',
            $affiliate->id,
            [
                'expires' => $expiry,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Create visit record
     */
    private function create_visit(object $affiliate): int {
        global $wpdb;

        $cookie_duration = (int) ($this->settings['cookie_duration'] ?? 30);

        $data = [
            'affiliate_id' => $affiliate->id,
            'customer_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'landing_url' => $this->get_current_url(),
            'referrer' => wp_get_referer() ?: ($_SERVER['HTTP_REFERER'] ?? ''),
            'campaign' => sanitize_text_field($_GET['campaign'] ?? ''),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'device_type' => $this->get_device_type(),
            'country' => $this->get_country_code(),
            'expires_at' => date('Y-m-d H:i:s', time() + ($cookie_duration * DAY_IN_SECONDS)),
        ];

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_visits,
            $data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Create click record
     */
    private function create_click(int $affiliate_id, int $visit_id = null): int {
        global $wpdb;

        $data = [
            'affiliate_id' => $affiliate_id,
            'visit_id' => $visit_id,
            'url' => $this->get_current_url(),
            'referrer' => wp_get_referer() ?: ($_SERVER['HTTP_REFERER'] ?? ''),
            'campaign' => sanitize_text_field($_GET['campaign'] ?? ''),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'device_type' => $this->get_device_type(),
            'browser' => $this->get_browser(),
            'os' => $this->get_os(),
            'country' => $this->get_country_code(),
        ];

        $result = $wpdb->insert(
            WCFM_Affiliate_DB::$table_clicks,
            $data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Track click via AJAX
     */
    public function track_click(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $url = sanitize_url($_POST['url'] ?? '');
        $creative_id = intval($_POST['creative_id'] ?? 0);

        if (!$affiliate_id || !$url) {
            wp_send_json_error();
        }

        $this->create_click($affiliate_id);

        // Update creative stats if applicable
        if ($creative_id) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE " . WCFM_Affiliate_DB::$table_creatives . " SET clicks = clicks + 1 WHERE id = %d",
                $creative_id
            ));
        }

        wp_send_json_success();
    }

    /**
     * Generate referral link
     */
    public function generate_link(int $affiliate_id, string $destination = '', array $params = []): string {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return '';
        }

        if (empty($destination)) {
            $destination = home_url();
        }

        $referral_var = $this->settings['referral_var'] ?? 'ref';

        // Build URL with referral parameter
        $url = add_query_arg($referral_var, $affiliate->affiliate_code, $destination);

        // Add campaign parameter if provided
        if (!empty($params['campaign'])) {
            $url = add_query_arg('campaign', sanitize_text_field($params['campaign']), $url);
        }

        // Add custom parameters
        if (!empty($params['custom'])) {
            foreach ($params['custom'] as $key => $value) {
                $url = add_query_arg(sanitize_key($key), sanitize_text_field($value), $url);
            }
        }

        return apply_filters('wcfm_affiliate_referral_link', $url, $affiliate_id, $destination, $params);
    }

    /**
     * Generate short referral link
     */
    public function generate_short_link(int $affiliate_id, string $destination = ''): string {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate) {
            return '';
        }

        // Use pretty permalink format if available
        $base_url = home_url('/ref/');

        if (empty($destination)) {
            return $base_url . $affiliate->affiliate_code;
        }

        // Encode destination
        $encoded_dest = base64_encode($destination);

        return $base_url . $affiliate->affiliate_code . '/' . $encoded_dest;
    }

    /**
     * Generate link AJAX
     */
    public function generate_link_ajax(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Non sei un affiliato', 'wcfm-affiliate-pro')]);
        }

        $destination = sanitize_url($_POST['destination'] ?? '');
        $campaign = sanitize_text_field($_POST['campaign'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'full');

        $params = [];
        if (!empty($campaign)) {
            $params['campaign'] = $campaign;
        }

        if ($type === 'short') {
            $link = $this->generate_short_link($affiliate->id, $destination);
        } else {
            $link = $this->generate_link($affiliate->id, $destination, $params);
        }

        wp_send_json_success([
            'link' => $link,
            'code' => $affiliate->affiliate_code,
        ]);
    }

    /**
     * Referral link shortcode
     */
    public function referral_link_shortcode(array $atts): string {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'destination' => '',
            'campaign' => '',
            'text' => '',
            'class' => 'wcfm-affiliate-link',
        ], $atts);

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            return '';
        }

        $params = [];
        if (!empty($atts['campaign'])) {
            $params['campaign'] = $atts['campaign'];
        }

        $link = $this->generate_link($affiliate->id, $atts['destination'], $params);
        $text = !empty($atts['text']) ? esc_html($atts['text']) : esc_url($link);

        return sprintf(
            '<a href="%s" class="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($link),
            esc_attr($atts['class']),
            $text
        );
    }

    /**
     * Get current affiliate from cookie/session
     */
    public function get_current_affiliate(): ?object {
        $cookie_name = $this->settings['cookie_name'] ?? 'wcfm_affiliate_ref';

        if (isset($_COOKIE[$cookie_name])) {
            return wcfm_affiliate_pro()->affiliates->get_affiliate_by_code($_COOKIE[$cookie_name]);
        }

        return null;
    }

    /**
     * Get referral stats for affiliate
     */
    public function get_stats(int $affiliate_id, string $period = '30days'): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        // Clicks
        $clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE affiliate_id = %d $date_condition",
            $affiliate_id
        ));

        // Visits
        $visits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE affiliate_id = %d $date_condition",
            $affiliate_id
        ));

        // Conversions
        $conversions = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE affiliate_id = %d AND converted = 1 $date_condition",
            $affiliate_id
        ));

        // Referrals
        $referrals = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_referrals . "
             WHERE affiliate_id = %d $date_condition",
            $affiliate_id
        ));

        // Clicks by day
        $clicks_by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as count
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE affiliate_id = %d $date_condition
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $affiliate_id
        ));

        // Top referrers
        $top_referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '://', -1) as domain, COUNT(*) as count
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE affiliate_id = %d AND referrer != '' $date_condition
             GROUP BY domain
             ORDER BY count DESC
             LIMIT 10",
            $affiliate_id
        ));

        // Device breakdown
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE affiliate_id = %d AND device_type != '' $date_condition
             GROUP BY device_type",
            $affiliate_id
        ));

        return [
            'clicks' => $clicks,
            'visits' => $visits,
            'conversions' => $conversions,
            'conversion_rate' => $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0,
            'referrals_count' => (int) ($referrals->count ?? 0),
            'referrals_total' => (float) ($referrals->total ?? 0),
            'clicks_by_day' => $clicks_by_day,
            'top_referrers' => $top_referrers,
            'devices' => $devices,
        ];
    }

    /**
     * Get clicks for affiliate
     */
    public function get_clicks(int $affiliate_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'date_from' => '',
            'date_to' => '',
            'converted' => null,
            'campaign' => '',
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['affiliate_id = %d'];
        $values = [$affiliate_id];

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(date_created) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(date_created) <= %s';
            $values[] = $args['date_to'];
        }

        if ($args['converted'] !== null) {
            $where[] = 'converted = %d';
            $values[] = $args['converted'] ? 1 : 0;
        }

        if (!empty($args['campaign'])) {
            $where[] = 'campaign = %s';
            $values[] = $args['campaign'];
        }

        $sql = "SELECT * FROM " . WCFM_Affiliate_DB::$table_clicks . "
                WHERE " . implode(' AND ', $where) . "
                ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']) . "
                LIMIT %d OFFSET %d";

        $values[] = $args['limit'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Get visits for affiliate
     */
    public function get_visits(int $affiliate_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'converted' => null,
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['affiliate_id = %d'];
        $values = [$affiliate_id];

        if ($args['converted'] !== null) {
            $where[] = 'converted = %d';
            $values[] = $args['converted'] ? 1 : 0;
        }

        $sql = "SELECT * FROM " . WCFM_Affiliate_DB::$table_visits . "
                WHERE " . implode(' AND ', $where) . "
                ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']) . "
                LIMIT %d OFFSET %d";

        $values[] = $args['limit'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Cleanup old tracking data
     */
    public function cleanup_old_data(): void {
        global $wpdb;

        // Delete expired visits (keep for stats but mark as expired)
        $wpdb->query(
            "DELETE FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE converted = 0 AND expires_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );

        // Delete old clicks (older than 1 year)
        $wpdb->query(
            "DELETE FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE date_created < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
        );
    }

    /**
     * Get date condition for SQL
     */
    private function get_date_condition(string $period): string {
        switch ($period) {
            case '7days':
                return 'AND date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            case '30days':
                return 'AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            case '90days':
                return 'AND date_created >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
            case 'year':
                return 'AND date_created >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            case 'all':
            default:
                return '';
        }
    }

    /**
     * Get current URL
     */
    private function get_current_url(): string {
        $protocol = is_ssl() ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get session ID
     */
    private function get_session_id(): string {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }

    /**
     * Get client IP
     */
    private function get_client_ip(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field(trim($ip));
    }

    /**
     * Get device type
     */
    private function get_device_type(): string {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|opera mini|iemobile/i', $user_agent)) {
            if (preg_match('/ipad|tablet/i', $user_agent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get browser
     */
    private function get_browser(): string {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (strpos($user_agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR') !== false) {
            return 'Opera';
        } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
            return 'IE';
        }

        return 'Other';
    }

    /**
     * Get OS
     */
    private function get_os(): string {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (preg_match('/windows/i', $user_agent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            return 'macOS';
        } elseif (preg_match('/linux/i', $user_agent)) {
            return 'Linux';
        } elseif (preg_match('/iphone|ipad/i', $user_agent)) {
            return 'iOS';
        } elseif (preg_match('/android/i', $user_agent)) {
            return 'Android';
        }

        return 'Other';
    }

    /**
     * Get country code from IP
     */
    private function get_country_code(): string {
        // Simple GeoIP lookup - can be extended with external services
        $ip = $this->get_client_ip();

        // Check if we have a cached result
        $cache_key = 'wcfm_affiliate_geo_' . md5($ip);
        $country = get_transient($cache_key);

        if ($country !== false) {
            return $country;
        }

        // Try ip-api.com (free, no API key needed, limited to 45 requests/minute)
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode", [
            'timeout' => 2,
        ]);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!empty($data['countryCode'])) {
                $country = $data['countryCode'];
                set_transient($cache_key, $country, DAY_IN_SECONDS);
                return $country;
            }
        }

        return '';
    }

    /**
     * Update page views for visit
     */
    public function update_page_views(int $visit_id): void {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE " . WCFM_Affiliate_DB::$table_visits . " SET pages_viewed = pages_viewed + 1 WHERE id = %d",
            $visit_id
        ));
    }
}
