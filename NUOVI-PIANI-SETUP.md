# 🚀 Setup Rapido Nuovi Piani di Pricing

**Aggiornamento v10.0.0** - Piani ottimizzati per massimizzare conversioni

---

## 📊 I Nuovi 4 Piani

| Piano | Prezzo | Video/Mese | Siti | Target |
|-------|--------|------------|------|--------|
| **Free** | €0 | 10 | 1 | Test & Hobby |
| **Basic** | €9,99/mese | 100 | 1 | Blogger |
| **Pro** | €19,99/mese | 200 | 3 | Creator |
| **Premium** | €39,99/mese | 500 | 5 | Agenzie |

---

## ⚡ Setup in WooCommerce (15 minuti)

### PRODOTTO 1: IPV Pro Free

```bash
# WordPress Admin → Prodotti → Aggiungi nuovo

Nome prodotto: IPV Pro - Free
Slug: ipv-pro-free
SKU: IPV-FREE

Tipo prodotto: Abbonamento semplice

PREZZO:
  Prezzo abbonamento: €0
  Periodo: ogni 1 mese
  Limite rinnovo: 1 (non si rinnova)

DESCRIZIONE BREVE:
Prova IPV Pro gratuitamente. 10 video al mese con AI completa.

CUSTOM FIELDS (in fondo alla pagina):
  Clicca "Custom Fields" → Aggiungi nuovo

  Campo 1:
    Nome: _ipv_is_license_product
    Valore: 1

  Campo 2:
    Nome: _ipv_plan_slug
    Valore: free

  Campo 3:
    Nome: _ipv_credits_total
    Valore: 10

  Campo 4:
    Nome: _ipv_activation_limit
    Valore: 1

→ Pubblica
```

---

### PRODOTTO 2: IPV Pro Basic

```bash
Nome prodotto: IPV Pro - Basic
Slug: ipv-pro-basic
SKU: IPV-BASIC

Tipo prodotto: Abbonamento semplice

PREZZO:
  Prezzo abbonamento: €9,99
  Periodo: ogni 1 mese
  Rinnovo: Automatico

PROVA GRATUITA (opzionale):
  Durata prova: 7 giorni

DESCRIZIONE BREVE:
Piano perfetto per blogger. 100 video/mese con AI completa.

CUSTOM FIELDS:
  _ipv_is_license_product = 1
  _ipv_plan_slug = basic
  _ipv_credits_total = 100
  _ipv_activation_limit = 1

→ Pubblica
```

---

### PRODOTTO 3: IPV Pro Pro ⭐ POPOLARE

```bash
Nome prodotto: IPV Pro - Pro
Slug: ipv-pro-pro
SKU: IPV-PRO

Tipo prodotto: Abbonamento semplice

PREZZO:
  Prezzo abbonamento: €19,99
  Periodo: ogni 1 mese
  Rinnovo: Automatico

PROVA GRATUITA:
  Durata prova: 7 giorni

DESCRIZIONE BREVE:
Il piano più scelto! 200 video/mese su 3 siti.

BADGE (in immagine prodotto):
  Aggiungi badge "PIÙ POPOLARE" sull'immagine

CUSTOM FIELDS:
  _ipv_is_license_product = 1
  _ipv_plan_slug = pro
  _ipv_credits_total = 200
  _ipv_activation_limit = 3

→ Pubblica
```

---

### PRODOTTO 4: IPV Pro Premium

```bash
Nome prodotto: IPV Pro - Premium
Slug: ipv-pro-premium
SKU: IPV-PREMIUM

Tipo prodotto: Abbonamento semplice

PREZZO:
  Prezzo abbonamento: €39,99
  Periodo: ogni 1 mese
  Rinnovo: Automatico

PROVA GRATUITA:
  Durata prova: 7 giorni

DESCRIZIONE BREVE:
Soluzione enterprise. 500 video/mese su 5 siti + support dedicato.

CUSTOM FIELDS:
  _ipv_is_license_product = 1
  _ipv_plan_slug = premium
  _ipv_credits_total = 500
  _ipv_activation_limit = 5

→ Pubblica
```

---

## ✅ Verifica Setup

### Test 1: Acquista Free Plan

```bash
# Frontend
1. Vai su /negozio/ o /prodotti/
2. Trova "IPV Pro - Free"
3. Aggiungi al carrello
4. Completa checkout (€0,00)

# Backend
5. Video IPV → Licenze
6. Verifica nuova licenza:
   ✓ Email: test@example.com
   ✓ Plan: free
   ✓ Credits: 10/10
   ✓ Activations: 0/1
   ✓ Status: active

# Email
7. Controlla inbox test@example.com
   ✓ Email "Licenza IPV Pro attivata"
   ✓ License Key: XXXX-XXXX-XXXX-XXXX
   ✓ Download link plugin
```

### Test 2: Verifica Custom Fields

```bash
# WP-CLI
wp post meta list [PRODUCT_ID] | grep _ipv

# Output atteso:
_ipv_is_license_product: 1
_ipv_plan_slug: free
_ipv_credits_total: 10
_ipv_activation_limit: 1
```

### Test 3: Simula Upgrade

```bash
# Cliente compra Free, poi vuole Basic

1. Cliente va su My Account → Abbonamenti
2. Vede "IPV Pro Free" (scadenza 30 giorni)
3. Clicca "Passa a piano superiore"
4. Seleziona "IPV Pro Basic" (€9,99/mese)
5. Conferma upgrade

# Sistema deve:
- Cancellare subscription Free
- Attivare subscription Basic
- Aggiornare licenza: plan=basic, credits=100/100
```

---

