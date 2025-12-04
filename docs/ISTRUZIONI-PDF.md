# Come Generare il PDF del Manuale

## 📄 File Disponibili

- **MANUALE-UTENTE.md** - Manuale in formato Markdown
- **MANUALE-UTENTE.html** - Manuale in formato HTML (pronto per la stampa)

## 🖨️ Metodo Raccomandato: Browser

Il modo più semplice per creare il PDF è usare il browser:

### Passo 1: Apri il File HTML

```bash
# Su Linux
xdg-open docs/MANUALE-UTENTE.html

# Su macOS
open docs/MANUALE-UTENTE.html

# Su Windows
start docs/MANUALE-UTENTE.html
```

### Passo 2: Stampa come PDF

1. Clicca sul pulsante **"🖨️ Stampa / Salva PDF"** in alto a destra

   OPPURE

2. Premi **Ctrl+P** (Windows/Linux) o **Cmd+P** (macOS)

3. Nella finestra di stampa:
   - Seleziona **"Salva come PDF"** o **"Microsoft Print to PDF"**
   - Imposta orientamento: **Verticale**
   - Imposta margini: **Predefiniti**
   - Clicca su **"Salva"**

4. Scegli dove salvare il PDF

✅ **Fatto!** Il PDF è pronto.

## 🌐 Metodo Alternativo: Online

Se preferisci usare uno strumento online:

### 1. HTML to PDF Online

1. Visita: https://www.sejda.com/html-to-pdf
2. Carica il file `MANUALE-UTENTE.html`
3. Clicca su "Convert HTML to PDF"
4. Scarica il PDF generato

### 2. CloudConvert

1. Visita: https://cloudconvert.com/html-to-pdf
2. Carica il file `MANUALE-UTENTE.html`
3. Clicca su "Convert"
4. Scarica il PDF

## 🔧 Metodo Avanzato: Pandoc (se installato)

Se hai Pandoc e LaTeX installati:

```bash
cd docs
./generate-pdf.sh
```

Questo genererà automaticamente `MANUALE-UTENTE.pdf`.

### Installare Pandoc

**Ubuntu/Debian:**
```bash
sudo apt-get install pandoc texlive-xetex texlive-fonts-recommended
```

**macOS:**
```bash
brew install pandoc
brew install --cask mactex
```

**Windows:**
Scarica da https://pandoc.org/installing.html

## 📋 Caratteristiche del PDF

Il manuale include:

✅ Indice automatico con link cliccabili
✅ Formattazione professionale
✅ Codice con syntax highlighting
✅ Tabelle formattate
✅ Impaginazione ottimizzata per A4
✅ 100+ pagine di documentazione completa

## 📊 Contenuto del Manuale

1. Introduzione e requisiti di sistema
2. Installazione guidata
3. Configurazione iniziale (API keys)
4. Funzionalità principali (Dashboard, Import, RSS)
5. **Gestione Multilingua (6 lingue supportate)**
6. Guida all'uso completa
7. Troubleshooting e FAQ
8. Supporto e risorse

## 🌍 Lingue Supportate nel Plugin

Il manuale documenta il supporto per:

- 🇮🇹 Italiano
- 🇩🇪 Tedesco
- 🇫🇷 Francese
- 🇪🇸 Spagnolo
- 🇵🇹 Portoghese
- 🇷🇺 Russo
- 🇬🇧 Inglese (predefinito)

---

**Versione Manuale:** 9.0.0
**Data:** Dicembre 2024
**Plugin:** IPV Production System Pro
