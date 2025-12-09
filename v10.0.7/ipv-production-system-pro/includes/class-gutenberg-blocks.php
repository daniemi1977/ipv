<?php
/**
 * IPV Production System Pro - Gutenberg Blocks
 *
 * Custom blocks for video embedding
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Gutenberg_Blocks {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_blocks' ] );
        add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_block_assets' ] );
    }

    /**
     * Register Gutenberg blocks
     */
    public static function register_blocks() {
        // Video player block
        register_block_type( 'ipv-production/video-player', [
            'render_callback' => [ __CLASS__, 'render_video_player' ],
            'attributes' => [
                'videoId' => [
                    'type' => 'number',
                    'default' => 0,
                ],
                'autoplay' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'controls' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
            ],
        ] );

        // Video grid block
        register_block_type( 'ipv-production/video-grid', [
            'render_callback' => [ __CLASS__, 'render_video_grid' ],
            'attributes' => [
                'columns' => [
                    'type' => 'number',
                    'default' => 3,
                ],
                'count' => [
                    'type' => 'number',
                    'default' => 6,
                ],
                'category' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
        ] );
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_assets() {
        wp_enqueue_script(
            'ipv-blocks',
            IPV_PROD_PLUGIN_URL . 'assets/js/blocks.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ],
            IPV_PROD_VERSION,
            true
        );

        wp_localize_script( 'ipv-blocks', 'ipvBlocks', [
            'apiUrl' => rest_url( 'ipv-production/v1' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    /**
     * Render video player block
     */
    public static function render_video_player( $attributes ) {
        $video_id = $attributes['videoId'] ?? 0;

        if ( ! $video_id ) {
            return '<p>Seleziona un video</p>';
        }

        $source = get_post_meta( $video_id, '_ipv_video_source', true ) ?: 'youtube';
        $video_code = get_post_meta( $video_id, '_ipv_video_id', true );
        $autoplay = $attributes['autoplay'] ? '&autoplay=1' : '';

        $embed_urls = [
            'youtube' => "https://www.youtube.com/embed/{$video_code}?rel=0{$autoplay}",
            'vimeo' => "https://player.vimeo.com/video/{$video_code}?{$autoplay}",
            'dailymotion' => "https://www.dailymotion.com/embed/video/{$video_code}?{$autoplay}",
        ];

        $embed_url = $embed_urls[ $source ] ?? $embed_urls['youtube'];

        ob_start();
        ?>
        <div class="ipv-block-video-player" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
            <iframe
                src="<?php echo esc_url( $embed_url ); ?>"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render video grid block
     */
    public static function render_video_grid( $attributes ) {
        $columns = $attributes['columns'] ?? 3;
        $count = $attributes['count'] ?? 6;
        $category = $attributes['category'] ?? '';

        $args = [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ( ! empty( $category ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ipv_categoria',
                    'field' => 'slug',
                    'terms' => $category,
                ],
            ];
        }

        $query = new WP_Query( $args );

        ob_start();
        ?>
        <div class="ipv-block-video-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr); gap: 20px;">
            <?php
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $thumbnail = get_post_meta( get_the_ID(), '_ipv_yt_thumbnail_url', true );
                    $duration = get_post_meta( get_the_ID(), '_ipv_yt_duration_formatted', true );
                    ?>
                    <div class="ipv-grid-item" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                        <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: inherit;">
                            <?php if ( $thumbnail ) : ?>
                                <div style="position: relative;">
                                    <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title(); ?>" style="width: 100%; display: block;">
                                    <?php if ( $duration ) : ?>
                                        <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo esc_html( $duration ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 15px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px;"><?php the_title(); ?></h3>
                                <p style="margin: 0; font-size: 14px; color: #666;"><?php echo wp_trim_words( get_the_excerpt(), 15 ); ?></p>
                            </div>
                        </a>
                    </div>
                    <?php
                }
                wp_reset_postdata();
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

IPV_Prod_Gutenberg_Blocks::init();
