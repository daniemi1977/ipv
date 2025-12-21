<?php
/**
 * IPV Elementor Widgets
 *
 * Registra widget Elementor per landing page
 *
 * @package IPV_Pro_Vendor
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Elementor_Widgets {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_category' ] );
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
    }

    /**
     * Add IPV category
     */
    public function add_category( $elements_manager ) {
        $elements_manager->add_category(
            'ipv-vendor',
            [
                'title' => __( 'IPV Pro Vendor', 'ipv-pro-vendor' ),
                'icon'  => 'eicon-video-playlist',
            ]
        );
    }

    /**
     * Editor styles
     */
    public function editor_styles() {
        wp_enqueue_style( 'ipv-landing-page', IPV_VENDOR_URL . 'assets/css/landing-page.css', [], IPV_VENDOR_VERSION );
    }

    /**
     * Register widgets
     */
    public function register_widgets( $widgets_manager ) {
        require_once IPV_VENDOR_DIR . 'includes/elementor/class-widget-hero.php';
        require_once IPV_VENDOR_DIR . 'includes/elementor/class-widget-features.php';
        require_once IPV_VENDOR_DIR . 'includes/elementor/class-widget-how-it-works.php';
        require_once IPV_VENDOR_DIR . 'includes/elementor/class-widget-pricing.php';
        require_once IPV_VENDOR_DIR . 'includes/elementor/class-widget-cta.php';

        $widgets_manager->register( new IPV_Widget_Hero() );
        $widgets_manager->register( new IPV_Widget_Features() );
        $widgets_manager->register( new IPV_Widget_How_It_Works() );
        $widgets_manager->register( new IPV_Widget_Pricing() );
        $widgets_manager->register( new IPV_Widget_CTA() );
    }
}

// Initialize if Elementor is active
add_action( 'plugins_loaded', function() {
    if ( did_action( 'elementor/loaded' ) ) {
        IPV_Vendor_Elementor_Widgets::instance();
    }
});
