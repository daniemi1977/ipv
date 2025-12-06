# 💰 IPV Pro Cloud Edition - Piani di Pricing

**Versione**: v10.0.0
**Aggiornato**: 2025-12-06

---

## 📊 4 Piani Disponibili

| Piano | Prezzo | Chiamate/Mese | Attivazioni | Ideale Per |
|-------|--------|---------------|-------------|------------|
| **Free** | €0 | 10 | 1 sito | Test e hobby |
| **Basic** | €9,99/mese | 100 | 1 sito | Blogger individuali |
| **Pro** | €19,99/mese | 200 | 3 siti | Content creator |
| **Premium** | €39,99/mese | 500 | 5 siti | Agenzie e team |

---

## 🛒 Configurazione Prodotti WooCommerce

### Prodotto 1: IPV Pro Free

```
DATI PRODOTTO:
Nome: IPV Pro - Free
Slug: ipv-pro-free
Tipo: Abbonamento semplice (Simple Subscription)
Prezzo: €0
Periodo: 1 mese
Limite rinnovo: 1 (non si rinnova automaticamente)
Stato: Pubblicato

DESCRIZIONE BREVE:
Prova IPV Pro gratuitamente con 10 importazioni video al mese. Perfetto per testare il sistema.

DESCRIZIONE COMPLETA:
Il piano Free include:
- 10 importazioni video/mese
- Trascrizioni AI automatiche (SupaData)
- Descrizioni ottimizzate SEO (GPT-4o)
- 1 sito attivabile
- Support via email

Ideale per: Blogger alle prime armi, test del sistema

CUSTOM FIELDS (Meta):
_ipv_is_license_product = 1
_ipv_plan_slug = free
_ipv_credits_total = 10
_ipv_activation_limit = 1
```

---

### Prodotto 2: IPV Pro Basic

```
DATI PRODOTTO:
Nome: IPV Pro - Basic
Slug: ipv-pro-basic
Tipo: Abbonamento semplice
Prezzo: €9,99
Periodo: 1 mese
Rinnovo: Automatico (fino a cancellazione)
Stato: Pubblicato

DESCRIZIONE BREVE:
Piano perfetto per blogger individuali. 100 video al mese con AI completa.

DESCRIZIONE COMPLETA:
Il piano Basic include:
- 100 importazioni video/mese
- Trascrizioni AI illimitate (SupaData)
- Descrizioni SEO ottimizzate (GPT-4o)
- Golden Prompt per massimizzare ranking YouTube
- 1 sito attivabile
- Support prioritario via email

Ideale per: YouTuber individuali, blogger con 3-4 video/giorno

CUSTOM FIELDS (Meta):
_ipv_is_license_product = 1
_ipv_plan_slug = basic
_ipv_credits_total = 100
_ipv_activation_limit = 1
```

---

### Prodotto 3: IPV Pro Pro

```
DATI PRODOTTO:
Nome: IPV Pro - Pro
Slug: ipv-pro-pro
Tipo: Abbonamento semplice
Prezzo: €19,99
Periodo: 1 mese
Rinnovo: Automatico
Stato: Pubblicato
Badge: POPOLARE / BEST VALUE

DESCRIZIONE BREVE:
Il piano più scelto! 200 video/mese su 3 siti. Perfetto per content creator professionisti.

DESCRIZIONE COMPLETA:
Il piano Pro include:
- 200 importazioni video/mese
- Trascrizioni AI illimitate (SupaData)
- Descrizioni SEO ottimizzate (GPT-4o)
- Golden Prompt avanzato
- 3 siti attivabili (perfetto per network)
- Support prioritario via email
- Dashboard analytics avanzata

Ideale per: Content creator professionisti, network di canali, multi-sito

CUSTOM FIELDS (Meta):
_ipv_is_license_product = 1
_ipv_plan_slug = pro
_ipv_credits_total = 200
_ipv_activation_limit = 3
```

---

### Prodotto 4: IPV Pro Premium

```
DATI PRODOTTO:
Nome: IPV Pro - Premium
Slug: ipv-pro-premium
Tipo: Abbonamento semplice
Prezzo: €39,99
Periodo: 1 mese
Rinnovo: Automatico
Stato: Pubblicato
Badge: AZIENDE

DESCRIZIONE BREVE:
Soluzione enterprise per agenzie e team. 500 video/mese su 5 siti.

DESCRIZIONE COMPLETA:
Il piano Premium include:
- 500 importazioni video/mese
- Trascrizioni AI illimitate (SupaData)
- Descrizioni SEO ottimizzate (GPT-4o)
- Golden Prompt personalizzabile
- 5 siti attivabili (perfetto per clienti)
- Support prioritario + chat dedicata
- Dashboard analytics completa
- Onboarding personalizzato

Ideale per: Agenzie, team marketing, gestori multi-canale

CUSTOM FIELDS (Meta):
_ipv_is_license_product = 1
_ipv_plan_slug = premium
_ipv_credits_total = 500
_ipv_activation_limit = 5
```

