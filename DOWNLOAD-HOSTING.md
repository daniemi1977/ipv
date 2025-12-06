# 📥 IPV Pro v10.0.0 - Download Hosting Guide

**File creato**: `download.html`
**Plugin**: `ipv-pro-vendor-v1.0.0.zip` (41KB) + `ipv-production-system-pro-v10.0.0.zip` (253KB)

---

## 🚀 Opzioni per Hostare la Pagina Download

### Opzione 1: GitHub Pages (GRATIS, più semplice)

```bash
# 1. Assicurati che i file siano committed
git add download.html *.zip
git commit -m "Add download page and plugin ZIPs"
git push origin claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo

# 2. Vai su GitHub
# Repository → Settings → Pages
# Source: Deploy from branch
# Branch: claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo
# Folder: / (root)
# Save

# 3. Attendi 1-2 minuti, poi visita:
https://daniemi1977.github.io/ipv/download.html
```

**Link download automatici**:
- `https://daniemi1977.github.io/ipv/ipv-pro-vendor-v1.0.0.zip`
- `https://daniemi1977.github.io/ipv/ipv-production-system-pro-v10.0.0.zip`

---

### Opzione 2: Netlify Drop (GRATIS, zero configurazione)

```bash
# 1. Crea cartella con file necessari
mkdir ipv-download
cp download.html ipv-download/index.html
cp *.zip ipv-download/

# 2. Vai su: https://app.netlify.com/drop

# 3. Trascina cartella ipv-download/

# 4. Ottieni URL tipo:
https://ipv-pro-v10.netlify.app
```

**Vantaggi**:
- Deploy istantaneo (drag & drop)
- HTTPS automatico
- CDN globale
- Custom domain gratis

---

### Opzione 3: Vercel (GRATIS, per progetti Git)

```bash
# 1. Installa Vercel CLI
npm i -g vercel

# 2. Deploy da repository
vercel

# Follow prompts:
# - Link to existing project? No
# - Project name: ipv-pro-downloads
# - Directory: ./
# - Deploy? Yes

# 3. Ottieni URL:
https://ipv-pro-downloads.vercel.app/download.html
```

---

### Opzione 4: WordPress su bissolomarket.com (integrato)

```bash
# 1. Carica file via FTP/cPanel
/public_html/download/
├── index.html (copia di download.html)
├── ipv-pro-vendor-v1.0.0.zip
└── ipv-production-system-pro-v10.0.0.zip

# 2. Accedi:
https://bissolomarket.com/download/

# 3. (Opzionale) Crea pagina WordPress:
Pagine → Aggiungi nuova
Titolo: Download IPV Pro
Template: Full Width
Editor HTML: incolla contenuto download.html
Pubblica
URL: https://bissolomarket.com/downloads/
```

---

### Opzione 5: Cloudflare Pages (GRATIS, super veloce)

```bash
# 1. Vai su: https://pages.cloudflare.com

# 2. Connetti repository GitHub:
# - Connect to Git
# - Seleziona repository: daniemi1977/ipv
# - Branch: claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo

# 3. Configurazione:
# - Framework preset: None
# - Build command: (lascia vuoto)
# - Build output directory: /
# - Deploy

# 4. URL:
https://ipv.pages.dev/download.html
```

---

## 🔗 Link Diretti ai File (GitHub Raw)

Se hai solo bisogno dei link diretti ai file ZIP (senza pagina download):

```
# VENDOR Plugin
https://raw.githubusercontent.com/daniemi1977/ipv/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-pro-vendor-v1.0.0.zip

# CLIENT Plugin
https://raw.githubusercontent.com/daniemi1977/ipv/claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo/ipv-production-system-pro-v10.0.0.zip
```

**Nota**: GitHub Raw ha limiti di banda. Per produzioni, usa GitHub Releases (vedi sotto).

---

## 🎯 Opzione CONSIGLIATA: GitHub Releases

**Migliore per distribuire software**:

```bash
# 1. Crea release su GitHub
# Vai su: https://github.com/daniemi1977/ipv/releases/new

# 2. Compila:
Tag version: v10.0.0
Release title: IPV Pro v10.0.0 - Cloud Edition 🚀
Description:
"""
## 🎉 IPV Pro v10.0.0 - Cloud Edition

Sistema SaaS completo per vendere licenze IPV Pro via WooCommerce.

### 📦 Download

- **Plugin Vendor** (41KB): Per server bissolomarket.com
- **Plugin Client** (253KB): Per distribuzione ai clienti

### ✨ Novità v10.0.0

- API Gateway per proteggere API keys
- License Manager con WooCommerce integration
- Credits system con reset mensile
- Remote Updates automatici
- 4 piani pricing (Free, Basic, Pro, Premium)

### 📚 Documentazione

Vedi file:
- DEPLOY-GUIDE-FINAL.md
- QUICK-START.md
- PRICING-PLANS.md

### 💰 Pricing

- Free: €0 - 10 video/mese
- Basic: €9,99 - 100 video/mese
- Pro: €19,99 - 200 video/mese
- Premium: €39,99 - 500 video/mese
"""

# 3. Trascina i file ZIP nella sezione "Attach binaries":
# - ipv-pro-vendor-v1.0.0.zip
# - ipv-production-system-pro-v10.0.0.zip

# 4. Pubblica Release

# 5. Link download permanenti:
https://github.com/daniemi1977/ipv/releases/download/v10.0.0/ipv-pro-vendor-v1.0.0.zip
https://github.com/daniemi1977/ipv/releases/download/v10.0.0/ipv-production-system-pro-v10.0.0.zip
```

