<?php
/**
 * IPV Vendor - Golden Prompt Manager
 * Gestisce licenze e contenuto Golden Prompt
 *
 * @package IPV_Vendor
 * @version 2.1.0
 */

defined('ABSPATH') || exit;

class IPV_Vendor_Golden_Prompt_Manager {

    /**
     * Encryption key (cambia in produzione!)
     */
    private static $encryption_key = 'ipv-golden-prompt-secret-key-2025'; // TODO: Usa wp_salt() in produzione

    /**
     * Generate new Golden Prompt license
     */
    public static function generate_license( $domain, $customer_email, $golden_prompt_content = '', $expires_at = null, $has_golden_prompt = 1 ) {
        global $wpdb;

        // Genera license key univoca
        $license_key = self::generate_license_key();

        // Cripta Golden Prompt content
        $encrypted_content = self::encrypt_golden_prompt( $golden_prompt_content );

        // Insert nel database
        $result = $wpdb->insert(
            $wpdb->prefix . 'ipv_golden_prompt_licenses',
            [
                'license_key' => $license_key,
                'domain' => $domain,
                'status' => 'active',
                'customer_email' => $customer_email,
                'golden_prompt_content' => $encrypted_content,
                'has_golden_prompt' => $has_golden_prompt,
                'expires_at' => $expires_at,
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
        );

        if ( ! $result ) {
            error_log( '[IPV Vendor] Failed to generate Golden Prompt license: ' . $wpdb->last_error );
            return false;
        }

        error_log( "[IPV Vendor] Golden Prompt license generated: {$license_key} for {$domain}" );

        return $license_key;
    }

    /**
     * Generate unique license key format: XXXX-XXXX-XXXX-XXXX
     */
    private static function generate_license_key() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I, O, 0, 1
        $segments = [];

        for ( $i = 0; $i < 4; $i++ ) {
            $segment = '';
            for ( $j = 0; $j < 4; $j++ ) {
                $segment .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
            }
            $segments[] = $segment;
        }

        return implode( '-', $segments );
    }

