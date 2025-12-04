# Documentazione IPV Production System Pro

Questa cartella contiene la documentazione completa del plugin.

## File Disponibili

- **MANUALE-UTENTE.md** - Manuale completo in formato Markdown (questo file)
- **MANUALE-UTENTE.pdf** - Versione PDF (da generare)

## Come Convertire in PDF

### Metodo 1: Pandoc + LaTeX (Professionale)

**Il più bello e professionale**

```bash
# 1. Installa Pandoc e LaTeX
# Ubuntu/Debian:
sudo apt-get install pandoc texlive-latex-base texlive-fonts-recommended texlive-latex-extra

# macOS:
brew install pandoc
brew install --cask mactex

# 2. Converti in PDF
cd docs/
pandoc MANUALE-UTENTE.md \
    -o MANUALE-UTENTE.pdf \
    --pdf-engine=xelatex \
    --toc \
    --toc-depth=2 \
    -V geometry:margin=2cm \
    -V fontsize=11pt \
    -V documentclass=report \
    -V papersize=a4 \
    -V lang=it-IT \
    --highlight-style=tango

# Output: MANUALE-UTENTE.pdf (con indice, formattazione professionale)
```

### Metodo 2: Pandoc (Semplice)

**Veloce, senza LaTeX**

```bash
# Installa solo Pandoc
sudo apt-get install pandoc wkhtmltopdf

# Converti
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf
```

### Metodo 3: Markdown to PDF Online

**Nessuna installazione necessaria**

1. Vai su uno di questi siti:
   - https://www.markdowntopdf.com/
   - https://md2pdf.netlify.app/
   - https://cloudconvert.com/md-to-pdf

2. Carica il file `MANUALE-UTENTE.md`
3. Scarica il PDF generato

### Metodo 4: Visual Studio Code

**Se usi VS Code**

1. Installa l'estensione: **Markdown PDF** di yzane
2. Apri `MANUALE-UTENTE.md`
3. Premi `Ctrl+Shift+P` (o `Cmd+Shift+P` su Mac)
4. Digita: `Markdown PDF: Export (pdf)`
5. Seleziona la destinazione

### Metodo 5: Typora (GUI)

**Editor Markdown con export integrato**

1. Scarica Typora da: https://typora.io/
2. Apri `MANUALE-UTENTE.md`
3. **File** → **Export** → **PDF**
4. Configura le opzioni (margini, font, etc.)
5. Clicca **Export**

### Metodo 6: Google Docs

**Usando Google Drive**

1. Vai su https://docs.google.com
2. **File** → **Importa** → Seleziona `MANUALE-UTENTE.md`
3. Una volta importato: **File** → **Scarica** → **PDF**

### Metodo 7: LibreOffice Writer

**Software gratuito**

1. Installa LibreOffice: https://www.libreoffice.org/
2. Apri Writer
3. **File** → **Apri** → Seleziona `MANUALE-UTENTE.md`
4. **File** → **Esporta come** → **PDF**

### Metodo 8: Node.js + markdown-pdf

**Per sviluppatori**

```bash
# Installa
npm install -g markdown-pdf

# Converti
markdown-pdf MANUALE-UTENTE.md
```

### Metodo 9: Python + markdown2pdf

```bash
# Installa
pip install markdown2pdf

# Converti
markdown2pdf MANUALE-UTENTE.md MANUALE-UTENTE.pdf
```

### Metodo 10: Docker (Isolato)

```bash
# Usa container Pandoc
docker run --rm -v $(pwd):/source \
    pandoc/latex:latest \
    MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf
```

## Risultato

Il file PDF avrà:
- ✅ Indice navigabile (TOC)
- ✅ Formattazione professionale
- ✅ Sintassi evidenziata per codice
- ✅ Tabelle ben formattate
- ✅ Collegamenti ipertestuali funzionanti
- ✅ ~80-100 pagine

## Personalizzazione PDF

### Con Pandoc (Avanzato)

Crea un file `template.yaml`:

```yaml
---
title: "IPV Production System Pro"
subtitle: "Manuale Utente Completo"
author: "IPV Team"
date: "Dicembre 2024"
version: "9.0.0"
lang: "it-IT"
toc: true
toc-depth: 3
numbersections: true
geometry: margin=2.5cm
fontsize: 11pt
papersize: a4
mainfont: "DejaVu Sans"
monofont: "DejaVu Sans Mono"
colorlinks: true
linkcolor: blue
urlcolor: blue
---
```

Poi converti:
```bash
pandoc MANUALE-UTENTE.md \
    -o MANUALE-UTENTE.pdf \
    --metadata-file=template.yaml \
    --pdf-engine=xelatex
```

### Aggiungi Copertina

