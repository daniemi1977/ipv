<?php
/**
 * IPV Production System Pro - Dailymotion API Integration
 *
 * Multi-source video support: Dailymotion
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Dailymotion_API {

    const API_ENDPOINT = 'https://api.dailymotion.com';
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Get video data from Dailymotion
     */
    public static function get_video_data( $video_id ) {
        // Check cache
        $cache_key = 'ipv_dailymotion_' . $video_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url = self::API_ENDPOINT . '/video/' . $video_id . '?fields=id,title,description,duration,thumbnail_720_url,created_time,views_total,likes_total,channel';

        $response = wp_remote_get( $url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error( 'dailymotion_error', 'Dailymotion API Error: ' . $code );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) ) {
            return new WP_Error( 'no_data', 'Nessun dato ricevuto da Dailymotion' );
        }

        $result = self::parse_video_data( $body );

        // Cache result
        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Parse Dailymotion video data
     */
    private static function parse_video_data( $data ) {
        return [
            'source' => 'dailymotion',
            'video_id' => $data['id'] ?? '',
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'duration' => $data['duration'] ?? 0,
            'duration_formatted' => self::format_duration( $data['duration'] ?? 0 ),
            'thumbnail_url' => $data['thumbnail_720_url'] ?? '',
            'embed_url' => 'https://www.dailymotion.com/embed/video/' . ( $data['id'] ?? '' ),
            'published_at' => date( 'Y-m-d H:i:s', $data['created_time'] ?? time() ),
            'view_count' => $data['views_total'] ?? 0,
            'like_count' => $data['likes_total'] ?? 0,
            'comment_count' => 0, // Not available in basic API
            'channel' => $data['channel'] ?? '',
        ];
    }

    /**
     * Extract Dailymotion ID from URL
     */
    public static function extract_video_id( $url ) {
        // Format: dailymotion.com/video/x123abc
        if ( preg_match( '/dailymotion\.com\/video\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Format: dai.ly/x123abc
        if ( preg_match( '/dai\.ly\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Already an ID (format: x123abc)
        if ( preg_match( '/^x[a-zA-Z0-9]+$/', $url ) ) {
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
     * Save Dailymotion video meta to post
     */
    public static function save_video_meta( $post_id, $video_data ) {
        update_post_meta( $post_id, '_ipv_video_source', 'dailymotion' );
        update_post_meta( $post_id, '_ipv_video_id', $video_data['video_id'] );
        update_post_meta( $post_id, '_ipv_dailymotion_embed_url', $video_data['embed_url'] );
        update_post_meta( $post_id, '_ipv_yt_title', $video_data['title'] );
        update_post_meta( $post_id, '_ipv_yt_description', $video_data['description'] );
        update_post_meta( $post_id, '_ipv_yt_duration_seconds', $video_data['duration'] );
        update_post_meta( $post_id, '_ipv_yt_duration_formatted', $video_data['duration_formatted'] );
        update_post_meta( $post_id, '_ipv_yt_thumbnail_url', $video_data['thumbnail_url'] );
        update_post_meta( $post_id, '_ipv_yt_view_count', $video_data['view_count'] );
        update_post_meta( $post_id, '_ipv_yt_like_count', $video_data['like_count'] );

        IPV_Prod_Logger::log( 'Dailymotion video meta saved', [ 'post_id' => $post_id, 'video_id' => $video_data['video_id'] ] );

        return true;
    }
}
