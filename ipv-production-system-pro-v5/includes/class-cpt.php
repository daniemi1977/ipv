<?php
/**
 * Custom Post Type per Video IPV
 *
 * Integrato con Elementor per editing e visualizzazione
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_CPT {

    const POST_TYPE = 'video_ipv';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_meta_boxes' ], 10, 2 );
    }

    public static function register() {
        $labels = [
            'name'                  => 'Video IPV',
            'singular_name'         => 'Video IPV',
            'menu_name'             => 'Video IPV',
            'add_new'               => 'Aggiungi nuovo',
            'add_new_item'          => 'Aggiungi nuovo Video',
            'edit_item'             => 'Modifica Video',
            'new_item'              => 'Nuovo Video',
            'view_item'             => 'Vedi Video',
            'view_items'            => 'Vedi Video',
            'search_items'          => 'Cerca Video',
            'not_found'             => 'Nessun video trovato',
            'not_found_in_trash'    => 'Nessun video nel cestino',
            'all_items'             => 'Tutti i Video',
            'archives'              => 'Archivio Video',
            'attributes'            => 'Attributi Video',
            'insert_into_item'      => 'Inserisci nel video',
            'uploaded_to_this_item' => 'Caricato in questo video',
            'filter_items_list'     => 'Filtra video',
            'items_list_navigation' => 'Navigazione video',
            'items_list'            => 'Lista video',
        ];

        $args = [
            'labels'              => $labels,
            'description'         => 'Video importati da YouTube con trascrizione e descrizione AI.',
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => [
                'slug'       => 'video',
                'with_front' => false,
            ],
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-video-alt3',
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
                'comments',
                'revisions',
                'elementor', // IMPORTANTE: Supporto Elementor
            ],
            'show_in_rest'        => true,
            'rest_base'           => 'video-ipv',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'taxonomies'          => [],
        ];

        register_post_type( self::POST_TYPE, $args );

        // Flush rewrite rules se necessario
        if ( get_option( 'ipv_flush_rewrite_rules' ) === 'yes' ) {
            flush_rewrite_rules();
            delete_option( 'ipv_flush_rewrite_rules' );
        }
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'ipv_youtube_data',
            '<span class="dashicons dashicons-youtube" style="color:#ff0000;"></span> Dati YouTube',
            [ __CLASS__, 'render_youtube_meta_box' ],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'ipv_transcript',
            '<span class="dashicons dashicons-text-page"></span> Trascrizione',
            [ __CLASS__, 'render_transcript_meta_box' ],
            self::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'ipv_files',
            '<span class="dashicons dashicons-media-document"></span> File e Slide',
            [ __CLASS__, 'render_files_meta_box' ],
            self::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'ipv_stats',
            '<span class="dashicons dashicons-chart-bar"></span> Statistiche YouTube',
            [ __CLASS__, 'render_stats_meta_box' ],
            self::POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'ipv_actions',
            '<span class="dashicons dashicons-admin-tools"></span> Azioni',
            [ __CLASS__, 'render_actions_meta_box' ],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public static function render_youtube_meta_box( $post ) {
        wp_nonce_field( 'ipv_save_meta', 'ipv_meta_nonce' );

        $video_id      = get_post_meta( $post->ID, '_ipv_video_id', true );
        $youtube_url   = get_post_meta( $post->ID, '_ipv_youtube_url', true );
        $yt_title      = get_post_meta( $post->ID, '_ipv_yt_title', true );
        $yt_desc       = get_post_meta( $post->ID, '_ipv_yt_description', true );
        $yt_published  = get_post_meta( $post->ID, '_ipv_yt_published_at', true );
        $yt_channel    = get_post_meta( $post->ID, '_ipv_yt_channel_title', true );
        $yt_tags       = get_post_meta( $post->ID, '_ipv_yt_tags', true );
        $yt_duration   = get_post_meta( $post->ID, '_ipv_yt_duration_formatted', true );
        $yt_definition = get_post_meta( $post->ID, '_ipv_yt_definition', true );
        $yt_thumbnail  = get_post_meta( $post->ID, '_ipv_yt_thumbnail_url', true );
        ?>
        <style>
            .ipv-meta-row { display: flex; margin-bottom: 15px; gap: 15px; }
            .ipv-meta-col { flex: 1; }
            .ipv-meta-label { font-weight: 600; margin-bottom: 5px; display: block; }
            .ipv-meta-value { background: #f5f5f5; padding: 8px 12px; border-radius: 4px; }
            .ipv-meta-input { width: 100%; }
            .ipv-video-preview { display: flex; gap: 15px; margin-bottom: 20px; }
            .ipv-video-thumb { width: 200px; border-radius: 8px; }
            .ipv-video-info { flex: 1; }
            .ipv-tags-list { display: flex; flex-wrap: wrap; gap: 5px; }
            .ipv-tag { background: #e0e0e0; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        </style>

        <?php if ( $video_id ) : ?>
            <div class="ipv-video-preview">
                <?php if ( $yt_thumbnail ) : ?>
                    <img src="<?php echo esc_url( $yt_thumbnail ); ?>" alt="" class="ipv-video-thumb">
                <?php endif; ?>
                <div class="ipv-video-info">
                    <h3 style="margin-top:0;"><?php echo esc_html( $yt_title ?: $post->post_title ); ?></h3>
                    <p>
                        <strong>Canale:</strong> <?php echo esc_html( $yt_channel ); ?><br>
                        <strong>Pubblicato:</strong> <?php echo $yt_published ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $yt_published ) ) ) : 'N/D'; ?><br>
                        <strong>Durata:</strong> <?php echo esc_html( $yt_duration ?: 'N/D' ); ?> |
                        <strong>Qualità:</strong> <?php echo esc_html( strtoupper( $yt_definition ?: 'N/D' ) ); ?>
                    </p>
                    <a href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-external" style="vertical-align:middle;"></span>
                        Apri su YouTube
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="ipv-meta-row">
            <div class="ipv-meta-col">
                <label class="ipv-meta-label">YouTube Video ID</label>
                <input type="text" name="ipv_video_id" value="<?php echo esc_attr( $video_id ); ?>" class="ipv-meta-input regular-text" placeholder="es. dQw4w9WgXcQ">
            </div>
            <div class="ipv-meta-col">
                <label class="ipv-meta-label">URL YouTube</label>
                <input type="url" name="ipv_youtube_url" value="<?php echo esc_attr( $youtube_url ); ?>" class="ipv-meta-input regular-text" placeholder="https://www.youtube.com/watch?v=...">
            </div>
        </div>

        <?php if ( ! empty( $yt_tags ) && is_array( $yt_tags ) ) : ?>
            <div class="ipv-meta-row">
                <div class="ipv-meta-col">
                    <label class="ipv-meta-label">Tag YouTube Originali</label>
                    <div class="ipv-tags-list">
                        <?php foreach ( $yt_tags as $tag ) : ?>
                            <span class="ipv-tag"><?php echo esc_html( $tag ); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $yt_desc ) : ?>
            <div class="ipv-meta-row">
                <div class="ipv-meta-col">
                    <label class="ipv-meta-label">Descrizione YouTube Originale</label>
                    <div class="ipv-meta-value" style="max-height:200px;overflow-y:auto;white-space:pre-wrap;"><?php echo esc_html( $yt_desc ); ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    public static function render_transcript_meta_box( $post ) {
        $transcript = get_post_meta( $post->ID, '_ipv_transcript', true );
        $word_count = $transcript ? str_word_count( $transcript ) : 0;
        $char_count = $transcript ? mb_strlen( $transcript ) : 0;
        ?>
        <p>
            <strong>Parole:</strong> <?php echo number_format_i18n( $word_count ); ?> |
            <strong>Caratteri:</strong> <?php echo number_format_i18n( $char_count ); ?>
        </p>
        <textarea name="ipv_transcript" rows="15" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $transcript ); ?></textarea>
        <p class="description">
            La trascrizione viene generata automaticamente da SupaData. Puoi modificarla manualmente se necessario.
        </p>
        <?php
    }

    public static function render_files_meta_box( $post ) {
        $files = get_post_meta( $post->ID, '_ipv_files', true );
        if ( ! is_array( $files ) ) {
            $files = [];
        }
        ?>
        <style>
            .ipv-files-list { margin-bottom: 15px; }
            .ipv-file-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; margin-bottom: 8px; }
            .ipv-file-icon { font-size: 24px; }
            .ipv-file-info { flex: 1; }
            .ipv-file-name { font-weight: 600; display: block; margin-bottom: 3px; }
            .ipv-file-meta { font-size: 12px; color: #666; }
            .ipv-file-remove { color: #d63638; cursor: pointer; text-decoration: none; }
            .ipv-file-remove:hover { color: #a00; }
        </style>

        <div class="ipv-files-list" id="ipv-files-list">
            <?php if ( ! empty( $files ) ) : ?>
                <?php foreach ( $files as $index => $file ) :
                    $file_url = isset( $file['url'] ) ? $file['url'] : '';
                    $file_name = isset( $file['name'] ) ? $file['name'] : '';
                    $file_size = isset( $file['size'] ) ? size_format( $file['size'], 2 ) : '';
                    $file_type = isset( $file['type'] ) ? $file['type'] : '';
                    ?>
                    <div class="ipv-file-item" data-index="<?php echo esc_attr( $index ); ?>">
                        <span class="ipv-file-icon dashicons dashicons-media-document"></span>
                        <div class="ipv-file-info">
                            <span class="ipv-file-name"><?php echo esc_html( $file_name ); ?></span>
                            <span class="ipv-file-meta">
                                <?php echo esc_html( $file_size ); ?>
                                <?php if ( $file_type ) : ?>| <?php echo esc_html( $file_type ); ?><?php endif; ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="button button-small">
                            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                        </a>
                        <a href="#" class="ipv-file-remove" data-index="<?php echo esc_attr( $index ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                        <input type="hidden" name="ipv_files[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $file_url ); ?>">
                        <input type="hidden" name="ipv_files[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $file_name ); ?>">
                        <input type="hidden" name="ipv_files[<?php echo esc_attr( $index ); ?>][size]" value="<?php echo esc_attr( $file['size'] ?? 0 ); ?>">
                        <input type="hidden" name="ipv_files[<?php echo esc_attr( $index ); ?>][type]" value="<?php echo esc_attr( $file_type ); ?>">
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="description">Nessun file caricato. Clicca su "Aggiungi File" per caricare slide o documenti.</p>
            <?php endif; ?>
        </div>

        <button type="button" class="button button-secondary" id="ipv-add-file-btn">
            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
            Aggiungi File/Slide
        </button>

        <p class="description" style="margin-top: 10px;">
            Carica slide PDF, PowerPoint, o altri documenti relativi al video. Saranno disponibili per il download nella pagina del video.
        </p>

        <script>
        jQuery(document).ready(function($) {
            var fileIndex = <?php echo count( $files ); ?>;
            var fileFrame;

            $('#ipv-add-file-btn').on('click', function(e) {
                e.preventDefault();

                if (fileFrame) {
                    fileFrame.open();
                    return;
                }

                fileFrame = wp.media({
                    title: 'Seleziona File',
                    button: { text: 'Aggiungi File' },
                    multiple: true
                });

                fileFrame.on('select', function() {
                    var attachments = fileFrame.state().get('selection').toJSON();

                    attachments.forEach(function(attachment) {
                        var html = '<div class="ipv-file-item" data-index="' + fileIndex + '">' +
                            '<span class="ipv-file-icon dashicons dashicons-media-document"></span>' +
                            '<div class="ipv-file-info">' +
                                '<span class="ipv-file-name">' + attachment.filename + '</span>' +
                                '<span class="ipv-file-meta">' +
                                    (attachment.filesizeHumanReadable || '') +
                                    (attachment.subtype ? ' | ' + attachment.subtype : '') +
                                '</span>' +
                            '</div>' +
                            '<a href="' + attachment.url + '" target="_blank" class="button button-small">' +
                                '<span class="dashicons dashicons-download" style="vertical-align: middle;"></span>' +
                            '</a>' +
                            '<a href="#" class="ipv-file-remove" data-index="' + fileIndex + '">' +
                                '<span class="dashicons dashicons-trash"></span>' +
                            '</a>' +
                            '<input type="hidden" name="ipv_files[' + fileIndex + '][url]" value="' + attachment.url + '">' +
                            '<input type="hidden" name="ipv_files[' + fileIndex + '][name]" value="' + attachment.filename + '">' +
                            '<input type="hidden" name="ipv_files[' + fileIndex + '][size]" value="' + (attachment.filesizeInBytes || 0) + '">' +
                            '<input type="hidden" name="ipv_files[' + fileIndex + '][type]" value="' + (attachment.subtype || '') + '">' +
                        '</div>';

                        $('#ipv-files-list').append(html);
                        fileIndex++;
                    });

                    // Rimuovi messaggio "Nessun file"
                    $('#ipv-files-list .description').remove();
                });

                fileFrame.open();
            });

            $(document).on('click', '.ipv-file-remove', function(e) {
                e.preventDefault();
                if (confirm('Rimuovere questo file?')) {
                    $(this).closest('.ipv-file-item').remove();

                    // Se non ci sono più file, mostra messaggio
                    if ($('#ipv-files-list .ipv-file-item').length === 0) {
                        $('#ipv-files-list').html('<p class="description">Nessun file caricato. Clicca su "Aggiungi File" per caricare slide o documenti.</p>');
                    }
                }
            });
        });
        </script>
        <?php
    }

    public static function render_stats_meta_box( $post ) {
        $view_count    = get_post_meta( $post->ID, '_ipv_yt_view_count', true );
        $like_count    = get_post_meta( $post->ID, '_ipv_yt_like_count', true );
        $comment_count = get_post_meta( $post->ID, '_ipv_yt_comment_count', true );
        $data_updated  = get_post_meta( $post->ID, '_ipv_yt_data_updated', true );
        ?>
        <style>
            .ipv-stat-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
            .ipv-stat-item:last-child { border-bottom: 0; }
            .ipv-stat-value { font-weight: 600; color: #2271b1; }
        </style>

        <div class="ipv-stat-item">
            <span><span class="dashicons dashicons-visibility"></span> Visualizzazioni</span>
            <span class="ipv-stat-value"><?php echo $view_count ? number_format_i18n( $view_count ) : 'N/D'; ?></span>
        </div>
        <div class="ipv-stat-item">
            <span><span class="dashicons dashicons-thumbs-up"></span> Like</span>
            <span class="ipv-stat-value"><?php echo $like_count ? number_format_i18n( $like_count ) : 'N/D'; ?></span>
        </div>
        <div class="ipv-stat-item">
            <span><span class="dashicons dashicons-admin-comments"></span> Commenti</span>
            <span class="ipv-stat-value"><?php echo $comment_count ? number_format_i18n( $comment_count ) : 'N/D'; ?></span>
        </div>

        <?php if ( $data_updated ) : ?>
            <p class="description" style="margin-top:10px;">
                <small>Ultimo aggiornamento: <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $data_updated ) ) ); ?></small>
            </p>
        <?php endif; ?>
        <?php
    }

    public static function render_actions_meta_box( $post ) {
        $video_id = get_post_meta( $post->ID, '_ipv_video_id', true );
        ?>
        <div style="display:grid;gap:10px;">
            <?php if ( $video_id ) : ?>
                <button type="button" class="button button-secondary" id="ipv-refresh-yt-data" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <span class="dashicons dashicons-update" style="vertical-align:middle;"></span>
                    Aggiorna Dati YouTube
                </button>
                <button type="button" class="button button-secondary" id="ipv-regenerate-transcript" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <span class="dashicons dashicons-text-page" style="vertical-align:middle;"></span>
                    Rigenera Trascrizione
                </button>
                <button type="button" class="button button-secondary" id="ipv-regenerate-description" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <span class="dashicons dashicons-editor-paste-text" style="vertical-align:middle;"></span>
                    Rigenera Descrizione AI
                </button>
            <?php else : ?>
                <p class="description">Salva prima il Video ID per abilitare le azioni.</p>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-refresh-yt-data, #ipv-regenerate-transcript, #ipv-regenerate-description').on('click', function() {
                var btn = $(this);
                var action = btn.attr('id').replace('ipv-', '').replace(/-/g, '_');
                var postId = btn.data('post-id');

                btn.prop('disabled', true).find('.dashicons').addClass('dashicons-update-spin');

                $.post(ajaxurl, {
                    action: 'ipv_prod_' + action,
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce( 'ipv_prod_action' ); ?>'
                }, function(response) {
                    btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['ipv_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ipv_meta_nonce'], 'ipv_save_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['ipv_video_id'] ) ) {
            $video_id = sanitize_text_field( wp_unslash( $_POST['ipv_video_id'] ) );
            update_post_meta( $post_id, '_ipv_video_id', $video_id );

            if ( $video_id && empty( $_POST['ipv_youtube_url'] ) ) {
                update_post_meta( $post_id, '_ipv_youtube_url', 'https://www.youtube.com/watch?v=' . $video_id );
            }
        }

        if ( isset( $_POST['ipv_youtube_url'] ) ) {
            update_post_meta( $post_id, '_ipv_youtube_url', esc_url_raw( wp_unslash( $_POST['ipv_youtube_url'] ) ) );
        }

        if ( isset( $_POST['ipv_transcript'] ) ) {
            update_post_meta( $post_id, '_ipv_transcript', sanitize_textarea_field( wp_unslash( $_POST['ipv_transcript'] ) ) );
        }

        // Salva files/slide
        if ( isset( $_POST['ipv_files'] ) && is_array( $_POST['ipv_files'] ) ) {
            $files = [];
            foreach ( $_POST['ipv_files'] as $file ) {
                if ( ! empty( $file['url'] ) ) {
                    $files[] = [
                        'url'  => esc_url_raw( $file['url'] ),
                        'name' => sanitize_text_field( $file['name'] ),
                        'size' => absint( $file['size'] ),
                        'type' => sanitize_text_field( $file['type'] ),
                    ];
                }
            }
            update_post_meta( $post_id, '_ipv_files', $files );
        } else {
            delete_post_meta( $post_id, '_ipv_files' );
        }
    }
}

IPV_Prod_CPT::init();

// AJAX Handlers
add_action( 'wp_ajax_ipv_prod_refresh_yt_data', function() {
    check_ajax_referer( 'ipv_prod_action', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
    }

    $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
    if ( ! $video_id ) {
        wp_send_json_error( [ 'message' => 'Video ID mancante.' ] );
    }

    $video_data = IPV_Prod_YouTube_API::get_video_data( $video_id );
    if ( is_wp_error( $video_data ) ) {
        wp_send_json_error( [ 'message' => $video_data->get_error_message() ] );
    }

    IPV_Prod_YouTube_API::save_video_meta( $post_id, $video_data );

    wp_send_json_success( [ 'message' => 'Dati YouTube aggiornati con successo!' ] );
} );

add_action( 'wp_ajax_ipv_prod_regenerate_transcript', function() {
    check_ajax_referer( 'ipv_prod_action', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
    }

    $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
    if ( ! $video_id ) {
        wp_send_json_error( [ 'message' => 'Video ID mancante.' ] );
    }

    $mode = get_option( 'ipv_transcript_mode', 'auto' );
    $transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );

    if ( is_wp_error( $transcript ) ) {
        wp_send_json_error( [ 'message' => $transcript->get_error_message() ] );
    }

    update_post_meta( $post_id, '_ipv_transcript', $transcript );

    wp_send_json_success( [ 'message' => 'Trascrizione rigenerata con successo!' ] );
} );

add_action( 'wp_ajax_ipv_prod_regenerate_description', function() {
    check_ajax_referer( 'ipv_prod_action', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( [ 'message' => 'Non autorizzato.' ] );
    }

    $transcript = get_post_meta( $post_id, '_ipv_transcript', true );
    if ( empty( $transcript ) ) {
        wp_send_json_error( [ 'message' => 'Trascrizione mancante. Genera prima la trascrizione.' ] );
    }

    $title = get_the_title( $post_id );
    $desc = IPV_Prod_AI_Generator::generate_description( $title, $transcript );

    if ( is_wp_error( $desc ) ) {
        wp_send_json_error( [ 'message' => $desc->get_error_message() ] );
    }

    wp_update_post( [
        'ID'           => $post_id,
        'post_content' => $desc,
    ] );
    update_post_meta( $post_id, '_ipv_ai_description', $desc );

    wp_send_json_success( [ 'message' => 'Descrizione AI rigenerata con successo!' ] );
} );