## 💰 Promozione Lancio (opzionale)

### Coupon Early Bird

```bash
# WooCommerce → Coupon → Aggiungi nuovo

Codice coupon: EARLY50
Tipo sconto: Percentuale
Importo: 50
Descrizione: Sconto 50% primi 100 clienti

LIMITAZIONI:
  Prodotti: ipv-pro-basic, ipv-pro-pro, ipv-pro-premium
  Limiti utilizzo: 100 (totali)
  Data scadenza: [+60 giorni da oggi]

RESTRIZIONI:
  ✓ Solo per nuovi utenti
  ✓ Applica sconto ai primi 3 mesi

→ Pubblica
```

**Marketing Message**:
```
🎉 Sconto 50% per i primi 100 clienti!

Basic: €9,99 → €4,99/mese (primi 3 mesi)
Pro: €19,99 → €9,99/mese (primi 3 mesi)
Premium: €39,99 → €19,99/mese (primi 3 mesi)

Usa codice: EARLY50
```

---

## 📈 Calcolo Revenue

### Scenario Conservativo (100 clienti, 60 giorni)

```
50x Free (€0)        = €0/mese
30x Basic (€9,99)    = €299,70/mese
15x Pro (€19,99)     = €299,85/mese
5x Premium (€39,99)  = €199,95/mese
────────────────────────────────────
MRR:                 = €799,50/mese
ARR:                 = €9.594/anno
```

### Scenario Ottimistico (500 clienti, 6 mesi)

```
200x Free (€0)         = €0/mese
200x Basic (€9,99)     = €1.998/mese
80x Pro (€19,99)       = €1.599,20/mese
20x Premium (€39,99)   = €799,80/mese
────────────────────────────────────
MRR:                   = €4.397/mese
ARR:                   = €52.764/anno
```

### Costi Operativi

```
SupaData API (3 keys): ~€150/mese
OpenAI GPT-4o:         ~€100/mese
Hosting WooCommerce:   ~€30/mese
────────────────────────────────────
TOTALE COSTI:          ~€280/mese

PROFITTO NETTO (scenario ottimistico):
€4.397 - €280 = €4.117/mese
Margine: 93% 🚀
```

---

## 🎨 Pagina Pricing Pronta

Ho creato `pricing-page.html` con:

✅ Design moderno con gradients
✅ 4 card pricing responsive
✅ Badge "PIÙ POPOLARE" su Pro
✅ Tabella comparativa completa
✅ FAQ section
✅ Call-to-action finale

**Come usare**:

1. **WordPress**:
   ```bash
   Pagine → Aggiungi nuova
   Titolo: Prezzi
   Editor: HTML (copia/incolla pricing-page.html)
   Template: Larghezza piena
   → Pubblica
   URL: /prezzi/
   ```

2. **Elementor**:
   ```bash
   Elementor → Aggiungi nuova pagina
   Importa template HTML
   Oppure ricrea design con widget Elementor
   ```

3. **Page Builder**:
   ```bash
   Usa il codice HTML come riferimento
   Ricrea con i blocchi del tuo page builder
   ```

---

## 📱 Next Steps

1. ✅ **Crea 4 prodotti** → 15 minuti
2. ✅ **Test acquisto Free** → 5 minuti
3. ✅ **Pubblica pagina pricing** → 10 minuti
4. ⏰ **Setup email automation** → 30 minuti
5. ⏰ **Lancia promozione Early Bird** → 5 minuti
6. ⏰ **Annuncia su social/email** → 20 minuti

**Tempo totale**: ~90 minuti da zero a live! 🚀

---

## 🎯 Email Automation da Configurare

### Email 1: Benvenuto Free

```
Trigger: Acquisto Free plan
Subject: 🎉 Benvenuto in IPV Pro!

Ciao {nome},

Grazie per aver scelto IPV Pro Free!

Ecco la tua licenza:
License Key: {license_key}

Cosa puoi fare:
✅ 10 video/mese con AI
✅ Trascrizioni automatiche
✅ Descrizioni SEO ottimizzate

📥 Download plugin: {download_link}

Ti piace? Passa a Basic per 100 video/mese → solo €9,99
[Upgrade Now]

Buon lavoro!
Il Team IPV Pro
```

### Email 2: Upgrade da Free (dopo 7 giorni)

```
Trigger: 7 giorni dopo acquisto Free
Subject: Pronto per più contenuti? 🚀

Ciao {nome},

Hai usato IPV Pro Free per 7 giorni.

I tuoi numeri:
- Video importati: {count}
- Crediti usati: {used}/{total}

Passa a Basic e ottieni:
✅ 100 video/mese (10x più del Free!)
✅ Support prioritario
✅ Solo €9,99/mese

[Upgrade a Basic - 7 giorni gratis]

Il Team IPV Pro
```

### Email 3: Crediti in esaurimento

```
Trigger: Crediti < 20%
Subject: ⚠️ Crediti in esaurimento

Ciao {nome},

Hai usato {used}/{total} crediti questo mese.

Ti rimangono solo {remaining} importazioni fino al reset
(prossimo reset: {reset_date}).

Opzioni:
1. Aspetta il reset automatico
2. Upgrade a piano superiore → crediti immediati

{current_plan} → {next_plan}: +{extra_credits} crediti
Solo €{price_diff} in più al mese

[Upgrade Now]

Il Team IPV Pro
```

---

**Documenti Completi**:
- `PRICING-PLANS.md` → Strategia completa
- `pricing-page.html` → Pagina pronta da pubblicare
- `NUOVI-PIANI-SETUP.md` → Questa guida

**Ready to launch! 🚀**
