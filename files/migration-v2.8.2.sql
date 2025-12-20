-- =====================================================
-- IPV VENDOR v2.8.2 - MIGRATION SCRIPT
-- Aggiunge campi mancanti alla tabella ipv_licenses
-- =====================================================

-- 1. Aggiungi variant_slug
ALTER TABLE wp_ipv_licenses 
ADD COLUMN variant_slug VARCHAR(30) NULL AFTER plan;

-- 2. Aggiungi credits_remaining
ALTER TABLE wp_ipv_licenses 
ADD COLUMN credits_remaining INT NOT NULL DEFAULT 10 AFTER credits_used;

-- 3. Aggiungi credits_extra
ALTER TABLE wp_ipv_licenses 
ADD COLUMN credits_extra INT NOT NULL DEFAULT 0 AFTER credits_remaining;

-- 4. Aggiungi activation_limit
ALTER TABLE wp_ipv_licenses 
ADD COLUMN activation_limit INT NOT NULL DEFAULT 1 AFTER credits_extra;

-- 5. Aggiungi activation_count
ALTER TABLE wp_ipv_licenses 
ADD COLUMN activation_count INT NOT NULL DEFAULT 0 AFTER activation_limit;

-- 6. Aggiungi last_check
ALTER TABLE wp_ipv_licenses 
ADD COLUMN last_check DATETIME NULL AFTER expires_at;

-- 7. Aggiungi indice per variant_slug
ALTER TABLE wp_ipv_licenses 
ADD KEY variant_slug (variant_slug);

-- 8. Inizializza credits_remaining per licenze esistenti
UPDATE wp_ipv_licenses 
SET credits_remaining = credits_total - credits_used 
WHERE credits_remaining = 0;

-- 9. Verifica risultato
SELECT 
    license_key,
    plan,
    variant_slug,
    credits_total,
    credits_used,
    credits_remaining,
    credits_extra,
    activation_limit,
    activation_count,
    domain,
    status
FROM wp_ipv_licenses
LIMIT 5;

-- ✅ MIGRATION COMPLETATA!
