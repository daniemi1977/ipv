# 📸 Photo Booth Auto Print Add-on

Automatically print photos as they are captured in Apple Photo Booth on macOS.

## Features

- 🔍 **Automatic Monitoring**: Watches Photo Booth library for new photos
- 🖨️ **Instant Printing**: Automatically sends photos to your printer
- ⚙️ **Configurable**: Choose printer, paper size, copies, orientation, and more
- 🚀 **Auto-start**: Optional launch on login using macOS LaunchAgent
- 📊 **Logging**: Keep track of all printed photos
- 🎯 **Simple**: Easy command-line interface

## Requirements

- macOS 10.12 or later
- Python 3.7 or later
- Apple Photo Booth app
- A configured printer (CUPS)

## Installation

### Quick Install

```bash
# Clone or download this repository
cd photobooth-print-addon

# Run installation script
chmod +x install.sh
./install.sh
```

### Manual Install

```bash
# Install dependencies
pip3 install -r requirements.txt

# Make script executable
chmod +x photobooth_printer.py

# Run setup
python3 photobooth_printer.py setup
```

## Usage

### First Time Setup

```bash
python3 photobooth_printer.py setup
```

This will guide you through:
- Selecting your printer
- Configuring print options
- Setting the Photo Booth library path

### Start Monitoring

```bash
python3 photobooth_printer.py start
```

The app will now monitor your Photo Booth library and automatically print new photos.

### Check Status

```bash
python3 photobooth_printer.py status
```

Shows current configuration and statistics.

### Enable/Disable Printing

```bash
# Temporarily disable printing
python3 photobooth_printer.py disable

# Re-enable printing
python3 photobooth_printer.py enable
```

### Auto-start on Login (Optional)

To have the printer start automatically when you log in:

```bash
chmod +x setup_autostart.sh
./setup_autostart.sh
```

To disable auto-start:

```bash
launchctl unload ~/Library/LaunchAgents/com.photobooth.autoprint.plist
```

## Configuration

Configuration is stored in `~/.photobooth-printer-config.json`

You can manually edit this file with the following options:

```json
{
    "enabled": true,
    "printer_name": "HP_LaserJet",
    "copies": 1,
    "paper_size": "Letter",
    "orientation": "portrait",
    "color_mode": "color",
    "photo_booth_path": "~/Pictures/Photo Booth Library/Pictures",
    "auto_rotate": true,
    "fit_to_page": true
}
```

### Configuration Options

- **enabled**: `true` or `false` - Enable/disable auto-printing
- **printer_name**: String - Name of printer (null = system default)
- **copies**: Number - Number of copies to print (default: 1)
- **paper_size**: String - Paper size: `Letter`, `A4`, `4x6`, `5x7`, etc.
- **orientation**: String - `portrait` or `landscape`
- **color_mode**: String - `color` or `monochrome`
- **photo_booth_path**: String - Path to Photo Booth pictures folder
- **auto_rotate**: Boolean - Automatically rotate images
- **fit_to_page**: Boolean - Scale image to fit page

## Common Paper Sizes

- `Letter` - US Letter (8.5" x 11")
- `A4` - International A4 (210mm x 297mm)
- `4x6` - 4" x 6" photo paper
- `5x7` - 5" x 7" photo paper
- `8x10` - 8" x 10" photo paper

## Troubleshooting

### No printers found

Make sure you have at least one printer configured in macOS System Preferences.

```bash
# Check available printers
lpstat -p -d
```

### Photo Booth path not found

The default path is `~/Pictures/Photo Booth Library/Pictures`. If Photo Booth stores photos elsewhere, update the config:

```bash
# Edit config file
nano ~/.photobooth-printer-config.json
```

### Printing not working

1. Check printer status: `lpstat -p`
2. Check CUPS status: `lpstat -r`
3. View logs: `tail -f ~/Library/Logs/photobooth-printer.log`
4. Test manual print: `lp -d YourPrinter /path/to/test/image.jpg`

### Permission issues

Make sure the script has permission to access the Photo Booth library and printer:

```bash
# Give Terminal/iTerm full disk access in:
# System Preferences > Security & Privacy > Privacy > Full Disk Access
```

## Logs

Logs are stored in:
- Main log: `~/Library/Logs/photobooth-printer.log`
- LaunchAgent stdout: `~/Library/Logs/photobooth-printer-stdout.log`
- LaunchAgent stderr: `~/Library/Logs/photobooth-printer-stderr.log`

View live logs:

```bash
tail -f ~/Library/Logs/photobooth-printer.log
```

## Uninstall

```bash
# Stop and remove LaunchAgent
launchctl unload ~/Library/LaunchAgents/com.photobooth.autoprint.plist
rm ~/Library/LaunchAgents/com.photobooth.autoprint.plist

# Remove config
rm ~/.photobooth-printer-config.json

# Remove logs
rm ~/Library/Logs/photobooth-printer*.log

# Remove the application folder
rm -rf photobooth-print-addon
```

## Use Cases

- **Photo booths at events**: Automatically print photos for guests
- **Birthday parties**: Instant photo printing for kids and adults
- **Office fun**: Quick photo printing for team building
- **Family gatherings**: Create instant memories
- **Professional events**: Corporate headshots, ID photos, etc.

## Technical Details

- Uses `watchdog` library for file system monitoring
- Integrates with macOS CUPS printing system via `lp` command
- Monitors Photo Booth library for new `.jpg`, `.png` files
- Maintains history of printed files to avoid duplicates
- Supports all CUPS-compatible printers

## License

MIT License - See LICENSE file for details

## Support

For issues, questions, or contributions, please open an issue on GitHub.

## Author

Created for easy Photo Booth printing automation on macOS.

---

**Enjoy instant photo printing! 📸🖨️**
