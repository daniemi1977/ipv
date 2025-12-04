# IPV Production System Pro
## Manuale Utente Completo

**Versione Plugin**: 9.0.0
**Data**: Dicembre 2024
**Autore**: IPV Team

---

## Indice

1. [Introduzione](#introduzione)
2. [Requisiti di Sistema](#requisiti-di-sistema)
3. [Installazione](#installazione)
4. [Configurazione Iniziale](#configurazione-iniziale)
5. [Funzionalità Principali](#funzionalità-principali)
6. [Gestione Multilingua](#gestione-multilingua)
7. [Guida all'Uso](#guida-alluso)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)
10. [Supporto](#supporto)

---

## 1. Introduzione

**IPV Production System Pro** è un plugin WordPress professionale per l'importazione automatica di video da YouTube con generazione di trascrizioni e descrizioni AI.

### Caratteristiche Principali

✅ **Importazione automatica** da YouTube
✅ **Trascrizioni automatiche** tramite SupaData API
✅ **Descrizioni AI** generate con OpenAI (GPT)
✅ **Sistema di coda** per elaborazione asincrona
✅ **Auto-import RSS** da canali YouTube
✅ **Multilingua** (6 lingue: IT, DE, FR, ES, PT, RU + EN)
✅ **Custom Post Type** dedicato ai video
✅ **Shortcode** per galleria video
✅ **Golden Prompt** ottimizzato (350+ righe)

### Flusso di Lavoro

```
1. Importa URL YouTube
   ↓
2. Genera Trascrizione (SupaData API)
   ↓
3. Genera Descrizione AI (OpenAI)
   ↓
4. Pubblica Video nel sito
```

---

## 2. Requisiti di Sistema

### Requisiti Server

| Componente | Versione Minima | Consigliata |
|------------|----------------|-------------|
| **WordPress** | 5.8+ | 6.4+ |
| **PHP** | 7.4+ | 8.1+ |
| **MySQL** | 5.7+ | 8.0+ |
| **Memoria PHP** | 128MB | 256MB+ |
| **Max Execution Time** | 60s | 300s |

### API Keys Necessarie

1. **SupaData API Key** (Obbligatoria)
   - Per generare trascrizioni video
   - Sito: https://supadata.ai

2. **OpenAI API Key** (Obbligatoria)
   - Per generare descrizioni AI
   - Sito: https://platform.openai.com

3. **YouTube Data API v3** (Opzionale)
   - Per recuperare metadati video
   - Console: https://console.cloud.google.com

### Permessi WordPress

- **Administrator** o **Editor** con capacità `manage_options`

---

## 3. Installazione

### Metodo 1: Upload ZIP

1. Accedi al pannello WordPress
2. Vai su **Plugin** → **Aggiungi Nuovo**
3. Clicca su **Carica Plugin**
4. Seleziona il file `ipv-production-system-pro.zip`
5. Clicca **Installa Ora**
6. Clicca **Attiva Plugin**

### Metodo 2: FTP/SFTP

1. Decomprimi il file ZIP
2. Carica la cartella `ipv-production-system-pro/` in `/wp-content/plugins/`
3. Vai su **Plugin** nel pannello WordPress
4. Attiva **IPV Production System Pro**

### Verifica Installazione

Dopo l'attivazione, dovresti vedere:
- Nuova voce **IPV Production** nel menu admin
- Custom Post Type **IPV Videos** nel menu
- Menu **Impostazioni** disponibile

---

## 4. Configurazione Iniziale

### 4.1 Configurazione API Keys

1. Vai su **IPV Production** → **Settings**

2. **Sezione API Keys**:

   **SupaData API Key** (Obbligatoria)
   ```
   ┌─────────────────────────────────────┐
   │ sk-supadata-xxxxxxxxxxxxxxxxxxxxx   │
   └─────────────────────────────────────┘
   ```
   - Ottieni la chiave da: https://supadata.ai
   - Piano consigliato: Pro ($29/mese)

   **OpenAI API Key** (Obbligatoria)
   ```
   ┌─────────────────────────────────────┐
   │ sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx    │
   └─────────────────────────────────────┘
   ```
   - Ottieni la chiave da: https://platform.openai.com
   - Modello usato: GPT-4 o GPT-4-turbo

   **YouTube Data API Key** (Opzionale)
   ```
   ┌─────────────────────────────────────┐
   │ AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxxxx │
   └─────────────────────────────────────┘
   ```
   - Migliora l'accuratezza dei titoli video
   - Quota gratuita: 10,000 unità/giorno

3. **Modalità Trascrizione**:
   - **Auto (consigliato)**: Usa sottotitoli YouTube se disponibili, altrimenti genera
   - **Native**: Usa solo sottotitoli esistenti
   - **Generate**: Genera sempre nuove trascrizioni

4. Clicca **Salva Impostazioni**

### 4.2 Configurazione Filtri Import

**Durata Minima Video**
```
┌─────┬─────┐
│  15 │ min │  ← Esempio: escludi video sotto 15 minuti
└─────┴─────┘
```
- Imposta `0` per disabilitare il filtro
- Utile per escludere contenuti troppo brevi

**Escludi Shorts/Reels**
```
☑ Escludi automaticamente Shorts / Reels
```
- Riconosce video ≤ 90 secondi
- Riconosce URL con `/shorts/`

### 4.3 Parametri Canale

**Sponsor di Default**
```
┌─────────────────────────────────────┐
│ Biovital – Progetto Italia          │
└─────────────────────────────────────┘
```

**Link Sponsor**
```
┌─────────────────────────────────────┐
│ https://biovital-italia.com         │
└─────────────────────────────────────┘
```

### 4.4 Social Media

Compila i link ai tuoi canali social:
- Telegram
- Facebook
- Instagram
- Sito Web
- Email Contatto

Questi dati verranno inclusi nelle descrizioni generate dall'AI.

### 4.5 Custom AI Prompt (Avanzato)

Puoi sovrascrivere il **Golden Prompt** predefinito:
```
┌─────────────────────────────────────────────┐
│ Il tuo prompt personalizzato...             │
│                                             │
│ [Lascia vuoto per usare il Golden Prompt]  │
└─────────────────────────────────────────────┘
```

⚠️ **Attenzione**: Il Golden Prompt integrato è ottimizzato con 350+ righe. Modificalo solo se sai cosa stai facendo.

---

## 5. Funzionalità Principali

### 5.1 Dashboard

La **Dashboard** mostra lo stato del sistema:

```
╔════════════════════════════════════════╗
║  📊 Statistiche                        ║
╠════════════════════════════════════════╣
║  Video Pubblicati:           42        ║
║  In Coda:                     8        ║
║  RSS Auto-Import:        ✓ Attivo      ║
║  CRON:                   ✓ Attivo      ║
║    Prossima esecuzione: 14:30:00       ║
╚════════════════════════════════════════╝

╔════════════════════════════════════════╗
║  🔑 Stato API                          ║
╠════════════════════════════════════════╣
║  SupaData:  ✓ OK                       ║
║  OpenAI:    ✓ OK                       ║
║  YouTube:   ✗ Mancante                 ║
╚════════════════════════════════════════╝
```

**Elaborazione Manuale**

Se il CRON non funziona, puoi elaborare la coda manualmente:
```
┌────────────────────────────┐
│ ⚡ Elabora Coda Adesso     │
└────────────────────────────┘
```

### 5.2 Importa Video

**Importazione Singola**

1. Vai su **IPV Production** → **Import Video**
2. Incolla l'URL YouTube:
   ```
   ┌─────────────────────────────────────────┐
   │ https://www.youtube.com/watch?v=xxxxx   │
   └─────────────────────────────────────────┘
   ```
3. Clicca **Importa e Pubblica**

**Formati URL Supportati**
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/watch?v=VIDEO_ID&t=123s`

**Processo di Importazione**

```
Fase 1: Importazione Video (5-10s)
  ├─ Creazione Custom Post Type
  ├─ Download metadati YouTube
  └─ Salvataggio thumbnail

Fase 2: Generazione Trascrizione (30-60s)
  ├─ Richiesta a SupaData API
  ├─ Processing audio
  └─ Salvataggio trascrizione

Fase 3: Generazione AI (60-120s)
  ├─ Invio trascrizione a OpenAI
  ├─ Generazione descrizione
  └─ Formattazione output

Fase 4: Pubblicazione (5s)
  ├─ Salvataggio contenuto
  └─ Pubblicazione post
```

**Tempo Totale**: 2-3 minuti per video

### 5.3 Bulk Import

Per importare più video contemporaneamente:

1. Vai su **IPV Production** → **Bulk Import**
2. Incolla gli URL (uno per riga):
   ```
   https://www.youtube.com/watch?v=xxxxx1
   https://www.youtube.com/watch?v=xxxxx2
   https://www.youtube.com/watch?v=xxxxx3
   ```
3. Clicca **Importa Tutti**

I video verranno aggiunti alla coda ed elaborati automaticamente.

### 5.4 RSS Auto-Import

**Configurazione Auto-Import**

1. Vai su **IPV Production** → **RSS Auto-Import**
2. Incolla l'URL del feed RSS del canale YouTube:
   ```
   ┌─────────────────────────────────────────────────────┐
   │ https://www.youtube.com/feeds/videos.xml?channel_id=│
   │ UCxxxxxxxxxxxxxxxxxxxxxx                            │
   └─────────────────────────────────────────────────────┘
   ```

3. Imposta la frequenza:
   - ⏱️ **Ogni 30 minuti** (maggiore frequenza)
   - ⏱️ **Ogni ora** (consigliato)
   - ⏱️ **Ogni 6 ore** (bassa frequenza)
   - ⏱️ **Due volte al giorno** (minima frequenza)

4. Clicca **Attiva Auto-Import**

**Come Funziona**

```
1. CRON esegue check periodico
   ↓
2. Controlla nuovi video nel feed RSS
   ↓
3. Confronta con video già importati
   ↓
4. Aggiunge nuovi video alla coda
   ↓
5. Elaborazione automatica
```

### 5.5 Gestione Coda

**Visualizza Coda**

Vai su **IPV Production** → **Queue** per vedere:
```
╔═══════════════════════════════════════════╗
║  Video in Coda                             ║
╠═══════════════════════════════════════════╣
║  1. Come investire nel 2024      [Pending]║
║  2. Analisi mercati azionari     [Pending]║
║  3. Strategie di trading         [Process]║
║  4. Bitcoin e criptovalute       [Done]   ║
╚═══════════════════════════════════════════╝
```

**Stati Video**:
- 🟡 **Pending**: In attesa di elaborazione
- 🔵 **Processing**: In elaborazione
- 🟢 **Done**: Completato e pubblicato
- 🔴 **Error**: Errore durante l'elaborazione

**Azioni Disponibili**:
- **Priorità Alta**: Sposta un video in cima alla coda
- **Riprova**: Riprova l'elaborazione di un video fallito
- **Elimina**: Rimuovi un video dalla coda

### 5.6 Custom Post Type: IPV Videos

I video importati vengono salvati come **IPV Videos**, un Custom Post Type dedicato.

**Metadati Salvati**:
- Video ID YouTube
- URL video
- Thumbnail
- Durata
- Data pubblicazione YouTube
- Visualizzazioni
- Like/Dislike
- Trascrizione completa
- Descrizione AI generata
- Statistiche

**Tassonomie**:
- **Categorie Video** (`ipv_categoria`)
- **Relatori** (`ipv_relatore`)
- **Categorie Legacy** (compatibilità)

**Permalink**:
```
https://tuosito.it/ipv-video/titolo-video/
```

### 5.7 Shortcode Video Wall

Mostra una galleria di video nel frontend:

**Shortcode Base**:
```
[ipv_video_wall]
```

**Con Filtri**:
```
[ipv_video_wall show_filters="yes"]
```

**Parametri Disponibili**:
- `show_filters`: Mostra filtri categorie/relatori (`yes`/`no`)
- `posts_per_page`: Numero video per pagina (default: 12)
- `category`: ID categoria specifica
- `speaker`: ID relatore specifico

**Esempio Output**:
```
┌─────────┬─────────┬─────────┐
│ Video 1 │ Video 2 │ Video 3 │
│  [img]  │  [img]  │  [img]  │
│ Titolo  │ Titolo  │ Titolo  │
└─────────┴─────────┴─────────┘
```

---

## 6. Gestione Multilingua

Il plugin supporta il sistema di traduzione WordPress standard.

### 6.1 Lingua Base: Inglese

Il codice è scritto in **inglese**. Tutte le stringhe usano funzioni di traduzione:
```php
__( 'Settings', 'ipv-production-system-pro' )
```

### 6.2 Lingue Supportate

Il plugin v9.0.0 include **6 traduzioni complete**:

| Lingua | Codice | File |
|--------|--------|------|
| 🇮🇹 **Italiano** | it_IT | ipv-production-system-pro-it_IT.mo |
| 🇩🇪 **Tedesco** | de_DE | ipv-production-system-pro-de_DE.mo |
| 🇫🇷 **Francese** | fr_FR | ipv-production-system-pro-fr_FR.mo |
| 🇪🇸 **Spagnolo** | es_ES | ipv-production-system-pro-es_ES.mo |
| 🇵🇹 **Portoghese** | pt_PT | ipv-production-system-pro-pt_PT.mo |
| 🇷🇺 **Russo** | ru_RU | ipv-production-system-pro-ru_RU.mo |

Tutte le traduzioni sono già **compilate e pronte all'uso**!

### 6.3 Compilare le Traduzioni

**Metodo 1: Poedit** (Consigliato)

1. Scarica [Poedit](https://poedit.net/)
2. Apri `ipv-production-system-pro-it_IT.po`
3. **File** → **Compila in MO**
4. Salvato automaticamente!

**Metodo 2: Terminale**

```bash
cd wp-content/plugins/ipv-production-system-pro
msgfmt languages/ipv-production-system-pro-it_IT.po \
       -o languages/ipv-production-system-pro-it_IT.mo
```

**Metodo 3: Plugin Loco Translate**

1. Installa **Loco Translate**
2. Vai su **Loco Translate** → **Plugins**
3. Seleziona **IPV Production System Pro**
4. Compila automaticamente

### 6.4 Cambiare Lingua

**In WordPress**:
1. Vai su **Impostazioni** → **Generali**
2. Imposta **Lingua del sito**:
   - `Italiano` → Plugin in italiano 🇮🇹
   - `Deutsch` → Plugin in tedesco 🇩🇪
   - `Français` → Plugin in francese 🇫🇷
   - `Español` → Plugin in spagnolo 🇪🇸
   - `Português` → Plugin in portoghese 🇵🇹
   - `Русский` → Plugin in russo 🇷🇺
   - `English (United States)` → Plugin in inglese 🇬🇧

Il plugin caricherà **automaticamente** la traduzione corretta!

### 6.5 Aggiungere Nuove Lingue

**Esempio: Cinese**

```bash
# 1. Crea il file .po dal template
msginit -i languages/ipv-production-system-pro.pot \
        -o languages/ipv-production-system-pro-zh_CN.po \
        -l zh_CN

# 2. Traduci con Poedit o editor di testo

# 3. Compila in .mo usando lo script Python incluso
python3 tools/compile-translations.py
```

### 6.6 Stringhe Tradotte

Il plugin ha **~155 stringhe tradotte**:
- Menu admin (8)
- Dashboard (35)
- Settings (40)
- Import page (25)
- CPT labels (20)
- Taxonomy labels (27)

---

## 7. Guida all'Uso

### 7.1 Scenario 1: Prima Importazione

**Obiettivo**: Importare il primo video e verificare il funzionamento

**Passi**:

1. **Verifica API Keys**
   - Vai su **Settings**
   - Controlla che tutte le API siano configurate
   - Lo stato deve essere ✓ **OK**

2. **Importa un Video di Test**
   - Vai su **Import Video**
   - Usa un video breve (5-10 minuti) per test
   - Incolla l'URL: `https://www.youtube.com/watch?v=xxxxx`
   - Clicca **Importa e Pubblica**

3. **Monitora l'Elaborazione**
   - Vedrai le fasi di elaborazione in tempo reale
   - Attendi 2-3 minuti

4. **Verifica Risultato**
   - Vai su **IPV Videos** → **Tutti i Video**
   - Apri il video importato
   - Controlla:
     - ✓ Trascrizione presente
     - ✓ Descrizione AI generata
     - ✓ Metadati YouTube salvati

### 7.2 Scenario 2: Import Massivo da Canale

**Obiettivo**: Importare tutti i video di un canale YouTube

**Passi**:

1. **Ottieni URL Feed RSS**
   - Vai sul canale YouTube
   - Copia l'ID canale dall'URL
   - Costruisci l'URL feed:
     ```
     https://www.youtube.com/feeds/videos.xml?channel_id=UC...
     ```

2. **Configura Auto-Import**
   - Vai su **RSS Auto-Import**
   - Incolla l'URL feed
   - Seleziona frequenza: **Ogni ora**
   - Attiva l'auto-import

3. **Import Iniziale (Opzionale)**
   - Se vuoi importare subito i video esistenti
   - Usa **Bulk Import**
   - Incolla gli URL dei video (massimo 50 per volta)

4. **Monitora la Coda**
   - Vai su **Queue**
   - Vedrai tutti i video in elaborazione
   - Il CRON elabora 1 video ogni 5 minuti (default)

5. **Ottimizzazione CRON** (Opzionale)
   ```php
   // Nel file wp-config.php
   define('ALTERNATE_WP_CRON', true);

   // Aggiungi un vero CRON job:
   */5 * * * * curl https://tuosito.it/wp-cron.php
   ```

### 7.3 Scenario 3: Personalizzazione Descrizioni AI

**Obiettivo**: Modificare il prompt AI per ottenere descrizioni diverse

**Passi**:

1. **Analizza il Golden Prompt**
   - Leggi il file `includes/golden-prompt.php`
   - Studia la struttura delle istruzioni

2. **Crea Prompt Personalizzato**
   - Vai su **Settings** → **Custom AI Prompt**
   - Scrivi il tuo prompt seguendo questa struttura:
   ```
   Tu sei un esperto di [tuo settore].

   Analizza questa trascrizione e genera:
   1. [Cosa vuoi nell'output]
   2. [Altro requisito]
   3. [Altro requisito]

   Usa questo stile:
   - [Indicazione stilistica]
   - [Altra indicazione]

   TRASCRIZIONE:
   [Verrà inserita automaticamente]
   ```

3. **Testa il Nuovo Prompt**
   - Importa un video di prova
   - Verifica l'output generato
   - Raffina il prompt se necessario

4. **Salva e Applica**
   - Clicca **Salva Impostazioni**
   - Tutte le nuove importazioni useranno il tuo prompt

### 7.4 Scenario 4: Troubleshooting Importazione Fallita

**Problema**: Un video non viene elaborato correttamente

**Diagnosi**:

1. **Controlla i Log**
   ```php
   // Abilita debug in wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);

   // Leggi il log
   tail -f wp-content/debug.log
   ```

2. **Verifica API Status**
   - Vai su **Dashboard**
   - Controlla lo stato delle API
   - Se ✗ **Mancante**: configura le chiavi

3. **Controlla Quote API**
   - SupaData: Verifica il piano su supadata.ai
   - OpenAI: Controlla i limiti su platform.openai.com
   - YouTube: Verifica quota su console.cloud.google.com

4. **Riprova Manualmente**
   - Vai su **Queue**
   - Trova il video fallito
   - Clicca **Riprova**

**Soluzioni Comuni**:

| Errore | Causa | Soluzione |
|--------|-------|-----------|
| `API Key invalid` | Chiave errata | Verifica e ricopia la chiave |
| `Quota exceeded` | Limite API superato | Attendi reset quota o upgrade piano |
| `Video too long` | Video > 3 ore | Spezza il video o usa un altro |
| `No subtitles` | Video senza sottotitoli | Cambia modalità in "Generate" |
| `Timeout` | Video troppo lungo | Aumenta `max_execution_time` PHP |

---

## 8. Troubleshooting

### 8.1 CRON Non Funziona

**Sintomi**:
- Video restano in coda indefinitamente
- Auto-import RSS non parte
- Dashboard mostra CRON: **Fermo**

**Soluzioni**:

**1. Verifica CRON WordPress**
```bash
# Testa il CRON
wp cron test

# Vedi eventi schedulati
wp cron event list
```

**2. Usa Real CRON** (Consigliato per produzione)
```bash
# Disabilita WP-CRON in wp-config.php
define('DISABLE_WP_CRON', true);

# Aggiungi vero CRON job
crontab -e

# Aggiungi questa riga (ogni 5 minuti)
*/5 * * * * curl https://tuosito.it/wp-cron.php >/dev/null 2>&1
```

**3. Usa Plugin CRON**
- Installa **WP Control** o **Advanced Cron Manager**
- Verifica gli eventi del plugin

**4. Elaborazione Manuale**
- Usa il pulsante **Elabora Coda Adesso** nella Dashboard

### 8.2 Errori API

**Error: `API Key invalid`**

```
❌ Chiave API non valida o scaduta
```

**Soluzione**:
1. Verifica di aver copiato la chiave completa
2. Controlla spazi prima/dopo la chiave
3. Rigenera una nuova chiave sul sito del provider

**Error: `Rate limit exceeded`**

```
❌ Limite richieste API superato
```

**Soluzione**:
1. Attendi il reset della quota (di solito 1 ora)
2. Controlla il piano sottoscritto
3. Upgrade a un piano superiore se necessario

**Error: `Insufficient credits`**

```
❌ Crediti API esauriti
```

**Soluzione**:
1. Verifica il saldo su platform.openai.com
2. Aggiungi crediti o configura auto-ricarica
3. Per SupaData: upgrade il piano

### 8.3 Timeout PHP

**Sintomi**:
- Video non completano l'elaborazione
- Errore "Maximum execution time exceeded"

**Soluzioni**:

**1. Aumenta Timeout in WordPress**
```php
// In wp-config.php
set_time_limit(300); // 5 minuti
ini_set('max_execution_time', 300);
```

**2. Aumenta Timeout in php.ini**
```ini
max_execution_time = 300
max_input_time = 300
```

**3. Aumenta Timeout in .htaccess**
```apache
php_value max_execution_time 300
```

**4. Usa Elaborazione Background**
- Il plugin usa già la coda asincrona
- Aumenta solo il timeout per il CRON

### 8.4 Memoria PHP Esaurita

**Sintomi**:
- Errore "Allowed memory size exhausted"
- Import si blocca durante l'elaborazione

**Soluzioni**:

**1. Aumenta Memory Limit**
```php
// In wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**2. In php.ini**
```ini
memory_limit = 256M
```

**3. In .htaccess**
```apache
php_value memory_limit 256M
```

### 8.5 Video Non Appare nel Frontend

**Sintomi**:
- Video importato correttamente
- Non visibile nella galleria o shortcode

**Soluzioni**:

**1. Verifica Stato Pubblicazione**
- Vai su **IPV Videos**
- Controlla che lo stato sia **Pubblicato** (non Bozza)

**2. Flush Rewrite Rules**
```php
// Vai su Impostazioni → Permalink
// Clicca "Salva modifiche" (non cambiare nulla)
```

**3. Verifica Shortcode**
```
[ipv_video_wall]  ✓ Corretto
[ipv_video_wall   ✗ Parentesi mancante
```

**4. Controlla Template**
- Il tema deve supportare `single-ipv_video.php`
- Oppure usa il template fallback di WordPress

### 8.6 Caratteri Speciali Corrotti

**Sintomi**:
- Caratteri accentati mostrati come `�` o `&eacute;`
- Titoli corrotti

**Soluzioni**:

**1. Verifica Charset Database**
```sql
ALTER DATABASE nome_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Verifica Charset WordPress**
```php
// In wp-config.php
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
```

**3. Reimporta il Video**
- Elimina il video corrotto
- Reimporta da YouTube

---

## 9. FAQ

### Q1: Quanti video posso importare?

**A**: Non c'è un limite tecnico nel plugin, ma considera:
- **Limiti API**: SupaData e OpenAI hanno quote mensili
- **Spazio Disco**: Ogni video occupa ~5-10 MB (metadati + trascrizione)
- **Performance**: Con migliaia di video, ottimizza il database

**Consiglio**: Per cataloghi > 1000 video, considera:
- Piano Enterprise delle API
- Server dedicato o VPS
- CDN per i media

---

### Q2: Posso usare altri modelli AI?

**A**: Il plugin è configurato per OpenAI, ma puoi modificarlo:

```php
// In includes/class-ai-generator.php
// Cambia il modello
'model' => 'gpt-4-turbo', // Predefinito
'model' => 'gpt-3.5-turbo', // Più economico
'model' => 'gpt-4', // Più accurato
```

Per usare altri provider (Claude, Gemini), serve modificare il codice.

---

### Q3: Come faccio backup dei video?

**A**: Due approcci:

**1. Backup Database** (Solo metadati)
```bash
# Esporta tabelle WordPress
mysqldump -u user -p database wp_posts wp_postmeta > backup.sql
```

**2. Backup Completo**
- Usa plugin come **UpdraftPlus** o **BackWPup**
- Include database + `/wp-content/uploads/`

**3. Export CSV** (Custom)
```php
// Puoi creare una funzione export personalizzata
$videos = get_posts(['post_type' => 'ipv_video', 'posts_per_page' => -1]);
// Esporta in CSV
```

---

### Q4: Il plugin è compatibile con multisite?

**A**: Sì, ma con limitazioni:

**Network Activation**: Non supportata (ancora)
**Single Site**: Attiva il plugin su ogni singolo sito

**Workaround**:
```php
// Per condividere API keys tra siti
// Usa costanti in wp-config.php
define('IPV_SUPADATA_KEY', 'sk-xxx');
define('IPV_OPENAI_KEY', 'sk-xxx');
```

---

### Q5: Posso personalizzare il template video?

**A**: Sì! Crea un template nel tuo tema:

```php
// Nel tema: single-ipv_video.php
<?php get_header(); ?>

<article <?php post_class(); ?>>
    <h1><?php the_title(); ?></h1>

    <!-- Player YouTube Embed -->
    <?php
    $video_id = get_post_meta(get_the_ID(), 'ipv_youtube_id', true);
    ?>
    <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>"
            width="100%" height="500"></iframe>

    <!-- Descrizione AI -->
    <?php the_content(); ?>

    <!-- Trascrizione -->
    <details>
        <summary>Trascrizione Completa</summary>
        <?php echo get_post_meta(get_the_ID(), 'ipv_transcript', true); ?>
    </details>
</article>

<?php get_footer(); ?>
```

---

### Q6: Come cambio lo slug URL dei video?

**A**: Modifica il Custom Post Type:

```php
// In includes/class-cpt.php, riga ~70
'rewrite' => [
    'slug' => 'video',  // Cambia da 'ipv-video' a 'video'
    'with_front' => false
],
```

Dopo la modifica:
1. Salva il file
2. Vai su **Impostazioni** → **Permalink**
3. Clicca **Salva modifiche**

Nuovo URL: `https://tuosito.it/video/titolo/`

---

### Q7: Posso nascondere alcuni video dalla galleria?

**A**: Sì, usa le categorie:

```
[ipv_video_wall category="5"]
```

Oppure usa un custom field:
```php
// Nascondi video
update_post_meta($post_id, 'ipv_hide_from_gallery', '1');

// Modifica lo shortcode per escluderli
$args['meta_query'] = [
    [
        'key' => 'ipv_hide_from_gallery',
        'compare' => 'NOT EXISTS'
    ]
];
```

---

### Q8: Il plugin rispetta il GDPR?

**A**: Il plugin in sé non raccoglie dati personali, MA:

**⚠️ Considera**:
- Le trascrizioni video potrebbero contenere dati personali
- OpenAI processa i testi (leggi [OpenAI Privacy](https://openai.com/privacy))
- SupaData processa gli audio

**Raccomandazioni**:
1. Aggiungi informativa privacy
2. Ottieni consenso per video con dati personali
3. Configura data retention su OpenAI
4. Usa agreement Business Associate (BAA) se necessario

---

### Q9: Posso vendere accesso ai video?

**A**: Sì! Integrazioni possibili:

**1. WooCommerce + Membership**
```php
// Proteggi i video
if (!wc_memberships_is_user_active_member($user_id, 'premium')) {
    // Mostra paywall
}
```

**2. Restrict Content Pro**
```php
// Shortcode condizionale
[rcp_access level="premium"]
    [ipv_video_wall]
[/rcp_access]
```

**3. MemberPress, Paid Memberships Pro, etc.**
- Tutti compatibili con protezione contenuti

---

### Q10: Come ottimizzare le performance?

**A**: Consigli per siti con molti video:

**1. Caching**
```php
// Usa plugin cache
// W3 Total Cache, WP Rocket, LiteSpeed Cache
```

**2. Lazy Loading**
```php
// Per embed YouTube
<iframe loading="lazy" src="..."></iframe>
```

**3. CDN**
- CloudFlare
- BunnyCDN
- StackPath

**4. Database**
```sql
-- Indici sulle meta_key usate
ALTER TABLE wp_postmeta ADD INDEX idx_youtube_id (meta_key(50), meta_value(50));
```

**5. Limit Query**
```php
// Nello shortcode
[ipv_video_wall posts_per_page="12"]
```

---

## 10. Supporto

### Documentazione

- **Repository GitHub**: https://github.com/daniemi1977/ipv
- **Wiki**: https://github.com/daniemi1977/ipv/wiki
- **Changelog**: Vedi `CHANGELOG.md`

### Bug Report

Per segnalare bug:
1. Vai su https://github.com/daniemi1977/ipv/issues
2. Clicca **New Issue**
3. Usa il template "Bug Report"
4. Includi:
   - Versione plugin
   - Versione WordPress
   - Versione PHP
   - Log di errore (se disponibile)
   - Passaggi per riprodurre

### Feature Request

Per richiedere nuove funzionalità:
1. Vai su https://github.com/daniemi1977/ipv/issues
2. Usa il template "Feature Request"
3. Descrivi dettagliatamente la funzionalità

### Supporto Commerciale

Per supporto personalizzato o sviluppo custom:
- Email: support@ipv-production.com
- Piano Enterprise disponibile su richiesta

---

## Appendice A: Riferimento API

### SupaData API

**Endpoint**: `https://api.supadata.ai/v1/transcribe`

**Metodo**: POST

**Parametri**:
```json
{
    "url": "https://www.youtube.com/watch?v=xxxxx",
    "mode": "auto",
    "language": "it"
}
```

**Risposta**:
```json
{
    "transcription": "Testo trascritto...",
    "duration": 1234,
    "language": "it",
    "confidence": 0.95
}
```

**Rate Limits**:
- Free: 10 richieste/giorno
- Pro: 1000 richieste/mese
- Enterprise: Illimitate

---

### OpenAI API

**Endpoint**: `https://api.openai.com/v1/chat/completions`

**Metodo**: POST

**Parametri**:
```json
{
    "model": "gpt-4-turbo",
    "messages": [
        {"role": "system", "content": "GOLDEN_PROMPT"},
        {"role": "user", "content": "TRASCRIZIONE"}
    ],
    "temperature": 0.7,
    "max_tokens": 4000
}
```

**Costi Stimati** (gpt-4-turbo):
- Input: $0.01 / 1K tokens
- Output: $0.03 / 1K tokens
- **Costo medio per video**: $0.15 - $0.50

**Rate Limits**:
- Tier 1: 3 RPM, 200 RPD
- Tier 2: 60 RPM, 10,000 RPD
- Tier 5: 10,000 RPM, 10,000,000 RPD

---

### YouTube Data API v3

**Endpoint**: `https://www.googleapis.com/youtube/v3/videos`

**Metodo**: GET

**Parametri**:
```
?part=snippet,contentDetails,statistics
&id=VIDEO_ID
&key=YOUR_API_KEY
```

**Quota**:
- 10,000 unità/giorno (gratis)
- 1 request = 3 unità
- ~3,333 richieste/giorno

---

## Appendice B: Shortcode Parametri Completi

### [ipv_video_wall]

```
[ipv_video_wall
    posts_per_page="12"          # Numero video per pagina
    show_filters="yes"           # Mostra filtri (yes/no)
    category="5"                 # ID categoria (singola)
    speaker="8"                  # ID relatore (singolo)
    orderby="date"               # Ordinamento (date/title/rand)
    order="DESC"                 # Ordine (DESC/ASC)
    columns="3"                  # Colonne griglia (2/3/4)
    show_excerpt="yes"           # Mostra excerpt (yes/no)
    excerpt_length="150"         # Lunghezza excerpt (caratteri)
    show_meta="yes"              # Mostra metadati (yes/no)
    show_categories="yes"        # Mostra badge categorie
    show_duration="yes"          # Mostra durata video
    lightbox="yes"               # Apri in lightbox (yes/no)
    class="custom-class"         # Classe CSS personalizzata
]
```

### Esempi Pratici

**Galleria Base**:
```
[ipv_video_wall]
```

**Solo Video Categoria "Trading"**:
```
[ipv_video_wall category="5" posts_per_page="20"]
```

**Video Random Sidebar**:
```
[ipv_video_wall posts_per_page="4" orderby="rand" columns="1" show_excerpt="no"]
```

**Archivio Relatore**:
```
[ipv_video_wall speaker="8" orderby="date" show_filters="no"]
```

---

## Appendice C: Hook e Filtri

Per sviluppatori che vogliono estendere il plugin.

### Action Hooks

```php
// Prima dell'importazione
do_action('ipv_before_import', $youtube_url);

// Dopo l'importazione (prima elaborazione)
do_action('ipv_after_import', $post_id, $video_data);

// Dopo generazione trascrizione
do_action('ipv_after_transcription', $post_id, $transcription);

// Dopo generazione descrizione AI
do_action('ipv_after_ai_generation', $post_id, $description);

// Dopo pubblicazione completa
do_action('ipv_after_publish', $post_id);

// Prima eliminazione video
do_action('ipv_before_delete', $post_id);
```

### Filter Hooks

```php
// Modifica dati video prima del salvataggio
apply_filters('ipv_video_data', $data, $youtube_url);

// Modifica trascrizione generata
apply_filters('ipv_transcription', $text, $post_id);

// Modifica prompt AI
apply_filters('ipv_ai_prompt', $prompt, $post_id);

// Modifica descrizione AI generata
apply_filters('ipv_ai_description', $description, $post_id);

// Modifica query shortcode
apply_filters('ipv_shortcode_query', $args);

// Modifica template video wall
apply_filters('ipv_wall_template', $template, $video_id);
```

### Esempi Uso

**Aggiungi campo custom dopo import**:
```php
add_action('ipv_after_import', function($post_id, $video_data) {
    // Estrai keyword dal titolo
    $keywords = extract_keywords(get_the_title($post_id));
    update_post_meta($post_id, 'ipv_keywords', $keywords);
}, 10, 2);
```

**Modifica prompt AI dinamicamente**:
```php
add_filter('ipv_ai_prompt', function($prompt, $post_id) {
    $category = get_the_category($post_id);

    if ($category[0]->slug === 'trading') {
        return "Analizza questo video di trading e...";
    }

    return $prompt;
}, 10, 2);
```

**Invia notifica dopo pubblicazione**:
```php
add_action('ipv_after_publish', function($post_id) {
    $title = get_the_title($post_id);
    $url = get_permalink($post_id);

    // Invia email
    wp_mail(
        'admin@tuosito.it',
        "Nuovo video pubblicato: $title",
        "Vedi qui: $url"
    );
});
```

---

## Appendice D: Comandi WP-CLI

Il plugin supporta WP-CLI per operazioni da terminale.

### Installazione WP-CLI

```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

### Comandi Disponibili

**Import singolo**:
```bash
wp ipv import "https://www.youtube.com/watch?v=xxxxx"
```

**Import multiplo**:
```bash
wp ipv import \
    "https://www.youtube.com/watch?v=xxxxx1" \
    "https://www.youtube.com/watch?v=xxxxx2" \
    "https://www.youtube.com/watch?v=xxxxx3"
```

**Processa coda**:
```bash
wp ipv process-queue
```

**Statistiche**:
```bash
wp ipv stats

# Output:
# Total videos: 156
# Published: 142
# In queue: 14
# Failed: 0
```

**Rigenera descrizione AI**:
```bash
wp ipv regenerate --post_id=123
```

**Reset coda**:
```bash
wp ipv reset-queue
```

**Export CSV**:
```bash
wp ipv export --file=videos.csv
```

---

## Conclusione

Questo manuale copre tutte le funzionalità principali del plugin **IPV Production System Pro**.

Per ulteriori informazioni:
- Repository: https://github.com/daniemi1977/ipv
- Issues: https://github.com/daniemi1977/ipv/issues
- Wiki: https://github.com/daniemi1977/ipv/wiki

**Buon lavoro con IPV Production System Pro!** 🚀

---

*Documento generato automaticamente per IPV Production System Pro v9.0.0*
*Copyright © 2024 IPV Team. Tutti i diritti riservati.*
