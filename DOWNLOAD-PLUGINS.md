# 📥 Download IPV Pro v10.0.0 - Plugin WordPress

**Versione**: v10.0.0 Cloud Edition
**Data Rilascio**: 6 Dicembre 2025

---

## 🔽 Download Diretto (da GitHub)

### 1️⃣ Plugin VENDOR (Server-Side)

**File**: `ipv-pro-vendor-v1.0.0.zip` (41 KB)
**Installare su**: bissolomarket.com (server di vendita)

**Download**:
```
https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-pro-vendor-v1.0.0.zip
```

**Cosa include**:
- ✅ License Manager (WooCommerce integration)
- ✅ API Gateway (protegge SupaData, OpenAI, YouTube keys)
- ✅ Credits system con reset mensile
- ✅ Remote Updates Server
- ✅ Admin dashboard completa
- ✅ Customer portal
- ✅ 12 REST API endpoints

---

### 2️⃣ Plugin CLIENT (Client-Side)

**File**: `ipv-production-system-pro-v10.0.0.zip` (253 KB)
**Distribuire a**: Clienti finali

**Download**:
```
https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-production-system-pro-v10.0.0.zip
```

**Cosa include**:
- ✅ API Client (no API keys esposte)
- ✅ License activation UI
- ✅ Remote auto-updates dal vendor server
- ✅ Import massivo video YouTube
- ✅ AI Transcriptions (SupaData)
- ✅ AI SEO Descriptions (GPT-4o)
- ✅ Video Wall + Widget Grid

---

## 🚀 Installazione Rapida

### VENDOR Plugin (sul tuo server)

```bash
# 1. Scarica plugin
wget "https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-pro-vendor-v1.0.0.zip"

# 2. WordPress Admin → Plugin → Aggiungi nuovo
# Carica file → ipv-pro-vendor-v1.0.0.zip → Installa ora

# 3. Attiva plugin

# 4. Configura API keys
# Modifica: wp-content/plugins/ipv-pro-vendor/includes/class-api-gateway.php
# Inserisci:
#   - SUPADATA_API_KEY_1, _2, _3
#   - OPENAI_API_KEY
#   - YOUTUBE_API_KEY

# 5. Crea prodotti WooCommerce
# Vedi: NUOVI-PIANI-SETUP.md
```

---

### CLIENT Plugin (per clienti)

```bash
# 1. Il cliente scarica da email (link auto-generato dopo acquisto)
# Oppure scarica manualmente:
wget "https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-production-system-pro-v10.0.0.zip"

# 2. WordPress Admin → Plugin → Aggiungi nuovo
# Carica file → ipv-production-system-pro-v10.0.0.zip → Installa ora

# 3. Attiva plugin

# 4. Attiva licenza
# Video IPV → Licenza
# Inserisci license key ricevuta via email
# Clicca "Attiva Licenza"

# 5. Inizia ad importare video!
# Video IPV → Nuovo Import
```

---

## 📋 Requisiti di Sistema

| Componente | Requisito Minimo |
|------------|------------------|
| WordPress | ≥ 6.0 |
| PHP | ≥ 7.4 (consigliato 8.0+) |
| MySQL | ≥ 5.7 |
| WooCommerce | ≥ 8.0 (solo VENDOR) |
| WooCommerce Subscriptions | Required (solo VENDOR) |
| cURL | Enabled |
| allow_url_fopen | Enabled |

---

## 🔧 Configurazione Post-Installazione

### VENDOR Server

Dopo aver installato `ipv-pro-vendor`:

1. **Configura API Keys** (CRITICAL):
   ```bash
   # Edita: wp-content/plugins/ipv-pro-vendor/includes/class-api-gateway.php

   const YOUTUBE_API_KEY = 'YOUR_YOUTUBE_API_KEY_HERE';
   const SUPADATA_API_KEY_1 = 'sd_YOUR_SUPADATA_KEY_1_HERE';
   const SUPADATA_API_KEY_2 = 'sd_YOUR_SUPADATA_KEY_2_HERE';
   const SUPADATA_API_KEY_3 = 'sd_YOUR_SUPADATA_KEY_3_HERE';
   const OPENAI_API_KEY = 'sk-proj-YOUR_OPENAI_KEY_HERE';
   ```

2. **Crea 4 Prodotti WooCommerce**:
   - Free: €0 - 10 video/mese
   - Basic: €9,99 - 100 video/mese
   - Pro: €19,99 - 200 video/mese
   - Premium: €39,99 - 500 video/mese

   Vedi guida: `NUOVI-PIANI-SETUP.md`

3. **Configura Remote Updates**:
   ```bash
   # Carica il CLIENT plugin ZIP sul server:
   wp-content/uploads/ipv-plugins/ipv-production-system-pro-v10.0.0.zip

   # Il sistema distribuirà automaticamente gli update ai clienti
   ```

4. **Testa Acquisto**:
   ```bash
   # Compra piano Free (€0)
   # Verifica:
   # - Email ricevuta con license key
   # - Licenza creata in: Video IPV → Licenze
   # - Credits: 10/10
   # - Status: active
   ```

---

### CLIENT (per clienti)

Dopo aver installato `ipv-production-system-pro`:

1. **Attiva Licenza**:
   ```
   Video IPV → Licenza
   License Key: XXXX-XXXX-XXXX-XXXX (dalla email)
   Server URL: https://bissolomarket.com
   Clicca "Attiva Licenza"
   ```

