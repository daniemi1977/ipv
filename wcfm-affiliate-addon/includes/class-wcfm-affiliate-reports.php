<?php
/**
 * Reports and Analytics
 *
 * Gestisce report e statistiche del sistema affiliate.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Reports
 */
class WCFM_Affiliate_Reports {

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
        add_action('wp_ajax_wcfm_affiliate_get_report', [$this, 'get_report_ajax']);
        add_action('wp_ajax_wcfm_affiliate_export_report', [$this, 'export_report_ajax']);
    }

    /**
     * Get overview report
     */
    public function get_overview(string $period = '30days', ?int $affiliate_id = null): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        // Referrals
        $referrals = $wpdb->get_row(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_referrals . "
             WHERE 1=1 $date_condition $affiliate_condition"
        );

        // Commissions by status
        $commissions = $wpdb->get_results(
            "SELECT status, COUNT(*) as count, SUM(commission_amount) as total
             FROM " . WCFM_Affiliate_DB::$table_commissions . "
             WHERE 1=1 $date_condition $affiliate_condition
             GROUP BY status",
            OBJECT_K
        );

        // Visits and conversions
        $visits = $wpdb->get_row(
            "SELECT COUNT(*) as total, SUM(converted) as converted
             FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE 1=1 $date_condition $affiliate_condition"
        );

        // Clicks
        $clicks = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE 1=1 $date_condition $affiliate_condition"
        );

        // Payouts
        $payouts = $wpdb->get_row(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM " . WCFM_Affiliate_DB::$table_payouts . "
             WHERE status = 'completed' $date_condition $affiliate_condition"
        );

        return [
            'referrals' => [
                'count' => (int) ($referrals->count ?? 0),
                'total' => (float) ($referrals->total ?? 0),
            ],
            'commissions' => [
                'pending' => [
                    'count' => (int) ($commissions['pending']->count ?? 0),
                    'total' => (float) ($commissions['pending']->total ?? 0),
                ],
                'approved' => [
                    'count' => (int) ($commissions['approved']->count ?? 0),
                    'total' => (float) ($commissions['approved']->total ?? 0),
                ],
                'paid' => [
                    'count' => (int) ($commissions['paid']->count ?? 0),
                    'total' => (float) ($commissions['paid']->total ?? 0),
                ],
            ],
            'visits' => (int) ($visits->total ?? 0),
            'conversions' => (int) ($visits->converted ?? 0),
            'conversion_rate' => $visits->total > 0 ? round(($visits->converted / $visits->total) * 100, 2) : 0,
            'clicks' => $clicks,
            'payouts' => [
                'count' => (int) ($payouts->count ?? 0),
                'total' => (float) ($payouts->total ?? 0),
            ],
        ];
    }

    /**
     * Get chart data
     */
    public function get_chart_data(string $period = '30days', string $metric = 'commissions', ?int $affiliate_id = null): array {
        global $wpdb;

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        $days = $this->get_days_from_period($period);
        $group_by = $days > 90 ? 'WEEK' : 'DATE';

        $labels = [];
        $data = [];

        switch ($metric) {
            case 'commissions':
                $results = $wpdb->get_results(
                    "SELECT DATE(date_created) as date, SUM(commission_amount) as value
                     FROM " . WCFM_Affiliate_DB::$table_commissions . "
                     WHERE date_created >= DATE_SUB(NOW(), INTERVAL $days DAY) $affiliate_condition
                     GROUP BY DATE(date_created)
                     ORDER BY date ASC",
                    OBJECT_K
                );
                break;

            case 'referrals':
                $results = $wpdb->get_results(
                    "SELECT DATE(date_created) as date, COUNT(*) as value
                     FROM " . WCFM_Affiliate_DB::$table_referrals . "
                     WHERE date_created >= DATE_SUB(NOW(), INTERVAL $days DAY) $affiliate_condition
                     GROUP BY DATE(date_created)
                     ORDER BY date ASC",
                    OBJECT_K
                );
                break;

            case 'clicks':
                $results = $wpdb->get_results(
                    "SELECT DATE(date_created) as date, COUNT(*) as value
                     FROM " . WCFM_Affiliate_DB::$table_clicks . "
                     WHERE date_created >= DATE_SUB(NOW(), INTERVAL $days DAY) $affiliate_condition
                     GROUP BY DATE(date_created)
                     ORDER BY date ASC",
                    OBJECT_K
                );
                break;

            case 'visits':
                $results = $wpdb->get_results(
                    "SELECT DATE(date_created) as date, COUNT(*) as value
                     FROM " . WCFM_Affiliate_DB::$table_visits . "
                     WHERE date_created >= DATE_SUB(NOW(), INTERVAL $days DAY) $affiliate_condition
                     GROUP BY DATE(date_created)
                     ORDER BY date ASC",
                    OBJECT_K
                );
                break;

            default:
                $results = [];
        }

        // Fill in missing dates
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date_i18n('j M', strtotime($date));
            $data[] = isset($results[$date]) ? (float) $results[$date]->value : 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get top affiliates
     */
    public function get_top_affiliates(string $period = '30days', int $limit = 10, string $order_by = 'earnings'): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period, 'c');

        $order_column = match($order_by) {
            'referrals' => 'referrals_count',
            'conversion' => 'conversion_rate',
            default => 'total_earnings',
        };

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name, u.user_email,
                    COALESCE(SUM(c.commission_amount), 0) as total_earnings,
                    COUNT(DISTINCT c.referral_id) as referrals_count,
                    (SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . " v WHERE v.affiliate_id = a.id AND v.converted = 1 $date_condition) /
                    NULLIF((SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . " v WHERE v.affiliate_id = a.id $date_condition), 0) * 100 as conversion_rate
             FROM " . WCFM_Affiliate_DB::$table_affiliates . " a
             LEFT JOIN " . WCFM_Affiliate_DB::$table_commissions . " c ON a.id = c.affiliate_id $date_condition
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.status = 'active'
             GROUP BY a.id
             ORDER BY $order_column DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get top products
     */
    public function get_top_products(string $period = '30days', int $limit = 10): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.product_id, p.post_title as product_name,
                    COUNT(*) as referrals_count,
                    SUM(c.commission_amount) as total_commissions,
                    SUM(c.base_amount) as total_sales
             FROM " . WCFM_Affiliate_DB::$table_commissions . " c
             LEFT JOIN {$wpdb->posts} p ON c.product_id = p.ID
             WHERE c.product_id > 0 $date_condition
             GROUP BY c.product_id
             ORDER BY total_commissions DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get geographic data
     */
    public function get_geographic_data(string $period = '30days', ?int $affiliate_id = null): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        return $wpdb->get_results(
            "SELECT country, COUNT(*) as visits, SUM(converted) as conversions
             FROM " . WCFM_Affiliate_DB::$table_visits . "
             WHERE country != '' AND country IS NOT NULL $date_condition $affiliate_condition
             GROUP BY country
             ORDER BY visits DESC
             LIMIT 20"
        );
    }

    /**
     * Get device data
     */
    public function get_device_data(string $period = '30days', ?int $affiliate_id = null): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        return $wpdb->get_results(
            "SELECT device_type, COUNT(*) as count
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE device_type != '' AND device_type IS NOT NULL $date_condition $affiliate_condition
             GROUP BY device_type"
        );
    }

    /**
     * Get referrer data
     */
    public function get_referrer_data(string $period = '30days', ?int $affiliate_id = null): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        return $wpdb->get_results(
            "SELECT
                CASE
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                    WHEN referrer LIKE '%twitter%' OR referrer LIKE '%t.co%' THEN 'Twitter'
                    WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                    WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                    WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                    WHEN referrer = '' OR referrer IS NULL THEN 'Diretto'
                    ELSE 'Altro'
                END as source,
                COUNT(*) as count
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE 1=1 $date_condition $affiliate_condition
             GROUP BY source
             ORDER BY count DESC"
        );
    }

    /**
     * Get campaign performance
     */
    public function get_campaign_data(string $period = '30days', ?int $affiliate_id = null): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $affiliate_condition = '';
        if ($affiliate_id) {
            $affiliate_condition = $wpdb->prepare(" AND v.affiliate_id = %d", $affiliate_id);
        }

        return $wpdb->get_results(
            "SELECT v.campaign,
                    COUNT(DISTINCT v.id) as visits,
                    SUM(v.converted) as conversions,
                    COALESCE(SUM(r.amount), 0) as revenue
             FROM " . WCFM_Affiliate_DB::$table_visits . " v
             LEFT JOIN " . WCFM_Affiliate_DB::$table_referrals . " r ON v.order_id = r.order_id
             WHERE v.campaign != '' AND v.campaign IS NOT NULL $date_condition $affiliate_condition
             GROUP BY v.campaign
             ORDER BY visits DESC
             LIMIT 20"
        );
    }

    /**
     * Update stats (cron job)
     */
    public function update_stats(): void {
        global $wpdb;

        // Update affiliate conversion rates
        $wpdb->query(
            "UPDATE " . WCFM_Affiliate_DB::$table_affiliates . " a
             SET a.conversion_rate = (
                 SELECT IFNULL(SUM(converted) / NULLIF(COUNT(*), 0) * 100, 0)
                 FROM " . WCFM_Affiliate_DB::$table_visits . " v
                 WHERE v.affiliate_id = a.id
             )"
        );

        // Clean up old statistics cache
        delete_transient('wcfm_affiliate_stats_cache');

        do_action('wcfm_affiliate_stats_updated');
    }

    /**
     * Get report via AJAX
     */
    public function get_report_ajax(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('view_affiliate_reports')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? 'overview');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0) ?: null;

        switch ($report_type) {
            case 'overview':
                $data = $this->get_overview($period, $affiliate_id);
                break;
            case 'chart':
                $metric = sanitize_text_field($_POST['metric'] ?? 'commissions');
                $data = $this->get_chart_data($period, $metric, $affiliate_id);
                break;
            case 'top_affiliates':
                $data = $this->get_top_affiliates($period);
                break;
            case 'top_products':
                $data = $this->get_top_products($period);
                break;
            case 'geographic':
                $data = $this->get_geographic_data($period, $affiliate_id);
                break;
            case 'devices':
                $data = $this->get_device_data($period, $affiliate_id);
                break;
            case 'referrers':
                $data = $this->get_referrer_data($period, $affiliate_id);
                break;
            case 'campaigns':
                $data = $this->get_campaign_data($period, $affiliate_id);
                break;
            default:
                $data = [];
        }

        wp_send_json_success($data);
    }

    /**
     * Export report via AJAX
     */
    public function export_report_ajax(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('view_affiliate_reports')) {
            wp_send_json_error(['message' => __('Non autorizzato', 'wcfm-affiliate-pro')]);
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? 'commissions');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');

        $data = $this->get_export_data($report_type, $period);

        if ($format === 'csv') {
            $csv = $this->generate_csv($data, $report_type);
            wp_send_json_success(['csv' => $csv, 'filename' => "affiliate-report-{$report_type}-" . date('Y-m-d') . ".csv"]);
        }

        wp_send_json_error(['message' => __('Formato non supportato', 'wcfm-affiliate-pro')]);
    }

    /**
     * Get export data
     */
    private function get_export_data(string $report_type, string $period): array {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        switch ($report_type) {
            case 'commissions':
                return $wpdb->get_results(
                    "SELECT c.id, u.display_name as affiliate_name, c.order_id, c.product_id,
                            c.commission_amount, c.status, c.date_created
                     FROM " . WCFM_Affiliate_DB::$table_commissions . " c
                     LEFT JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON c.affiliate_id = a.id
                     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                     WHERE 1=1 $date_condition
                     ORDER BY c.date_created DESC",
                    ARRAY_A
                );

            case 'referrals':
                return $wpdb->get_results(
                    "SELECT r.id, u.display_name as affiliate_name, r.order_id, r.amount,
                            r.status, r.date_created
                     FROM " . WCFM_Affiliate_DB::$table_referrals . " r
                     LEFT JOIN " . WCFM_Affiliate_DB::$table_affiliates . " a ON r.affiliate_id = a.id
                     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                     WHERE 1=1 $date_condition
                     ORDER BY r.date_created DESC",
                    ARRAY_A
                );

            case 'affiliates':
                return $wpdb->get_results(
                    "SELECT a.id, u.display_name, u.user_email, a.affiliate_code, a.status,
                            a.earnings_balance, a.earnings_total, a.referrals_count, a.date_created
                     FROM " . WCFM_Affiliate_DB::$table_affiliates . " a
                     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                     ORDER BY a.date_created DESC",
                    ARRAY_A
                );

            default:
                return [];
        }
    }

    /**
     * Generate CSV
     */
    private function generate_csv(array $data, string $report_type): string {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Headers
        fputcsv($output, array_keys($data[0]));

        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get date condition
     */
    private function get_date_condition(string $period, string $table_alias = ''): string {
        $prefix = $table_alias ? "{$table_alias}." : '';

        return match($period) {
            '7days' => "AND {$prefix}date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30days' => "AND {$prefix}date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '90days' => "AND {$prefix}date_created >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            'year' => "AND {$prefix}date_created >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            'all' => '',
            default => "AND {$prefix}date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        };
    }

    /**
     * Get days from period
     */
    private function get_days_from_period(string $period): int {
        return match($period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            'all' => 365,
            default => 30,
        };
    }
}
