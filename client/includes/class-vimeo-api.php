<?php
/**
 * IPV Production System Pro - Vimeo API Integration
 *
 * Multi-source video support: Vimeo
 *
 * @package IPV_Production_System_Pro
 * @version 7.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Vimeo_API {

    const API_ENDPOINT = 'https://api.vimeo.com';
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Get video data from Vimeo
     */
    public static function get_video_data( $video_id ) {
        $access_token = get_option( 'ipv_vimeo_access_token', '' );

        if ( empty( $access_token ) ) {
            return new WP_Error( 'no_token', 'Vimeo Access Token non configurato' );
        }

        // Check cache
        $cache_key = 'ipv_vimeo_' . $video_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url = self::API_ENDPOINT . '/videos/' . $video_id;

        $response = wp_remote_get( $url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error( 'vimeo_error', 'Vimeo API Error: ' . $code );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) ) {
            return new WP_Error( 'no_data', 'Nessun dato ricevuto da Vimeo' );
        }

        $result = self::parse_video_data( $body );

        // Cache result
        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Parse Vimeo video data
     */
    private static function parse_video_data( $data ) {
        $pictures = $data['pictures']['sizes'] ?? [];
        $thumbnail_url = ! empty( $pictures ) ? end( $pictures )['link'] : '';

        return [
            'source' => 'vimeo',
            'video_id' => str_replace( '/videos/', '', $data['uri'] ),
            'title' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'duration' => $data['duration'] ?? 0,
            'duration_formatted' => self::format_duration( $data['duration'] ?? 0 ),
            'thumbnail_url' => $thumbnail_url,
            'embed_url' => 'https://player.vimeo.com/video/' . str_replace( '/videos/', '', $data['uri'] ),
            'published_at' => $data['created_time'] ?? '',
            'view_count' => $data['stats']['plays'] ?? 0,
            'like_count' => $data['metadata']['connections']['likes']['total'] ?? 0,
            'comment_count' => $data['metadata']['connections']['comments']['total'] ?? 0,
            'width' => $data['width'] ?? 1920,
            'height' => $data['height'] ?? 1080,
        ];
    }

    /**
     * Extract Vimeo ID from URL
     */
    public static function extract_video_id( $url ) {
        // Format: vimeo.com/123456789
        if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Format: player.vimeo.com/video/123456789
        if ( preg_match( '/player\.vimeo\.com\/video\/(\d+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Already an ID
        if ( is_numeric( $url ) ) {
            return $url;
        }

        return false;
    }

    /**
     * Format duration
     */
    private static function format_duration( $seconds ) {
        $hours = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
        }

        return sprintf( '%d:%02d', $minutes, $secs );
    }

    /**
     * Save Vimeo video meta to post
     */
    public static function save_video_meta( $post_id, $video_data ) {
        update_post_meta( $post_id, '_ipv_video_source', 'vimeo' );
        update_post_meta( $post_id, '_ipv_video_id', $video_data['video_id'] );
        update_post_meta( $post_id, '_ipv_vimeo_embed_url', $video_data['embed_url'] );
        update_post_meta( $post_id, '_ipv_yt_title', $video_data['title'] ); // Reuse YouTube meta keys for compatibility
        update_post_meta( $post_id, '_ipv_yt_description', $video_data['description'] );
        update_post_meta( $post_id, '_ipv_yt_duration_seconds', $video_data['duration'] );
        update_post_meta( $post_id, '_ipv_yt_duration_formatted', $video_data['duration_formatted'] );
        update_post_meta( $post_id, '_ipv_yt_thumbnail_url', $video_data['thumbnail_url'] );
        update_post_meta( $post_id, '_ipv_yt_view_count', $video_data['view_count'] );
        update_post_meta( $post_id, '_ipv_yt_like_count', $video_data['like_count'] );
        update_post_meta( $post_id, '_ipv_yt_comment_count', $video_data['comment_count'] );

        IPV_Prod_Logger::log( 'Vimeo video meta saved', [ 'post_id' => $post_id, 'video_id' => $video_data['video_id'] ] );

        return true;
    }
}
