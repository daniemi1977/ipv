# IPV Pro Vendor System v1.0

> Sistema SaaS completo per vendere licenze IPV Pro Plugin via WooCommerce

![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588a.svg)

## Panoramica

IPV Pro Vendor System e' il cuore del sistema SaaS per la gestione di licenze software WordPress. Permette di vendere, gestire e monitorare licenze per il plugin client IPV Production System Pro.

## Funzionalita' Principali

### Sistema Licenze
- Generazione automatica license key (formato corto/lungo)
- Attivazione/Deattivazione con domain binding
- Limite attivazioni configurabile per piano
- Scadenza automatica annuale (trial mai scade)
- Multi-formato license key supportato

### Piani e Pricing
| Piano | Crediti | Durata | Prezzo Suggerito |
|-------|---------|--------|------------------|
| Trial | 5 | Illimitata | Gratis |
| Basic | 300/anno | 365 giorni | 110 EUR/anno |
| Pro | 600/anno | 365 giorni | 220 EUR/anno |
| Business | 1200/anno | 365 giorni | 330 EUR/anno |
| Enterprise | 1800/anno | 365 giorni | 440 EUR/anno |

### Golden Prompt System
- Template personalizzabile per ogni cliente
- 18 flags per personalizzazione sezioni
- Compilazione automatica con dati cliente
- Sincronizzazione sicura via API REST
- Push automatico al client

### API Gateway Integrato
- Proxy per YouTube Data API
- Proxy per OpenAI (GPT-4o, GPT-4o-mini, ecc.)
- Proxy per SupaData (trascrizioni)
- Rate limiting configurabile
- Logging completo

### Dashboard Analytics
- MRR/ARR in tempo reale
- Grafici utilizzo crediti
- Distribuzione piani
- Customer LTV
- Export CSV

### Sicurezza
- Rate limiting per endpoint
- Audit log completo
- HMAC signature
- Domain binding
- Encryption AES-256

## Requisiti

- WordPress 6.0+
- PHP 8.0+
- WooCommerce 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- OpenSSL extension
- SSL Certificate (HTTPS)

## Installazione

1. **Upload**: Carica il file ZIP in WordPress > Plugin > Aggiungi nuovo
2. **Attiva**: Attiva il plugin
3. **Wizard**: Segui il wizard di configurazione (5 passi)
4. **API Keys**: Configura le chiavi API (YouTube, OpenAI, SupaData)
5. **Prodotti**: Crea i prodotti WooCommerce automaticamente

## Configurazione API Keys

**Dove trovare le chiavi:**
- YouTube: https://console.cloud.google.com/apis/credentials
- SupaData: https://app.supadata.ai/settings/api-keys
- OpenAI: https://platform.openai.com/api-keys

## API Endpoints

### Licenze
```
POST /wp-json/ipv-vendor/v1/license/activate
POST /wp-json/ipv-vendor/v1/license/deactivate
GET  /wp-json/ipv-vendor/v1/license/info
```

### Golden Prompt
```
GET  /wp-json/ipv-vendor/v1/golden-prompt
POST /wp-json/ipv-vendor/v1/golden-prompt/compile
GET  /wp-json/ipv-vendor/v1/golden-prompt/hash
```

### Gateway
```
POST /wp-json/ipv-vendor/v1/youtube/video-data
POST /wp-json/ipv-vendor/v1/ai/generate
POST /wp-json/ipv-vendor/v1/transcription/get
```

## Database Schema

Il plugin crea automaticamente queste tabelle:
- `wp_ipv_licenses` - Licenze e status
- `wp_ipv_credit_ledger` - Storico transazioni crediti
- `wp_ipv_golden_prompts` - Configurazioni Golden Prompt
- `wp_ipv_rate_limits` - Rate limiting
- `wp_ipv_audit_log` - Audit trail

## Changelog

### v1.0.0 (2025-12-20)
- Prima release commerciale stabile
- Sistema licenze completo con attivazione/deattivazione
- Golden Prompt con 18 flags personalizzabili
- API Gateway integrato (YouTube, OpenAI, SupaData)
- Dashboard analytics con MRR/ARR
- Encryption AES-256 per dati sensibili
- Rate limiting configurabile
- Multi-lingua (IT, EN, FR, DE, ES, PT, RU)
- WooCommerce HPOS compatibile
- Setup Wizard 5 passi

## Supporto

- Documentazione: https://ipv-production-system.com/docs
- Email: support@ipv-production-system.com
- GitHub: https://github.com/daniemi1977/ipv/issues

## Licenza

GPL v2 or later

---

Made with love by IPV Team
