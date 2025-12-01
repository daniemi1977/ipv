<?php
/**
 * Archive Video Template
 *
 * Override this template by copying it to:
 * your-theme/ipv-production-templates/archive-ipv_video.php
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="ipv-archive-wrapper" style="max-width: 1400px; margin: 40px auto; padding: 0 20px;">

    <!-- Archive Header -->
    <header class="ipv-archive-header" style="margin-bottom: 40px; text-align: center;">
        <?php
        if ( is_post_type_archive( 'ipv_video' ) ) {
            echo '<h1 style="font-size: 42px; margin-bottom: 10px;">Video Archive</h1>';
            echo '<p style="font-size: 18px; color: #666;">Browse all videos</p>';
        } elseif ( is_tax() ) {
            $term = get_queried_object();
            echo '<h1 style="font-size: 42px; margin-bottom: 10px;">' . esc_html( $term->name ) . '</h1>';
            if ( $term->description ) {
                echo '<p style="font-size: 18px; color: #666;">' . wp_kses_post( $term->description ) . '</p>';
            }
        }
        ?>
    </header>

    <!-- Hook: Before archive -->
    <?php do_action( 'ipv_before_video_archive' ); ?>

    <?php if ( have_posts() ) : ?>

        <!-- Video Grid -->
        <div class="ipv-archive-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; margin-bottom: 40px;">
            <?php
            while ( have_posts() ) :
                the_post();

                // Render video card
                IPV_Prod_Theme_Compatibility::render_video_card( get_the_ID(), [
                    'show_thumbnail' => true,
                    'show_title' => true,
                    'show_excerpt' => true,
                    'show_meta' => true,
                    'show_categories' => true,
                    'excerpt_length' => 20,
                ] );

            endwhile;
            ?>
        </div>

        <!-- Pagination -->
        <div class="ipv-pagination" style="text-align: center; margin-top: 40px;">
            <?php
            the_posts_pagination( [
                'mid_size' => 2,
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
            ] );
            ?>
        </div>

    <?php else : ?>

        <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
            <h2 style="font-size: 24px; margin-bottom: 10px;">No videos found</h2>
            <p style="color: #666;">Try searching for something else or check back later.</p>
        </div>

    <?php endif; ?>

    <!-- Hook: After archive -->
    <?php do_action( 'ipv_after_video_archive' ); ?>

</div>

<?php get_footer(); ?>
