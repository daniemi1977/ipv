# IPV Pro - Pacchetto Completo CLIENT + SERVER
## Versione: 1.4.8 (16 Dicembre 2025)

Questo pacchetto contiene entrambi i plugin necessari per il sistema IPV Production System Pro.

---

## 📦 Contenuto del Pacchetto

### 🎬 CLIENT Plugin (Production Site)
**Directory:** `client/ipv-production-system-pro-optimized/`
**Versione:** 10.3.2-FIXED2
**Installazione:** Sito WordPress di produzione del cliente

**Funzionalità:**
- Generazione descrizioni YouTube con AI
- Sistema crediti e licensing
- Template BASE incluso (gratuito per tutti)
- Template GOLDEN PROMPT personalizzato (per licenze Premium)
- Auto-download Golden Prompt dal SERVER
- Gestione video e trascrizioni
- Analisi AI: Argomenti, Ospiti, Persone/Enti

### 🏢 SERVER Plugin (Vendor Site)
**Directory:** `server/ipv-pro-vendor-optimized-v1.4.8/`
**Versione:** 1.4.8
**Installazione:** Tuo sito WordPress (vendor/amministratore)

**Funzionalità:**
- Gestione licenze e attivazioni
- Sistema billing WooCommerce
- 8 piani SaaS (Trial, Starter, Professional, Business, Executive, Golden Prompt, IPV Pro 10, IPV Pro 100)
- **NUOVO v1.4.8:** Auto-generazione Golden Prompt con form guidato
- Sistema Dual-Template (BASE gratuito + GOLDEN Premium)
- API Gateway per YouTube Data API
- Dashboard admin completa
- Statistiche e report

---

## 🚀 Installazione

### 1. **Installa SERVER Plugin (sul tuo sito vendor)**

```bash
# Vai nella directory server
cd server/ipv-pro-vendor-optimized-v1.4.8/

# Crea ZIP per upload WordPress
zip -r ipv-pro-vendor-v1.4.8.zip .

# Carica su WordPress:
# Dashboard → Plugin → Aggiungi nuovo → Carica plugin
```

**Oppure via FTP:**
```bash
# Carica l'intera cartella in:
wp-content/plugins/ipv-pro-vendor-optimized-v1.4.8/
```

**Setup Iniziale:**
1. Attiva il plugin
2. Vai su **IPV Vendor → Setup Wizard**
3. Segui i 4 step di configurazione
4. Configura WooCommerce e i prodotti SaaS

---

### 2. **Installa CLIENT Plugin (su sito produzione cliente)**

```bash
# Vai nella directory client
cd client/ipv-production-system-pro-optimized/

# Crea ZIP per upload WordPress
zip -r ipv-production-system-pro-v10.3.2.zip .

# Carica su WordPress:
# Dashboard → Plugin → Aggiungi nuovo → Carica plugin
```

**Oppure via FTP:**
```bash
# Carica l'intera cartella in:
wp-content/plugins/ipv-production-system-pro-optimized/
```

**Setup Iniziale:**
1. Attiva il plugin
2. Vai su **IPV Pro → Impostazioni**
3. Inserisci License Key (acquistata dal SERVER)
4. Attiva la licenza
5. Configura API keys (OpenAI, ElevenLabs, ecc.)

---

## 🆕 Novità v1.4.8 - Auto-Generazione Golden Prompt

### **SERVER - Nuova Funzionalità**

Ora puoi generare automaticamente Golden Prompt personalizzati tramite form guidato!

**Flusso di Utilizzo:**

1. **Tabella Licenze** → Trova licenza Golden Prompt
2. Clicca **"⚙️ Configura"**
3. Compila form con dati cliente:
   - 📺 Nome Canale (obbligatorio)
   - 🔗 Link Social (Telegram, Facebook, Instagram, Sito, Donazioni)
   - 🤝 Sponsor (nome + link, opzionale)
   - 💬 Testo "Supporta il Canale" (customizzabile)
4. Clicca **"✨ Genera Golden Prompt"**
5. Sistema genera automaticamente file .txt personalizzato
6. Clicca **"⭐ Abilita"** nella tabella licenze
7. CLIENT scarica automaticamente il template

