<?php
/**
 * Analytics Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Analytics {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get dashboard KPIs
     */
    public static function get_dashboard_kpis($affiliate_id = null, $period = '30days') {
        $date_range = self::get_date_range($period);

        $kpis = [
            'total_earnings' => self::get_total_earnings($affiliate_id, $date_range),
            'total_clicks' => self::get_total_clicks($affiliate_id, $date_range),
            'total_conversions' => self::get_total_conversions($affiliate_id, $date_range),
            'conversion_rate' => 0,
            'avg_order_value' => self::get_avg_order_value($affiliate_id, $date_range),
            'pending_commissions' => self::get_pending_commissions($affiliate_id),
            'top_products' => self::get_top_products($affiliate_id, $date_range, 5),
            'earnings_trend' => self::get_earnings_trend($affiliate_id, $period),
        ];

        // Calculate conversion rate
        if ($kpis['total_clicks'] > 0) {
            $kpis['conversion_rate'] = round(($kpis['total_conversions'] / $kpis['total_clicks']) * 100, 2);
        }

        // Get comparison with previous period
        $prev_range = self::get_previous_date_range($period);
        $kpis['comparison'] = [
            'earnings_change' => self::calculate_change(
                self::get_total_earnings($affiliate_id, $prev_range),
                $kpis['total_earnings']
            ),
            'clicks_change' => self::calculate_change(
                self::get_total_clicks($affiliate_id, $prev_range),
                $kpis['total_clicks']
            ),
            'conversions_change' => self::calculate_change(
                self::get_total_conversions($affiliate_id, $prev_range),
                $kpis['total_conversions']
            ),
        ];

        return $kpis;
    }

    /**
     * Get date range from period
     */
    private static function get_date_range($period) {
        $end = date('Y-m-d 23:59:59');

        switch ($period) {
            case 'today':
                $start = date('Y-m-d 00:00:00');
                break;
            case '7days':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case '30days':
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case '90days':
                $start = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
            case 'year':
                $start = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
            case 'month':
                $start = date('Y-m-01 00:00:00');
                break;
            default:
                $start = '2000-01-01 00:00:00';
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get previous period date range
     */
    private static function get_previous_date_range($period) {
        $current = self::get_date_range($period);
        $diff = strtotime($current['end']) - strtotime($current['start']);

        return [
            'start' => date('Y-m-d H:i:s', strtotime($current['start']) - $diff - 1),
            'end' => date('Y-m-d H:i:s', strtotime($current['start']) - 1),
        ];
    }

    /**
     * Get total earnings
     */
    private static function get_total_earnings($affiliate_id, $date_range) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE created_at BETWEEN %s AND %s";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(commission_amount), 0) FROM $table $where",
            ...$params
        )));
    }

    /**
     * Get total clicks
     */
    private static function get_total_clicks($affiliate_id, $date_range) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $where = "WHERE created_at BETWEEN %s AND %s";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table $where",
            ...$params
        )));
    }

    /**
     * Get total conversions
     */
    private static function get_total_conversions($affiliate_id, $date_range) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE created_at BETWEEN %s AND %s AND commission_type = 'sale'";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table $where",
            ...$params
        )));
    }

    /**
     * Get average order value
     */
    private static function get_avg_order_value($affiliate_id, $date_range) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE created_at BETWEEN %s AND %s AND commission_type = 'sale'";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(AVG(order_total), 0) FROM $table $where",
            ...$params
        )));
    }

    /**
     * Get pending commissions
     */
    private static function get_pending_commissions($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE status = 'pending'";
        if ($affiliate_id) {
            $where .= $wpdb->prepare(" AND affiliate_id = %d", $affiliate_id);
        }

        return floatval($wpdb->get_var("SELECT COALESCE(SUM(commission_amount), 0) FROM $table $where"));
    }

    /**
     * Get top products
     */
    private static function get_top_products($affiliate_id, $date_range, $limit = 5) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $where = "WHERE created_at BETWEEN %s AND %s AND product_id > 0";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        $params[] = $limit;

        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT product_id, COUNT(*) as sales, SUM(commission_amount) as earnings
             FROM $table $where
             GROUP BY product_id
             ORDER BY sales DESC
             LIMIT %d",
            ...$params
        ));

        // Enhance with product data
        foreach ($products as &$product) {
            $wc_product = wc_get_product($product->product_id);
            if ($wc_product) {
                $product->name = $wc_product->get_name();
                $product->image = wp_get_attachment_image_url($wc_product->get_image_id(), 'thumbnail');
            } else {
                $product->name = __('Product #', 'jewe-affiliate-pro') . $product->product_id;
                $product->image = '';
            }
        }

        return $products;
    }

    /**
     * Get earnings trend
     */
    private static function get_earnings_trend($affiliate_id, $period) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_commissions';

        $date_range = self::get_date_range($period);
        $group_format = $period === 'year' ? '%Y-%m' : '%Y-%m-%d';

        $where = "WHERE created_at BETWEEN %s AND %s";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, '$group_format') as date,
                SUM(commission_amount) as earnings,
                COUNT(*) as sales
             FROM $table $where
             GROUP BY DATE_FORMAT(created_at, '$group_format')
             ORDER BY date ASC",
            ...$params
        ));
    }

    /**
     * Calculate percentage change
     */
    private static function calculate_change($old, $new) {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }
        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Get geographic data
     */
    public static function get_geographic_data($affiliate_id = null, $period = '30days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_range = self::get_date_range($period);

        $where = "WHERE created_at BETWEEN %s AND %s AND country != ''";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as clicks, SUM(converted) as conversions
             FROM $table $where
             GROUP BY country
             ORDER BY clicks DESC
             LIMIT 20",
            ...$params
        ));
    }

    /**
     * Get hourly performance
     */
    public static function get_hourly_performance($affiliate_id = null, $period = '7days') {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_tracking';

        $date_range = self::get_date_range($period);

        $where = "WHERE created_at BETWEEN %s AND %s";
        $params = [$date_range['start'], $date_range['end']];

        if ($affiliate_id) {
            $where .= " AND affiliate_id = %d";
            $params[] = $affiliate_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                HOUR(created_at) as hour,
                COUNT(*) as clicks,
                SUM(converted) as conversions
             FROM $table $where
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC",
            ...$params
        ));
    }

    /**
     * Get program overview (admin)
     */
    public static function get_program_overview($period = '30days') {
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'jewe_affiliates';
        $commissions_table = $wpdb->prefix . 'jewe_commissions';

        $date_range = self::get_date_range($period);

        // Total affiliates
        $total_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM $affiliates_table");
        $active_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM $affiliates_table WHERE status = 'active'");

        // Period stats
        $period_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_sales,
                SUM(commission_amount) as total_commissions,
                SUM(order_total) as total_revenue
             FROM $commissions_table
             WHERE created_at BETWEEN %s AND %s AND commission_type = 'sale'",
            $date_range['start'],
            $date_range['end']
        ));

        // New affiliates in period
        $new_affiliates = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $affiliates_table WHERE created_at BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));

        return [
            'total_affiliates' => intval($total_affiliates),
            'active_affiliates' => intval($active_affiliates),
            'new_affiliates' => intval($new_affiliates),
            'total_sales' => intval($period_stats->total_sales ?? 0),
            'total_commissions' => floatval($period_stats->total_commissions ?? 0),
            'total_revenue' => floatval($period_stats->total_revenue ?? 0),
        ];
    }
}
