<?php
/**
 * IPV Vendor YouTube Data API Endpoints
 *
 * API Gateway per YouTube Data API v3
 * Tutte le API keys sono sul server, il client non le vede mai!
 *
 * @package IPV_Pro_Vendor
 * @version 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_YouTube_Endpoints {

    public function register_routes() {
        // POST /wp-json/ipv-vendor/v1/youtube/video-data
        register_rest_route( 'ipv-vendor/v1', '/youtube/video-data', [
            'methods' => 'POST',
            'callback' => [ $this, 'get_video_data' ],
            'permission_callback' => '__return_true'
        ]);

        // POST /wp-json/ipv-vendor/v1/youtube/channel-videos
        register_rest_route( 'ipv-vendor/v1', '/youtube/channel-videos', [
            'methods' => 'POST',
            'callback' => [ $this, 'get_channel_videos' ],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Validate license from request
     */
    private function validate_request_license( $request ) {
        $license_key = '';

        // Check Authorization header (Bearer token)
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if ( preg_match( '/Bearer\s+(.+)$/i', $auth, $matches ) ) {
                $license_key = $matches[1];
            }
        }

        // Fallback to X-License-Key header
        if ( empty( $license_key ) && isset( $_SERVER['HTTP_X_LICENSE_KEY'] ) ) {
            $license_key = $_SERVER['HTTP_X_LICENSE_KEY'];
        }

        // Fallback to body parameter
        if ( empty( $license_key ) && $request ) {
            $license_key = $request->get_param( 'license_key' ) ?: '';
        }

        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_license',
                'License key mancante',
                [ 'status' => 401 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        return $license;
    }

    /**
     * POST /youtube/video-data
     * Get video data via YouTube Data API (server-side)
     */
    public function get_video_data( $request ) {
        // Validate license
        $license = $this->validate_request_license( $request );
        if ( is_wp_error( $license ) ) {
            return $license;
        }

        // Get YouTube API key from server settings
        $youtube_key = get_option( 'ipv_vendor_youtube_api_key', '' );
        if ( empty( $youtube_key ) ) {
            return new WP_Error(
                'youtube_key_missing',
                'YouTube API Key non configurata sul server',
                [ 'status' => 500 ]
            );
        }

        // Get params
        $video_id = $request->get_param( 'video_id' );
        if ( empty( $video_id ) ) {
            return new WP_Error(
                'missing_video_id',
                'video_id parameter is required',
                [ 'status' => 400 ]
            );
        }

        // Call YouTube Data API
        $url = add_query_arg([
            'part' => 'snippet,contentDetails,statistics,status',
            'id'   => $video_id,
            'key'  => $youtube_key,
        ], 'https://www.googleapis.com/youtube/v3/videos' );

        $response = wp_remote_get( $url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'youtube_api_error',
                'Errore chiamata YouTube API: ' . $response->get_error_message(),
                [ 'status' => 502 ]
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Handle errors
        if ( $status_code === 403 ) {
            return new WP_Error(
                'youtube_quota_exceeded',
                'Quota YouTube API esaurita. Riprova domani.',
                [ 'status' => 403 ]
            );
        }

        if ( $status_code === 400 ) {
            return new WP_Error(
                'youtube_bad_request',
                'Richiesta non valida: ' . ( $data['error']['message'] ?? 'Errore sconosciuto' ),
                [ 'status' => 400 ]
            );
        }

        if ( $status_code < 200 || $status_code >= 300 ) {
            return new WP_Error(
                'youtube_http_error',
                'Errore HTTP YouTube: ' . $status_code,
                [ 'status' => $status_code ]
            );
        }

        if ( empty( $data['items'][0] ) ) {
            return new WP_Error(
                'youtube_video_not_found',
                'Video non trovato o privato',
                [ 'status' => 404 ]
            );
        }

        // Parse and return video data
        $item = $data['items'][0];
        $snippet = $item['snippet'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];
        $statistics = $item['statistics'] ?? [];

        return rest_ensure_response([
            'success' => true,
            'video_data' => [
                'video_id' => $video_id,
                'title' => $snippet['title'] ?? '',
                'description' => $snippet['description'] ?? '',
                'published_at' => $snippet['publishedAt'] ?? '',
                'channel_id' => $snippet['channelId'] ?? '',
                'channel_title' => $snippet['channelTitle'] ?? '',
                'tags' => $snippet['tags'] ?? [],
                'category_id' => $snippet['categoryId'] ?? '',
                'thumbnail_default' => $snippet['thumbnails']['default']['url'] ?? '',
                'thumbnail_medium' => $snippet['thumbnails']['medium']['url'] ?? '',
                'thumbnail_high' => $snippet['thumbnails']['high']['url'] ?? '',
                'thumbnail_standard' => $snippet['thumbnails']['standard']['url'] ?? '',
                'thumbnail_maxres' => $snippet['thumbnails']['maxres']['url'] ?? '',
                'duration' => $contentDetails['duration'] ?? '',
                'definition' => $contentDetails['definition'] ?? '',
                'caption' => $contentDetails['caption'] ?? '',
                'view_count' => $statistics['viewCount'] ?? 0,
                'like_count' => $statistics['likeCount'] ?? 0,
                'comment_count' => $statistics['commentCount'] ?? 0,
            ]
        ]);
    }

    /**
     * POST /youtube/channel-videos
     * Get channel videos list
     */
    public function get_channel_videos( $request ) {
        // Validate license
        $license = $this->validate_request_license( $request );
        if ( is_wp_error( $license ) ) {
            return $license;
        }

        // Get YouTube API key from server settings
        $youtube_key = get_option( 'ipv_vendor_youtube_api_key', '' );
        if ( empty( $youtube_key ) ) {
            return new WP_Error(
                'youtube_key_missing',
                'YouTube API Key non configurata sul server',
                [ 'status' => 500 ]
            );
        }

        // Get params
        $channel_id = $request->get_param( 'channel_id' );
        $max_results = $request->get_param( 'max_results' ) ?? 50;
        $page_token = $request->get_param( 'page_token' ) ?? '';

        if ( empty( $channel_id ) ) {
            return new WP_Error(
                'missing_channel_id',
                'channel_id parameter is required',
                [ 'status' => 400 ]
            );
        }

        // First get uploads playlist ID
        $channel_url = add_query_arg([
            'part' => 'contentDetails',
            'id'   => $channel_id,
            'key'  => $youtube_key,
        ], 'https://www.googleapis.com/youtube/v3/channels' );

        $channel_response = wp_remote_get( $channel_url, [ 'timeout' => 30 ]);

        if ( is_wp_error( $channel_response ) ) {
            return new WP_Error(
                'youtube_api_error',
                'Errore chiamata YouTube API: ' . $channel_response->get_error_message(),
                [ 'status' => 502 ]
            );
        }

        $channel_data = json_decode( wp_remote_retrieve_body( $channel_response ), true );

        if ( empty( $channel_data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
            return new WP_Error(
                'channel_not_found',
                'Canale non trovato',
                [ 'status' => 404 ]
            );
        }

        $uploads_playlist = $channel_data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];

        // Now get playlist videos
        $playlist_args = [
            'part' => 'snippet,contentDetails',
            'playlistId' => $uploads_playlist,
            'maxResults' => min( $max_results, 50 ),
            'key' => $youtube_key,
        ];

        if ( ! empty( $page_token ) ) {
            $playlist_args['pageToken'] = $page_token;
        }

        $playlist_url = add_query_arg( $playlist_args, 'https://www.googleapis.com/youtube/v3/playlistItems' );
        $playlist_response = wp_remote_get( $playlist_url, [ 'timeout' => 30 ]);

        if ( is_wp_error( $playlist_response ) ) {
            return new WP_Error(
                'youtube_api_error',
                'Errore chiamata YouTube API: ' . $playlist_response->get_error_message(),
                [ 'status' => 502 ]
            );
        }

        $playlist_data = json_decode( wp_remote_retrieve_body( $playlist_response ), true );

        $videos = [];
        foreach ( $playlist_data['items'] ?? [] as $item ) {
            $videos[] = [
                'video_id' => $item['contentDetails']['videoId'] ?? '',
                'title' => $item['snippet']['title'] ?? '',
                'published_at' => $item['snippet']['publishedAt'] ?? '',
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'videos' => $videos,
            'next_page_token' => $playlist_data['nextPageToken'] ?? '',
            'total_results' => $playlist_data['pageInfo']['totalResults'] ?? 0,
        ]);
    }
}
