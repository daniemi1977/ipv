<?php
/**
 * IPV Production System Pro - Shortcodes
 *
 * Universal shortcodes for theme compatibility
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Shortcodes {

    public static function init() {
        add_shortcode( 'ipv_video', [ __CLASS__, 'video_player_shortcode' ] );
        add_shortcode( 'ipv_grid', [ __CLASS__, 'video_grid_shortcode' ] );
        add_shortcode( 'ipv_search', [ __CLASS__, 'search_form_shortcode' ] );
        add_shortcode( 'ipv_stats', [ __CLASS__, 'stats_shortcode' ] );
    }

    /**
     * Video Player Shortcode
     * [ipv_video id="123" autoplay="no" controls="yes" width="100%"]
     */
    public static function video_player_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'id' => 0,
            'autoplay' => 'no',
            'controls' => 'yes',
            'mute' => 'no',
            'loop' => 'no',
            'width' => '100%',
            'aspect' => '16:9',
        ], $atts, 'ipv_video' );

        $post_id = absint( $atts['id'] );

        if ( ! $post_id ) {
            return '<p style="color: red;">Error: Video ID required. Usage: [ipv_video id="123"]</p>';
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'ipv_video' ) {
            return '<p style="color: red;">Error: Video not found.</p>';
        }

        // Get video data
        $video_source = get_post_meta( $post_id, '_ipv_video_source', true ) ?: 'youtube';
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( ! $video_id ) {
            return '<p style="color: red;">Error: Video ID not found in post meta.</p>';
        }

        // Build embed URL
        $autoplay = $atts['autoplay'] === 'yes' ? 1 : 0;
        $controls = $atts['controls'] === 'yes' ? 1 : 0;
        $mute = $atts['mute'] === 'yes' ? 1 : 0;
        $loop = $atts['loop'] === 'yes' ? 1 : 0;

        $embed_url = '';

        switch ( $video_source ) {
            case 'youtube':
                $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}&loop={$loop}";
                break;
            case 'vimeo':
                $embed_url = "https://player.vimeo.com/video/{$video_id}?autoplay={$autoplay}&controls={$controls}&muted={$mute}&loop={$loop}";
                break;
            case 'dailymotion':
                $embed_url = "https://www.dailymotion.com/embed/video/{$video_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}";
                break;
        }

        // Calculate aspect ratio padding
        $aspect_ratios = [
            '16:9' => '56.25%',
            '4:3' => '75%',
            '21:9' => '42.857%',
            '1:1' => '100%',
        ];
        $padding = $aspect_ratios[ $atts['aspect'] ] ?? '56.25%';

        ob_start();
        ?>
        <div class="ipv-shortcode-player" style="width: <?php echo esc_attr( $atts['width'] ); ?>; max-width: 100%;">
            <div style="position: relative; padding-bottom: <?php echo esc_attr( $padding ); ?>; height: 0; overflow: hidden;">
                <iframe
                    src="<?php echo esc_url( $embed_url ); ?>"
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Video Grid Shortcode
     * [ipv_grid count="6" columns="3" category="1,2" orderby="date" order="DESC"]
     */
    public static function video_grid_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'count' => 6,
            'columns' => 3,
            'category' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'show_title' => 'yes',
            'show_excerpt' => 'yes',
            'show_meta' => 'yes',
            'gap' => '20px',
        ], $atts, 'ipv_grid' );

        // Build query
        $args = [
            'post_type' => 'ipv_video',
            'posts_per_page' => absint( $atts['count'] ),
            'orderby' => $atts['orderby'],
            'order' => strtoupper( $atts['order'] ),
        ];

        // Category filter
        if ( ! empty( $atts['category'] ) ) {
            $category_ids = array_map( 'absint', explode( ',', $atts['category'] ) );
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ipv_categoria',
                    'field' => 'term_id',
                    'terms' => $category_ids,
                ],
            ];
        }

        // Order by views
        if ( $atts['orderby'] === 'views' ) {
            $args['meta_key'] = '_ipv_yt_view_count';
            $args['orderby'] = 'meta_value_num';
        }

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '<p>No videos found.</p>';
        }

        $columns = absint( $atts['columns'] );
        $gap = esc_attr( $atts['gap'] );

        ob_start();
        ?>
        <div class="ipv-shortcode-grid" style="display: grid; grid-template-columns: repeat(<?php echo $columns; ?>, 1fr); gap: <?php echo $gap; ?>;">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $video_id = get_the_ID();
                $thumbnail = get_post_meta( $video_id, '_ipv_yt_thumbnail_url', true );
                $duration = get_post_meta( $video_id, '_ipv_yt_duration_formatted', true );
                $views = get_post_meta( $video_id, '_ipv_yt_view_count', true );
                $permalink = get_permalink();
                ?>
                <div class="ipv-grid-item" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                    <a href="<?php echo esc_url( $permalink ); ?>" style="text-decoration: none; color: inherit; display: block;">
                        <?php if ( $thumbnail ) : ?>
                            <div style="position: relative; padding-bottom: 56.25%; background: #000;">
                                <img
                                    src="<?php echo esc_url( $thumbnail ); ?>"
                                    alt="<?php the_title_attribute(); ?>"
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                                >
                                <?php if ( $atts['show_meta'] === 'yes' && $duration ) : ?>
                                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                        <?php echo esc_html( $duration ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div style="padding: 15px;">
                            <?php if ( $atts['show_title'] === 'yes' ) : ?>
                                <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; line-height: 1.4; color: #333;">
                                    <?php the_title(); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $atts['show_excerpt'] === 'yes' ) : ?>
                                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666; line-height: 1.5;">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ( $atts['show_meta'] === 'yes' && $views ) : ?>
                                <div style="font-size: 13px; color: #999;">
                                    👁️ <?php echo number_format( $views ); ?> views
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <style>
            .ipv-grid-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }

            @media (max-width: 768px) {
                .ipv-shortcode-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }

            @media (max-width: 480px) {
                .ipv-shortcode-grid {
                    grid-template-columns: repeat(1, 1fr) !important;
                }
            }
        </style>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Search Form Shortcode
     * [ipv_search placeholder="Search videos..." button_text="Search"]
     */
    public static function search_form_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'placeholder' => 'Search videos...',
            'button_text' => 'Search',
            'show_filters' => 'yes',
            'show_sorting' => 'yes',
        ], $atts, 'ipv_search' );

        ob_start();
        ?>
        <div class="ipv-shortcode-search">
            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                <input type="hidden" name="post_type" value="ipv_video">

                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <input
                        type="text"
                        name="s"
                        value="<?php echo get_search_query(); ?>"
                        placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                        style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                    >
                    <button type="submit" style="padding: 12px 30px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600;">
                        🔍 <?php echo esc_html( $atts['button_text'] ); ?>
                    </button>
                </div>

                <?php if ( $atts['show_filters'] === 'yes' ) : ?>
                    <?php
                    $categories = get_terms( [
                        'taxonomy' => 'ipv_categoria',
                        'hide_empty' => true,
                    ] );
                    $relatori = get_terms( [
                        'taxonomy' => 'ipv_relatore',
                        'hide_empty' => true,
                    ] );
                    ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <?php if ( ! empty( $categories ) ) : ?>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #666;">Category</label>
                                <select name="ipv_categoria" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                    <option value="">All Categories</option>
                                    <?php foreach ( $categories as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( get_query_var( 'ipv_categoria' ), $cat->slug ); ?>>
                                            <?php echo esc_html( $cat->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $relatori ) && $atts['show_sorting'] === 'yes' ) : ?>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #666;">Speaker</label>
                                <select name="ipv_relatore" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                    <option value="">All Speakers</option>
                                    <?php foreach ( $relatori as $rel ) : ?>
                                        <option value="<?php echo esc_attr( $rel->slug ); ?>" <?php selected( get_query_var( 'ipv_relatore' ), $rel->slug ); ?>>
                                            <?php echo esc_html( $rel->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ( $atts['show_sorting'] === 'yes' ) : ?>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: #666;">Sort By</label>
                                <select name="orderby" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                    <option value="date">Newest First</option>
                                    <option value="title">Title A-Z</option>
                                    <option value="views">Most Viewed</option>
                                    <option value="rand">Random</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Stats Shortcode
     * [ipv_stats show="total,views,categories,recent"]
     */
    public static function stats_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'show' => 'total,views,categories,recent',
            'style' => 'cards', // cards, list, inline
        ], $atts, 'ipv_stats' );

        $show_items = array_map( 'trim', explode( ',', $atts['show'] ) );

        // Calculate stats
        $stats = [];

        if ( in_array( 'total', $show_items ) ) {
            $total_videos = wp_count_posts( 'ipv_video' )->publish;
            $stats['total'] = [
                'label' => 'Total Videos',
                'value' => number_format( $total_videos ),
                'icon' => '🎬',
            ];
        }

        if ( in_array( 'views', $show_items ) ) {
            global $wpdb;
            $total_views = $wpdb->get_var( "
                SELECT SUM(CAST(meta_value AS UNSIGNED))
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_ipv_yt_view_count'
            " );
            $stats['views'] = [
                'label' => 'Total Views',
                'value' => number_format( $total_views ),
                'icon' => '👁️',
            ];
        }

        if ( in_array( 'categories', $show_items ) ) {
            $categories_count = wp_count_terms( 'ipv_categoria' );
            $stats['categories'] = [
                'label' => 'Categories',
                'value' => number_format( $categories_count ),
                'icon' => '📁',
            ];
        }

        if ( in_array( 'recent', $show_items ) ) {
            $recent_count = get_posts( [
                'post_type' => 'ipv_video',
                'date_query' => [
                    [
                        'after' => '7 days ago',
                    ],
                ],
                'fields' => 'ids',
            ] );
            $stats['recent'] = [
                'label' => 'Added This Week',
                'value' => count( $recent_count ),
                'icon' => '🆕',
            ];
        }

        ob_start();

        if ( $atts['style'] === 'cards' ) :
        ?>
            <div class="ipv-stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <?php foreach ( $stats as $stat ) : ?>
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="font-size: 36px; margin-bottom: 10px;">
                            <?php echo $stat['icon']; ?>
                        </div>
                        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                            <?php echo $stat['value']; ?>
                        </div>
                        <div style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo $stat['label']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ( $atts['style'] === 'list' ) : ?>
            <ul class="ipv-stats-list" style="list-style: none; padding: 0; margin: 20px 0;">
                <?php foreach ( $stats as $stat ) : ?>
                    <li style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 16px;">
                            <?php echo $stat['icon']; ?> <?php echo $stat['label']; ?>
                        </span>
                        <strong style="font-size: 20px; color: #2271b1;">
                            <?php echo $stat['value']; ?>
                        </strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="ipv-stats-inline" style="display: flex; gap: 30px; flex-wrap: wrap; margin: 20px 0;">
                <?php foreach ( $stats as $stat ) : ?>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: #2271b1;">
                            <?php echo $stat['icon']; ?> <?php echo $stat['value']; ?>
                        </div>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            <?php echo $stat['label']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif;

        return ob_get_clean();
    }
}

IPV_Prod_Shortcodes::init();
