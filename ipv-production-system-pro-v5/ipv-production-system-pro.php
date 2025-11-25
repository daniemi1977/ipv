<?php
/**
 * Plugin Name: IPV Production System Pro v5
 * Plugin URI: https://aiedintorni.it
 * Description: Sistema di produzione avanzato per "Il Punto di Vista" con supporto Elementor, tassonomie intelligenti, video wall e compatibilità Influencers/WoodMart
 * Version: 6.1.1
 * Author: Daniele / IPV
 * Text Domain: ipv-production-system-pro
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IPV_PROD_VERSION', '6.1.1' );
define( 'IPV_PROD_PLUGIN_FILE', __FILE__ );
define( 'IPV_PROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_PROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes Core
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-logger.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-settings.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cpt.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-taxonomies.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-auto-tagger.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-supadata.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-ai-generator.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-queue.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cron-manager.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-rss-importer.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-api.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-bulk-import.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php';

// Elementor Integration
require_once IPV_PROD_PLUGIN_DIR . 'elementor/class-elementor-integration.php';

// Video Wall
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-wall.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-wall-settings.php';

// Theme Compatibility
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-theme-compat.php';

class IPV_Production_System_Pro {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // CPT e Tassonomie
        add_action( 'init', [ 'IPV_Prod_CPT', 'register' ] );
        add_action( 'init', [ 'IPV_Prod_Taxonomies', 'register' ] );

        // Settings
        add_action( 'admin_init', [ 'IPV_Prod_Settings', 'register_settings' ] );

        // Menu admin
        add_action( 'admin_menu', [ $this, 'register_menu' ] );

        // Assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

        // Cron Manager
        add_filter( 'cron_schedules', [ 'IPV_Prod_Cron_Manager', 'add_schedules' ] );
        add_action( 'ipv_prod_process_queue', [ 'IPV_Prod_Queue', 'process_queue' ] );

        // AJAX endpoints
        add_action( 'wp_ajax_ipv_prod_get_stats', [ $this, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_ipv_prod_process_queue', [ $this, 'ajax_process_queue' ] );

        // AJAX Video Wall
        add_action( 'wp_ajax_ipv_load_videos', [ 'IPV_Prod_Video_Wall', 'ajax_load_videos' ] );
        add_action( 'wp_ajax_nopriv_ipv_load_videos', [ 'IPV_Prod_Video_Wall', 'ajax_load_videos' ] );

        // Elementor Integration
        add_action( 'elementor/widgets/register', [ 'IPV_Elementor_Integration', 'register_widgets' ] );

        // Auto-tagger (popola automaticamente tassonomie)
        add_action( 'save_post_video_ipv', [ 'IPV_Prod_Auto_Tagger', 'auto_tag_video' ], 20, 2 );

        // Markdown rendering per descrizioni AI
        add_filter( 'the_content', [ $this, 'render_markdown_content' ] );

        // Theme Compatibility
        IPV_Prod_Theme_Compat::init();
    }

    /**
     * Render markdown content per post type video_ipv
     */
    public function render_markdown_content( $content ) {
        if ( get_post_type() !== 'video_ipv' ) {
            return $content;
        }

        $markdown = get_post_meta( get_the_ID(), '_ipv_ai_description', true );
        if ( ! $markdown ) {
            return $content;
        }

        return $this->parse_markdown( $markdown );
    }

    /**
     * Parse markdown semplice con supporto line breaks
     */
    private function parse_markdown( $text ) {
        // Headers
        $text = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $text );
        $text = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $text );
        $text = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $text );

        // Bold
        $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );

        // Italic
        $text = preg_replace( '/_(.+?)_/', '<em>$1</em>', $text );

        // Links
        $text = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text );

        // Lists
        $text = preg_replace( '/^• (.+)$/m', '<li>$1</li>', $text );
        $text = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text );

        // Line breaks: converti singoli \n in <br>, ma proteggi i doppi \n\n
        // Prima sostituisci \n\n con un placeholder temporaneo
        $text = str_replace( "\n\n", '{{PARAGRAPH}}', $text );
        // Poi converti i singoli \n rimasti in <br>
        $text = str_replace( "\n", '<br>', $text );
        // Infine ripristina i paragrafi
        $text = '<p>' . str_replace( '{{PARAGRAPH}}', '</p><p>', $text ) . '</p>';

        // Pulisci eventuali <p> vuoti
        $text = preg_replace( '/<p>\s*<\/p>/', '', $text );

        return $text;
    }

    public function enqueue_admin_assets( $hook ) {
        // Carica solo nelle nostre pagine
        if ( strpos( $hook, 'ipv-production' ) === false && get_post_type() !== 'video_ipv' ) {
            return;
        }

        // Bootstrap 5
        wp_enqueue_style(
            'ipv-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
            [],
            '5.3.2'
        );

        // Chart.js
        wp_enqueue_script(
            'ipv-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'ipv-bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
            [],
            '1.11.1'
        );

        // CSS personalizzato
        wp_enqueue_style(
            'ipv-prod-admin',
            IPV_PROD_PLUGIN_URL . 'assets/css/admin.css',
            [ 'ipv-bootstrap' ],
            IPV_PROD_VERSION
        );

        // JavaScript personalizzato
        wp_enqueue_script(
            'ipv-prod-admin',
            IPV_PROD_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'ipv-chartjs' ],
            IPV_PROD_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'ipv-prod-admin',
            'ipvProdAjax',
            [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ipv_prod_ajax' ),
            ]
        );
    }

    public function enqueue_frontend_assets() {
        // Video Wall CSS
        wp_enqueue_style(
            'ipv-video-wall',
            IPV_PROD_PLUGIN_URL . 'assets/css/video-wall.css',
            [],
            IPV_PROD_VERSION
        );

        // Video Wall JS
        wp_enqueue_script(
            'ipv-video-wall',
            IPV_PROD_PLUGIN_URL . 'assets/js/video-wall.js',
            [ 'jquery' ],
            IPV_PROD_VERSION,
            true
        );

        // Localize
        wp_localize_script(
            'ipv-video-wall',
            'ipvVideoWall',
            [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ipv_video_wall' ),
            ]
        );
    }

    public function register_menu() {
        add_menu_page(
            'IPV Production',
            'IPV Production',
            'manage_options',
            'ipv-production-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-video-alt3',
            26
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ipv-production-dashboard',
            [ $this, 'render_dashboard_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Importa Video',
            'Importa Video',
            'manage_options',
            'ipv-production-import',
            [ 'IPV_Prod_YouTube_Importer', 'render_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Bulk Import',
            'Bulk Import',
            'manage_options',
            'ipv-production-bulk-import',
            [ 'IPV_Prod_Bulk_Import', 'render_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Auto-Import RSS',
            'Auto-Import RSS',
            'manage_options',
            'ipv-production-rss',
            [ 'IPV_Prod_RSS_Importer', 'render_settings_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Coda',
            'Coda',
            'manage_options',
            'ipv-production-queue',
            [ 'IPV_Prod_Queue', 'render_admin_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Video Wall',
            'Video Wall',
            'manage_options',
            'ipv-production-wall',
            [ 'IPV_Prod_Video_Wall_Settings', 'render_page' ]
        );

        add_submenu_page(
            'ipv-production-dashboard',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'ipv-production-settings',
            [ 'IPV_Prod_Settings', 'render_page' ]
        );
    }

    public function render_dashboard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $stats = IPV_Prod_Queue::get_stats();
        $cron_status = $this->check_cron_status();
        $rss_stats = IPV_Prod_RSS_Importer::get_rss_stats();
        $rss_enabled = get_option( 'ipv_rss_enabled', false );

        require IPV_PROD_PLUGIN_DIR . 'templates/dashboard.php';
    }

    private function check_cron_status() {
        return (bool) wp_next_scheduled( 'ipv_prod_process_queue' );
    }

    public function ajax_get_stats() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        $stats = IPV_Prod_Queue::get_stats();
        wp_send_json_success( $stats );
    }

    public function ajax_process_queue() {
        check_ajax_referer( 'ipv_prod_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
        }

        IPV_Prod_Queue::process_queue();
        $stats = IPV_Prod_Queue::get_stats();

        wp_send_json_success( [
            'message' => 'Coda processata con successo.',
            'stats'   => $stats,
        ] );
    }

    public static function activate() {
        IPV_Prod_Queue::create_table();
        IPV_Prod_CPT::register();
        IPV_Prod_Taxonomies::register();

        // Attiva cron
        IPV_Prod_Cron_Manager::activate();

        // Flush rewrite rules
        flush_rewrite_rules();

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            IPV_Prod_Logger::log( 'Plugin attivato v' . IPV_PROD_VERSION );
        }
    }

    public static function deactivate() {
        IPV_Prod_Cron_Manager::deactivate();

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            IPV_Prod_Logger::log( 'Plugin disattivato.' );
        }
    }
}

register_activation_hook( __FILE__, [ 'IPV_Production_System_Pro', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'IPV_Production_System_Pro', 'deactivate' ] );

IPV_Production_System_Pro::get_instance();
