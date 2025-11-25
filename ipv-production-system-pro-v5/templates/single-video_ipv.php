<?php
/**
 * Template singolo video
 */

get_header();

while ( have_posts() ) : the_post();
    $video_id = get_post_meta( get_the_ID(), '_ipv_video_id', true );
    $youtube_url = get_post_meta( get_the_ID(), '_ipv_youtube_url', true );
    ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class('ipv-single-video'); ?>>
        <header class="entry-header">
            <h1 class="entry-title"><?php the_title(); ?></h1>
        </header>

        <div class="entry-content">
            <?php if ( $video_id ) : ?>
                <div class="ipv-video-embed">
                    <iframe width="100%" height="500" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>

            <?php the_content(); ?>
        </div>
    </article>

    <?php
endwhile;

get_footer();
