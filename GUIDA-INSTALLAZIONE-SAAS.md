# 🚀 IPV Production System Pro - Sistema SaaS COMPLETO

## 📋 PANORAMICA

Sistema SaaS WordPress completo per la gestione e vendita di licenze per IPV Production System Pro.

**Versioni corrette:**
- ✅ **Client Plugin**: `ipv-production-system-pro-v10.0.3-saas-fixed.zip` (246 KB)
- ✅ **Vendor Plugin**: `ipv-pro-vendor-v1.3.0-fixed.zip` (56 KB)

---

## 🔧 PROBLEMI RISOLTI

### Versione Originale (v10.0.2)
1. ❌ **Dominio hardcoded**: `https://bissolomarket.com` (non più esistente)
2. ❌ **Link hardcoded** nella pagina licenza
3. ❌ **Riferimenti brand specifici** (Il Punto di Vista, etc.)

### Versione Corretta (v10.0.3)
1. ✅ **DEFAULT_SERVER vuoto**: L'utente deve configurare il proprio server
2. ✅ **Link rimossi**: Nessun riferimento a domini esterni
3. ✅ **Plugin generico**: Funziona con qualsiasi tema e dominio
4. ✅ **Author/URI aggiornati**: Riferimento GitHub

---

## 🏗️ ARCHITETTURA SISTEMA

```
┌──────────────────────────────────────────────┐
│      SERVER VENDOR (https://aiedintorni.it)  │
│  ┌────────────────────────────────────────┐  │
│  │   IPV PRO VENDOR v1.3.0                │  │
│  │   - Gestione licenze                   │  │
│  │   - Piani SaaS                         │  │
│  │   - API Gateway (YouTube/OpenAI/       │  │
│  │     SupaData)                          │  │
│  │   - WooCommerce Integration            │  │
│  └────────────────────────────────────────┘  │
│             │                                 │
│             │ REST API: /wp-json/            │
│             │           ipv-vendor/v1/        │
└─────────────┼─────────────────────────────────┘
              │
              │ HTTPS
              ▼
┌──────────────────────────────────────────────┐
│       SITO CLIENTE (cliente.com)             │
│  ┌────────────────────────────────────────┐  │
│  │   IPV PRODUCTION SYSTEM PRO v10.0.3    │  │
│  │   - Import video YouTube                │  │
│  │   - Trascrizioni (via server)           │  │
│  │   - Descrizioni AI (via server)         │  │
│  │   - Video Wall frontend                 │  │
│  └────────────────────────────────────────┘  │
└──────────────────────────────────────────────┘
```

---

## 📦 INSTALLAZIONE

### PARTE 1: SERVER VENDOR (aiedintorni.it)

#### 1.1 Prerequisiti
- ✅ WordPress 6.0+
- ✅ PHP 8.0+
- ✅ **WooCommerce** installato e attivo
- ✅ SSL attivo (HTTPS)

#### 1.2 Installa Plugin Vendor

```bash
1. Vai su WordPress Admin → Plugin → Aggiungi nuovo → Carica plugin
2. Carica: ipv-pro-vendor-v1.3.0-fixed.zip
3. Attiva plugin
```

#### 1.3 Configura API Keys

```
WordPress Admin → IPV Pro Vendor → Impostazioni

API KEYS (OBBLIGATORIE):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
┌─ YouTube Data API v3 ─────────────────────┐
│ https://console.cloud.google.com/apis     │
│ Abilita: YouTube Data API v3              │
│ Chiave: AIza...                           │
└───────────────────────────────────────────┘

┌─ OpenAI API (GPT-4) ──────────────────────┐
│ https://platform.openai.com/api-keys      │
│ Chiave: sk-proj-...                       │
└───────────────────────────────────────────┘

┌─ SupaData Transcription API ──────────────┐
│ https://supadata.ai/api-keys              │
│ Key 1: supa_...                           │
│ Key 2: supa_... (opzionale)               │
│ Key 3: supa_... (opzionale)               │
│                                           │
│ Rotazione: Fixed o Round-Robin            │
└───────────────────────────────────────────┘
```

**⚠️ IMPORTANTE**: Le API keys NON vengono mai esposte ai clienti!

