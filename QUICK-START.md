# ⚡ IPV Pro Cloud Edition - Quick Start

**Deployment rapido in 30 minuti**

---

## 📦 File Necessari

```
✅ ipv-pro-vendor-v1.0.0.zip (41KB)
✅ ipv-production-system-pro-v10.0.0.zip (253KB)
✅ DEPLOY-GUIDE-FINAL.md (guida completa)
```

---

## 🚀 Deployment in 5 Passi

### 1️⃣ Upload Plugin VENDOR (10 min)

**Su bissolomarket.com**:

```bash
# Admin → Plugin → Aggiungi nuovo
Upload: ipv-pro-vendor-v1.0.0.zip

# Attiva plugin
# Vai su: Video IPV → Impostazioni Vendor

# Inserisci API Keys:
SupaData Keys (3):
  sd_abc123...
  sd_def456...
  sd_ghi789...

OpenAI Key: sk-proj-xyz123...
YouTube Key: AIzaSyABC123... (opzionale)

# Salva e testa
```

**Verifica database**:
```sql
SHOW TABLES LIKE 'wp_ipv_%';
-- Deve mostrare 5 tabelle
```

**Verifica cron**:
```bash
wp cron event list | grep ipv_vendor_reset_credits
# Output: monthly cron schedulato per 1° del mese
```

---

### 2️⃣ Setup WooCommerce (10 min)

**Crea 4 Prodotti** (tipo: Simple Subscription):

#### Prodotto 1: IPV Pro Trial
```
Nome: IPV Pro - Trial
Prezzo: €0
Rinnovo: 1 mese (limite 1 periodo)

Custom Fields:
_ipv_is_license_product = 1
_ipv_plan_slug = trial
_ipv_credits_total = 10
_ipv_activation_limit = 1
```

#### Prodotto 2: IPV Pro Starter
```
Nome: IPV Pro - Starter
Prezzo: €29/mese
Rinnovo: 1 mese

Custom Fields:
_ipv_is_license_product = 1
_ipv_plan_slug = starter
_ipv_credits_total = 25
_ipv_activation_limit = 1
```

#### Prodotto 3: IPV Pro Professional
```
Nome: IPV Pro - Professional
Prezzo: €89/mese
Rinnovo: 1 mese

Custom Fields:
_ipv_is_license_product = 1
_ipv_plan_slug = professional
_ipv_credits_total = 100
_ipv_activation_limit = 3
```

#### Prodotto 4: IPV Pro Business
```
Nome: IPV Pro - Business
Prezzo: €249/mese
Rinnovo: 1 mese

Custom Fields:
_ipv_is_license_product = 1
_ipv_plan_slug = business
_ipv_credits_total = 500
_ipv_activation_limit = 10
```

**Test rapido**:
```bash
# Acquista Trial (gratis)
# Verifica: Video IPV → Licenze
# Deve apparire nuova licenza con key XXXX-XXXX-XXXX-XXXX
```

---

### 3️⃣ Upload Plugin CLIENT (5 min)

**Su bissolomarket.com**:

```bash
# Vai su: Video IPV → Remote Updates
# Clicca: Upload New Version

File: ipv-production-system-pro-v10.0.0.zip
Version: 10.0.0
Changelog:
🔒 Cloud Edition - API keys protette server-side
✨ Sistema licenze e crediti mensili
🔄 Auto-updates automatici
🚀 Dashboard crediti integrata

# Upload → Verifica file caricato
```

---

### 4️⃣ Test Cliente (10 min)

**Simula cliente**:

1. **Acquista piano** (usa email test):
   ```
   Email: test@example.com
   Prodotto: IPV Pro Starter (€29)
   → Completa checkout
   ```

2. **Ricevi email**:
   ```
   ✅ Ordine WooCommerce completato
   ✅ Email licenza IPV Pro
   Subject: "La tua licenza IPV Pro è pronta!"

   License Key: ABCD-1234-EFGH-5678
   Download: [link con nonce]
   ```