**Vantaggi GitHub Releases**:
- ✅ Link permanenti
- ✅ Versioning automatico
- ✅ Download illimitati
- ✅ Changelog integrato
- ✅ Statistiche download
- ✅ API per remote updates

---

## 📊 Tabella Comparativa

| Opzione | Difficoltà | Costo | Velocità | Custom Domain | Consigliata Per |
|---------|-----------|-------|----------|---------------|-----------------|
| **GitHub Pages** | Facile | Gratis | Media | Sì | Pagine statiche |
| **GitHub Releases** | Facile | Gratis | Alta | No | Software releases ⭐ |
| **Netlify Drop** | Facilissimo | Gratis | Alta | Sì | Deploy rapidi |
| **Vercel** | Media | Gratis | Alta | Sì | Progetti Git |
| **Cloudflare Pages** | Media | Gratis | Altissima | Sì | Performance max |
| **WordPress** | Facile | €0* | Media | Sì | Integrazione esistente |

*Se hai già hosting WordPress

---

## 🎨 Personalizzazione Pagina Download

### Cambia colori brand

Modifica `download.html` linea 15:

```css
/* Da purple gradient a tuo colore */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Esempio: Blue gradient */
background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);

/* Esempio: Green gradient */
background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
```

### Aggiungi Google Analytics

Prima di `</head>` in `download.html`:

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### Traccia download

Prima di `</body>` in `download.html`:

```html
<script>
// Track download clicks
document.querySelectorAll('.download-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const plugin = this.textContent.includes('Vendor') ? 'vendor' : 'client';

        // Google Analytics event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'download', {
                'plugin_type': plugin,
                'version': '10.0.0'
            });
        }

        // Console log
        console.log('Download:', plugin, 'plugin');
    });
});
</script>
```

---

## 🔒 Download con Password (opzionale)

Se vuoi proteggere i download con password:

```html
<!-- Aggiungi prima di </body> -->
<script>
function checkPassword() {
    const password = prompt('Inserisci password per scaricare:');
    if (password !== 'IPV2025Pro') {
        alert('Password errata!');
        return false;
    }
    return true;
}

// Applica a tutti i download button
document.querySelectorAll('.download-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!checkPassword()) {
            e.preventDefault();
        }
    });
});
</script>
```

---

## 🚀 Deploy Automatico (GitHub Actions)

Crea `.github/workflows/deploy.yml`:

```yaml
name: Deploy Download Page

on:
  push:
    branches: [ claude/ipv-production-plugin-dev-01LkCUv348tpRLhXPtqTpGqo ]
    paths:
      - 'download.html'
      - '*.zip'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Deploy to Netlify
        uses: netlify/actions/cli@master
        env:
          NETLIFY_AUTH_TOKEN: ${{ secrets.NETLIFY_AUTH_TOKEN }}
          NETLIFY_SITE_ID: ${{ secrets.NETLIFY_SITE_ID }}
        with:
          args: deploy --prod --dir=.
```

---

## ✅ Checklist Finale

Prima di pubblicare i link:

- [ ] File ZIP funzionanti (testa download locale)
- [ ] `download.html` funziona in locale (`open download.html`)
- [ ] Link corretti ai file ZIP
- [ ] Versioni corrette (1.0.0 vendor, 10.0.0 client)
- [ ] Documentazione caricata nel repository
- [ ] README.md aggiornato con link download
- [ ] (Opzionale) Google Analytics configurato
- [ ] (Opzionale) Custom domain impostato

---

## 📞 Problemi Comuni

### Download non parte

**Problema**: Click su button ma niente succede

**Soluzione**: Controlla percorsi file ZIP in `download.html`:

```html
<!-- Deve essere relativo alla posizione di download.html -->
<a href="ipv-pro-vendor-v1.0.0.zip" class="download-btn" download>

<!-- Se ZIP in sottocartella: -->
<a href="plugins/ipv-pro-vendor-v1.0.0.zip" class="download-btn" download>
```

### File ZIP corrotto

**Problema**: Dopo download, ZIP non si apre

**Soluzione**: GitHub Raw può corrompere file binari. Usa:
- GitHub Releases (consigliato)
- GitHub LFS (Large File Storage)
- Hosting esterno (Netlify, Vercel)

### 404 su GitHub Pages

**Problema**: Pagina non trovata

**Soluzione**:
1. Verifica Settings → Pages sia abilitato
2. Branch corretta selezionata
3. File nella root del repository (non in sottocartella)
4. Aspetta 2-5 minuti dopo deploy

---

**Ready to Deploy!** 🚀

Scegli una delle opzioni sopra e condividi i link con i tuoi clienti.

**Consiglio**: Usa **GitHub Releases** per massima compatibilità e statistiche download.
