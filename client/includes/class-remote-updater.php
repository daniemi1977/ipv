<?php
/**
 * IPV Remote Updater
 *
 * Gestisce gli update automatici dal server bissolomarket.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Remote_Updater {

    private static $instance = null;

    const PLUGIN_SLUG = 'ipv-production-system-pro';
    const PLUGIN_FILE = 'ipv-production-system-pro/ipv-production-system-pro.php';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into WordPress update system
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );

        // Check updates ogni 12 ore
        add_action( 'init', [ $this, 'schedule_update_check' ] );
        add_action( 'ipv_check_updates', [ $this, 'force_update_check' ] );
    }

    /**
     * Schedule update check
     */
    public function schedule_update_check() {
        if ( ! wp_next_scheduled( 'ipv_check_updates' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'ipv_check_updates' );
        }
    }

    /**
     * Check for updates
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $api_client = IPV_Prod_API_Client_Optimized::instance();
        $current_version = IPV_PROD_VERSION;

        $update_info = $api_client->check_update( $current_version );

        if ( ! $update_info || is_wp_error( $update_info ) ) {
            return $transient;
        }

        if ( ! $update_info['update_available'] ) {
            return $transient;
        }

        // Add update to transient
        $plugin_data = [
            'slug' => self::PLUGIN_SLUG,
            'plugin' => self::PLUGIN_FILE,
            'new_version' => $update_info['latest_version'],
            'url' => 'https://bissolomarket.com/ipv-pro/',
            'package' => $update_info['download_url'],
            'tested' => '6.4',
            'requires_php' => '8.0',
            'compatibility' => new stdClass()
        ];

        $transient->response[ self::PLUGIN_FILE ] = (object) $plugin_data;

        return $transient;
    }

    /**
     * Get plugin info for details screen
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        if ( $args->slug !== self::PLUGIN_SLUG ) {
            return $result;
        }

        $api_client = IPV_Prod_API_Client_Optimized::instance();
        $plugin_info = $api_client->get_plugin_info();

        if ( ! $plugin_info || is_wp_error( $plugin_info ) ) {
            return $result;
        }

        // Convert to object format WordPress expects
        $result = (object) [
            'name' => $plugin_info['name'] ?? 'IPV Production System Pro',
            'slug' => self::PLUGIN_SLUG,
            'version' => $plugin_info['version'] ?? IPV_PROD_VERSION,
            'author' => $plugin_info['author'] ?? 'Daniele Bissoli',
            'author_profile' => $plugin_info['author_profile'] ?? 'https://example.com',
            'requires' => $plugin_info['requires'] ?? '6.0',
            'tested' => $plugin_info['tested'] ?? '6.4',
            'requires_php' => $plugin_info['requires_php'] ?? '8.0',
            'download_link' => $plugin_info['download_url'] ?? '',
            'last_updated' => date_i18n( 'd/m/Y', $plugin_info['last_updated'] ?? time() ),
            'sections' => [
                'description' => $plugin_info['sections']['description'] ?? 'Sistema professionale per automatizzare la produzione di video YouTube',
                'changelog' => $plugin_info['sections']['changelog'] ?? 'Nessuna nota di rilascio'
            ],
            'banners' => $plugin_info['banners'] ?? [],
            'icons' => $plugin_info['icons'] ?? []
        ];

        return $result;
    }

    /**
     * Force update check
     */
    public function force_update_check() {
        delete_site_transient( 'update_plugins' );
    }
}
