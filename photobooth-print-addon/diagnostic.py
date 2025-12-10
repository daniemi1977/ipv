#!/usr/bin/env python3
"""
PhotoBooth Printer - Diagnostic Tool
Tests the configuration and helps troubleshoot issues
"""

import os
import sys
import json
import subprocess
from pathlib import Path

print("=" * 60)
print("📸 PhotoBooth Printer - DIAGNOSTIC TOOL")
print("=" * 60)
print()

# Colors for terminal
GREEN = '\033[92m'
RED = '\033[91m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
RESET = '\033[0m'

def check(message):
    print(f"{BLUE}[CHECK]{RESET} {message}...", end=" ")
    return True

def success(message="OK"):
    print(f"{GREEN}✓ {message}{RESET}")

def error(message):
    print(f"{RED}✗ {message}{RESET}")

def warning(message):
    print(f"{YELLOW}⚠ {message}{RESET}")

# 1. Check Python version
check("Python version")
python_version = sys.version_info
if python_version.major >= 3 and python_version.minor >= 7:
    success(f"Python {python_version.major}.{python_version.minor}.{python_version.micro}")
else:
    error(f"Python {python_version.major}.{python_version.minor} (need 3.7+)")
    sys.exit(1)

# 2. Check watchdog library
check("watchdog library")
try:
    import watchdog
    success(f"Installed (version {watchdog.__version__})")
except ImportError:
    error("NOT installed")
    print(f"\n{YELLOW}Fix:{RESET} Run: pip3 install watchdog\n")
    sys.exit(1)

# 3. Check config file
check("Configuration file")
config_path = os.path.expanduser("~/.photobooth-printer-config.json")
if os.path.exists(config_path):
    success("Found")
    try:
        with open(config_path, 'r') as f:
            config = json.load(f)
        print(f"  {BLUE}Enabled:{RESET} {config.get('enabled', False)}")
        print(f"  {BLUE}Printer:{RESET} {config.get('printer_name', 'System default')}")
        print(f"  {BLUE}Copies:{RESET} {config.get('copies', 1)}")
        print(f"  {BLUE}Paper:{RESET} {config.get('paper_size', 'Letter')}")
        print(f"  {BLUE}Path:{RESET} {config.get('photo_booth_path', 'Not set')}")
    except Exception as e:
        error(f"Error reading config: {e}")
else:
    error("NOT found")
    print(f"\n{YELLOW}Fix:{RESET} Run setup first: python3 photobooth_printer.py setup\n")
    config = None
    sys.exit(1)

# 4. Check Photo Booth path
check("Photo Booth library path")
pb_path = os.path.expanduser(config.get('photo_booth_path', '~/Pictures/Photo Booth Library/Pictures'))
if os.path.exists(pb_path):
    success(f"Exists: {pb_path}")

    # Count files
    try:
        files = list(Path(pb_path).glob('*.jpg')) + list(Path(pb_path).glob('*.png'))
        print(f"  {BLUE}Photos found:{RESET} {len(files)}")
        if files:
            latest = max(files, key=os.path.getctime)
            print(f"  {BLUE}Latest:{RESET} {latest.name}")
    except Exception as e:
        warning(f"Cannot list files: {e}")
else:
    error(f"NOT found: {pb_path}")
    print(f"\n{YELLOW}Possible fixes:{RESET}")
    print("  1. Check if Photo Booth saves photos in a different location")
    print("  2. Take a photo with Photo Booth first to create the folder")
    print("  3. Update the path in config file")
    print()

# 5. Check printers
check("Available printers")
try:
    result = subprocess.run(['lpstat', '-p'], capture_output=True, text=True)
    if result.returncode == 0:
        printers = [line.split()[1] for line in result.stdout.split('\n') if line.startswith('printer')]
        if printers:
            success(f"{len(printers)} printer(s) found")
            for printer in printers:
                print(f"  • {printer}")
        else:
            warning("No printers configured")
    else:
        error("Cannot check printers")
except Exception as e:
    error(f"Error: {e}")

