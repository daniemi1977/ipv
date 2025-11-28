
# IPV Production System Pro – Changelog

## v7.9.9 - 2025-11-28
### 🔍 Tool Controllo Duplicati + Fix Video Embed Tagliato

### 🎯 Nuove Funzionalità
- **NEW**: Pannello "Controllo Duplicati" in admin
- **NEW**: Check post duplicati per titolo
- **NEW**: Check post duplicati per Video ID YouTube
- **NEW**: Check media duplicati per nome file
- **NEW**: Comparazione visiva media con anteprime
- **NEW**: Info dimensioni file per ogni media

### 🐛 Bug Risolti
- **FIX**: Video embed tagliato/troncato sul lato destro/inferiore
- **CAUSA**: Overflow del tema nascondeva parte del video
- **FIX**: Aggiunto `overflow: visible !important` ai contenitori

### 📝 File Nuovi/Modificati
- **NEW**: `includes/class-duplicate-checker.php`
  - Pannello admin "🔍 Duplicati" sotto "Video IPV"
  - Query SQL ottimizzate per trovare duplicati
  - Anteprime visuali per media
  - Link diretti per modificare/eliminare

- `includes/class-video-frontend.php`:
  - CSS: `overflow: visible !important` per container video
  - CSS: Fix contenitori tema (entry-content, post-content, article, hentry)
  - `min-height: 0 !important` per prevenire altezza minima del tema

- `ipv-production-system-pro.php`:
  - Aggiunto require per class-duplicate-checker.php

### ✅ Risultato
- ✅ Video embed completo (non più tagliato)
- ✅ Tool duplicati accessibile da "Video IPV → 🔍 Duplicati"
- ✅ Identificazione rapida post duplicati
- ✅ Identificazione rapida media duplicati
- ✅ Comparazione visiva immagini

---

## v7.9.8 - 2025-11-28
### 🔧 Fix: Featured Image nei Related Posts + CSS .bt-post-tags

### 🎯 Problemi Risolti
- **BUG**: Featured image non visibili nei related posts (thumbs grigi)
- **BUG**: Tag `.bt-post-tags` del tema Influencer visibili nei post ipv_video
- **CAUSA**: Filtro `remove_featured_image` troppo aggressivo (rimuoveva TUTTE le featured image)
- **CAUSA**: CSS non includeva `.bt-post-tags` del tema

### 💡 Soluzione Implementata
- **FIX**: Filtro `remove_featured_image` ora verifica `is_main_query()` - rimuove solo nel post principale
- **FIX**: CSS selettori più specifici per targettare solo featured image del post principale
- **NEW**: Aggiunto `.bt-post-tags` al CSS per nascondere tag del tema Influencer
- **IMPROVED**: Selettori CSS più mirati (`article.ipv_video`, `.hentry`, `.entry-header`)

### 📝 File Modificati
- `includes/class-video-frontend.php`:
  - `remove_featured_image()`: aggiunta verifica `is_main_query()`
  - CSS: selettori più specifici per featured image
  - CSS: aggiunto `.bt-post-tags` nella lista nascosta

### ✅ Risultato
- ✅ Featured image visibili nei related posts
- ✅ Featured image nascosta solo nel post principale ipv_video
- ✅ `.bt-post-tags` nascosto nei post ipv_video
- ✅ Related posts con thumbs corretti (non più grigi)

---

## v7.9.7 - 2025-11-28
### 📺 Video Embed Centrato - Larghezza Area Contenuto

### 🎯 Correzione Richiesta
- **FEEDBACK v7.9.6**: Video viewport breakout troppo largo, esce dall'area contenuto
- **RICHIESTA**: Video centrato che occupi tutta la larghezza dell'area contenuto CPT
- **OBIETTIVO**: Stesso comportamento desktop/mobile, nessun bordo nero, centrato

