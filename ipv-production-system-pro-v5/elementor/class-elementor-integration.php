<?php
/**
 * Elementor Integration per IPV Production System Pro v5
 *
 * Compatibile con temi Influencers e WoodMart
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Elementor_Integration {

    /**
     * Registra i widget Elementor
     */
    public static function register_widgets( $widgets_manager ) {
        // Verifica che Elementor sia attivo
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        require_once IPV_PROD_PLUGIN_DIR . 'elementor/widgets/video-player-widget.php';
        require_once IPV_PROD_PLUGIN_DIR . 'elementor/widgets/video-grid-widget.php';
        require_once IPV_PROD_PLUGIN_DIR . 'elementor/widgets/video-slider-widget.php';

        $widgets_manager->register( new \IPV_Video_Player_Widget() );
        $widgets_manager->register( new \IPV_Video_Grid_Widget() );
        $widgets_manager->register( new \IPV_Video_Slider_Widget() );
    }

    /**
     * Aggiungi categoria Elementor personalizzata
     */
    public static function add_elementor_category( $elements_manager ) {
        $elements_manager->add_category(
            'ipv-elements',
            [
                'title' => __( 'IPV Video', 'ipv-production-system-pro' ),
                'icon'  => 'fa fa-video',
            ]
        );
    }
}

// Inizializza
add_action( 'elementor/elements/categories_registered', [ 'IPV_Elementor_Integration', 'add_elementor_category' ] );
