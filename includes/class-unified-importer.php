<?php
/**
 * IPV Production System Pro - Unified Video Importer
 *
 * Auto-detect and import from YouTube, Vimeo, Dailymotion
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Unified_Importer {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 20 );
        add_action( 'wp_ajax_ipv_unified_import', [ __CLASS__, 'ajax_import_video' ] );
        add_action( 'wp_ajax_ipv_detect_source', [ __CLASS__, 'ajax_detect_source' ] );
    }

    /**
     * Add submenu page
     */
    public static function add_submenu() {
        add_submenu_page(
            'ipv-production',
            'Multi-Source Importer',
            '<span class="dashicons dashicons-video-alt2"></span> Multi-Source',
            'manage_options',
            'ipv-unified-importer',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render importer page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>🎬 Multi-Source Video Importer</h1>
            <p class="description">Importa video da YouTube, Vimeo o Dailymotion - rilevamento automatico!</p>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Importa Video</h2>

                <div class="ipv-import-form">
                    <p>
                        <label for="ipv-video-url"><strong>URL Video:</strong></label>
                        <input type="url" id="ipv-video-url" class="large-text" placeholder="https://youtube.com/watch?v=... o https://vimeo.com/... o https://dailymotion.com/video/...">
                    </p>

                    <p id="ipv-detected-source" style="display:none;">
                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <strong>Rilevato:</strong> <span id="ipv-source-name"></span>
                        <span id="ipv-source-icon"></span>
                    </p>

                    <p>
                        <button type="button" id="ipv-detect-btn" class="button">
                            🔍 Rileva Fonte
                        </button>
                        <button type="button" id="ipv-import-btn" class="button button-primary" disabled>
                            ⬇️ Importa Video
                        </button>
                    </p>

                    <div id="ipv-import-progress" style="display:none;">
                        <p><strong>Importazione in corso...</strong></p>
                        <div class="ipv-progress-bar">
                            <div class="ipv-progress-fill"></div>
                        </div>
                        <p id="ipv-progress-text">Rilevamento fonte...</p>
                    </div>

                    <div id="ipv-import-result" style="display:none;"></div>
                </div>

                <hr>

                <h3>Formati Supportati</h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Piattaforma</th>
                            <th>Formati URL</th>
                            <th>Esempio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>YouTube</strong> 🔴</td>
                            <td>
                                youtube.com/watch?v=<br>
                                youtu.be/<br>
                                youtube.com/embed/
                            </td>
                            <td><code>https://youtube.com/watch?v=dQw4w9WgXcQ</code></td>
                        </tr>
                        <tr>
                            <td><strong>Vimeo</strong> 🔵</td>
                            <td>
                                vimeo.com/<br>
                                player.vimeo.com/video/
                            </td>
                            <td><code>https://vimeo.com/123456789</code></td>
                        </tr>
                        <tr>
                            <td><strong>Dailymotion</strong> 🟠</td>
                            <td>
                                dailymotion.com/video/<br>
                                dai.ly/
                            </td>
                            <td><code>https://dailymotion.com/video/x123abc</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
        .ipv-import-form {
            padding: 20px;
        }
        .ipv-progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
        }
        .ipv-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #72aee6);
            width: 0%;
            transition: width 0.3s ease;
        }
        #ipv-import-result.success {
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            color: #155724;
        }
        #ipv-import-result.error {
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let detectedSource = null;
            let videoId = null;

            $('#ipv-detect-btn').on('click', function() {
                const url = $('#ipv-video-url').val().trim();
                if (!url) {
                    alert('Inserisci un URL video');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).text('🔍 Rilevamento...');
                $('#ipv-detected-source').hide();
                $('#ipv-import-btn').prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_detect_source',
                        url: url,
                        nonce: '<?php echo wp_create_nonce( 'ipv_unified_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            detectedSource = response.data.source;
                            videoId = response.data.video_id;

                            let icon = '';
                            if (detectedSource === 'youtube') icon = '🔴';
                            else if (detectedSource === 'vimeo') icon = '🔵';
                            else if (detectedSource === 'dailymotion') icon = '🟠';

                            $('#ipv-source-name').text(response.data.source.toUpperCase());
                            $('#ipv-source-icon').text(icon);
                            $('#ipv-detected-source').fadeIn();
                            $('#ipv-import-btn').prop('disabled', false);
                        } else {
                            alert('❌ ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🔍 Rileva Fonte');
                    }
                });
            });

            $('#ipv-import-btn').on('click', function() {
                if (!detectedSource || !videoId) {
                    alert('Rileva prima la fonte del video');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $('#ipv-import-progress').show();
                $('#ipv-import-result').hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_unified_import',
                        source: detectedSource,
                        video_id: videoId,
                        nonce: '<?php echo wp_create_nonce( 'ipv_unified_nonce' ); ?>'
                    },
                    success: function(response) {
                        $('#ipv-import-progress').hide();

                        if (response.success) {
                            $('#ipv-import-result')
                                .removeClass('error')
                                .addClass('success')
                                .html('✅ <strong>Video importato con successo!</strong><br>' +
                                      'Post ID: ' + response.data.post_id + '<br>' +
                                      '<a href="' + response.data.edit_url + '" class="button">Modifica Video</a>')
                                .fadeIn();

                            // Reset form
                            $('#ipv-video-url').val('');
                            $('#ipv-detected-source').hide();
                            detectedSource = null;
                            videoId = null;
                        } else {
                            $('#ipv-import-result')
                                .removeClass('success')
                                .addClass('error')
                                .html('❌ <strong>Errore:</strong> ' + response.data)
                                .fadeIn();
                        }
                    },
                    error: function() {
                        $('#ipv-import-progress').hide();
                        $('#ipv-import-result')
                            .removeClass('success')
                            .addClass('error')
                            .text('❌ Errore di connessione')
                            .fadeIn();
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Detect video source from URL
     */
    public static function ajax_detect_source() {
        check_ajax_referer( 'ipv_unified_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

        if ( empty( $url ) ) {
            wp_send_json_error( 'URL non valido' );
        }

        $detection = self::detect_source( $url );

        if ( is_wp_error( $detection ) ) {
            wp_send_json_error( $detection->get_error_message() );
        }

        wp_send_json_success( $detection );
    }

    /**
     * Detect video source and extract ID
     */
    public static function detect_source( $url ) {
        // Try YouTube
        if ( preg_match( '/youtube\.com|youtu\.be/', $url ) ) {
            $video_id = self::extract_youtube_id( $url );
            if ( $video_id ) {
                return [
                    'source' => 'youtube',
                    'video_id' => $video_id,
                ];
            }
        }

        // Try Vimeo
        if ( preg_match( '/vimeo\.com/', $url ) ) {
            $video_id = IPV_Prod_Vimeo_API::extract_video_id( $url );
            if ( $video_id ) {
                return [
                    'source' => 'vimeo',
                    'video_id' => $video_id,
                ];
            }
        }

        // Try Dailymotion
        if ( preg_match( '/dailymotion\.com|dai\.ly/', $url ) ) {
            $video_id = IPV_Prod_Dailymotion_API::extract_video_id( $url );
            if ( $video_id ) {
                return [
                    'source' => 'dailymotion',
                    'video_id' => $video_id,
                ];
            }
        }

        return new WP_Error( 'unknown_source', 'Fonte video non riconosciuta. Supportati: YouTube, Vimeo, Dailymotion' );
    }

    /**
     * Extract YouTube video ID
     */
    private static function extract_youtube_id( $url ) {
        preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches );
        return $matches[1] ?? false;
    }

    /**
     * AJAX: Import video from detected source
     */
    public static function ajax_import_video() {
        check_ajax_referer( 'ipv_unified_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : '';
        $video_id = isset( $_POST['video_id'] ) ? sanitize_text_field( $_POST['video_id'] ) : '';

        if ( empty( $source ) || empty( $video_id ) ) {
            wp_send_json_error( 'Parametri mancanti' );
        }

        $result = self::import_video( $source, $video_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( [
            'post_id' => $result,
            'edit_url' => get_edit_post_link( $result, 'raw' ),
        ] );
    }

    /**
     * Import video from source
     */
    public static function import_video( $source, $video_id ) {
        // Get video data based on source
        switch ( $source ) {
            case 'youtube':
                $video_data = IPV_Prod_YouTube_API::get_video_data( $video_id );
                break;
            case 'vimeo':
                $video_data = IPV_Prod_Vimeo_API::get_video_data( $video_id );
                break;
            case 'dailymotion':
                $video_data = IPV_Prod_Dailymotion_API::get_video_data( $video_id );
                break;
            default:
                return new WP_Error( 'invalid_source', 'Fonte non valida' );
        }

        if ( is_wp_error( $video_data ) ) {
            return $video_data;
        }

        // Check if video already exists
        $existing = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_ipv_video_id',
                    'value' => $video_id,
                ],
                [
                    'key' => '_ipv_video_source',
                    'value' => $source,
                ],
            ],
            'fields' => 'ids',
        ] );

        if ( ! empty( $existing ) ) {
            return new WP_Error( 'already_exists', 'Video già importato (Post ID: ' . $existing[0] . ')' );
        }

        // Create post
        $post_data = [
            'post_type' => 'ipv_video',
            'post_title' => $video_data['title'],
            'post_content' => $video_data['description'],
            'post_status' => 'draft',
        ];

        // Use video publish date as post_date if available
        if ( ! empty( $video_data['published_at'] ) ) {
            $post_data['post_date'] = get_date_from_gmt( $video_data['published_at'] );
            $post_data['post_date_gmt'] = $video_data['published_at'];
        }

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Save meta based on source
        switch ( $source ) {
            case 'youtube':
                IPV_Prod_YouTube_API::save_video_meta( $post_id, $video_data );
                break;
            case 'vimeo':
                IPV_Prod_Vimeo_API::save_video_meta( $post_id, $video_data );
                break;
            case 'dailymotion':
                IPV_Prod_Dailymotion_API::save_video_meta( $post_id, $video_data );
                break;
        }

        IPV_Prod_Logger::log( 'Unified import successful', [
            'source' => $source,
            'video_id' => $video_id,
            'post_id' => $post_id,
        ] );

        return $post_id;
    }
}

IPV_Prod_Unified_Importer::init();
