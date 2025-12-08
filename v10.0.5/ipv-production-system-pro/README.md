# IPV Production System Pro

Professional video production system for YouTube content creators.

## Version 9.2.0 - SupaData Rotation Restored

### Features

- **Multi-source imports**: YouTube, Vimeo, Dailymotion
- **AI-powered transcriptions**: SupaData integration with key rotation
- **SupaData Key Rotation**: 3 separate API keys with Fixed or Round-Robin modes
- **Automated descriptions**: OpenAI with Golden Prompt
- **Video Wall**: AJAX filters, pagination, responsive grid
- **Elementor integration**: Custom widgets
- **Queue system**: Background processing with CRON
- **Analytics**: YouTube statistics tracking
- **Multilingua**: 6 languages (IT, EN, FR, DE, ES, PT, RU)

### Requirements

- WordPress 6.0+
- PHP 7.4+
- API Keys: YouTube Data API v3, SupaData, OpenAI

### Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to **IPV Production > Settings** to configure API keys
4. Start importing videos!

### Shortcodes

```
[ipv_video_wall]
[ipv_video_wall show_filters="yes" per_page="12" columns="3"]
[ipv_video_wall category="tutorial" speaker="john"]
```

### API Keys Setup

1. **YouTube Data API v3**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a project and enable YouTube Data API v3
   - Create an API key

2. **SupaData**
   - Register at [SupaData.ai](https://supadata.ai)
   - Get your API key from dashboard
   - **Multiple Keys Support**: You can configure up to 3 API keys
   - **Rotation Modes**:
     - 🔒 **Fixed Key**: Always use primary key, others as fallback
     - 🔄 **Round-Robin**: Rotate between keys on each call (perfect for distributing load across 3 accounts with 300 calls/month = 900 total)

3. **OpenAI**
   - Register at [OpenAI Platform](https://platform.openai.com)
   - Create an API key

### SupaData Key Rotation

The plugin supports advanced SupaData key management:

**Configuration**:
- Go to **IPV Production > Settings**
- Enter up to 3 SupaData API keys (Key #1, #2, #3)
- Select rotation mode:
  - **Fixed Key**: Primary key is used for all calls, secondary keys only as fallback when primary runs out of credits
  - **Round-Robin Rotation**: Each API call uses the next key in sequence, distributing load evenly

**Benefits**:
- **Increased quota**: 3 accounts × 300 calls = 900 calls/month
- **Automatic failover**: If one key fails (quota/rate limit), automatically switches to next
- **Load distribution**: Round-Robin mode evenly distributes API calls
- **Status monitoring**: Real-time display of active keys and rotation state

**How it works**:
1. **Fixed mode**: Uses Key #1 → if 402/429 error → tries Key #2 → if error → tries Key #3
2. **Rotation mode**: Call 1 uses Key #1 → Call 2 uses Key #2 → Call 3 uses Key #3 → Call 4 uses Key #1 (loop)

**Error Handling**:
- `402 Payment Required`: Quota exhausted, switches to next key
- `429 Too Many Requests`: Rate limit hit, switches to next key
- Other errors: Stops trying (likely video/network issue, not key issue)

### Meta Keys Reference

The plugin uses standardized meta keys (all prefixed with `_ipv_`):

| Constant | Meta Key | Description |
|----------|----------|-------------|
| `META_VIDEO_ID` | `_ipv_video_id` | YouTube video ID |
| `META_YOUTUBE_URL` | `_ipv_youtube_url` | Full YouTube URL |
| `META_VIDEO_SOURCE` | `_ipv_video_source` | Source platform |
| `META_TRANSCRIPT` | `_ipv_transcript` | Video transcription |
| `META_AI_DESCRIPTION` | `_ipv_ai_description` | AI-generated description |
| `META_YT_DURATION_SEC` | `_ipv_yt_duration_seconds` | Duration in seconds |
| `META_YT_VIEW_COUNT` | `_ipv_yt_view_count` | YouTube views |
| `META_YT_THUMBNAIL_URL` | `_ipv_yt_thumbnail_url` | Thumbnail URL |

Use `IPV_Prod_Helpers::META_*` constants in your code.

### Helper Functions

```php
// Extract YouTube ID from URL
$video_id = IPV_Prod_Helpers::extract_youtube_id( $url );

// Check if video exists
$post_id = IPV_Prod_Helpers::video_exists( $video_id );

// Format duration
$formatted = IPV_Prod_Helpers::format_duration( 3665 ); // "1:01:05"

// Detect video source
$source = IPV_Prod_Helpers::detect_video_source( $url ); // 'youtube', 'vimeo', 'dailymotion'

// Set thumbnail from YouTube
IPV_Prod_Helpers::set_youtube_thumbnail( $post_id, $video_id );
```

### Hooks & Filters

```php
// After video import
do_action( 'ipv_video_imported', $post_id, $video_id );

// Modify AI prompt
add_filter( 'ipv_golden_prompt', function( $prompt, $title, $transcript ) {
    return $prompt . "\n\nExtra instructions...";
}, 10, 3 );
```

### Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

### Support

For support, contact: [email protected]

### License

Proprietary - All rights reserved.
