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

class IPV_Prod_Simple_Import {

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
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&error=url_empty' ) );
            exit;
        }

        $result = self::import_video( $url );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&error=' . urlencode( $result->get_error_message() ) ) );
        } else {
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&success=1&post_id=' . $result ) );
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
     * Import a single video using production queue (with transcription and AI)
     * v10.2.13 - FIX: Now uses queue instead of immediate publish
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

        // v10.2.13 - NEW: Add to production queue instead of creating post directly
        // This ensures transcription and AI description are generated automatically
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::enqueue( $video_id, $url, 'manual' );

            IPV_Prod_Helpers::log( '✅ Video added to production queue', [
                'video_id' => $video_id,
                'url' => $url,
                'source' => 'manual'
            ]);

            // Return a special message to inform user that video is being processed
            return $video_id;
        }

        // Fallback: if queue class doesn't exist, return error
        return new WP_Error(
            'queue_unavailable',
            __( 'Production queue is not available. Please contact support.', 'ipv-production-system-pro' )
        );
    }

    /**
     * Get basic video data from YouTube oEmbed (fallback method)
     * v10.2.5 - Enhanced fallback with better error handling
     */
    public static function get_youtube_data_oembed( $video_id ) {
        // Default data
        $data = [
            'title'       => 'Video YouTube ' . $video_id,
            'description' => '',
        ];

        // Try oEmbed (no API key required)
        $oembed_url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $video_id . '&format=json';
        
        $oembed_response = wp_remote_get( $oembed_url, [
            'timeout' => 30,
            'sslverify' => false, // Some hosts have SSL issues
            'user-agent' => 'Mozilla/5.0 (compatible; WordPress/' . get_bloginfo( 'version' ) . ')',
        ]);

        if ( is_wp_error( $oembed_response ) ) {
            IPV_Prod_Helpers::log( 'oEmbed request failed', [
                'video_id' => $video_id,
                'error' => $oembed_response->get_error_message()
            ]);
        } elseif ( wp_remote_retrieve_response_code( $oembed_response ) === 200 ) {
            $oembed_data = json_decode( wp_remote_retrieve_body( $oembed_response ), true );
            if ( $oembed_data && ! empty( $oembed_data['title'] ) ) {
                $data['title'] = $oembed_data['title'];
                $data['channel_title'] = $oembed_data['author_name'] ?? '';
                $data['thumbnail_url'] = $oembed_data['thumbnail_url'] ?? '';
                
                IPV_Prod_Helpers::log( 'oEmbed success', [
                    'video_id' => $video_id,
                    'title' => $data['title']
                ]);
            } else {
                IPV_Prod_Helpers::log( 'oEmbed empty response', [
                    'video_id' => $video_id,
                    'response' => substr( wp_remote_retrieve_body( $oembed_response ), 0, 200 )
                ]);
            }
        } else {
            IPV_Prod_Helpers::log( 'oEmbed HTTP error', [
                'video_id' => $video_id,
                'status' => wp_remote_retrieve_response_code( $oembed_response )
            ]);
        }

        // Ensure we have a thumbnail URL
        if ( empty( $data['thumbnail_url'] ) ) {
            $data['thumbnail_url'] = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        }

        return $data;
    }
}

// Backwards compatibility alias (old class name)
if ( ! class_exists( 'IPV_Simple_Import' ) ) {
    class_alias( 'IPV_Prod_Simple_Import', 'IPV_Simple_Import' );
}

// Initialize
add_action( 'init', [ 'IPV_Prod_Simple_Import', 'init' ] );

