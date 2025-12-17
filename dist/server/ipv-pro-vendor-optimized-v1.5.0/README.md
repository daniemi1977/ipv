# IPV Pro Vendor System

Sistema completo per vendere **IPV Production System Pro** via WooCommerce con API Gateway integrato.

## 🎯 Caratteristiche

- ✅ **License Management System** - Generazione, attivazione, validazione licenze
- ✅ **API Gateway** - Protegge API keys server-side (SupaData, OpenAI, YouTube)
- ✅ **Credits System** - Gestione crediti mensili con reset automatico
- ✅ **WooCommerce Integration** - Prodotti, subscriptions, pagamenti
- ✅ **Remote Updates** - Sistema aggiornamenti automatici plugin client
- ✅ **REST API Endpoints** - API complete per il plugin client
- ✅ **Admin Dashboard** - Pannello gestione licenze, analytics, settings
- ✅ **Customer Portal** - Interfaccia clienti in My Account

## 📋 Requisiti

- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- WooCommerce 8.0+
- SSL Certificate (HTTPS obbligatorio)

## 🚀 Installazione

### 1. Upload Plugin

```bash
# Upload via FTP/SFTP
/wp-content/plugins/ipv-pro-vendor/

# Oppure usa WordPress Admin
Plugin → Aggiungi nuovo → Carica plugin
```

### 2. Configurazione API Keys

**IMPORTANTE:** Prima di attivare, configura le tue API keys!

Modifica: `includes/class-api-gateway.php`

```php
const YOUTUBE_API_KEY = 'YOUR_YOUTUBE_API_KEY_HERE';
const SUPADATA_API_KEY_1 = 'sd_YOUR_KEY_1';
const SUPADATA_API_KEY_2 = 'sd_YOUR_KEY_2';
const SUPADATA_API_KEY_3 = 'sd_YOUR_KEY_3';
const OPENAI_API_KEY = 'sk-proj_YOUR_KEY';
```

**Dove trovare le chiavi:**
- YouTube: https://console.cloud.google.com/apis/credentials
- SupaData: https://app.supadata.ai/settings/api-keys
- OpenAI: https://platform.openai.com/api-keys

### 3. Attivazione

1. WordPress Admin → Plugin
2. Trova "IPV Pro Vendor System"
3. Clicca "Attiva"
4. Verifica creazione tabelle database

### 4. Setup WooCommerce

1. Installa **WooCommerce Subscriptions** (per piani mensili)
2. Configura gateway pagamento (Stripe/PayPal)
3. Crea prodotti IPV Pro (vedi sotto)

## 📦 Creazione Prodotti

### Prodotto 1: Trial (Gratis)

- **Nome:** IPV Pro - Trial (10 Video Gratis)
- **Prezzo:** €0
- **Tipo:** Prodotto semplice
- **Settings IPV Pro:**
  - ☑ IPV Pro License Product
  - Piano: Trial
  - Crediti Mensili: 10
  - Limite Attivazioni: 1

### Prodotto 2: Starter (€19.95/mese)

- **Nome:** IPV Pro - Starter (25 Video/Mese)
- **Prezzo:** €19.95/mese
- **Tipo:** Abbonamento semplice
- **Settings IPV Pro:**
  - ☑ IPV Pro License Product
  - Piano: Starter
  - Crediti Mensili: 25
  - Limite Attivazioni: 1

### Prodotto 3: Professional (€49.95/mese)

- **Nome:** IPV Pro - Professional (100 Video/Mese)
- **Prezzo:** €49.95/mese
- **Tipo:** Abbonamento semplice
- **Settings IPV Pro:**
  - ☑ IPV Pro License Product
  - Piano: Professional
  - Crediti Mensili: 100
  - Limite Attivazioni: 3

### Prodotto 4: Business (€99.95/mese)

- **Nome:** IPV Pro - Business (500 Video/Mese)
- **Prezzo:** €99.95/mese
- **Tipo:** Abbonamento semplice
- **Settings IPV Pro:**
  - ☑ IPV Pro License Product
  - Piano: Business
  - Crediti Mensili: 500
  - Limite Attivazioni: 10

