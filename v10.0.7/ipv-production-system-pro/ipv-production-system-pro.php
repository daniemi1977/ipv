<?php
/**
 * Plugin Name: IPV Production System Pro
 * Plugin URI: https://github.com/daniemi1977/ipv
 * Description: Professional video production system for YouTube content creators: multi-source imports, AI-powered transcriptions, automated descriptions with Golden Prompt, video wall with AJAX filters, and Elementor integration.
 * Version: 10.0.7
 * Author: IPV Team
 * Author URI: https://github.com/daniemi1977/ipv
 * Text Domain: ipv-production-system-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 *
 * CHANGELOG v10.0.0:
 * - Architettura SaaS: API keys gestite dal server, non più locali
 * - Sistema licenze integrato
 * - Golden Prompt inseribile manualmente dall'utente
 * - Pannello Server Settings per configurazione endpoint
 * - Crediti mensili con tracking
 * - Aggiornamenti automatici dal server
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================
// CONSTANTS
// ============================================

define( 'IPV_PROD_VERSION', '10.0.7' );
define( 'IPV_PROD_PLUGIN_FILE', __FILE__ );
define( 'IPV_PROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_PROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ============================================
// LOAD TEXT DOMAIN
// ============================================

function ipv_prod_load_textdomain() {
    $plugin_locale = get_option( 'ipv_plugin_language', 'auto' );
    
    if ( $plugin_locale !== 'auto' ) {
        return;
    }
    
    load_plugin_textdomain(
        'ipv-production-system-pro',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'ipv_prod_load_textdomain', 5 );

// ============================================
// LOAD CORE FILES (Prima dell'autoloader)
// ============================================

// Logger - DEVE essere caricato per primo (usato da tutti)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-logger.php';

// API Client - Gestisce comunicazione server (usa logger)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-api-client.php';

// SupaData - Gestisce trascrizioni (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-supadata.php';

// AI Generator - Gestisce descrizioni AI (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-ai-generator.php';

// YouTube API - Gestisce dati video (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-api.php';

// License Manager - Gestisce attivazione licenza
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-license-manager-client.php';

// Helpers
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-helpers.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-helpers.php';
}

// Language Manager
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-language-manager.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-language-manager.php';
}

// CPT
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cpt.php';

// Video List Columns
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php';
}

// Taxonomy Manager
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-taxonomy-manager.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-taxonomy-manager.php';
}

// ============================================
// v10.0.4 - UNIFIED INTERFACES
// ============================================

// Dashboard - Panoramica crediti e stats
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-dashboard.php';

// Import Unificato - Singolo/Batch/RSS/Canale
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-import-unified.php';

// Settings Unificato - Server/Golden/Lingua/Generale
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-settings-unified.php';

// Tools Unificato - Bulk/Duplicati/Pulizia
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-tools.php';

// ============================================
// AUTOLOAD CLASSES
// ============================================

spl_autoload_register( function( $class ) {
    $prefix_map = [
        'IPV_Prod_' => 'class-',
        'IPV_'      => 'class-ipv-',
    ];

    foreach ( $prefix_map as $prefix => $file_prefix ) {
        if ( strpos( $class, $prefix ) === 0 ) {
            $class_name = str_replace( $prefix, '', $class );
            $class_file = strtolower( str_replace( '_', '-', $class_name ) );
            
            $file = IPV_PROD_PLUGIN_DIR . 'includes/' . $file_prefix . $class_file . '.php';
            
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
            
            if ( $prefix === 'IPV_' ) {
                $file = IPV_PROD_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
                if ( file_exists( $file ) ) {
                    require_once $file;
                    return;
                }
            }
        }
    }
} );

// ============================================
// LEGACY HELPER FUNCTIONS
// ============================================

if ( ! function_exists( 'ipv_duration_to_seconds' ) ) {
    function ipv_duration_to_seconds( $duration ) {
        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            return IPV_Prod_Helpers::duration_to_seconds( $duration );
        }
        return 0;
    }
}

if ( ! function_exists( 'ipv_get_formatted_duration' ) ) {
    function ipv_get_formatted_duration( $post_id ) {
        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            return IPV_Prod_Helpers::get_formatted_duration( $post_id );
        }
        return '';
    }
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
        // v10.0.4 - UNIFIED MENUS
        IPV_Prod_Dashboard::init();
        IPV_Prod_Import_Unified::init();
        IPV_Prod_Settings_Unified::init();
        IPV_Prod_Tools::init();
        IPV_Prod_License_Manager_Client::init();
        IPV_Prod_CPT::init();

        // v10.0.7 - Queue Menu
        add_action( 'admin_menu', [ $this, 'register_queue_menu' ] );

        // Video Wall
        if ( class_exists( 'IPV_Prod_Video_Wall' ) ) {
            IPV_Prod_Video_Wall::init();
        }
        if ( class_exists( 'IPV_Prod_Video_Wall_Admin' ) ) {
            IPV_Prod_Video_Wall_Admin::init();
        }

        // Coming Soon
        if ( class_exists( 'IPV_Prod_Coming_Soon' ) ) {
            IPV_Prod_Coming_Soon::init();
        }

        // Admin menu (v10.0.4 - OLD menus disabled, now using unified classes)
        // add_action( 'admin_menu', [ $this, 'register_menu' ] );

        // Admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Cron Schedules
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
        
        // Cron Actions
        add_action( 'ipv_prod_process_queue', [ 'IPV_Prod_Queue', 'process_queue' ] );
        add_action( 'ipv_prod_update_youtube_data', [ 'IPV_Prod_Queue', 'update_all_youtube_data' ] );

        // Auto-schedule CRON
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
     * Add custom cron schedules
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['ipv_every_5_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 5 Minutes', 'ipv-production-system-pro' ),
        ];
        $schedules['ipv_every_15_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'ipv-production-system-pro' ),
        ];
        return $schedules;
    }

    /**
     * Register Queue submenu (v10.0.7)
     */
    public function register_queue_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Coda Elaborazione', 'ipv-production-system-pro' ),
            __( 'Coda', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-queue',
            [ $this, 'render_queue_page' ]
        );
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Dashboard è già aggiunto dal CPT come menu principale
        
        // Submenu: Import
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Import Video', 'ipv-production-system-pro' ),
            '📥 ' . __( 'Import', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-import',
            [ $this, 'render_import_page' ]
        );

        // Submenu: Bulk Import
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Bulk Import', 'ipv-production-system-pro' ),
            '📦 ' . __( 'Bulk Import', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-bulk',
            [ $this, 'render_bulk_import_page' ]
        );

        // Submenu: Settings
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Settings', 'ipv-production-system-pro' ),
            '⚙️ ' . __( 'Settings', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-settings',
            [ 'IPV_Prod_Settings', 'render_page' ]
        );

        // Submenu: Queue Dashboard
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Queue', 'ipv-production-system-pro' ),
            '📋 ' . __( 'Queue', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-queue',
            [ $this, 'render_queue_page' ]
        );

        // Submenu: Dashboard/Info
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Dashboard', 'ipv-production-system-pro' ),
            '📊 ' . __( 'Dashboard', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-dashboard',
            [ $this, 'render_dashboard_page' ]
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        
        if ( ! $screen || ( $screen->post_type !== 'ipv_video' && strpos( $hook, 'ipv-production' ) === false ) ) {
            return;
        }

        // Bootstrap 5 CSS
        wp_enqueue_style(
            'bootstrap-5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
            [],
            '1.10.0'
        );

        // Custom admin CSS
        if ( file_exists( IPV_PROD_PLUGIN_DIR . 'assets/css/admin.css' ) ) {
            wp_enqueue_style(
                'ipv-admin',
                IPV_PROD_PLUGIN_URL . 'assets/css/admin.css',
                [ 'bootstrap-5' ],
                IPV_PROD_VERSION
            );
        }

        // Admin JS
        if ( file_exists( IPV_PROD_PLUGIN_DIR . 'assets/js/admin.js' ) ) {
            wp_enqueue_script(
                'ipv-admin',
                IPV_PROD_PLUGIN_URL . 'assets/js/admin.js',
                [ 'jquery' ],
                IPV_PROD_VERSION,
                true
            );
        }

        wp_localize_script( 'ipv-admin', 'ipvAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ipv_admin_nonce' ),
            'i18n'    => [
                'processing' => __( 'Processing...', 'ipv-production-system-pro' ),
                'success'    => __( 'Success!', 'ipv-production-system-pro' ),
                'error'      => __( 'Error', 'ipv-production-system-pro' ),
            ],
        ] );
    }

    /**
     * Render import page
     */
    public function render_import_page() {
        if ( class_exists( 'IPV_Prod_Simple_Import' ) ) {
            IPV_Prod_Simple_Import::render_page();
        } else {
            echo '<div class="wrap"><h1>Import</h1><p>Modulo import non disponibile.</p></div>';
        }
    }

    /**
     * Render bulk import page
     */
    public function render_bulk_import_page() {
        if ( class_exists( 'IPV_Prod_Bulk_Import' ) ) {
            IPV_Prod_Bulk_Import::render_page();
        } else {
            echo '<div class="wrap"><h1>Bulk Import</h1><p>Modulo bulk import non disponibile.</p></div>';
        }
    }

    /**
     * Render queue page
     */
    public function render_queue_page() {
        if ( class_exists( 'IPV_Prod_Queue_Dashboard' ) ) {
            IPV_Prod_Queue_Dashboard::render_page();
        } elseif ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::render_admin_page();
        } else {
            echo '<div class="wrap"><h1>Queue</h1><p>Modulo coda non disponibile.</p></div>';
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $total_videos = wp_count_posts( 'ipv_video' );
        $published = $total_videos->publish ?? 0;
        $draft = $total_videos->draft ?? 0;
        
        $license_info = get_option( 'ipv_license_info', [] );
        $credits = $license_info['credits'] ?? [];
        $is_licensed = IPV_Prod_API_Client::is_license_active();

        ?>
        <div class="wrap">
            <h1>📊 <?php _e( 'Dashboard', 'ipv-production-system-pro' ); ?></h1>

            <div class="row mt-4" style="max-width: 1200px;">
                <!-- License Status -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 <?php echo $is_licensed ? 'border-success' : 'border-warning'; ?>">
                        <div class="card-header <?php echo $is_licensed ? 'bg-success text-white' : 'bg-warning'; ?>">
                            <h5 class="mb-0">🔑 <?php _e( 'Licenza', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ( $is_licensed ) : ?>
                                <p class="text-success"><strong>✅ <?php _e( 'Attiva', 'ipv-production-system-pro' ); ?></strong></p>
                                <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                                    <p><?php _e( 'Piano:', 'ipv-production-system-pro' ); ?> <strong><?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?></strong></p>
                                <?php endif; ?>
                            <?php else : ?>
                                <p class="text-warning"><strong>⚠️ <?php _e( 'Non attiva', 'ipv-production-system-pro' ); ?></strong></p>
                                <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>" class="btn btn-primary btn-sm">
                                    <?php _e( 'Attiva ora', 'ipv-production-system-pro' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Credits -->
                <?php if ( $is_licensed && ! empty( $credits ) ) : 
                    $percentage = $credits['percentage'] ?? 0;
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">📊 <?php _e( 'Crediti', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?> / <?php echo esc_html( $credits['credits_total'] ?? 0 ); ?></h3>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?php echo $percentage > 50 ? 'bg-success' : ( $percentage > 20 ? 'bg-warning' : 'bg-danger' ); ?>" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <?php if ( ! empty( $credits['reset_date_formatted'] ) ) : ?>
                                <small class="text-muted"><?php _e( 'Reset:', 'ipv-production-system-pro' ); ?> <?php echo esc_html( $credits['reset_date_formatted'] ); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Videos Stats -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">🎬 <?php _e( 'Video', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo number_format_i18n( $published ); ?></h3>
                            <p class="text-muted mb-0"><?php _e( 'Video pubblicati', 'ipv-production-system-pro' ); ?></p>
                            <?php if ( $draft > 0 ) : ?>
                                <small><?php echo number_format_i18n( $draft ); ?> <?php _e( 'bozze', 'ipv-production-system-pro' ); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4" style="max-width: 800px;">
                <div class="card-header">
                    <h5 class="mb-0">⚡ <?php _e( 'Azioni rapide', 'ipv-production-system-pro' ); ?></h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-production-import' ); ?>" class="btn btn-primary me-2">
                        📥 <?php _e( 'Import Video', 'ipv-production-system-pro' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="btn btn-outline-secondary me-2">
                        📋 <?php _e( 'Gestisci Video', 'ipv-production-system-pro' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-production-settings' ); ?>" class="btn btn-outline-secondary">
                        ⚙️ <?php _e( 'Impostazioni', 'ipv-production-system-pro' ); ?>
                    </a>
                </div>
            </div>

            <div class="mt-4 text-muted">
                <strong>IPV Production System Pro</strong> v<?php echo IPV_PROD_VERSION; ?> |
                <strong>Shortcode:</strong> <code>[ipv_video_wall]</code>
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

        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            $md = get_post_meta( get_the_ID(), IPV_Prod_Helpers::META_AI_DESCRIPTION, true );
        } else {
            $md = get_post_meta( get_the_ID(), '_ipv_ai_description', true );
        }
        
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

        $meta_key_sec = class_exists( 'IPV_Prod_Helpers' ) ? IPV_Prod_Helpers::META_YT_DURATION_SEC : '_ipv_yt_duration_seconds';
        $meta_key_dur = class_exists( 'IPV_Prod_Helpers' ) ? IPV_Prod_Helpers::META_YT_DURATION : '_ipv_yt_duration';

        $duration_seconds = get_post_meta( $post_id, $meta_key_sec, true );
        if ( empty( $duration_seconds ) ) {
            $duration_iso = get_post_meta( $post_id, $meta_key_dur, true );
            if ( $duration_iso ) {
                $seconds = ipv_duration_to_seconds( $duration_iso );
                update_post_meta( $post_id, $meta_key_sec, $seconds );
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
        
        wp_send_json_success( [ 'message' => __( 'Queue processed', 'ipv-production-system-pro' ) ] );
    }

    /**
     * Ensure CRON is scheduled
     */
    public function ensure_cron_scheduled() {
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        }

        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
        }
    }

    /**
     * AJAX: Start/Restart CRON
     */
    public function ajax_start_cron() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::process_queue();
        }

        $next = wp_next_scheduled( 'ipv_prod_process_queue' );
        
        wp_send_json_success( [ 
            'message'  => __( 'CRON started!', 'ipv-production-system-pro' ),
            'next_run' => $next ? date_i18n( 'H:i:s', $next ) : 'N/A',
        ] );
    }

    /**
     * Activation
     */
    public function activate() {
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        }

        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
        }

        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::create_table();
        }

        flush_rewrite_rules();
    }

    /**
     * Deactivation
     */
    public function deactivate() {
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

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
