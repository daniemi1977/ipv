<?php
/**
 * IPV Production System Pro - Logger
 * 
 * @version 9.1.0
 * @deprecated Use IPV_Prod_Helpers::log() directly
 * 
 * Questa classe è mantenuta per retrocompatibilità.
 * Le nuove implementazioni dovrebbero usare IPV_Prod_Helpers::log()
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Logger {

    /**
     * Log message if WP_DEBUG is enabled
     *
     * @param string $message Message to log
     * @param array  $context Additional context
     * 
     * @deprecated Use IPV_Prod_Helpers::log()
     */
    public static function log( $message, $context = [] ) {
        IPV_Prod_Helpers::log( $message, $context );
    }
}
