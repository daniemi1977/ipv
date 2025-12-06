# 🚀 IPV Production System Pro v10.0.0 - Cloud Edition

**Il sistema WordPress più avanzato per importare, ottimizzare e monetizzare i tuoi video YouTube con AI.**

[![Version](https://img.shields.io/badge/version-10.0.0-blue.svg)](https://github.com/daniemi1977/ipv)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-green.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net)

---

## 🎯 Cosa Fa

IPV Pro automatizza completamente l'importazione di video YouTube nel tuo WordPress, generando:

- ✅ **Trascrizioni AI** complete (SupaData)
- ✅ **Descrizioni SEO** ottimizzate (GPT-4o)
- ✅ **Post WordPress** pronti alla pubblicazione
- ✅ **Video Wall** responsive con shortcodes
- ✅ **Sistema licenze** SaaS con WooCommerce

**Nuovo in v10.0**: Architettura SaaS con API Gateway centralizzato, credits system e remote updates.

---

## 📦 Download Plugin

### Plugin VENDOR (Server-Side)
Per installare su **bissolomarket.com** (server di vendita):

```
https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-pro-vendor-v1.0.0.zip
```

**Dimensione**: 41 KB
**Requisiti**: WordPress 6.0+, WooCommerce 8.0+, WooCommerce Subscriptions

### Plugin CLIENT (Client-Side)
Per **distribuire ai clienti**:

```
https://github.com/daniemi1977/ipv/raw/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-production-system-pro-v10.0.0.zip
```

**Dimensione**: 253 KB
**Requisiti**: WordPress 6.0+, PHP 7.4+, License key valida

---

## ⚡ Quick Start (30 minuti)

### 1. Installa Plugin VENDOR

```bash
# WordPress Admin → Plugin → Aggiungi nuovo → Carica
# Seleziona: ipv-pro-vendor-v1.0.0.zip
# Attiva plugin
```

### 2. Configura API Keys

```bash
# Edita: wp-content/plugins/ipv-pro-vendor/includes/class-api-gateway.php

const SUPADATA_API_KEY_1 = 'sd_YOUR_KEY_HERE';
const SUPADATA_API_KEY_2 = 'sd_YOUR_KEY_HERE';
const SUPADATA_API_KEY_3 = 'sd_YOUR_KEY_HERE';
const OPENAI_API_KEY = 'sk-proj-YOUR_KEY_HERE';
const YOUTUBE_API_KEY = 'YOUR_YOUTUBE_KEY_HERE';
```

### 3. Crea Prodotti WooCommerce

4 piani disponibili:

| Piano | Prezzo | Video/Mese |
|-------|--------|------------|
| Free | €0 | 10 |
| Basic | €9,99/mese | 100 |
| Pro | €19,99/mese | 200 |
| Premium | €39,99/mese | 500 |

Vedi guida: [NUOVI-PIANI-SETUP.md](NUOVI-PIANI-SETUP.md)

### 4. Testa Acquisto

```bash
# Compra piano Free (€0)
# Controlla email → ricevi license key
# Verifica: Video IPV → Licenze
```

### 5. Distribuisci Plugin CLIENT

```bash
# Il cliente scarica da email (link auto-generato)
# Oppure: fornisci link download manuale
# Cliente attiva licenza in: Video IPV → Licenza
```

**Documentazione completa**: [QUICK-START.md](QUICK-START.md)

---

## 🌟 Caratteristiche Principali

### 🤖 AI-Powered Content

- **Trascrizioni automatiche** con SupaData (50+ lingue)
- **Descrizioni SEO** generate da GPT-4o
- **Tags e categorie** automatici
- **Golden Prompt** ottimizzato per ranking YouTube

### 🔒 SaaS Licensing System (v10.0)

- **API Gateway** centralizzato (API keys mai esposte)
- **License Manager** con WooCommerce Subscriptions
- **Credits system** con reset mensile automatico
- **Remote updates** distribuiti da vendor server
- **Multi-sito** (1, 3, 5 attivazioni per piano)

### 📊 Business Intelligence

- **Dashboard MRR** (Monthly Recurring Revenue)
- **Analytics utilizzo** per cliente
- **Churn rate** monitoring
- **Export CSV/PDF** reports

### 📺 Video Management

- Import **canale YouTube completo** in un click
- Import **playlist** pubbliche
- Import **singolo video** via URL
- **Video Wall** responsive con shortcodes
- **Widget WordPress** per sidebar

---

## 🏗️ Architettura Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                    VENDOR SERVER                            │
│                 (bissolomarket.com)                         │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Plugin VENDOR v1.0.0                                 │  │
│  │                                                      │  │
│  │ • License Manager                                   │  │
│  │ • API Gateway (SupaData, OpenAI, YouTube)          │  │
│  │ • Credits System                                    │  │
│  │ • WooCommerce Integration                           │  │
│  │ • Remote Updates Server                             │  │
│  │ • Admin Dashboard                                    │  │
│  └──────────────────────────────────────────────────────┘  │
│                          ▲                                  │
│                          │ REST API                         │
│                          │ (Bearer Token)                   │
└──────────────────────────┼──────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         │                 │                 │
         ▼                 ▼                 ▼
┌────────────────┐ ┌────────────────┐ ┌────────────────┐
│ CLIENTE 1      │ │ CLIENTE 2      │ │ CLIENTE N      │
│                │ │                │ │                │
│ Plugin CLIENT  │ │ Plugin CLIENT  │ │ Plugin CLIENT  │
│ v10.0.0        │ │ v10.0.0        │ │ v10.0.0        │
│                │ │                │ │                │
│ License:       │ │ License:       │ │ License:       │
│ ABCD-1234-...  │ │ EFGH-5678-...  │ │ IJKL-9012-...  │
│                │ │                │ │                │
│ Piano: Basic   │ │ Piano: Pro     │ │ Piano: Premium │
│ Credits: 100   │ │ Credits: 200   │ │ Credits: 500   │
└────────────────┘ └────────────────┘ └────────────────┘
```

**Flusso Chiamata API**:
1. Cliente importa video YouTube
2. Plugin CLIENT invia richiesta a VENDOR (con license key)
3. VENDOR valida licenza + check credits
4. VENDOR chiama API esterne (SupaData/OpenAI) con proprie keys
5. VENDOR decrementa credits cliente
6. VENDOR restituisce risultato a CLIENT
7. CLIENT crea post WordPress

**Vantaggi**:
- ✅ API keys MAI esposte ai clienti
- ✅ Controllo completo costi API
- ✅ Rate limiting server-side (impossibile bypassare)
- ✅ Updates centralizzati

---

## 📋 Requisiti

### VENDOR Server

| Componente | Requisito |
|------------|-----------|
| WordPress | ≥ 6.0 |
| PHP | ≥ 7.4 (consigliato 8.0+) |
| MySQL | ≥ 5.7 / MariaDB ≥ 10.3 |
| WooCommerce | ≥ 8.0 |
| WC Subscriptions | Required |
| SSL | HTTPS required |
| RAM | ≥ 2GB |
| Storage | ≥ 10GB SSD |

### CLIENT

| Componente | Requisito |
|------------|-----------|
| WordPress | ≥ 6.0 |
| PHP | ≥ 7.4 |
| MySQL | ≥ 5.7 |
| cURL | Enabled |
| License | Valida e attiva |

### API Keys (solo VENDOR)

- **SupaData** (3 keys): https://supadata.ai
- **OpenAI** (GPT-4o): https://platform.openai.com
- **YouTube Data API v3**: https://console.cloud.google.com

---

## 💰 Pricing Plans

| Piano | Prezzo | Video/Mese | Siti | Target |
|-------|--------|------------|------|--------|
| **Free** | €0 | 10 | 1 | Test & Hobby |
| **Basic** | €9,99/mese | 100 | 1 | Blogger |
| **Pro** ⭐ | €19,99/mese | 200 | 3 | Creator |
| **Premium** | €39,99/mese | 500 | 5 | Agenzie |

**Calcolo Revenue (scenario 200 clienti)**:
```
100x Free (€0)         = €0/mese
60x Basic (€9,99)      = €599,40/mese
30x Pro (€19,99)       = €599,70/mese
10x Premium (€39,99)   = €399,90/mese
───────────────────────────────────────
MRR:                   = €1.599/mese
ARR:                   = €19.188/anno

Costi API:             = ~€350/mese
PROFITTO NETTO:        = €1.249/mese
Margine:               = 78% 🚀
```

Dettagli: [PRICING-PLANS.md](PRICING-PLANS.md)

---

## 📚 Documentazione

| File | Descrizione |
|------|-------------|
| [FEATURES.md](FEATURES.md) | **Elenco completo caratteristiche** |
| [QUICK-START.md](QUICK-START.md) | Setup rapido in 30 minuti |
| [DEPLOY-GUIDE-FINAL.md](DEPLOY-GUIDE-FINAL.md) | Deployment completo (5 step) |
| [NUOVI-PIANI-SETUP.md](NUOVI-PIANI-SETUP.md) | Creazione prodotti WooCommerce |
| [PRICING-PLANS.md](PRICING-PLANS.md) | Strategia pricing + revenue |
| [ELEMENTOR-IMPORT-GUIDE.md](ELEMENTOR-IMPORT-GUIDE.md) | Pagina pricing Elementor |
| [DOWNLOAD-PLUGINS.md](DOWNLOAD-PLUGINS.md) | Link download diretti |
| [DOWNLOAD-HOSTING.md](DOWNLOAD-HOSTING.md) | Hosting pagina download |

---

## 🚀 Roadmap

### v10.1.0 (Q1 2026)
- [ ] Gutenberg blocks nativi
- [ ] Video playlists frontend
- [ ] Advanced analytics
- [ ] A/B testing descriptions

### v11.0.0 (Q2 2026)
- [ ] Mobile app (iOS/Android)
- [ ] AI thumbnail generator
- [ ] Auto-posting social media
- [ ] Multi-language admin

### v12.0.0 (Q3 2026)
- [ ] Video hosting proprietario
- [ ] Live streaming integration
- [ ] Monetization features
- [ ] Affiliate program

---

## 🆘 Support

### Community Support (Free/Basic)
- 📖 [Documentation](https://github.com/daniemi1977/ipv/wiki)
- 🐛 [Bug Reports](https://github.com/daniemi1977/ipv/issues)
- 💡 [Feature Requests](https://github.com/daniemi1977/ipv/discussions)

### Priority Support (Pro/Premium)
- 📧 Email: support@ipvpro.com (4-12h response)
- 💬 Discord: community.ipvpro.com
- 📞 Video call 1-to-1 (Premium only)

---

## 📜 Changelog

### v10.0.0 - 2025-12-06 (Cloud Edition)

**Nuove Features**:
- ✨ API Gateway centralizzato (API keys server-side)
- ✨ License Manager con WooCommerce integration
- ✨ Credits system con reset mensile
- ✨ Remote updates automatici
- ✨ 4 piani pricing (Free/Basic/Pro/Premium)
- ✨ Admin dashboard con MRR tracking
- ✨ Customer portal per gestione licenze

**Miglioramenti**:
- 🔒 Sicurezza massima (no API keys esposte)
- 📊 Analytics e reporting completi
- 🚀 Performance ottimizzate
- 📱 UI/UX migliorata

**Breaking Changes**:
- ⚠️ v9.0 non compatibile con v10.0 (architettura diversa)
- ⚠️ Richiede migrazione manuale da v9.0

Vedi changelog completo nelle versioni precedenti.

---

## 🔐 Sicurezza

Scoperto una vulnerabilità? Per favore **NON** aprire issue pubblico.

Invia email a: security@ipvpro.com

Rispondiamo entro 48 ore e rilasciamo patch prioritaria.

---

## 📄 License

**Proprietaria** - Tutti i diritti riservati

© 2025 IPV Production Team. Questo software è fornito "as is" senza garanzie.

La distribuzione, modifica o vendita non autorizzata è vietata.

---

## 🙏 Credits

**Developed by**: IPV Production Team
**Lead Developer**: Daniele Missori
**Version**: 10.0.0 Cloud Edition
**Released**: 6 Dicembre 2025

**Powered by**:
- [SupaData.ai](https://supadata.ai) - AI Transcriptions
- [OpenAI GPT-4o](https://openai.com) - AI Descriptions
- [WordPress](https://wordpress.org) - CMS Platform
- [WooCommerce](https://woocommerce.com) - E-Commerce

---

## 🌐 Links

- 🐙 **GitHub**: https://github.com/daniemi1977/ipv
- 📖 **Documentazione**: Vedi file .md nella repository
- 🐛 **Bug Reports**: GitHub Issues
- 💡 **Feature Requests**: GitHub Discussions

---

<p align="center">
  <strong>Made with ❤️ in Italy</strong><br>
  IPV Production System Pro v10.0.0 - Cloud Edition
</p>