## 🔌 REST API Endpoints

### Health Check
```
GET /wp-json/ipv-vendor/v1/health
```

### License Management
```
POST /wp-json/ipv-vendor/v1/license/activate
POST /wp-json/ipv-vendor/v1/license/deactivate
POST /wp-json/ipv-vendor/v1/license/validate
GET  /wp-json/ipv-vendor/v1/license/info
```

### API Gateway
```
POST /wp-json/ipv-vendor/v1/transcript
POST /wp-json/ipv-vendor/v1/description
GET  /wp-json/ipv-vendor/v1/credits
```

### Remote Updates
```
GET  /wp-json/ipv-vendor/v1/plugin-info
POST /wp-json/ipv-vendor/v1/check-update
```

## 📊 Database Schema

Il plugin crea automaticamente 5 tabelle:

- `wp_ipv_licenses` - Licenze
- `wp_ipv_activations` - Attivazioni siti
- `wp_ipv_api_logs` - Log chiamate API
- `wp_ipv_transcript_cache` - Cache trascrizioni
- `wp_ipv_usage_stats` - Statistiche uso giornaliere

## 🔄 Cron Jobs

### Reset Crediti Mensile
- **Schedule:** Primo giorno del mese alle 02:00
- **Action:** `ipv_vendor_reset_credits`
- **Funzione:** Resetta crediti per tutte le licenze attive

## 🎬 Upload Nuove Versioni Plugin Client

1. Admin → IPV Pro Vendor → Updates
2. Upload file .zip (es: ipv-production-system-pro-v10.0.0.zip)
3. Inserisci versione (es: 10.0.0)
4. Aggiungi changelog (opzionale)
5. Carica versione

Il sistema gestirà automaticamente:
- Download con validazione license
- Check updates automatici dal client
- Update 1-click da WordPress

## 🔒 Sicurezza

### API Keys Protection

**✅ SICURO:**
- API keys SOLO sul server (class-api-gateway.php)
- Client NON vede mai le keys
- Tutte le chiamate validate server-side

**❌ MAI FARE:**
- Non includere class-api-gateway.php nel plugin client
- Non distribuire API keys al cliente
- Non committare keys in Git

### License Validation

Ogni richiesta API richiede:
- `Authorization: Bearer LICENSE_KEY` header, oppure
- `X-License-Key: LICENSE_KEY` header, oppure
- `license_key` nel body della richiesta

## 📖 Documentazione

### Admin Dashboard
- **Menu:** IPV Pro Vendor
- **Sezioni:** Dashboard, Licenze, Updates, Analytics, Settings

### Customer Portal
- **Menu My Account:** IPV Licenses
- **Features:** Download plugin, gestione attivazioni, monitor crediti

## 🐛 Troubleshooting

### License non generata
1. Verifica WooCommerce attivo
2. Check product meta: `_ipv_is_license_product = yes`
3. Verifica log: `wp-content/debug.log`

### API non risponde
1. Test health check: `/wp-json/ipv-vendor/v1/health`
2. Verifica permalink salvati (Settings → Permalink → Save)
3. Check .htaccess per REST API

### Cron non funziona
```bash
wp cron event list | grep ipv
wp cron event run ipv_vendor_reset_credits
```

## 📞 Supporto

- **Email:** support@ilpuntodivista.com
- **Documentazione:** https://bissolomarket.com/docs/ipv-pro-vendor/
- **GitHub:** (privato)

## 📝 Changelog

### 1.1.0
- Aggiunto pannello impostazioni per YouTube/SupaData/OpenAI API keys con salvataggio in wp_options
- Le API keys sono ora lette prima dalle opzioni, con fallback alle costanti di class-api-gateway.php

## 1.0.0 (2024-12-06)
- Release iniziale
- License management system
- API Gateway completo
- WooCommerce integration
- Remote updates system
- Admin dashboard
- Customer portal

## 📜 Licenza

Proprietario - Tutti i diritti riservati.

## 👨‍💻 Autore

**Daniele Milone**
- GitHub: https://github.com/daniemi1977
- Email: info@ilpuntodivista.com

---

**Made with ❤️ for IPV Production System**
