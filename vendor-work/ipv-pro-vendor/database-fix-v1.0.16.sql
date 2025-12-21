-- ============================================
-- IPV Pro Vendor v1.0.16 - Database Fix
-- REGOLA: 1 dominio = 1 licenza
-- ============================================

-- 1. BACKUP - Crea tabella storico licenze (esegui PRIMA di tutto)
CREATE TABLE IF NOT EXISTS iel_ipv_licenses_history LIKE iel_ipv_licenses;

-- 2. SALVA SNAPSHOT attuale
INSERT INTO iel_ipv_licenses_history SELECT * FROM iel_ipv_licenses;

-- 3. VERIFICA DUPLICATI (esegui questa query prima del passo 4)
-- SELECT domain, COUNT(*) as count FROM iel_ipv_licenses GROUP BY domain HAVING count > 1;

-- 4. RIMUOVI DUPLICATI (mantiene solo l'ultimo per dominio)
-- ATTENZIONE: Esegui SOLO se hai verificato i duplicati al passo 3
-- DELETE l1 FROM iel_ipv_licenses l1
-- INNER JOIN iel_ipv_licenses l2 
-- WHERE l1.domain = l2.domain 
-- AND l1.id < l2.id;

-- 5. AGGIUNGI UNIQUE KEY (dopo aver rimosso duplicati)
-- NOTA: Se fallisce, significa che ci sono ancora duplicati
-- ALTER TABLE iel_ipv_licenses ADD UNIQUE KEY uniq_domain (domain);

-- 6. VERIFICA CONSTRAINT
-- SHOW INDEX FROM iel_ipv_licenses WHERE Key_name = 'uniq_domain';

-- ============================================
-- NOTE IMPORTANTI
-- ============================================
-- 
-- La UNIQUE KEY su `domain` garantisce che:
-- - MySQL impedisce INSERT di licenze con dominio già esistente
-- - Il codice PHP ora usa UPDATE invece di INSERT per domini esistenti
-- - Comportamento deterministico: 1 dominio = 1 licenza sempre
--
-- COMPORTAMENTO DOPO IL FIX:
-- - Nuova licenza per dominio senza licenza → INSERT
-- - Nuova licenza per dominio con licenza → UPDATE (sovrascrive)
-- - La license_key CAMBIA solo se crei una licenza senza dominio
-- - La license_key RESTA se aggiorni una licenza esistente
--
-- ============================================
