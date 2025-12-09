<?php
/**
 * IPV Video SEO
 * Schema.org VideoObject, Open Graph, Twitter Cards
 *
 * @version 7.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_SEO {

    public static function init() {
        // Inietta Schema.org JSON-LD
        add_action( 'wp_head', [ __CLASS__, 'inject_schema_markup' ], 1 );

        // Inietta Open Graph tags
        add_action( 'wp_head', [ __CLASS__, 'inject_og_tags' ], 2 );

        // Inietta Twitter Cards
        add_action( 'wp_head', [ __CLASS__, 'inject_twitter_cards' ], 3 );

        // Modifica sitemap XML per includere video
        add_filter( 'wpseo_sitemap_urlimages', [ __CLASS__, 'add_videos_to_sitemap' ], 10, 2 );
    }

    /**
     * Inietta Schema.org VideoObject JSON-LD
     */
    public static function inject_schema_markup() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $yt_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => get_the_title( $post_id ),
            'description' => self::get_description( $post_id ),
            'thumbnailUrl' => [
                self::get_thumbnail_url( $post_id, $yt_id ),
            ],
            'uploadDate' => self::get_upload_date( $post_id ),
            'duration' => self::get_duration_iso8601( $post_id ),
            'contentUrl' => 'https://www.youtube.com/watch?v=' . $yt_id,
            'embedUrl' => 'https://www.youtube.com/embed/' . $yt_id,
            'interactionStatistic' => [
                '@type' => 'InteractionCounter',
                'interactionType' => [ '@type' => 'WatchAction' ],
                'userInteractionCount' => get_post_meta( $post_id, '_ipv_yt_views', true ) ?: 0,
            ],
        ];

        // Aggiungi autore se disponibile
        $author_id = get_post_field( 'post_author', $post_id );
        if ( $author_id ) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => get_the_author_meta( 'display_name', $author_id ),
                'url' => get_author_posts_url( $author_id ),
            ];
        }

        // Aggiungi capitoli se disponibili
        $chapters = get_post_meta( $post_id, '_ipv_chapters', true );
        if ( ! empty( $chapters ) && is_array( $chapters ) ) {
            $schema['hasPart'] = [];
            foreach ( $chapters as $chapter ) {
                $schema['hasPart'][] = [
                    '@type' => 'Clip',
                    'name' => $chapter['title'],
                    'startOffset' => $chapter['start_seconds'],
                    'url' => 'https://www.youtube.com/watch?v=' . $yt_id . '&t=' . $chapter['start_seconds'],
                ];
            }
        }

        echo "\n<!-- IPV Video SEO - Schema.org VideoObject -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
        echo '</script>' . "\n";
    }

    /**
     * Inietta Open Graph tags
     */
    public static function inject_og_tags() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $yt_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return;
        }

        echo "\n<!-- IPV Video SEO - Open Graph -->\n";
        echo '<meta property="og:type" content="video.other">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( self::get_description( $post_id ) ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( self::get_thumbnail_url( $post_id, $yt_id ) ) . '">' . "\n";
        echo '<meta property="og:image:width" content="1280">' . "\n";
        echo '<meta property="og:image:height" content="720">' . "\n";
        echo '<meta property="og:video" content="https://www.youtube.com/watch?v=' . esc_attr( $yt_id ) . '">' . "\n";
        echo '<meta property="og:video:secure_url" content="https://www.youtube.com/embed/' . esc_attr( $yt_id ) . '">' . "\n";
        echo '<meta property="og:video:type" content="text/html">' . "\n";
        echo '<meta property="og:video:width" content="1280">' . "\n";
        echo '<meta property="og:video:height" content="720">' . "\n";

        $duration = get_post_meta( $post_id, '_ipv_duration', true );
        if ( $duration ) {
            echo '<meta property="video:duration" content="' . absint( $duration ) . '">' . "\n";
        }

        $published_at = get_post_meta( $post_id, '_ipv_published_at', true );
        if ( $published_at ) {
            echo '<meta property="video:release_date" content="' . esc_attr( $published_at ) . '">' . "\n";
        }
    }

    /**
     * Inietta Twitter Cards
     */
    public static function inject_twitter_cards() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $yt_id = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return;
        }

        echo "\n<!-- IPV Video SEO - Twitter Cards -->\n";
        echo '<meta name="twitter:card" content="player">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( self::get_description( $post_id ) ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( self::get_thumbnail_url( $post_id, $yt_id ) ) . '">' . "\n";
        echo '<meta name="twitter:player" content="https://www.youtube.com/embed/' . esc_attr( $yt_id ) . '">' . "\n";
        echo '<meta name="twitter:player:width" content="1280">' . "\n";
        echo '<meta name="twitter:player:height" content="720">' . "\n";
    }

    /**
     * Helper: Ottieni descrizione (max 300 caratteri per SEO)
     */
    private static function get_description( $post_id ) {
        $desc = get_post_meta( $post_id, '_ipv_ai_description', true );
        if ( empty( $desc ) ) {
            $desc = get_the_excerpt( $post_id );
        }
        if ( empty( $desc ) ) {
            $desc = get_the_title( $post_id );
        }

        // Rimuovi HTML e shortcode
        $desc = wp_strip_all_tags( strip_shortcodes( $desc ) );

        // Limita a 300 caratteri (best practice SEO)
        if ( mb_strlen( $desc ) > 300 ) {
            $desc = mb_substr( $desc, 0, 297 ) . '...';
        }

        return $desc;
    }

    /**
     * Helper: Ottieni URL thumbnail (maxresdefault YouTube o featured image)
     */
    private static function get_thumbnail_url( $post_id, $yt_id ) {
        // Prova prima la featured image
        $thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( $thumbnail ) {
            return $thumbnail;
        }

        // Fallback: maxresdefault YouTube
        return 'https://i.ytimg.com/vi/' . $yt_id . '/maxresdefault.jpg';
    }

    /**
     * Helper: Ottieni upload date in formato ISO8601
     */
    private static function get_upload_date( $post_id ) {
        $published_at = get_post_meta( $post_id, '_ipv_published_at', true );
        if ( ! empty( $published_at ) ) {
            return gmdate( 'c', strtotime( $published_at ) );
        }

        // Fallback: data pubblicazione WordPress
        return get_the_date( 'c', $post_id );
    }

    /**
     * Helper: Converti durata in formato ISO8601 (es: PT1H30M45S)
     */
    private static function get_duration_iso8601( $post_id ) {
        $seconds = get_post_meta( $post_id, '_ipv_duration', true );
        if ( empty( $seconds ) ) {
            return 'PT0S';
        }

        $hours = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;

        $duration = 'PT';
        if ( $hours > 0 ) {
            $duration .= $hours . 'H';
        }
        if ( $minutes > 0 ) {
            $duration .= $minutes . 'M';
        }
        if ( $secs > 0 || ( $hours === 0 && $minutes === 0 ) ) {
            $duration .= $secs . 'S';
        }

        return $duration;
    }

    /**
     * Aggiungi video al sitemap XML (Yoast SEO compatibility)
     */
    public static function add_videos_to_sitemap( $images, $post_id ) {
        if ( get_post_type( $post_id ) !== 'ipv_video' ) {
            return $images;
        }

        $yt_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( empty( $yt_id ) ) {
            return $images;
        }

        $video = [
            'type' => 'video',
            'src' => 'https://www.youtube.com/embed/' . $yt_id,
            'title' => get_the_title( $post_id ),
            'thumbnail' => self::get_thumbnail_url( $post_id, $yt_id ),
            'description' => self::get_description( $post_id ),
        ];

        $duration = get_post_meta( $post_id, '_ipv_duration', true );
        if ( $duration ) {
            $video['duration'] = absint( $duration );
        }

        $images[] = $video;

        return $images;
    }
}

IPV_Prod_Video_SEO::init();
