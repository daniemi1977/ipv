<?php
/**
 * IPV Simple Import - Import e Pubblica Subito
 * 
 * Importa video da YouTube e li pubblica immediatamente,
 * senza aspettare trascrizione/AI.
 * 
 * @version 9.1.0
 * CHANGELOG v9.1.0:
 * - Rimosso extract_video_id() duplicato → usa IPV_Prod_Helpers
 * - Rimosso video_exists() duplicato → usa IPV_Prod_Helpers  
 * - Rimosso set_thumbnail() duplicato → usa IPV_Prod_Helpers
 * - Usa costanti META_* standardizzate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Simple_Import {

    /**
     * Init
     */
    public static function init() {
        add_action( 'admin_post_ipv_simple_import', [ __CLASS__, 'handle_import' ] );
        add_action( 'wp_ajax_ipv_quick_import', [ __CLASS__, 'ajax_quick_import' ] );
    }

    /**
     * Handle form submission
     */
    public static function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'ipv_simple_import_nonce' );

        $url = isset( $_POST['youtube_url'] ) ? sanitize_text_field( wp_unslash( $_POST['youtube_url'] ) ) : '';
        
        if ( empty( $url ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=ipv-production-import&error=url_empty' ) );
            exit;
        }

        $result = self::import_video( $url );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=ipv-production-import&error=' . urlencode( $result->get_error_message() ) ) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=ipv-production-import&success=1&post_id=' . $result ) );
        }
        exit;
    }

    /**
     * AJAX quick import
     */
    public static function ajax_quick_import() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $url = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
        
        if ( empty( $url ) ) {
            wp_send_json_error( __( 'URL missing', 'ipv-production-system-pro' ) );
        }

        $result = self::import_video( $url );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( [
            'post_id'   => $result,
            'edit_link' => get_edit_post_link( $result, 'raw' ),
            'view_link' => get_permalink( $result ),
            'title'     => get_the_title( $result ),
        ] );
    }

    /**
     * Import a single video and publish immediately
     */
    public static function import_video( $url ) {
        // Usa helper centralizzato per estrazione ID
        $video_id = IPV_Prod_Helpers::extract_youtube_id( $url );
        
        if ( ! $video_id ) {
            return new WP_Error( 'invalid_url', __( 'Invalid YouTube URL', 'ipv-production-system-pro' ) );
        }

        // Usa helper centralizzato per verifica duplicati
        $existing = IPV_Prod_Helpers::video_exists( $video_id );
        if ( $existing ) {
            return new WP_Error( 
                'duplicate', 
                sprintf( __( 'Video already imported (ID: %d)', 'ipv-production-system-pro' ), $existing ) 
            );
        }

        // Get video data from YouTube API (if available)
        $video_data = self::get_youtube_data( $video_id );

        // Create post
        $post_data = [
            'post_type'   => 'ipv_video',
            'post_status' => 'publish',
            'post_title'  => $video_data['title'] ?? 'Video YouTube ' . $video_id,
            'post_content' => $video_data['description'] ?? '',
        ];

        // Use YouTube publish date as post_date if available
        if ( ! empty( $video_data['published_at'] ) ) {
            $post_data['post_date'] = get_date_from_gmt( $video_data['published_at'] );
            $post_data['post_date_gmt'] = $video_data['published_at'];
        }

        $post_id = wp_insert_post( $post_data );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return new WP_Error( 'insert_failed', __( 'Failed to create post', 'ipv-production-system-pro' ) );
        }

        // Save meta using standardized constants
        update_post_meta( $post_id, IPV_Prod_Helpers::META_VIDEO_ID, $video_id );
        update_post_meta( $post_id, IPV_Prod_Helpers::META_YOUTUBE_URL, $url );
        update_post_meta( $post_id, IPV_Prod_Helpers::META_IMPORT_DATE, current_time( 'mysql' ) );
        update_post_meta( $post_id, IPV_Prod_Helpers::META_VIDEO_SOURCE, 'youtube' );

        // Save YouTube data if available
        if ( ! empty( $video_data ) ) {
            if ( isset( $video_data['duration_seconds'] ) ) {
                update_post_meta( $post_id, IPV_Prod_Helpers::META_YT_DURATION_SEC, $video_data['duration_seconds'] );
            }
            if ( isset( $video_data['duration'] ) ) {
                update_post_meta( $post_id, IPV_Prod_Helpers::META_YT_DURATION, $video_data['duration'] );
            }
            if ( isset( $video_data['published_at'] ) ) {
                update_post_meta( $post_id, IPV_Prod_Helpers::META_YT_PUBLISHED_AT, $video_data['published_at'] );
            }
            if ( isset( $video_data['view_count'] ) ) {
                update_post_meta( $post_id, IPV_Prod_Helpers::META_YT_VIEW_COUNT, $video_data['view_count'] );
            }
            if ( isset( $video_data['channel_title'] ) ) {
                update_post_meta( $post_id, IPV_Prod_Helpers::META_YT_CHANNEL_TITLE, $video_data['channel_title'] );
            }

            // Tags
            if ( ! empty( $video_data['tags'] ) ) {
                wp_set_object_terms( $post_id, $video_data['tags'], 'post_tag', true );
            }
        }

        // Usa helper centralizzato per thumbnail
        IPV_Prod_Helpers::set_youtube_thumbnail( 
            $post_id, 
            $video_id, 
            $video_data['thumbnail_url'] ?? null 
        );

        // Log
        IPV_Prod_Helpers::log( 'Video imported and published', [
            'post_id'  => $post_id,
            'video_id' => $video_id,
        ] );

        return $post_id;
    }

    /**
     * Get video data from YouTube API
     */
    public static function get_youtube_data( $video_id ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );
        
        // Default data using oEmbed (no API key needed)
        $data = [
            'title'       => 'Video YouTube ' . $video_id,
            'description' => '',
        ];

        // Try oEmbed first (no API key required)
        $oembed_url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $video_id . '&format=json';
        $oembed_response = wp_remote_get( $oembed_url );

        if ( ! is_wp_error( $oembed_response ) && wp_remote_retrieve_response_code( $oembed_response ) === 200 ) {
            $oembed_data = json_decode( wp_remote_retrieve_body( $oembed_response ), true );
            if ( $oembed_data ) {
                $data['title'] = $oembed_data['title'] ?? $data['title'];
                $data['channel_title'] = $oembed_data['author_name'] ?? '';
                $data['thumbnail_url'] = $oembed_data['thumbnail_url'] ?? '';
            }
        }

        // If we have YouTube API key, get more details
        if ( ! empty( $api_key ) && class_exists( 'IPV_Prod_YouTube_API' ) ) {
            $api_data = IPV_Prod_YouTube_API::get_video_data( $video_id );
            if ( ! is_wp_error( $api_data ) && is_array( $api_data ) ) {
                $data = array_merge( $data, $api_data );
            }
        }

        // Ensure we have a thumbnail URL
        if ( empty( $data['thumbnail_url'] ) ) {
            $data['thumbnail_url'] = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        }

        return $data;
    }
}

// Initialize
add_action( 'init', [ 'IPV_Simple_Import', 'init' ] );
