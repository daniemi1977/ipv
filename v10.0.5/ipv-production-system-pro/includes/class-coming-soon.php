<?php
/**
 * IPV Production System Pro - Coming Soon / In Programma
 * 
 * Mostra i video programmati su YouTube (premiere) con durata 0
 * 
 * Shortcode: [ipv_coming_soon]
 * Parametri:
 *   - layout: list|cards|grid (default: list)
 *   - limit: numero massimo video (default: 5)
 *   - title: titolo sezione (default: "In Programma")
 *   - show_countdown: true|false (default: true)
 *
 * @package IPV_Production_System_Pro
 * @version 7.9.40
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Coming_Soon {

    public static function init() {
        add_shortcode( 'ipv_coming_soon', [ __CLASS__, 'render_shortcode' ] );
        add_shortcode( 'ipv_in_programma', [ __CLASS__, 'render_shortcode' ] ); // Alias italiano
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets() {
        // Usa lo stesso CSS del video wall + CSS aggiuntivo inline
        wp_enqueue_style( 'ipv-video-wall' );
    }

    /**
     * Render shortcode
     */
    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'layout'         => 'list',
            'limit'          => 5,
            'title'          => 'In Programma',
            'show_countdown' => 'true',
            'show_title'     => 'true',
        ], $atts, 'ipv_coming_soon' );

        // Query video programmati (durata 0)
        $args = [
            'post_type'      => 'ipv_video',
            'posts_per_page' => intval( $atts['limit'] ),
            'post_status'    => 'publish',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_ipv_yt_duration_seconds',
                    'value'   => 0,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_ipv_premiere_pending',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'ASC', // I più vicini prima
        ];

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return ''; // Nessun video in programma, non mostrare nulla
        }

        ob_start();
        ?>
        <style>
        .ipv-coming-soon {
            margin: 30px 0;
        }
        .ipv-coming-soon-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--ipv-accent-color, #FB0F5A);
        }
        .ipv-coming-soon-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .ipv-coming-soon-badge {
            background: linear-gradient(135deg, #FF6B6B, #FF0000);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Layout Lista */
        .ipv-coming-soon-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .ipv-coming-soon-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #FF0000;
        }
        .ipv-coming-soon-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .ipv-coming-soon-thumb {
            width: 160px;
            min-width: 160px;
            height: 90px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .ipv-coming-soon-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .ipv-coming-soon-thumb::after {
            content: '🔴 LIVE';
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(255,0,0,0.9);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
        }
        .ipv-coming-soon-content {
            flex: 1;
        }
        .ipv-coming-soon-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        .ipv-coming-soon-title a {
            color: inherit;
            text-decoration: none;
        }
        .ipv-coming-soon-title a:hover {
            color: var(--ipv-accent-color, #FB0F5A);
        }
        .ipv-coming-soon-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .ipv-coming-soon-date {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 0.9rem;
        }
        .ipv-coming-soon-date svg {
            width: 16px;
            height: 16px;
        }
        .ipv-coming-soon-countdown {
            display: flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .ipv-coming-soon-countdown.soon {
            background: linear-gradient(135deg, #FF6B6B, #FF0000);
            animation: pulse-badge 1.5s infinite;
        }

        /* Layout Cards */
        .ipv-coming-soon-cards {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .ipv-coming-soon-card {
            display: flex;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .ipv-coming-soon-card:hover {
            transform: scale(1.02);
        }
        .ipv-coming-soon-card .ipv-coming-soon-thumb {
            width: 300px;
            min-width: 300px;
            height: 170px;
        }
        .ipv-coming-soon-card .ipv-coming-soon-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .ipv-coming-soon-card .ipv-coming-soon-title {
            font-size: 1.3rem;
        }

        /* Layout Grid */
        .ipv-coming-soon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .ipv-coming-soon-grid-item {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .ipv-coming-soon-grid-item:hover {
            transform: translateY(-5px);
        }
        .ipv-coming-soon-grid-item .ipv-coming-soon-thumb {
            width: 100%;
            height: 160px;
            border-radius: 0;
        }
        .ipv-coming-soon-grid-item .ipv-coming-soon-content {
            padding: 15px;
        }
        .ipv-coming-soon-grid-item .ipv-coming-soon-title {
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ipv-coming-soon-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .ipv-coming-soon-thumb {
                width: 100%;
                height: 180px;
            }
            .ipv-coming-soon-card {
                flex-direction: column;
            }
            .ipv-coming-soon-card .ipv-coming-soon-thumb {
                width: 100%;
                height: 200px;
            }
        }
        </style>

        <div class="ipv-coming-soon">
            <?php if ( $atts['show_title'] === 'true' ) : ?>
            <div class="ipv-coming-soon-header">
                <span class="ipv-coming-soon-badge">🔴 LIVE</span>
                <h3><?php echo esc_html( $atts['title'] ); ?></h3>
            </div>
            <?php endif; ?>

            <?php
            switch ( $atts['layout'] ) {
                case 'cards':
                    self::render_cards( $query, $atts );
                    break;
                case 'grid':
                    self::render_grid( $query, $atts );
                    break;
                default:
                    self::render_list( $query, $atts );
                    break;
            }
            ?>
        </div>
        <?php

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Render layout lista
     */
    private static function render_list( $query, $atts ) {
        ?>
        <div class="ipv-coming-soon-list">
            <?php while ( $query->have_posts() ) : $query->the_post();
                $data = self::get_video_data( get_the_ID() );
            ?>
            <div class="ipv-coming-soon-item">
                <div class="ipv-coming-soon-thumb">
                    <?php if ( $data['thumbnail'] ) : ?>
                        <img src="<?php echo esc_url( $data['thumbnail'] ); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php elseif ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium' ); ?>
                    <?php endif; ?>
                </div>
                <div class="ipv-coming-soon-content">
                    <h4 class="ipv-coming-soon-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="ipv-coming-soon-meta">
                        <div class="ipv-coming-soon-date">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span><?php echo esc_html( $data['date_formatted'] ); ?></span>
                        </div>
                        <?php if ( $atts['show_countdown'] === 'true' && $data['countdown'] ) : ?>
                        <div class="ipv-coming-soon-countdown <?php echo $data['days_left'] <= 1 ? 'soon' : ''; ?>">
                            ⏱️ <?php echo esc_html( $data['countdown'] ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Render layout cards
     */
    private static function render_cards( $query, $atts ) {
        ?>
        <div class="ipv-coming-soon-cards">
            <?php while ( $query->have_posts() ) : $query->the_post();
                $data = self::get_video_data( get_the_ID() );
            ?>
            <div class="ipv-coming-soon-card">
                <div class="ipv-coming-soon-thumb">
                    <?php if ( $data['thumbnail'] ) : ?>
                        <img src="<?php echo esc_url( $data['thumbnail'] ); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php elseif ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large' ); ?>
                    <?php endif; ?>
                </div>
                <div class="ipv-coming-soon-content">
                    <h4 class="ipv-coming-soon-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="ipv-coming-soon-meta">
                        <div class="ipv-coming-soon-date">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span><?php echo esc_html( $data['date_formatted'] ); ?></span>
                        </div>
                        <?php if ( $atts['show_countdown'] === 'true' && $data['countdown'] ) : ?>
                        <div class="ipv-coming-soon-countdown <?php echo $data['days_left'] <= 1 ? 'soon' : ''; ?>">
                            ⏱️ <?php echo esc_html( $data['countdown'] ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Render layout grid
     */
    private static function render_grid( $query, $atts ) {
        ?>
        <div class="ipv-coming-soon-grid">
            <?php while ( $query->have_posts() ) : $query->the_post();
                $data = self::get_video_data( get_the_ID() );
            ?>
            <div class="ipv-coming-soon-grid-item">
                <div class="ipv-coming-soon-thumb">
                    <?php if ( $data['thumbnail'] ) : ?>
                        <img src="<?php echo esc_url( $data['thumbnail'] ); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php elseif ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium' ); ?>
                    <?php endif; ?>
                </div>
                <div class="ipv-coming-soon-content">
                    <h4 class="ipv-coming-soon-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="ipv-coming-soon-meta">
                        <div class="ipv-coming-soon-date">
                            📅 <?php echo esc_html( $data['date_formatted'] ); ?>
                        </div>
                        <?php if ( $atts['show_countdown'] === 'true' && $data['countdown'] ) : ?>
                        <div class="ipv-coming-soon-countdown <?php echo $data['days_left'] <= 1 ? 'soon' : ''; ?>">
                            ⏱️ <?php echo esc_html( $data['countdown'] ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Ottiene i dati del video
     */
    private static function get_video_data( $post_id ) {
        // Data YouTube programmata
        $yt_published = get_post_meta( $post_id, '_ipv_yt_published_at', true );
        $post_date = get_the_date( 'Y-m-d H:i:s', $post_id );
        
        // Usa la data YouTube se disponibile, altrimenti la data del post
        $target_date = $yt_published ? strtotime( $yt_published ) : strtotime( $post_date );
        $now = current_time( 'timestamp' );
        
        // Calcola countdown
        $diff = $target_date - $now;
        $days_left = floor( $diff / DAY_IN_SECONDS );
        $hours_left = floor( ( $diff % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
        
        $countdown = '';
        if ( $diff > 0 ) {
            if ( $days_left > 0 ) {
                $countdown = sprintf( 
                    _n( 'Manca %d giorno', 'Mancano %d giorni', $days_left, 'ipv-production' ), 
                    $days_left 
                );
            } elseif ( $hours_left > 0 ) {
                $countdown = sprintf( 
                    _n( 'Manca %d ora', 'Mancano %d ore', $hours_left, 'ipv-production' ), 
                    $hours_left 
                );
            } else {
                $countdown = 'Tra poco!';
            }
        }

        // Thumbnail
        $thumbnail = get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true );
        if ( empty( $thumbnail ) ) {
            $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
            if ( $video_id ) {
                $thumbnail = 'https://img.youtube.com/vi/' . $video_id . '/mqdefault.jpg';
            }
        }

        return [
            'thumbnail'      => $thumbnail,
            'date_formatted' => date_i18n( 'd M Y \a\l\l\e H:i', $target_date ),
            'countdown'      => $countdown,
            'days_left'      => $days_left,
        ];
    }
}