#### 1.4 Crea Piani SaaS

```
WordPress Admin → IPV Pro Vendor → Piani SaaS → Aggiungi Nuovo

ESEMPIO PIANO "PROFESSIONAL":
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Nome Piano:          Professional
Slug:                pro
Prezzo:              29.99
Periodo:             month
Crediti Totali:      100
Limite Attivazioni:  3
Features:            - 100 trascrizioni/mese
                     - 3 siti
                     - Supporto prioritario

[Carica Plugin ZIP]: ipv-production-system-pro-v10.0.3-saas-fixed.zip
```

**Clicca "Salva Piano"** → Il sistema crea automaticamente il prodotto WooCommerce

#### 1.5 Genera Prodotti WooCommerce

```
IPV Pro Vendor → Piani SaaS → Tab "Prodotti WooCommerce"
→ Click "Genera Prodotti da Piani"
```

Questo crea automaticamente:
- Prodotti WooCommerce
- Download del plugin client
- Metadati per generazione licenze

#### 1.6 Verifica Installazione Server

```
TEST 1: Verifica Endpoint
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
URL: https://aiedintorni.it/wp-json/ipv-vendor/v1/health

Risposta attesa:
{
  "status": "ok",
  "version": "1.3.0",
  "api_keys_configured": {
    "youtube": true,
    "openai": true,
    "supadata_1": true
  }
}
```

---

### PARTE 2: INSTALLAZIONE CLIENT (Sito Cliente)

#### 2.1 Come il Cliente Ottiene il Plugin

**OPZIONE A: Acquisto via WooCommerce**
```
1. Cliente visita: https://aiedintorni.it/shop/
2. Sceglie piano (es: Professional - €29.99/mese)
3. Completa checkout
4. Riceve email con:
   - License Key: XXXX-XXXX-XXXX-XXXX
   - Link download plugin
```

**OPZIONE B: Licenza Manuale (Admin)**
```
IPV Pro Vendor → Licenze → Crea Licenza Manuale

Email:               cliente@example.com
Piano:               Professional
Crediti:             100
Limite Attivazioni:  3
Scadenza:            2025-12-31

→ [Genera Licenza]
→ Copia License Key e inviala al cliente
```

#### 2.2 Cliente: Installa Plugin

```bash
1. Scarica ipv-production-system-pro-v10.0.3-saas-fixed.zip
2. WordPress Admin → Plugin → Aggiungi nuovo → Carica
3. Attiva plugin
```

#### 2.3 Cliente: Configura Server

```
WordPress Admin → IPV Videos → Server

┌─ Configurazione Server ────────────────────┐
│                                            │
│ Server URL:                                │
│ https://aiedintorni.it                     │
│                                            │
│ [Test Connessione]                         │
│                                            │
│ Status: ✅ Server raggiungibile            │
│ API Keys: ✅ Configurate                   │
│                                            │
│ [Salva]                                    │
└────────────────────────────────────────────┘
```

#### 2.4 Cliente: Attiva Licenza

```
WordPress Admin → IPV Videos → Licenza

┌─ Attivazione Licenza ──────────────────────┐
│                                            │
│ License Key:                               │
│ [XXXX-XXXX-XXXX-XXXX]                      │
│                                            │
│ [Attiva Licenza]                           │
│                                            │
└────────────────────────────────────────────┘

Dopo attivazione vedi:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Licenza Attiva

License Key:    XXXX-****-****-XXXX
Piano:          Professional
Email:          cliente@example.com
Scadenza:       31/12/2025
Attivata il:    08/12/2024 20:45

📊 Crediti Mensili
[████████████████░░░░] 85 / 100
Reset: 01/01/2025

[Aggiorna Info] [Deattiva Licenza]
```

---

## 🎯 TEST COMPLETO FUNZIONAMENTO

### TEST 1: Import Video YouTube

```
SITO CLIENTE:
WordPress Admin → IPV Videos → Import

1. Incolla URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ
2. Click "Importa"
3. Sistema:
   ✅ Chiama server per metadati YouTube
   ✅ Crea post "ipv_video"
   ✅ Scarica thumbnail
   ✅ Richiede trascrizione (usa 1 credito)
   ✅ Genera descrizione AI

Risultato: Video importato con trascrizione e descrizione
```

