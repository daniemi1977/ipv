<?php
/**
 * Video Wall con filtri avanzati per IPV Production System Pro v5
 *
 * Funzionalità:
 * - Filtri per Anno, Relatore, Argomento
 * - Ricerca testuale
 * - Paginazione AJAX
 * - Layout responsive
 * - Shortcode [ipv_video_wall]
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Wall {

    /**
     * Inizializza
     */
    public static function init() {
        add_shortcode( 'ipv_video_wall', [ __CLASS__, 'render_shortcode' ] );
    }

    /**
     * Render shortcode [ipv_video_wall]
     *
     * Parametri:
     * - per_page: Video per pagina (default: dalle impostazioni o 12)
     * - layout: grid|list (default: dalle impostazioni o grid)
     * - columns: 2|3|4 (default: dalle impostazioni o 3)
     * - show_filters: yes|no (default: dalle impostazioni o yes)
     */
    public static function render_shortcode( $atts ) {
        // Usa le impostazioni salvate come default
        $defaults = [
            'per_page'     => get_option( 'ipv_wall_per_page', 12 ),
            'layout'       => get_option( 'ipv_wall_layout', 'grid' ),
            'columns'      => get_option( 'ipv_wall_columns', 3 ),
            'show_filters' => get_option( 'ipv_wall_show_filters', 'yes' ),
        ];

        $atts = shortcode_atts( $defaults, $atts, 'ipv_video_wall' );

        ob_start();
        self::render_video_wall( $atts );
        return ob_get_clean();
    }

    /**
     * Render video wall HTML
     */
    private static function render_video_wall( $atts ) {
        $show_filters = $atts['show_filters'] === 'yes';
        $layout       = $atts['layout'];
        $columns      = intval( $atts['columns'] );
        $per_page     = intval( $atts['per_page'] );

        // Ottieni tassonomie per filtri
        $anni      = IPV_Prod_Taxonomies::get_all_anni();
        $relatori  = IPV_Prod_Taxonomies::get_all_relatori();
        $argomenti = IPV_Prod_Taxonomies::get_all_argomenti();

        ?>
        <div class="ipv-video-wall" data-layout="<?php echo esc_attr( $layout ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>">

            <?php if ( $show_filters ) : ?>
            <!-- Filtri -->
            <div class="ipv-wall-filters">
                <div class="ipv-filters-row">
                    <!-- Ricerca testuale -->
                    <div class="ipv-filter-item ipv-filter-search">
                        <label for="ipv-search">🔍 Cerca</label>
                        <input type="text" id="ipv-search" class="ipv-filter-input" placeholder="Cerca video..." />
                    </div>

                    <!-- Filtro Anno -->
                    <?php if ( ! empty( $anni ) && ! is_wp_error( $anni ) ) : ?>
                    <div class="ipv-filter-item">
                        <label for="ipv-filter-anno">📅 Anno</label>
                        <select id="ipv-filter-anno" class="ipv-filter-select">
                            <option value="">Tutti gli anni</option>
                            <?php foreach ( $anni as $anno ) : ?>
                                <option value="<?php echo esc_attr( $anno->slug ); ?>">
                                    <?php echo esc_html( $anno->name ); ?> (<?php echo intval( $anno->count ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Filtro Relatore -->
                    <?php if ( ! empty( $relatori ) && ! is_wp_error( $relatori ) ) : ?>
                    <div class="ipv-filter-item">
                        <label for="ipv-filter-relatore">🎙️ Relatore</label>
                        <select id="ipv-filter-relatore" class="ipv-filter-select">
                            <option value="">Tutti i relatori</option>
                            <?php foreach ( $relatori as $relatore ) : ?>
                                <option value="<?php echo esc_attr( $relatore->slug ); ?>">
                                    <?php echo esc_html( $relatore->name ); ?> (<?php echo intval( $relatore->count ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Filtro Argomento -->
                    <?php if ( ! empty( $argomenti ) && ! is_wp_error( $argomenti ) ) : ?>
                    <div class="ipv-filter-item">
                        <label for="ipv-filter-argomento">📌 Argomento</label>
                        <select id="ipv-filter-argomento" class="ipv-filter-select">
                            <option value="">Tutti gli argomenti</option>
                            <?php foreach ( $argomenti as $argomento ) : ?>
                                <option value="<?php echo esc_attr( $argomento->slug ); ?>">
                                    <?php echo esc_html( $argomento->name ); ?> (<?php echo intval( $argomento->count ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Info risultati -->
                <div class="ipv-filters-info">
                    <span id="ipv-results-count">Caricamento...</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Loading Spinner -->
            <div class="ipv-loading" style="display: none;">
                <div class="ipv-spinner"></div>
                <p>Caricamento video...</p>
            </div>

            <!-- Grid Video -->
            <div class="ipv-video-grid ipv-layout-<?php echo esc_attr( $layout ); ?> ipv-columns-<?php echo esc_attr( $columns ); ?>" id="ipv-video-grid">
                <!-- Popolato via AJAX -->
            </div>

            <!-- Paginazione -->
            <div class="ipv-pagination" id="ipv-pagination">
                <!-- Popolato via AJAX -->
            </div>

        </div>
        <?php
    }

    /**
     * AJAX: Carica video filtrati
     */
    public static function ajax_load_videos() {
        check_ajax_referer( 'ipv_video_wall', 'nonce' );

        $page       = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page   = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 12;
        $search     = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $anno       = isset( $_POST['anno'] ) ? sanitize_text_field( $_POST['anno'] ) : '';
        $relatore   = isset( $_POST['relatore'] ) ? sanitize_text_field( $_POST['relatore'] ) : '';
        $argomento  = isset( $_POST['argomento'] ) ? sanitize_text_field( $_POST['argomento'] ) : '';
        $layout     = isset( $_POST['layout'] ) ? sanitize_text_field( $_POST['layout'] ) : 'grid';
        $columns    = isset( $_POST['columns'] ) ? intval( $_POST['columns'] ) : 3;

        // Build query
        $args = [
            'post_type'      => 'video_ipv',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_ipv_yt_duration_seconds',
                    'value'   => 300,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        // Ricerca testuale
        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        // Filtri tassonomia
        $tax_query = [];

        if ( ! empty( $anno ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_anno',
                'field'    => 'slug',
                'terms'    => $anno,
            ];
        }

        if ( ! empty( $relatore ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_relatore',
                'field'    => 'slug',
                'terms'    => $relatore,
            ];
        }

        if ( ! empty( $argomento ) ) {
            $tax_query[] = [
                'taxonomy' => 'ipv_argomento',
                'field'    => 'slug',
                'terms'    => $argomento,
            ];
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // NOTA: meta_query per durata minima è già impostato sopra in $args

        // Query
        $query = new WP_Query( $args );

        // Build HTML
        $html = '';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $html .= self::render_video_card( $layout );
            }
        } else {
            $html = '<div class="ipv-no-results"><p>😕 Nessun video trovato con i filtri selezionati.</p></div>';
        }

        // Paginazione
        $pagination_html = self::render_pagination( $query->max_num_pages, $page );

        // Informazioni risultati
        $total      = $query->found_posts;
        $from       = ( ( $page - 1 ) * $per_page ) + 1;
        $to         = min( $page * $per_page, $total );
        $results_info = sprintf( 'Mostrando %d-%d di %d video', $from, $to, $total );

        wp_reset_postdata();

        wp_send_json_success( [
            'html'         => $html,
            'pagination'   => $pagination_html,
            'results_info' => $results_info,
            'total'        => $total,
            'pages'        => $query->max_num_pages,
        ] );
    }

    /**
     * Render singola video card
     */
    private static function render_video_card( $layout = 'grid' ) {
        $post_id      = get_the_ID();
        $video_id     = get_post_meta( $post_id, '_ipv_video_id', true );
        $thumbnail    = get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true );
        $duration     = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        $views        = get_post_meta( $post_id, '_ipv_yt_view_count', true );
        $published    = get_post_meta( $post_id, '_ipv_yt_published_at', true );
        $youtube_url  = get_post_meta( $post_id, '_ipv_youtube_url', true );

        // Ottieni tassonomie
        $relatori     = get_the_terms( $post_id, 'ipv_relatore' );
        $argomenti    = get_the_terms( $post_id, 'ipv_argomento' );
        $anno         = get_the_terms( $post_id, 'ipv_anno' );

        // Fallback thumbnail
        if ( empty( $thumbnail ) && has_post_thumbnail() ) {
            $thumbnail = get_the_post_thumbnail_url( $post_id, 'large' );
        }
        if ( empty( $thumbnail ) ) {
            $thumbnail = 'https://via.placeholder.com/640x360/333/fff?text=Video';
        }

        ob_start();
        ?>
        <div class="ipv-video-card">
            <div class="ipv-video-thumb">
                <?php
                // Featured image -> YouTube URL, Titolo -> CPT
                $thumb_url = ! empty( $youtube_url ) ? $youtube_url : get_permalink();
                $thumb_target = ! empty( $youtube_url ) ? '_blank' : '_self';
                $thumb_rel = ! empty( $youtube_url ) ? 'noopener noreferrer' : '';
                ?>
                <a href="<?php echo esc_url( $thumb_url ); ?>"
                   target="<?php echo esc_attr( $thumb_target ); ?>"
                   <?php if ( $thumb_rel ) : ?>rel="<?php echo esc_attr( $thumb_rel ); ?>"<?php endif; ?>
                   title="<?php echo esc_attr( get_the_title() ); ?>">
                    <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
                    <?php if ( $duration ) : ?>
                        <span class="ipv-video-duration"><?php echo esc_html( $duration ); ?></span>
                    <?php endif; ?>
                    <div class="ipv-video-overlay">
                        <span class="ipv-play-btn">▶</span>
                    </div>
                </a>
            </div>
            <div class="ipv-video-info">
                <h3 class="ipv-video-title">
                    <a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
                </h3>

                <div class="ipv-video-meta">
                    <?php if ( $published ) : ?>
                        <span class="ipv-meta-item">
                            📅 <?php echo esc_html( date_i18n( 'd M Y', strtotime( $published ) ) ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $views ) : ?>
                        <span class="ipv-meta-item">
                            👁️ <?php echo esc_html( number_format_i18n( $views ) ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Tassonomie -->
                <div class="ipv-video-tax">
                    <?php if ( ! empty( $relatori ) && ! is_wp_error( $relatori ) ) : ?>
                        <div class="ipv-tax-group">
                            <?php foreach ( array_slice( $relatori, 0, 2 ) as $rel ) : ?>
                                <span class="ipv-tax-tag ipv-tax-relatore">🎙️ <?php echo esc_html( $rel->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $argomenti ) && ! is_wp_error( $argomenti ) ) : ?>
                        <div class="ipv-tax-group">
                            <?php foreach ( array_slice( $argomenti, 0, 3 ) as $arg ) : ?>
                                <span class="ipv-tax-tag ipv-tax-argomento">📌 <?php echo esc_html( $arg->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( $youtube_url ) : ?>
                    <div class="ipv-video-actions">
                        <a href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" class="ipv-btn ipv-btn-youtube" rel="noopener">
                            ▶️ Guarda su YouTube
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render paginazione
     */
    private static function render_pagination( $max_pages, $current_page ) {
        if ( $max_pages <= 1 ) {
            return '';
        }

        $html = '<div class="ipv-pagination-inner">';

        // Previous
        if ( $current_page > 1 ) {
            $html .= '<button class="ipv-page-btn ipv-page-prev" data-page="' . ( $current_page - 1 ) . '">« Precedente</button>';
        }

        // Numeri pagina
        for ( $i = 1; $i <= $max_pages; $i++ ) {
            // Mostra solo alcune pagine (1, 2, 3 ... current-1, current, current+1 ... last-2, last-1, last)
            if ( $i === 1 || $i === $max_pages || ( $i >= $current_page - 1 && $i <= $current_page + 1 ) ) {
                $active = ( $i === $current_page ) ? ' ipv-page-active' : '';
                $html .= '<button class="ipv-page-btn' . $active . '" data-page="' . $i . '">' . $i . '</button>';
            } elseif ( $i === $current_page - 2 || $i === $current_page + 2 ) {
                $html .= '<span class="ipv-page-dots">...</span>';
            }
        }

        // Next
        if ( $current_page < $max_pages ) {
            $html .= '<button class="ipv-page-btn ipv-page-next" data-page="' . ( $current_page + 1 ) . '">Successivo »</button>';
        }

        $html .= '</div>';

        return $html;
    }
}

// Inizializza
IPV_Prod_Video_Wall::init();
