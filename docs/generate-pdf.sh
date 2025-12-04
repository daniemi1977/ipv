#!/bin/bash
# Script per generare il PDF del manuale utente IPV Production System Pro

echo "🚀 Generazione PDF in corso..."
echo ""

# Verifica se pandoc è installato
if ! command -v pandoc &> /dev/null; then
    echo "❌ Errore: Pandoc non è installato"
    echo ""
    echo "Per installare Pandoc:"
    echo ""
    echo "Ubuntu/Debian:"
    echo "  sudo apt-get install pandoc texlive-xetex texlive-fonts-recommended"
    echo ""
    echo "macOS:"
    echo "  brew install pandoc"
    echo "  brew install --cask mactex"
    echo ""
    echo "Oppure usa uno dei metodi alternativi nel file README.md"
    exit 1
fi

# Directory del manuale
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# File input e output
INPUT="MANUALE-UTENTE.md"
OUTPUT="MANUALE-UTENTE.pdf"

# Verifica che il file input esista
if [ ! -f "$INPUT" ]; then
    echo "❌ Errore: File $INPUT non trovato"
    exit 1
fi

echo "📄 File sorgente: $INPUT"
echo "📑 File destinazione: $OUTPUT"
echo ""

# Genera il PDF
pandoc "$INPUT" \
    -o "$OUTPUT" \
    --pdf-engine=xelatex \
    --toc \
    --toc-depth=2 \
    --number-sections \
    -V geometry:margin=2cm \
    -V fontsize=11pt \
    -V documentclass=report \
    -V papersize=a4 \
    -V lang=it-IT \
    -V colorlinks=true \
    -V linkcolor=blue \
    -V urlcolor=blue \
    --highlight-style=tango \
    2>&1

# Verifica se la generazione è riuscita
if [ $? -eq 0 ] && [ -f "$OUTPUT" ]; then
    echo ""
    echo "✅ PDF generato con successo!"
    echo ""
    echo "📊 Dettagli file:"
    ls -lh "$OUTPUT" | awk '{print "   Dimensione: " $5}'
    echo "   Percorso: $SCRIPT_DIR/$OUTPUT"
    echo ""
    echo "🎉 Puoi aprire il PDF con:"
    echo "   xdg-open $OUTPUT    # Linux"
    echo "   open $OUTPUT         # macOS"
    echo "   start $OUTPUT        # Windows"
else
    echo ""
    echo "❌ Errore durante la generazione del PDF"
    echo ""
    echo "Prova uno dei metodi alternativi descritti in README.md:"
    echo "  - Markdown to PDF online"
    echo "  - Visual Studio Code + Markdown PDF extension"
    echo "  - Typora"
    echo "  - Google Docs"
    exit 1
fi
