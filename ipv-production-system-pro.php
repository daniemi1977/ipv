<?php
/**
 * Plugin Name: IPV Production System Pro
 * Plugin URI: https://aiedintorni.it
 * Description: Sistema di produzione video per "Il Punto di Vista": importazione YouTube, trascrizioni SupaData, AI con Golden Prompt, Video Wall con filtri AJAX.
 * Version: 7.7.2
 * Author: Daniele / IPV
 * Text Domain: ipv-production-system-pro
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================
// CONSTANTS
// ============================================

define( 'IPV_PROD_VERSION', '7.7.2' );
define( 'IPV_PROD_PLUGIN_FILE', __FILE__ );
define( 'IPV_PROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_PROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ============================================
// AUTOLOAD CLASSES
// ============================================

spl_autoload_register( function( $class ) {
    $prefix = 'IPV_Prod_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }
    
    $class_file = str_replace( $prefix, '', $class );
    $class_file = strtolower( str_replace( '_', '-', $class_file ) );
    $file = IPV_PROD_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
    
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ============================================
// CORE INCLUDES
// ============================================

require_once IPV_PROD_PLUGIN_DIR . 'includes/class-logger.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-settings.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cpt.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-supadata.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-ai-generator.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-queue.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-rss-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-api.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-bulk-import.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-simple-import.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-frontend.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-speaker-rules.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-bulk-tools.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-wall.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-wall-settings.php';

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Convert YouTube ISO 8601 duration to seconds
 */
function ipv_duration_to_seconds( $duration ) {
    if ( empty( $duration ) ) {
        return 0;
    }
    
    $pattern = '/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/';
    preg_match( $pattern, $duration, $matches );

    $hours   = isset( $matches[1] ) ? (int) $matches[1] : 0;
    $minutes = isset( $matches[2] ) ? (int) $matches[2] : 0;
    $seconds = isset( $matches[3] ) ? (int) $matches[3] : 0;

    return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
}

/**
 * Get formatted duration from post ID
 */
function ipv_get_formatted_duration( $post_id ) {
    // First try formatted duration
    $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
    if ( ! empty( $duration_formatted ) ) {
        return $duration_formatted;
    }
    
    // Fallback to seconds
    $duration_sec = get_post_meta( $post_id, '_ipv_yt_duration_seconds', true );

    if ( ! empty( $duration_sec ) ) {
        $duration_sec = (int) $duration_sec;
        $hours   = floor( $duration_sec / 3600 );
        $minutes = floor( ( $duration_sec % 3600 ) / 60 );
        $seconds = $duration_sec % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $seconds );
        }
        return sprintf( '%d:%02d', $minutes, $seconds );
    }

    return '';
}

// ============================================
// MAIN PLUGIN CLASS
// ============================================

class IPV_Production_System_Pro {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // CPT & Taxonomies
        add_action( 'init', [ 'IPV_Prod_CPT', 'register' ] );

        // Settings
        add_action( 'admin_init', [ 'IPV_Prod_Settings', 'register_settings' ] );
        add_action( 'admin_init', [ 'IPV_Prod_Video_Wall_Settings', 'register_settings' ] );

        // Video Wall
        IPV_Prod_Video_Wall::init();

        // Admin menu
        add_action( 'admin_menu', [ $this, 'register_menu' ] );

        // Admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Cron
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
        add_action( 'ipv_prod_process_queue', [ 'IPV_Prod_Queue', 'process_queue' ] );
        add_action( 'ipv_prod_update_youtube_data', [ 'IPV_Prod_Queue', 'update_all_youtube_data' ] );

        // Auto-schedule CRON se non esiste (fix per aggiornamenti plugin)
        add_action( 'admin_init', [ $this, 'ensure_cron_scheduled' ] );

