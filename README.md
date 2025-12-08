# IPV Production System Pro - SaaS Edition

Sistema completo per vendere e gestire licenze per il plugin WordPress "IPV Production System Pro".

## 🚀 Quick Start

### File Corretti (v10.0.3)

- ✅ **ipv-production-system-pro-v10.0.3-saas-fixed.zip** (246 KB) - Plugin CLIENT
- ✅ **ipv-pro-vendor-v1.3.0-fixed.zip** (56 KB) - Plugin SERVER

### Installazione Rapida

**1. SERVER (https://aiedintorni.it)**
```bash
- Installa WooCommerce
- Carica: ipv-pro-vendor-v1.3.0-fixed.zip
- Configura API keys (YouTube, OpenAI, SupaData)
- Crea piani SaaS
- Genera prodotti WooCommerce
```

**2. CLIENT (sito cliente)**
```bash
- Carica: ipv-production-system-pro-v10.0.3-saas-fixed.zip
- Configura Server URL: https://aiedintorni.it
- Inserisci License Key
- Attiva licenza
```

## 📖 Documentazione Completa

Leggi: **GUIDA-INSTALLAZIONE-SAAS.md** per istruzioni dettagliate.

## 🔧 Problemi Risolti (v10.0.3)

- ✅ Rimosso dominio hardcoded `bissolomarket.com`
- ✅ Rimossi link brand-specific
- ✅ Plugin generico per tutti i temi e domini
- ✅ Author/URI aggiornati

## 🏗️ Architettura

```
SERVER (aiedintorni.it)          CLIENT (cliente.com)
├─ IPV Pro Vendor v1.3.0         ├─ IPV Pro v10.0.3
├─ Gestione Licenze              ├─ Import Video YouTube
├─ Piani SaaS                    ├─ Trascrizioni (via server)
├─ API Gateway                   ├─ AI Descriptions (via server)
└─ WooCommerce                   └─ Video Wall Frontend
```

## ✅ Checklist

### Server
- [ ] WooCommerce attivo
- [ ] API keys configurate
- [ ] Almeno 1 piano creato
- [ ] Test: `/wp-json/ipv-vendor/v1/health` → OK

### Client
- [ ] Server URL configurato
- [ ] Licenza attivata
- [ ] Crediti > 0
- [ ] Test import video → Funziona

## 📦 File nel Repository

```
ipv/
├── README.md                                          (questo file)
├── GUIDA-INSTALLAZIONE-SAAS.md                        (guida completa)
├── ipv-production-system-pro-v10.0.3-saas-fixed.zip  (CLIENT - FIXED)
├── ipv-pro-vendor-v1.3.0-fixed.zip                   (SERVER - FIXED)
├── ipv-production-system-pro-v10.0.2-saas.zip        (vecchia versione)
├── ipv-production-system-pro-v9.2.2 (2).zip          (originale non-SaaS)
└── ipv-pro-vendor-v1.3.0.zip                         (vecchia versione)
```

## 🔐 Sicurezza

- ✅ API keys SOLO sul server
- ✅ Client usa solo license key
- ✅ Validazione su ogni richiesta
- ✅ Rate limiting con crediti
- ✅ HTTPS obbligatorio

## 🆘 Supporto

- **Issues**: https://github.com/daniemi1977/ipv/issues
- **Server Vendor**: https://aiedintorni.it

## 📝 Changelog

### v10.0.3 (2024-12-08) - FIXED ✅
- Rimosso dominio hardcoded bissolomarket.com
- Plugin completamente generico
- Compatibile con qualsiasi dominio

### v10.0.2 (2024-12-05)
- Sistema SaaS completo
- Crediti mensili

### v10.0.0 (2024-11-24)
- Architettura SaaS
- Sistema licenze integrato

---

**Versione**: 10.0.3
**Testato**: WordPress 6.4+ / PHP 8.0+ / WooCommerce 8.0+
