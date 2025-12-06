# 🚀 IPV Pro Cloud Edition - Guida Deploy Completa

**Versione**: v10.0.0
**Data**: 2025-12-06
**Architettura**: Plugin VENDOR (bissolomarket.com) + Plugin CLIENT (clienti)

---

## 📋 Indice

1. [Panoramica Sistema](#panoramica-sistema)
2. [Pre-requisiti](#pre-requisiti)
3. [STEP 1: Deploy Plugin VENDOR](#step-1-deploy-plugin-vendor)
4. [STEP 2: Configurazione WooCommerce](#step-2-configurazione-woocommerce)
5. [STEP 3: Upload Plugin CLIENT](#step-3-upload-plugin-client)
6. [STEP 4: Testing Completo](#step-4-testing-completo)
7. [STEP 5: Go-Live](#step-5-go-live)
8. [Manutenzione](#manutenzione)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Panoramica Sistema

### Componenti

```
┌─────────────────────────────────────┐
│   BISSOLOMARKET.COM (Server)       │
│                                     │
│  ├── Plugin VENDOR                 │
│  │   ├── License Manager           │
│  │   ├── API Gateway               │
│  │   │   ├── SupaData Keys (3x)    │
│  │   │   ├── OpenAI Key            │
│  │   │   └── YouTube Key           │
│  │   ├── Credits Manager           │
│  │   ├── Remote Updates            │
│  │   └── WooCommerce Integration   │
│  │                                  │
│  └── WooCommerce                   │
│      └── Prodotti IPV Pro          │
└─────────────────────────────────────┘
           ↓ API REST
┌─────────────────────────────────────┐
│   SITO CLIENTE (Client)            │
│                                     │
│  └── Plugin CLIENT v10.0.0         │
│      ├── License Key                │
│      ├── API Client                 │
│      ├── Remote Updater             │
│      └── Import Videos              │
└─────────────────────────────────────┘
```

### Sicurezza

**✅ API Keys Protette**: SupaData (3 chiavi), OpenAI, YouTube → SOLO su server vendor
**✅ License Validation**: Ogni chiamata API validata con license key
**✅ Credits System**: Quota mensile gestita server-side
**✅ Auto-Updates**: Plugin client si aggiorna da server vendor

---

## ⚙️ Pre-requisiti

### Server bissolomarket.com

- ✅ WordPress 6.0+
- ✅ PHP 8.0+ (raccomandato 8.1)
- ✅ MySQL 8.0+ / MariaDB 10.5+
- ✅ WooCommerce 8.0+
- ✅ **WooCommerce Subscriptions** (per piani mensili)
- ✅ SSL Certificate (HTTPS)
- ✅ Cron WordPress funzionante

### API Keys Necessarie

Devi avere già ottenuto:

1. **SupaData API Keys** (3 chiavi consigliate per rotazione)
   - Registrazione: https://supadata.ai
   - Piano consigliato: Pro (per gestire multipli clienti)

2. **OpenAI API Key**
   - Registrazione: https://platform.openai.com
   - Modello: GPT-4o (incluso in API)

3. **YouTube Data API Key** (opzionale)
   - Console: https://console.cloud.google.com
   - API: YouTube Data API v3

### File Necessari

- ✅ `ipv-pro-vendor-v1.0.0.zip` (Plugin VENDOR)
- ✅ `ipv-production-system-pro-v10.0.0.zip` (Plugin CLIENT)

---

## 🔧 STEP 1: Deploy Plugin VENDOR

**Tempo stimato**: 30 minuti

### 1.1 Upload Plugin

```bash
# Via WordPress Admin
Plugin → Aggiungi nuovo → Carica plugin → ipv-pro-vendor-v1.0.0.zip

# Oppure via SFTP
cd /path/to/wordpress/wp-content/plugins/
unzip ipv-pro-vendor-v1.0.0.zip
```

### 1.2 Attivazione

1. Vai su **Plugin** → Trova "IPV Pro Vendor"
2. Clicca **Attiva**
3. Verifica creazione tabelle database:

```sql
-- Verifica che queste tabelle esistano:
SHOW TABLES LIKE 'wp_ipv_licenses';
SHOW TABLES LIKE 'wp_ipv_activations';
SHOW TABLES LIKE 'wp_ipv_api_logs';
SHOW TABLES LIKE 'wp_ipv_transcript_cache';
SHOW TABLES LIKE 'wp_ipv_usage_stats';
```

### 1.3 Configurazione API Keys

**CRITICO**: Le API keys NON devono MAI essere condivise!

1. Vai su **Video IPV → Impostazioni Vendor**

2. Inserisci le API keys:

```
# SupaData Keys (una per riga per rotazione)
sd_abc123456...
sd_def789012...
sd_ghi345678...

# OpenAI Key
sk-proj-xyz123456...

# YouTube Key (opzionale)
AIzaSyABC123...
```

3. **Testa le chiavi** → Clicca "Test API Keys"

4. Salva

### 1.4 Verifica Cron

```bash
# Verifica che il cron mensile sia schedulato
wp cron event list | grep ipv_vendor_reset_credits

# Output atteso:
# ipv_vendor_reset_credits  2025-01-01 02:00:00  monthly
```

Se non c'è:

```bash
wp cron event schedule ipv_vendor_reset_credits 'first day of next month 02:00:00' monthly
```

---

## 🛒 STEP 2: Configurazione WooCommerce

**Tempo stimato**: 15 minuti

### 2.1 Crea Prodotti IPV Pro

Vai su **Prodotti → Aggiungi nuovo**

#### Prodotto: IPV Pro Trial (€0/mese)

```
Nome: IPV Pro - Trial
Tipo: Abbonamento semplice (Simple Subscription)
Prezzo: €0
Periodo rinnovo: 1 mese
Limite: 1 periodo (non si rinnova)

Meta personalizzati:
_ipv_is_license_product: 1
_ipv_plan_slug: trial
_ipv_credits_total: 10
_ipv_activation_limit: 1
```

#### Prodotto: IPV Pro Starter (€29/mese)

```
Nome: IPV Pro - Starter
Tipo: Abbonamento semplice
Prezzo: €29
Periodo rinnovo: 1 mese
Iscrizione: Manuale (non automatica)

Meta personalizzati:
_ipv_is_license_product: 1
_ipv_plan_slug: starter
_ipv_credits_total: 25
_ipv_activation_limit: 1
```

#### Prodotto: IPV Pro Professional (€89/mese)

```
Nome: IPV Pro - Professional
Tipo: Abbonamento semplice
Prezzo: €89
Periodo rinnovo: 1 mese

Meta personalizzati:
_ipv_is_license_product: 1
_ipv_plan_slug: professional
_ipv_credits_total: 100
_ipv_activation_limit: 3
```

#### Prodotto: IPV Pro Business (€249/mese)

```
Nome: IPV Pro - Business
Tipo: Abbonamento semplice
Prezzo: €249
Periodo rinnovo: 1 mese

Meta personalizzati:
_ipv_is_license_product: 1
_ipv_plan_slug: business
_ipv_credits_total: 500
_ipv_activation_limit: 10
```

### 2.2 Testa Ordine

1. Aggiungi "IPV Pro Trial" al carrello
2. Completa l'ordine (test)
3. Vai su **Video IPV → Licenze**
4. Verifica che la licenza sia stata creata automaticamente

Esempio:

```
License Key: ABCD-1234-EFGH-5678
Email: test@example.com
Piano: trial
Crediti: 10/10
Attivazioni: 0/1
Stato: active
Reset: 2025-01-01
```

### 2.3 Verifica Email

Controlla inbox di `test@example.com`:

- ✅ Email ordine completato WooCommerce
- ✅ Email licenza generata IPV Pro
- ✅ License key visibile
- ✅ Link download plugin

---

## 📦 STEP 3: Upload Plugin CLIENT

**Tempo stimato**: 10 minuti

### 3.1 Upload Prima Versione

1. Vai su **Video IPV → Remote Updates**

2. Clicca **Upload New Version**

3. Compila:

```
File: ipv-production-system-pro-v10.0.0.zip
Version: 10.0.0
Changelog:
- 🔒 Cloud Edition: API keys protette server-side
- ✨ Nuovo sistema licenze e crediti mensili
- 🔄 Auto-updates dal server vendor
- 🚀 Dashboard crediti integrata
```

4. Clicca **Upload**

5. Verifica:

```
Versione corrente: 10.0.0
File: ipv-production-system-pro-v10.0.0.zip
Dimensione: ~250KB
Data upload: 2025-12-06
```

### 3.2 Testa Download

1. Copia il "Download Link" dalla dashboard
2. Apri in browser incognito
3. Dovrebbe scaricare il file ZIP

**IMPORTANTE**: Il download è protetto da license nonce, scade dopo 1 ora.

---

## 🧪 STEP 4: Testing Completo

**Tempo stimato**: 15 minuti

### Test 1: Acquisto e Attivazione Licenza

**Scenario**: Cliente acquista IPV Pro Starter

1. **Acquisto**:
   - Vai su sito (frontend)
   - Aggiungi "IPV Pro Starter" al carrello
   - Completa checkout
   - Paga (usa Stripe Test Mode o modalità test)

2. **Verifica Backend**:
   - Vai su **Video IPV → Licenze**
   - Verifica licenza creata
   - Stato: `active`
   - Crediti: `25/25`

3. **Verifica Email**:
   - Cliente riceve email con license key
   - Link download funziona

### Test 2: Installazione Plugin CLIENT

**Scenario**: Cliente installa plugin sul suo sito

1. **Download Plugin**:
   - Cliente clicca link download da email
   - Scarica `ipv-production-system-pro-v10.0.0.zip`

2. **Installazione**:
   - Carica su sito cliente
   - Attiva plugin
   - Vai su **Video IPV → 🔑 Licenza**

3. **Attivazione Licenza**:
   ```
   License Key: [dalla email]
   Site URL: https://sitocliente.com
   Site Name: Sito Cliente
   ```
   - Clicca **Activate License**

4. **Verifica**:
   - Dashboard mostra:
     ```
     ✅ Licenza Attiva
     Piano: Starter
     Crediti: 25/25 (100%)
     Reset: 01/01/2026
     ```

### Test 3: Import Video (Usa Credito)

**Scenario**: Cliente importa un video YouTube

1. **Import**:
   - Vai su **Video IPV → Import Video**
   - URL: `https://youtube.com/watch?v=dQw4w9WgXcQ`
   - Modalità: Auto
   - Generate AI Description: ✅
   - Clicca **Import**

2. **Verifica Processo**:
   - Video importato
   - Trascrizione generata
   - Descrizione AI generata
   - **Crediti decrementati**: `24/25`

3. **Verifica Vendor**:
   - Vai su **Video IPV → Analytics**
   - Verifica log API:
     ```
     Transcript: sitocliente.com - 200 OK - 1 credit
     Description: sitocliente.com - 200 OK - 0 credits
     ```

### Test 4: Remote Updates

**Scenario**: Upload nuova versione plugin

1. **Upload v10.0.1**:
   - Vendor: Upload file fake (copia v10.0.0, rinomina changelog)
   - Versione: `10.0.1`
   - Changelog: "Bug fixes"

2. **Check Update (Client)**:
   - Vai su **Plugin** (sito cliente)
   - Dovrebbe apparire notifica: "Aggiornamento disponibile: v10.0.1"
   - Clicca **Aggiorna ora**
   - Plugin aggiornato a v10.0.1

### Test 5: Reset Crediti Mensile

**Scenario**: Simula reset mensile

1. **Simula Cron**:
   ```bash
   # Sul server vendor
   wp cron event run ipv_vendor_reset_credits
   ```

2. **Verifica**:
   - Licenze attive con subscription attiva → crediti resettati a `25/25`
   - Licenze cancellate → NON resettate, status → `cancelled`

3. **Email**:
   - Cliente riceve email "Crediti Resettati"

### Test 6: Crediti Esauriti

**Scenario**: Cliente esaurisce crediti

1. **Simula Esaurimento**:
   ```sql
   UPDATE wp_ipv_licenses
   SET credits_remaining = 1
   WHERE id = [license_id];
   ```

2. **Import Video**:
   - Cliente tenta import → successo
   - Crediti: `0/25`

3. **Secondo Import**:
   - Cliente tenta import → ERRORE
   - Messaggio: "Crediti esauriti. Riprova dopo il reset mensile (01/01/2026) o aggiorna al piano superiore."

4. **Email Warning**:
   - Se crediti < 10% → cliente riceve email "Crediti in esaurimento"

---

## ✅ STEP 5: Go-Live

**Checklist Finale**

### Pre-Go-Live

- [ ] Tutti i test completati
- [ ] API keys configurate correttamente
- [ ] 4 prodotti WooCommerce creati
- [ ] Plugin CLIENT uploadato
- [ ] Email transazionali testate
- [ ] Cron mensile schedulato
- [ ] SSL attivo su bissolomarket.com
- [ ] Backup database effettuato

### Configurazioni Finali

1. **WooCommerce**:
   - [ ] Payment gateway configurati (Stripe/PayPal)
   - [ ] Email templates personalizzate
   - [ ] Tax settings configurati

2. **Email**:
   - [ ] SMTP configurato (es: SendGrid, Mailgun)
   - [ ] Test invio email da server

3. **Monitoring**:
   - [ ] Log errors: `/wp-content/debug.log`
   - [ ] Uptime monitoring (es: UptimeRobot)
   - [ ] API quota monitoring (SupaData dashboard)

### Go-Live!

1. **Annuncio**:
   - Pubblica prodotti su bissolomarket.com
   - Crea pagina vendita `/ipv-pro/`
   - Aggiungi FAQ, prezzi, testimonials

2. **Distribuzione Plugin CLIENT**:
   - Invia email clienti esistenti con upgrade path
   - Offri sconto Early Adopter
   - Fornisci guida migrazione (da v9 standalone a v10 cloud)

3. **Support**:
   - Imposta canale support (email, ticket system)
   - Prepara knowledge base
   - Monitora prime attivazioni

---

## 🔄 Manutenzione

### Task Giornalieri

**Automatici (via Cron)**:
- ✅ Verifica licenze attive
- ✅ Log pulizia (> 90 giorni)
- ✅ Cache cleanup (> 30 giorni)

**Manuali**:
- [ ] Check error logs
- [ ] Monitor API quota usage

### Task Mensili

**Automatici**:
- ✅ Reset crediti (1° giorno del mese, ore 02:00)

**Manuali**:
- [ ] Review usage analytics
- [ ] Check subscription renewals
- [ ] Rotate SupaData keys se necessario
- [ ] Backup database completo

### Aggiornamenti Plugin

**Quando rilasci nuova versione CLIENT**:

1. Crea ZIP nuova versione
2. Vai su **Video IPV → Remote Updates**
3. Upload nuova versione + changelog
4. I clienti riceveranno notifica automaticamente
5. Possono aggiornare con 1 click

**Quando rilasci nuova versione VENDOR**:

1. Backup database
2. Upload ZIP via FTP o admin
3. Disattiva → Riattiva plugin
4. Test funzionamento

---

## 🛠️ Troubleshooting

### Problema: Licenza non generata dopo acquisto

**Sintomi**: Ordine completato ma nessuna licenza in dashboard

**Cause**:
- Meta prodotto mancanti
- Hook WooCommerce non attivato

**Soluzione**:
```bash
# Verifica meta prodotto
wp post meta list [product_id] | grep _ipv

# Output atteso:
# _ipv_is_license_product: 1
# _ipv_plan_slug: starter
# _ipv_credits_total: 25
# _ipv_activation_limit: 1

# Se mancanti, aggiungi manualmente
wp post meta add [product_id] _ipv_is_license_product 1
wp post meta add [product_id] _ipv_plan_slug starter
wp post meta add [product_id] _ipv_credits_total 25
wp post meta add [product_id] _ipv_activation_limit 1

# Quindi rigenera licenza manualmente
wp eval 'IPV_Vendor_License_Manager::instance()->create_license_from_order([order_id], [product_id]);'
```

### Problema: "License validation failed"

**Sintomi**: Cliente non riesce ad attivare licenza

**Cause**:
- License key errata
- API server non raggiungibile
- License già attivata su altro sito

**Soluzione**:
```bash
# Verifica license
wp db query "SELECT * FROM wp_ipv_licenses WHERE license_key = 'ABCD-1234-EFGH-5678'"

# Check attivazioni
wp db query "SELECT * FROM wp_ipv_activations WHERE license_id = [id]"

# Se limite raggiunto, aumenta o disattiva vecchio sito
wp db query "UPDATE wp_ipv_licenses SET activation_limit = 5 WHERE id = [id]"

# Oppure disattiva sito
wp db query "UPDATE wp_ipv_activations SET is_active = 0 WHERE id = [activation_id]"
```

### Problema: "Insufficient credits"

**Sintomi**: Cliente non può importare video nonostante crediti disponibili

**Cause**:
- Crediti non sincronizzati
- Cache problema

**Soluzione**:
```bash
# Verifica crediti
wp db query "SELECT credits_remaining, credits_total FROM wp_ipv_licenses WHERE id = [id]"

# Reset manuale crediti (se è passato il mese)
wp eval 'IPV_Vendor_Credits_Manager::instance()->reset_license_credits([license_id]);'

# Forza aggiunta crediti (emergenza)
wp db query "UPDATE wp_ipv_licenses SET credits_remaining = credits_total WHERE id = [id]"
```

### Problema: Plugin CLIENT non si aggiorna

**Sintomi**: Notifica update non compare

**Cause**:
- Transient WordPress cached
- API updates endpoint non raggiungibile

**Soluzione (sito cliente)**:
```bash
# Cancella transient update
wp transient delete update_plugins

# Forza check updates
wp plugin status ipv-production-system-pro

# Test manuale API updates
curl -X POST https://bissolomarket.com/wp-json/ipv-vendor/v1/check-update \
  -H "Content-Type: application/json" \
  -d '{"license_key":"ABCD-1234-EFGH-5678","plugin_version":"10.0.0"}'
```

### Problema: SupaData 402 "Quota exceeded"

**Sintomi**: Trascrizioni falliscono con errore quota

**Cause**:
- Crediti SupaData esauriti
- Tutte e 3 le key esaurite

**Soluzione**:
```bash
# Verifica rotazione key
wp option get ipv_supadata_rotation_mode
# Deve essere: round-robin

# Aggiungi crediti su SupaData.ai dashboard
# Oppure aggiungi nuova key

# Test key
curl -H "x-api-key: sd_YOUR_KEY" \
  "https://api.supadata.ai/v1/transcript?url=https://youtube.com/watch?v=dQw4w9WgXcQ&mode=native&text=true"
```

### Problema: Cron mensile non resetta crediti

**Sintomi**: Passato il 1° del mese ma crediti non resettati

**Cause**:
- Cron WordPress non funziona
- Cron non schedulato

**Soluzione**:
```bash
# Verifica cron
wp cron event list | grep ipv_vendor_reset_credits

# Se mancante, schedula
wp cron event schedule ipv_vendor_reset_credits 'first day of next month 02:00:00' monthly

# Forza esecuzione manuale (test)
wp cron event run ipv_vendor_reset_credits

# Verifica output logs
wp db query "SELECT * FROM wp_ipv_licenses WHERE status = 'active' ORDER BY updated_at DESC LIMIT 5"
# updated_at deve essere recente dopo reset
```

---

## 📊 Monitoring & Analytics

### Dashboard Vendor

**Video IPV → Dashboard** mostra:

- Licenze attive/totali
- Revenue mensile
- Crediti utilizzati oggi
- Top clienti (per usage)
- API errors (ultimi 7 giorni)

### Analytics

**Video IPV → Analytics** mostra:

- API calls per endpoint
- Cache hit rate
- Response time medio
- Errori per tipo
- Usage per licenza

### Database Queries Utili

```sql
-- Licenze attive
SELECT COUNT(*) FROM wp_ipv_licenses WHERE status = 'active';

-- Crediti utilizzati oggi
SELECT SUM(credits_used)
FROM wp_ipv_usage_stats
WHERE date = CURDATE();

-- Top 10 clienti per usage
SELECT l.email, l.plan, SUM(u.credits_used) as total_credits
FROM wp_ipv_licenses l
JOIN wp_ipv_usage_stats u ON l.id = u.license_id
WHERE u.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY l.id
ORDER BY total_credits DESC
LIMIT 10;

-- Cache hit rate
SELECT
  SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as cache_hits,
  COUNT(*) as total_calls,
  ROUND(SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as hit_rate
FROM wp_ipv_api_logs
WHERE endpoint = 'transcript'
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

---

## 🎉 Congratulazioni!

Sistema IPV Pro Cloud Edition completamente deployato!

**Prossimi Passi**:

1. 📣 Annuncia lancio ai clienti
2. 📈 Monitora prime attivazioni
3. 💬 Raccogli feedback
4. 🔄 Itera e migliora

**Supporto**:

- 📧 Email: support@ilpuntodivista.com
- 📖 Docs: https://docs.ilpuntodivista.com (TODO)
- 🐛 Bug Reports: GitHub Issues (TODO)

---

**Made with ❤️ by IPV Production Team**
v10.0.0 Cloud Edition - Secure, Scalable, Smart
