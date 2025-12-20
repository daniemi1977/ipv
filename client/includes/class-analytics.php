<?php
/**
 * IPV Production System Pro - Analytics Dashboard
 *
 * Statistiche aggregate, performance metrics, top videos, trending topics
 *
 * @package IPV_Production_System_Pro
 * @version 7.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Analytics {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 98 );

        // AJAX handlers
        add_action( 'wp_ajax_ipv_analytics_get_stats', [ __CLASS__, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_ipv_analytics_get_chart_data', [ __CLASS__, 'ajax_get_chart_data' ] );
        add_action( 'wp_ajax_ipv_analytics_get_top_videos', [ __CLASS__, 'ajax_get_top_videos' ] );
        add_action( 'wp_ajax_ipv_analytics_get_trending_topics', [ __CLASS__, 'ajax_get_trending_topics' ] );
    }

    /**
     * Add Analytics submenu
     */
    public static function add_submenu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Analytics',
            '<span class="dashicons dashicons-chart-bar"></span> Analytics',
            'manage_options',
            'ipv-production-analytics',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render Analytics Dashboard page
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $stats = self::get_aggregate_stats();
        ?>
        <div class="wrap ipv-analytics-wrap">
            <h1><i class="dashicons dashicons-chart-bar"></i> Analytics Dashboard</h1>
            <p class="description">Statistiche aggregate, performance video, trending topics</p>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">
                <!-- Total Videos -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="display-4 mb-0"><?php echo number_format( $stats['total_videos'] ); ?></h3>
                        <p class="text-muted mb-0">Video Totali</p>
                        <small class="text-success">✓ Pubblicati</small>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="display-4 mb-0"><?php echo self::format_number( $stats['total_views'] ); ?></h3>
                        <p class="text-muted mb-0">Visualizzazioni Totali</p>
                        <small class="text-muted">YouTube</small>
                    </div>
                </div>

                <!-- Total Duration -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="display-4 mb-0"><?php echo self::format_hours( $stats['total_duration'] ); ?></h3>
                        <p class="text-muted mb-0">Ore di Contenuto</p>
                        <small class="text-muted"><?php echo number_format( $stats['total_duration'] / 60 ); ?> minuti</small>
                    </div>
                </div>

                <!-- Avg Views -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="display-4 mb-0"><?php echo self::format_number( $stats['avg_views'] ); ?></h3>
                        <p class="text-muted mb-0">Media Views per Video</p>
                        <small class="text-muted">Engagement</small>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Videos Over Time Chart -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">📈 Video Pubblicati per Mese</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ipv-videos-timeline-chart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Views Distribution Chart -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">👁️ Distribuzione Views</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ipv-views-distribution-chart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Videos -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🏆 Top 10 Video per Visualizzazioni</h5>
                    <button class="btn btn-sm btn-outline-primary" id="ipv-refresh-top-videos">
                        <i class="dashicons dashicons-update"></i> Aggiorna
                    </button>
                </div>
                <div class="card-body">
                    <div id="ipv-top-videos-container">
                        <p class="text-center text-muted">Caricamento...</p>
                    </div>
                </div>
            </div>

            <!-- Trending Topics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">🔥 Trending Topics (da Transcript)</h5>
                </div>
                <div class="card-body">
                    <div id="ipv-trending-topics-container">
                        <p class="text-center text-muted">Caricamento...</p>
                    </div>
                </div>
            </div>

            <!-- Category Performance -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">📊 Performance per Categoria</h5>
                </div>
                <div class="card-body">
                    <canvas id="ipv-category-performance-chart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <style>
        .ipv-analytics-wrap .card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
        }
        .ipv-analytics-wrap .card-header {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .ipv-analytics-wrap .card-body {
            padding: 1.5rem;
        }
        .ipv-analytics-wrap .display-4 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #2271b1;
        }
        .ipv-analytics-wrap .shadow-sm {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ipv-top-video-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .ipv-top-video-item:last-child {
            border-bottom: none;
        }
        .ipv-top-video-thumb {
            width: 120px;
            height: 68px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        .ipv-top-video-info {
            flex: 1;
        }
        .ipv-top-video-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .ipv-top-video-meta {
            font-size: 0.875rem;
            color: #666;
        }
        .ipv-topic-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .ipv-topic-tag strong {
            color: #2271b1;
            margin-left: 0.5rem;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'ipv_analytics_nonce' ); ?>';

            // Load Top Videos
            function loadTopVideos() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ipv_analytics_get_top_videos',
                        nonce: nonce,
                        limit: 10
                    },
                    success: function(response) {
                        if (response.success) {
                            renderTopVideos(response.data);
                        }
                    }
                });
            }

            function renderTopVideos(videos) {
                let html = '';
                videos.forEach((video, index) => {
                    html += `
                        <div class="ipv-top-video-item">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #999; margin-right: 1rem; min-width: 30px;">#${index + 1}</div>
                            <img src="${video.thumbnail}" alt="${video.title}" class="ipv-top-video-thumb">
                            <div class="ipv-top-video-info">
                                <div class="ipv-top-video-title">${video.title}</div>
                                <div class="ipv-top-video-meta">
                                    👁️ ${video.views_formatted} visualizzazioni •
                                    ⏱️ ${video.duration} •
                                    📅 ${video.date}
                                </div>
                            </div>
                            <a href="${video.edit_url}" class="button button-small">Modifica</a>
                        </div>
                    `;
                });
                $('#ipv-top-videos-container').html(html);
            }

            // Load Trending Topics
            function loadTrendingTopics() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ipv_analytics_get_trending_topics',
                        nonce: nonce,
                        limit: 20
                    },
                    success: function(response) {
                        if (response.success) {
                            renderTrendingTopics(response.data);
                        }
                    }
                });
            }

            function renderTrendingTopics(topics) {
                let html = '<div style="padding: 1rem;">';
                topics.forEach(topic => {
                    html += `<span class="ipv-topic-tag">${topic.topic}<strong>${topic.count}</strong></span>`;
                });
                html += '</div>';
                $('#ipv-trending-topics-container').html(html);
            }

            // Load Chart Data
            function loadChartData() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ipv_analytics_get_chart_data',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderCharts(response.data);
                        }
                    }
                });
            }

            function renderCharts(data) {
                // Videos Timeline Chart
                new Chart(document.getElementById('ipv-videos-timeline-chart'), {
                    type: 'line',
                    data: {
                        labels: data.timeline.labels,
                        datasets: [{
                            label: 'Video Pubblicati',
                            data: data.timeline.values,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });

                // Views Distribution Chart
                new Chart(document.getElementById('ipv-views-distribution-chart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.views_distribution.labels,
                        datasets: [{
                            data: data.views_distribution.values,
                            backgroundColor: [
                                '#2271b1', '#72aee6', '#00a0d2', '#0073aa', '#005177'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true
                    }
                });

                // Category Performance Chart
                new Chart(document.getElementById('ipv-category-performance-chart'), {
                    type: 'bar',
                    data: {
                        labels: data.category_performance.labels,
                        datasets: [{
                            label: 'Visualizzazioni Totali',
                            data: data.category_performance.values,
                            backgroundColor: '#2271b1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            // Refresh button
            $('#ipv-refresh-top-videos').on('click', function() {
                loadTopVideos();
            });

            // Initial load
            loadTopVideos();
            loadTrendingTopics();
            loadChartData();
        });
        </script>
        <?php
    }

    /**
     * Get aggregate statistics
     */
    public static function get_aggregate_stats() {
        global $wpdb;

        $stats = [
            'total_videos' => 0,
            'total_views' => 0,
            'total_duration' => 0,
            'avg_views' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
        ];

        // Get all published videos
        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ] );

        $stats['total_videos'] = count( $videos );

        if ( empty( $videos ) ) {
            return $stats;
        }

        // Aggregate views and duration
        foreach ( $videos as $post_id ) {
            $views = (int) get_post_meta( $post_id, '_ipv_yt_view_count', true );
            $duration = (int) get_post_meta( $post_id, '_ipv_yt_duration_seconds', true );
            $likes = (int) get_post_meta( $post_id, '_ipv_yt_like_count', true );
            $comments = (int) get_post_meta( $post_id, '_ipv_yt_comment_count', true );

            $stats['total_views'] += $views;
            $stats['total_duration'] += $duration;
            $stats['total_likes'] += $likes;
            $stats['total_comments'] += $comments;
        }

        $stats['avg_views'] = $stats['total_videos'] > 0 ? round( $stats['total_views'] / $stats['total_videos'] ) : 0;

        return $stats;
    }

    /**
     * AJAX: Get aggregate stats
     */
    public static function ajax_get_stats() {
        check_ajax_referer( 'ipv_analytics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $stats = self::get_aggregate_stats();
        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Get chart data
     */
    public static function ajax_get_chart_data() {
        check_ajax_referer( 'ipv_analytics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $data = [
            'timeline' => self::get_timeline_data(),
            'views_distribution' => self::get_views_distribution(),
            'category_performance' => self::get_category_performance(),
        ];

        wp_send_json_success( $data );
    }

    /**
     * Get timeline data (videos published per month)
     */
    private static function get_timeline_data() {
        global $wpdb;

        $results = $wpdb->get_results( "
            SELECT
                DATE_FORMAT(post_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_status = 'publish'
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        " );

        $labels = [];
        $values = [];

        foreach ( array_reverse( $results ) as $row ) {
            $labels[] = date( 'M Y', strtotime( $row->month . '-01' ) );
            $values[] = (int) $row->count;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get views distribution (by range)
     */
    private static function get_views_distribution() {
        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ] );

        $ranges = [
            '0-1K' => 0,
            '1K-10K' => 0,
            '10K-50K' => 0,
            '50K-100K' => 0,
            '100K+' => 0,
        ];

        foreach ( $videos as $post_id ) {
            $views = (int) get_post_meta( $post_id, '_ipv_yt_view_count', true );

            if ( $views < 1000 ) {
                $ranges['0-1K']++;
            } elseif ( $views < 10000 ) {
                $ranges['1K-10K']++;
            } elseif ( $views < 50000 ) {
                $ranges['10K-50K']++;
            } elseif ( $views < 100000 ) {
                $ranges['50K-100K']++;
            } else {
                $ranges['100K+']++;
            }
        }

        return [
            'labels' => array_keys( $ranges ),
            'values' => array_values( $ranges ),
        ];
    }

    /**
     * Get category performance
     */
    private static function get_category_performance() {
        $categories = get_terms( [
            'taxonomy' => 'ipv_category',
            'hide_empty' => true,
        ] );

        $labels = [];
        $values = [];

        foreach ( $categories as $category ) {
            $videos = get_posts( [
                'post_type' => 'ipv_video',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'ipv_category',
                        'field' => 'term_id',
                        'terms' => $category->term_id,
                    ],
                ],
            ] );

            $total_views = 0;
            foreach ( $videos as $post_id ) {
                $total_views += (int) get_post_meta( $post_id, '_ipv_yt_view_count', true );
            }

            $labels[] = $category->name;
            $values[] = $total_views;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * AJAX: Get top videos
     */
    public static function ajax_get_top_videos() {
        check_ajax_referer( 'ipv_analytics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 10;

        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => '_ipv_yt_view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ] );

        $results = [];
        foreach ( $videos as $video ) {
            $views = (int) get_post_meta( $video->ID, '_ipv_yt_view_count', true );
            $duration = get_post_meta( $video->ID, '_ipv_yt_duration_formatted', true );
            $thumbnail = get_post_meta( $video->ID, '_ipv_yt_thumbnail_url', true );

            if ( empty( $thumbnail ) ) {
                $thumbnail = get_the_post_thumbnail_url( $video->ID, 'medium' );
            }

            $results[] = [
                'id' => $video->ID,
                'title' => get_the_title( $video->ID ),
                'views' => $views,
                'views_formatted' => self::format_number( $views ),
                'duration' => $duration ?: 'N/A',
                'date' => get_the_date( 'd M Y', $video->ID ),
                'thumbnail' => $thumbnail ?: 'https://via.placeholder.com/120x68?text=No+Thumb',
                'edit_url' => get_edit_post_link( $video->ID ),
            ];
        }

        wp_send_json_success( $results );
    }

    /**
     * AJAX: Get trending topics
     */
    public static function ajax_get_trending_topics() {
        check_ajax_referer( 'ipv_analytics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 20;

        // Extract topics from transcripts
        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => 100, // Last 100 videos
            'orderby' => 'date',
            'order' => 'DESC',
        ] );

        $topics_count = [];

        foreach ( $videos as $video ) {
            $transcript = get_post_meta( $video->ID, '_ipv_transcript', true );

            if ( empty( $transcript ) ) {
                continue;
            }

            // Extract common Italian words (simplified topic extraction)
            $words = str_word_count( strtolower( $transcript ), 1, 'àèéìòù' );

            // Filter stop words and short words
            $stop_words = [ 'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una', 'di', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra', 'a', 'del', 'dello', 'della', 'dei', 'degli', 'delle', 'al', 'allo', 'alla', 'ai', 'agli', 'alle', 'nel', 'nello', 'nella', 'nei', 'negli', 'nelle', 'sul', 'sullo', 'sulla', 'sui', 'sugli', 'sulle', 'e', 'è', 'o', 'che', 'chi', 'cui', 'non', 'più', 'anche', 'come', 'ma', 'se', 'ci', 'si', 'ne', 'questo', 'questa', 'questi', 'queste', 'quello', 'quella', 'quelli', 'quelle' ];

            foreach ( $words as $word ) {
                if ( strlen( $word ) > 4 && ! in_array( $word, $stop_words, true ) ) {
                    if ( ! isset( $topics_count[ $word ] ) ) {
                        $topics_count[ $word ] = 0;
                    }
                    $topics_count[ $word ]++;
                }
            }
        }

        // Sort by frequency
        arsort( $topics_count );
        $topics_count = array_slice( $topics_count, 0, $limit, true );

        $results = [];
        foreach ( $topics_count as $topic => $count ) {
            $results[] = [
                'topic' => ucfirst( $topic ),
                'count' => $count,
            ];
        }

        wp_send_json_success( $results );
    }

    /**
     * Format large numbers
     */
    private static function format_number( $num ) {
        if ( $num >= 1000000 ) {
            return round( $num / 1000000, 1 ) . 'M';
        }
        if ( $num >= 1000 ) {
            return round( $num / 1000, 1 ) . 'K';
        }
        return number_format( $num );
    }

    /**
     * Format hours
     */
    private static function format_hours( $seconds ) {
        $hours = round( $seconds / 3600, 1 );
        return number_format( $hours, 1 ) . 'h';
    }
}

// Disabilitato - ripetizione di Dashboard, non necessario per v9.0.0
// IPV_Prod_Analytics::init();
