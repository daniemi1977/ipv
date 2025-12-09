<?php
/**
 * IPV Production System Pro - Performance Optimization
 *
 * Database indices, query optimization, asset loading optimization
 *
 * @package IPV_Production_System_Pro
 * @version 7.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Performance {

    public static function init() {
        // Create database indices on plugin activation
        register_activation_hook( IPV_PROD_PLUGIN_FILE, [ __CLASS__, 'create_database_indices' ] );

        // Defer JavaScript loading
        add_filter( 'script_loader_tag', [ __CLASS__, 'defer_scripts' ], 10, 3 );

        // Optimize admin queries
        add_action( 'pre_get_posts', [ __CLASS__, 'optimize_admin_queries' ] );

        // Add custom query optimization for video wall
        add_filter( 'posts_clauses', [ __CLASS__, 'optimize_video_wall_query' ], 10, 2 );
    }

    /**
     * Create database indices for faster queries
     */
    public static function create_database_indices() {
        global $wpdb;

        // Index for _ipv_yt_view_count (sorting by views)
        $wpdb->query( "
            CREATE INDEX IF NOT EXISTS idx_ipv_views
            ON {$wpdb->postmeta} (meta_key, meta_value(10))
            WHERE meta_key = '_ipv_yt_view_count'
        " );

        // Index for _ipv_yt_duration_seconds (sorting by duration)
        $wpdb->query( "
            CREATE INDEX IF NOT EXISTS idx_ipv_duration
            ON {$wpdb->postmeta} (meta_key, meta_value(10))
            WHERE meta_key = '_ipv_yt_duration_seconds'
        " );

        // Index for _ipv_video_id (lookups by YouTube ID)
        $wpdb->query( "
            CREATE INDEX IF NOT EXISTS idx_ipv_video_id
            ON {$wpdb->postmeta} (meta_key, meta_value(20))
            WHERE meta_key = '_ipv_video_id'
        " );

        // Composite index for video filtering
        $wpdb->query( "
            CREATE INDEX IF NOT EXISTS idx_ipv_post_type_status_date
            ON {$wpdb->posts} (post_type, post_status, post_date)
        " );

        IPV_Prod_Logger::log( 'Database indices created for performance optimization' );
    }

    /**
     * Defer non-critical JavaScript
     */
    public static function defer_scripts( $tag, $handle, $src ) {
        // Skip admin scripts
        if ( is_admin() ) {
            return $tag;
        }

        // Scripts to defer (non-critical)
        $defer_scripts = [
            'ipv-video-wall',
            'jquery-core', // jQuery can be deferred if not critical
        ];

        // Scripts to async load (independent)
        $async_scripts = [
            'ipv-chartjs',
        ];

        if ( in_array( $handle, $defer_scripts, true ) ) {
            return str_replace( ' src', ' defer src', $tag );
        }

        if ( in_array( $handle, $async_scripts, true ) ) {
            return str_replace( ' src', ' async src', $tag );
        }

        return $tag;
    }

    /**
     * Optimize admin queries for ipv_video post type
     */
    public static function optimize_admin_queries( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        // Only for ipv_video post type lists
        if ( $query->get( 'post_type' ) !== 'ipv_video' ) {
            return;
        }

        // Limit fields to ID only when possible
        if ( ! $query->get( 'fields' ) ) {
            $query->set( 'no_found_rows', true ); // Skip total count query if pagination not needed
        }

        // Set reasonable posts_per_page for admin
        if ( ! $query->get( 'posts_per_page' ) ) {
            $query->set( 'posts_per_page', 20 );
        }
    }

    /**
     * Optimize video wall query with custom SQL
     */
    public static function optimize_video_wall_query( $clauses, $query ) {
        global $wpdb;

        // Only optimize video wall queries
        if ( $query->get( 'post_type' ) !== 'ipv_video' || is_admin() ) {
            return $clauses;
        }

        // Add index hints for MySQL optimizer
        if ( $query->get( 'orderby' ) === 'meta_value_num' ) {
            $meta_key = $query->get( 'meta_key' );

            if ( in_array( $meta_key, [ '_ipv_yt_view_count', '_ipv_yt_duration_seconds' ], true ) ) {
                // Force use of our custom indices
                $clauses['join'] = str_replace(
                    "INNER JOIN {$wpdb->postmeta}",
                    "INNER JOIN {$wpdb->postmeta} USE INDEX (idx_ipv_views, idx_ipv_duration)",
                    $clauses['join']
                );
            }
        }

        return $clauses;
    }

    /**
     * Clean up orphaned post meta
     */
    public static function cleanup_orphaned_meta() {
        global $wpdb;

        $deleted = $wpdb->query( "
            DELETE pm
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE '_ipv_%'
        " );

        IPV_Prod_Logger::log( "Cleaned up {$deleted} orphaned post meta entries" );

        return $deleted;
    }

    /**
     * Optimize database tables
     */
    public static function optimize_tables() {
        global $wpdb;

        $tables = [ $wpdb->posts, $wpdb->postmeta, $wpdb->term_relationships ];

        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE {$table}" );
        }

        IPV_Prod_Logger::log( 'Database tables optimized' );
    }

    /**
     * Get performance statistics
     */
    public static function get_performance_stats() {
        global $wpdb;

        $stats = [];

        // Check if indices exist
        $indices = $wpdb->get_results( "
            SHOW INDEX FROM {$wpdb->postmeta}
            WHERE Key_name IN ('idx_ipv_views', 'idx_ipv_duration', 'idx_ipv_video_id')
        " );

        $stats['indices_created'] = count( $indices );
        $stats['indices_expected'] = 3;

        // Get postmeta table size
        $table_size = $wpdb->get_row( "
            SELECT
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND table_name = '{$wpdb->postmeta}'
        " );

        $stats['postmeta_size_mb'] = $table_size->size_mb ?? 0;
        $stats['postmeta_rows'] = $table_size->table_rows ?? 0;

        // Count orphaned meta
        $orphaned = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE '_ipv_%'
        " );

        $stats['orphaned_meta'] = (int) $orphaned;

        // Average query time (from slow query log if available)
        $stats['avg_query_time'] = 'N/A'; // Would need slow query log access

        return $stats;
    }

    /**
     * Clear all caches
     */
    public static function clear_all_caches() {
        // Clear WordPress object cache
        wp_cache_flush();

        // Clear all ipv transients
        global $wpdb;
        $wpdb->query( "
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_ipv_%'
            OR option_name LIKE '_transient_timeout_ipv_%'
        " );

        IPV_Prod_Logger::log( 'All caches cleared' );
    }

    /**
     * Preload critical data (warmup cache)
     */
    public static function warmup_cache() {
        // Preload top 20 videos
        $top_videos = get_posts( [
            'post_type' => 'ipv_video',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'meta_value_num',
            'meta_key' => '_ipv_yt_view_count',
            'order' => 'DESC',
        ] );

        // Preload taxonomies
        get_terms( [
            'taxonomy' => 'ipv_categoria',
            'hide_empty' => true,
        ] );

        get_terms( [
            'taxonomy' => 'ipv_relatore',
            'hide_empty' => true,
        ] );

        IPV_Prod_Logger::log( 'Cache warmed up with critical data' );
    }
}

IPV_Prod_Performance::init();
