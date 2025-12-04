#!/bin/bash

# IPV Production System Pro - Release Package Creator
# Creates a clean distribution ZIP for WordPress plugin

VERSION="9.0.0"
PLUGIN_SLUG="ipv-production-system-pro"
RELEASE_NAME="${PLUGIN_SLUG}-v${VERSION}"
BUILD_DIR="/tmp/${RELEASE_NAME}"
OUTPUT_FILE="${RELEASE_NAME}.zip"

echo "============================================================"
echo "IPV Production System Pro - Release Builder"
echo "Version: ${VERSION}"
echo "============================================================"
echo ""

# Clean up any previous build
if [ -d "$BUILD_DIR" ]; then
    echo "🧹 Cleaning previous build..."
    rm -rf "$BUILD_DIR"
fi

# Create build directory
echo "📁 Creating build directory..."
mkdir -p "$BUILD_DIR"

# Copy plugin files
echo "📦 Copying plugin files..."

# Main plugin file
cp ipv-production-system-pro.php "$BUILD_DIR/"

# Documentation
cp README.md "$BUILD_DIR/"
cp CHANGELOG.md "$BUILD_DIR/"

# Core directories
echo "   - Copying includes/"
cp -r includes "$BUILD_DIR/"

echo "   - Copying languages/ (6 translations)"
cp -r languages "$BUILD_DIR/"

echo "   - Copying assets/"
cp -r assets "$BUILD_DIR/"

echo "   - Copying templates/"
cp -r templates "$BUILD_DIR/"

# Clean up any development files that might have been copied
echo "🧹 Cleaning development files..."
find "$BUILD_DIR" -name ".DS_Store" -delete
find "$BUILD_DIR" -name "*.swp" -delete
find "$BUILD_DIR" -name "*.swo" -delete
find "$BUILD_DIR" -name ".gitkeep" -delete

# Create ZIP archive
echo "📦 Creating ZIP archive..."
cd /tmp
zip -r "/home/user/ipv/${OUTPUT_FILE}" "${RELEASE_NAME}" -q

# Show file size
FILE_SIZE=$(du -h "/home/user/ipv/${OUTPUT_FILE}" | cut -f1)

echo ""
echo "============================================================"
echo "✅ Release package created successfully!"
echo "============================================================"
echo ""
echo "📦 Package: ${OUTPUT_FILE}"
echo "📊 Size: ${FILE_SIZE}"
echo "📍 Location: /home/user/ipv/${OUTPUT_FILE}"
echo ""
echo "Package contents:"
echo "   ✅ Main plugin file"
echo "   ✅ Includes directory (all PHP classes)"
echo "   ✅ Languages directory (6 translations: IT, DE, FR, ES, PT, RU)"
echo "   ✅ Assets directory"
echo "   ✅ Templates directory"
echo "   ✅ README.md"
echo "   ✅ CHANGELOG.md"
echo ""
echo "🚀 Ready for distribution!"
echo "============================================================"

# Clean up build directory
rm -rf "$BUILD_DIR"