        // AJAX
        add_action( 'wp_ajax_ipv_prod_get_stats', [ $this, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_ipv_prod_process_queue', [ $this, 'ajax_process_queue' ] );
        add_action( 'wp_ajax_ipv_prod_start_cron', [ $this, 'ajax_start_cron' ] );

        // Activation/Deactivation
        register_activation_hook( IPV_PROD_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( IPV_PROD_PLUGIN_FILE, [ $this, 'deactivate' ] );

        // Save duration on post save
        add_action( 'save_post_ipv_video', [ $this, 'save_duration_seconds' ] );

        // Markdown filter
        add_filter( 'the_content', [ $this, 'filter_video_content' ] );
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            'IPV Production System',
            'IPV Production',
            'manage_options',
            'ipv-production',
            [ $this, 'render_dashboard' ],
            'dashicons-video-alt3',
            25
        );

        // Dashboard
        add_submenu_page(
            'ipv-production',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ipv-production',
            [ $this, 'render_dashboard' ]
        );

        // Import Video
        add_submenu_page(
            'ipv-production',
            'Importa Video',
            'Importa Video',
            'manage_options',
            'ipv-production-import',
            [ 'IPV_Prod_YouTube_Importer', 'render_page' ]
        );

        // RSS Auto-Import
        add_submenu_page(
            'ipv-production',
            'Auto-Import RSS',
            'Auto-Import RSS',
            'manage_options',
            'ipv-production-rss',
            [ 'IPV_Prod_RSS_Importer', 'render_settings_page' ]
        );

        // Queue
        add_submenu_page(
            'ipv-production',
            'Coda',
            'Coda',
            'manage_options',
            'ipv-production-queue',
            [ 'IPV_Prod_Bulk_Import', 'render_page' ]
        );

        // Video Wall
        add_submenu_page(
            'ipv-production',
            'Video Wall',
            'Video Wall',
            'manage_options',
            'ipv-production-video-wall',
            [ 'IPV_Prod_Video_Wall_Settings', 'render_settings_page' ]
        );

        // Settings
        add_submenu_page(
            'ipv-production',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'ipv-production-settings',
            [ 'IPV_Prod_Settings', 'render_page' ]
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'ipv-production' ) === false ) {
            return;
        }

        // Bootstrap 5
        wp_enqueue_style( 'ipv-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', [], '5.3.2' );
        wp_enqueue_style( 'ipv-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1' );
        wp_enqueue_script( 'ipv-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', [], '5.3.2', true );

        // Chart.js
        wp_enqueue_script( 'ipv-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true );

        // Admin CSS & JS
        wp_enqueue_style( 'ipv-admin', IPV_PROD_PLUGIN_URL . 'assets/css/admin.css', [], IPV_PROD_VERSION );
        wp_enqueue_script( 'ipv-admin', IPV_PROD_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'ipv-chartjs', 'ipv-bootstrap-js' ], IPV_PROD_VERSION, true );

        wp_localize_script( 'ipv-admin', 'ipvAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ipv_admin_nonce' ),
        ] );
    }

    /**
     * Add cron schedules
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['ipv_every_5_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => 'Every 5 Minutes',
        ];
        return $schedules;
    }

    /**
     * Render Dashboard
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        // Stats
        $total_videos = wp_count_posts( 'ipv_video' );
        $published = isset( $total_videos->publish ) ? $total_videos->publish : 0;
        $pending = isset( $total_videos->pending ) ? $total_videos->pending : 0;
        $draft = isset( $total_videos->draft ) ? $total_videos->draft : 0;

        // API Keys
        $supadata_key = get_option( 'ipv_supadata_api_key', '' );
        $openai_key = get_option( 'ipv_openai_api_key', '' );
        $youtube_key = get_option( 'ipv_youtube_api_key', '' );
        
        // RSS
        $rss_enabled = get_option( 'ipv_rss_enabled', false );
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header mb-4">
                <h1 class="mb-1">
                    <i class="bi bi-play-circle-fill text-primary me-2"></i>
                    IPV Production System Pro
                </h1>
                <p class="text-muted">
                    <span class="badge bg-success">v<?php echo esc_html( IPV_PROD_VERSION ); ?></span>
                    Golden Prompt Edition
                </p>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h2 class="display-4 mb-0"><?php echo esc_html( $published ); ?></h2>
                            <p class="text-muted mb-0">Video Pubblicati</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h2 class="display-4 mb-0"><?php echo esc_html( $pending + $draft ); ?></h2>
                            <p class="text-muted mb-0">In Coda</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <?php if ( $rss_enabled ) : ?>
                                <span class="badge bg-success fs-5">✓ Attivo</span>
                            <?php else : ?>
                                <span class="badge bg-secondary fs-5">Disattivo</span>
                            <?php endif; ?>
                            <p class="text-muted mb-0 mt-2">Auto-Import RSS</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <?php if ( wp_next_scheduled( 'ipv_prod_process_queue' ) ) : ?>
                                <span class="badge bg-success fs-5">✓ Attivo</span>
                                <p class="text-muted mb-0 mt-2">CRON</p>
                                <small class="text-muted">Prossima: <?php echo date( 'H:i:s', wp_next_scheduled( 'ipv_prod_process_queue' ) ); ?></small>
                            <?php else : ?>
                                <span class="badge bg-danger fs-5">Fermo</span>
                                <p class="text-muted mb-0 mt-2">CRON</p>
                                <button type="button" class="btn btn-sm btn-success mt-2" id="ipv-start-cron-btn">
                                    ▶ Avvia CRON
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pulsante Elabora Coda Manuale -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Elaborazione Manuale</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Se il CRON non funziona, puoi elaborare la coda manualmente:</p>
                    <button type="button" class="btn btn-primary" id="ipv-process-queue-btn">
                        <i class="bi bi-play-fill me-1"></i> Elabora Coda Adesso
                    </button>
                    <span id="ipv-process-result" class="ms-3"></span>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                // Avvia CRON
                $('#ipv-start-cron-btn').on('click', function() {
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('Avvio...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ipv_prod_start_cron',
                            nonce: '<?php echo wp_create_nonce( 'ipv_admin_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('CRON avviato! Prossima esecuzione: ' + response.data.next_run);
                                location.reload();
                            } else {
                                alert('Errore: ' + response.data);
                                $btn.prop('disabled', false).text('▶ Avvia CRON');
                            }
                        },
                        error: function() {
                            alert('Errore di connessione');
                            $btn.prop('disabled', false).text('▶ Avvia CRON');
                        }
                    });
                });

                // Elabora coda manualmente
                $('#ipv-process-queue-btn').on('click', function() {
                    var $btn = $(this);
                    var $result = $('#ipv-process-result');
                    
                    $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Elaborazione...');
                    $result.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ipv_prod_process_queue',
                            nonce: '<?php echo wp_create_nonce( 'ipv_admin_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span class="text-success">✓ ' + response.data.message + '</span>');
                            } else {
                                $result.html('<span class="text-danger">✗ Errore</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span class="text-danger">✗ Errore connessione</span>');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> Elabora Coda Adesso');
                        }
                    });
                });
            });
            </script>

            <!-- API Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-key me-2"></i>Stato API</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if ( $supadata_key ) : ?>
                                <i class="bi bi-check-circle-fill text-success"></i> SupaData: <strong>OK</strong>
                            <?php else : ?>
                                <i class="bi bi-x-circle-fill text-danger"></i> SupaData: <strong>Mancante</strong>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <?php if ( $openai_key ) : ?>
                                <i class="bi bi-check-circle-fill text-success"></i> OpenAI: <strong>OK</strong>
                            <?php else : ?>
                                <i class="bi bi-x-circle-fill text-danger"></i> OpenAI: <strong>Mancante</strong>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <?php if ( $youtube_key ) : ?>
                                <i class="bi bi-check-circle-fill text-success"></i> YouTube: <strong>OK</strong>
                            <?php else : ?>
                                <i class="bi bi-x-circle-fill text-danger"></i> YouTube: <strong>Mancante</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Azioni Rapide</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-production-import' ); ?>" class="btn btn-primary me-2">
                        <i class="bi bi-youtube me-1"></i> Importa Video
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-list me-1"></i> Gestisci Video
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-production-settings' ); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i> Impostazioni
                    </a>
                </div>
            </div>

            <div class="mt-4 text-muted small">
                <strong>Shortcode:</strong> <code>[ipv_video_wall]</code> oppure <code>[ipv_video_wall show_filters="yes"]</code>
            </div>
        </div>
        <?php
    }

    /**
     * Filter video content for markdown
     */
    public function filter_video_content( $content ) {
        if ( get_post_type() !== 'ipv_video' ) {
            return $content;
        }

        $md = get_post_meta( get_the_ID(), '_ipv_ai_description', true );
        if ( $md && class_exists( 'IPV_Markdown_Full' ) ) {
            return IPV_Markdown_Full::parse( $md );
        }

        return $content;
    }

    /**
     * Save duration in seconds
     */
    public function save_duration_seconds( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $duration_seconds = get_post_meta( $post_id, '_ipv_duration_seconds', true );
        if ( empty( $duration_seconds ) ) {
            $duration_iso = get_post_meta( $post_id, '_ipv_yt_duration', true );
            if ( $duration_iso ) {
                update_post_meta( $post_id, '_ipv_duration_seconds', ipv_duration_to_seconds( $duration_iso ) );
            }
        }
    }

    /**
     * AJAX: Get stats
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $total = wp_count_posts( 'ipv_video' );
        wp_send_json_success( [
            'total_videos' => isset( $total->publish ) ? $total->publish : 0,
            'pending'      => isset( $total->pending ) ? $total->pending : 0,
        ] );
    }

    /**
     * AJAX: Process queue
     */
    public function ajax_process_queue() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::process_queue();
        }
        
        wp_send_json_success( [ 'message' => 'Coda elaborata' ] );
    }

    /**
     * Assicura che il CRON sia schedulato (fix per aggiornamenti)
     */
    public function ensure_cron_scheduled() {
        // CRON processamento coda (ogni 5 minuti)
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
            IPV_Prod_Logger::log( 'CRON auto-schedulato su admin_init: process_queue' );
        }

        // CRON aggiornamento dati YouTube (ogni ora)
        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
            IPV_Prod_Logger::log( 'CRON auto-schedulato su admin_init: update_youtube_data' );
        }
    }

    /**
     * AJAX: Avvia/Riavvia CRON
     */
    public function ajax_start_cron() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        // Rimuovi eventuale cron esistente
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        // Schedula nuovo cron
        wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        
        // Esegui subito una volta
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::process_queue();
        }

        $next = wp_next_scheduled( 'ipv_prod_process_queue' );
        
        wp_send_json_success( [ 
            'message'   => 'CRON avviato!',
            'next_run'  => $next ? date( 'H:i:s', $next ) : 'N/A',
            'status'    => $next ? 'attivo' : 'errore'
        ] );
    }

    /**
     * Activation
     */
    public function activate() {
        // Schedule CRON: processamento coda (ogni 5 minuti)
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        }

        // Schedule CRON: aggiornamento YouTube (ogni ora)
        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation
     */
    public function deactivate() {
        // Rimuovi CRON processamento coda
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        // Rimuovi CRON aggiornamento YouTube
        $timestamp = wp_next_scheduled( 'ipv_prod_update_youtube_data' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_update_youtube_data' );
        }
    }
}

// ============================================
// INIT
// ============================================

add_action( 'plugins_loaded', function() {
    IPV_Production_System_Pro::get_instance();
}, 10 );
