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
                <div class="ipv-video-grid ipv-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
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

            <button type="button" class="ipv-filter-button" id="ipv-apply-filters">
                <i class="dashicons dashicons-search"></i> Filtra
            </button>
        </div>
        <?php
    }

    private static function render_videos( $atts, $paged = 1 ) {
        $args = [
            'post_type'      => 'video_ipv',
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
        $yt_id = get_post_meta( $post_id, '_ipv_yt_id', true );
        $thumbnail = get_post_meta( $post_id, '_ipv_yt_thumbnail', true );
        $duration = get_post_meta( $post_id, '_ipv_yt_duration', true );
        $views = get_post_meta( $post_id, '_ipv_yt_views', true );
        $publish_date = get_post_meta( $post_id, '_ipv_yt_published', true );

        if ( empty( $thumbnail ) && ! empty( $yt_id ) ) {
            $thumbnail = "https://img.youtube.com/vi/{$yt_id}/maxresdefault.jpg";
        }

        ?>
        <div class="ipv-video-card" data-video-id="<?php echo esc_attr( $post_id ); ?>">
            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="ipv-video-link">
                <div class="ipv-video-thumbnail">
                    <?php if ( $thumbnail ) : ?>
                        <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
                        <div class="ipv-play-overlay">
                            <div class="ipv-play-button">
                                <svg viewBox="0 0 68 48" width="68" height="48">
                                    <path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path>
                                    <path d="M 45,24 27,14 27,34" fill="#fff"></path>
                                </svg>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ( $duration ) : ?>
                        <span class="ipv-video-duration"><?php echo esc_html( $duration ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ipv-video-info">
                    <h3 class="ipv-video-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                    <div class="ipv-video-meta">
                        <?php if ( $views ) : ?>
                            <span class="ipv-video-views">
                                <i class="dashicons dashicons-visibility"></i>
                                <?php echo number_format_i18n( $views ); ?> visualizzazioni
                            </span>
                        <?php endif; ?>
                        <?php if ( $publish_date ) : ?>
                            <span class="ipv-video-date">
                                <i class="dashicons dashicons-calendar"></i>
                                <?php echo human_time_diff( strtotime( $publish_date ), current_time( 'timestamp' ) ); ?> fa
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php
    }

    private static function render_pagination( $atts, $query = null ) {
        if ( ! $query ) {
            $args = [
                'post_type'      => 'video_ipv',
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
        <div class="ipv-pagination-wrapper">
            <button class="ipv-page-btn ipv-page-prev" data-page="prev" disabled>
                <i class="dashicons dashicons-arrow-left-alt2"></i> Precedente
            </button>
            <span class="ipv-page-info">Pagina <span class="ipv-current-page">1</span> di <?php echo esc_html( $total_pages ); ?></span>
            <button class="ipv-page-btn ipv-page-next" data-page="next">
                Successiva <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
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