Crea `cover.md`:
```markdown
---
title: "IPV Production System Pro"
subtitle: "Manuale Utente Completo"
author: "IPV Team"
date: "v9.0.0 - Dicembre 2024"
---

\newpage
```

Combina i file:
```bash
pandoc cover.md MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf
```

## Opzioni di Styling Pandoc

```bash
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf \
    --toc \                          # Indice
    --toc-depth=3 \                  # Profondità indice (1-3)
    --number-sections \              # Numera sezioni
    -V geometry:margin=2cm \         # Margini
    -V fontsize=11pt \               # Dimensione font
    -V mainfont="Arial" \            # Font principale
    -V monofont="Courier" \          # Font codice
    -V colorlinks=true \             # Link colorati
    -V linkcolor=blue \              # Colore link
    -V urlcolor=blue \               # Colore URL
    -V toccolor=black \              # Colore TOC
    --highlight-style=tango \        # Stile syntax highlight
    --pdf-engine=xelatex             # Engine PDF
```

## Stili Syntax Highlighting Disponibili

```bash
# Vedi tutti gli stili disponibili
pandoc --list-highlight-styles

# Stili popolari:
# - tango (colorato, leggibile)
# - pygments (classico)
# - kate (professionale)
# - monochrome (bianco/nero)
# - breezedark (scuro)
# - espresso (scuro)
# - zenburn (scuro)

# Esempio con stile diverso
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf \
    --highlight-style=kate
```

## Troubleshooting

### Error: "pandoc: command not found"

```bash
# Installa Pandoc
curl -LO https://github.com/jgm/pandoc/releases/download/3.1.9/pandoc-3.1.9-linux-amd64.tar.gz
tar xvzf pandoc-3.1.9-linux-amd64.tar.gz --strip-components 1 -C /usr/local/
```

### Error: "pdflatex not found"

```bash
# Installa LaTeX
sudo apt-get install texlive-xetex texlive-fonts-recommended
```

### Error: "Failed to produce PDF"

Prova con un engine diverso:
```bash
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf --pdf-engine=wkhtmltopdf
```

### Font non trovati

```bash
# Lista font disponibili
fc-list : family | sort | uniq

# Usa font di sistema
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf \
    -V mainfont="Liberation Sans"
```

## Qualità PDF

### Alta Qualità (per stampa)

```bash
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf \
    --pdf-engine=xelatex \
    -V geometry:margin=2.5cm \
    -V fontsize=12pt \
    -V papersize=a4 \
    -V documentclass=book \
    --toc \
    --number-sections \
    -V colorlinks=false  # Link in nero per stampa
```

### Web/Email (dimensioni ridotte)

```bash
pandoc MANUALE-UTENTE.md -o MANUALE-UTENTE.pdf \
    --pdf-engine=xelatex \
    -V geometry:margin=1.5cm \
    -V fontsize=10pt \
    -V colorlinks=true
```

## Automazione

### Script Bash

Crea `generate-pdf.sh`:
```bash
#!/bin/bash
pandoc MANUALE-UTENTE.md \
    -o MANUALE-UTENTE.pdf \
    --pdf-engine=xelatex \
    --toc \
    --toc-depth=2 \
    -V geometry:margin=2cm \
    -V fontsize=11pt \
    -V lang=it-IT \
    --highlight-style=tango

echo "✅ PDF generato: MANUALE-UTENTE.pdf"
```

Esegui:
```bash
chmod +x generate-pdf.sh
./generate-pdf.sh
```

### Makefile

Crea `Makefile`:
```makefile
.PHONY: pdf clean

pdf:
	pandoc MANUALE-UTENTE.md \
		-o MANUALE-UTENTE.pdf \
		--pdf-engine=xelatex \
		--toc \
		-V geometry:margin=2cm \
		-V fontsize=11pt

clean:
	rm -f MANUALE-UTENTE.pdf

watch:
	while true; do \
		inotifywait -e modify MANUALE-UTENTE.md; \
		make pdf; \
	done
```

Usa:
```bash
make pdf    # Genera PDF
make clean  # Rimuovi PDF
make watch  # Rigenera ad ogni modifica
```

## Risultati di Esempio

### Dimensioni File
- Markdown: ~150 KB
- PDF (base): ~500 KB
- PDF (immagini): ~2-5 MB

### Pagine
- Circa 80-100 pagine formato A4
- Font 11pt
- Margini 2cm

## Supporto

Per problemi con la conversione:
- Repository: https://github.com/daniemi1977/ipv
- Issues: https://github.com/daniemi1977/ipv/issues/new

---

**Raccomandazione**: Per il miglior risultato, usa **Pandoc + XeLaTeX** (Metodo 1)
