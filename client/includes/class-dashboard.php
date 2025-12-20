<?php
/**
 * IPV Production System Pro - Dashboard
 *
 * Pannello dashboard con panoramica crediti, statistiche e quick actions
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Dashboard {

    public static function init() {
        // v10.0.24 - Menu registration moved to Menu Manager
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function enqueue_scripts( $hook ) {
        // Accept both old and new hook names
        if ( ! in_array( $hook, [ 'ipv_video_page_ipv-dashboard', 'toplevel_page_ipv-production', 'ipv-videos_page_ipv-production' ] ) ) {
            return;
        }

        wp_enqueue_style( 'ipv-dashboard', IPV_PROD_PLUGIN_URL . 'assets/css/admin.css', [], IPV_PROD_VERSION );
    }

    /**
     * Render page - chiamato da Menu Manager
     */
    public static function render_page() {
        self::render();
    }

    public static function render() {
        $license_info = get_option( 'ipv_license_info', [] );
        $is_active = IPV_Prod_API_Client_Optimized::is_license_active();

        // Stats
        $stats = self::get_stats();

        ?>
        <div class="wrap ipv-dashboard">
            <h1>📊 <?php _e( 'Dashboard', 'ipv-production-system-pro' ); ?></h1>

            <div class="ipv-dashboard-grid">

                <!-- Licenza e Crediti -->
                <div class="ipv-dashboard-card ipv-license-card">
                    <h2><?php _e( 'Licenza & Crediti', 'ipv-production-system-pro' ); ?></h2>

                    <?php if ( $is_active ) :
                        $credits = $license_info['credits'] ?? [];
                        $percentage = $credits['percentage'] ?? 0;
                        $bar_color = $percentage > 50 ? '#28a745' : ( $percentage > 20 ? '#ffc107' : '#dc3545' );
                    ?>
                        <div class="ipv-license-active">
                            <div class="ipv-license-badge">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e( 'Licenza Attiva', 'ipv-production-system-pro' ); ?>
                            </div>

                            <div class="ipv-license-info">
                                <div class="ipv-info-row">
                                    <span class="label"><?php _e( 'Piano:', 'ipv-production-system-pro' ); ?></span>
                                    <span class="value"><strong><?php echo esc_html( ucfirst( $license_info['variant'] ?? 'N/A' ) ); ?></strong></span>
                                </div>
                                <?php if ( ! empty( $license_info['expires_at'] ) ) : ?>
                                <div class="ipv-info-row">
                                    <span class="label"><?php _e( 'Scadenza:', 'ipv-production-system-pro' ); ?></span>
                                    <span class="value"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license_info['expires_at'] ) ) ); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="ipv-credits-section">
                                <h3><?php _e( 'Crediti Mensili', 'ipv-production-system-pro' ); ?></h3>
                                <div class="ipv-credits-bar">
                                    <div class="ipv-credits-progress" style="width: <?php echo $percentage; ?>%; background: <?php echo $bar_color; ?>;"></div>
                                </div>
                                <div class="ipv-credits-text">
                                    <strong><?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?></strong> /
                                    <?php echo esc_html( $credits['credits_total'] ?? 0 ); ?>
                                    <?php if ( ! empty( $credits['reset_date_formatted'] ) ) : ?>
                                        <span class="ipv-reset-date">
                                            <?php printf( __( 'Reset: %s', 'ipv-production-system-pro' ), esc_html( $credits['reset_date_formatted'] ) ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="ipv-license-inactive">
                            <span class="dashicons dashicons-warning"></span>
                            <p><?php _e( 'Licenza non attiva', 'ipv-production-system-pro' ); ?></p>
                            <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>" class="button button-primary">
                                <?php _e( 'Attiva Licenza →', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statistiche Video -->
                <div class="ipv-dashboard-card ipv-stats-card">
                    <h2><?php _e( 'Statistiche Video', 'ipv-production-system-pro' ); ?></h2>

                    <div class="ipv-stats-grid">
                        <div class="ipv-stat-box">
                            <div class="ipv-stat-icon">📺</div>
                            <div class="ipv-stat-value"><?php echo number_format_i18n( $stats['total_videos'] ); ?></div>
                            <div class="ipv-stat-label"><?php _e( 'Video Totali', 'ipv-production-system-pro' ); ?></div>
                        </div>

                        <div class="ipv-stat-box">
                            <div class="ipv-stat-icon">✅</div>
                            <div class="ipv-stat-value"><?php echo number_format_i18n( $stats['published'] ); ?></div>
                            <div class="ipv-stat-label"><?php _e( 'Pubblicati', 'ipv-production-system-pro' ); ?></div>
                        </div>

                        <div class="ipv-stat-box">
                            <div class="ipv-stat-icon">📝</div>
                            <div class="ipv-stat-value"><?php echo number_format_i18n( $stats['drafts'] ); ?></div>
                            <div class="ipv-stat-label"><?php _e( 'Bozze', 'ipv-production-system-pro' ); ?></div>
                        </div>

                        <div class="ipv-stat-box">
                            <div class="ipv-stat-icon">🆕</div>
                            <div class="ipv-stat-value"><?php echo number_format_i18n( $stats['today'] ); ?></div>
                            <div class="ipv-stat-label"><?php _e( 'Oggi', 'ipv-production-system-pro' ); ?></div>
                        </div>
                    </div>

                    <?php if ( $stats['queue_pending'] > 0 ) : ?>
                    <div class="ipv-queue-status">
                        <span class="dashicons dashicons-update"></span>
                        <?php printf(
                            _n( '%d video in coda', '%d video in coda', $stats['queue_pending'], 'ipv-production-system-pro' ),
                            $stats['queue_pending']
                        ); ?>
                        <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-queue' ); ?>">
                            <?php _e( 'Vedi coda →', 'ipv-production-system-pro' ); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="ipv-dashboard-card ipv-quick-actions">
                    <h2><?php _e( 'Azioni Rapide', 'ipv-production-system-pro' ); ?></h2>

                    <div class="ipv-actions-grid">
                        <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-import' ); ?>" class="ipv-action-button">
                            <span class="dashicons dashicons-video-alt2"></span>
                            <span class="ipv-action-label"><?php _e( 'Importa Video', 'ipv-production-system-pro' ); ?></span>
                        </a>

                        <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="ipv-action-button">
                            <span class="dashicons dashicons-list-view"></span>
                            <span class="ipv-action-label"><?php _e( 'Tutti i Video', 'ipv-production-system-pro' ); ?></span>
                        </a>

                        <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-settings' ); ?>" class="ipv-action-button">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span class="ipv-action-label"><?php _e( 'Impostazioni', 'ipv-production-system-pro' ); ?></span>
                        </a>

                        <?php if ( class_exists( 'IPV_Prod_Queue' ) ) : ?>
                        <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-queue' ); ?>" class="ipv-action-button">
                            <span class="dashicons dashicons-randomize"></span>
                            <span class="ipv-action-label"><?php _e( 'Coda Import', 'ipv-production-system-pro' ); ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ultimi Video Importati -->
                <div class="ipv-dashboard-card ipv-recent-videos">
                    <h2><?php _e( 'Ultimi Video Importati', 'ipv-production-system-pro' ); ?></h2>

                    <?php
                    $recent_videos = get_posts([
                        'post_type' => 'ipv_video',
                        'posts_per_page' => 5,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ]);

                    if ( $recent_videos ) :
                    ?>
                        <div class="ipv-recent-list">
                            <?php foreach ( $recent_videos as $video ) :
                                $video_id = get_post_meta( $video->ID, '_ipv_video_id', true );
                                $duration = get_post_meta( $video->ID, '_ipv_yt_duration_formatted', true );
                            ?>
                                <div class="ipv-recent-item">
                                    <?php if ( has_post_thumbnail( $video->ID ) ) : ?>
                                        <div class="ipv-recent-thumb">
                                            <?php echo get_the_post_thumbnail( $video->ID, 'thumbnail' ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="ipv-recent-info">
                                        <a href="<?php echo get_edit_post_link( $video->ID ); ?>" class="ipv-recent-title">
                                            <?php echo esc_html( $video->post_title ); ?>
                                        </a>
                                        <div class="ipv-recent-meta">
                                            <?php if ( $duration ) : ?>
                                                <span class="ipv-duration">⏱️ <?php echo esc_html( $duration ); ?></span>
                                            <?php endif; ?>
                                            <span class="ipv-date"><?php echo human_time_diff( get_the_time( 'U', $video ), current_time( 'timestamp' ) ); ?> fa</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="ipv-no-videos"><?php _e( 'Nessun video ancora. Inizia importando il tuo primo video!', 'ipv-production-system-pro' ); ?></p>
                    <?php endif; ?>

                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="button button-secondary">
                        <?php _e( 'Vedi tutti →', 'ipv-production-system-pro' ); ?>
                    </a>
                </div>

            </div>
        </div>

        <style>
        .ipv-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .ipv-dashboard-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .ipv-dashboard-card h2 {
            margin-top: 0;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .ipv-license-active .ipv-license-badge {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .ipv-license-badge .dashicons {
            color: #28a745;
            vertical-align: middle;
        }
        .ipv-license-inactive {
            text-align: center;
            padding: 20px;
            background: #fff3cd;
            border-radius: 4px;
        }
        .ipv-license-inactive .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #856404;
        }
        .ipv-license-info {
            margin-bottom: 15px;
        }
        .ipv-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .ipv-credits-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .ipv-credits-section h3 {
            margin-top: 0;
            font-size: 14px;
        }
        .ipv-credits-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .ipv-credits-progress {
            height: 100%;
            transition: width 0.3s;
        }
        .ipv-credits-text {
            font-size: 14px;
            text-align: center;
        }
        .ipv-reset-date {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .ipv-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .ipv-stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .ipv-stat-icon {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .ipv-stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2271b1;
        }
        .ipv-stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .ipv-queue-status {
            background: #cfe2ff;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin-top: 15px;
        }
        .ipv-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .ipv-action-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .ipv-action-button:hover {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .ipv-action-button .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 5px;
        }
        .ipv-action-label {
            font-size: 13px;
            font-weight: 600;
        }
        .ipv-recent-list {
            margin: 15px 0;
        }
        .ipv-recent-item {
            display: flex;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .ipv-recent-item:last-child {
            border-bottom: none;
        }
        .ipv-recent-thumb {
            flex-shrink: 0;
        }
        .ipv-recent-thumb img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .ipv-recent-info {
            flex: 1;
        }
        .ipv-recent-title {
            font-weight: 600;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }
        .ipv-recent-meta {
            font-size: 12px;
            color: #666;
        }
        .ipv-recent-meta span {
            margin-right: 10px;
        }
        .ipv-no-videos {
            text-align: center;
            color: #666;
            padding: 20px;
        }
        </style>
        <?php
    }

    /**
     * Get dashboard stats
     */
    private static function get_stats() {
        $stats = [
            'total_videos' => 0,
            'published' => 0,
            'drafts' => 0,
            'today' => 0,
            'queue_pending' => 0
        ];

        // Total videos
        $stats['total_videos'] = wp_count_posts( 'ipv_video' )->publish + wp_count_posts( 'ipv_video' )->draft;
        $stats['published'] = wp_count_posts( 'ipv_video' )->publish;
        $stats['drafts'] = wp_count_posts( 'ipv_video' )->draft;

        // Today's imports
        $today = get_posts([
            'post_type' => 'ipv_video',
            'date_query' => [
                [
                    'after' => 'today',
                    'inclusive' => true
                ]
            ],
            'fields' => 'ids'
        ]);
        $stats['today'] = count( $today );

        // Queue pending (if queue exists)
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            global $wpdb;
            $stats['queue_pending'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_prod_queue WHERE status IN ('pending', 'processing')"
            );
        }

        return $stats;
    }
}
