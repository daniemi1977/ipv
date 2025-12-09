<?php
/**
 * IPV Production System Pro - Transcript Service
 *
 * Cloud-based video transcription service
 * All processing happens on secure IPV servers
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class name kept for backward compatibility with existing code
 */
class IPV_Prod_Supadata {

    /**
     * Get video transcript via cloud service
     *
     * @param string $video_id YouTube Video ID
     * @param string $mode Mode: 'auto', 'native', 'generate'
     * @param string $lang Preferred language (ISO 639-1)
     * @return string|WP_Error Transcript or error
     */
    public static function get_transcript( $video_id, $mode = 'auto', $lang = 'it' ) {
        // Check license
        if ( ! IPV_Prod_API_Client::is_license_active() ) {
            return new WP_Error(
                'ipv_license_required',
                __( 'License not active. Activate your license to use transcription.', 'ipv-production-system-pro' )
            );
        }

        // Sanitize input
        $video_id = sanitize_text_field( $video_id );
        $mode = in_array( $mode, [ 'auto', 'native', 'generate', 'whisper' ] ) ? $mode : 'auto';
        $lang = sanitize_text_field( $lang );

        // Log request
        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'Transcript: request', [
                'video_id' => $video_id,
                'mode' => $mode,
                'lang' => $lang
            ]);
        }

        // Call cloud API
        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->get_transcript( $video_id, $mode, $lang );

        if ( is_wp_error( $result ) ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'Transcript: error', [
                    'video_id' => $video_id,
                    'error' => $result->get_error_message()
                ]);
            }
            return $result;
        }

        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'Transcript: completed', [
                'video_id' => $video_id,
                'length' => strlen( $result )
            ]);
        }

        return $result;
    }

    /**
     * Alias for backward compatibility
     */
    public static function get_video_transcript( $video_id, $mode = 'auto', $lang = 'it' ) {
        return self::get_transcript( $video_id, $mode, $lang );
    }

    /**
     * Get service status info
     *
     * @return array Service info
     */
    public static function get_keys_status() {
        $license_info = get_option( 'ipv_license_info', [] );
        $credits = $license_info['credits'] ?? [];

        return [
            'service' => 'cloud',
            'credits_remaining' => $credits['credits_remaining'] ?? 0,
            'credits_total' => $credits['credits_total'] ?? 0,
            'reset_date' => $credits['reset_date_formatted'] ?? '',
            'license_active' => IPV_Prod_API_Client::is_license_active()
        ];
    }

    /**
     * Test cloud connection
     *
     * @param string $api_key Not used in SaaS mode
     * @return bool|WP_Error
     */
    public static function test_api_key( $api_key = '' ) {
        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->test_connection();

        if ( $result['success'] ) {
            return true;
        }

        return new WP_Error(
            'connection_failed',
            __( 'Unable to connect to cloud service', 'ipv-production-system-pro' )
        );
    }

    /**
     * Stub method for compatibility
     */
    protected static function get_api_keys() {
        return [];
    }

    /**
     * Stub method for compatibility
     */
    protected static function get_all_api_keys() {
        return [];
    }
}