### TEST 2: Verifica Crediti

```
SERVER VENDOR:
IPV Pro Vendor → Licenze → [Trova licenza cliente]

Crediti:
- Totali: 100
- Usati: 1
- Rimanenti: 99
- Reset: 01/01/2025

API Logs:
┌────────────────────────────────────────────────┐
│ 2024-12-08 20:45:12 | Transcript | dQw4w9WgXcQ │
│ Status: 200 | Credits: -1 | Response: 2.3s    │
└────────────────────────────────────────────────┘
```

### TEST 3: Frontend Video Wall

```
SITO CLIENTE:
1. Crea pagina: "Video"
2. Aggiungi shortcode: [ipv_video_wall]
3. Pubblica
4. Visita frontend

Vedi:
- Griglia video con thumbnail
- Filtri per categoria
- Player YouTube embedded
- Trascrizione e descrizione
```

---

## ✅ CHECKLIST COMPLETA

### Server Vendor (aiedintorni.it)
- [ ] WooCommerce installato e attivo
- [ ] Plugin `ipv-pro-vendor-v1.3.0-fixed.zip` installato
- [ ] YouTube API key configurata
- [ ] OpenAI API key configurata
- [ ] SupaData API key configurata
- [ ] Almeno 1 piano SaaS creato
- [ ] Prodotti WooCommerce generati
- [ ] Endpoint `/health` risponde correttamente
- [ ] SSL attivo (HTTPS)

### Client (Sito Cliente)
- [ ] Plugin `ipv-production-system-pro-v10.0.3-saas-fixed.zip` installato
- [ ] Server URL configurato: `https://aiedintorni.it`
- [ ] Test connessione superato
- [ ] License key inserita
- [ ] Licenza attivata (status: ✅ Attiva)
- [ ] Crediti > 0
- [ ] Test import video riuscito
- [ ] Frontend video wall funzionante

---

## 🔍 TROUBLESHOOTING

### ❌ Problema: "Server non raggiungibile"

**Causa**: URL server errato o firewall

**Soluzione**:
```bash
# Test manuale endpoint
curl https://aiedintorni.it/wp-json/ipv-vendor/v1/health

# Verifica SSL
openssl s_client -connect aiedintorni.it:443
```

---

### ❌ Problema: "License key non valida"

**Causa**: License key inesistente o disattivata

**Soluzione**:
```
SERVER:
IPV Pro Vendor → Licenze → [Cerca license key]

Verifica:
- Status = "active" (non "inactive" o "expired")
- Expires At non passato
- Email corretta
```

---

### ❌ Problema: "Limite attivazioni raggiunto"

**Causa**: Licenza già attiva su troppi siti

**Soluzione**:
```
SERVER:
IPV Pro Vendor → Licenze → [Licenza] → Tab "Attivazioni"

Vedi lista siti attivi:
1. cliente.com       ✅ Attivo
2. test.cliente.com  ✅ Attivo
3. dev.cliente.com   ✅ Attivo (LIMITE RAGGIUNTO)

→ Disattiva un sito per liberare slot
→ O aumenta limite attivazioni nel piano
```

---

### ❌ Problema: "Crediti esauriti"

**Causa**: Cliente ha usato tutti i crediti mensili

**Soluzione**:
```
OPZIONE A: Attendere reset mensile (1° del mese)

OPZIONE B: Admin aggiunge crediti manualmente
SERVER:
IPV Pro Vendor → Licenze → [Licenza] → Edit
Crediti Rimanenti: 0 → 50
[Salva]
```

---

### ❌ Problema: "API Key non configurata"

**Causa**: API keys mancanti nel server

**Soluzione**:
```
SERVER:
IPV Pro Vendor → Impostazioni

Verifica che TUTTE siano compilate:
✅ YouTube Data API v3:  AIza...
✅ OpenAI API Key:       sk-proj-...
✅ SupaData Key 1:       supa_...

Test:
curl -X POST https://aiedintorni.it/wp-json/ipv-vendor/v1/health
```

---

### ❌ Problema: "Trascrizione fallita"

**Cause possibili**:
1. Video privato/non disponibile
2. SupaData API key invalida
3. Crediti SupaData esauriti
4. Timeout (video troppo lungo)

