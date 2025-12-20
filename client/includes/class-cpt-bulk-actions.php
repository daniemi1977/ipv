<?php
/**
 * IPV Production System Pro - CPT Bulk Actions
 *
 * Azioni di gruppo per il CPT ipv_video
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_CPT_Bulk_Actions {

    public static function init() {
        // Registra bulk actions
        add_filter( 'bulk_actions-edit-ipv_video', [ __CLASS__, 'register_bulk_actions' ] );

        // Handle bulk actions
        add_filter( 'handle_bulk_actions-edit-ipv_video', [ __CLASS__, 'handle_bulk_actions' ], 10, 3 );

        // Admin notices
        add_action( 'admin_notices', [ __CLASS__, 'bulk_action_admin_notice' ] );

        // Enqueue admin scripts
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    /**
     * Register bulk actions
     */
    public static function register_bulk_actions( $bulk_actions ) {
        $bulk_actions['ipv_refresh_youtube'] = '🔄 Refresh Dati YouTube';
        $bulk_actions['ipv_regen_transcript'] = '📝 Rigenera Trascrizioni';
        $bulk_actions['ipv_regen_ai'] = '✨ Rigenera Descrizioni AI';
        $bulk_actions['ipv_pipeline_full'] = '🚀 Pipeline Completa (T + AI)';

        return $bulk_actions;
    }

    /**
     * Handle bulk actions
     */
    public static function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
        // Remove query args
        $redirect_to = remove_query_arg( [ 'ipv_bulk_processed', 'ipv_bulk_action' ], $redirect_to );

        // Check if it's our action
        if ( ! in_array( $doaction, [ 'ipv_refresh_youtube', 'ipv_regen_transcript', 'ipv_regen_ai', 'ipv_pipeline_full' ] ) ) {
            return $redirect_to;
        }

        // Process action
        $processed = 0;

        foreach ( $post_ids as $post_id ) {
            $result = false;

            switch ( $doaction ) {
                case 'ipv_refresh_youtube':
                    $result = self::refresh_youtube_data( $post_id );
                    break;

                case 'ipv_regen_transcript':
                    $result = self::regenerate_transcript( $post_id );
                    break;

                case 'ipv_regen_ai':
                    $result = self::regenerate_ai_description( $post_id );
                    break;

                case 'ipv_pipeline_full':
                    $result = self::full_pipeline( $post_id );
                    break;
            }

            if ( $result ) {
                $processed++;
            }

            // Log
            IPV_Prod_Logger::log( "Bulk action {$doaction} on post {$post_id}", [ 'result' => $result ? 'success' : 'failed' ] );
        }

        // Add query args for notice
        $redirect_to = add_query_arg( [
            'ipv_bulk_processed' => $processed,
            'ipv_bulk_action' => $doaction,
            'ipv_bulk_total' => count( $post_ids ),
        ], $redirect_to );

        return $redirect_to;
    }

    /**
     * Refresh YouTube data - v10.0.21 usa regenerate_video_data che aggiorna anche il titolo
     */
    private static function refresh_youtube_data( $post_id ) {
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        $video_source = get_post_meta( $post_id, '_ipv_video_source', true ) ?: 'youtube';

        if ( empty( $video_id ) ) {
            return false;
        }

        // v10.0.21 - Use regenerate_video_data for YouTube which updates title + all meta
        if ( $video_source === 'youtube' && class_exists( 'IPV_Prod_YouTube_API' ) ) {
            $result = IPV_Prod_YouTube_API::regenerate_video_data( $post_id );
            return ! is_wp_error( $result );
        }

        // Get fresh data from API for other sources
        $video_data = null;

        switch ( $video_source ) {
            case 'vimeo':
                if ( class_exists( 'IPV_Prod_Vimeo_API' ) ) {
                    $video_data = IPV_Prod_Vimeo_API::get_video_data( $video_id );
                }
                break;

            case 'dailymotion':
                if ( class_exists( 'IPV_Prod_Dailymotion_API' ) ) {
                    $video_data = IPV_Prod_Dailymotion_API::get_video_data( $video_id );
                }
                break;
        }

        if ( is_wp_error( $video_data ) || empty( $video_data ) ) {
            return false;
        }

        // Update metadata
        if ( isset( $video_data['title'] ) ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => $video_data['title'] ] );
        }

        if ( isset( $video_data['view_count'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_view_count', $video_data['view_count'] );
        }

        if ( isset( $video_data['like_count'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_like_count', $video_data['like_count'] );
        }

        if ( isset( $video_data['comment_count'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_comment_count', $video_data['comment_count'] );
        }

        if ( isset( $video_data['duration'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_duration_formatted', $video_data['duration'] );
        }

        if ( isset( $video_data['duration_seconds'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_duration_seconds', $video_data['duration_seconds'] );
        }

        if ( isset( $video_data['thumbnail_url'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_thumbnail_url', $video_data['thumbnail_url'] );
        }

        if ( isset( $video_data['channel_title'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_channel_title', $video_data['channel_title'] );
        }

        if ( isset( $video_data['published_at'] ) ) {
            update_post_meta( $post_id, '_ipv_yt_published_at', $video_data['published_at'] );
        }

        return true;
    }

    /**
     * Regenerate transcript
     */
    private static function regenerate_transcript( $post_id ) {
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $video_id ) ) {
            return false;
        }

        // Get transcript from SupaData
        if ( ! class_exists( 'IPV_Prod_SupaData' ) ) {
            return false;
        }

        $transcript = IPV_Prod_SupaData::get_transcript( $video_id );

        if ( is_wp_error( $transcript ) || empty( $transcript ) ) {
            return false;
        }

        // Save transcript
        update_post_meta( $post_id, '_ipv_transcript', $transcript );

        return true;
    }

    /**
     * Regenerate AI description
     */
    private static function regenerate_ai_description( $post_id ) {
        if ( ! class_exists( 'IPV_Prod_AI_Generator' ) ) {
            return false;
        }

        // Check if transcript exists
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return false;
        }

        // Generate AI description
        $result = IPV_Prod_AI_Generator::generate_and_save( $post_id );

        if ( is_wp_error( $result ) ) {
            return false;
        }

        return true;
    }

    /**
     * Full pipeline: Transcript + AI Description
     */
    private static function full_pipeline( $post_id ) {
        // Step 1: Regenerate transcript
        $transcript_ok = self::regenerate_transcript( $post_id );

        if ( ! $transcript_ok ) {
            return false;
        }

        // Step 2: Regenerate AI description
        $ai_ok = self::regenerate_ai_description( $post_id );

        return $ai_ok;
    }

    /**
     * Display admin notice
     */
    public static function bulk_action_admin_notice() {
        if ( ! isset( $_REQUEST['ipv_bulk_processed'] ) ) {
            return;
        }

        $processed = intval( $_REQUEST['ipv_bulk_processed'] );
        $total = intval( $_REQUEST['ipv_bulk_total'] );
        $action = sanitize_text_field( $_REQUEST['ipv_bulk_action'] );

        $action_labels = [
            'ipv_refresh_youtube' => 'Refresh Dati YouTube',
            'ipv_regen_transcript' => 'Rigenera Trascrizioni',
            'ipv_regen_ai' => 'Rigenera Descrizioni AI',
            'ipv_pipeline_full' => 'Pipeline Completa',
        ];

        $action_label = $action_labels[ $action ] ?? $action;

        $class = 'notice notice-success is-dismissible';
        $message = sprintf(
            '<strong>%s:</strong> %d di %d video elaborati con successo.',
            $action_label,
            $processed,
            $total
        );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }

    /**
     * Enqueue admin scripts
     */
    public static function enqueue_scripts( $hook ) {
        if ( 'edit.php' !== $hook ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'ipv_video' ) {
            return;
        }

        // Add confirmation for slow actions
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('select[name="action"], select[name="action2"]').on('change', function() {
                var action = $(this).val();
                var slowActions = ['ipv_regen_transcript', 'ipv_regen_ai', 'ipv_pipeline_full'];

                if (slowActions.indexOf(action) !== -1) {
                    var $form = $(this).closest('form');

                    $form.off('submit.ipv_bulk').on('submit.ipv_bulk', function(e) {
                        var checked = $('input[name="post[]"]:checked').length;

                        if (checked === 0) {
                            return true;
                        }

                        var actionLabels = {
                            'ipv_regen_transcript': 'Rigenera Trascrizioni (usa crediti SupaData)',
                            'ipv_regen_ai': 'Rigenera Descrizioni AI (usa crediti OpenAI)',
                            'ipv_pipeline_full': 'Pipeline Completa (usa crediti SupaData + OpenAI)'
                        };

                        var label = actionLabels[action] || action;
                        var message = 'Stai per eseguire "' + label + '" su ' + checked + ' video.\n\n';
                        message += 'Questa operazione potrebbe richiedere diversi minuti e consumare crediti API.\n\n';
                        message += 'Vuoi continuare?';

                        if (!confirm(message)) {
                            e.preventDefault();
                            return false;
                        }
                    });
                } else {
                    $(this).closest('form').off('submit.ipv_bulk');
                }
            });
        });
        </script>
        <?php
    }
}

IPV_Prod_CPT_Bulk_Actions::init();