---

## 💡 Come Configurare in WooCommerce

### Passo 1: Installa WooCommerce Subscriptions

```bash
# Necessario per creare subscription products
Plugin → Aggiungi nuovo → "WooCommerce Subscriptions"
```

### Passo 2: Crea i Prodotti

Per **ogni prodotto** (Free, Basic, Pro, Premium):

1. **Prodotti → Aggiungi nuovo**

2. **Dati Prodotto** → Seleziona **Abbonamento semplice**

3. **Prezzo**:
   - Free: €0
   - Basic: €9,99
   - Pro: €19,99
   - Premium: €39,99

4. **Periodo abbonamento**: 1 mese

5. **Opzioni abbonamento**:
   - Rinnovo: Automatico (tranne Free: limite 1 periodo)
   - Trial: Nessuno
   - Quota iscrizione: €0

6. **Custom Fields** (sotto al corpo del prodotto):

   Clicca "Custom Fields" → Aggiungi:

   ```
   Nome: _ipv_is_license_product
   Valore: 1

   Nome: _ipv_plan_slug
   Valore: free (o basic, pro, premium)

   Nome: _ipv_credits_total
   Valore: 10 (o 100, 200, 500)

   Nome: _ipv_activation_limit
   Valore: 1 (o 1, 3, 5)
   ```

7. **Pubblica**

### Passo 3: Test Acquisto

```bash
# Acquista piano Free (gratis)
# Vai su: Video IPV → Licenze
# Verifica licenza generata con:
# - Plan: free
# - Credits: 10/10
# - Activations: 0/1
```

---

## 📈 Calcolo Revenue

### Scenario Conservativo (50 clienti)

```
30x Free (€0)        = €0/mese
15x Basic (€9,99)    = €149,85/mese
4x Pro (€19,99)      = €79,96/mese
1x Premium (€39,99)  = €39,99/mese
────────────────────────────────────
TOTALE MRR           = €269,80/mese
TOTALE ANNUALE       = €3.237,60/anno
```

### Scenario Moderato (200 clienti)

```
100x Free (€0)       = €0/mese
60x Basic (€9,99)    = €599,40/mese
30x Pro (€19,99)     = €599,70/mese
10x Premium (€39,99) = €399,90/mese
────────────────────────────────────
TOTALE MRR           = €1.599/mese
TOTALE ANNUALE       = €19.188/anno
```

### Scenario Ottimistico (500 clienti)

```
200x Free (€0)        = €0/mese
200x Basic (€9,99)    = €1.998/mese
80x Pro (€19,99)      = €1.599,20/mese
20x Premium (€39,99)  = €799,80/mese
────────────────────────────────────
TOTALE MRR            = €4.397/mese
TOTALE ANNUALE        = €52.764/anno
```

---

## 💰 Costi Operativi Mensili

### API Costs (scenario moderato 200 clienti)

```
SupaData API:
- 60x Basic (100 vid/m)  = 6.000 trascrizioni
- 30x Pro (200 vid/m)    = 6.000 trascrizioni
- 10x Premium (500 vid/m)= 5.000 trascrizioni
TOTALE: 17.000 trascrizioni/mese
Costo: ~€170/mese (3 keys SupaData Pro)

OpenAI GPT-4o:
- 17.000 descrizioni/mese
- ~$0,01 per descrizione
Costo: ~€150/mese

Hosting + WooCommerce:
- VPS/Cloud hosting
Costo: ~€30/mese

TOTALE COSTI: ~€350/mese
```

### Profitto Netto (scenario moderato)

```
Revenue: €1.599/mese
Costi:   €350/mese
─────────────────────
PROFITTO: €1.249/mese (€14.988/anno)
Margine: 78% 🚀
```

---

## 🎯 Strategia di Lancio

### Fase 1: Early Adopters (Mese 1-2)

**Sconto 50% primi 100 clienti**:

```
Basic: €9,99 → €4,99/mese (primi 3 mesi)
Pro: €19,99 → €9,99/mese (primi 3 mesi)
Premium: €39,99 → €19,99/mese (primi 3 mesi)
```

