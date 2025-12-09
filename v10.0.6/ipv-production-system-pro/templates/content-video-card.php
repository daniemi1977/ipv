<?php
/**
 * Video Card Template Part
 *
 * Override this template by copying it to:
 * your-theme/ipv-production-templates/content-video-card.php
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 *
 * Available variables:
 * @var int $post_id Video post ID
 * @var array $args Card arguments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$video_data = IPV_Prod_Theme_Compatibility::get_video_data( $post_id );

// Defaults
$show_thumbnail = $args['show_thumbnail'] ?? true;
$show_title = $args['show_title'] ?? true;
$show_excerpt = $args['show_excerpt'] ?? true;
$show_meta = $args['show_meta'] ?? true;
$show_categories = $args['show_categories'] ?? false;
$excerpt_length = $args['excerpt_length'] ?? 20;
?>

<article id="post-<?php echo esc_attr( $post_id ); ?>" class="ipv-video-card" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column;">

    <?php if ( $show_thumbnail && $video_data['thumbnail_url'] ) : ?>
        <div class="ipv-card-thumbnail" style="position: relative; padding-bottom: 56.25%; background: #000; overflow: hidden;">
            <a href="<?php echo esc_url( $video_data['permalink'] ); ?>" style="display: block; position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                <img
                    src="<?php echo esc_url( $video_data['thumbnail_url'] ); ?>"
                    alt="<?php echo esc_attr( $video_data['title'] ); ?>"
                    style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                    loading="lazy"
                >

                <!-- Play Overlay -->
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60px; height: 60px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.3s ease;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="margin-left: 3px;">
                        <path d="M8 5v14l11-7z" fill="#333"/>
                    </svg>
                </div>

                <!-- Duration Badge -->
                <?php if ( $show_meta && $video_data['duration'] ) : ?>
                    <span style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.85); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">
                        <?php echo esc_html( $video_data['duration'] ); ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="ipv-card-content" style="padding: 20px; flex: 1; display: flex; flex-direction: column;">

        <?php if ( $show_categories && ! empty( $video_data['categories'] ) ) : ?>
            <div class="ipv-card-categories" style="margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ( array_slice( $video_data['categories'], 0, 2 ) as $cat ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" style="padding: 4px 10px; background: #f0f0f0; color: #666; border-radius: 4px; font-size: 11px; text-decoration: none; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                        <?php echo esc_html( $cat->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( $show_title ) : ?>
            <h3 class="ipv-card-title" style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; line-height: 1.4; color: #333;">
                <a href="<?php echo esc_url( $video_data['permalink'] ); ?>" style="text-decoration: none; color: inherit; transition: color 0.2s;">
                    <?php echo esc_html( $video_data['title'] ); ?>
                </a>
            </h3>
        <?php endif; ?>

        <?php if ( $show_excerpt ) : ?>
            <p class="ipv-card-excerpt" style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #666; flex: 1;">
                <?php
                if ( $video_data['excerpt'] ) {
                    echo wp_trim_words( $video_data['excerpt'], $excerpt_length, '...' );
                } else {
                    echo wp_trim_words( $video_data['content'], $excerpt_length, '...' );
                }
                ?>
            </p>
        <?php endif; ?>

        <?php if ( $show_meta ) : ?>
            <div class="ipv-card-meta" style="display: flex; gap: 15px; flex-wrap: wrap; font-size: 13px; color: #999; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                <?php if ( $video_data['view_count'] ) : ?>
                    <span title="Views">
                        👁️ <?php echo number_format( $video_data['view_count'] ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $video_data['like_count'] ) : ?>
                    <span title="Likes">
                        👍 <?php echo number_format( $video_data['like_count'] ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $video_data['published_at'] ) : ?>
                    <span title="Published">
                        📅 <?php echo human_time_diff( strtotime( $video_data['published_at'] ), current_time( 'timestamp' ) ); ?> ago
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</article>

<style>
    .ipv-video-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .ipv-video-card:hover .ipv-card-thumbnail img {
        transform: scale(1.05);
    }

    .ipv-card-title a:hover {
        color: #2271b1;
    }
</style>
