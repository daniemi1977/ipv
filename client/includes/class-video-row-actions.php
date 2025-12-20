<?php
/**
 * IPV Production System Pro - Video Row Actions Handlers
 *
 * Gestisce le azioni rapide disponibili nella lista video:
 * - Refresh YouTube Data (aggiorna views, likes, title, etc.)
 * - Regenerate Transcript (rigenera trascrizione)
 * - Regenerate AI Description (rigenera descrizione AI)
 *
 * @package IPV_Production_System_Pro
 * @version 10.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Row_Actions {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'admin_post_ipv_refresh_single_video', [ __CLASS__, 'handle_refresh_youtube' ] );
        add_action( 'admin_post_ipv_regenerate_transcript', [ __CLASS__, 'handle_regenerate_transcript' ] );
        add_action( 'admin_post_ipv_regenerate_ai_desc', [ __CLASS__, 'handle_regenerate_ai_desc' ] );
        add_action( 'admin_notices', [ __CLASS__, 'show_admin_notices' ] );
    }

    /**
     * Show admin notices for row action results
     */
    public static function show_admin_notices() {
        if ( ! isset( $_GET['ipv_message'] ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'ipv_video' ) {
            return;
        }

        $message_type = sanitize_text_field( $_GET['ipv_message'] );
        $error = isset( $_GET['error'] ) ? urldecode( $_GET['error'] ) : '';

        $messages = [
            'refresh_success' => [
                'type' => 'success',
                'text' => '✅ Dati YouTube aggiornati con successo!'
            ],
            'refresh_error' => [
                'type' => 'error',
                'text' => '❌ Errore aggiornamento dati YouTube: ' . esc_html( $error )
            ],
            'transcript_success' => [
                'type' => 'success',
                'text' => '✅ Trascrizione rigenerata con successo!'
            ],
            'transcript_error' => [
                'type' => 'error',
                'text' => '❌ Errore rigenerazione trascrizione: ' . esc_html( $error )
            ],
            'transcript_missing' => [
                'type' => 'warning',
                'text' => '⚠️ Impossibile rigenerare descrizione AI: trascrizione mancante.'
            ],
            'ai_success' => [
                'type' => 'success',
                'text' => '✅ Descrizione AI rigenerata con successo!'
            ],
            'ai_error' => [
                'type' => 'error',
                'text' => '❌ Errore rigenerazione descrizione AI: ' . esc_html( $error )
            ],
            'license_required' => [
                'type' => 'error',
                'text' => '❌ Licenza non attiva. Attiva la licenza per usare questa funzionalità.'
            ],
        ];

        if ( ! isset( $messages[ $message_type ] ) ) {
            return;
        }

        $message = $messages[ $message_type ];
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $message['type'] ),
            $message['text']
        );
    }

    /**
     * Handle Refresh YouTube Data
     */
    public static function handle_refresh_youtube() {
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ipv_refresh_single_' . $post_id ) ) {
            wp_die( __( 'Security check failed', 'ipv-production-system-pro' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You do not have permission to edit this video', 'ipv-production-system-pro' ) );
        }

        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( empty( $video_id ) ) {
            wp_die( __( 'Video ID not found', 'ipv-production-system-pro' ) );
        }

        // Refresh YouTube data
        if ( class_exists( 'IPV_Prod_YouTube_API' ) ) {
            $video_data = IPV_Prod_YouTube_API::get_video_data( $video_id );

            if ( is_wp_error( $video_data ) ) {
                // Redirect with error
                wp_redirect( add_query_arg( [
                    'post_type' => 'ipv_video',
                    'ipv_message' => 'refresh_error',
                    'error' => urlencode( $video_data->get_error_message() )
                ], admin_url( 'edit.php' ) ) );
                exit;
            }

            // Update post meta
            if ( ! empty( $video_data['title'] ) ) {
                wp_update_post( [
                    'ID' => $post_id,
                    'post_title' => sanitize_text_field( $video_data['title'] )
                ] );
            }

            // Update YouTube metadata
            $meta_fields = [
                '_ipv_yt_view_count' => 'view_count',
                '_ipv_yt_like_count' => 'like_count',
                '_ipv_yt_comment_count' => 'comment_count',
                '_ipv_yt_duration_seconds' => 'duration_seconds',
                '_ipv_yt_duration_formatted' => 'duration_formatted',
                '_ipv_yt_thumbnail_url' => 'thumbnail',
                '_ipv_yt_published_at' => 'published_at',
            ];

            foreach ( $meta_fields as $meta_key => $data_key ) {
                if ( isset( $video_data[ $data_key ] ) ) {
                    update_post_meta( $post_id, $meta_key, $video_data[ $data_key ] );
                }
            }

            // Update last refresh timestamp
            update_post_meta( $post_id, '_ipv_last_refresh', current_time( 'mysql' ) );

            // Redirect with success
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'refresh_success'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        wp_die( __( 'YouTube API class not found', 'ipv-production-system-pro' ) );
    }

    /**
     * Handle Regenerate Transcript
     */
    public static function handle_regenerate_transcript() {
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ipv_regenerate_transcript_' . $post_id ) ) {
            wp_die( __( 'Security check failed', 'ipv-production-system-pro' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You do not have permission to edit this video', 'ipv-production-system-pro' ) );
        }

        // Check license
        if ( ! class_exists( 'IPV_Prod_API_Client_Optimized' ) || ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'license_required'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( empty( $video_id ) ) {
            wp_die( __( 'Video ID not found', 'ipv-production-system-pro' ) );
        }

        // Get transcript
        if ( class_exists( 'IPV_Prod_Supadata' ) ) {
            $transcript = IPV_Prod_Supadata::get_transcript( $video_id, 'auto', 'auto' );

            if ( is_wp_error( $transcript ) ) {
                // Redirect with error
                wp_redirect( add_query_arg( [
                    'post_type' => 'ipv_video',
                    'ipv_message' => 'transcript_error',
                    'error' => urlencode( $transcript->get_error_message() )
                ], admin_url( 'edit.php' ) ) );
                exit;
            }

            // Save transcript
            update_post_meta( $post_id, '_ipv_transcript', $transcript );
            update_post_meta( $post_id, '_ipv_transcript_generated_at', current_time( 'mysql' ) );

            // Redirect with success
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'transcript_success'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        wp_die( __( 'Transcript service not found', 'ipv-production-system-pro' ) );
    }

    /**
     * Handle Regenerate AI Description
     */
    public static function handle_regenerate_ai_desc() {
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ipv_regenerate_ai_desc_' . $post_id ) ) {
            wp_die( __( 'Security check failed', 'ipv-production-system-pro' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You do not have permission to edit this video', 'ipv-production-system-pro' ) );
        }

        // Check license
        if ( ! class_exists( 'IPV_Prod_API_Client_Optimized' ) || ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'license_required'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );
        if ( empty( $transcript ) ) {
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'transcript_missing'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        // Get video data
        $video_title = get_the_title( $post_id );
        $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        $duration_seconds = get_post_meta( $post_id, '_ipv_yt_duration_seconds', true );

        // Generate AI description
        if ( class_exists( 'IPV_Prod_AI_Generator' ) ) {
            $ai_description = IPV_Prod_AI_Generator::generate_description(
                $video_title,
                $transcript,
                $duration_formatted,
                $duration_seconds,
                ''
            );

            if ( is_wp_error( $ai_description ) ) {
                // Redirect with error
                wp_redirect( add_query_arg( [
                    'post_type' => 'ipv_video',
                    'ipv_message' => 'ai_error',
                    'error' => urlencode( $ai_description->get_error_message() )
                ], admin_url( 'edit.php' ) ) );
                exit;
            }

            // Update post content with proper formatting
            // wpautop() converts line breaks to <p> and <br> tags, then sanitize
            wp_update_post( [
                'ID' => $post_id,
                'post_content' => wp_kses_post( wpautop( $ai_description ) ) // ✅ Preserva a capo!
            ] );

            // Save AI metadata
            update_post_meta( $post_id, '_ipv_ai_description', $ai_description );
            update_post_meta( $post_id, '_ipv_ai_generated_at', current_time( 'mysql' ) );

            // Redirect with success
            wp_redirect( add_query_arg( [
                'post_type' => 'ipv_video',
                'ipv_message' => 'ai_success'
            ], admin_url( 'edit.php' ) ) );
            exit;
        }

        wp_die( __( 'AI Generator class not found', 'ipv-production-system-pro' ) );
    }
}

// Initialize
IPV_Prod_Video_Row_Actions::init();
