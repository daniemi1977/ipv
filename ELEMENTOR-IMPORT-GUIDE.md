# 🎨 Guida Import Pagina Pricing in Elementor + WoodMart

**File**: `elementor-pricing-template.json`
**Tema**: WoodMart
**Tempo**: 5 minuti

---

## 📥 Metodo 1: Import Template JSON (Rapido)

### Passo 1: Import Template

```bash
# WordPress Admin
Elementor → My Templates → Import Templates

# Seleziona file
elementor-pricing-template.json

# Clicca "Import Now"
```

### Passo 2: Crea Pagina

```bash
# Pagine → Aggiungi nuova
Titolo: Prezzi
URL slug: prezzi

# Clicca "Modifica con Elementor"

# Aggiungi template:
📂 Icon (in basso a sinistra)
→ My Templates
→ "IPV Pro - Pricing"
→ Insert

# Pubblica
```

### Passo 3: Configura Link Prodotti

```bash
# In Elementor editor:

# Piano Free - Modifica button:
Trova widget "Button" piano Free
→ Link: /carrello/?add-to-cart=[ID_PRODOTTO_FREE]

# Piano Basic:
→ Link: /carrello/?add-to-cart=[ID_PRODOTTO_BASIC]

# Piano Pro:
→ Link: /carrello/?add-to-cart=[ID_PRODOTTO_PRO]

# Piano Premium:
→ Link: /contatti/ (o ID prodotto)

# Salva → Update
```

---

## 🎨 Metodo 2: Crea da Zero con WoodMart (Personalizzato)

### Struttura Consigliata

```
SEZIONE 1: Hero Header
├─ Heading: "🚀 Scegli il Tuo Piano"
├─ Text: Sottotitolo
└─ Badge: "Pagamento sicuro"

SEZIONE 2: Pricing Cards (4 colonne)
├─ Colonna 1: Free Plan
├─ Colonna 2: Basic Plan
├─ Colonna 3: Pro Plan (⭐ badge)
└─ Colonna 4: Premium Plan

SEZIONE 3: Tabella Comparativa
└─ WoodMart Table widget

SEZIONE 4: FAQ Accordion
└─ WoodMart Accordion widget

SEZIONE 5: CTA Finale
├─ Heading
├─ Button
└─ Subtext
```

---

## 🛠️ Widget WoodMart Consigliati

### Widget 1: WoodMart Pricing Tables

```bash
# Aggiungi widget
Cerca: "Pricing Tables" (WoodMart)

# Configurazione Piano FREE:
Title: Free
Subtitle: Perfetto per testare
Price: 0
Currency: €
Features:
  - 10 video/mese
  - Trascrizioni AI
  - Descrizioni SEO
  - 1 sito
  - Support email
Button text: Inizia Gratis
Button link: /carrello/?add-to-cart=XXX

# Style:
Background: Linear gradient #667eea → #764ba2
Border radius: 20px
Shadow: Yes
```

### Widget 2: WoodMart Info Box (per Hero)

```bash
# Widget: Info Box
Title: 🚀 Scegli il Tuo Piano
Subtitle: Automatizza la creazione di contenuti YouTube con AI
Style: Center aligned
Background: Gradient
Text color: White
```

### Widget 3: WoodMart Accordion (per FAQ)

```bash
# Widget: Accordion
Style: Boxed

Items:
1. Title: Cosa succede se finisco i crediti?
   Content: I crediti si resettano automaticamente...

2. Title: Posso cancellare in qualsiasi momento?
   Content: Sì! Nessun vincolo...

3. Title: Le API keys sono al sicuro?
   Content: Assolutamente! Tutte le API keys...

[... altre FAQ]
```

---

## 🎨 Design Tips per WoodMart

### Colori Brand

```css
/* Primary gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Piano Free */
--color-free: #667eea;

/* Piano Basic */
--color-basic: #f5576c;

/* Piano Pro */
--color-pro: #4facfe;

/* Piano Premium */
--color-premium: #43e97b;
```

### Spacing

```
Section padding: 80px top/bottom
Container width: 1200px
Colonne gap: 30px
Card padding: 40px
Card border-radius: 20px
```

### Typography

```
H1 (Hero): 56px, weight 800
H2 (Section): 42px, weight 700
H3 (Plan name): 32px, weight 700
Body: 16px, line-height 1.6
Button: 18px, weight 600
```

---

## 📱 Responsive Settings

### Mobile (< 768px)

```bash
# Elementor Mobile Settings:

Hero heading: 36px (vs 56px desktop)
Section padding: 40px (vs 80px desktop)
Pricing cards: Stack (1 colonna)
Button: Full width
```

### Tablet (768px - 1024px)

```bash
Pricing cards: 2 colonne
Hero heading: 44px
Section padding: 60px
```

---

## ✨ Animazioni Elementor

### Entrance Animations

```bash
# Hero section
Animation: Fade In Up
Duration: 600ms
Delay: 0ms

# Pricing cards
Card 1 (Free): Fade In, delay 0ms
Card 2 (Basic): Fade In, delay 100ms
Card 3 (Pro): Fade In, delay 200ms
Card 4 (Premium): Fade In, delay 300ms
```

### Hover Effects

