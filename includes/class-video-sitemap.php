<?php
/**
 * IPV Production System Pro - Video Sitemap XML
 *
 * Auto-generate video sitemap for SEO
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Sitemap {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'add_rewrite_rules' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_serve_sitemap' ] );
        add_action( 'admin_init', [ __CLASS__, 'flush_rewrite_on_activation' ] );

        // Add sitemap to robots.txt
        add_filter( 'robots_txt', [ __CLASS__, 'add_sitemap_to_robots' ], 10, 2 );
    }

    /**
     * Add rewrite rules for sitemap
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule( '^video-sitemap\.xml$', 'index.php?ipv_video_sitemap=1', 'top' );
        add_rewrite_tag( '%ipv_video_sitemap%', '([^&]+)' );
    }

    /**
     * Serve sitemap XML
     */
    public static function maybe_serve_sitemap() {
        if ( ! get_query_var( 'ipv_video_sitemap' ) ) {
            return;
        }

        header( 'Content-Type: application/xml; charset=utf-8' );
        echo self::generate_sitemap();
        exit;
    }

    /**
     * Generate sitemap XML
     */
    public static function generate_sitemap() {
        // Check cache
        $cache_key = 'ipv_video_sitemap_xml';
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => 1000,
            'orderby' => 'date',
            'order' => 'DESC',
        ] );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

        foreach ( $videos as $video ) {
            $video_id = get_post_meta( $video->ID, '_ipv_video_id', true );
            $source = get_post_meta( $video->ID, '_ipv_video_source', true ) ?: 'youtube';
            $thumbnail = get_post_meta( $video->ID, '_ipv_yt_thumbnail_url', true );
            $duration = get_post_meta( $video->ID, '_ipv_yt_duration_seconds', true );
            $views = get_post_meta( $video->ID, '_ipv_yt_view_count', true );
            $description = wp_trim_words( strip_tags( $video->post_content ), 50, '...' );

            // Build video URL based on source
            $video_urls = [
                'youtube' => "https://www.youtube.com/watch?v={$video_id}",
                'vimeo' => "https://vimeo.com/{$video_id}",
                'dailymotion' => "https://www.dailymotion.com/video/{$video_id}",
            ];
            $content_url = $video_urls[ $source ] ?? $video_urls['youtube'];

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url( get_permalink( $video->ID ) ) . "</loc>\n";
            $xml .= "    <lastmod>" . mysql2date( 'c', $video->post_modified ) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "    <video:video>\n";
            $xml .= "      <video:thumbnail_loc>" . esc_url( $thumbnail ) . "</video:thumbnail_loc>\n";
            $xml .= "      <video:title><![CDATA[" . $video->post_title . "]]></video:title>\n";
            $xml .= "      <video:description><![CDATA[" . $description . "]]></video:description>\n";
            $xml .= "      <video:content_loc>" . esc_url( $content_url ) . "</video:content_loc>\n";

            if ( $duration ) {
                $xml .= "      <video:duration>" . $duration . "</video:duration>\n";
            }

            if ( $views ) {
                $xml .= "      <video:view_count>" . $views . "</video:view_count>\n";
            }

            $xml .= "      <video:publication_date>" . mysql2date( 'c', $video->post_date ) . "</video:publication_date>\n";
            $xml .= "      <video:family_friendly>yes</video:family_friendly>\n";

            // Add categories
            $categories = get_the_terms( $video->ID, 'ipv_categoria' );
            if ( $categories && ! is_wp_error( $categories ) ) {
                $xml .= "      <video:category><![CDATA[" . $categories[0]->name . "]]></video:category>\n";
            }

            $xml .= "    </video:video>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        // Cache for 1 day
        set_transient( $cache_key, $xml, DAY_IN_SECONDS );

        return $xml;
    }

    /**
     * Add sitemap to robots.txt
     */
    public static function add_sitemap_to_robots( $output, $public ) {
        if ( '1' !== $public ) {
            return $output;
        }

        $sitemap_url = home_url( '/video-sitemap.xml' );
        $output .= "\n# IPV Production Video Sitemap\n";
        $output .= "Sitemap: {$sitemap_url}\n";

        return $output;
    }

    /**
     * Flush rewrite rules on activation
     */
    public static function flush_rewrite_on_activation() {
        if ( get_option( 'ipv_sitemap_flushed' ) !== IPV_PROD_VERSION ) {
            flush_rewrite_rules();
            update_option( 'ipv_sitemap_flushed', IPV_PROD_VERSION );
        }
    }

    /**
     * Clear sitemap cache
     */
    public static function clear_cache() {
        delete_transient( 'ipv_video_sitemap_xml' );
    }
}

IPV_Prod_Video_Sitemap::init();

// Clear cache on post publish/update
add_action( 'save_post_ipv_video', [ 'IPV_Prod_Video_Sitemap', 'clear_cache' ] );
