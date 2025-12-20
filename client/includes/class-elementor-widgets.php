<?php
/**
 * IPV Production System Pro - Elementor Widgets
 *
 * Custom Elementor widgets for video embedding
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Elementor_Widgets {

    public static function init() {
        // Check if Elementor is installed
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'add_elementor_category' ] );
    }

    /**
     * Add custom Elementor category
     */
    public static function add_elementor_category( $elements_manager ) {
        $elements_manager->add_category(
            'edit.php?post_type=ipv_video',
            [
                'title' => 'IPV Production',
                'icon' => 'fa fa-video-camera',
            ]
        );
    }

    /**
     * Register all widgets
     */
    public static function register_widgets( $widgets_manager ) {
        require_once IPV_PROD_PLUGIN_DIR . 'includes/elementor-widgets/video-player-widget.php';
        require_once IPV_PROD_PLUGIN_DIR . 'includes/elementor-widgets/video-grid-widget.php';
        require_once IPV_PROD_PLUGIN_DIR . 'includes/elementor-widgets/video-wall-widget.php';

        $widgets_manager->register( new IPV_Elementor_Video_Player_Widget() );
        $widgets_manager->register( new IPV_Elementor_Video_Grid_Widget() );
        $widgets_manager->register( new IPV_Elementor_Video_Wall_Widget() );
    }
}

IPV_Prod_Elementor_Widgets::init();
