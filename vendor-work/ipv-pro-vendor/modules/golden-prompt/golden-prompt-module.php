<?php
/**
 * IPV Golden Prompt Module
 *
 * Modulo per la gestione dei Golden Prompts nel sistema vendor.
 * Include questo file nel plugin ipv-pro-vendor principale.
 *
 * UTILIZZO:
 * Nel file principale del plugin vendor (ipv-pro-vendor.php), aggiungi:
 * 
 * require_once IPV_VENDOR_PATH . 'modules/golden-prompt/golden-prompt-module.php';
 *
 * @package IPV_Pro_Vendor
 * @since 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define module constants
if ( ! defined( 'IPV_GOLDEN_PROMPT_VERSION' ) ) {
    define( 'IPV_GOLDEN_PROMPT_VERSION', '1.0.0' );
}

/**
 * Golden Prompt Module Loader
 */
class IPV_Vendor_Golden_Prompt_Module {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        $module_path = dirname( __FILE__ ) . '/';

        require_once $module_path . 'includes/class-golden-prompt-manager.php';
        require_once $module_path . 'includes/class-golden-prompt-admin.php';
        require_once $module_path . 'includes/class-golden-prompt-api.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize classes
        add_action( 'init', [ $this, 'init_classes' ] );

        // Database migration on activation
        add_action( 'ipv_vendor_activated', [ $this, 'create_tables' ] );

        // Add to admin menu
        add_action( 'admin_init', [ $this, 'maybe_create_tables' ] );
    }

    /**
     * Initialize module classes
     */
    public function init_classes() {
        IPV_Vendor_Golden_Prompt_Manager::instance();
        
        if ( is_admin() ) {
            IPV_Vendor_Golden_Prompt_Admin::instance();
        }

        IPV_Vendor_Golden_Prompt_API::instance();
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        IPV_Vendor_Golden_Prompt_Manager::create_table();
    }

    /**
     * Maybe create tables (on first load)
     */
    public function maybe_create_tables() {
        $db_version = get_option( 'ipv_golden_prompt_db_version', '0' );

        if ( version_compare( $db_version, IPV_GOLDEN_PROMPT_VERSION, '<' ) ) {
            $this->create_tables();
            update_option( 'ipv_golden_prompt_db_version', IPV_GOLDEN_PROMPT_VERSION );
        }
    }
}

// Initialize module
IPV_Vendor_Golden_Prompt_Module::instance();
