# 🔧 IPV Pro Vendor - Server v1.3.5

**Data**: 9 Dicembre 2024
**Tipo**: Bug Fix (Permissions)
**Compatibilità**: Client v10.0.9+

---

## 🐛 Bug Fix

### ✅ RISOLTO: Errore "Sorry, you are not allowed to access this page"

**Problema**:
Gli amministratori WordPress non potevano accedere alla pagina di status/troubleshooting:
```
URL: https://aiedintorni.it/wp-admin/admin.php?page=ipv-vendor-troubleshooting
Errore: Sorry, you are not allowed to access this page.
```

**Causa**:
La pagina richiedeva la capability `manage_woocommerce` invece di `manage_options`. Solo gli utenti con ruolo "Shop Manager" di WooCommerce potevano accedere, mentre gli amministratori WordPress standard no.

**Soluzione**:
- ✅ Cambiata capability da `manage_woocommerce` → `manage_options`
- ✅ Aggiunto alias per retrocompatibilità: URL `ipv-vendor-troubleshooting` funziona
- ✅ URL principale rimane: `ipv-vendor-status`
- ✅ Ora accessibile a tutti gli amministratori WordPress

---

## 📝 Modifiche Tecniche

### File: `includes/class-admin-status-page.php`

**1. Capability Cambiata (Line 38, 49, 217)**:
```php
// Prima (v1.3.4):
'manage_woocommerce'

// Dopo (v1.3.5):
'manage_options' // Capability standard amministratori WordPress
```

**2. Alias Aggiunto per Retrocompatibilità (Lines 43-51)**:
```php
// v1.3.5 - Backward compatibility alias for old URL
add_submenu_page(
    null, // Hidden menu (no parent)
    'IPV Vendor Troubleshooting',
    'IPV Vendor Troubleshooting',
    'manage_options',
    'ipv-vendor-troubleshooting', // ← Old URL ancora funzionante
    [ $this, 'render_page' ]
);
```

---

## 🎯 Risultato

### Prima (v1.3.4):
```
❌ Solo utenti con ruolo WooCommerce Shop Manager
❌ Amministratori WordPress: "Access Denied"
❌ URL ipv-vendor-troubleshooting: non funziona
```

### Dopo (v1.3.5):
```
✅ Tutti gli amministratori WordPress (manage_options)
✅ URL principale: admin.php?page=ipv-vendor-status
✅ URL vecchio: admin.php?page=ipv-vendor-troubleshooting (alias)
✅ Stessa pagina, entrambi gli URL funzionano
```

---

## 🔄 Upgrade Path

### Da v1.3.4 → v1.3.5:
1. Carica `ipv-pro-vendor-v1.3.5.zip` via WordPress → Plugin → Aggiungi nuovo
2. **NON serve disattivare**
3. Vai a WooCommerce → IPV Vendor Status (oppure vecchio URL)
4. La pagina ora si carica correttamente

---

## 📚 URL Funzionanti

Entrambi questi URL ora funzionano:

**URL Principale (Consigliato)**:
```
https://aiedintorni.it/wp-admin/admin.php?page=ipv-vendor-status
```

**URL Vecchio (Retrocompatibilità)**:
```
https://aiedintorni.it/wp-admin/admin.php?page=ipv-vendor-troubleshooting
```

**Menu WordPress**:
```
WooCommerce → IPV Vendor Status
```

---

## 📦 Cosa Include la Pagina Status

Una volta accessibile, la pagina mostra:

✅ Status Authorization Header (funzionante/bloccato)
✅ Fix applicati automaticamente
✅ Ultimo controllo sistema
✅ Versione plugin
✅ Bottone "Verifica Ora & Ri-applica Fix"
✅ Istruzioni troubleshooting dettagliate
✅ Test manuale con cURL
✅ Documentazione GitHub

---

## ⚠️ Note

**Capability WordPress**:
- `manage_options` = Standard per amministratori WordPress
- `manage_woocommerce` = Solo per Shop Manager WooCommerce
- La v1.3.5 usa `manage_options` per maggiore accessibilità

**Sicurezza**:
- Entrambe le capability richiedono login come amministratore
- Nessun cambio al livello di sicurezza, solo più permissivo per admin

---

## 🚀 Prossimi Step

Dopo aver installato v1.3.5:

1. **Accedi alla pagina Status**:
   - WooCommerce → IPV Vendor Status
   - Verifica che si carichi senza errori

2. **Controlla Authorization Status**:
   - Dovrebbe mostrare "✅ FUNZIONANTE" o "❌ BLOCCATO"
   - Se bloccato, clicca "Verifica Ora"

3. **Test Trascrizione**:
   - Vai sul client
   - Testa "Download Transcript" su un video
   - Verifica che funzioni senza "unauthorized"

---

## 📥 Download

**File**: `ipv-pro-vendor-v1.3.5.zip` (68 KB)

**Link GitHub**:
```
https://github.com/daniemi1977/ipv/raw/claude/get-recent-uploads-01V9asSqzYj32qNmxnS6wSyY/ipv-pro-vendor-v1.3.5.zip
```

---

**Versione**: 1.3.5
**Autore**: IPV Team
**Repository**: https://github.com/daniemi1977/ipv
**Fix**: Permissions + Backward Compatibility
