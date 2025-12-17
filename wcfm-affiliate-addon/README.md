# WCFM Affiliate Pro

**Sistema di affiliazione avanzato e INDIPENDENTE per WCFM Marketplace**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/daniemi1977/ipv)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## Caratteristiche Principali

### Sistema Completamente Indipendente
- **NON sovrascrive** tabelle esistenti di WCFM Affiliate o AffiliateWP
- Prefisso unico `wcfm_aff_pro_` per tutte le tabelle, opzioni e cookie
- **Completamente reversibile**: la disattivazione NON elimina dati
- Può coesistere con altri plugin affiliate

### Gestione Affiliati
- Registrazione affiliati con approvazione manuale o automatica
- Dashboard affiliato completa con statistiche in tempo reale
- Codice affiliato personalizzato per ogni utente
- Gestione livelli e tier di commissione

### Sistema Commissioni Avanzato
- Commissioni percentuali, fisse o a tier
- Commissioni per prodotto, categoria o vendor
- Commissioni ricorrenti per abbonamenti
- Esclusione automatica di tasse, spedizione e sconti

### MLM / Network Marketing
- Struttura multi-livello fino a N livelli configurabili
- **Visualizzazione albero rete** interattiva con D3.js
- **Spostamento affiliati** tra reti con drag & drop
- Commissioni override per upline
- Statistiche di rete per ogni affiliato

### Dual Role: Affiliato ↔ Venditore
- Gli **affiliati possono diventare venditori** con un click
- I **venditori ottengono codice affiliato** istantaneamente
- Gestione unificata dei guadagni
- Auto-registrazione opzionale per nuovi vendor

### Tracking & Analytics
- Tracking click e visite con cookie configurabile
- Geolocalizzazione visitatori
- Report dettagliati per periodo
- Grafici interattivi con Chart.js
- Tasso di conversione per affiliato

### Pagamenti
- Richieste payout con soglia minima configurabile
- Metodi: PayPal, Bonifico Bancario, Credito Negozio
- Approvazione manuale o automatica
- Storico completo pagamenti

### Integrazioni
- **WCFM Marketplace**: Menu dedicato nel dashboard vendor
- **WooCommerce**: Tracking ordini e commissioni automatiche
- **REST API**: Endpoint completi per integrazioni esterne

---

## Requisiti di Sistema

| Requisito | Versione Minima |
|-----------|-----------------|
| PHP | 8.0+ |
| WordPress | 6.0+ |
| WooCommerce | 8.0+ |
| WCFM Marketplace | 6.5+ (consigliato) |

---

## Installazione

### Via WordPress Admin
1. Scarica `wcfm-affiliate-pro.zip`
2. Vai su **WordPress Admin → Plugin → Aggiungi nuovo**
3. Clicca **Carica plugin**
4. Seleziona il file ZIP e clicca **Installa ora**
5. Clicca **Attiva**

### Via FTP
1. Estrai `wcfm-affiliate-pro.zip`
2. Carica la cartella `wcfm-affiliate-addon` in `/wp-content/plugins/`
3. Attiva il plugin da **WordPress Admin → Plugin**

---

## Configurazione

### Impostazioni Generali
```
WordPress Admin → WCFM Affiliate Pro → Impostazioni
```

| Opzione | Descrizione | Default |
|---------|-------------|---------|
| Abilita Plugin | Attiva/disattiva il sistema | Sì |
| Tipo Registrazione | auto, approval, invite | approval |
| Durata Cookie | Giorni di validità referral | 30 |
| Variabile URL | Parametro per tracking | refpro |
| Soglia Payout Minimo | Importo minimo per richiedere payout | €50 |

### Commissioni
```
WordPress Admin → WCFM Affiliate Pro → Commissioni
```

| Opzione | Descrizione | Default |
|---------|-------------|---------|
| Tipo Commissione | percentage, flat, tiered | percentage |
| Tasso Base | Percentuale o importo fisso | 10% |
| Escludi Tasse | Non calcolare su IVA | Sì |
| Escludi Spedizione | Non calcolare su shipping | Sì |
| Approvazione | manual, auto, delay | manual |

### MLM / Multi-Livello
```
WordPress Admin → WCFM Affiliate Pro → MLM
```

| Opzione | Descrizione | Default |
|---------|-------------|---------|
| Abilita MLM | Attiva struttura multi-livello | No |
| Livelli | Numero di livelli di profondità | 3 |
| Tassi per Livello | Array di percentuali [L1, L2, L3...] | [10, 5, 2] |

---

## Shortcode Disponibili

### Dashboard Affiliato
```
[wcfm_aff_pro_dashboard]
```
Mostra la dashboard completa dell'affiliato con statistiche, link referral e azioni rapide.

### Registrazione Affiliato
```
[wcfm_aff_pro_registration]
[wcfm_aff_pro_register]  // alias
```
Form di registrazione per nuovi affiliati.

### Login Affiliato
```
[wcfm_aff_pro_login]
```
Form di login con redirect alla dashboard.

### Link Referral
```
[wcfm_aff_pro_link url="https://example.com/product"]
```
Genera automaticamente il link referral per l'affiliato corrente.

### Statistiche
```
[wcfm_aff_pro_stats]
```
Widget statistiche rapide (guadagni, referral, visite).

### Materiali Promozionali
```
[wcfm_aff_pro_creatives]
```
Galleria di banner e materiali per la promozione.

### Classifica Affiliati
```
[wcfm_aff_pro_leaderboard limit="10"]
```
Classifica top affiliati per guadagni.

### Dual Role Status
```
[wcfm_aff_pro_dual_role]
```
Mostra status affiliato/venditore con opzioni di upgrade.

