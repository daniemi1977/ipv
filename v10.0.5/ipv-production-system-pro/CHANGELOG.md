# IPV Production System Pro - Changelog

## [9.2.1] - 2025-12-05 - MENU FIX

### ✅ Fixed - Missing Menus
- **Bulk Import menu restored** from v7.9.40 (was missing in v9.1.7)
- **Video Wall menu restored** from v7.9.40 (was missing in v9.1.7)
- **Language menu added** for multilingua management (new in v9.2.1)

### 📝 Complete Menu Structure
Now includes all menus:
1. Dashboard
2. Import Video
3. Auto-Import RSS
4. Queue
5. **Bulk Import** (restored)
6. **Video Wall** (restored)
7. Bulk Tools (from v9.1.7)
8. Settings
9. **Language** (new)

**Total:** 9 submenu items (3 more than v9.1.7, full parity with v7.9.40 + new Language)

---

## [9.2.0] - 2025-12-05 - SUPADATA ROTATION RESTORED

### 🎯 Highlight
Questa release unisce il meglio di due mondi: l'architettura enterprise della 9.1.7 con il sistema di rotazione delle chiavi SupaData della 7.9.40.

### ✅ Added - SupaData Multiple Keys
- **3 campi separati per le chiavi API SupaData** (Primary, Optional #2, Optional #3)
- **Modalità di rotazione selezionabile:**
  - 🔒 **Fixed Key**: Usa sempre la chiave #1, passa alle altre solo se i crediti si esauriscono
  - 🔄 **Round-Robin Rotation**: Alterna tra le chiavi configurate ad ogni chiamata API
- **Indicatore di stato delle chiavi**: Mostra quante chiavi sono configurate, modalità attiva, e quale chiave sarà usata alla prossima chiamata
- **Sistema intelligente di fallback**: Se una chiave fallisce (402/429), passa automaticamente alla successiva

### ✅ Fixed - Backward Compatibility
- **100% compatibile con le impostazioni della v7.9.40**
- Migrazione automatica delle chiavi esistenti
- Mantiene tutte le opzioni: `ipv_supadata_api_key`, `ipv_supadata_api_key_2`, `ipv_supadata_api_key_3`, `ipv_supadata_rotation_mode`

### 🚀 Maintained - Enterprise Features from v9.1.7
- 20 nuove classi enterprise (Elementor, Gutenberg, REST API, WP-CLI, ecc.)
- Sistema multilingua completo (6 lingue)
- Templates WordPress standard
- Analytics avanzati
- Video SEO
- Performance optimization
- Taxonomy manager
- Golden Prompt manager

### 📝 Technical Details
- **class-supadata.php**: Ripristinato dalla v7.9.40 con sistema completo di rotazione
- **class-settings.php**: Aggiornato con i 3 campi separati e select per la modalità
- **Logging migliorato**: Traccia quale chiave viene utilizzata per ogni chiamata
- **Gestione errori raffinata**: Distingue tra quota esaurita (402) e rate limit (429)

### 🔄 Migration Notes
Se stai aggiornando dalla 7.9.40:
- ✅ Le tue chiavi esistenti saranno mantenute
- ✅ La modalità di rotazione sarà preservata
- ✅ Non è necessaria alcuna riconfigurazione

Se stai aggiornando dalla 9.1.x:
- ⚠️ Il campo textarea con newline è stato sostituito con 3 campi separati
- ✅ La chiave principale sarà mantenuta
- 📝 Dovrai riconfigurare eventuali chiavi multiple inserite come newline

### 💡 Use Case
Perfetto per chi ha 3 account SupaData con 300 chiamate/mese ciascuno (= 900 chiamate/mese totali) e vuole distribuire equamente il carico con la rotazione Round-Robin.

---

## [9.1.0] - 2025-06-05 - REFACTORING RELEASE

### 🚨 Breaking Changes
- Nessuno. 100% retrocompatibile.

### ✅ Fixed - Codice Morto Rimosso
- Rimosso `class-cron-interval.php` (mai incluso)
- Rimosso `class-full-pipeline.php` (IPV_Full_Pipeline mai usato)
- Rimosso `class-queue-dashboard.php` (IPV_Queue_Dashboard mai caricato)
- Rimosso `class-video-wall-settings.php` (già commentato in v9.0.0)
- Rimosso `class-ai-queue.php` (sistema coda duplicato)

### ✅ Fixed - Duplicazioni Codice
- Centralizzate 3 implementazioni di `extract_video_id()` in `IPV_Prod_Helpers::extract_youtube_id()`
- Centralizzate 3 implementazioni di `video_exists()` in `IPV_Prod_Helpers::video_exists()`
- Centralizzata `get_post_id_by_video_id()` in `IPV_Prod_Helpers`
- Centralizzata `set_thumbnail()` in `IPV_Prod_Helpers::set_youtube_thumbnail()`
- Centralizzata `format_duration()` in `IPV_Prod_Helpers`

### ✅ Fixed - Meta Key Standardizzate
- Introdotte costanti `IPV_Prod_Helpers::META_*` per tutte le meta key
- Documentazione chiara delle meta key supportate
- Retrocompatibilità con vecchie meta key mantenuta

### 🆕 New - Classe Helper Centralizzata
- Nuova classe `IPV_Prod_Helpers` con:
  - `extract_youtube_id()` - Estrazione ID da tutti i formati URL YouTube
  - `video_exists()` - Verifica video duplicato
  - `get_post_id_by_video_id()` - Ottieni post ID da video ID
  - `duration_to_seconds()` - Converti durata ISO 8601 in secondi
  - `format_duration()` - Formatta secondi in stringa leggibile
  - `get_formatted_duration()` - Ottieni durata formattata da post ID
  - `detect_video_source()` - Rileva piattaforma da URL
  - `sanitize_youtube_url()` - Valida e normalizza URL YouTube
  - `set_youtube_thumbnail()` - Scarica e imposta thumbnail
  - `format_number()` - Formatta numeri grandi (1.2M, 500K)
  - `log()` - Logging centralizzato

### 🔧 Changed - Miglioramenti
- `IPV_Prod_CPT::init()` ora chiamato correttamente (fix meta boxes)
- Menu admin consolidati (rimossi duplicati)
- Sistema CRON unificato (rimosso cron duplicato `ipv_ai_queue_runner`)
- Migliorata performance autoloader
- Aggiunte traduzioni mancanti

### 📊 Stats
- File rimossi: 5
- Linee di codice rimosse: ~500
- Duplicazioni eliminate: ~400 linee
- Nuova classe helper: ~350 linee
- Risparmio netto: ~550 linee

---

## [9.0.0] - 2024-XX-XX

### Added
- Lazy loading con spl_autoload_register
- Multi-source import (YouTube, Vimeo, Dailymotion)
- Video Wall con filtri AJAX
- Elementor integration

### Changed
- Migrazione da 39 require manuali a 2
- Video Wall settings unificati

---

## [8.x.x] - Previous versions

See previous changelog entries for older versions.