**Template Generato Include:**
- ✨ Descrizione ottimizzata
- 🗂️ Argomenti Trattati (estratti da AI)
- 👤 Ospiti (estratti da AI)
- 🏛️ Persone/Enti Menzionati (estratti da AI)
- 🤝 Sponsor personalizzato
- 💬 Supporta il Canale (custom text)
- ⏱️ Capitoli/timestamp
- 🔧 Link Utili (social personalizzati)
- 🏷️ Hashtag strategici

---

## 📋 Sistema Dual-Template

### **Template BASE (Gratuito)**
Disponibile per tutte le licenze attive:
- Descrizione
- Capitoli
- Hashtag

**Endpoint:** `GET /license/download-template-base`

### **Template GOLDEN PROMPT (Premium)**
Solo per licenze Golden Prompt con configurazione abilitata:
- Tutto del BASE +
- Argomenti trattati
- Ospiti e relatori
- Persone/enti menzionati
- Link sponsor personalizzati
- Link social personalizzati

**Endpoint:** `GET /license/download-golden-prompt`

---

## 🔑 Piani SaaS Disponibili

| Piano | Prezzo | Crediti | Attivazioni | Tipo |
|-------|--------|---------|-------------|------|
| Trial | Gratis | 10 (una tantum) | 1 | Once |
| Starter | €9.90/mese | 50/mese | 1 | Subscription |
| Professional | €29.90/mese | 200/mese | 5 | Subscription |
| Business | €99/mese | 600/mese | 10 | Subscription |
| Executive | €499/mese | 2000/mese | 50 | Subscription |
| **Golden Prompt** | €59 (una tantum) | 0 | 1 | Premium Asset |
| IPV Pro - 10 | €5 (una tantum) | 10 extra | - | Once |
| IPV Pro - 100 | €49 (una tantum) | 100 extra | - | Once |

---

## 🔗 API Endpoints Principali

### **SERVER Endpoints:**

```
POST   /wp-json/ipv-vendor/v1/license/activate
POST   /wp-json/ipv-vendor/v1/license/deactivate
POST   /wp-json/ipv-vendor/v1/license/validate
GET    /wp-json/ipv-vendor/v1/license/info
GET    /wp-json/ipv-vendor/v1/license/download-template-base
GET    /wp-json/ipv-vendor/v1/license/download-golden-prompt
POST   /wp-json/ipv-vendor/v1/youtube/video-data
POST   /wp-json/ipv-vendor/v1/youtube/channel-videos
```

---

## 📞 Supporto

Per problemi o domande:
- GitHub Issues: https://github.com/daniemi1977/ipv/issues
- Branch corrente: `claude/explore-repository-QSsLd`

---

## 📝 Changelog

### v1.4.8 (2025-12-16) - SERVER
- ✨ Sistema Auto-Generazione Golden Prompt con form guidato
- ⚙️ Nuova pagina "Configura Golden Prompt"
- 📝 9 campi personalizzabili (canale, social, sponsor, CTA)
- 🤖 Sezioni AI-driven automatiche (argomenti, ospiti, persone)
- 💾 Metadata salvati per ogni configurazione
- 🔄 Rigenerazione facile per aggiornamenti

### v1.4.7 (2025-12-16) - SERVER
- 📄 Template BASE gratuito per tutti
- 🔀 Sistema Dual-Template (BASE vs GOLDEN)
- 📥 Endpoint download-template-base
- 🎯 Admin UI per gestione Golden Prompt

### v10.3.2-FIXED2 (2025-12-15) - CLIENT
- 🐛 Fix vari e ottimizzazioni
- 🔗 Integrazione con nuovo sistema Golden Prompt
- 📊 Miglioramenti UI/UX

---

## ✅ Requisiti Sistema

**SERVER:**
- WordPress 6.0+
- PHP 8.0+
- WooCommerce 8.0+
- MySQL 5.7+

**CLIENT:**
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+
- API Keys: OpenAI, ElevenLabs (opzionali)

---

**Buon utilizzo! 🚀**