### Diventa Venditore
```
[wcfm_aff_pro_become_vendor]
```
Widget per affiliati che vogliono aprire un negozio.

### Diventa Affiliato
```
[wcfm_aff_pro_become_affiliate]
```
Widget per vendor che vogliono ottenere codice affiliato.

---

## REST API

Base URL: `/wp-json/wcfm-affiliate-pro/v1/`

### Endpoint Disponibili

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/affiliates` | Lista affiliati |
| GET | `/affiliates/{id}` | Dettaglio affiliato |
| POST | `/affiliates` | Crea affiliato |
| PUT | `/affiliates/{id}` | Aggiorna affiliato |
| DELETE | `/affiliates/{id}` | Elimina affiliato |
| GET | `/referrals` | Lista referral |
| GET | `/commissions` | Lista commissioni |
| GET | `/payouts` | Lista pagamenti |
| POST | `/payouts` | Richiedi payout |
| GET | `/stats` | Statistiche globali |
| GET | `/stats/{affiliate_id}` | Statistiche affiliato |

### Autenticazione
```bash
curl -X GET \
  'https://example.com/wp-json/wcfm-affiliate-pro/v1/affiliates' \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'
```

---

## Hooks & Filtri

### Actions

```php
// Quando un affiliato viene approvato
do_action('wcfm_aff_pro_affiliate_approved', $affiliate_id);

// Quando viene creato un referral
do_action('wcfm_aff_pro_referral_created', $referral_id, $affiliate_id, $order_id);

// Quando una commissione viene approvata
do_action('wcfm_aff_pro_commission_approved', $commission_id);

// Quando un payout viene processato
do_action('wcfm_aff_pro_payout_processed', $payout_id, $affiliate_id, $amount);

// Quando un vendor diventa anche affiliato
do_action('wcfm_aff_pro_vendor_affiliate_activated', $user_id, $affiliate_id);
```

### Filtri

```php
// Modifica il tasso di commissione
add_filter('wcfm_aff_pro_commission_rate', function($rate, $affiliate_id, $product_id) {
    // Logica personalizzata
    return $rate;
}, 10, 3);

// Modifica la durata del cookie
add_filter('wcfm_aff_pro_cookie_duration', function($days) {
    return 60; // 60 giorni
});

// Escludi prodotti dal tracking
add_filter('wcfm_aff_pro_excluded_products', function($product_ids) {
    $product_ids[] = 123; // Escludi prodotto ID 123
    return $product_ids;
});
```

---

## Struttura Database

### Tabelle Create

| Tabella | Descrizione |
|---------|-------------|
| `{prefix}wcfm_aff_pro_affiliates` | Dati affiliati |
| `{prefix}wcfm_aff_pro_referrals` | Referral tracciati |
| `{prefix}wcfm_aff_pro_commissions` | Commissioni generate |
| `{prefix}wcfm_aff_pro_payouts` | Richieste payout |
| `{prefix}wcfm_aff_pro_payout_items` | Dettaglio payout |
| `{prefix}wcfm_aff_pro_clicks` | Click su link referral |
| `{prefix}wcfm_aff_pro_visits` | Visite tracciate |
| `{prefix}wcfm_aff_pro_coupons` | Coupon affiliati |
| `{prefix}wcfm_aff_pro_tiers` | Livelli commissione |
| `{prefix}wcfm_aff_pro_creatives` | Materiali promozionali |
| `{prefix}wcfm_aff_pro_mlm` | Struttura network MLM |

---

## Disinstallazione

### Disattivazione (Reversibile)
- I dati **NON vengono eliminati**
- Puoi riattivare il plugin in qualsiasi momento
- Tutte le statistiche e guadagni vengono mantenuti

### Eliminazione Completa
1. Vai su **Impostazioni → Avanzate**
2. Abilita "Elimina dati alla disinstallazione"
3. Salva
4. Elimina il plugin da WordPress

**ATTENZIONE**: L'eliminazione con l'opzione abilitata rimuoverà TUTTI i dati del plugin in modo irreversibile.

---

## FAQ

### Il plugin interferisce con WCFM Affiliate esistente?
No. WCFM Affiliate Pro usa prefissi completamente diversi e non tocca le tabelle o opzioni di altri plugin affiliate.

### Posso usare entrambi i sistemi contemporaneamente?
Sì, i due sistemi sono completamente indipendenti. Puoi avere affiliati in entrambi i sistemi.

### Come funziona il tracking?
Il plugin usa un cookie (`wcfm_aff_pro_ref`) e un parametro URL (`refpro`) per tracciare i referral. Esempio: `https://tuosito.it/?refpro=ABC123`

### Un venditore può essere anche affiliato?
Sì! Con la funzionalità Dual Role, un venditore può ottenere il suo codice affiliato con un click dalla dashboard.

### Come funziona il sistema MLM?
Ogni affiliato può avere un "parent" (upline). Quando genera una commissione, anche gli upline ricevono una percentuale configurabile per livello.

---

## Changelog

### 1.0.0 (2024-12-17)
- Release iniziale
- Sistema affiliazione completo
- Integrazione WCFM Marketplace
- Visualizzazione albero rete MLM
- Funzionalità spostamento affiliati tra reti
- Sistema dual-role affiliato/venditore
- REST API completa
- Dashboard admin con report

---

## Supporto

- **Documentazione**: [README.md](./README.md)
- **Issues**: [GitHub Issues](https://github.com/daniemi1977/ipv/issues)
- **Email**: support@aiedintorni.it

---

## Licenza

GPL v2 or later - [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

**Sviluppato da IPV Production** | [aiedintorni.it](https://aiedintorni.it)
