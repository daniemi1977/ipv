#!/bin/bash
# Setup LaunchAgent for automatic startup on macOS

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLIST_FILE="$HOME/Library/LaunchAgents/com.photobooth.autoprint.plist"

echo "🚀 Setting up auto-start on login..."
echo ""

# Create LaunchAgents directory if it doesn't exist
mkdir -p "$HOME/Library/LaunchAgents"

# Create plist file
cat > "$PLIST_FILE" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.photobooth.autoprint</string>

    <key>ProgramArguments</key>
    <array>
        <string>/usr/bin/python3</string>
        <string>$SCRIPT_DIR/photobooth_printer.py</string>
        <string>start</string>
    </array>

    <key>RunAtLoad</key>
    <true/>

    <key>KeepAlive</key>
    <true/>

    <key>StandardOutPath</key>
    <string>$HOME/Library/Logs/photobooth-printer-stdout.log</string>

    <key>StandardErrorPath</key>
    <string>$HOME/Library/Logs/photobooth-printer-stderr.log</string>

    <key>WorkingDirectory</key>
    <string>$SCRIPT_DIR</string>
</dict>
</plist>
EOF

echo "✓ LaunchAgent plist created: $PLIST_FILE"

# Load the LaunchAgent
launchctl unload "$PLIST_FILE" 2>/dev/null
launchctl load "$PLIST_FILE"

if [ $? -eq 0 ]; then
    echo "✓ LaunchAgent loaded successfully"
    echo ""
    echo "=========================================="
    echo "✅ Auto-start setup complete!"
    echo ""
    echo "The Photo Booth printer will now start automatically on login."
    echo ""
    echo "To disable auto-start:"
    echo "  launchctl unload $PLIST_FILE"
    echo ""
    echo "To enable again:"
    echo "  launchctl load $PLIST_FILE"
    echo ""
else
    echo "❌ Error loading LaunchAgent"
    exit 1
fi
