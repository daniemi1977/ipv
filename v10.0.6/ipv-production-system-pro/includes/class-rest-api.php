<?php
/**
 * IPV Production System Pro - REST API
 *
 * Custom REST API endpoints for external integrations
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_REST_API {

    const NAMESPACE = 'ipv-production/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Get videos list
        register_rest_route( self::NAMESPACE, '/videos', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'get_videos' ],
            'permission_callback' => '__return_true',
            'args' => [
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'orderby' => [
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Get single video
        register_rest_route( self::NAMESPACE, '/videos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'get_video' ],
            'permission_callback' => '__return_true',
        ] );

        // Create video (auth required)
        register_rest_route( self::NAMESPACE, '/videos', [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'create_video' ],
            'permission_callback' => [ __CLASS__, 'check_auth' ],
            'args' => [
                'url' => [
                    'required' => true,
                    'sanitize_callback' => 'esc_url_raw',
                ],
            ],
        ] );

        // Get statistics
        register_rest_route( self::NAMESPACE, '/stats', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'get_stats' ],
            'permission_callback' => '__return_true',
        ] );

        // Webhook endpoint
        register_rest_route( self::NAMESPACE, '/webhook', [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'handle_webhook' ],
            'permission_callback' => [ __CLASS__, 'verify_webhook' ],
        ] );

        // Search videos
        register_rest_route( self::NAMESPACE, '/search', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'search_videos' ],
            'permission_callback' => '__return_true',
            'args' => [
                's' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * Get videos list
     */
    public static function get_videos( $request ) {
        $per_page = $request->get_param( 'per_page' );
        $page = $request->get_param( 'page' );
        $orderby = $request->get_param( 'orderby' );

        $args = [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => 'DESC',
        ];

        $query = new WP_Query( $args );

        $videos = [];
        foreach ( $query->posts as $post ) {
            $videos[] = self::format_video_response( $post->ID );
        }

        return new WP_REST_Response( [
            'videos' => $videos,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ], 200 );
    }

    /**
     * Get single video
     */
    public static function get_video( $request ) {
        $post_id = $request->get_param( 'id' );

        if ( get_post_type( $post_id ) !== 'ipv_video' ) {
            return new WP_Error( 'not_found', 'Video not found', [ 'status' => 404 ] );
        }

        return new WP_REST_Response( self::format_video_response( $post_id ), 200 );
    }

    /**
     * Create video from URL
     */
    public static function create_video( $request ) {
        $url = $request->get_param( 'url' );

        // Detect source and import
        $detection = IPV_Prod_Unified_Importer::detect_source( $url );

        if ( is_wp_error( $detection ) ) {
            return $detection;
        }

        $post_id = IPV_Prod_Unified_Importer::import_video( $detection['source'], $detection['video_id'] );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        return new WP_REST_Response( [
            'post_id' => $post_id,
            'video' => self::format_video_response( $post_id ),
        ], 201 );
    }

    /**
     * Get statistics
     */
    public static function get_stats( $request ) {
        $stats = IPV_Prod_Analytics::get_aggregate_stats();

        return new WP_REST_Response( $stats, 200 );
    }

    /**
     * Search videos
     */
    public static function search_videos( $request ) {
        $search = $request->get_param( 's' );

        $args = [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search,
        ];

        $query = new WP_Query( $args );

        $videos = [];
        foreach ( $query->posts as $post ) {
            $videos[] = self::format_video_response( $post->ID );
        }

        return new WP_REST_Response( [
            'results' => $videos,
            'total' => $query->found_posts,
        ], 200 );
    }

    /**
     * Handle incoming webhook
     */
    public static function handle_webhook( $request ) {
        $body = $request->get_json_params();

        // Log webhook
        IPV_Prod_Logger::log( 'Webhook received', $body );

        // Trigger action for external integrations
        do_action( 'ipv_webhook_received', $body );

        return new WP_REST_Response( [ 'status' => 'received' ], 200 );
    }

    /**
     * Format video response
     */
    private static function format_video_response( $post_id ) {
        $source = get_post_meta( $post_id, '_ipv_video_source', true ) ?: 'youtube';
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );

        return [
            'id' => $post_id,
            'title' => get_the_title( $post_id ),
            'content' => get_the_content( null, false, $post_id ),
            'source' => $source,
            'video_id' => $video_id,
            'url' => get_permalink( $post_id ),
            'thumbnail' => get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true ),
            'duration' => get_post_meta( $post_id, '_ipv_yt_duration_formatted', true ),
            'views' => (int) get_post_meta( $post_id, '_ipv_yt_view_count', true ),
            'likes' => (int) get_post_meta( $post_id, '_ipv_yt_like_count', true ),
            'published_at' => get_the_date( 'c', $post_id ),
        ];
    }

    /**
     * Check authentication
     */
    public static function check_auth( $request ) {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verify webhook signature
     */
    public static function verify_webhook( $request ) {
        $secret = get_option( 'ipv_webhook_secret', '' );

        if ( empty( $secret ) ) {
            return true; // Allow if no secret set
        }

        $signature = $request->get_header( 'X-IPV-Signature' );
        $body = $request->get_body();
        $expected = hash_hmac( 'sha256', $body, $secret );

        return hash_equals( $expected, $signature );
    }
}

IPV_Prod_REST_API::init();