# 6. Check CUPS
check("CUPS printing system")
try:
    result = subprocess.run(['lpstat', '-r'], capture_output=True, text=True)
    if 'running' in result.stdout:
        success("Running")
    else:
        warning("May not be running")
except Exception as e:
    error(f"Error: {e}")

# 7. Check if process is running
check("PhotoBooth Printer process")
try:
    result = subprocess.run(['pgrep', '-f', 'photobooth_printer.py'], capture_output=True, text=True)
    if result.returncode == 0:
        pids = result.stdout.strip().split('\n')
        success(f"Running (PID: {', '.join(pids)})")
    else:
        warning("Not running")
        print(f"  {YELLOW}Start it with:{RESET} python3 photobooth_printer.py start")
except Exception as e:
    warning(f"Cannot check: {e}")

# 8. Check log file
check("Log file")
log_path = os.path.expanduser("~/Library/Logs/photobooth-printer.log")
if os.path.exists(log_path):
    success(f"Found: {log_path}")
    print(f"\n{BLUE}Last 10 lines of log:{RESET}")
    print("-" * 60)
    try:
        with open(log_path, 'r') as f:
            lines = f.readlines()
            for line in lines[-10:]:
                print(f"  {line.rstrip()}")
    except Exception as e:
        warning(f"Cannot read log: {e}")
    print("-" * 60)
else:
    warning("Not found (normal if never run)")

# 9. Test permissions
check("File system permissions")
test_file = os.path.join(pb_path, '.test_write_permission')
try:
    with open(test_file, 'w') as f:
        f.write('test')
    os.remove(test_file)
    success("Can read/write in Photo Booth folder")
except Exception as e:
    error(f"Cannot write to folder: {e}")
    print(f"\n{YELLOW}Fix:{RESET} Grant Full Disk Access to Terminal/Python:")
    print("  System Preferences → Security & Privacy → Privacy → Full Disk Access")

print()
print("=" * 60)
print("📋 SUMMARY & RECOMMENDATIONS")
print("=" * 60)
print()

# Recommendations
recommendations = []

if config and not config.get('enabled', True):
    recommendations.append("⚠️  Printing is DISABLED. Enable it with: python3 photobooth_printer.py enable")

if not os.path.exists(pb_path):
    recommendations.append("⚠️  Photo Booth folder not found. Check the path in config.")

try:
    result = subprocess.run(['pgrep', '-f', 'photobooth_printer.py'], capture_output=True)
    if result.returncode != 0:
        recommendations.append("⚠️  PhotoBooth Printer is not running. Start it with: python3 photobooth_printer.py start")
except:
    pass

if not recommendations:
    print(f"{GREEN}✓ Everything looks good!{RESET}")
    print()
    print("If auto-printing still doesn't work:")
    print("  1. Make sure you clicked 'Start' in the app")
    print("  2. Take a NEW photo (after starting the monitor)")
    print("  3. Wait a few seconds")
    print("  4. Check the log file for errors")
else:
    print("Issues found:\n")
    for rec in recommendations:
        print(f"  {rec}")

print()
print("=" * 60)
print()

# Offer to create a test
print("Would you like to create a TEST photo to verify monitoring?")
response = input("Type 'yes' to create a test file in Photo Booth folder: ").strip().lower()

if response in ['yes', 'y', 'si', 'sì']:
    if os.path.exists(pb_path):
        test_photo = os.path.join(pb_path, f'TEST_PHOTO_{int(os.times().elapsed * 1000)}.txt')
        try:
            with open(test_photo, 'w') as f:
                f.write("This is a test file to verify monitoring.\n")
                f.write("If PhotoBooth Printer is running, it should detect this file.\n")
            print(f"\n{GREEN}✓ Test file created:{RESET} {test_photo}")
            print("Check if the monitor detected it (check logs)")
        except Exception as e:
            print(f"\n{RED}✗ Cannot create test file:{RESET} {e}")
    else:
        print(f"\n{RED}Cannot create test file - folder doesn't exist{RESET}")

print()
print("For more help, see: INSTALL_INSTRUCTIONS.md")
print()
