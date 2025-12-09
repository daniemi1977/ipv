# 🚨 DEBUG: "Error unauthorized" - Guida Step-by-Step

## 📋 Situazione

Hai installato:
- ✅ Server v1.3.2 con fix .htaccess
- ✅ Client v10.0.8 con diagnostica

Ma continui a vedere: **"Error: unauthorized"**

---

## 🔍 DEBUG FASE 1: Verifica Cosa Arriva al Server

### Step 1: Carica Script di Debug

**File:** `ipv-vendor-debug-test.php`

**Dove:** Caricalo nella root del server (stesso livello di wp-config.php)

```
Percorso: public_html/ipv-vendor-debug-test.php
```

### Step 2: Accedi allo Script

**URL:** `https://aiedintorni.it/ipv-vendor-debug-test.php`

Apri questo URL nel browser.

### Step 3: Leggi il Risultato

Lo script ti mostrerà:

#### ✅ **Scenario A: Authorization Header RICEVUTO**

```
✅ SUCCESS: Authorization header RICEVUTO dal server!
Valore: Bearer abc123...
```

**Significato:** L'.htaccess funziona! L'header arriva correttamente.

**Problema:** Non è l'header bloccato, ma la licenza stessa.

**Prossimi passi:** Vai a FASE 2

---

#### ❌ **Scenario B: Authorization Header NON ARRIVA**

```
❌ FAIL: Authorization header NON ARRIVA al server!
```

**Significato:** L'hosting continua a bloccare l'header.

**Problema:** .htaccess non configurato correttamente.

**Soluzione:** Vai a FASE 3

---

## 🔍 DEBUG FASE 2: Problema Licenza (se header arriva)

Se lo script mostra che l'header **ARRIVA**, il problema è la validazione della licenza.

### Step 1: Attiva Test Endpoint sul Server

**File:** `ipv-vendor-test-endpoint.php`

**Dove:** Caricalo in: `wp-content/plugins/ipv-pro-vendor/ipv-vendor-test-endpoint.php`

### Step 2: Modifica Plugin Principale

Apri: `wp-content/plugins/ipv-pro-vendor/ipv-pro-vendor.php`

Cerca l'ultima riga (dovrebbe essere `// Load core`) e **PRIMA** aggiungi:

```php
// TEMPORANEO - Test endpoint
require_once IPV_VENDOR_DIR . 'ipv-vendor-test-endpoint.php';
```

Salva.

### Step 3: Testa Endpoint Debug

**URL:** `https://aiedintorni.it/wp-json/ipv-vendor/v1/test-debug`

Apri nel browser. Dovrebbe mostrare:

```json
{
  "success": true,
  "debug": {
    "plugin": {
      "version": "1.3.2",
      "active": true
    },
    "options": {
      "supadata_key_configured": true,
      "openai_key_configured": true
    }
  }
}
```

**Se non risponde:** Il plugin non è attivo o ci sono errori PHP.

### Step 4: Testa con License Key dal Client

**Sul client**, esegui questo nel browser console (F12):

```javascript
fetch('https://aiedintorni.it/wp-json/ipv-vendor/v1/test-transcript', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_LICENSE_KEY_HERE', // Sostituisci con vera license key
  },
  body: JSON.stringify({
    video_id: 'test123',
    mode: 'auto',
    lang: 'it'
  })
})
.then(r => r.json())
.then(data => console.log(data))
```

**Output Atteso:**

```json
{
  "success": true,
  "debug": {
    "overall_status": "AUTHORIZATION_RECEIVED",
    "headers": {
      "HTTP_AUTHORIZATION": "Bearer abc123..."
    },
    "license_test": {
      "result": "ERROR",
      "error_code": "invalid_license",
      "error_message": "License key not found"
    }
  }
}
```

**Interpretazione:**

| overall_status | license_test result | Significato |
|----------------|---------------------|-------------|
| `AUTHORIZATION_RECEIVED` | `SUCCESS` | ✅ Tutto funziona! |
| `AUTHORIZATION_RECEIVED` | `ERROR: invalid_license` | ❌ License key non esiste nel database |
| `AUTHORIZATION_RECEIVED` | `ERROR: license_expired` | ❌ License key scaduta |
| `AUTHORIZATION_RECEIVED` | `ERROR: wrong_domain` | ❌ License key non attivata per questo dominio |
| `AUTHORIZATION_MISSING` | N/A | ❌ Header ancora bloccato |

---

## 🔍 DEBUG FASE 3: Fix .htaccess (se header NON arriva)

Se l'header **NON ARRIVA**, l'.htaccess non funziona.

### Opzione 1: Modifica .htaccess Root (Raccomandato per SiteGround)