    /**
     * Verify Golden Prompt license
     */
    public static function verify_license( $license_key, $domain ) {
        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_licenses 
             WHERE license_key = %s AND status = 'active'",
            $license_key
        ) );

        if ( ! $license ) {
            error_log( "[IPV Vendor] Golden Prompt license not found or inactive: {$license_key}" );
            return false;
        }

        // Check domain match
        if ( $license->domain !== $domain ) {
            error_log( "[IPV Vendor] Golden Prompt domain mismatch. Expected: {$license->domain}, Got: {$domain}" );
            return false;
        }

        // Check expiration
        if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
            error_log( "[IPV Vendor] Golden Prompt license expired: {$license_key}" );
            
            // Auto-suspend expired licenses
            $wpdb->update(
                $wpdb->prefix . 'ipv_golden_prompt_licenses',
                [ 'status' => 'expired' ],
                [ 'license_key' => $license_key ],
                [ '%s' ],
                [ '%s' ]
            );
            
            return false;
        }

        error_log( "[IPV Vendor] Golden Prompt license verified: {$license_key} for {$domain}" );

        return true;
    }

    /**
     * Fetch Golden Prompt content
     */
    public static function fetch_golden_prompt( $license_key ) {
        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT golden_prompt_content, has_golden_prompt FROM {$wpdb->prefix}ipv_golden_prompt_licenses 
             WHERE license_key = %s AND status = 'active'",
            $license_key
        ) );

        if ( ! $license ) {
            error_log( "[IPV Vendor] Golden Prompt license not found: {$license_key}" );
            return '';
        }
        
        // ✅ CHECK SWITCH: has_golden_prompt must be ON
        if ( empty( $license->has_golden_prompt ) ) {
            error_log( "[IPV Vendor] Golden Prompt disabled for license: {$license_key}" );
            return '';
        }
        
        if ( empty( $license->golden_prompt_content ) ) {
            error_log( "[IPV Vendor] Golden Prompt content empty for license: {$license_key}" );
            return '';
        }

        // Decripta content
        $decrypted = self::decrypt_golden_prompt( $license->golden_prompt_content );

        error_log( "[IPV Vendor] Golden Prompt content fetched for license: {$license_key}" );

        return $decrypted;
    }

    /**
     * Update Golden Prompt content for a license
     */
    public static function update_golden_prompt_content( $license_key, $new_content ) {
        global $wpdb;

        // Cripta nuovo content
        $encrypted = self::encrypt_golden_prompt( $new_content );

        $result = $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_licenses',
            [ 'golden_prompt_content' => $encrypted ],
            [ 'license_key' => $license_key ],
            [ '%s' ],
            [ '%s' ]
        );

        if ( $result === false ) {
            error_log( "[IPV Vendor] Failed to update Golden Prompt content for: {$license_key}" );
            return false;
        }

        error_log( "[IPV Vendor] Golden Prompt content updated for: {$license_key}" );

        return true;
    }

    /**
     * Get all Golden Prompt licenses
     */
    public static function get_all_licenses( $status = 'active' ) {
        global $wpdb;

        $where = '';
        if ( $status ) {
            $where = $wpdb->prepare( "WHERE status = %s", $status );
        }

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_licenses {$where} ORDER BY created_at DESC"
        );
    }

    /**
     * Get license by key
     */
    public static function get_license( $license_key ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_licenses WHERE license_key = %s",
            $license_key
        ) );
    }

    /**
     * Delete license
     */
    public static function delete_license( $license_key ) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'ipv_golden_prompt_licenses',
            [ 'license_key' => $license_key ],
            [ '%s' ]
        );
    }

    /**
     * Update license status
     */
    public static function update_license_status( $license_key, $status ) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_licenses',
            [ 'status' => $status ],
            [ 'license_key' => $license_key ],
            [ '%s' ],
            [ '%s' ]
        );
    }

    /**
     * Encrypt Golden Prompt content
     */
    private static function encrypt_golden_prompt( $content ) {
        if ( empty( $content ) ) {
            return '';
        }

        // Simple encryption (usa OpenSSL in produzione!)
        $key = self::$encryption_key;
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
        $encrypted = openssl_encrypt( $content, 'aes-256-cbc', $key, 0, $iv );
        
        // Combina IV + encrypted data
        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt Golden Prompt content
     */
    private static function decrypt_golden_prompt( $encrypted_content ) {
        if ( empty( $encrypted_content ) ) {
            return '';
        }

        try {
            $key = self::$encryption_key;
            $data = base64_decode( $encrypted_content );
            
            $iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
            $iv = substr( $data, 0, $iv_length );
            $encrypted = substr( $data, $iv_length );
            
            $decrypted = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, 0, $iv );
            
            return $decrypted !== false ? $decrypted : '';
        } catch ( Exception $e ) {
            error_log( '[IPV Vendor] Decryption error: ' . $e->getMessage() );
            return '';
        }
    }

    /**
     * Get stats
     */
    public static function get_stats() {
        global $wpdb;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_licenses" );
        $active = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_licenses WHERE status = 'active'" );
        $expired = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_licenses WHERE status = 'expired'" );
        $enabled = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_licenses WHERE has_golden_prompt = 1" );

        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'expired' => (int) $expired,
            'enabled' => (int) $enabled,
        ];
    }
    
    /**
     * ✅ Toggle Golden Prompt ON/OFF per singola licenza
     */
    public static function toggle_golden_prompt( $license_key, $enabled ) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_licenses',
            [ 'has_golden_prompt' => $enabled ? 1 : 0 ],
            [ 'license_key' => $license_key ],
            [ '%d' ],
            [ '%s' ]
        );
        
        $status = $enabled ? 'ENABLED' : 'DISABLED';
        error_log( "[IPV Vendor] Golden Prompt {$status} for license: {$license_key}" );
        
        return $result !== false;
    }
    
    /**
     * ✅ Check if license has Golden Prompt enabled
     */
    public static function has_golden_prompt_enabled( $license_key ) {
        global $wpdb;
        
        $enabled = $wpdb->get_var( $wpdb->prepare(
            "SELECT has_golden_prompt FROM {$wpdb->prefix}ipv_golden_prompt_licenses 
             WHERE license_key = %s",
            $license_key
        ) );
        
        return ! empty( $enabled );
    }
    
    // ═══════════════════════════════════════════════════════════════════
    // MASTER TEMPLATE MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════
    
    /**
     * Get active master template
     */
    public static function get_master_template() {
        global $wpdb;
        
        return $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_master 
             WHERE is_active = 1 
             ORDER BY created_at DESC 
             LIMIT 1"
        );
    }
    
    /**
     * Save new master template (crea nuova versione)
     */
    public static function save_master_template( $content, $created_by = '' ) {
        global $wpdb;
        
        // Disattiva vecchie versioni
        $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_master',
            [ 'is_active' => 0 ],
            [ 'is_active' => 1 ],
            [ '%d' ],
            [ '%d' ]
        );
        
        // Genera version number
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_master" );
        $version = 'v' . ( $count + 1 ) . '.0';
        
        // Insert nuova versione
        $result = $wpdb->insert(
            $wpdb->prefix . 'ipv_golden_prompt_master',
            [
                'version' => $version,
                'content' => $content, // NON criptato (è template)
                'is_active' => 1,
                'created_by' => $created_by,
            ],
            [ '%s', '%s', '%d', '%s' ]
        );
        
        if ( $result ) {
            error_log( "[IPV Vendor] Master template saved: {$version}" );
            return $version;
        }
        
        return false;
    }
    
    /**
     * Get all versions (history)
     */
    public static function get_master_history( $limit = 10 ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_master 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ) );
    }
    
    /**
     * Restore version (set as active)
     */
    public static function restore_master_version( $version ) {
        global $wpdb;
        
        // Disattiva tutte
        $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_master',
            [ 'is_active' => 0 ],
            [ 'is_active' => 1 ],
            [ '%d' ],
            [ '%d' ]
        );
        
        // Attiva versione selezionata
        return $wpdb->update(
            $wpdb->prefix . 'ipv_golden_prompt_master',
            [ 'is_active' => 1 ],
            [ 'version' => $version ],
            [ '%d' ],
            [ '%s' ]
        );
    }
    
    /**
     * Push master template to all active licenses
     */
    public static function push_master_to_all_licenses() {
        global $wpdb;
        
        // Get master template
        $master = self::get_master_template();
        
        if ( ! $master ) {
            error_log( '[IPV Vendor] No active master template found' );
            return false;
        }
        
        // ✅ Get all active licenses WITH Golden Prompt enabled
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_golden_prompt_licenses 
             WHERE status = 'active' AND has_golden_prompt = 1"
        );
        
        if ( empty( $licenses ) ) {
            error_log( '[IPV Vendor] No active licenses with Golden Prompt enabled' );
            return 0;
        }
        
        $updated = 0;
        
        foreach ( $licenses as $license ) {
            $result = self::update_golden_prompt_content( $license->license_key, $master->content );
            if ( $result ) {
                $updated++;
            }
        }
        
        error_log( "[IPV Vendor] Master template pushed to {$updated} licenses (Golden Prompt enabled only)" );
        
        return $updated;
    }
    
    /**
     * Get master stats
     */
    public static function get_master_stats() {
        global $wpdb;
        
        $master = self::get_master_template();
        $versions_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_golden_prompt_master" );
        
        return [
            'has_master' => ! empty( $master ),
            'current_version' => $master ? $master->version : 'N/A',
            'total_versions' => (int) $versions_count,
            'last_updated' => $master ? $master->created_at : null,
        ];
    }
}