Codice sconto WooCommerce:
```
Codice: EARLY50
Tipo: Percentuale
Valore: 50%
Limite utilizzi: 100
Scadenza: +60 giorni
Solo per: ipv-pro-basic, ipv-pro-pro, ipv-pro-premium
```

### Fase 2: Upselling Automatico

**Email automation** quando cliente:

1. **Raggiunge 80% crediti**:
   ```
   Subject: Stai per finire i crediti! ⚠️

   Hai usato 80/100 crediti questo mese.

   Passa a piano superiore:
   Basic → Pro (+100 crediti): solo €10/mese in più
   Pro → Premium (+300 crediti): solo €20/mese in più

   [Upgrade Now]
   ```

2. **Finisce crediti**:
   ```
   Subject: Crediti esauriti - Upgrade o aspetta reset 📊

   Hai finito i 100 crediti di Dicembre.

   Opzioni:
   1. Aspetta reset (01 Gennaio)
   2. Upgrade a Pro (200 crediti) → €19,99/mese

   [Upgrade] [Aspetto Reset]
   ```

3. **Piano Free dopo 30 giorni**:
   ```
   Subject: Ti piace IPV Pro? Passa a Basic! 🚀

   Hai usato il piano Free per 30 giorni.

   Passa a Basic per soli €9,99/mese:
   - 100 video/mese (10x più del Free)
   - Support prioritario
   - Reset automatico crediti

   [Upgrade a Basic - €9,99/mese]
   ```

---

## 🎁 Bundle e Promozioni

### Bundle Annuale (risparmio 20%)

```
Basic Annuale:  €9,99 x 12 = €119,88 → €95,90 (-20%)
Pro Annuale:    €19,99 x 12 = €239,88 → €191,90 (-20%)
Premium Annuale: €39,99 x 12 = €479,88 → €383,90 (-20%)
```

WooCommerce setup:
```
Prodotto: IPV Pro Basic (Annuale)
Tipo: Abbonamento semplice
Prezzo: €95,90
Periodo: 1 anno
Risparmio visualizzato: "Risparmia €23,98 (20%)"
```

### Promo Stagionali

**Black Friday** (Novembre):
```
Sconto 40% su tutti i piani annuali
Codice: BLACKFRIDAY40
```

**Natale** (Dicembre):
```
3 mesi gratis passando ad annuale
(= 25% di sconto)
```

**Compleanno canale** (data tua):
```
Sconto 30% per 48 ore
Flash sale
```

---

## 📊 Tracking & Analytics

### KPI da Monitorare

**WordPress Dashboard** → Video IPV → Analytics:

1. **MRR (Monthly Recurring Revenue)**
   - Obiettivo Mese 1: €500
   - Obiettivo Mese 3: €1.500
   - Obiettivo Mese 6: €3.000

2. **Churn Rate** (cancellazioni)
   - Target: < 5% mensile
   - Se > 10%: problema da investigare

3. **ARPU (Average Revenue Per User)**
   - Target: €10-15/mese

4. **Conversion Rate Free → Paid**
   - Target: > 30%
   - Ottimo: > 50%

5. **Lifetime Value (LTV)**
   - Calcolo: ARPU x (1/Churn Rate)
   - Target: > €150

### Dashboard Query

```sql
-- MRR corrente
SELECT SUM(
  CASE plan
    WHEN 'free' THEN 0
    WHEN 'basic' THEN 9.99
    WHEN 'pro' THEN 19.99
    WHEN 'premium' THEN 39.99
  END
) as mrr
FROM wp_ipv_licenses
WHERE status = 'active';

-- Distribuzione piani
SELECT plan, COUNT(*) as count
FROM wp_ipv_licenses
WHERE status = 'active'
GROUP BY plan;

-- Churn rate (ultimi 30 giorni)
SELECT
  COUNT(*) as cancelled,
  (SELECT COUNT(*) FROM wp_ipv_licenses WHERE status='active') as active,
  ROUND(COUNT(*) / (SELECT COUNT(*) FROM wp_ipv_licenses WHERE status='active') * 100, 2) as churn_rate
FROM wp_ipv_licenses
WHERE status = 'cancelled'
AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🎯 Prossimi Passi

1. **Crea i 4 prodotti in WooCommerce** (15 min)
2. **Testa acquisto per ogni piano** (10 min)
3. **Configura email automation** (30 min)
4. **Setup promozione Early Adopters** (5 min)
5. **Pubblica pagina pricing** (20 min)
6. **Annuncia su canali social** (10 min)

**Totale deployment**: ~90 minuti

---

**Made with ❤️ by IPV Production Team**
v10.0.0 Cloud Edition - Prezzi competitivi, valore immenso
