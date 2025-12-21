# CHANGELOG - IPV Pro Vendor System

## v1.6.4 (2025-12-18) - SaaS CORE RELIABILITY

**Release Type**: SaaS core reliability

### ✅ Fixed

- **Authorization Parsing**
  - Parsing robusto header Authorization
  - Gestione corretta di `Bearer` prefix
  - Eliminati falsi positivi su licenze attive
  - 401 restituito solo per errori reali

- **License Validation**
  - Coerenza API key ↔ dominio ↔ piano
  - Validazione centralizzata licenze
  - Fix controllo dominio (anti domain spoofing)

### ⚡ Improved

- **API Gateway**
  - Header parsing con `ipv_parse_auth_header()`
  - Logging audit-safe (no credenziali)
  - Compatibilità Cloudflare / SG headers
  - Validazione licenza prima di processare

### 🔐 Security

- **Authorization**
  - No retry su chiamate non autorizzate (client-side)
  - Anti domain spoofing enforcement
  - Preparazione per credit ledger system
  - IP tracking per audit

### 🛠️ Technical Changes

**New Files:**
- `includes/ipv-safe.php` - PHP 8.1+ safe helpers

**Modified Files:**
- `ipv-pro-vendor.php` - Version bump, ipv-safe require
- `includes/class-api-gateway.php` - Authorization header parsing (if applicable)
- `includes/class-license-manager.php` - License validation logic (if applicable)

**Functions Added:**
- `ipv_safe_string($value): string` - Convert any value to safe string
- `ipv_safe_array($value): array` - Convert any value to safe array
- `ipv_safe_sanitize($value): string` - Safe sanitization wrapper
- `ipv_parse_auth_header(): string` - Parse Authorization header safely

---

## Upgrade Notes

### From v1.6.3 to v1.6.4

✅ **100% Backward Compatible**  
No breaking changes. All existing functionality preserved.

**What's New:**
- More stable authorization handling
- Better license validation
- Cleaner audit logs

**Action Required:**
None - Plugin auto-updates safely.

**Recommended:**
- Review audit logs for 401 errors
- Test license validation flow

---

## Previous Releases

### v1.6.3 (2025-12-17)
- Golden Prompt module fixes
- Analytics dashboard improvements

### v1.6.0 (2025-12-17) - GOLDEN PROMPT MODULE
- Nuovo modulo completo per gestione Golden Prompts
- Auto-configuratore con placeholder
- REST API autenticata
- Pannello admin dedicato