3. **Installa plugin** (su sito test):
   ```bash
   # Download ZIP da link email
   # Upload su sito test
   # Attiva plugin
   ```

4. **Attiva licenza**:
   ```
   Video IPV → 🔑 Licenza

   License Key: ABCD-1234-EFGH-5678
   Site URL: https://sito-test.com
   Site Name: Sito Test

   → Activate License
   ```

5. **Verifica dashboard**:
   ```
   ✅ Licenza Attiva
   Piano: Starter
   Crediti: 25/25 (100%)
   Reset: 01/01/2026
   Giorni rimanenti: 25
   ```

6. **Test import video**:
   ```
   Video IPV → Import Video
   URL: https://youtube.com/watch?v=dQw4w9WgXcQ
   Mode: Auto
   AI Description: ✅

   → Import

   ✅ Video importato
   ✅ Trascrizione generata (via vendor)
   ✅ Descrizione AI generata (via vendor)
   ✅ Crediti: 24/25 (-1)
   ```

---

### 5️⃣ Go-Live! 🎉

**Checklist finale**:

- [ ] API keys testate e funzionanti
- [ ] Cron mensile schedulato
- [ ] 4 prodotti WooCommerce pubblicati
- [ ] Plugin CLIENT caricato su Remote Updates
- [ ] Test acquisto + attivazione completato
- [ ] Test import video completato
- [ ] Email SMTP configurato
- [ ] Backup database effettuato

**Pubblica**:

1. **Crea pagina vendita**: `/ipv-pro/`
2. **Aggiungi prodotti** al menu
3. **Configura payment gateway** (Stripe/PayPal)
4. **Annuncia** ai clienti esistenti

---

## 🔧 Comandi Utili

### Vendor Server

```bash
# Verifica licenze attive
wp db query "SELECT COUNT(*) FROM wp_ipv_licenses WHERE status='active'"

# Lista licenze
wp db query "SELECT email, plan, credits_remaining, status FROM wp_ipv_licenses ORDER BY created_at DESC LIMIT 10"

# Forza reset crediti (test)
wp cron event run ipv_vendor_reset_credits

# Check API logs ultimi 10
wp db query "SELECT * FROM wp_ipv_api_logs ORDER BY created_at DESC LIMIT 10"

# Verifica cache
wp db query "SELECT COUNT(*) FROM wp_ipv_transcript_cache"
```

### Troubleshooting

```bash
# License non generata?
wp post meta list [product_id] | grep _ipv

# Crediti non resettati?
wp cron event list | grep ipv_vendor

# API Gateway non risponde?
curl -X POST https://bissolomarket.com/wp-json/ipv-vendor/v1/license/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"ABCD-1234-EFGH-5678","site_url":"https://test.com"}'
```

---

## 📊 Monitoring

**Dashboard Vendor** → `Video IPV → Dashboard`:
- Licenze attive/totali
- Revenue mensile
- Crediti utilizzati oggi
- API errors

**Analytics** → `Video IPV → Analytics`:
- API calls per endpoint
- Cache hit rate
- Response time
- Top clienti per usage

---

## 📧 Support

**Se qualcosa non funziona**:

1. Controlla **DEPLOY-GUIDE-FINAL.md** → Sezione Troubleshooting
2. Verifica error logs: `/wp-content/debug.log`
3. Test API manualmente con curl
4. Verifica database tables esistono

---

## 🎯 Risultato Finale

Dopo questi 5 passi avrai:

✅ Sistema SaaS completo e funzionante
✅ Vendita automatica con WooCommerce
✅ Licenze generate automaticamente
✅ Credits gestiti server-side
✅ API keys protette (mai esposte!)
✅ Auto-updates per clienti
✅ Email automation
✅ Analytics completa

**Revenue potenziale**:
- 10 clienti Starter: €290/mese
- 5 clienti Professional: €445/mese
- 2 clienti Business: €498/mese
- **Totale: €1.233/mese ricorrente**

---

**Made with ❤️ by IPV Production Team**
v10.0.0 Cloud Edition - Secure, Scalable, Smart