### 💡 Soluzione Implementata
- **REVERTED**: Rimosso viewport breakout (100vw con margini negativi)
- **NEW**: `width: 100%` (larghezza area contenuto, non viewport)
- **NEW**: `margin: 0 auto` (centrato nell'area contenuto)
- **REMOVED**: `left: 50%`, `margin-left: -50vw` (no breakout)
- **MAINTAINED**: Aspect ratio 16:9 perfetto, nessun border-radius

### 📝 File Modificati
- `includes/class-video-frontend.php`:
  - Container: `width: 100% !important` (non 100vw)
  - Container: `margin: 0 auto 40px auto !important`
  - Rimossi left, right, margini negativi viewport

### ✅ Risultato
- ✅ Video centrato nell'area contenuto CPT
- ✅ Larghezza 100% dell'area contenuto (non viewport)
- ✅ Nessun bordo nero sopra/lati
- ✅ Aspect ratio 16:9 perfetto
- ✅ Stesso comportamento desktop e mobile

---

## v7.9.6 - 2025-11-28 [SUPERSEDED by v7.9.7]
### 📺 Video Embed Full Width Desktop (REVERTED)

---

## v7.9.5 - 2025-11-28
### 🎨 Frontend CPT: Rimozione Featured Image e Tag Cliccabili

### 🎯 Modifiche Richieste
- **RICHIESTA**: Rimuovere featured image dal single post ipv_video
- **RICHIESTA**: Rimuovere tag e categorie cliccabili (desktop e mobile)
- **RICHIESTA**: Mostrare solo video embed YouTube + contenuto

### 💡 Soluzione Implementata
- **NEW**: Filtro `post_thumbnail_html` per rimuovere featured image su ipv_video
- **NEW**: CSS in `wp_head` per nascondere:
  - Featured images (post-thumbnail, entry-thumbnail, ecc.)
  - Tag e categorie (entry-meta, post-categories, post-tags)
  - Tassonomie custom (ipv_categoria, ipv_relatore)
- **NEW**: Media query mobile per stesse regole

### 📝 File Modificati
- `includes/class-video-frontend.php`:
  - `remove_featured_image()` - Rimuove immagine in evidenza
  - `hide_tags_and_meta()` - CSS per nascondere metadati
  - Hook `post_thumbnail_html` e `wp_head`

### ✅ Risultato
- ✅ Nessuna featured image visibile su single ipv_video
- ✅ Nessun tag o categoria cliccabile (desktop)
- ✅ Nessun tag o categoria cliccabile (mobile)
- ✅ Solo video embed YouTube + contenuto AI

---

## v7.9.4 - 2025-11-28
### 🚨 CRITICAL FIX: Filtri AJAX e Paginazione Video Wall

### 🎯 Problema Risolto
- **BUG CRITICO**: Filtri non caricavano nessun video
- **BUG CRITICO**: Paginazione "Carica altri video" mostrava "Nessun video trovato"
- **CAUSA**: Post type errato `'video_ipv'` nella funzione AJAX (linea 346)

### 💡 Soluzione
- **FIX**: Corretto `'video_ipv'` → `'ipv_video'` in `ajax_load_videos()`
- **NOTA**: Stesso bug già risolto in v7.9.0 per lo shortcode, ma dimenticato nella funzione AJAX

### 📝 File Modificati
- `includes/class-video-wall.php` (linea 346) - Post type AJAX corretto

### ✅ Risultato
- ✅ Filtri per categoria funzionanti
- ✅ Filtri per relatore funzionanti
- ✅ Ricerca testuale funzionante
- ✅ Paginazione "Carica altri X video" funzionante
- ✅ Tutti i video vengono correttamente recuperati

---

## v7.9.3 - 2025-11-28
### 🔧 Fix Layout 2+3 Video Wall

### 🎯 Problema Risolto
- **FIX**: Layout "2+3" non veniva applicato correttamente
- **CAUSA**: Parametro `layout` non utilizzato per applicare classe CSS
- **CAUSA**: Regole CSS `ipv-columns-3` con `!important` sovrascrivevano layout 2+3

### 💡 Soluzione Implementata
- **NEW**: Classe `ipv-layout-2-3` applicata al grid container
- **NEW**: CSS con specificità aumentata: `.ipv-video-grid.ipv-layout-2-3`
- **NEW**: `!important` sulle regole layout 2+3 per priorità corretta
- **FIX**: Media queries responsive aggiornate per layout 2+3

### 📝 File Modificati
- `includes/class-video-wall.php` (linea 81) - Aggiunta classe layout
- `assets/css/video-wall.css` (linee 85-96, 401-434) - CSS layout specifico

### ✅ Risultato
- **Desktop**: 2 video al 50% (prima riga) + 3 video al 33% (seconda riga)
- **Tablet**: 2 colonne uniformi
- **Mobile**: 1 colonna al 100%

---

## v7.9.2 - 2025-11-28
### 🤖 Golden Prompt v4.4 - Anti-Procrastinazione Saluti Finali

### 🎯 Fix Timestamp "Saluti Finali"
- **FIX**: Risolto problema timestamp finali spacchettati in più voci
- **PRIMA**: `1:53:30 — Ringraziamenti / 1:59:00 — Chiusura / 2:03:40 — Fine`
- **DOPO**: `1:53:30 — Saluti finali` (timestamp unico)
- **NEW**: Istruzioni esplicite per NON dividere ringraziamenti/chiusura/fine
- **NEW**: Regola anti-procrastinazione: quando arrivi a saluti, FERMATI

### 📝 File Modificati
- `includes/class-ai-generator.php` - Golden Prompt v4.4 (linee 679-680)
- Aggiunta istruzione: "Quando arrivi a 'Saluti finali', FERMATI LÌ"
- Aggiunta istruzione: "L'ultimo timestamp deve essere UNICO"

---

## v7.9.1 - 2025-11-28
### 🎛️ Pannello Admin Completo per Video Wall

### 🆕 NUOVO: Pannello di Controllo Video Wall
- **NEW**: Classe `IPV_Prod_Video_Wall_Admin` per gestione completa
- **NEW**: Menu admin dedicato in "Video IPV → Video Wall"
- **NEW**: Interfaccia grafica completa con sidebar informativa
- **NEW**: Salvataggio opzioni nel database WordPress

### 🎨 Impostazioni Layout e Struttura
- **Layout Griglia**: 2+3, Standard, Masonry, Lista
- **Video per Pagina**: 1-50 video configurabili
- **Numero Colonne**: 2, 3, 4, 5 colonne
- **Filtri**: On/Off per categorie e relatori
- **Ricerca**: On/Off per campo ricerca

### 👁️ Elementi Visibili Configurabili
- ✅/❌ Data pubblicazione
- ✅/❌ Categoria
- ✅/❌ Relatore/Speaker
- ✅/❌ Numero visualizzazioni
- ✅/❌ Durata video
- **Estratto**: Lunghezza caratteri estratto (0 = disabilitato)

### 🎨 Personalizzazione Colori (Color Picker)
- **Accent Color**: Badge data, link hover, pulsanti
- **Background Card**: Sfondo card video
- **Background Meta**: Sfondo barra info
- **Colore Testo**: Colore testo principale
- Preview live dei colori nel pannello

### 🔃 Ordinamento Video
- **Ordina per**: Data, Titolo, Modificato, Casuale, Visualizzazioni
- **Ordine**: Crescente/Decrescente

### ✨ Animazioni Configurabili
- **Hover Effect**: Lift (solleva), Zoom (ingrandisce), Nessuno
- **Load Animation**: Fade In, Slide Up, Nessuna

### 📋 Shortcode Generator
- **Generator automatico** con parametri dinamici
- **Copia shortcode** con 1 click
- Shortcode base: `[ipv_video_wall]`
- Shortcode personalizzato con parametri da panel

### 📊 Sidebar Informativa
- **Statistiche**: Video totali, Categorie, Relatori
- **Preview colori live**: Vedi modifiche in tempo reale
- **Guida rapida**: Help contestuale

### 🎛️ Funzioni Pannello
- **Salva Impostazioni**: Persist nel database
- **Ripristina Predefiniti**: Reset alle impostazioni default
- **Color Picker**: WordPress native color picker
- **Visual Feedback**: Animazioni su save/copy

### 🔧 Integrazione Tecnica
- Video Wall ora legge tutte le opzioni dal database
- CSS dinamico iniettato con `wp_add_inline_style`
- CSS Variables aggiornate dinamicamente
- Backward compatibility con shortcode parametri

### 📦 File Nuovi/Modificati
- **NEW**: `includes/class-video-wall-admin.php` - Admin panel class
- **NEW**: `assets/css/video-wall-admin.css` - Admin panel styles
- **NEW**: `assets/js/video-wall-admin.js` - Admin panel interactions
- **MODIFIED**: `includes/class-video-wall.php` - Dynamic CSS colors
- **MODIFIED**: `ipv-production-system-pro.php` - Init admin class

### 🎯 Come Accedere
WordPress Admin → **Video IPV** → **Video Wall**

---

## v7.9.0 - 2025-11-28
### 🎨 Video Wall Redesign - Influencer Theme Style

### 🐛 BUG FIX CRITICO
- **FIXED**: Bug `post_type` nel Video Wall
  - Era: `'post_type' => 'video_ipv'` (SBAGLIATO)
  - Ora: `'post_type' => 'ipv_video'` (CORRETTO)
  - Fix applicato in 2 punti: `render_videos()` e `render_pagination()`
  - Questo bug impediva al wall di mostrare qualsiasi video

### 🎨 Design Overhaul - Influencer Theme Integration
- **NEW**: Applicato stile "Style 1" del tema Influencer
- **NEW**: Background card #F5F5F5 con border-radius 15px
- **NEW**: Badge data floating sopra l'immagine (position absolute)
  - Background accent color #FB0F5A
  - Shadow effect con hover
  - Centrato con transform translateX(-50%)
- **NEW**: Text alignment centrato
- **NEW**: Meta bar con background #EAEAEA in basso
- **NEW**: Hover effects eleganti con lift animation

### 🎨 Struttura HTML Aggiornata
- **UPGRADED**: `render_video_card()` completamente riscritta
- **NEW**: Struttura `.ipv-post--inner` → `.ipv-post--featured` → `.ipv-post--infor`
- **NEW**: `.ipv-cover-image` con background-image invece di `<img>`
- **NEW**: `.ipv-post--publish` badge floating con SVG calendar icon
- **NEW**: `.ipv-post--title` con line-clamp per 2 righe max
- **NEW**: `.ipv-post--meta` con categorie, speaker, views

### 🎨 CSS Variables
```css
--ipv-accent-color: #FB0F5A
--ipv-text-color: #555
--ipv-card-bg: #F5F5F5
--ipv-meta-bg: #EAEAEA
```

### 📱 Responsive Design Migliorato
- **Desktop**: Layout 2+3 mantenuto (2 video 50%, 3 video 33%)
- **Tablet** (< 1024px): 2 colonne
- **Mobile** (< 768px): 1 colonna
- **Small Mobile** (< 519px): Badge data più piccolo, font ridotti

### 🆕 Nuove Informazioni Card
- **NEW**: Categoria principale mostrata
- **NEW**: Speaker/Relatore principale mostrato
- **NEW**: Numero visualizzazioni mostrato
- **NEW**: Data formattata (es: "28 Nov 2025")
- Tutti con separatore "-" automatico

### 🎯 Shortcode Usage
```
[ipv_video_wall per_page="5" layout="grid" columns="3" show_filters="yes"]
```

### 📦 File Modificati
- **MODIFIED**: `includes/class-video-wall.php` - Fix bug + HTML structure
- **MODIFIED**: `assets/css/video-wall.css` - Complete redesign Influencer style

---

## v7.8.0 - 2025-11-28
### 🎯 Sistema Timestamp Intelligente + Capitoli Nativi YouTube

### 🆕 Recupero Capitoli Nativi YouTube
- **NEW**: Classe `IPV_Prod_YouTube_Chapters` per recuperare capitoli esistenti
- **NEW**: API third-party (yt.lemnoslife.com) per capitoli YouTube nativi
- **WORKFLOW**: Prima prova capitoli nativi → Fallback su generazione AI
- **BENEFIT**: Timestamp accurati al 100% quando disponibili, zero troncamento
- **BENEFIT**: Copertura completa della durata video garantita
- **BENEFIT**: Riduzione costi OpenAI per video con capitoli esistenti

### 🔍 Post-Processing Intelligente Timestamp
- **NEW**: Metodo `verify_timestamp_coverage()` per verificare copertura
- **NEW**: Metodo `continue_timestamps()` per retry automatico
- **LOGICA**: Verifica se ultimo timestamp copre almeno 75% durata
- **RETRY**: Se copertura insufficiente → Chiamata AI di continuazione
- **RETRY**: Secondo prompt specifico per timestamp mancanti
- **RETRY**: Merge automatico timestamp aggiuntivi nella descrizione

### ⚙️ Ottimizzazioni AI Generator
- **UPGRADED**: Golden Prompt v4.3
- **UPGRADED**: Limite trascrizione aumentato: 14k → 30k caratteri (per video lunghi)
- **UPGRADED**: Supporto capitoli nativi in `generate_description()`
- **UPGRADED**: Istruzioni timestamp dinamiche (nativi vs generati)
- **NEW**: Log dettagliato recupero capitoli e verifica copertura

### 🎬 Workflow Completo
```
Video importato
    ↓
Tentativo recupero capitoli nativi YouTube
    ↓
SE capitoli esistono:
    → AI usa capitoli nativi (copertura 100%)
    ✅ FINE
    ↓
SE NON esistono:
    → AI genera timestamp da trascrizione (30k caratteri)
    ↓
    Verifica copertura timestamp generati
        ↓
        SE copertura < 75%:
            → Retry con continuazione automatica
            → Merge timestamp aggiuntivi
            ✅ FINE
        ↓
        SE copertura >= 75%:
            ✅ FINE
```

### 🐛 Fix Problema Timestamp Troncati
- **FIXED**: Video di 2 ore con timestamp solo fino a 45 minuti
- **ROOT CAUSE**: Trascrizione troncata a 14k caratteri + AI non leggeva fino alla fine
- **SOLUTION 1**: Capitoli nativi YouTube (quando disponibili)
- **SOLUTION 2**: Limite trascrizione aumentato (14k → 30k)
- **SOLUTION 3**: Verifica automatica + retry con continuazione
- **RESULT**: Timestamp ora coprono TUTTA la durata del video

### 📦 File Modificati/Aggiunti
- **NEW**: `includes/class-youtube-chapters.php` - Recupero capitoli nativi
- **MODIFIED**: `includes/class-ai-generator.php` - Post-processing e retry
- **MODIFIED**: `ipv-production-system-pro.php` - Require nuova classe

---

## v7.7.0 - 2025-11-28
### 🚀 Sistema Editoriale Avanzato + Premiere Videos

### 🤖 Golden Prompt v4.0
- **UPGRADED**: Timestamp generation system con verifiche multiple
  - 🔴 Warning prominenti per durata video
  - Calcolo timestamp finale suggerito (2-3 min prima della fine)
  - Minimo 15-20 timestamp per video > 60 minuti
  - Distribuzione uniforme lungo TUTTA la trascrizione
  - Verifica finale automatica
- **UPGRADED**: Introduzione SEO espansa (150-200 parole vs 80-120)
  - Struttura in 3 paragrafi ottimizzati
  - Keywords strategiche per SEO YouTube
  - Termini specifici e rilevanti
- **UPGRADED**: Argomenti trattati (8-12 vs 6-8)
  - Nomi concisi ma descrittivi (max 3-4 parole)
  - Esempi specifici per categorie WordPress
  - Mix tra argomenti generali e specifici
- **UPGRADED**: Sistema categorie da logica titolo+descrizione
  - Estrazione intelligente delle categorie
  - Categorie chiare come "Energia libera", "Disclosure UFO", "Tartaria"
- **UPGRADED**: Relatore/Anno come tassonomie CPT
  - Fallback automatico a "Il Punto di Vista"
  - Anno estratto da YouTube Data v3

### 📺 Gestione Video in Premiere/Programmazione
- **NEW**: Rilevamento automatico video con durata 00:00:00
- **NEW**: Sospensione processo editoriale per video non ancora disponibili
- **NEW**: Meta flag `_ipv_premiere_pending` per tracciamento
- **NEW**: Riattivazione automatica quando video diventa disponibile
- Video in premiere:
  - CPT creato immediatamente con metadata YouTube
  - Thumbnail scaricata e impostata
  - Processo editoriale (trascrizione + AI) SOSPESO
  - Marcato con status `waiting_premiere`

### ⏰ CRON Hourly - Aggiornamento Dati YouTube
- **NEW**: CRON automatico ogni ora per TUTTI i video
- **NEW**: Aggiornamento metadata: durata, views, data pubblicazione
- **NEW**: Rilevamento video premiere ora disponibili
- **NEW**: Re-queuing automatico per processo editoriale
- Funzionalità:
  - Aggiorna `_ipv_yt_duration_seconds`
  - Aggiorna `_ipv_yt_view_count`
  - Aggiorna `_ipv_yt_published_at`
  - Rileva cambio durata 00:00:00 → > 0
  - Rimette in coda con source `premiere_ready`

### 🔄 Flusso Automatico Completo
**Scenario 1: Video Normale (durata > 0)**
1. RSS Feed → Nuovo video rilevato
2. Aggiunto alla coda
3. CRON (5 min) → Crea CPT → Scarica dati YouTube
4. ✅ Durata > 0 → Avvia processo editoriale
5. Trascrizione (SupaData) → AI (Golden Prompt v4.0) → Pubblicazione

**Scenario 2: Video in Premiere (durata = 00:00:00)**
1. RSS Feed → Video premiere rilevato
2. Aggiunto alla coda
3. CRON (5 min) → Crea CPT → Scarica dati YouTube
4. ⏸️ Durata = 0 → Processo editoriale SOSPESO
5. Meta `_ipv_premiere_pending` = yes
6. CRON hourly → Controlla e aggiorna dati
7. ✅ Durata > 0 rilevata → Rimesso in coda
8. Trascrizione → AI → Pubblicazione

### 🗄️ Nuovi Meta Fields
- `_ipv_premiere_pending`: Video in attesa di premiere
- `_ipv_queue_status`: waiting_premiere | processing | done

### 📊 Logging Migliorato
- Log specifici per video in premiere
- Log aggiornamento YouTube data hourly
- Log re-queuing video premiere disponibili
- Statistiche complete aggiornamento (total, updated, errors)

### 🔧 Technical Details
- CRON hook: `ipv_prod_update_youtube_data`
- Metodi: `IPV_Prod_Queue::update_all_youtube_data()`
- Metodi: `IPV_Prod_Queue::check_premiere_videos()`
- Schedule: `hourly` (WordPress built-in)
- Auto-schedule on plugin activation
- Auto-unschedule on plugin deactivation

## v7.6.0 - 2025-11-28
### 🎉 MAJOR UPDATE - Complete Integration
Merge of v7.5.8 production features with v6.4.0 Video Wall into a unified, production-ready release.

### 🎬 Video Wall (from v6.4.0)
- **NEW**: Video Wall shortcode `[ipv_video_wall]` with advanced filtering
- **NEW**: 2+3 layout (2 videos top row, 3 videos bottom row)
- **NEW**: AJAX-powered filtering by categoria and relatore
- **NEW**: Search functionality within video wall
- **NEW**: Load More button with progressive loading
- **NEW**: YouTube Shorts filtering - automatically excludes videos < 60 seconds
- **NEW**: Admin settings page for Video Wall configuration (IPV Production > Video Wall)
- **NEW**: Multi-tier thumbnail fallback system (YouTube maxres → WordPress featured → hqdefault → placeholder)
- Default 5 videos per page (configurable)
- Modern UI with hover effects and smooth animations
- Mobile-responsive design

### ⚡ Production Features (from v7.5.8)
- **NEW**: Simple Import - Import and publish videos immediately without waiting for transcription/AI
- **NEW**: Speaker Rules - Automatic speaker assignment based on title patterns
- **NEW**: Video Frontend - Automatic YouTube embed in single video pages
- **NEW**: Bulk Tools - Mass regeneration of taxonomies, descriptions, transcriptions, thumbnails
- **NEW**: Autoloading system with `spl_autoload_register`
- Enhanced CPT with improved metadata handling
- Golden Prompt v3.2 optimized for timestamp generation
- Improved duration parsing (ISO8601 → seconds → formatted)
- Progress bars and real-time logging for bulk operations
- Speaker priority system (manual rules → title parsing → AI extraction → fallback)

### 🔧 Technical Improvements
- Modern architecture with autoloading
- Clean separation of concerns (MVC-like structure)
- Performance optimized with AJAX loading
- Proper meta_query for duration filtering
- Enhanced error handling and logging
- CSS with high specificity to avoid theme conflicts
- Responsive design across all components

### 📋 Workflow Improvements
- Complete automation: Import → Transcription → AI → Taxonomies → Publish
- Manual override capabilities for all automated features
- Real-time progress tracking for bulk operations
- Configurable CRON (every 5 minutes)
- Queue system with status tracking (pending → processing → done/error)

### 🗄️ Database & Metadata
- Enhanced meta fields: `_ipv_yt_duration_seconds`, `_ipv_yt_duration_formatted`
- Improved taxonomy extraction from AI descriptions
- Automatic hashtag to WordPress tag conversion
- Better handling of speakers/guests across formats

## v7.5.8 - 2025-11-27
### Production Release
- Bulk Tools panel integration
- Fix hashtag extraction (same line parsing)
- Fix ISO8601 duration parsing
- Timestamp generation based on content changes
- Debug logging for video duration
- Speaker Rules menu with correct priority
- Improved YouTube embed CSS

## v6.4.0 - 2025-11-25
### Video Wall Feature 🎬
- **NEW**: Video Wall shortcode `[ipv_video_wall]` with advanced filtering
- **NEW**: 2+3 layout (2 videos top row, 3 videos bottom row)
- **NEW**: AJAX-powered filtering by categoria and relatore
- **NEW**: Search functionality within video wall
- **NEW**: Responsive pagination with smooth transitions
- **NEW**: YouTube Shorts filtering - automatically excludes videos < 60 seconds
- **NEW**: Admin settings page for Video Wall configuration (IPV Production > Video Wall)
- **NEW**: YouTube-style red play button overlay on video thumbnails

### Features
- Default 5 videos per page (configurable)
- Filters for categoria, relatore, and search
- Modern UI with hover effects and smooth animations
- Mobile-responsive design
- Backward compatible with column-based layouts

### Technical Improvements
- Performance optimized with AJAX loading
- Proper meta_query for duration filtering
- Enqueued assets only when needed
- Clean separation of concerns (MVC-like structure)

## v4.5
- Added Prompt Gold integration
- Added Markdown/Notion renderer
- Added Short Filter (<5 minutes)
- Added Custom Template for CPT
- Added Info screen in admin
- General improvements and fixes
