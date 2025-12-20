-- IPV Pro Vendor - Database Repair Script v1.6.1
-- Esegui questo script in phpMyAdmin o via WP-CLI per riparare il database esistente
-- NOTA: Sostituisci 'iel_' con il prefisso del tuo database se diverso

-- =====================================================
-- 1. AGGIUNGI COLONNE MANCANTI A ipv_licenses
-- =====================================================

-- Aggiungi order_id se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'order_id') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER license_key'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi product_id se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'product_id') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER order_id'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi user_id se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'user_id') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER product_id'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi variant_slug se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'variant_slug') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN variant_slug VARCHAR(50) NOT NULL DEFAULT ''starter'' AFTER status'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi credits_monthly se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'credits_monthly') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN credits_monthly INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_total'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi credits_extra se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'credits_extra') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN credits_extra INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_monthly'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi credits_used_month se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'credits_used_month') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN credits_used_month INT UNSIGNED NOT NULL DEFAULT 0 AFTER credits_extra'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi activation_limit se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'activation_limit') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN activation_limit INT UNSIGNED DEFAULT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi activation_count se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'activation_count') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN activation_count INT UNSIGNED DEFAULT 0'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi site_url se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'site_url') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN site_url VARCHAR(255) NULL'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi site_unlock_at se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'site_unlock_at') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN site_unlock_at DATETIME NULL'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi expires_at se mancante
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iel_ipv_licenses' AND COLUMN_NAME = 'expires_at') > 0,
    'SELECT 1',
    'ALTER TABLE iel_ipv_licenses ADD COLUMN expires_at DATETIME NULL'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 2. CREA TABELLA ipv_rate_limits SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_rate_limits (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier varchar(191) NOT NULL,
    endpoint varchar(100) NOT NULL,
    request_count int(11) NOT NULL DEFAULT 1,
    window_start datetime NOT NULL,
    PRIMARY KEY (id),
    KEY identifier_endpoint (identifier, endpoint),
    KEY window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CREA TABELLA ipv_audit_log SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_audit_log (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type varchar(50) NOT NULL,
    event_action varchar(50) NOT NULL,
    user_id bigint(20) UNSIGNED DEFAULT 0,
    user_email varchar(255) DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    object_type varchar(50) DEFAULT NULL,
    object_id varchar(100) DEFAULT NULL,
    metadata longtext DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY event_type (event_type),
    KEY user_id (user_id),
    KEY object_type_id (object_type, object_id),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CREA TABELLA ipv_credit_ledger SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_credit_ledger (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_key varchar(64) NOT NULL,
    type varchar(32) NOT NULL,
    amount int NOT NULL,
    balance_after int NOT NULL,
    ref_type varchar(32) NULL,
    ref_id varchar(64) NULL,
    note text NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_license (license_key),
    KEY idx_type (type),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CREA TABELLA ipv_activations SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_activations (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id bigint(20) UNSIGNED NOT NULL,
    site_url varchar(255) NOT NULL,
    site_name varchar(255) NULL,
    ip_address varchar(45) NULL,
    activated_at datetime DEFAULT CURRENT_TIMESTAMP,
    last_checked_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY license_id (license_id),
    KEY site_url (site_url),
    KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. CREA TABELLA ipv_golden_prompts SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_golden_prompts (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id bigint(20) UNSIGNED NOT NULL,
    config_json longtext NULL,
    golden_prompt longtext NOT NULL,
    is_active tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_license (license_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. CREA TABELLA ipv_transcript_cache SE NON ESISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS iel_ipv_transcript_cache (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    video_id varchar(50) NOT NULL,
    mode varchar(20) NOT NULL,
    lang varchar(10) NOT NULL,
    transcript mediumtext NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_video (video_id, mode, lang),
    KEY created_at (created_at),
    KEY video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. AGGIORNA VERSIONE DB
-- =====================================================

-- In WordPress, esegui anche:
-- UPDATE iel_options SET option_value = '1.6.1' WHERE option_name = 'ipv_vendor_db_version';

SELECT 'Database repair completed successfully!' AS status;
