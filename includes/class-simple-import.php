<?php
/**
 * IPV Simple Import - Import e Pubblica Subito
 * 
 * Importa video da YouTube e li pubblica immediatamente,
 * senza aspettare trascrizione/AI.
 * 
 * @version 7.2.0
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
            wp_send_json_error( 'URL mancante' );
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
        // Extract video ID
        $video_id = self::extract_video_id( $url );
        
        if ( ! $video_id ) {
            return new WP_Error( 'invalid_url', 'URL YouTube non valido' );
        }

        // Check if already exists
        $existing = self::video_exists( $video_id );
        if ( $existing ) {
            return new WP_Error( 'duplicate', 'Video già importato (ID: ' . $existing . ')' );
        }

        // Get video data from YouTube API (if available)
        $video_data = self::get_youtube_data( $video_id );

        // Create post
        $post_data = [
            'post_type'   => 'ipv_video',
            'post_status' => 'publish', // PUBBLICA SUBITO!
            'post_title'  => $video_data['title'] ?? 'Video YouTube ' . $video_id,
            'post_content' => $video_data['description'] ?? '',
        ];

        $post_id = wp_insert_post( $post_data );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return new WP_Error( 'insert_failed', 'Impossibile creare il post' );
        }

        // Save meta
        update_post_meta( $post_id, '_ipv_video_id', $video_id );
        update_post_meta( $post_id, '_ipv_youtube_url', $url );
        update_post_meta( $post_id, '_ipv_import_date', current_time( 'mysql' ) );

        // Save YouTube data if available
        if ( ! empty( $video_data ) ) {
            if ( isset( $video_data['duration_seconds'] ) ) {
                update_post_meta( $post_id, '_ipv_duration_seconds', $video_data['duration_seconds'] );
            }
            if ( isset( $video_data['duration'] ) ) {
                update_post_meta( $post_id, '_ipv_yt_duration', $video_data['duration'] );
            }
            if ( isset( $video_data['published_at'] ) ) {
                update_post_meta( $post_id, '_ipv_published_at', $video_data['published_at'] );
                // Extract year
                $year = date( 'Y', strtotime( $video_data['published_at'] ) );
                update_post_meta( $post_id, '_ipv_year', $year );
            }
            if ( isset( $video_data['view_count'] ) ) {
                update_post_meta( $post_id, '_ipv_view_count', $video_data['view_count'] );
            }
            if ( isset( $video_data['channel_title'] ) ) {
                update_post_meta( $post_id, '_ipv_channel', $video_data['channel_title'] );
            }

            // Tags
            if ( ! empty( $video_data['tags'] ) ) {
                wp_set_object_terms( $post_id, $video_data['tags'], 'post_tag', true );
            }
        }

        // Download and set thumbnail
        self::set_thumbnail( $post_id, $video_id, $video_data['thumbnail_url'] ?? null );

        // Log
        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'Video importato e pubblicato', [
                'post_id'  => $post_id,
                'video_id' => $video_id,
            ] );
        }

        return $post_id;
    }

    /**
     * Extract video ID from URL
     */
    public static function extract_video_id( $url ) {
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }

        // Maybe it's just the ID
        if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $url ) ) {
            return $url;
        }

        return false;
    }

    /**
     * Check if video already exists
     */
    public static function video_exists( $video_id ) {
        global $wpdb;
        
        $post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ipv_video_id' AND meta_value = %s 
             LIMIT 1",
            $video_id
        ) );

        return $post_id ? intval( $post_id ) : false;
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

    /**
     * Set post thumbnail from YouTube
     */
    public static function set_thumbnail( $post_id, $video_id, $thumbnail_url = null ) {
        if ( has_post_thumbnail( $post_id ) ) {
            return;
        }

        // Try different thumbnail qualities
        $urls = [];
        
        if ( $thumbnail_url ) {
            $urls[] = $thumbnail_url;
        }
        
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/sddefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/mqdefault.jpg';

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ( $urls as $url ) {
            // Check if URL is valid
            $response = wp_remote_head( $url );
            if ( is_wp_error( $response ) ) {
                continue;
            }
            
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                continue;
            }

            // Download and attach
            $tmp = download_url( $url );
            if ( is_wp_error( $tmp ) ) {
                continue;
            }

            $file_array = [
                'name'     => $video_id . '.jpg',
                'tmp_name' => $tmp,
            ];

            $attach_id = media_handle_sideload( $file_array, $post_id, get_the_title( $post_id ) );

            if ( ! is_wp_error( $attach_id ) ) {
                set_post_thumbnail( $post_id, $attach_id );
                return true;
            }

            // Clean up
            @unlink( $tmp );
        }

        return false;
    }
}

// Initialize
add_action( 'init', [ 'IPV_Simple_Import', 'init' ] );
