<?php
/**
 * IPV Production System Pro - Custom Post Type
 *
 * Gestione completa del CPT video_ipv con:
 * - Registrazione post type e tassonomie
 * - Meta boxes per dati YouTube e trascrizione
 * - Colonne admin personalizzate (thumbnail, durata, views, stato)
 * - Integrazione SEO (Yoast/RankMath)
 * - Salvataggio e sincronizzazione dati
 *
 * @package IPV_Production_System_Pro
 * @version 4.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_CPT {

    /**
     * Slug del post type
     */
    const POST_TYPE = 'ipv_video';

    /**
     * Inizializza il CPT
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_meta_boxes' ], 10, 2 );
        // Aggiungi submenu tassonomie con priorità alta
        // RIMOSSO duplicati: add_action( 'admin_menu', [ __CLASS__, 'add_taxonomy_submenus' ], 999 );
        // Le colonne admin sono gestite da class-video-list-columns.php
    }

    /**
     * Aggiunge manualmente i submenu per le tassonomie sotto IPV Videos
     */
    public static function add_taxonomy_submenus() {
        $parent = 'edit.php?post_type=' . self::POST_TYPE;
        
        // Categories (ipv_categoria)
        add_submenu_page(
            $parent,
            __( 'Video Categories', 'ipv-production-system-pro' ),
            __( 'Categories', 'ipv-production-system-pro' ),
            'manage_categories',
            'edit-tags.php?taxonomy=ipv_categoria&post_type=' . self::POST_TYPE,
            ''
        );
        
        // Speakers (ipv_relatore)
        add_submenu_page(
            $parent,
            __( 'Speakers', 'ipv-production-system-pro' ),
            __( 'Speakers', 'ipv-production-system-pro' ),
            'manage_categories',
            'edit-tags.php?taxonomy=ipv_relatore&post_type=' . self::POST_TYPE,
            ''
        );
        
        // Tags (post_tag)
        add_submenu_page(
            $parent,
            __( 'Video Tags', 'ipv-production-system-pro' ),
            __( 'Tags', 'ipv-production-system-pro' ),
            'manage_categories',
            'edit-tags.php?taxonomy=post_tag&post_type=' . self::POST_TYPE,
            ''
        );
    }

    /**
     * Register the Custom Post Type
     */
    public static function register() {
        $labels = [
            'name'                  => _x( 'IPV Videos', 'post type general name', 'ipv-production-system-pro' ),
            'singular_name'         => _x( 'IPV Video', 'post type singular name', 'ipv-production-system-pro' ),
            'menu_name'             => _x( 'IPV Videos', 'admin menu', 'ipv-production-system-pro' ),
            'add_new'               => __( 'Add New', 'ipv-production-system-pro' ),
            'add_new_item'          => __( 'Add New Video', 'ipv-production-system-pro' ),
            'edit_item'             => __( 'Edit Video', 'ipv-production-system-pro' ),
            'new_item'              => __( 'New Video', 'ipv-production-system-pro' ),
            'view_item'             => __( 'View Video', 'ipv-production-system-pro' ),
            'view_items'            => __( 'View Videos', 'ipv-production-system-pro' ),
            'search_items'          => __( 'Search Videos', 'ipv-production-system-pro' ),
            'not_found'             => __( 'No videos found', 'ipv-production-system-pro' ),
            'not_found_in_trash'    => __( 'No videos found in Trash', 'ipv-production-system-pro' ),
            'all_items'             => __( 'All Videos', 'ipv-production-system-pro' ),
            'archives'              => __( 'Video Archives', 'ipv-production-system-pro' ),
            'attributes'            => __( 'Video Attributes', 'ipv-production-system-pro' ),
            'insert_into_item'      => __( 'Insert into video', 'ipv-production-system-pro' ),
            'uploaded_to_this_item' => __( 'Uploaded to this video', 'ipv-production-system-pro' ),
            'filter_items_list'     => __( 'Filter videos list', 'ipv-production-system-pro' ),
            'items_list_navigation' => __( 'Videos list navigation', 'ipv-production-system-pro' ),
            'items_list'            => __( 'Videos list', 'ipv-production-system-pro' ),
        ];

        $args = [
            'labels'              => $labels,
            'description'         => __( 'Videos imported from YouTube with transcription and AI description.', 'ipv-production-system-pro' ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // v10.0.24 - Menu gestito da Menu Manager
            'query_var'           => true,
            'rewrite'             => [
                'slug'       => 'video',
                'with_front' => false,
            ],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'show_in_admin_bar'   => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-video-alt3',
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
                'comments',
                'revisions',
            ],
            'show_in_rest'        => true,
            'rest_base'           => 'video-ipv',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'taxonomies'          => [ 'ipv_categoria', 'ipv_relatore', 'post_tag' ],
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register taxonomies
     */
    public static function register_taxonomies() {
        // Video Category (ipv_categoria) - PRIMARY
        register_taxonomy( 'ipv_categoria', self::POST_TYPE, [
            'labels' => [
                'name'              => _x( 'Video Categories', 'taxonomy general name', 'ipv-production-system-pro' ),
                'singular_name'     => _x( 'Video Category', 'taxonomy singular name', 'ipv-production-system-pro' ),
                'search_items'      => __( 'Search Categories', 'ipv-production-system-pro' ),
                'all_items'         => __( 'All Categories', 'ipv-production-system-pro' ),
                'parent_item'       => __( 'Parent Category', 'ipv-production-system-pro' ),
                'parent_item_colon' => __( 'Parent Category:', 'ipv-production-system-pro' ),
                'edit_item'         => __( 'Edit Category', 'ipv-production-system-pro' ),
                'update_item'       => __( 'Update Category', 'ipv-production-system-pro' ),
                'add_new_item'      => __( 'Add New Category', 'ipv-production-system-pro' ),
                'new_item_name'     => __( 'New Category Name', 'ipv-production-system-pro' ),
                'menu_name'         => __( 'Categories', 'ipv-production-system-pro' ),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => true, // WordPress gestisce automaticamente
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'video-category' ],
            'show_in_rest'      => true,
        ] );

        // Speaker / Guest (ipv_relatore)
        register_taxonomy( 'ipv_relatore', self::POST_TYPE, [
            'labels' => [
                'name'              => _x( 'Speakers', 'taxonomy general name', 'ipv-production-system-pro' ),
                'singular_name'     => _x( 'Speaker', 'taxonomy singular name', 'ipv-production-system-pro' ),
                'search_items'      => __( 'Search Speakers', 'ipv-production-system-pro' ),
                'all_items'         => __( 'All Speakers', 'ipv-production-system-pro' ),
                'edit_item'         => __( 'Edit Speaker', 'ipv-production-system-pro' ),
                'update_item'       => __( 'Update Speaker', 'ipv-production-system-pro' ),
                'add_new_item'      => __( 'Add New Speaker', 'ipv-production-system-pro' ),
                'new_item_name'     => __( 'New Speaker Name', 'ipv-production-system-pro' ),
                'menu_name'         => __( 'Speakers', 'ipv-production-system-pro' ),
            ],
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => true, // WordPress gestisce automaticamente
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'speaker' ],
            'show_in_rest'      => true,
        ] );

        // Register standard post_tag taxonomy for ipv_video
        register_taxonomy_for_object_type( 'post_tag', self::POST_TYPE );
        // Il submenu Tags viene aggiunto manualmente in add_taxonomy_submenus()

        // Keep video_category for backward compatibility (hidden)
        register_taxonomy( 'video_category', self::POST_TYPE, [
            'labels' => [
                'name'              => __( 'Legacy Categories', 'ipv-production-system-pro' ),
                'singular_name'     => __( 'Legacy Category', 'ipv-production-system-pro' ),
                'menu_name'         => __( 'Legacy', 'ipv-production-system-pro' ),
            ],
            'hierarchical'      => true,
            'show_ui'           => false,
            'show_admin_column' => false,
            'query_var'         => false,
            'show_in_rest'      => true,
        ] );
    }

    /**
     * Add Meta Boxes
     */
    public static function add_meta_boxes() {
        // YouTube Data
        add_meta_box(
            'ipv_youtube_data',
            '<span class="dashicons dashicons-youtube" style="color:#ff0000;"></span> ' . __( 'YouTube Data', 'ipv-production-system-pro' ),
            [ __CLASS__, 'render_youtube_meta_box' ],
            self::POST_TYPE,
            'normal',
            'high'
        );

        // Transcription
        add_meta_box(
            'ipv_transcript',
            '<span class="dashicons dashicons-text-page"></span> ' . __( 'Transcription', 'ipv-production-system-pro' ),
            [ __CLASS__, 'render_transcript_meta_box' ],
            self::POST_TYPE,
            'normal',
            'default'
        );

        // Statistics
        add_meta_box(
            'ipv_stats',
            '<span class="dashicons dashicons-chart-bar"></span> ' . __( 'YouTube Statistics', 'ipv-production-system-pro' ),
            [ __CLASS__, 'render_stats_meta_box' ],
            self::POST_TYPE,
            'side',
            'default'
        );

        // v10.0.22 - Extraction Flags
        add_meta_box(
            'ipv_extraction_flags',
            '<span class="dashicons dashicons-tag"></span> ' . __( 'AI Extraction', 'ipv-production-system-pro' ),
            [ __CLASS__, 'render_extraction_flags_meta_box' ],
            self::POST_TYPE,
            'side',
            'default'
        );

        // Actions
        add_meta_box(
            'ipv_actions',
            '<span class="dashicons dashicons-admin-tools"></span> ' . __( 'Actions', 'ipv-production-system-pro' ),
            [ __CLASS__, 'render_actions_meta_box' ],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    /**
     * Renderizza la Meta Box Dati YouTube
     */
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

    /**
     * Renderizza la Meta Box Trascrizione
     */
    public static function render_transcript_meta_box( $post ) {
        $transcript = get_post_meta( $post->ID, '_ipv_transcript', true );
        $word_count = $transcript ? str_word_count( $transcript ) : 0;
        $char_count = $transcript ? mb_strlen( $transcript ) : 0;
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <p style="margin:0;">
                <strong>Parole:</strong> <?php echo number_format_i18n( $word_count ); ?> |
                <strong>Caratteri:</strong> <?php echo number_format_i18n( $char_count ); ?>
            </p>
            <?php if ( ! empty( $transcript ) ) : ?>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=ipv_download_transcript&post_id=' . $post->ID ), 'ipv_download_transcript_' . $post->ID ) ); ?>" class="button button-secondary button-small">
                    <span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
                    Download TXT
                </a>
            <?php endif; ?>
        </div>
        <textarea name="ipv_transcript" rows="15" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $transcript ); ?></textarea>
        <p class="description">
            La trascrizione viene generata automaticamente. Puoi modificarla manualmente se necessario.
        </p>
        <?php
    }

    /**
     * Renderizza la Meta Box Statistiche
     */
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

    /**
     * Renderizza la Meta Box Azioni
     */
    /**
     * v10.0.22 - Renderizza la Meta Box Extraction Flags
     */
    public static function render_extraction_flags_meta_box( $post ) {
        $extract_tags       = get_post_meta( $post->ID, '_ipv_extract_tags', true );
        $extract_categories = get_post_meta( $post->ID, '_ipv_extract_categories', true );
        $extract_speakers   = get_post_meta( $post->ID, '_ipv_extract_speakers', true );
        
        // Default globali per riferimento
        $global_tags       = get_option( 'ipv_extract_tags', '1' ) === '1';
        $global_categories = get_option( 'ipv_extract_categories', '1' ) === '1';
        $global_speakers   = get_option( 'ipv_extract_speakers', '1' ) === '1';
        ?>
        <style>
            .ipv-extract-row { margin-bottom: 12px; }
            .ipv-extract-select { width: 100%; }
            .ipv-extract-label { font-weight: 500; margin-bottom: 4px; display: block; }
            .ipv-extract-help { font-size: 11px; color: #666; margin-top: 2px; }
            .ipv-extract-default { color: #0073aa; }
        </style>
        
        <p class="description" style="margin-bottom: 12px;">
            <?php esc_html_e( 'Override global settings for this video:', 'ipv-production-system-pro' ); ?>
        </p>
        
        <div class="ipv-extract-row">
            <label class="ipv-extract-label">
                <span class="dashicons dashicons-tag" style="font-size: 14px; vertical-align: middle;"></span>
                <?php esc_html_e( 'Extract Tags', 'ipv-production-system-pro' ); ?>
            </label>
            <select name="ipv_extract_tags" class="ipv-extract-select">
                <option value="" <?php selected( $extract_tags, '' ); ?>>
                    <?php printf( 
                        esc_html__( 'Use global (%s)', 'ipv-production-system-pro' ),
                        $global_tags ? '✓ ON' : '✗ OFF'
                    ); ?>
                </option>
                <option value="yes" <?php selected( $extract_tags, 'yes' ); ?>>
                    <?php esc_html_e( '✓ Yes - Extract', 'ipv-production-system-pro' ); ?>
                </option>
                <option value="no" <?php selected( $extract_tags, 'no' ); ?>>
                    <?php esc_html_e( '✗ No - Skip', 'ipv-production-system-pro' ); ?>
                </option>
            </select>
            <div class="ipv-extract-help"><?php esc_html_e( 'Hashtags → WP Tags', 'ipv-production-system-pro' ); ?></div>
        </div>
        
        <div class="ipv-extract-row">
            <label class="ipv-extract-label">
                <span class="dashicons dashicons-category" style="font-size: 14px; vertical-align: middle;"></span>
                <?php esc_html_e( 'Extract Categories', 'ipv-production-system-pro' ); ?>
            </label>
            <select name="ipv_extract_categories" class="ipv-extract-select">
                <option value="" <?php selected( $extract_categories, '' ); ?>>
                    <?php printf( 
                        esc_html__( 'Use global (%s)', 'ipv-production-system-pro' ),
                        $global_categories ? '✓ ON' : '✗ OFF'
                    ); ?>
                </option>
                <option value="yes" <?php selected( $extract_categories, 'yes' ); ?>>
                    <?php esc_html_e( '✓ Yes - Extract', 'ipv-production-system-pro' ); ?>
                </option>
                <option value="no" <?php selected( $extract_categories, 'no' ); ?>>
                    <?php esc_html_e( '✗ No - Skip', 'ipv-production-system-pro' ); ?>
                </option>
            </select>
            <div class="ipv-extract-help"><?php esc_html_e( 'Topics → Video Categories', 'ipv-production-system-pro' ); ?></div>
        </div>
        
        <div class="ipv-extract-row">
            <label class="ipv-extract-label">
                <span class="dashicons dashicons-admin-users" style="font-size: 14px; vertical-align: middle;"></span>
                <?php esc_html_e( 'Extract Speakers', 'ipv-production-system-pro' ); ?>
            </label>
            <select name="ipv_extract_speakers" class="ipv-extract-select">
                <option value="" <?php selected( $extract_speakers, '' ); ?>>
                    <?php printf( 
                        esc_html__( 'Use global (%s)', 'ipv-production-system-pro' ),
                        $global_speakers ? '✓ ON' : '✗ OFF'
                    ); ?>
                </option>
                <option value="yes" <?php selected( $extract_speakers, 'yes' ); ?>>
                    <?php esc_html_e( '✓ Yes - Extract', 'ipv-production-system-pro' ); ?>
                </option>
                <option value="no" <?php selected( $extract_speakers, 'no' ); ?>>
                    <?php esc_html_e( '✗ No - Skip', 'ipv-production-system-pro' ); ?>
                </option>
            </select>
            <div class="ipv-extract-help"><?php esc_html_e( 'Guests → Speakers Taxonomy', 'ipv-production-system-pro' ); ?></div>
        </div>
        
        <p class="description" style="margin-top: 12px; font-style: italic;">
            <?php esc_html_e( 'Applied when AI description is generated.', 'ipv-production-system-pro' ); ?>
        </p>
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

    /**
     * Salva le Meta Boxes
     */
    public static function save_meta_boxes( $post_id, $post ) {
        // Verifica nonce
        if ( ! isset( $_POST['ipv_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ipv_meta_nonce'], 'ipv_save_meta' ) ) {
            return;
        }

        // Verifica autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Verifica permessi
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Salva Video ID
        if ( isset( $_POST['ipv_video_id'] ) ) {
            $video_id = sanitize_text_field( wp_unslash( $_POST['ipv_video_id'] ) );
            update_post_meta( $post_id, '_ipv_video_id', $video_id );

            // Auto-genera URL se mancante
            if ( $video_id && empty( $_POST['ipv_youtube_url'] ) ) {
                update_post_meta( $post_id, '_ipv_youtube_url', 'https://www.youtube.com/watch?v=' . $video_id );
            }
        }

        // Salva URL YouTube
        if ( isset( $_POST['ipv_youtube_url'] ) ) {
            update_post_meta( $post_id, '_ipv_youtube_url', esc_url_raw( wp_unslash( $_POST['ipv_youtube_url'] ) ) );
        }

        // Salva Trascrizione
        if ( isset( $_POST['ipv_transcript'] ) ) {
            update_post_meta( $post_id, '_ipv_transcript', sanitize_textarea_field( wp_unslash( $_POST['ipv_transcript'] ) ) );
        }

        // v10.0.22 - Salva Extraction Flags
        $extraction_flags = [ 'ipv_extract_tags', 'ipv_extract_categories', 'ipv_extract_speakers' ];
        foreach ( $extraction_flags as $flag ) {
            if ( isset( $_POST[ $flag ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $flag ] ) );
                // Salva solo se non vuoto (vuoto = usa default globale)
                if ( in_array( $value, [ 'yes', 'no' ], true ) ) {
                    update_post_meta( $post_id, '_' . $flag, $value );
                } else {
                    delete_post_meta( $post_id, '_' . $flag );
                }
            }
        }
    }

    /**
     * Definisce le colonne admin personalizzate
     */
    public static function admin_columns( $columns ) {
        $new_columns = [];

        foreach ( $columns as $key => $value ) {
            if ( $key === 'title' ) {
                $new_columns['ipv_thumbnail'] = 'Anteprima';
            }
            $new_columns[ $key ] = $value;

            if ( $key === 'title' ) {
                $new_columns['ipv_duration']  = 'Durata';
                $new_columns['ipv_views']     = 'Views';
                $new_columns['ipv_status']    = 'Stato';
            }
        }

        return $new_columns;
    }

    /**
     * Popola il contenuto delle colonne admin
     */
    public static function admin_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'ipv_thumbnail':
                $thumbnail = get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true );
                $video_id  = get_post_meta( $post_id, '_ipv_video_id', true );

                if ( $thumbnail ) {
                    printf(
                        '<a href="https://www.youtube.com/watch?v=%s" target="_blank"><img src="%s" width="80" style="border-radius:4px;"></a>',
                        esc_attr( $video_id ),
                        esc_url( $thumbnail )
                    );
                } else {
                    echo '<span class="dashicons dashicons-format-video" style="font-size:40px;color:#ccc;"></span>';
                }
                break;

            case 'ipv_duration':
                $duration = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
                echo $duration ? '<code>' . esc_html( $duration ) . '</code>' : '—';
                break;

            case 'ipv_views':
                $views = get_post_meta( $post_id, '_ipv_yt_view_count', true );
                if ( $views ) {
                    $formatted = $views >= 1000000 ? number_format( $views / 1000000, 1 ) . 'M' : ( $views >= 1000 ? number_format( $views / 1000, 1 ) . 'K' : number_format_i18n( $views ) );
                    echo '<span style="font-weight:600;">' . esc_html( $formatted ) . '</span>';
                } else {
                    echo '—';
                }
                break;

            case 'ipv_status':
                $has_transcript = get_post_meta( $post_id, '_ipv_transcript', true );
                $has_ai_desc    = get_post_meta( $post_id, '_ipv_ai_description', true );

                echo '<div style="display:flex;gap:5px;flex-wrap:wrap;">';

                if ( $has_transcript ) {
                    echo '<span style="background:#d4edda;color:#155724;padding:2px 6px;border-radius:3px;font-size:11px;">Trascritto</span>';
                } else {
                    echo '<span style="background:#fff3cd;color:#856404;padding:2px 6px;border-radius:3px;font-size:11px;">No Trascr.</span>';
                }

                if ( $has_ai_desc ) {
                    echo '<span style="background:#cce5ff;color:#004085;padding:2px 6px;border-radius:3px;font-size:11px;">AI</span>';
                }

                echo '</div>';
                break;
        }
    }

    /**
     * Definisce le colonne ordinabili
     */
    public static function sortable_columns( $columns ) {
        $columns['ipv_views']    = 'ipv_views';
        $columns['ipv_duration'] = 'ipv_duration';
        return $columns;
    }

    /**
     * Gestisce l'ordinamento per colonne custom
     */
    public static function sort_by_custom_column( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( $orderby === 'ipv_views' ) {
            $query->set( 'meta_key', '_ipv_yt_view_count' );
            $query->set( 'orderby', 'meta_value_num' );
        }

        if ( $orderby === 'ipv_duration' ) {
            $query->set( 'meta_key', '_ipv_yt_duration_seconds' );
            $query->set( 'orderby', 'meta_value_num' );
        }
    }
}

