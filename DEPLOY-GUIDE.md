# 🚀 IPV Pro - Guida Deploy Completa

## 📋 Checklist Pre-Deploy

### Server bissolomarket.com
- [ ] WordPress 6.0+ installato
- [ ] PHP 8.0+ attivo
- [ ] MySQL 8.0+ attivo
- [ ] SSL certificate valido (HTTPS)
- [ ] WooCommerce installato e configurato
- [ ] Gateway pagamento configurato (Stripe/PayPal)

### API Keys Ready
- [ ] YouTube API Key
- [ ] SupaData API Keys (3x)
- [ ] OpenAI API Key

---

## 🔧 STEP 1: Deploy Plugin VENDOR (30 min)

### 1.1 Upload Plugin

```bash
# Via SFTP/FTP
Upload: ipv-pro-vendor-v1.0.0.zip
To: /wp-content/plugins/

# Oppure via WordPress Admin
Plugin → Aggiungi nuovo → Carica plugin → Scegli file
```

### 1.2 Configurare API Keys

**PRIMA DI ATTIVARE**, modifica:

`ipv-pro-vendor/includes/class-api-gateway.php`

```php
// Linee 18-22
const YOUTUBE_API_KEY = 'AIza...'; // TUA YouTube Key
const SUPADATA_API_KEY_1 = 'sd_...'; // TUA SupaData Key 1
const SUPADATA_API_KEY_2 = 'sd_...'; // TUA SupaData Key 2
const SUPADATA_API_KEY_3 = 'sd_...'; // TUA SupaData Key 3
const OPENAI_API_KEY = 'sk-proj-...'; // TUA OpenAI Key
```

### 1.3 Attivare Plugin

1. WordPress Admin → Plugin
2. Trova "IPV Pro Vendor System"
3. Clicca **Attiva**
4. Attendi messaggio successo

### 1.4 Verifica Database

```sql
-- Connetti a MySQL
mysql -u USERNAME -p DATABASE_NAME

-- Controlla tabelle create
SHOW TABLES LIKE 'wp_ipv_%';

-- Dovresti vedere 5 tabelle:
-- wp_ipv_licenses
-- wp_ipv_activations
-- wp_ipv_api_logs
-- wp_ipv_transcript_cache
-- wp_ipv_usage_stats
```

### 1.5 Test Health Check

```bash
curl https://bissolomarket.com/wp-json/ipv-vendor/v1/health

# Risposta attesa:
# {"status":"ok","service":"IPV Pro Vendor API","version":"1.0.0",...}
```

---

## 🛍️ STEP 2: Setup WooCommerce (15 min)

### 2.1 Installa WooCommerce Subscriptions

```
Plugin → Aggiungi nuovo
Cerca: "WooCommerce Subscriptions"
Installa e Attiva
```

### 2.2 Configura Gateway Pagamento

```
WooCommerce → Impostazioni → Pagamenti
Attiva: Stripe (o PayPal)
Inserisci: API Keys
Salva
```

### 2.3 Crea Prodotti IPV Pro

**Prodotto 1: Trial (Gratis)**
```
Prodotti → Aggiungi nuovo
Nome: IPV Pro - Trial (10 Video Gratis)
Prezzo: €0
Tipo: Prodotto semplice
Virtuale: ✓

Scroll giù → IPV Pro License Settings:
☑ IPV Pro License Product
Piano: trial
Crediti Mensili: 10
Limite Attivazioni: 1

Pubblica
```

**Prodotto 2: Starter (€19.95/mese)**
```
Nome: IPV Pro - Starter (25 Video/Mese)
Prezzo: €19.95
Tipo: Abbonamento semplice
Periodo: 1 mese

IPV Pro Settings:
☑ IPV Pro License Product
Piano: starter
Crediti Mensili: 25
Limite Attivazioni: 1

Pubblica
```

**Prodotto 3: Professional (€49.95/mese)**
```
Nome: IPV Pro - Professional (100 Video/Mese)
Prezzo: €49.95
Tipo: Abbonamento semplice
Periodo: 1 mese

IPV Pro Settings:
☑ IPV Pro License Product
Piano: professional
Crediti Mensili: 100
Limite Attivazioni: 3

Pubblica
```

**Prodotto 4: Business (€99.95/mese)**
```
Nome: IPV Pro - Business (500 Video/Mese)
Prezzo: €99.95
Tipo: Abbonamento semplice
Periodo: 1 mese

IPV Pro Settings:
☑ IPV Pro License Product
Piano: business
Crediti Mensili: 500
Limite Attivazioni: 10

Pubblica
```

---

## 📤 STEP 3: Upload Plugin CLIENT v10.0.0 (10 min)

### 3.1 Prepara Plugin CLIENT

Il plugin CLIENT è nella cartella corrente già modificato con:
- API keys RIMOSSE
- API Client che chiama bissolomarket.com
- License activation UI
- Remote updates client

### 3.2 Crea ZIP Plugin CLIENT

```bash
cd /home/user/ipv
zip -r ipv-production-system-pro-v10.0.0.zip ipv-production-system-pro/ \
  -x "*.git*" "*.DS_Store" "*node_modules*"
```

### 3.3 Upload nel Sistema Updates

```
1. Admin → IPV Pro Vendor → Updates
2. Clicca "Upload Nuova Versione"
3. File ZIP: ipv-production-system-pro-v10.0.0.zip
4. Versione: 10.0.0
5. Changelog:
   🚀 IPV Pro v10.0.0 - Cloud Edition

   ✨ NOVITÀ:
   - Sistema cloud completamente remotizzato
   - API Gateway per SupaData e OpenAI
   - License activation system
   - Remote updates automatici

   🔒 SICUREZZA:
   - Zero API keys nel client
   - Tutte le chiamate validate server-side
6. Carica versione
```

