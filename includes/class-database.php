<?php
defined('ABSPATH') || exit;

class IPV_Vendor_Database {
    
    public function create_tables() {
        global $wpdb;
        
        $charset = $wpdb->get_charset_collate();
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_licenses (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key VARCHAR(64) NOT NULL,
            domain VARCHAR(191) NOT NULL,
            plan VARCHAR(30) NOT NULL DEFAULT 'trial',
            variant_slug VARCHAR(30) NULL,
            status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active',
            credits_total INT NOT NULL DEFAULT 10,
            credits_used INT NOT NULL DEFAULT 0,
            credits_remaining INT NOT NULL DEFAULT 10,
            credits_extra INT NOT NULL DEFAULT 0,
            activation_limit INT NOT NULL DEFAULT 1,
            activation_count INT NOT NULL DEFAULT 0,
            customer_email VARCHAR(191) NULL,
            expires_at DATETIME NULL,
            last_check DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY domain (domain),
            KEY status (status),
            KEY variant_slug (variant_slug)
        ) {$charset};";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_ledger (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            video_id VARCHAR(20) NULL,
            credits INT NOT NULL,
            balance_after INT NOT NULL,
            request_id VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY created_at (created_at)
        ) {$charset};";
        
        $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_cache (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            video_id VARCHAR(20) NOT NULL,
            type VARCHAR(20) NOT NULL,
            data LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY video_type (video_id, type),
            KEY created_at (created_at)
        ) {$charset};";
        
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        
        // ✅ NEW: Golden Prompt Licenses Table
        $sql4 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_golden_prompt_licenses (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key VARCHAR(64) NOT NULL,
            domain VARCHAR(191) NOT NULL,
            status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active',
            customer_email VARCHAR(191) NULL,
            golden_prompt_content LONGTEXT NULL,
            has_golden_prompt TINYINT(1) NOT NULL DEFAULT 1,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY domain (domain),
            KEY status (status),
            KEY has_golden_prompt (has_golden_prompt)
        ) {$charset};";
        
        dbDelta($sql4);
        
        // ✅ NEW: Golden Prompt Master Template Table
        $sql5 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_golden_prompt_master (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version VARCHAR(20) NOT NULL,
            content LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_by VARCHAR(191) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY created_at (created_at)
        ) {$charset};";
        
        dbDelta($sql5);
        
        update_option('ipv_vendor_db_version', IPV_VENDOR_VERSION);
        
        error_log('[IPV Vendor] Database tables created');
    }
}
