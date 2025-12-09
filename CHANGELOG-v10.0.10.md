# 🔧 IPV Production System Pro - Client v10.0.10

**Data**: 9 Dicembre 2024
**Tipo**: Bug Fix (Critical)
**Compatibilità**: Server v1.3.4+

---

## 🐛 Bug Fix

### ✅ RISOLTO: Errore "Table 'wp_ipv_prod_queue' doesn't exist"

**Problema**:
Utenti che hanno aggiornato da versioni precedenti a v10.0.7 (prima dell'introduzione della queue) **senza disattivare/riattivare** il plugin ricevevano errori continui:

```
WordPress database error Table 'wp_ipv_prod_queue' doesn't exist
for query SELECT * FROM wp_ipv_prod_queue WHERE status = 'pending'
```

**Causa**:
La tabella `wp_ipv_prod_queue` veniva creata solo durante l'**attivazione del plugin** (hook `register_activation_hook`). Se l'utente aggiornava da una versione precedente senza riattivare il plugin, la tabella non veniva mai creata.

**Soluzione**:
- ✅ Aggiunto controllo automatico in `admin_init` che verifica se la tabella esiste
- ✅ Se mancante, la crea automaticamente usando `IPV_Prod_Queue::create_table()`
- ✅ Il controllo viene eseguito **una sola volta per versione** per evitare query ripetute
- ✅ Completamente trasparente per l'utente - zero intervento richiesto

---

## 📝 Modifiche Tecniche

### File: `ipv-production-system-pro.php`

**Nuovo Hook (Line 235)**:
```php
// v10.0.10 - Auto-create queue table if missing (for upgrades from pre-v10.0.7)
add_action( 'admin_init', [ $this, 'ensure_queue_table_exists' ] );
```

**Nuovo Metodo (Lines 626-656)**:
```php
/**
 * v10.0.10 - Ensure queue table exists (auto-create on upgrade)
 * Fixes issue where users upgrading from pre-v10.0.7 don't have the table
 */
public function ensure_queue_table_exists() {
    // Only check once per version to avoid unnecessary DB queries
    $checked_version = get_option( 'ipv_queue_table_checked', '' );
    if ( $checked_version === IPV_PROD_VERSION ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ipv_prod_queue';

    // Check if table exists
    $table_exists = $wpdb->get_var( $wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table_name
    ) );

    if ( ! $table_exists && class_exists( 'IPV_Prod_Queue' ) ) {
        IPV_Prod_Queue::create_table();

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[IPV Production] Auto-created missing queue table during upgrade to v' . IPV_PROD_VERSION );
        }
    }

    // Mark as checked for this version
    update_option( 'ipv_queue_table_checked', IPV_PROD_VERSION );
}
```

---

## 🎯 Impatto

### Prima (v10.0.9):
```
❌ Errori continui nel log ogni 5 minuti
❌ Queue page vuota o con errori
❌ Bulk import potenzialmente non funzionante
❌ Richiede intervento manuale: disattivare/riattivare plugin o creare tabella via SQL
```

### Dopo (v10.0.10):
```
✅ Tabella creata automaticamente al primo accesso all'admin
✅ Nessun errore nel log
✅ Queue funzionante immediatamente
✅ Zero intervento richiesto dall'utente
```

---

## 📊 Testing

**Scenario testato**:
1. Installato plugin versione < v10.0.7 (senza queue table)
2. Aggiornato direttamente a v10.0.10 senza disattivare
3. **Risultato**: Tabella creata automaticamente, nessun errore

**Query di verifica**:
```sql
SHOW TABLES LIKE 'wp_ipv_prod_queue';
-- Deve restituire: wp_ipv_prod_queue

SELECT * FROM wp_options WHERE option_name = 'ipv_queue_table_checked';
-- Deve restituire: 10.0.10
```

---

## 🔄 Upgrade Path

### Da qualsiasi versione → v10.0.10:
1. Carica il nuovo ZIP via WordPress → Plugin → Aggiungi nuovo → Carica
2. **NON serve disattivare il plugin**
3. La tabella viene creata automaticamente al primo accesso all'admin
4. Verifica: IPV Videos → Coda Import (non deve mostrare errori)

---

## 📚 Documentazione

### Per sviluppatori

**Logica del controllo**:
- Salvato in `wp_options` come `ipv_queue_table_checked` con valore = versione corrente
- Al primo `admin_init` dopo upgrade:
  - Legge `ipv_queue_table_checked`
  - Se diverso da versione corrente → esegue controllo
  - Controlla se tabella esiste con `SHOW TABLES LIKE`
  - Se mancante → chiama `IPV_Prod_Queue::create_table()`
  - Salva versione corrente in `ipv_queue_table_checked`
- Agli accessi successivi: skip immediato (versioni corrispondono)

**Perché non usare `admin_init` ogni volta?**
- Evita query `SHOW TABLES` inutili a ogni caricamento pagina admin
- Performance: controllo una volta per versione, poi cached

---

## ⚠️ Note

- **Compatibilità**: Funziona con tutte le versioni di WordPress 6.0+
- **Database**: Usa `dbDelta()` per creazione tabella (WordPress best practice)
- **Logging**: Se `WP_DEBUG` attivo, log quando tabella viene creata
- **Sicurezza**: Controllo con `class_exists()` prima di chiamare metodi

---

## 🆘 Troubleshooting

### La tabella non viene creata

**Verifica**:
1. Accedi all'admin WordPress (backend)
2. Controlla `debug.log` per:
   ```
   [IPV Production] Auto-created missing queue table during upgrade to v10.0.10
   ```
3. Se non presente, verifica permessi database

**Fix manuale** (se necessario):
```sql
CREATE TABLE IF NOT EXISTS `wp_ipv_prod_queue` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    video_id VARCHAR(32) NOT NULL,
    video_url TEXT NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'manual',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY  (id),
    KEY status (status),
    KEY video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📦 Download

**Link**: `ipv-production-system-pro-v10.0.10.zip` (266 KB)

**Checksum MD5**: `[generato automaticamente]`

---

## 🎉 Prossimi Passi

Dopo aver installato v10.0.10:

1. **Verifica tabella creata**:
   - Admin → IPV Videos → Coda Import
   - Non deve mostrare errori

2. **Installa Server v1.3.4** (se non già fatto):
   - Risolve problema "Error: unauthorized"
   - Enhanced debug logging
   - Skip activation check per API calls

3. **Test completo**:
   - Importa un video singolo
   - Verifica trascrizione funziona
   - Controlla crediti aggiornati correttamente

---

**Versione**: 10.0.10
**Autore**: IPV Team
**Repository**: https://github.com/daniemi1977/ipv
