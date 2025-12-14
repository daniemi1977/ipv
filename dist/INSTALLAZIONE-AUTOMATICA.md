# 🚀 Installazione Automatica - IPV Pro

Plugin **chiavi in mano** con wizard di setup integrato.

---

## ⚡ Quick Start (3 Passi)

### SERVER (IPV Pro Vendor)

```bash
# 1. Upload plugin
wp plugin install dist/ipv-pro-vendor-server-v1.4.0-optimized.zip

# 2. Attiva
wp plugin activate ipv-pro-vendor

# 3. Segui il wizard automatico
# Si aprirà automaticamente in admin al primo accesso
```

✅ **Il wizard configura automaticamente:**
- Database (5 tabelle)
- API keys (YouTube, OpenAI, SupaData)
- WooCommerce check
- Prodotti IPV Pro
- CRON schedulati

---

### CLIENT (IPV Production System Pro)

```bash
# 1. Upload plugin
wp plugin install dist/ipv-production-system-pro-client-v10.3.0-optimized.zip

# 2. Attiva
wp plugin activate ipv-production-system-pro

# 3. Segui il wizard automatico
# Si aprirà automaticamente in admin al primo accesso
```

✅ **Il wizard configura automaticamente:**
- Database (3 tabelle)
- Server URL
- Licenza activation
- CRON setup
- Test import

---

## 📋 Wizard Steps - Server

1. **Database** ✅ Auto-create 5 tabelle
   - `wp_ipv_licenses`
   - `wp_ipv_license_activations`
   - `wp_ipv_api_logs`
   - `wp_ipv_security_log`
   - `wp_ipv_performance_stats`

2. **API Keys** 🔑 Inserisci credenziali
   - YouTube Data API Key
   - OpenAI API Key
   - SupaData Key + Secret

3. **WooCommerce** 🛒 Verifica installazione
   - Check WooCommerce attivo
   - Check WooCommerce Subscriptions (opzionale)

4. **Prodotti** 📦 Crea piani IPV Pro
   - Trial (€0, 10 credits)
   - Starter (€19.95/m, 25 credits)
   - Professional (€49.95/m, 100 credits)
   - Business (€99.95/m, 500 credits)

5. **Completo** 🎉 Pronto all'uso!

---

## 📋 Wizard Steps - Client

1. **Database** ✅ Auto-create 3 tabelle
   - `wp_ipv_prod_queue`
   - `wp_ipv_prod_youtube_cache`
   - `wp_ipv_prod_metrics`

2. **Server URL** 🌐 Connessione al vendor
   - Inserisci URL server IPV Pro Vendor
   - Test connessione automatico
   - Esempio: `https://your-vendor.com`

3. **Licenza** 🔑 Attivazione
   - Inserisci license key (32 caratteri)
   - Validazione automatica
   - Sincronizzazione crediti

4. **CRON** ⏰ Schedulazione
   - WordPress CRON (default)
   - System CRON (raccomandato)
   - Comandi crontab generati automaticamente

5. **Test** 🎯 Verifica setup
   - Import video di test
   - Check connettività
   - Dashboard statistiche

---

## 🎯 Caratteristiche Auto-Installer

### Server

✅ **Database automatico**
- Creazione tabelle con indici ottimizzati
- Schema audit logging completo
- Retention policy configurabile

✅ **CRON automatico**
- Monthly credit reset
- Daily cleanup logs
- Daily stats aggregation

✅ **Health check**
- Endpoint `/wp-json/ipv-vendor/v1/health`
- Monitoring automatico
- Alerting configurabile

✅ **Uninstall pulito**
- Rimozione completa tabelle
- Delete options
- Clear transients
- Clear scheduled events

### Client

✅ **Database automatico**
- Tabelle queue e cache
- Performance metrics tracking
- Auto-cleanup old data

✅ **Connection test**
- Verifica server reachability
- Check SSL certificate
- Version compatibility

✅ **License validation**
- Attivazione one-click
- Auto-sync crediti
- Renewal notifications

✅ **CRON flessibile**
- WordPress CRON (zero config)
- System CRON (max performance)
- Commands auto-generated

---

## 🆘 Troubleshooting

### Wizard non si apre dopo attivazione

```bash
# Forza redirect al wizard
wp option set ipv_vendor_show_wizard 1  # Server
wp option set ipv_prod_show_wizard 1    # Client
```

### Database non si crea

```bash
# Server - Crea tabelle manualmente
wp db query < wp-content/plugins/ipv-pro-vendor/includes/audit-tables.sql

# Client - Trigger creazione
wp eval "IPV_Prod_Auto_Installer::install();"
```

### Licenza non si attiva

```bash
# Check server URL
wp option get ipv_prod_server_url

# Test connessione
curl https://your-server.com/wp-json/ipv-vendor/v1/health

# Validazione manuale
curl -X POST https://your-server.com/wp-json/ipv-vendor/v1/license/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXX","site_url":"https://client.com"}'
```

---

## 📊 Verifica Installazione

### Server

```bash
# Check tabelle
wp db query "SHOW TABLES LIKE 'wp_ipv_%'"

# Check health
curl https://your-server.com/wp-json/ipv-vendor/v1/health

# Check CRON
wp cron event list | grep ipv_vendor
```

### Client

```bash
# Check tabelle
wp db query "SHOW TABLES LIKE 'wp_ipv_prod_%'"

# Check setup
wp option get ipv_prod_license_status

# Check CRON
wp cron event list | grep ipv_prod
```

---

## ✨ Novità v1.4.0 / v10.3.0

### Auto-Installer Features

- ✅ **Zero configurazione manuale** - tutto via wizard UI
- ✅ **Progress tracking** - visualizzazione step completati
- ✅ **Validation automatica** - check configurazione real-time
- ✅ **Rollback sicuro** - uninstall completo
- ✅ **Health monitoring** - diagnostics integrati
- ✅ **Multi-language** - wizard disponibile in 6 lingue

### Security Enhancements

- 🔒 **Nonce validation** su tutti i form
- 🔒 **Capability check** (manage_options required)
- 🔒 **Input sanitization** su tutte le richieste
- 🔒 **SQL injection prevention** con prepared statements
- 🔒 **XSS protection** con esc_html/esc_attr/esc_url

---

## 📞 Supporto

**Email**: [email protected]
**GitHub**: https://github.com/daniemi1977/ipv/issues
**Docs**: Vedi README.md nei plugin

---

**Build**: 2025-12-14
**Server Version**: 1.4.0-optimized
**Client Version**: 10.3.0-optimized
**Status**: ✅ Production Ready con Auto-Installer
