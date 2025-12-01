<?php
/**
 * IPV Production System Pro - Theme Compatibility
 *
 * Template system with override support and customization hooks
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Theme_Compatibility {

    public static function init() {
        add_filter( 'template_include', [ __CLASS__, 'template_loader' ] );
        add_filter( 'single_template', [ __CLASS__, 'single_video_template' ] );
        add_filter( 'archive_template', [ __CLASS__, 'archive_video_template' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
    }

    /**
     * Load custom templates with theme override support
     */
    public static function template_loader( $template ) {
        if ( is_singular( 'ipv_video' ) ) {
            $custom_template = self::locate_template( 'single-ipv_video.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        if ( is_post_type_archive( 'ipv_video' ) || is_tax( 'ipv_categoria' ) || is_tax( 'ipv_relatore' ) ) {
            $custom_template = self::locate_template( 'archive-ipv_video.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Single video template
     */
    public static function single_video_template( $template ) {
        if ( is_singular( 'ipv_video' ) ) {
            $custom_template = self::locate_template( 'single-ipv_video.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Archive video template
     */
    public static function archive_video_template( $template ) {
        if ( is_post_type_archive( 'ipv_video' ) || is_tax( 'ipv_categoria' ) || is_tax( 'ipv_relatore' ) ) {
            $custom_template = self::locate_template( 'archive-ipv_video.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Locate template with theme override support
     *
     * Priority:
     * 1. Theme: /ipv-production-templates/{template}
     * 2. Plugin: /templates/{template}
     */
    public static function locate_template( $template_name ) {
        // Hook: Allow other plugins to override
        $template = apply_filters( 'ipv_locate_template_before', null, $template_name );
        if ( $template ) {
            return $template;
        }

        // Check in theme folder first
        $theme_template = locate_template( [
            'ipv-production-templates/' . $template_name,
            $template_name,
        ] );

        if ( $theme_template ) {
            return apply_filters( 'ipv_locate_template', $theme_template, $template_name );
        }

        // Fallback to plugin templates
        $plugin_template = IPV_PROD_PLUGIN_DIR . 'templates/' . $template_name;

        if ( file_exists( $plugin_template ) ) {
            return apply_filters( 'ipv_locate_template', $plugin_template, $template_name );
        }

        return false;
    }

    /**
     * Get template part with theme override support
     */
    public static function get_template_part( $slug, $name = '', $args = [] ) {
        // Build template name
        $templates = [];
        if ( $name ) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        // Locate template
        $template = null;
        foreach ( $templates as $template_name ) {
            $located = self::locate_template( $template_name );
            if ( $located ) {
                $template = $located;
                break;
            }
        }

        // Hook: Modify args before template load
        $args = apply_filters( 'ipv_get_template_part_args', $args, $slug, $name );

        // Hook: Before template
        do_action( 'ipv_before_template_part', $slug, $name, $args, $template );

        if ( $template ) {
            // Extract args as variables
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args );
            }

            include $template;
        }

        // Hook: After template
        do_action( 'ipv_after_template_part', $slug, $name, $args, $template );
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Allow themes to disable plugin styles
        if ( ! apply_filters( 'ipv_load_frontend_styles', true ) ) {
            return;
        }

        // Check if custom theme CSS exists
        $theme_css = get_stylesheet_directory() . '/ipv-production-templates/ipv-custom.css';
        if ( file_exists( $theme_css ) ) {
            wp_enqueue_style(
                'ipv-theme-custom',
                get_stylesheet_directory_uri() . '/ipv-production-templates/ipv-custom.css',
                [],
                filemtime( $theme_css )
            );
        }
    }

    /**
     * Render video player
     */
    public static function render_video_player( $post_id, $args = [] ) {
        $defaults = [
            'autoplay' => false,
            'controls' => true,
            'mute' => false,
            'loop' => false,
            'width' => '100%',
            'aspect' => '16:9',
        ];

        $args = wp_parse_args( $args, $defaults );

        // Hook: Modify player args
        $args = apply_filters( 'ipv_video_player_args', $args, $post_id );

        // Get video data
        $video_source = get_post_meta( $post_id, '_ipv_video_source', true ) ?: 'youtube';
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );

        // Hook: Before player
        do_action( 'ipv_before_video_player', $post_id, $args );

        // Render player
        self::get_template_part( 'content', 'video-player', [
            'post_id' => $post_id,
            'video_source' => $video_source,
            'video_id' => $video_id,
            'args' => $args,
        ] );

        // Hook: After player
        do_action( 'ipv_after_video_player', $post_id, $args );
    }

    /**
     * Render video card
     */
    public static function render_video_card( $post_id, $args = [] ) {
        $defaults = [
            'show_thumbnail' => true,
            'show_title' => true,
            'show_excerpt' => true,
            'show_meta' => true,
            'show_categories' => false,
            'excerpt_length' => 20,
        ];

        $args = wp_parse_args( $args, $defaults );

        // Hook: Modify card args
        $args = apply_filters( 'ipv_video_card_args', $args, $post_id );

        // Hook: Before card
        do_action( 'ipv_before_video_card', $post_id, $args );

        // Render card
        self::get_template_part( 'content', 'video-card', [
            'post_id' => $post_id,
            'args' => $args,
        ] );

        // Hook: After card
        do_action( 'ipv_after_video_card', $post_id, $args );
    }

    /**
     * Get video data for templates
     */
    public static function get_video_data( $post_id ) {
        $data = [
            'id' => $post_id,
            'title' => get_the_title( $post_id ),
            'excerpt' => get_the_excerpt( $post_id ),
            'content' => get_post_field( 'post_content', $post_id ),
            'permalink' => get_permalink( $post_id ),
            'video_source' => get_post_meta( $post_id, '_ipv_video_source', true ) ?: 'youtube',
            'video_id' => get_post_meta( $post_id, '_ipv_video_id', true ),
            'thumbnail_url' => get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true ),
            'duration' => get_post_meta( $post_id, '_ipv_yt_duration_formatted', true ),
            'view_count' => get_post_meta( $post_id, '_ipv_yt_view_count', true ),
            'like_count' => get_post_meta( $post_id, '_ipv_yt_like_count', true ),
            'published_at' => get_post_meta( $post_id, '_ipv_yt_published_at', true ),
            'categories' => wp_get_post_terms( $post_id, 'ipv_categoria' ),
            'speakers' => wp_get_post_terms( $post_id, 'ipv_relatore' ),
        ];

        // Hook: Modify video data
        return apply_filters( 'ipv_video_data', $data, $post_id );
    }

    /**
     * Register customization hooks
     */
    public static function register_hooks() {
        // Example hooks for theme developers:

        /**
         * Filter: Modify video player arguments
         * @param array $args Player arguments
         * @param int $post_id Video post ID
         */
        apply_filters( 'ipv_video_player_args', [], 0 );

        /**
         * Filter: Modify video card arguments
         * @param array $args Card arguments
         * @param int $post_id Video post ID
         */
        apply_filters( 'ipv_video_card_args', [], 0 );

        /**
         * Filter: Modify video data
         * @param array $data Video data
         * @param int $post_id Video post ID
         */
        apply_filters( 'ipv_video_data', [], 0 );

        /**
         * Action: Before video player
         * @param int $post_id Video post ID
         * @param array $args Player arguments
         */
        do_action( 'ipv_before_video_player', 0, [] );

        /**
         * Action: After video player
         * @param int $post_id Video post ID
         * @param array $args Player arguments
         */
        do_action( 'ipv_after_video_player', 0, [] );

        /**
         * Action: Before video card
         * @param int $post_id Video post ID
         * @param array $args Card arguments
         */
        do_action( 'ipv_before_video_card', 0, [] );

        /**
         * Action: After video card
         * @param int $post_id Video post ID
         * @param array $args Card arguments
         */
        do_action( 'ipv_after_video_card', 0, [] );

        /**
         * Filter: Load frontend styles
         * @param bool $load Whether to load plugin styles
         */
        apply_filters( 'ipv_load_frontend_styles', true );

        /**
         * Filter: Locate template before default search
         * @param string|null $template Template path or null
         * @param string $template_name Template filename
         */
        apply_filters( 'ipv_locate_template_before', null, '' );

        /**
         * Filter: Locate template result
         * @param string $template Template path
         * @param string $template_name Template filename
         */
        apply_filters( 'ipv_locate_template', '', '' );

        /**
         * Action: Before template part
         * @param string $slug Template slug
         * @param string $name Template name
         * @param array $args Template arguments
         * @param string $template Template path
         */
        do_action( 'ipv_before_template_part', '', '', [], '' );

        /**
         * Action: After template part
         * @param string $slug Template slug
         * @param string $name Template name
         * @param array $args Template arguments
         * @param string $template Template path
         */
        do_action( 'ipv_after_template_part', '', '', [], '' );
    }
}

IPV_Prod_Theme_Compatibility::init();
