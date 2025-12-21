-- IPV Pro Vendor Database Schema
-- Generated: 2024-12-06
-- WordPress tables prefix: wp_ (adjust as needed)

-- ====================
-- TABLE: Licenses
-- ====================
CREATE TABLE IF NOT EXISTS `wp_ipv_licenses` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_key` VARCHAR(100) UNIQUE NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NOT NULL,
    `product_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'active',
    `variant_slug` VARCHAR(50) NOT NULL,
    `credits_total` INT UNSIGNED NOT NULL DEFAULT 10,
    `credits_remaining` INT UNSIGNED NOT NULL DEFAULT 10,
    `credits_reset_date` DATE NOT NULL,
    `activation_limit` INT UNSIGNED DEFAULT 1,
    `activation_count` INT UNSIGNED DEFAULT 0,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_license_key` (`license_key`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TABLE: Activations
-- ====================
CREATE TABLE IF NOT EXISTS `wp_ipv_activations` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` BIGINT(20) UNSIGNED NOT NULL,
    `site_url` VARCHAR(255) NOT NULL,
    `site_name` VARCHAR(255),
    `ip_address` VARCHAR(45),
    `activated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_checked_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT 1,
    INDEX `idx_license_id` (`license_id`),
    INDEX `idx_site_url` (`site_url`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TABLE: API Logs
-- ====================
CREATE TABLE IF NOT EXISTS `wp_ipv_api_logs` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` BIGINT(20) UNSIGNED,
    `endpoint` VARCHAR(100) NOT NULL,
    `video_id` VARCHAR(50),
    `method` VARCHAR(10) NOT NULL,
    `status_code` INT UNSIGNED,
    `response_time` INT UNSIGNED,
    `credits_used` INT UNSIGNED DEFAULT 0,
    `cached` TINYINT(1) DEFAULT 0,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_license_id` (`license_id`),
    INDEX `idx_endpoint` (`endpoint`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_video_id` (`video_id`),
    INDEX `idx_status_code` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TABLE: Transcript Cache
-- ====================
CREATE TABLE IF NOT EXISTS `wp_ipv_transcript_cache` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `video_id` VARCHAR(50) NOT NULL,
    `mode` VARCHAR(20) NOT NULL,
    `lang` VARCHAR(10) NOT NULL,
    `transcript` MEDIUMTEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_video` (`video_id`, `mode`, `lang`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TABLE: Usage Stats
-- ====================
CREATE TABLE IF NOT EXISTS `wp_ipv_usage_stats` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `license_id` BIGINT(20) UNSIGNED,
    `transcripts_count` INT UNSIGNED DEFAULT 0,
    `descriptions_count` INT UNSIGNED DEFAULT 0,
    `credits_used` INT UNSIGNED DEFAULT 0,
    `cache_hits` INT UNSIGNED DEFAULT 0,
    UNIQUE KEY `unique_daily` (`date`, `license_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_license_id` (`license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- Sample Data (for testing)
-- ====================

-- Insert test license (adjust as needed)
-- INSERT INTO `wp_ipv_licenses` (
--     `license_key`, `order_id`, `product_id`, `user_id`, `email`,
--     `variant_slug`, `credits_total`, `credits_remaining`,
--     `credits_reset_date`, `activation_limit`
-- ) VALUES (
--     'TEST-1234-5678-9ABC', 1, 1, 1, 'test@example.com',
--     'starter', 25, 25, DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 1
-- );

-- ====================
-- Useful Queries
-- ====================

-- Check all active licenses
-- SELECT * FROM `wp_ipv_licenses` WHERE `status` = 'active';

-- Check licenses expiring soon
-- SELECT * FROM `wp_ipv_licenses`
-- WHERE `expires_at` IS NOT NULL
-- AND `expires_at` < DATE_ADD(NOW(), INTERVAL 7 DAY);

-- Check licenses with low credits
-- SELECT * FROM `wp_ipv_licenses`
-- WHERE `credits_remaining` < (`credits_total` * 0.1)
-- AND `status` = 'active';

-- API calls last 24 hours
-- SELECT COUNT(*) FROM `wp_ipv_api_logs`
-- WHERE `created_at` > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Cache hit rate
-- SELECT
--     SUM(CASE WHEN `cached` = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100 as cache_hit_rate
-- FROM `wp_ipv_api_logs`
-- WHERE `endpoint` = 'transcript';

-- ====================
-- Maintenance
-- ====================

-- Clear old cache (older than 30 days)
-- DELETE FROM `wp_ipv_transcript_cache`
-- WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Clear old API logs (older than 90 days)
-- DELETE FROM `wp_ipv_api_logs`
-- WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);
