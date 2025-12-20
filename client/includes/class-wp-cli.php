<?php
/**
 * IPV Production System Pro - WP-CLI Commands
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class IPV_Prod_WP_CLI {

    /**
     * Import video from URL
     *
     * ## OPTIONS
     *
     * <url>
     * : Video URL (YouTube, Vimeo, or Dailymotion)
     *
     * ## EXAMPLES
     *
     *     wp ipv import https://youtube.com/watch?v=ABC123
     *
     * @when after_wp_load
     */
    public function import( $args, $assoc_args ) {
        list( $url ) = $args;

        WP_CLI::log( "Rilevamento fonte da URL: {$url}" );

        $detection = IPV_Prod_Unified_Importer::detect_source( $url );

        if ( is_wp_error( $detection ) ) {
            WP_CLI::error( $detection->get_error_message() );
        }

        WP_CLI::log( "Fonte rilevata: {$detection['source']}, ID: {$detection['video_id']}" );

        $post_id = IPV_Prod_Unified_Importer::import_video( $detection['source'], $detection['video_id'] );

        if ( is_wp_error( $post_id ) ) {
            WP_CLI::error( $post_id->get_error_message() );
        }

        WP_CLI::success( "Video importato! Post ID: {$post_id}" );
    }

    /**
     * List all videos
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format (table, json, csv)
     * ---
     * default: table
     * ---
     *
     * ## EXAMPLES
     *
     *     wp ipv list
     *     wp ipv list --format=json
     *
     * @when after_wp_load
     */
    public function list( $args, $assoc_args ) {
        $format = $assoc_args['format'] ?? 'table';

        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ] );

        $items = [];
        foreach ( $videos as $video ) {
            $items[] = [
                'ID' => $video->ID,
                'Title' => $video->post_title,
                'Source' => get_post_meta( $video->ID, '_ipv_video_source', true ) ?: 'youtube',
                'Views' => get_post_meta( $video->ID, '_ipv_yt_view_count', true ) ?: 0,
                'Date' => $video->post_date,
            ];
        }

        WP_CLI\Utils\format_items( $format, $items, [ 'ID', 'Title', 'Source', 'Views', 'Date' ] );
    }

    /**
     * Get video statistics
     *
     * ## EXAMPLES
     *
     *     wp ipv stats
     *
     * @when after_wp_load
     */
    public function stats( $args, $assoc_args ) {
        $stats = IPV_Prod_Analytics::get_aggregate_stats();

        WP_CLI::log( "📊 Statistiche IPV Production" );
        WP_CLI::log( "----------------------------" );
        WP_CLI::log( "Total Videos: " . $stats['total_videos'] );
        WP_CLI::log( "Total Views: " . number_format( $stats['total_views'] ) );
        WP_CLI::log( "Total Duration: " . round( $stats['total_duration'] / 3600, 2 ) . " ore" );
        WP_CLI::log( "Average Views: " . number_format( $stats['avg_views'] ) );
    }

    /**
     * Optimize database
     *
     * ## EXAMPLES
     *
     *     wp ipv optimize
     *
     * @when after_wp_load
     */
    public function optimize( $args, $assoc_args ) {
        WP_CLI::log( "Ottimizzazione database in corso..." );

        // Cleanup orphaned meta
        $deleted = IPV_Prod_Performance::cleanup_orphaned_meta();
        WP_CLI::log( "✓ Rimossi {$deleted} meta orfani" );

        // Optimize tables
        IPV_Prod_Performance::optimize_tables();
        WP_CLI::log( "✓ Tabelle ottimizzate" );

        // Clear caches
        IPV_Prod_Performance::clear_all_caches();
        WP_CLI::log( "✓ Cache pulita" );

        WP_CLI::success( "Ottimizzazione completata!" );
    }

    /**
     * Regenerate video thumbnails
     *
     * ## OPTIONS
     *
     * [<post_id>]
     * : Specific post ID (optional, otherwise all videos)
     *
     * ## EXAMPLES
     *
     *     wp ipv regen-thumbnails
     *     wp ipv regen-thumbnails 123
     *
     * @when after_wp_load
     */
    public function regen_thumbnails( $args, $assoc_args ) {
        $post_id = $args[0] ?? null;

        if ( $post_id ) {
            $videos = [ get_post( $post_id ) ];
        } else {
            $videos = get_posts( [
                'post_type' => 'ipv_video',
                'post_status' => 'any',
                'posts_per_page' => -1,
            ] );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Rigenerazione thumbnails', count( $videos ) );

        foreach ( $videos as $video ) {
            $video_id = get_post_meta( $video->ID, '_ipv_video_id', true );
            $source = get_post_meta( $video->ID, '_ipv_video_source', true ) ?: 'youtube';

            if ( $source === 'youtube' && $video_id ) {
                $thumb_url = "https://i.ytimg.com/vi/{$video_id}/maxresdefault.jpg";
                update_post_meta( $video->ID, '_ipv_yt_thumbnail_url', $thumb_url );
            }

            $progress->tick();
        }

        $progress->finish();
        WP_CLI::success( "Thumbnails rigenerati!" );
    }
}

WP_CLI::add_command( 'ipv', 'IPV_Prod_WP_CLI' );
