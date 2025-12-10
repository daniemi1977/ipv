#!/usr/bin/env python3
"""
Photo Booth Auto Print Add-on
Monitors Apple Photo Booth library and automatically prints new photos.
"""

import os
import sys
import time
import json
import subprocess
import logging
from pathlib import Path
from datetime import datetime
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(os.path.expanduser('~/Library/Logs/photobooth-printer.log')),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger('PhotoBoothPrinter')


class Config:
    """Configuration manager for Photo Booth Printer"""

    DEFAULT_CONFIG = {
        'enabled': True,
        'printer_name': None,  # None = default printer
        'copies': 1,
        'paper_size': 'Letter',  # Letter, A4, 4x6, etc.
        'orientation': 'portrait',  # portrait, landscape
        'color_mode': 'color',  # color, monochrome
        'photo_booth_path': '~/Pictures/Photo Booth Library/Pictures',
        'processed_files': [],
        'auto_rotate': True,
        'fit_to_page': True
    }

    def __init__(self, config_path='~/.photobooth-printer-config.json'):
        self.config_path = os.path.expanduser(config_path)
        self.config = self.load()

    def load(self):
        """Load configuration from file"""
        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, 'r') as f:
                    config = json.load(f)
                    # Merge with defaults for any missing keys
                    return {**self.DEFAULT_CONFIG, **config}
            except Exception as e:
                logger.error(f"Error loading config: {e}")
                return self.DEFAULT_CONFIG.copy()
        return self.DEFAULT_CONFIG.copy()

    def save(self):
        """Save configuration to file"""
        try:
            with open(self.config_path, 'w') as f:
                json.dump(self.config, f, indent=4)
            logger.info(f"Configuration saved to {self.config_path}")
        except Exception as e:
            logger.error(f"Error saving config: {e}")

    def get(self, key, default=None):
        """Get configuration value"""
        return self.config.get(key, default)

    def set(self, key, value):
        """Set configuration value"""
        self.config[key] = value
        self.save()


class PrinterManager:
    """Manages printing operations via CUPS"""

    @staticmethod
    def get_available_printers():
        """Get list of available printers"""
        try:
            result = subprocess.run(
                ['lpstat', '-p', '-d'],
                capture_output=True,
                text=True
            )
            printers = []
            default_printer = None

            for line in result.stdout.split('\n'):
                if line.startswith('printer'):
                    printer_name = line.split()[1]
                    printers.append(printer_name)
                elif line.startswith('system default destination:'):
                    default_printer = line.split(':')[1].strip()

            return printers, default_printer
        except Exception as e:
            logger.error(f"Error getting printers: {e}")
            return [], None

    @staticmethod
    def print_file(file_path, config):
        """Print a file with specified configuration"""
        if not os.path.exists(file_path):
            logger.error(f"File not found: {file_path}")
            return False

        try:
            cmd = ['lp']

            # Printer selection
            printer_name = config.get('printer_name')
            if printer_name:
                cmd.extend(['-d', printer_name])

            # Number of copies
            copies = config.get('copies', 1)
            cmd.extend(['-n', str(copies)])

            # Options
            options = []

            # Orientation
            orientation = config.get('orientation', 'portrait')
            if orientation == 'landscape':
                options.append('orientation-requested=4')
            else:
                options.append('orientation-requested=3')

            # Color mode
            color_mode = config.get('color_mode', 'color')
            if color_mode == 'monochrome':
                options.append('ColorModel=Gray')

            # Fit to page
            if config.get('fit_to_page', True):
                options.append('fit-to-page')

            # Paper size
            paper_size = config.get('paper_size', 'Letter')
            options.append(f'media={paper_size}')

            # Add options to command
            if options:
                cmd.extend(['-o', ','.join(options)])

            # Add file
            cmd.append(file_path)

            logger.info(f"Printing: {file_path}")
            logger.debug(f"Print command: {' '.join(cmd)}")

            result = subprocess.run(cmd, capture_output=True, text=True)

            if result.returncode == 0:
                logger.info(f"Successfully queued print job for: {file_path}")
                return True
            else:
                logger.error(f"Print failed: {result.stderr}")
                return False

        except Exception as e:
            logger.error(f"Error printing file: {e}")
            return False


class PhotoBoothHandler(FileSystemEventHandler):
    """Handles new files in Photo Booth library"""

    def __init__(self, config, printer_manager):
        self.config = config
        self.printer_manager = printer_manager
        self.processed_files = set(config.get('processed_files', []))
        self.processing_lock = set()

    def on_created(self, event):
        """Handle new file creation"""
        if event.is_directory:
            return

        file_path = event.src_path

        # Check if it's an image file
        if not self._is_image_file(file_path):
            return

        # Check if already processed
        if file_path in self.processed_files or file_path in self.processing_lock:
            return

        # Check if enabled
        if not self.config.get('enabled', True):
            logger.info(f"Printing disabled, skipping: {file_path}")
            return

        # Add to processing lock to prevent duplicate processing
        self.processing_lock.add(file_path)

        # Wait a bit for file to be fully written
        time.sleep(1)

        logger.info(f"New photo detected: {file_path}")

        # Print the file
        if self.printer_manager.print_file(file_path, self.config.config):
            # Mark as processed
            self.processed_files.add(file_path)
            self.config.set('processed_files', list(self.processed_files))
            logger.info(f"Photo printed successfully: {file_path}")
        else:
            logger.error(f"Failed to print photo: {file_path}")

        # Remove from processing lock
        self.processing_lock.discard(file_path)

    def _is_image_file(self, file_path):
        """Check if file is an image"""
        image_extensions = {'.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff'}
        return Path(file_path).suffix.lower() in image_extensions