// Inizializza
IPV_Prod_CPT::init();

// AJAX Handlers per le azioni del CPT
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

    // Salva i metadati strutturali di YouTube
    IPV_Prod_YouTube_API::save_video_meta( $post_id, $video_data );

    // v10.1.0 - Allinea anche il titolo del CPT al titolo YouTube
    if ( ! empty( $video_data['title'] ) ) {
        wp_update_post( [
            'ID'         => $post_id,
            'post_title' => wp_strip_all_tags( $video_data['title'] ),
        ] );
    }

    wp_send_json_success( [ 'message' => 'Dati YouTube aggiornati con successo!' ] );
} );

add_action( 'wp_ajax_ipv_prod_generate_transcript', function() {
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

    // Update post content with proper formatting
    wp_update_post( [
        'ID'           => $post_id,
        'post_content' => wpautop( $desc ), // ✅ Preserva a capo!
    ] );
    update_post_meta( $post_id, '_ipv_ai_description', $desc );

    wp_send_json_success( [ 'message' => 'Descrizione AI rigenerata con successo!' ] );
} );

/**
 * AJAX Handler: Download Transcript
 * Permette di scaricare la trascrizione come file .txt
 */
add_action( 'wp_ajax_ipv_download_transcript', function() {
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

    // Verifica nonce
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ipv_download_transcript_' . $post_id ) ) {
        wp_die( 'Unauthorized: Invalid nonce' );
    }

    // Verifica permessi
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Unauthorized: Insufficient permissions' );
    }

    // Verifica che sia un video IPV
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'ipv_video' ) {
        wp_die( 'Invalid post type' );
    }

    // Ottieni trascrizione
    $transcript = get_post_meta( $post_id, '_ipv_transcript', true );
    if ( empty( $transcript ) ) {
        wp_die( 'Transcript not found' );
    }

    // Prepara nome file sicuro
    $video_title = sanitize_title( get_the_title( $post_id ) );
    $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
    $filename = $video_id ? "transcript-{$video_id}-{$video_title}.txt" : "transcript-{$video_title}.txt";

    // Invia headers per download
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $transcript ) );
    header( 'Cache-Control: no-cache, must-revalidate' );
    header( 'Expires: 0' );

    // Output trascrizione
    echo $transcript;
    exit;
} );
