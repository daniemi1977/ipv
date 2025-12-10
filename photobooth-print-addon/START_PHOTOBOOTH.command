#!/bin/bash
# PhotoBooth Printer App - Easy Launcher

cd "$(dirname "$0")"

echo "📸 Avvio PhotoBooth Printer App..."
echo ""

# Check Python 3
if ! command -v python3 &> /dev/null; then
    osascript -e 'display alert "Python 3 Required" message "Installa Python 3 da python.org"'
    open "https://www.python.org/downloads/"
    exit 1
fi

# Install dependencies if needed
echo "Controllo dipendenze..."
pip3 install -q opencv-python pillow 2>&1 | grep -v "already satisfied" || true

echo ""
echo "✓ Pronto!"
echo ""

# Run app
python3 photobooth_app.py