2. **Verifica Attivazione**:
   ```
   Dashboard → Video IPV
   Dovresti vedere:
   ✓ Piano: Basic (o il tuo piano)
   ✓ Credits: 100/100
   ✓ Scadenza: 01/01/2026
   ✓ Status: Attiva
   ```

3. **Primo Import**:
   ```
   Video IPV → Nuovo Import
   YouTube URL: https://www.youtube.com/watch?v=VIDEO_ID
   Importa Video

   Il sistema:
   1. Valida licenza con server
   2. Scarica trascrizione (SupaData API via server)
   3. Genera descrizione SEO (GPT-4o via server)
   4. Crea post WordPress
   5. Decrementa credits (99/100)
   ```

---

## 📚 Documentazione Completa

| File | Descrizione |
|------|-------------|
| `DEPLOY-GUIDE-FINAL.md` | Guida deployment completa (5 step) |
| `QUICK-START.md` | Installazione rapida in 30 minuti |
| `PRICING-PLANS.md` | Strategia pricing + revenue calculations |
| `NUOVI-PIANI-SETUP.md` | Setup WooCommerce prodotti |
| `ELEMENTOR-IMPORT-GUIDE.md` | Import pagina pricing in Elementor |
| `DOWNLOAD-HOSTING.md` | Come hostare pagina download |

---

## 🔒 Sicurezza

**VENDOR Plugin**:
- ✅ API keys SOLO server-side (mai esposte a clienti)
- ✅ License validation su ogni richiesta
- ✅ Credits check server-side (impossibile bypassare)
- ✅ IP logging per audit
- ✅ Nonce-protected downloads

**CLIENT Plugin**:
- ✅ NO API keys hardcoded
- ✅ Tutte le chiamate API passano per server vendor
- ✅ License key inviata via Bearer token (HTTPS)
- ✅ Remote updates con signature check

---

## ❓ FAQ

### Dove inserisco le API keys SupaData/OpenAI?

**Solo nel VENDOR plugin**, file `includes/class-api-gateway.php`.
Il CLIENT plugin NON contiene API keys (più sicuro).

### Posso distribuire il CLIENT plugin pubblicamente?

Sì! Il CLIENT plugin può essere distribuito liberamente perché non contiene API keys.
La protezione è lato server tramite license validation.

### Come funzionano gli updates?

Il CLIENT plugin controlla automaticamente nuove versioni dal VENDOR server ogni 12 ore.
Se disponibile update, appare notifica in WordPress Admin → Plugin.

### Cosa succede se cliente supera crediti?

Il server risponde con errore `insufficient_credits`.
Il plugin mostra messaggio: "Crediti esauriti. Upgrade piano o attendi reset."

### Quando si resettano i crediti?

Automaticamente il 1° giorno di ogni mese alle 02:00 (WP Cron).
Solo per abbonamenti attivi (check via WooCommerce Subscriptions).

---

## 🆘 Troubleshooting

### Plugin VENDOR non crea tabelle database

```bash
# Disattiva e riattiva plugin
wp plugin deactivate ipv-pro-vendor
wp plugin activate ipv-pro-vendor

# Oppure manualmente:
wp eval 'do_action("activate_ipv-pro-vendor/ipv-pro-vendor.php");'
```

### CLIENT non si connette al server

```bash
# Verifica server URL
# Video IPV → Licenza
# Server URL deve essere: https://bissolomarket.com (senza trailing slash)

# Testa endpoint manualmente:
curl https://bissolomarket.com/wp-json/ipv-vendor/v1/health
# Output atteso: {"status":"ok","version":"1.0.0"}
```

### Updates non funzionano

```bash
# VENDOR: Verifica file ZIP presente
ls -lh wp-content/uploads/ipv-plugins/ipv-production-system-pro-v10.0.0.zip

# CLIENT: Forza check updates
wp transient delete update_plugins
wp cron event run wp_update_plugins
```

---

## 📊 Monitoraggio (VENDOR)

Dashboard admin fornisce:

- **MRR (Monthly Recurring Revenue)**: Entrate mensili ricorrenti
- **Active Licenses**: Licenze attive per piano
- **Usage Stats**: Chiamate API per cliente
- **Churn Rate**: Tasso cancellazioni
- **Top Customers**: Clienti con più utilizzo

```sql
-- Query MRR corrente
SELECT SUM(
  CASE plan
    WHEN 'free' THEN 0
    WHEN 'basic' THEN 9.99
    WHEN 'pro' THEN 19.99
    WHEN 'premium' THEN 39.99
  END
) as mrr
FROM wp_ipv_licenses
WHERE status = 'active';
```

---

## 🎯 Prossimi Passi

1. ✅ **Download plugin** (link sopra)
2. ✅ **Installa VENDOR** su bissolomarket.com
3. ✅ **Configura API keys** (class-api-gateway.php)
4. ⏰ **Crea 4 prodotti WooCommerce** (15 min)
5. ⏰ **Testa acquisto Free** (5 min)
6. ⏰ **Distribuisci CLIENT** ai primi utenti
7. ⏰ **Pubblica pagina pricing** (usa Elementor template)
8. ⏰ **Launch! 🚀**

---

**Made with ❤️ by IPV Production Team**
v10.0.0 Cloud Edition - 6 Dicembre 2025

Per support: [GitHub Issues](https://github.com/daniemi1977/ipv/issues)
