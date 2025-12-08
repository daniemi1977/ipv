<?php
/**
 * Single Video Template
 *
 * Override this template by copying it to:
 * your-theme/ipv-production-templates/single-ipv_video.php
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="ipv-single-video-wrapper" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <?php
    while ( have_posts() ) :
        the_post();

        $video_data = IPV_Prod_Theme_Compatibility::get_video_data( get_the_ID() );

        // Hook: Before content
        do_action( 'ipv_before_single_video_content', get_the_ID() );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'ipv-single-video' ); ?>>

            <!-- Video Player -->
            <div class="ipv-video-player-container" style="margin-bottom: 30px;">
                <?php
                IPV_Prod_Theme_Compatibility::render_video_player( get_the_ID(), [
                    'controls' => true,
                    'width' => '100%',
                    'aspect' => '16:9',
                ] );
                ?>
            </div>

            <!-- Video Title -->
            <h1 class="ipv-video-title" style="font-size: 32px; margin: 0 0 20px 0; line-height: 1.3;">
                <?php the_title(); ?>
            </h1>

            <!-- Video Meta -->
            <div class="ipv-video-meta" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0; font-size: 14px; color: #666;">
                <?php if ( $video_data['duration'] ) : ?>
                    <span>⏱️ <?php echo esc_html( $video_data['duration'] ); ?></span>
                <?php endif; ?>

                <?php if ( $video_data['view_count'] ) : ?>
                    <span>👁️ <?php echo number_format( $video_data['view_count'] ); ?> views</span>
                <?php endif; ?>

                <?php if ( $video_data['like_count'] ) : ?>
                    <span>👍 <?php echo number_format( $video_data['like_count'] ); ?> likes</span>
                <?php endif; ?>

                <?php if ( $video_data['published_at'] ) : ?>
                    <span>📅 <?php echo date_i18n( 'F j, Y', strtotime( $video_data['published_at'] ) ); ?></span>
                <?php endif; ?>
            </div>

            <!-- Taxonomies -->
            <?php if ( ! empty( $video_data['categories'] ) || ! empty( $video_data['speakers'] ) ) : ?>
                <div class="ipv-video-taxonomies" style="margin-bottom: 30px; display: flex; gap: 30px; flex-wrap: wrap;">
                    <?php if ( ! empty( $video_data['categories'] ) ) : ?>
                        <div>
                            <strong style="display: block; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; color: #999;">Categories</strong>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php foreach ( $video_data['categories'] as $cat ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" style="padding: 6px 12px; background: #f0f0f0; border-radius: 4px; font-size: 13px; text-decoration: none; color: #333;">
                                        <?php echo esc_html( $cat->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $video_data['speakers'] ) ) : ?>
                        <div>
                            <strong style="display: block; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; color: #999;">Speakers</strong>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php foreach ( $video_data['speakers'] as $speaker ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $speaker ) ); ?>" style="padding: 6px 12px; background: #e8f4fd; border-radius: 4px; font-size: 13px; text-decoration: none; color: #2271b1;">
                                        👤 <?php echo esc_html( $speaker->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Video Content/Description -->
            <div class="ipv-video-content" style="font-size: 16px; line-height: 1.8; color: #333;">
                <?php
                // Hook: Filter content
                echo apply_filters( 'ipv_single_video_content', get_the_content() );
                ?>
            </div>

        </article>

        <?php
        // Hook: After content
        do_action( 'ipv_after_single_video_content', get_the_ID() );

    endwhile;
    ?>
</div>

<?php get_footer(); ?>