**File:** `public_html/.htaccess`

**Posizione:** Root del sito (stesso livello wp-config.php)

**Apri il file e aggiungi IN CIMA (prima di tutto):**

```apache
# BEGIN IPV Authorization Fix
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>
# END IPV Authorization Fix

# BEGIN WordPress
# ... resto del file ...
```

**Salva e riprova** lo script debug (Fase 1).

### Opzione 2: Se Opzione 1 Non Funziona (SiteGround Avanzato)

Alcuni account SiteGround richiedono questa sintassi:

```apache
# BEGIN IPV Authorization Fix
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} .+
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>
# END IPV Authorization Fix
```

### Opzione 3: Via php.ini (Alternativa)

Crea file `php.ini` in `public_html/`:

```ini
; Preserve Authorization header
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

### Opzione 4: Via wp-config.php (Last Resort)

Apri `wp-config.php` e aggiungi PRIMA di `/* That's all, stop editing! */`:

```php
// Fix Authorization header per SiteGround
if ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) && empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
```

---

## 🔍 DEBUG FASE 4: Verifica License Key nel Database

Se l'header arriva ma la validazione fallisce, controlla il database.

### Step 1: Accedi a phpMyAdmin

SiteGround: Site Tools → MySQL → phpMyAdmin

### Step 2: Trova Tabella Licenze

Tabella: `wp_ipv_licenses` (o `{prefix}_ipv_licenses`)

### Step 3: Cerca License Key

```sql
SELECT * FROM wp_ipv_licenses WHERE license_key = 'YOUR_LICENSE_KEY';
```

**Sostituisci `YOUR_LICENSE_KEY` con la license key reale che usa il client.**

### Step 4: Verifica Campi

| Campo | Valore Atteso | Se Diverso |
|-------|---------------|------------|
| `status` | `active` | Deve essere `active`, non `inactive` |
| `expiry_date` | Data futura | Se passata, licenza scaduta |
| `site_url` | URL cliente | Deve corrispondere al dominio cliente |
| `product_id` | Non NULL | Deve esistere |

### Step 5: Correggi se Necessario

**Se license key non esiste:**

```sql
INSERT INTO wp_ipv_licenses (license_key, status, product_id, site_url, expiry_date)
VALUES ('YOUR_LICENSE_KEY', 'active', 1, 'https://cliente.com', '2025-12-31');
```

**Se status è inactive:**

```sql
UPDATE wp_ipv_licenses SET status = 'active' WHERE license_key = 'YOUR_LICENSE_KEY';
```

**Se site_url è sbagliato:**

```sql
UPDATE wp_ipv_licenses SET site_url = 'https://cliente-corretto.com' WHERE license_key = 'YOUR_LICENSE_KEY';
```

---

## 🔍 DEBUG FASE 5: Verifica Client Configuration

Sul **client** (non server).

### Step 1: Verifica License Key Configurata

```
WordPress Admin → IPV Videos → Licenza
```

**Controlla:**
- License key è inserita?
- Status mostra "Licenza Attiva 🟢"?

**Se NO:**
1. Clicca "Disattiva Licenza"
2. Reinserisci license key
3. Clicca "Attiva Licenza"
4. Verifica che mostri "🟢 Attiva"

### Step 2: Verifica Server URL

```
WordPress Admin → IPV Videos → Impostazioni → Server
```

**Controlla:**
- Server URL: `https://aiedintorni.it` (NO slash finale)
- Clicca "Salva Impostazioni"

### Step 3: Esegui Diagnostica (se v10.0.8+)

```
WordPress Admin → IPV Videos → Diagnostica → Esegui Diagnostica
```

**Risultato atteso:**
- ✅ Configurazione Locale
- ✅ Server Raggiungibilità (v1.3.2)
- ✅ Validazione Licenza
- ✅ Crediti Disponibili

**Se TUTTO verde ma trascrizione fallisce:**
→ Problema è l'Authorization header → Torna a Fase 3

---

## 🔍 DEBUG FASE 6: Log Server

### Step 1: Abilita WP_DEBUG

Sul **server**, modifica `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
```

### Step 2: Riprova Trascrizione

Sul **client**, vai su un video e clicca "Rigenera Trascrizione".

### Step 3: Controlla Log

Sul **server**: `wp-content/debug.log`

**Cerca:**

```
=== IPV VENDOR DEBUG - License Validation ===
```

**Interpretazione:**

#### A) Authorization Header Arriva

```
HTTP_AUTHORIZATION: Bearer abc123xyz...
IPV VENDOR: License key trovata: abc123xy...xyz
```

