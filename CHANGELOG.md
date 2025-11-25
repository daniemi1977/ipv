
# IPV Production System Pro – Changelog

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
