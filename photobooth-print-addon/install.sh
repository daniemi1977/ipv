#!/bin/bash
# Installation script for Photo Booth Auto Print Add-on

echo "🖨️  Photo Booth Auto Print - Installation"
echo "=========================================="
echo ""

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Error: Python 3 is not installed"
    echo "Please install Python 3 first: https://www.python.org/downloads/"
    exit 1
fi

echo "✓ Python 3 found: $(python3 --version)"

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "❌ Error: pip3 is not installed"
    echo "Please install pip3 first"
    exit 1
fi

echo "✓ pip3 found"
echo ""

# Install dependencies
echo "📦 Installing dependencies..."
pip3 install -r requirements.txt

if [ $? -ne 0 ]; then
    echo "❌ Error installing dependencies"
    exit 1
fi

echo "✓ Dependencies installed"
echo ""

# Make the script executable
chmod +x photobooth_printer.py

echo "✓ Script made executable"
echo ""

# Run setup
echo "🔧 Running setup..."
python3 photobooth_printer.py setup

echo ""
echo "=========================================="
echo "✅ Installation complete!"
echo ""
echo "To start monitoring:"
echo "  python3 photobooth_printer.py start"
echo ""
echo "To run automatically on login (optional):"
echo "  ./setup_autostart.sh"
echo ""
