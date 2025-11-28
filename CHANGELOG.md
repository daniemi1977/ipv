
# IPV Production System Pro – Changelog

## v7.6.0 - 2025-11-28
### 🎉 MAJOR UPDATE - Complete Integration
Merge of v7.5.8 production features with v6.4.0 Video Wall into a unified, production-ready release.

### 🎬 Video Wall (from v6.4.0)
- **NEW**: Video Wall shortcode `[ipv_video_wall]` with advanced filtering
- **NEW**: 2+3 layout (2 videos top row, 3 videos bottom row)
- **NEW**: AJAX-powered filtering by categoria and relatore
- **NEW**: Search functionality within video wall
- **NEW**: Load More button with progressive loading
- **NEW**: YouTube Shorts filtering - automatically excludes videos < 60 seconds
- **NEW**: Admin settings page for Video Wall configuration (IPV Production > Video Wall)
- **NEW**: Multi-tier thumbnail fallback system (YouTube maxres → WordPress featured → hqdefault → placeholder)
- Default 5 videos per page (configurable)
- Modern UI with hover effects and smooth animations
- Mobile-responsive design

### ⚡ Production Features (from v7.5.8)
- **NEW**: Simple Import - Import and publish videos immediately without waiting for transcription/AI
- **NEW**: Speaker Rules - Automatic speaker assignment based on title patterns
- **NEW**: Video Frontend - Automatic YouTube embed in single video pages
- **NEW**: Bulk Tools - Mass regeneration of taxonomies, descriptions, transcriptions, thumbnails
- **NEW**: Autoloading system with `spl_autoload_register`
- Enhanced CPT with improved metadata handling
- Golden Prompt v3.2 optimized for timestamp generation
- Improved duration parsing (ISO8601 → seconds → formatted)
- Progress bars and real-time logging for bulk operations
- Speaker priority system (manual rules → title parsing → AI extraction → fallback)

### 🔧 Technical Improvements
- Modern architecture with autoloading
- Clean separation of concerns (MVC-like structure)
- Performance optimized with AJAX loading
- Proper meta_query for duration filtering
- Enhanced error handling and logging
- CSS with high specificity to avoid theme conflicts
- Responsive design across all components

### 📋 Workflow Improvements
- Complete automation: Import → Transcription → AI → Taxonomies → Publish
- Manual override capabilities for all automated features
- Real-time progress tracking for bulk operations
- Configurable CRON (every 5 minutes)
- Queue system with status tracking (pending → processing → done/error)

### 🗄️ Database & Metadata
- Enhanced meta fields: `_ipv_yt_duration_seconds`, `_ipv_yt_duration_formatted`
- Improved taxonomy extraction from AI descriptions
- Automatic hashtag to WordPress tag conversion
- Better handling of speakers/guests across formats

## v7.5.8 - 2025-11-27
### Production Release
- Bulk Tools panel integration
- Fix hashtag extraction (same line parsing)
- Fix ISO8601 duration parsing
- Timestamp generation based on content changes
- Debug logging for video duration
- Speaker Rules menu with correct priority
- Improved YouTube embed CSS

## v6.4.0 - 2025-11-25
### Video Wall Feature 🎬
- **NEW**: Video Wall shortcode `[ipv_video_wall]` with advanced filtering
- **NEW**: 2+3 layout (2 videos top row, 3 videos bottom row)
- **NEW**: AJAX-powered filtering by categoria and relatore
- **NEW**: Search functionality within video wall
- **NEW**: Responsive pagination with smooth transitions
- **NEW**: YouTube Shorts filtering - automatically excludes videos < 60 seconds
- **NEW**: Admin settings page for Video Wall configuration (IPV Production > Video Wall)
- **NEW**: YouTube-style red play button overlay on video thumbnails

### Features
- Default 5 videos per page (configurable)
- Filters for categoria, relatore, and search
- Modern UI with hover effects and smooth animations
- Mobile-responsive design
- Backward compatible with column-based layouts

### Technical Improvements
- Performance optimized with AJAX loading
- Proper meta_query for duration filtering
- Enqueued assets only when needed
- Clean separation of concerns (MVC-like structure)

## v4.5
- Added Prompt Gold integration
- Added Markdown/Notion renderer
- Added Short Filter (<5 minutes)
- Added Custom Template for CPT
- Added Info screen in admin
- General improvements and fixes
