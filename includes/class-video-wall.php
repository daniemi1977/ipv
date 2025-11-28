<?php
/**
 * Video Wall - Display video grid with filters and pagination
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Wall {

    public static function init() {
        add_shortcode( 'ipv_video_wall', [ __CLASS__, 'render_shortcode' ] );
        add_action( 'wp_ajax_ipv_load_videos', [ __CLASS__, 'ajax_load_videos' ] );
        add_action( 'wp_ajax_nopriv_ipv_load_videos', [ __CLASS__, 'ajax_load_videos' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'ipv-video-wall',
            IPV_PROD_PLUGIN_URL . 'assets/css/video-wall.css',
            [],
            IPV_PROD_VERSION
        );

        // Add custom colors from admin settings
        $accent_color = get_option( 'ipv_wall_accent_color', '#FB0F5A' );
        $card_bg      = get_option( 'ipv_wall_card_bg', '#F5F5F5' );
        $meta_bg      = get_option( 'ipv_wall_meta_bg', '#EAEAEA' );
        $text_color   = get_option( 'ipv_wall_text_color', '#555' );

        $custom_css = "
        :root {
            --ipv-accent-color: {$accent_color};
            --ipv-card-bg: {$card_bg};
            --ipv-meta-bg: {$meta_bg};
            --ipv-text-color: {$text_color};
        }
        ";

        wp_add_inline_style( 'ipv-video-wall', $custom_css );

        wp_enqueue_script(
            'ipv-video-wall',
            IPV_PROD_PLUGIN_URL . 'assets/js/video-wall.js',
            [ 'jquery' ],
            IPV_PROD_VERSION,
            true
        );

        wp_localize_script( 'ipv-video-wall', 'ipvVideoWall', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ipv_video_wall_nonce' ),
        ]);
    }

    public static function render_shortcode( $atts ) {
        $defaults = [
            'per_page'     => get_option( 'ipv_wall_per_page', 5 ),
            'layout'       => get_option( 'ipv_wall_layout', 'grid' ),
            'columns'      => get_option( 'ipv_wall_columns', 3 ),
            'show_filters' => get_option( 'ipv_wall_show_filters', 'yes' ),
            'categoria'    => '',
            'relatore'     => '',
        ];

        $atts = shortcode_atts( $defaults, $atts, 'ipv_video_wall' );

        ob_start();
        ?>
        <div class="ipv-video-wall-container" data-per-page="<?php echo esc_attr( $atts['per_page'] ); ?>">

            <?php if ( $atts['show_filters'] === 'yes' ) : ?>
                <div class="ipv-video-filters">
                    <?php self::render_filters(); ?>
                </div>
            <?php endif; ?>

            <div class="ipv-video-grid-wrapper">
                <div class="ipv-video-grid ipv-layout-<?php echo esc_attr( str_replace( '+', '-', $atts['layout'] ) ); ?> ipv-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
                    <?php self::render_videos( $atts ); ?>
                </div>
                <div class="ipv-video-loading" style="display:none;">
                    <div class="ipv-spinner"></div>
                    <p>Caricamento video...</p>
                </div>
            </div>

            <div class="ipv-video-pagination">
                <?php self::render_pagination( $atts ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_filters() {
        $categorie = get_terms([
            'taxonomy'   => 'ipv_categoria',
            'hide_empty' => true,
        ]);

        $relatori = get_terms([
            'taxonomy'   => 'ipv_relatore',
            'hide_empty' => true,
        ]);

        ?>
        <div class="ipv-filters-row">
            <div class="ipv-filter-group">
                <label for="ipv-filter-categoria">Categoria:</label>
                <select id="ipv-filter-categoria" class="ipv-filter-select">
                    <option value="">Tutte le categorie</option>
                    <?php foreach ( $categorie as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat->term_id ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ipv-filter-group">
                <label for="ipv-filter-relatore">Relatore:</label>
                <select id="ipv-filter-relatore" class="ipv-filter-select">
                    <option value="">Tutti i relatori</option>
                    <?php foreach ( $relatori as $rel ) : ?>
                        <option value="<?php echo esc_attr( $rel->term_id ); ?>">
                            <?php echo esc_html( $rel->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ipv-filter-group">
                <label for="ipv-filter-search">Cerca:</label>
                <input type="text" id="ipv-filter-search" class="ipv-filter-input" placeholder="Cerca nei video...">
            </div>
        </div>
        <?php
    }

    private static function render_videos( $atts, $paged = 1 ) {
        $args = [
            'post_type'      => 'ipv_video',
            'posts_per_page' => $atts['per_page'],
            'paged'          => $paged,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filter out YouTube Shorts (videos shorter than 60 seconds)
        $args['meta_query'] = [
            'relation' => 'OR',
            [
                'key'     => '_ipv_yt_duration_seconds',
                'value'   => 60,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => '_ipv_yt_duration_seconds',
                'compare' => 'NOT EXISTS',
            ],
        ];

        // Taxonomy filters
        $tax_query = [];

        if ( ! empty( $atts['categoria'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_categoria',
                'field'    => 'term_id',
                'terms'    => $atts['categoria'],
            ];
        }

        if ( ! empty( $atts['relatore'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_relatore',
                'field'    => 'term_id',
                'terms'    => $atts['relatore'],
            ];
        }

        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                self::render_video_card( get_the_ID() );
            }
            wp_reset_postdata();
        } else {
            echo '<div class="ipv-no-videos"><p>Nessun video trovato.</p></div>';
        }

        return $query;
    }

    private static function render_video_card( $post_id ) {
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        $thumbnail = get_post_meta( $post_id, '_ipv_yt_thumbnail', true );
        $duration = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        $views = get_post_meta( $post_id, '_ipv_yt_views', true );
        $publish_date = get_post_meta( $post_id, '_ipv_yt_published', true );

        // Get categories
        $categories = get_the_terms( $post_id, 'ipv_categoria' );
        $cat_name = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';

        // Get speakers
        $speakers = get_the_terms( $post_id, 'ipv_relatore' );
        $speaker_name = ( $speakers && ! is_wp_error( $speakers ) ) ? $speakers[0]->name : '';

        // Try multiple sources for thumbnail
        if ( empty( $thumbnail ) && ! empty( $video_id ) ) {
            $thumbnail = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        }

        // Fallback to WordPress featured image
        if ( empty( $thumbnail ) ) {
            $thumbnail = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        // Last fallback to standard quality YouTube thumbnail
        if ( empty( $thumbnail ) && ! empty( $video_id ) ) {
            $thumbnail = "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";
        }

        // Format publish date
        $date_formatted = $publish_date ? date_i18n( 'd M Y', strtotime( $publish_date ) ) : get_the_date( 'd M Y', $post_id );

        ?>
        <article class="ipv-video-card" data-video-id="<?php echo esc_attr( $post_id ); ?>">
            <div class="ipv-post--inner">
                <!-- Featured Image -->
                <div class="ipv-post--featured">
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                        <?php if ( $thumbnail ) : ?>
                            <div class="ipv-cover-image" style="background-image: url('<?php echo esc_url( $thumbnail ); ?>');"></div>
                        <?php else : ?>
                            <div class="ipv-cover-image ipv-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <?php if ( $duration ) : ?>
                        <span class="ipv-video-duration"><?php echo esc_html( $duration ); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="ipv-post--infor">
                    <!-- Publish Date Badge (floating over image) -->
                    <div class="ipv-post--publish">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M8 2v3m8-3v3M3 9h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span><?php echo esc_html( $date_formatted ); ?></span>
                    </div>

                    <!-- Title -->
                    <h3 class="ipv-post--title">
                        <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                            <?php echo esc_html( get_the_title( $post_id ) ); ?>
                        </a>
                    </h3>

                    <!-- Meta -->
                    <div class="ipv-post--meta">
                        <?php if ( $cat_name ) : ?>
                            <span class="ipv-post-cat"><?php echo esc_html( $cat_name ); ?></span>
                        <?php endif; ?>
                        <?php if ( $speaker_name ) : ?>
                            <span class="ipv-post-author"><?php echo esc_html( $speaker_name ); ?></span>
                        <?php endif; ?>
                        <?php if ( $views ) : ?>
                            <span class="ipv-post-views"><?php echo number_format_i18n( $views ); ?> views</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php
    }

    private static function render_pagination( $atts, $query = null ) {
        if ( ! $query ) {
            $args = [
                'post_type'      => 'ipv_video',
                'posts_per_page' => $atts['per_page'],
                'post_status'    => 'publish',
            ];

            // Filter out YouTube Shorts
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => '_ipv_yt_duration_seconds',
                    'value'   => 60,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_ipv_yt_duration_seconds',
                    'compare' => 'NOT EXISTS',
                ],
            ];

            $query = new WP_Query( $args );
        }

        $total_pages = $query->max_num_pages;

        if ( $total_pages <= 1 ) {
            return;
        }

        ?>
        <div class="ipv-load-more-wrapper">
            <button class="ipv-load-more-btn" data-page="1" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
                <i class="dashicons dashicons-update"></i>
                <span class="ipv-load-more-text">Carica altri 5 video</span>
            </button>
            <div class="ipv-load-more-info">
                <span class="ipv-videos-loaded">5</span> di <span class="ipv-videos-total"><?php echo esc_html( $query->found_posts ); ?></span> video
            </div>
        </div>
        <?php
    }

    public static function ajax_load_videos() {
        check_ajax_referer( 'ipv_video_wall_nonce', 'nonce' );

        $paged = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 5;
        $categoria = isset( $_POST['categoria'] ) ? sanitize_text_field( $_POST['categoria'] ) : '';
        $relatore = isset( $_POST['relatore'] ) ? sanitize_text_field( $_POST['relatore'] ) : '';
        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

        $args = [
            'post_type'      => 'video_ipv',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filter out YouTube Shorts (videos shorter than 60 seconds)
        $args['meta_query'] = [
            'relation' => 'OR',
            [
                'key'     => '_ipv_yt_duration_seconds',
                'value'   => 60,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => '_ipv_yt_duration_seconds',
                'compare' => 'NOT EXISTS',
            ],
        ];

        // Search
        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        // Taxonomy filters
        $tax_query = [];

        if ( ! empty( $categoria ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_categoria',
                'field'    => 'term_id',
                'terms'    => absint( $categoria ),
            ];
        }

        if ( ! empty( $relatore ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_relatore',
                'field'    => 'term_id',
                'terms'    => absint( $relatore ),
            ];
        }

        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                self::render_video_card( get_the_ID() );
            }
            wp_reset_postdata();
        } else {
            echo '<div class="ipv-no-videos"><p>Nessun video trovato.</p></div>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'        => $html,
            'current_page' => $paged,
            'total_pages'  => $query->max_num_pages,
        ]);
    }
}

IPV_Prod_Video_Wall::init();
