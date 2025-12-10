#!/bin/bash
# Create DMG installer for PhotoBooth Printer

echo "📦 Creating PhotoBooth Printer Installer..."
echo ""

APP_NAME="PhotoBooth Printer"
DMG_NAME="PhotoBooth_Printer_Installer"
VOLUME_NAME="PhotoBooth Printer"

# Create temporary directory
TMP_DIR=$(mktemp -d)
echo "✓ Created temporary directory"

# Copy app to temp directory
cp -R "$APP_NAME.app" "$TMP_DIR/"
echo "✓ Copied application"

# Create Applications symlink
ln -s /Applications "$TMP_DIR/Applications"
echo "✓ Created Applications link"

# Create README
cat > "$TMP_DIR/README.txt" << 'EOF'
PhotoBooth Printer - Installation Instructions
==============================================

INSTALLATION:
1. Drag "PhotoBooth Printer.app" to the Applications folder
2. Open "PhotoBooth Printer" from Applications
3. Follow the setup wizard

USAGE:
- Open the app and click "Start" to begin monitoring
- Take photos with Photo Booth
- Photos will automatically print!

REQUIREMENTS:
- macOS 10.12 or later
- Python 3 (will prompt to install if needed)
- A configured printer

For support, visit: https://github.com/daniemi1977/ipv
EOF

echo "✓ Created README"

# Create DMG
echo "Creating DMG image..."

if command -v hdiutil &> /dev/null; then
    # Create DMG
    hdiutil create -volname "$VOLUME_NAME" -srcfolder "$TMP_DIR" -ov -format UDZO "$DMG_NAME.dmg"

    if [ $? -eq 0 ]; then
        echo ""
        echo "=========================================="
        echo "✅ SUCCESS!"
        echo ""
        echo "Installer created: $DMG_NAME.dmg"
        echo ""
        echo "Users can now:"
        echo "1. Double-click $DMG_NAME.dmg"
        echo "2. Drag PhotoBooth Printer to Applications"
        echo "3. Run it!"
        echo "=========================================="
    else
        echo "❌ Error creating DMG"
    fi
else
    echo "⚠️  hdiutil not available (not on macOS)"
    echo "Creating ZIP archive instead..."

    cd "$TMP_DIR"
    zip -r "../$DMG_NAME.zip" . -q
    cd - > /dev/null

    if [ $? -eq 0 ]; then
        echo ""
        echo "=========================================="
        echo "✅ SUCCESS!"
        echo ""
        echo "Installer created: $DMG_NAME.zip"
        echo ""
        echo "Users can now:"
        echo "1. Extract $DMG_NAME.zip"
        echo "2. Drag PhotoBooth Printer to Applications"
        echo "3. Run it!"
        echo "=========================================="
    fi
fi

# Cleanup
rm -rf "$TMP_DIR"
echo "✓ Cleaned up temporary files"
