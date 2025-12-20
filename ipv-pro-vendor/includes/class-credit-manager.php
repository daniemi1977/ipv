<?php
defined('ABSPATH') || exit;

class IPV_Vendor_Credit_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function deduct($license_id, $action, $credits = 1, $metadata = array()) {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $license = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d FOR UPDATE",
                $license_id
            ));
            
            if (!$license) {
                throw new Exception('License not found');
            }
            
            $new_used = $license->credits_used + $credits;
            $balance = $license->credits_total - $new_used;
            
            if ($balance < 0) {
                throw new Exception('Insufficient credits');
            }
            
            $updated = $wpdb->update(
                $wpdb->prefix . 'ipv_licenses',
                array('credits_used' => $new_used),
                array('id' => $license_id),
                array('%d'),
                array('%d')
            );
            
            if ($updated === false) {
                throw new Exception('Update failed');
            }
            
            $wpdb->insert(
                $wpdb->prefix . 'ipv_ledger',
                array(
                    'license_id' => $license_id,
                    'action' => $action,
                    'video_id' => isset($metadata['video_id']) ? $metadata['video_id'] : null,
                    'credits' => $credits,
                    'balance_after' => $balance,
                    'request_id' => isset($metadata['request_id']) ? $metadata['request_id'] : uniqid('req_')
                ),
                array('%d', '%s', '%s', '%d', '%d', '%s')
            );
            
            $wpdb->query('COMMIT');
            
            error_log('[IPV Vendor] Credits deducted: license_id=' . $license_id . ', action=' . $action . ', balance=' . $balance);
            
            return array(
                'success' => true,
                'balance' => $balance,
                'used' => $new_used,
                'total' => $license->credits_total
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            error_log('[IPV Vendor] Credit deduction failed: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    public function has_credits($license_id, $needed = 1) {
        global $wpdb;
        
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT credits_total, credits_used FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));
        
        if (!$license) return false;
        
        $available = $license->credits_total - $license->credits_used;
        
        return $available >= $needed;
    }
}
