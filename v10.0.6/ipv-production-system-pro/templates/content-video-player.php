<?php
/**
 * Video Player Template Part
 *
 * Override this template by copying it to:
 * your-theme/ipv-production-templates/content-video-player.php
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 *
 * Available variables:
 * @var int $post_id Video post ID
 * @var string $video_source Video source (youtube, vimeo, dailymotion)
 * @var string $video_id Video ID
 * @var array $args Player arguments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Build embed URL
$autoplay = ! empty( $args['autoplay'] ) ? 1 : 0;
$controls = ! empty( $args['controls'] ) ? 1 : 0;
$mute = ! empty( $args['mute'] ) ? 1 : 0;
$loop = ! empty( $args['loop'] ) ? 1 : 0;

$embed_url = '';

switch ( $video_source ) {
    case 'youtube':
        $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}&loop={$loop}";
        if ( $loop ) {
            $embed_url .= "&playlist={$video_id}";
        }
        break;

    case 'vimeo':
        $embed_url = "https://player.vimeo.com/video/{$video_id}?autoplay={$autoplay}&controls={$controls}&muted={$mute}&loop={$loop}";
        break;

    case 'dailymotion':
        $embed_url = "https://www.dailymotion.com/embed/video/{$video_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}";
        if ( $loop ) {
            $embed_url .= '&queue-enable=false&ui-logo=false';
        }
        break;
}

// Hook: Modify embed URL
$embed_url = apply_filters( 'ipv_video_embed_url', $embed_url, $video_source, $video_id, $args );

// Calculate aspect ratio
$aspect_ratios = [
    '16:9' => '56.25%',
    '4:3' => '75%',
    '21:9' => '42.857%',
    '1:1' => '100%',
];
$padding = $aspect_ratios[ $args['aspect'] ?? '16:9' ] ?? '56.25%';
?>

<div class="ipv-video-player" data-video-id="<?php echo esc_attr( $video_id ); ?>" data-source="<?php echo esc_attr( $video_source ); ?>" style="width: <?php echo esc_attr( $args['width'] ?? '100%' ); ?>; max-width: 100%; margin: 0 auto;">
    <div class="ipv-player-wrapper" style="position: relative; padding-bottom: <?php echo esc_attr( $padding ); ?>; height: 0; overflow: hidden; background: #000; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <iframe
            src="<?php echo esc_url( $embed_url ); ?>"
            class="ipv-player-iframe"
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"
        ></iframe>
    </div>
</div>