```bash
# Pricing cards
Hover: translateY(-10px)
Transition: 0.3s ease
Shadow increase on hover
```

---

## 🔧 CSS Custom (opzionale)

Aggiungi in **Elementor → Custom CSS**:

```css
/* Badge "PIÙ POPOLARE" */
.pricing-card.popular {
    position: relative;
    border: 3px solid #6366f1;
    transform: scale(1.05);
}

.pricing-card.popular::before {
    content: "PIÙ POPOLARE";
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    padding: 8px 30px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 1px;
}

/* Hover effect cards */
.pricing-card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

/* Gradients per icone piani */
.plan-icon.free {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.plan-icon.basic {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.plan-icon.pro {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.plan-icon.premium {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

/* Responsive mobile */
@media (max-width: 768px) {
    .pricing-card.popular {
        transform: scale(1);
    }

    .pricing-card {
        margin-bottom: 30px;
    }
}
```

---

## 🔗 Integrazione WooCommerce

### Link Diretti Prodotto

```bash
# Metodo 1: Add to cart diretto
/carrello/?add-to-cart=[ID]

# Metodo 2: Pagina prodotto
/prodotto/ipv-pro-free/
/prodotto/ipv-pro-basic/
/prodotto/ipv-pro-pro/
/prodotto/ipv-pro-premium/

# Metodo 3: Checkout diretto con prodotto
/checkout/?add-to-cart=[ID]
```

### Trova ID Prodotto

```bash
# WP-CLI
wp post list --post_type=product --format=table

# Output:
ID    post_title           post_status
123   IPV Pro - Free       publish
124   IPV Pro - Basic      publish
125   IPV Pro - Pro        publish
126   IPV Pro - Premium    publish
```

### Button personalizzato per WooCommerce

```html
<!-- Button con icona carrello -->
<a href="/carrello/?add-to-cart=123" class="elementor-button">
    <i class="fa fa-shopping-cart"></i>
    Aggiungi al Carrello
</a>
```

---

## 🎯 Ottimizzazioni Performance

### Lazy Load Images

```bash
# Elementor → Settings → Performance
✅ Lazy Load Background Images
✅ Improved Asset Loading
✅ Minify CSS/JS
```

### Font Optimization

```bash
# Usa font Google ottimizzati:
Primary: Inter (weights: 400, 600, 700, 800)
Display: Poppins (per headings)

# Elementor → Typography
Preload font weights utilizzati
```

### Cache WoodMart

```bash
# WoodMart → Performance
✅ Lazy loading
✅ Combined CSS
✅ Combined JS
✅ Critical CSS
```

---

## 📊 Tracking Conversioni

### Google Analytics Events

```javascript
<!-- Aggiungi in Custom Code Elementor -->
<script>
jQuery(document).ready(function($) {
    // Track click piani
    $('.pricing-button').on('click', function() {
        var plan = $(this).data('plan');
        gtag('event', 'click_pricing_plan', {
            'plan_name': plan,
            'value': $(this).data('price')
        });
    });
});
</script>
```

### Facebook Pixel

```javascript
<!-- Track aggiunte al carrello -->
<script>
jQuery(document).ready(function($) {
    $('.pricing-button').on('click', function() {
        fbq('track', 'AddToCart', {
            content_name: $(this).data('plan'),
            value: $(this).data('price'),
            currency: 'EUR'
        });
    });
});
</script>
```

---

## ✅ Checklist Finale

Prima di pubblicare:

- [ ] Tutti i link prodotti funzionano
- [ ] Prezzi corretti (€0, €9,99, €19,99, €39,99)
- [ ] Badge "PIÙ POPOLARE" visibile su Pro
- [ ] Responsive testato (mobile, tablet, desktop)
- [ ] Animazioni fluide
- [ ] Performance > 90 (GTmetrix/PageSpeed)
- [ ] FAQ complete e accurate
- [ ] CTA button funzionante
- [ ] Tracking analytics attivo
- [ ] SEO title e description impostati

---

## 🚀 Varianti Alternative

### Variante 1: Con Video Hero

```bash
# Sezione Hero
Background: Video loop
Overlay: Gradient dark 70%
Text: White
CTA button: Bright color
```

### Variante 2: Con Testimonials

```bash
# Aggiungi sezione dopo Pricing Cards:
Widget: WoodMart Testimonials
Layout: Carousel
Items: 3-5 recensioni clienti
```

### Variante 3: Con Countdown Timer

```bash
# Per promo Early Bird:
Widget: Countdown Timer
Text: "Sconto 50% scade tra:"
Style: Urgency colors (red/orange)
Position: Above pricing cards
```

---

## 📞 Support

**Problemi con import?**
- Verifica Elementor versione ≥ 3.18
- WoodMart versione ≥ 7.0
- PHP ≥ 7.4

**Template non appare?**
```bash
# Pulisci cache:
Elementor → Tools → Regenerate CSS
WoodMart → Purgecache
```

**Widget mancanti?**
```bash
# Attiva tutti i widget WoodMart:
WoodMart → Dashboard → Elements
→ Seleziona tutto
→ Save
```

---

**Pronto per pubblicare!** 🎉

Template professionale, responsive, ottimizzato per conversioni.
