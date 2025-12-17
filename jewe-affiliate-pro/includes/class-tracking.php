<?php
/**
 * Tracking Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Tracking {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('init', [$this, 'track_referral']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tracking_script']);
    }

    /**
     * Track referral visit
     */
    public function track_referral() {
        $ref_param = apply_filters('jewe_affiliate_ref_param', 'ref');

        if (!isset($_GET[$ref_param])) {
            return;
        }

        $affiliate_code = sanitize_text_field($_GET[$ref_param]);
        $affiliate = JEWE_Affiliate::get_by_code($affiliate_code);

        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Set cookie
        $cookie_days = intval(get_option('jewe_affiliate_cookie_days', 30));
        $expire = time() + ($cookie_days * DAY_IN_SECONDS);

        setcookie('jewe_affiliate_ref', $affiliate_code, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // Generate visitor hash
        $visitor_hash = $this->generate_visitor_hash();
        setcookie('jewe_visitor_hash', $visitor_hash, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // Record visit
        $this->record_visit($affiliate->id, $visitor_hash);

        // Link customer if logged in (lifetime commissions)
        if (is_user_logged_in() && get_option('jewe_affiliate_lifetime_commissions', 'yes') === 'yes') {
            $user_id = get_current_user_id();
            $existing_link = get_user_meta($user_id, '_jewe_linked_affiliate', true);
            if (!$existing_link) {
                update_user_meta($user_id, '_jewe_linked_affiliate', $affiliate->id);
            }
        }

        do_action('jewe_affiliate_visit_tracked', $affiliate->id, $visitor_hash);
    }

    /**
     * Generate visitor hash
     */
    private function generate_visitor_hash() {
        $ip = $this->get_visitor_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        return hash('sha256', $ip . $user_agent . time() . wp_rand());
    }

    /**
     * Get visitor IP (anonymized)
     */
    private function get_visitor_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        // Anonymize IP (remove last octet)
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            $ip = implode('.', $parts);
        }

        return $ip;
    }

    /**
     * Record visit in database
     */
    private function record_visit($affiliate_id, $visitor_hash) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        // Parse UTM parameters
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';
        $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '';

        // Detect device
        $device_type = $this->detect_device();
        $browser = $this->detect_browser();

        // Get country (basic detection)
        $country = $this->detect_country();

        $wpdb->insert($table, [
            'affiliate_id' => $affiliate_id,
            'visitor_ip' => $this->get_visitor_ip(),
            'visitor_hash' => $visitor_hash,
            'referral_url' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
            'landing_page' => esc_url_raw(home_url($_SERVER['REQUEST_URI'])),
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'device_type' => $device_type,
            'browser' => $browser,
            'country' => $country,
        ]);

        // Update affiliate click count
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
        $wpdb->query($wpdb->prepare(
            "UPDATE $affiliates_table SET total_clicks = total_clicks + 1 WHERE id = %d",
            $affiliate_id
        ));
    }

    /**
     * Detect device type
     */
    private function detect_device() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $user_agent)) {
            if (preg_match('/tablet|ipad/i', $user_agent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser
     */
    private function detect_browser() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) return 'IE';

        return 'Other';
    }

    /**
     * Detect country (basic)
     */
    private function detect_country() {
        // This is a placeholder - in production you'd use a GeoIP service
        return '';
    }

    /**
     * Enqueue tracking script
     */
    public function enqueue_tracking_script() {
        wp_enqueue_script(
            'jewe-affiliate-tracking',
            JEWE_AFFILIATE_PLUGIN_URL . 'assets/js/tracking.js',
            ['jquery'],
            JEWE_AFFILIATE_VERSION,
            true
        );

        wp_localize_script('jewe-affiliate-tracking', 'jeweAffiliateTracking', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jewe_tracking_nonce'),
        ]);
    }

    /**
     * Get tracking stats for affiliate
     */
    public static function get_stats($affiliate_id, $period = '30days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_from = self::get_date_from($period);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_clicks,
                SUM(converted) as conversions,
                COUNT(DISTINCT visitor_hash) as unique_visitors,
                COUNT(DISTINCT DATE(created_at)) as active_days
             FROM $table
             WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate_id,
            $date_from
        ));
    }

    /**
     * Get traffic by source
     */
    public static function get_traffic_by_source($affiliate_id, $period = '30days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_from = self::get_date_from($period);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                CASE
                    WHEN utm_source != '' THEN utm_source
                    WHEN referral_url LIKE '%google%' THEN 'Google'
                    WHEN referral_url LIKE '%facebook%' THEN 'Facebook'
                    WHEN referral_url LIKE '%instagram%' THEN 'Instagram'
                    WHEN referral_url LIKE '%twitter%' OR referral_url LIKE '%x.com%' THEN 'Twitter/X'
                    WHEN referral_url = '' THEN 'Direct'
                    ELSE 'Other'
                END as source,
                COUNT(*) as clicks,
                SUM(converted) as conversions
             FROM $table
             WHERE affiliate_id = %d AND created_at >= %s
             GROUP BY source
             ORDER BY clicks DESC",
            $affiliate_id,
            $date_from
        ));
    }

    /**
     * Get traffic by device
     */
    public static function get_traffic_by_device($affiliate_id, $period = '30days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_from = self::get_date_from($period);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                device_type,
                COUNT(*) as clicks,
                SUM(converted) as conversions
             FROM $table
             WHERE affiliate_id = %d AND created_at >= %s
             GROUP BY device_type
             ORDER BY clicks DESC",
            $affiliate_id,
            $date_from
        ));
    }

    /**
     * Get date from period
     */
    private static function get_date_from($period) {
        switch ($period) {
            case '7days':
                return date('Y-m-d', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d', strtotime('-90 days'));
            case 'year':
                return date('Y-m-d', strtotime('-1 year'));
            default:
                return '2000-01-01';
        }
    }

    /**
     * Track QR code scan
     */
    public static function track_qr_scan($qr_code_id) {
        global $wpdb;

        $qr_table = $wpdb->prefix . 'jewe_qrcodes';
        $qr = $wpdb->get_row($wpdb->prepare("SELECT * FROM $qr_table WHERE id = %d", $qr_code_id));

        if (!$qr) {
            return false;
        }

        // Update scan count
        $wpdb->query($wpdb->prepare(
            "UPDATE $qr_table SET scans = scans + 1 WHERE id = %d",
            $qr_code_id
        ));

        // Record tracking with QR reference
        $tracking_table = $wpdb->prefix . 'jewe_tracking';
        $wpdb->insert($tracking_table, [
            'affiliate_id' => $qr->affiliate_id,
            'qr_code_id' => $qr_code_id,
            'device_type' => 'mobile', // QR scans are typically mobile
        ]);

        return $qr->target_url;
    }
}
