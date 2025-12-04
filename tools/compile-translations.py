#!/usr/bin/env python3
"""
IPV Production System Pro - Translation Compiler
Compiles .po files to .mo files without requiring msgfmt
"""

import os
import struct
import array
from pathlib import Path

def generate_mo_file(po_file, mo_file):
    """
    Generate a .mo file from a .po file
    Implements the GNU gettext .mo file format
    """

    print(f"📖 Reading: {po_file}")

    # Parse .po file
    messages = {}
    current_msgid = None
    current_msgstr = None
    in_msgid = False
    in_msgstr = False

    with open(po_file, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()

            # Skip comments and empty lines
            if not line or line.startswith('#'):
                continue

            # msgid
            if line.startswith('msgid '):
                if current_msgid is not None and current_msgstr is not None:
                    messages[current_msgid] = current_msgstr
                current_msgid = line[7:-1]  # Remove 'msgid "' and '"'
                current_msgstr = None
                in_msgid = True
                in_msgstr = False
                continue

            # msgstr
            if line.startswith('msgstr '):
                current_msgstr = line[8:-1]  # Remove 'msgstr "' and '"'
                in_msgid = False
                in_msgstr = True
                continue

            # msgctxt (context)
            if line.startswith('msgctxt '):
                continue

            # Continuation line
            if line.startswith('"') and line.endswith('"'):
                text = line[1:-1]
                if in_msgid:
                    current_msgid += text
                elif in_msgstr:
                    current_msgstr += text

    # Add last message
    if current_msgid is not None and current_msgstr is not None:
        messages[current_msgid] = current_msgstr

    # Remove empty msgid (header)
    if '' in messages:
        del messages['']

    # Filter out untranslated messages
    messages = {k: v for k, v in messages.items() if v and v != k}

    print(f"   Found {len(messages)} translated strings")

    if len(messages) == 0:
        print(f"   ⚠️  No translations found, skipping .mo generation")
        return False

    # Generate .mo file
    print(f"💾 Writing: {mo_file}")

    # Encode messages
    keys = sorted(messages.keys())
    offsets = []
    ids = b''
    strs = b''

    for key in keys:
        # Encode key
        key_bytes = key.encode('utf-8')
        offsets.append((len(ids), len(key_bytes), len(strs), len(messages[key].encode('utf-8'))))
        ids += key_bytes + b'\x00'
        strs += messages[key].encode('utf-8') + b'\x00'

    # .mo file format
    keystart = 7 * 4 + 16 * len(keys)
    valuestart = keystart + len(ids)

    # Header
    output = [
        struct.pack('Iiiiiii',
            0x950412de,        # Magic number
            0,                 # Version
            len(keys),         # Number of entries
            7 * 4,             # Start of key index
            7 * 4 + 8 * len(keys),  # Start of value index
            0, 0               # Size and offset of hash table
        )
    ]

    # Key index
    for o1, l1, o2, l2 in offsets:
        output.append(struct.pack('ii', l1, o1 + keystart))

    # Value index
    for o1, l1, o2, l2 in offsets:
        output.append(struct.pack('ii', l2, o2 + valuestart))

    # Keys and values
    output.append(ids)
    output.append(strs)

    # Write file
    with open(mo_file, 'wb') as f:
        for item in output:
            f.write(item)

    # Check file size
    file_size = os.path.getsize(mo_file)
    print(f"   ✅ Created ({file_size} bytes)")

    return True


def main():
    """Main function"""

    print("=" * 60)
    print("IPV Production System Pro - Translation Compiler")
    print("=" * 60)
    print()

    # Get languages directory
    script_dir = Path(__file__).parent
    plugin_dir = script_dir.parent
    lang_dir = plugin_dir / 'languages'

    print(f"📁 Languages directory: {lang_dir}")
    print()

    # Find all .po files
    po_files = list(lang_dir.glob('*.po'))

    if not po_files:
        print("❌ No .po files found!")
        return 1

    print(f"Found {len(po_files)} .po file(s):")
    for po_file in po_files:
        print(f"   - {po_file.name}")
    print()

    # Compile each .po file
    success_count = 0
    error_count = 0

    for po_file in po_files:
        mo_file = po_file.with_suffix('.mo')

        try:
            if generate_mo_file(po_file, mo_file):
                success_count += 1
            else:
                error_count += 1
        except Exception as e:
            print(f"   ❌ Error: {e}")
            error_count += 1

        print()

    # Summary
    print("=" * 60)
    print(f"✅ Successfully compiled: {success_count}")
    if error_count > 0:
        print(f"⚠️  Errors: {error_count}")
    print("=" * 60)
    print()

    if success_count > 0:
        print("🎉 Translation files ready!")
        print()
        print("To use translations:")
        print("1. Set WordPress language in Settings → General")
        print("2. The plugin will automatically load the correct translation")
        print()
        print("Available languages:")
        print("   - it_IT (Italiano)")
        print("   - de_DE (Deutsch)")
        print("   - fr_FR (Français)")
        print("   - ru_RU (Русский)")

    return 0 if error_count == 0 else 1


if __name__ == '__main__':
    exit(main())
