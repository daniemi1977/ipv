<?php
/**
 * Theme Compatibility Layer
 * Supporto per Influencers e WoodMart
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Theme_Compat {

    public static function init() {
        add_action( 'wp', [ __CLASS__, 'detect_theme' ] );
        add_filter( 'single_template', [ __CLASS__, 'single_video_template' ] );
    }

    public static function detect_theme() {
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        
        // Aggiungi body class per il tema
        add_filter( 'body_class', function($classes) use ($theme_name) {
            if ( strpos(strtolower($theme_name), 'influencer') !== false ) {
                $classes[] = 'ipv-theme-influencers';
            } elseif ( strpos(strtolower($theme_name), 'woodmart') !== false ) {
                $classes[] = 'ipv-theme-woodmart';
            }
            return $classes;
        });
    }

    public static function single_video_template( $template ) {
        if ( is_singular( 'video_ipv' ) ) {
            $custom_template = IPV_PROD_PLUGIN_DIR . 'templates/single-video_ipv.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }
        return $template;
    }
}