class PhotoBoothPrinter:
    """Main application class"""

    def __init__(self):
        self.config = Config()
        self.printer_manager = PrinterManager()
        self.observer = None

    def setup(self):
        """Initial setup and configuration"""
        logger.info("Photo Booth Printer - Setup")
        print("\n=== Photo Booth Auto Print Setup ===\n")

        # Get available printers
        printers, default_printer = self.printer_manager.get_available_printers()

        if not printers:
            print("⚠️  No printers found. Please install a printer first.")
            return False

        print(f"Available printers:")
        for i, printer in enumerate(printers, 1):
            default_mark = " (default)" if printer == default_printer else ""
            print(f"  {i}. {printer}{default_mark}")

        print(f"  {len(printers) + 1}. Use system default printer")

        choice = input(f"\nSelect printer [1-{len(printers) + 1}]: ").strip()

        try:
            choice_num = int(choice)
            if 1 <= choice_num <= len(printers):
                selected_printer = printers[choice_num - 1]
                self.config.set('printer_name', selected_printer)
                print(f"✓ Printer set to: {selected_printer}")
            else:
                self.config.set('printer_name', None)
                print(f"✓ Using system default printer")
        except ValueError:
            print("Invalid choice, using default printer")
            self.config.set('printer_name', None)

        # Configure copies
        copies = input("\nNumber of copies [1]: ").strip()
        if copies:
            try:
                self.config.set('copies', int(copies))
            except ValueError:
                pass

        # Photo Booth path
        pb_path = os.path.expanduser(self.config.get('photo_booth_path'))
        print(f"\nPhoto Booth library path: {pb_path}")

        if not os.path.exists(pb_path):
            print(f"⚠️  Path does not exist. Creating...")
            try:
                os.makedirs(pb_path, exist_ok=True)
            except Exception as e:
                print(f"Error: Could not create path: {e}")
                return False

        print("\n✓ Setup complete!")
        print(f"\nConfiguration saved to: {self.config.config_path}")
        print("\nRun 'python3 photobooth_printer.py start' to start monitoring.")

        return True

    def start(self):
        """Start monitoring Photo Booth library"""
        if not self.config.get('enabled', True):
            print("Printing is disabled in config. Enable it first.")
            return

        pb_path = os.path.expanduser(self.config.get('photo_booth_path'))

        if not os.path.exists(pb_path):
            logger.error(f"Photo Booth path does not exist: {pb_path}")
            print(f"Error: Photo Booth path not found: {pb_path}")
            print("Run setup first: python3 photobooth_printer.py setup")
            return

        logger.info(f"Starting Photo Booth Printer monitor on: {pb_path}")
        print(f"\n📸 Monitoring Photo Booth library: {pb_path}")
        print("Waiting for new photos to print...")
        print("Press Ctrl+C to stop\n")

        event_handler = PhotoBoothHandler(self.config, self.printer_manager)
        self.observer = Observer()
        self.observer.schedule(event_handler, pb_path, recursive=False)
        self.observer.start()

        try:
            while True:
                time.sleep(1)
        except KeyboardInterrupt:
            logger.info("Stopping Photo Booth Printer...")
            print("\n\nStopping monitor...")
            self.observer.stop()

        self.observer.join()
        logger.info("Photo Booth Printer stopped")
        print("Monitor stopped.")

    def status(self):
        """Show current status"""
        print("\n=== Photo Booth Printer Status ===\n")

        enabled = self.config.get('enabled', True)
        print(f"Status: {'✓ Enabled' if enabled else '✗ Disabled'}")

        printer_name = self.config.get('printer_name')
        if printer_name:
            print(f"Printer: {printer_name}")
        else:
            print("Printer: System default")

        print(f"Copies: {self.config.get('copies', 1)}")
        print(f"Paper size: {self.config.get('paper_size', 'Letter')}")
        print(f"Orientation: {self.config.get('orientation', 'portrait')}")
        print(f"Color mode: {self.config.get('color_mode', 'color')}")

        pb_path = os.path.expanduser(self.config.get('photo_booth_path'))
        print(f"Monitoring: {pb_path}")

        processed_count = len(self.config.get('processed_files', []))
        print(f"Photos printed: {processed_count}")

        print(f"\nConfig file: {self.config.config_path}")

    def enable(self):
        """Enable printing"""
        self.config.set('enabled', True)
        print("✓ Printing enabled")

    def disable(self):
        """Disable printing"""
        self.config.set('enabled', False)
        print("✓ Printing disabled")


def main():
    """Main entry point"""
    app = PhotoBoothPrinter()

    if len(sys.argv) < 2:
        print("Photo Booth Auto Print Add-on")
        print("\nUsage:")
        print("  python3 photobooth_printer.py setup    - Initial configuration")
        print("  python3 photobooth_printer.py start    - Start monitoring")
        print("  python3 photobooth_printer.py status   - Show current status")
        print("  python3 photobooth_printer.py enable   - Enable printing")
        print("  python3 photobooth_printer.py disable  - Disable printing")
        sys.exit(1)

    command = sys.argv[1].lower()

    if command == 'setup':
        app.setup()
    elif command == 'start':
        app.start()
    elif command == 'status':
        app.status()
    elif command == 'enable':
        app.enable()
    elif command == 'disable':
        app.disable()
    else:
        print(f"Unknown command: {command}")
        sys.exit(1)


if __name__ == '__main__':
    main()
