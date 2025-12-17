<?php
/**
 * Reports Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Reports {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('wp_ajax_jewe_export_report', [$this, 'handle_export']);
    }

    /**
     * Handle export request
     */
    public function handle_export() {
        check_ajax_referer('jewe_export_nonce', 'nonce');

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'commissions';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';

        // Check permissions
        $is_admin = current_user_can('manage_options');
        $affiliate_id = null;

        if (!$is_admin) {
            $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
            if (!$affiliate) {
                wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
            }
            $affiliate_id = $affiliate->id;
        }

        // Generate report
        switch ($type) {
            case 'commissions':
                $data = $this->get_commissions_report($affiliate_id, $period);
                break;
            case 'traffic':
                $data = $this->get_traffic_report($affiliate_id, $period);
                break;
            case 'team':
                $data = $this->get_team_report($affiliate_id);
                break;
            case 'affiliates':
                if (!$is_admin) {
                    wp_send_json_error(['message' => __('Non autorizzato', 'jewe-affiliate-pro')]);
                }
                $data = $this->get_affiliates_report($period);
                break;
            default:
                wp_send_json_error(['message' => __('Tipo report non valido', 'jewe-affiliate-pro')]);
        }

        // Export in requested format
        if ($format === 'csv') {
            $this->export_csv($data, $type);
        } else {
            $this->export_excel($data, $type);
        }
    }

    /**
     * Get commissions report data
     */
    private function get_commissions_report($affiliate_id, $period) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $date_from = $this->get_date_from($period);

        $where = "WHERE created_at >= '$date_from'";
        if ($affiliate_id) {
            $where .= " AND affiliate_id = " . intval($affiliate_id);
        }

        $results = $wpdb->get_results(
            "SELECT
                c.id,
                c.affiliate_id,
                c.order_id,
                c.product_id,
                c.commission_type,
                c.commission_rate,
                c.commission_amount,
                c.order_total,
                c.mlm_level,
                c.status,
                c.paid_at,
                c.created_at
             FROM $table c
             $where
             ORDER BY c.created_at DESC",
            ARRAY_A
        );

        // Add headers
        array_unshift($results, [
            'ID',
            'Affiliate ID',
            'Order ID',
            'Product ID',
            'Type',
            'Rate %',
            'Amount',
            'Order Total',
            'MLM Level',
            'Status',
            'Paid At',
            'Created At',
        ]);

        return $results;
    }

    /**
     * Get traffic report data
     */
    private function get_traffic_report($affiliate_id, $period) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_from = $this->get_date_from($period);

        $where = "WHERE created_at >= '$date_from'";
        if ($affiliate_id) {
            $where .= " AND affiliate_id = " . intval($affiliate_id);
        }

        $results = $wpdb->get_results(
            "SELECT
                DATE(created_at) as date,
                COUNT(*) as clicks,
                COUNT(DISTINCT visitor_hash) as unique_visitors,
                SUM(converted) as conversions,
                device_type,
                browser,
                utm_source,
                utm_medium,
                utm_campaign
             FROM $table
             $where
             GROUP BY DATE(created_at), device_type, browser, utm_source, utm_medium, utm_campaign
             ORDER BY date DESC",
            ARRAY_A
        );

        array_unshift($results, [
            'Date',
            'Clicks',
            'Unique Visitors',
            'Conversions',
            'Device',
            'Browser',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
        ]);

        return $results;
    }

    /**
     * Get team report data
     */
    private function get_team_report($affiliate_id) {
        $downline = JEWE_Affiliate_MLM::get_downline($affiliate_id, 5);

        $data = [];
        $data[] = [
            'Level',
            'Name',
            'Code',
            'Status',
            'Earnings',
            'Joined',
        ];

        foreach ($downline as $member) {
            $user = get_userdata($member->user_id);
            $data[] = [
                $member->level_depth,
                $user ? $user->display_name : 'N/A',
                $member->affiliate_code,
                $member->status,
                number_format($member->lifetime_earnings, 2),
                $member->created_at,
            ];
        }

        return $data;
    }

    /**
     * Get affiliates report data (admin)
     */
    private function get_affiliates_report($period) {
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
        $commissions_table = $wpdb->prefix . 'jewe_commissions';

        $date_from = $this->get_date_from($period);

        $results = $wpdb->get_results(
            "SELECT
                a.id,
                a.affiliate_code,
                a.status,
                a.tier_level,
                a.lifetime_earnings,
                a.current_balance,
                a.total_referrals,
                a.total_clicks,
                a.created_at,
                COALESCE(SUM(c.commission_amount), 0) as period_earnings,
                COUNT(c.id) as period_sales
             FROM $affiliates_table a
             LEFT JOIN $commissions_table c ON a.id = c.affiliate_id AND c.created_at >= '$date_from'
             GROUP BY a.id
             ORDER BY a.lifetime_earnings DESC",
            ARRAY_A
        );

        // Enhance with user data
        foreach ($results as &$row) {
            $affiliate = JEWE_Affiliate::get($row['id']);
            if ($affiliate) {
                $user = get_userdata($affiliate->user_id);
                $row['name'] = $user ? $user->display_name : 'N/A';
                $row['email'] = $user ? $user->user_email : 'N/A';
            }
        }

        // Reorder columns
        $data = [];
        $data[] = [
            'ID',
            'Name',
            'Email',
            'Code',
            'Status',
            'Tier',
            'Lifetime Earnings',
            'Current Balance',
            'Period Earnings',
            'Period Sales',
            'Referrals',
            'Clicks',
            'Joined',
        ];

        foreach ($results as $row) {
            $data[] = [
                $row['id'],
                $row['name'] ?? 'N/A',
                $row['email'] ?? 'N/A',
                $row['affiliate_code'],
                $row['status'],
                $row['tier_level'],
                $row['lifetime_earnings'],
                $row['current_balance'],
                $row['period_earnings'],
                $row['period_sales'],
                $row['total_referrals'],
                $row['total_clicks'],
                $row['created_at'],
            ];
        }

        return $data;
    }

    /**
     * Export as CSV
     */
    private function export_csv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export as Excel (using CSV with .xls extension for compatibility)
     */
    private function export_excel($data, $filename) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body><table border="1">';

        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . esc_html($cell) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    /**
     * Get date from period
     */
    private function get_date_from($period) {
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
     * Generate PDF report (requires external library)
     */
    public static function generate_pdf_report($affiliate_id, $period = '30days') {
        // This would require a PDF library like TCPDF or mPDF
        // Placeholder for future implementation
        return new WP_Error('not_implemented', __('PDF export non ancora disponibile', 'jewe-affiliate-pro'));
    }

    /**
     * Schedule automated reports
     */
    public static function schedule_report($affiliate_id, $frequency, $email) {
        // Placeholder for scheduled report functionality
        // Would use wp_schedule_event()
    }
}