---

## 🧪 STEP 4: Test Completo (15 min)

### 4.1 Test Ordine

```
1. Apri sito in incognito
2. Vai su /ipv-pro/ (o URL prodotto)
3. Aggiungi "IPV Pro - Trial" al carrello
4. Checkout (email: test@example.com)
5. Completa ordine (€0, no pagamento)
```

### 4.2 Verifica Email License

```
Controlla inbox: test@example.com
Email ricevuta: "🔑 La tua License Key..."
License Key visibile: XXXX-XXXX-XXXX-XXXX
Download link funzionante
```

### 4.3 Verifica Database

```sql
SELECT * FROM wp_ipv_licenses ORDER BY id DESC LIMIT 1;

-- Verifica:
-- license_key: popolato
-- status: active
-- credits_remaining: 10
-- credits_total: 10
```

### 4.4 Test Download Plugin

```
Clicca link download nell'email
File scaricato: ipv-production-system-pro-v10.0.0.zip
```

### 4.5 Test Attivazione License

```
1. Installa WordPress pulito su localhost (o sito test)
2. Upload plugin scaricato
3. Attiva plugin
4. Video IPV → 🔑 Licenza
5. Inserisci license key dall'email
6. Clicca "Attiva Licenza"
7. Verifica messaggio successo
8. Verifica crediti visibili: 10/10
```

### 4.6 Test Import Video

```
1. Video IPV → Importa Video
2. Inserisci URL YouTube: https://youtube.com/watch?v=dQw4w9WgXcQ
3. Mode: Auto
4. Language: Italian
5. Clicca "Importa"
6. Attendi (~30 sec)
7. Verifica trascrizione generata
8. Verifica descrizione AI generata
9. Verifica crediti decrementati: 9/10
```

---

## ✅ STEP 5: Go Live (5 min)

### 5.1 Landing Page

Crea: `/ipv-pro/`

```html
<h1>🎬 IPV Production System Pro</h1>
<p>Il sistema professionale per automatizzare la produzione dei tuoi video YouTube</p>

[products ids="ID1,ID2,ID3,ID4" columns="4"]

<a href="/docs/ipv-pro/">Documentazione</a>
```

### 5.2 Annuncio

- ✅ Email lista subscriber
- ✅ Post Facebook/Instagram
- ✅ Video YouTube annuncio
- ✅ Story Instagram

### 5.3 Monitoring

```bash
# Setup cron monitoring (opzionale)
crontab -e

# Aggiungi:
*/5 * * * * curl -s https://bissolomarket.com/wp-json/ipv-vendor/v1/health >> /var/log/ipv-health.log
```

---

## 🔄 STEP 6: Manutenzione

### Mensile

```bash
# Verifica reset crediti
wp cron event list | grep ipv

# Force reset manuale se necessario
wp cron event run ipv_vendor_reset_credits
```

### Trimestrale

```sql
-- Clear old cache
DELETE FROM wp_ipv_transcript_cache
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Clear old logs
DELETE FROM wp_ipv_api_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Update Plugin CLIENT

```
1. Nuova versione pronta
2. Admin → Updates → Upload
3. File: ipv-production-system-pro-vX.Y.Z.zip
4. Versione: X.Y.Z
5. Changelog: ...
6. Carica
7. Clienti ricevono notifica auto (24h)
```

---

## 🆘 Troubleshooting

### License non generata

```
1. Check: WooCommerce attivo?
2. Check: Prodotto ha "_ipv_is_license_product = yes"?
3. Check: wp-content/debug.log
4. Force regenerate:
   wp eval "IPV_Vendor_License_Manager::instance()->create_license_from_order(ORDER_ID, PRODUCT_ID);"
```

### API non risponde

```bash
# Test health
curl https://bissolomarket.com/wp-json/ipv-vendor/v1/health

# Resave permalinks
WP Admin → Settings → Permalinks → Save Changes

# Check .htaccess
cat /var/www/bissolomarket.com/.htaccess | grep -A10 "BEGIN WordPress"
```

### Crediti non resettati

```bash
# Check cron scheduled
wp cron event list | grep ipv

# Run manually
wp cron event run ipv_vendor_reset_credits

# Check logs
tail -f /var/www/bissolomarket.com/wp-content/debug.log | grep IPV
```

---

## 📊 KPI da Monitorare

### Giornaliero
- Nuovi ordini
- Licenze attive
- API calls totali
- Errori API (status 4xx, 5xx)

### Settimanale
- Revenue totale
- Churn rate subscription
- Credits usage medio
- Cache hit rate

### Mensile
- MRR (Monthly Recurring Revenue)
- LTV (Lifetime Value) clienti
- CAC (Customer Acquisition Cost)
- Support tickets

---

## 🎯 Checklist Finale

- [ ] Plugin VENDOR attivato su bissolomarket.com
- [ ] API keys configurate
- [ ] 5 tabelle database create
- [ ] WooCommerce configurato
- [ ] 4 prodotti pubblicati
- [ ] Plugin CLIENT v10.0.0 uploadato
- [ ] Test ordine completato ✓
- [ ] Test license activation ✓
- [ ] Test import video ✓
- [ ] Health check OK
- [ ] Landing page pubblicata
- [ ] Monitoring attivo
- [ ] **READY TO LAUNCH!** 🚀

---

**Buon lancio! 🎉**

Per supporto: daniele@ilpuntodivista.com