→ Header OK! Controlla riga successiva:

```
IPV VENDOR ERROR: License not found in database
```

→ License key non esiste nel DB → Fase 4

#### B) Authorization Header NON Arriva

```
HTTP_AUTHORIZATION: NULL
HTTP_X_LICENSE_KEY: NULL
IPV VENDOR ERROR: License key mancante dopo tutti i fallback!
```

→ Header bloccato → Fase 3

---

## 📊 Checklist Completa

Usa questa checklist per verificare tutto:

### Sul Server (aiedintorni.it)

- [ ] IPV Pro Vendor v1.3.2 installato e attivo
- [ ] File `.htaccess` in `public_html/` con regole RewriteEngine
- [ ] Script debug `ipv-vendor-debug-test.php` mostra "SUCCESS"
- [ ] WP_DEBUG abilitato in `wp-config.php`
- [ ] Controllato `debug.log` per errori
- [ ] Tabella `wp_ipv_licenses` esiste
- [ ] License key esiste nella tabella con status `active`
- [ ] `site_url` nella tabella corrisponde al dominio cliente

### Sul Client (cliente.com)

- [ ] IPV Production System Pro v10.0.8 installato
- [ ] License key configurata in IPV Videos → Licenza
- [ ] Server URL configurato: `https://aiedintorni.it`
- [ ] Licenza mostra "🟢 Attiva"
- [ ] Diagnostica eseguita (se v10.0.8) → Tutto ✅
- [ ] Browser console (F12) non mostra errori JavaScript

---

## 🎯 Test Rapido

Per verificare velocemente dove è il problema:

```bash
# 1. Dal tuo computer, esegui:
curl -v -H "Authorization: Bearer test123" https://aiedintorni.it/ipv-vendor-debug-test.php

# Guarda nell'output se vedi:
# ✅ "SUCCESS: Authorization header RICEVUTO" → .htaccess OK
# ❌ "FAIL: Authorization header NON ARRIVA" → .htaccess KO

# 2. Test WordPress API:
curl -X POST https://aiedintorni.it/wp-json/ipv-vendor/v1/test-transcript \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_REAL_LICENSE_KEY" \
  -d '{"video_id":"test","mode":"auto","lang":"it"}'

# Guarda il JSON risultato:
# - overall_status: "AUTHORIZATION_RECEIVED" → Header OK
# - license_test.result: "SUCCESS" → Licenza OK
# - license_test.result: "ERROR" → Licenza KO (leggi error_message)
```

---

## 🆘 Se Niente Funziona

### Inviami Questi Dati

1. **Output completo dello script debug**
   ```
   https://aiedintorni.it/ipv-vendor-debug-test.php
   ```
   Copia TUTTO l'output HTML

2. **Output test endpoint**
   ```
   https://aiedintorni.it/wp-json/ipv-vendor/v1/test-debug
   ```
   Copia il JSON

3. **Ultimi 100 righe debug.log**
   ```
   tail -100 wp-content/debug.log
   ```

4. **Screenshot diagnostica client**
   ```
   IPV Videos → Diagnostica → Esegui Diagnostica
   ```

5. **Screenshot errore browser**
   ```
   F12 → Console + Network tab durante errore
   ```

6. **Query database**
   ```sql
   SELECT license_key, status, site_url, expiry_date
   FROM wp_ipv_licenses
   WHERE license_key = 'YOUR_KEY';
   ```

7. **Contenuto .htaccess root**
   ```
   cat public_html/.htaccess
   ```

---

## ⚠️ IMPORTANTE: Pulizia Dopo Debug

Dopo aver risolto, **ELIMINA questi file**:

```
public_html/ipv-vendor-debug-test.php
wp-content/plugins/ipv-pro-vendor/ipv-vendor-test-endpoint.php
```

E **RIMUOVI** da `ipv-pro-vendor.php`:
```php
// TEMPORANEO - Test endpoint
require_once IPV_VENDOR_DIR . 'ipv-vendor-test-endpoint.php';
```

E **DISABILITA** WP_DEBUG in `wp-config.php`:
```php
define( 'WP_DEBUG', false );
```

---

## 📞 Link Utili

- **Script Debug**: ipv-vendor-debug-test.php (in questa cartella)
- **Test Endpoint**: ipv-vendor-test-endpoint.php (in questa cartella)
- **Troubleshooting Completo**: TROUBLESHOOTING-AUTHORIZATION-ERROR.md
- **Changelog Server**: CHANGELOG-v1.3.2-SERVER.md

---

**Versione:** 1.0
**Data:** 9 Dicembre 2024
**Per:** Debug "Error: unauthorized" persistente dopo v1.3.2