**Soluzione**:
```
SERVER:
IPV Pro Vendor → Analytics → API Logs

Cerca chiamata transcript per video_id
Status Code:
- 200 → OK
- 401 → API key invalida
- 402 → Crediti SupaData esauriti
- 404 → Video non trovato
- 500 → Errore server SupaData

Se 402 → Ricarica crediti SupaData su https://supadata.ai
```

---

## 🔐 SICUREZZA

### API Keys
✅ **MAI esporre API keys nel client**
- Tutte le keys sono SOLO sul server vendor
- Client comunica solo con license key
- Server fa da proxy sicuro

### Validazione Richieste
✅ **Ogni chiamata API è validata**
```php
1. Verifica license key
2. Verifica site URL
3. Verifica crediti disponibili
4. Verifica scadenza licenza
5. Esegue azione
6. Decrementa crediti
7. Logga operazione
```

### Rate Limiting
✅ **Crediti mensili limitano l'uso**
- Impossibile abusare del sistema
- Reset automatico ogni mese
- Tracking completo nell'admin

---

## 📊 MONITORAGGIO

### Dashboard Server
```
IPV Pro Vendor → Dashboard

Oggi:
- 🎫 Licenze attive: 15
- 💰 Revenue mensile: €449.85
- 📈 Utilizzo API: 1,247 chiamate
- 💳 Crediti usati: 856 / 1,500

Grafici:
- Attivazioni nel tempo
- Utilizzo crediti
- Revenue trend
```

### Analytics
```
IPV Pro Vendor → Analytics

API Calls (ultimi 7 giorni):
┌──────────┬────────────┬─────────┬──────────┐
│ Endpoint │ Calls      │ Success │ Avg Time │
├──────────┼────────────┼─────────┼──────────┤
│ Transcr. │ 425        │ 98.2%   │ 2.3s     │
│ AI Desc. │ 398        │ 99.5%   │ 1.8s     │
│ YouTube  │ 425        │ 100%    │ 0.5s     │
└──────────┴────────────┴─────────┴──────────┘

Top Clients:
1. cliente1.com  →  145 calls
2. cliente2.com  →  98 calls
3. cliente3.com  →  67 calls
```

---

## 🎉 RIEPILOGO MODIFICHE v10.0.3

### File Modificati

**1. ipv-production-system-pro.php**
```diff
- Version: 10.0.2
+ Version: 10.0.3

- Plugin URI: https://bissolomarket.com/ipv-pro/
+ Plugin URI: https://github.com/daniemi1977/ipv

- Author: Daniele Bissoli / IPV
+ Author: IPV Team

- Author URI: https://ilpuntodivista.com
+ Author URI: https://github.com/daniemi1977/ipv
```

**2. includes/class-api-client.php**
```diff
- const DEFAULT_SERVER = 'https://bissolomarket.com';
+ const DEFAULT_SERVER = '';
```

**3. includes/class-license-manager-client.php**
```diff
- <a href="https://bissolomarket.com/ipv-pro/">Acquista Ora</a>
+ <p>Contatta il tuo fornitore di licenze</p>
```

---

## 📞 SUPPORTO

### Per Sviluppatori
- Repository: https://github.com/daniemi1977/ipv
- Issues: https://github.com/daniemi1977/ipv/issues

### Per Clienti Finali
Contatta il fornitore della tua licenza (aiedintorni.it)

---

## 📝 CHANGELOG

### v10.0.3 (2024-12-08) - FIXED
- ✅ Rimosso dominio hardcoded bissolomarket.com
- ✅ Rimossi link esterni non funzionanti
- ✅ Rimossi riferimenti brand-specific
- ✅ Plugin reso completamente generico
- ✅ Aggiornati header e metadati

### v10.0.2 (2024-12-05)
- Sistema SaaS completo
- Crediti mensili con tracking
- Golden Prompt configurabile

### v10.0.0 (2024-11-24)
- Architettura SaaS
- Sistema licenze
- API Gateway

---

**Versione Guida**: 1.0
**Data**: 8 Dicembre 2024
**Testato con**: WordPress 6.4+ / PHP 8.0+ / WooCommerce 8.0+
