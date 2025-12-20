# ⚡ QUICK DEPLOY v2.8.2

## 🎯 FIX: "Errore server sconosciuto" → RISOLTO!

---

## 📦 FILE NECESSARI

1. **ipv-pro-vendor-v2.8.2-FIX-ACTIVATION.zip** (208KB)
2. **migration-v2.8.2.sql** (per upgrade)

---

## 🚀 DEPLOY RAPIDO

### Se è la PRIMA INSTALLAZIONE:

```bash
# 1. Upload
wp plugin install ipv-pro-vendor-v2.8.2-FIX-ACTIVATION.zip

# 2. Activate
wp plugin activate ipv-pro-vendor

# 3. Test
# Vai a: cliente.com/wp-admin/edit.php?post_type=ipv_video&page=ipv-license
# Inserisci license key
# ✅ Deve attivarsi!
```

### Se STAI AGGIORNANDO da v2.8.0 o v2.8.1:

```bash
# 1. BACKUP!
wp db export backup-pre-v2.8.2.sql

# 2. Aggiungi campi alla tabella
wp db query < migration-v2.8.2.sql

# 3. Update plugin
wp plugin install ipv-pro-vendor-v2.8.2-FIX-ACTIVATION.zip --force

# 4. Verifica
wp db query "SELECT 
    license_key, 
    variant_slug, 
    activation_limit, 
    activation_count 
FROM wp_ipv_licenses 
LIMIT 1;"

# Se mostra i campi = ✅ OK!
```

---

## ✅ VERIFICA FUNZIONAMENTO

### Test Attivazione Licenza:

```
1. Login cliente WordPress
2. IPV Videos → Licenza
3. Inserisci: XXXXX-XXXXX-XXXXX
4. Click "Attiva Licenza"
5. ✅ Deve mostrare: "Licenza Attiva"
6. ✅ Deve mostrare credits
7. ❌ NO "Errore server sconosciuto"
```

### Se vedi ancora errore:

```bash
# Check logs
tail -50 wp-content/debug.log

# Verifica metodo esiste
grep "function activate_license" \
  wp-content/plugins/ipv-pro-vendor/includes/class-license-manager.php

# Se NON trova il metodo:
# → Re-upload plugin
# → Svuota cache PHP: service php-fpm restart
```

---

## 🔍 COSA È STATO FIXATO

**Problema:**
- ❌ Metodo `activate_license()` non esisteva
- ❌ Fatal error PHP
- ❌ Client riceveva "Errore server sconosciuto"

**Soluzione:**
- ✅ Metodo `activate_license()` implementato
- ✅ Verifica licenza, status, scadenza
- ✅ Gestione activation_limit
- ✅ Schema database completo

---

## 📞 TROUBLESHOOTING

### Errore: "Colonna sconosciuta variant_slug"

```bash
# Run migration
wp db query < migration-v2.8.2.sql
```

### Errore: "Method activate_license doesn't exist"

```bash
# Re-upload plugin
wp plugin install ipv-pro-vendor-v2.8.2-FIX-ACTIVATION.zip --force --activate
```

### Errore: "Limite attivazioni raggiunto"

```sql
-- Check attivazioni
SELECT license_key, domain, activation_count, activation_limit 
FROM wp_ipv_licenses 
WHERE license_key = 'XXXXX-XXXXX-XXXXX';

-- Reset se necessario
UPDATE wp_ipv_licenses 
SET activation_count = 0, domain = '' 
WHERE license_key = 'XXXXX-XXXXX-XXXXX';
```

---

## 🎉 DEPLOY COMPLETATO!

**v2.8.2 = Attivazione Licenza Funzionante!** ✅

**Tempo Deploy: ~2 minuti** ⚡

**Compatibilità: 100% con client v10.9.0** ✅
