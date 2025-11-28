
# IPV Production System Pro – Changelog

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
