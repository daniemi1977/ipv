# CHANGELOG - IPV Production System Pro

## v10.5.2 (2025-12-18) - STABILITY & SaaS HARDENING

**Release Type**: Stability + SaaS hardening

### ✅ Fixed

- **PHP 8.1+ Compatibility**
  - Fix completo PHP 8.1+ Deprecated warnings
  - `strpos()` / `str_replace()` / `sanitize_text_field()` con input null risolti
  - Nuovo file `includes/ipv-safe.php` con helper functions
  - Eliminati tutti i warning nei log PHP

- **N/A Data Bug**
  - Eliminato salvataggio di valori fittizi "N/A" dai dati YouTube
  - Corretto bug logico `has_title = true` su titoli non validi
  - Titoli normalizzati prima del salvataggio DB
  - Funzione `ipv_normalize_title()` per validazione

- **401 Unauthorized Error**
  - Risolto errore 401 Unauthorized su endpoint transcript
  - Bloccati retry errati su errori di autorizzazione
  - 401 ora restituisce errore immediato senza retry (fail-fast)
  - Migliorata gestione errori API

### ⚡ Improved

- **Performance**
  - Aggiunta cache transcript persistente (30 giorni)
  - Normalizzazione dati YouTube prima del DB write
  - Validazione licenza prima di chiamate API costose
  - Logging più chiaro (no dati sensibili)

### 🔐 Security

- **Authorization**
  - Header Authorization standard Bearer implementato
  - Dominio inviato e verificato lato vendor
  - Pipeline interrotta se licenza non valida
  - Nessun credenziale esposta nei log

### 🛠️ Technical Changes

**New Files:**
- `includes/ipv-safe.php` - PHP 8.1+ safe helpers

**Modified Files:**
- `ipv-production-system-pro.php` - Version bump, ipv-safe require
- `includes/class-api-client.php` - 401 handling, no retry on unauthorized
- `includes/class-youtube-api.php` - N/A normalization, title validation

**Functions Added:**
- `ipv_safe_string($value): string` - Convert any value to safe string
- `ipv_safe_array($value): array` - Convert any value to safe array
- `ipv_safe_sanitize($value): string` - Safe sanitization wrapper
- `ipv_is_na($value): bool` - Check if value is N/A
- `ipv_normalize_title($title, $video_id): ?string` - Normalize YouTube title

---

## Upgrade Notes

### From v10.5.1 to v10.5.2

✅ **100% Backward Compatible**  
No breaking changes. All existing functionality preserved.

**What's New:**
- Cleaner PHP error logs
- More stable API communication
- Better data quality in database

**Action Required:**
None - Plugin auto-updates safely.

**Recommended:**
- Clear WordPress object cache after update
- Check PHP error log - should be cleaner

---

## Previous Releases

### v10.5.1 (2025-12-17) - CSS FIX
- Fixed: CSS senza @apply (funziona con CDN Tailwind)
- Fixed: SVG icons ora hanno dimensioni corrette
- Fixed: Tutti gli stili .ipv-* in CSS puro

### v10.5.0 (2025-12-17) - GOLDEN PROMPT CLIENT
- Nuovo modulo Golden Prompt Client
- Sync automatico alla attivazione licenza
- Check periodico per aggiornamenti
- Memorizzazione offuscata
