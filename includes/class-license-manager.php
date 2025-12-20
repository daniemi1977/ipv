<?php
defined('ABSPATH') || exit;

class IPV_Vendor_License_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Alias for get_instance() - for compatibility
     */
    /**
     * Normalize license key - support both formats
     * Format 1: XXXXX-XXXXX-XXXXX (3 segments)
     * Format 2: IPV-XXXXX-XXXXX-XXXXX-XXXXX (5 segments with IPV prefix)
     * 
     * @param string $license_key
     * @return array [key_variant1, key_variant2] to search
     */
    private function get_license_key_variants($license_key) {
        $key = strtoupper(trim($license_key));
        
        // Remove any whitespace
        $key = preg_replace('/\s+/', '', $key);
        
        $variants = array();
        
        // Variant 1: Key as-is
        $variants[] = $key;
        
        // Variant 2: If has IPV- prefix, try without it
        if (strpos($key, 'IPV-') === 0) {
            $without_ipv = substr($key, 4); // Remove "IPV-"
            $variants[] = $without_ipv;
            
            // Also try truncating to 3 segments if it has more
            $segments = explode('-', $without_ipv);
            if (count($segments) > 3) {
                $variants[] = implode('-', array_slice($segments, 0, 3));
            }
        }
        // Variant 3: If doesn't have IPV-, try with it
        else {
            $variants[] = 'IPV-' . $key;
        }
        
        // Remove duplicates
        return array_unique($variants);
    }
    
    public static function instance() {
        return self::get_instance();
    }

    /**
     * v1.0.7 - Validate license (alias for API endpoints)
     * Called by YouTube, Gateway, etc. endpoints
     * Returns license object if valid, or WP_Error if invalid
     *
     * @param string $license_key
     * @return object|WP_Error
     */
    public function validate_license( $license_key ) {
        global $wpdb;

        // Get all possible variants of this license key
        $variants = $this->get_license_key_variants( $license_key );

        // Build SQL to search for any variant
        $placeholders = implode( ', ', array_fill( 0, count( $variants ), '%s' ) );
        $sql = "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key IN ({$placeholders})";

        $license = $wpdb->get_row( $wpdb->prepare( $sql, ...$variants ) );

        if ( ! $license ) {
            return new WP_Error( 'license_not_found', 'Licenza non trovata', [ 'status' => 404 ] );
        }

        if ( $license->status !== 'active' ) {
            return new WP_Error( 'license_inactive', 'Licenza non attiva', [ 'status' => 403 ] );
        }

        if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
            return new WP_Error( 'license_expired', 'Licenza scaduta', [ 'status' => 403 ] );
        }

        return $license;
    }

    public function validate($license_key, $domain) {
        global $wpdb;
        
        // Get all possible variants of this license key
        $variants = $this->get_license_key_variants($license_key);
        
        // Build SQL to search for any variant
        $placeholders = implode(' OR license_key = ', array_fill(0, count($variants), '%s'));
        $sql = "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = {$placeholders}";
        
        $license = $wpdb->get_row($wpdb->prepare($sql, ...$variants));
        
        if (!$license) {
            error_log('[IPV Vendor] License not found. Tried variants: ' . implode(', ', $variants));
            return array('valid' => false, 'error' => 'not_found');
        }
        
        error_log('[IPV Vendor] License found: ' . $license->license_key . ' (searched: ' . $license_key . ')');
        
        if ($license->status !== 'active') {
            return array('valid' => false, 'error' => 'inactive');
        }
        
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return array('valid' => false, 'error' => 'expired');
        }
        
        $stored_domain = $this->normalize_domain($license->domain);
        $request_domain = $this->normalize_domain($domain);
        
        if (!empty($stored_domain) && $stored_domain !== $request_domain) {
            error_log('[IPV Vendor] Domain mismatch: stored=' . $stored_domain . ', request=' . $request_domain);
            return array('valid' => false, 'error' => 'domain_mismatch');
        }
        
        return array(
            'valid' => true,
            'license' => $license,
            'credits_total' => (int)$license->credits_total,
            'credits_used' => (int)$license->credits_used,
            'credits_remaining' => (int)($license->credits_total - $license->credits_used),
            'plan' => $license->plan
        );
    }
    
    private function normalize_domain($domain) {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');
        return $domain;
    }
    
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'plan' => 'trial',
            'variant_slug' => 'trial',
            'domain' => '',
            'credits_total' => 10,
            'customer_email' => '',
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Use generate_license_key() for consistent format
        $license_key = $this->generate_license_key();
        
        // ✅ Calculate expires_at (365 giorni, tranne trial)
        $variant_slug = $data['variant_slug'] ?: $data['plan'];
        $expires_at = null;
        
        if ( $variant_slug !== 'trial' ) {
            // Tutte le licenze tranne trial durano 365 giorni
            $expires_at = date( 'Y-m-d H:i:s', strtotime( '+365 days' ) );
        }
        // Trial: expires_at = NULL (nessuna scadenza)
        
        $insert_data = array(
            'license_key' => $license_key,
            'domain' => $data['domain'],
            'plan' => $data['plan'],
            'variant_slug' => $variant_slug,
            'credits_total' => $data['credits_total'],
            'credits_remaining' => $data['credits_total'], // ✅ Tutti i crediti insieme all'inizio
            'customer_email' => $data['customer_email'],
            'status' => 'active',
            'expires_at' => $expires_at,
        );
        
        $format = array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s');
        
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'ipv_licenses',
            $insert_data,
            $format
        );
        
        if ($inserted) {
            error_log( sprintf(
                '[IPV Vendor] License created: %s | Plan: %s | Credits: %d | Expires: %s',
                $license_key,
                $variant_slug,
                $data['credits_total'],
                $expires_at ? $expires_at : 'Never (Trial)'
            ));
            return $this->get_by_key($license_key);
        }
        
        return false;
    }
    
    public function get_by_key($license_key) {
        global $wpdb;
        
        // Get all possible variants of this license key
        $variants = $this->get_license_key_variants($license_key);
        
        // Build SQL to search for any variant
        $placeholders = implode(' OR license_key = ', array_fill(0, count($variants), '%s'));
        $sql = "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = {$placeholders}";
        
        return $wpdb->get_row($wpdb->prepare($sql, ...$variants));
    }
    
    /**
     * Deactivate license (clear domain binding)
     * 
     * @param string $license_key License key to deactivate
     * @param string $site_url Site URL requesting deactivation
     * @return array|WP_Error Result with success or error
     */
    public function deactivate($license_key, $site_url) {
        global $wpdb;
        
        // Get license
        $license = $this->get_by_key($license_key);
        
        if (!$license) {
            error_log('[IPV Vendor] Deactivate failed: License not found - ' . $license_key);
            return new WP_Error('not_found', 'License key non valida', array('status' => 401));
        }
        
        // Normalize domains for comparison
        $stored_domain = $this->normalize_domain($license->domain);
        $request_domain = $this->normalize_domain($site_url);
        
        // Verify the site requesting deactivation owns this license
        if (!empty($stored_domain) && $stored_domain !== $request_domain) {
            error_log('[IPV Vendor] Deactivate failed: Domain mismatch - stored=' . $stored_domain . ', request=' . $request_domain);
            return new WP_Error('domain_mismatch', 'Questo sito non ha autorizzazione per deattivare questa licenza', array('status' => 401));
        }
        
        // Clear domain binding (allow reactivation on another site)
        $updated = $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            array('domain' => ''), // Clear domain
            array('license_key' => $license_key),
            array('%s'),
            array('%s')
        );
        
        if ($updated !== false) {
            error_log('[IPV Vendor] License deactivated (domain cleared): ' . $license_key);
            return array(
                'success' => true,
                'message' => 'Licenza deattivata con successo',
                'license_key' => $license_key
            );
        }
        
        error_log('[IPV Vendor] Deactivate failed: Database error');
        return new WP_Error('database_error', 'Errore database durante deattivazione', array('status' => 500));
    }
    
    /**
     * Alias for deactivate() - for compatibility with endpoints
     */
    public function deactivate_license($license_key, $site_url) {
        return $this->deactivate($license_key, $site_url);
    }
    
    /**
     * Generate unique license key
     * 
     * @param string $format 'short' (3 segments) or 'long' (5 segments with IPV-)
     * @return string Generated license key
     */
    public function generate_license_key($format = 'short') {
        $segments = array();
        
        // Determine number of segments based on format
        $num_segments = ($format === 'long') ? 5 : 3;
        
        // Generate segments
        for ($i = 0; $i < $num_segments; $i++) {
            $segment = '';
            for ($j = 0; $j < 5; $j++) {
                // Use safe characters (no 0, O, I, 1 to avoid confusion)
                $segment .= strtoupper(substr('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', rand(0, 31), 1));
            }
            $segments[] = $segment;
        }
        
        // Build license key based on format
        if ($format === 'long') {
            $license_key = 'IPV-' . implode('-', $segments);
        } else {
            $license_key = implode('-', $segments);
        }
        
        // Check if key already exists (any variant)
        global $wpdb;
        $variants = $this->get_license_key_variants($license_key);
        $placeholders = implode(' OR license_key = ', array_fill(0, count($variants), '%s'));
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE license_key = {$placeholders}";
        $exists = $wpdb->get_var($wpdb->prepare($sql, ...$variants));
        
        // If exists (extremely rare), regenerate
        if ($exists > 0) {
            return $this->generate_license_key($format); // Recursive call
        }
        
        return $license_key;
    }
    
    public function get_all($status = '') {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}ipv_licenses";
        
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 100";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Activate license for a site
     * 
     * @param string $license_key License key
     * @param string $site_url Site URL to activate
     * @param string $site_name Site name (optional)
     * @param string $client_ip Client IP (optional)
     * @return object|WP_Error License object or error
     */
    public function activate_license( $license_key, $site_url, $site_name = '', $client_ip = '' ) {
        global $wpdb;

        // Get license
        $license = $this->get_by_key( $license_key );
        
        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License key non valida',
                [ 'status' => 404 ]
            );
        }

        // Check if license is active
        if ( $license->status !== 'active' ) {
            return new WP_Error(
                'license_inactive',
                'Licenza non attiva. Contatta il supporto.',
                [ 'status' => 403 ]
            );
        }

        // Check if license is expired
        if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
            return new WP_Error(
                'license_expired',
                'Licenza scaduta. Rinnova la tua licenza.',
                [ 'status' => 403 ]
            );
        }

        // Normalize domain
        $normalized_domain = $this->normalize_domain( $site_url );

        // Check if already activated on this domain
        if ( $license->domain === $normalized_domain ) {
            // Already activated on this domain - return success
            error_log( sprintf(
                '[IPV Vendor] License %s already activated for %s',
                substr( $license_key, 0, 8 ) . '...',
                $normalized_domain
            ));
            return $license;
        }

        // Check activation limit
        if ( ! empty( $license->domain ) && $license->domain !== $normalized_domain ) {
            $activation_limit = (int) ( $license->activation_limit ?? 1 );
            $activation_count = (int) ( $license->activation_count ?? 0 );
            
            if ( $activation_count >= $activation_limit ) {
                return new WP_Error(
                    'activation_limit_reached',
                    sprintf(
                        'Limite di attivazioni raggiunto (%d/%d). Deattiva la licenza su un altro sito prima di attivarla qui.',
                        $activation_count,
                        $activation_limit
                    ),
                    [ 'status' => 403 ]
                );
            }
        }

        // Activate license
        $updated = $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [
                'domain' => $normalized_domain,
                'activation_count' => ( (int) ( $license->activation_count ?? 0 ) ) + 1,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $license->id ],
            [ '%s', '%d', '%s' ],
            [ '%d' ]
        );

        if ( $updated === false ) {
            return new WP_Error(
                'activation_failed',
                'Errore durante l\'attivazione della licenza',
                [ 'status' => 500 ]
            );
        }

        // Log activation
        error_log( sprintf(
            '[IPV Vendor] License %s activated for %s (IP: %s)',
            substr( $license_key, 0, 8 ) . '...',
            $normalized_domain,
            $client_ip ?: 'unknown'
        ));

        // Return updated license
        return $this->get_by_key( $license_key );
    }

    /**
     * Create license from WooCommerce order
     * 
     * @param int $order_id Order ID
     * @param int $product_id Product ID
     * @return object|false License object or false on failure
     */
    public function create_license_from_order( $order_id, $product_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            error_log( '[IPV Vendor] Order not found: ' . $order_id );
            return false;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            error_log( '[IPV Vendor] Product not found: ' . $product_id );
            return false;
        }

        // Get product metadata
        $variant_slug = get_post_meta( $product_id, '_ipv_variant_slug', true );
        $credits_total = (int) get_post_meta( $product_id, '_ipv_credits_total', true );
        
        if ( empty( $variant_slug ) ) {
            $variant_slug = 'trial'; // Default
        }
        
        if ( empty( $credits_total ) ) {
            // Default credits based on variant - PREZZI REALI
            $credits_map = [
                'trial' => 5,
                'basic' => 300,
                'pro' => 600,
                'business' => 1200,
                'enterprise' => 1800,
            ];
            $credits_total = $credits_map[ $variant_slug ] ?? 5;
        }

        // Create license data
        $license_data = [
            'plan' => 'pro', // Base plan
            'variant_slug' => $variant_slug,
            'credits_total' => $credits_total,
            'customer_email' => $order->get_billing_email(),
            'domain' => '', // Sarà impostato al primo uso
        ];

        $license = $this->create( $license_data );

        if ( $license ) {
            // Save order metadata
            $order->update_meta_data( '_ipv_license_key', $license->license_key );
            $order->save();

            error_log( sprintf(
                '[IPV Vendor] License created from order %d: %s | Variant: %s | Credits: %d | Expires: %s',
                $order_id,
                $license->license_key,
                $variant_slug,
                $credits_total,
                $license->expires_at ? $license->expires_at : 'Never (Trial)'
            ));

            return $license;
        }

        return false;
    }
}
