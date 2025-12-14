# 📁 IPV Production System - File Index

Indice rapido di tutti i file del progetto con descrizioni.

---

## 🎯 Plugin Client (67 file PHP)

### Core System (10 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `ipv-production-system-pro.php` | 808 | **Main plugin file** - Bootstrap, autoloader, CRON, AJAX |
| `class-api-client.php` | 501 | **API Client** - Comunicazione server SaaS |
| `class-logger.php` | 22 | Logger minimale per debug |
| `class-helpers.php` | 381 | Utility functions (extract_youtube_id, format_duration, etc.) |
| `class-cpt.php` | 1283 | Custom Post Type "ipv_video" + taxonomies |
| `class-menu-manager.php` | 1003 | **Sistema menu centralizzato** - 9 submenu |
| `class-dashboard.php` | 630 | Dashboard admin con stats e quick actions |
| `class-settings-unified.php` | 263 | Settings page (delegata a class-settings.php) |
| `class-settings.php` | 1372 | Impostazioni complete (4 tab) |
| `class-tools.php` | 263 | Tools page (diagnostics, bulk, cleanup) |

### AI & Transcriptions (4 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-supadata.php` | - | Wrapper SupaData API (trascrizioni) |
| `class-ai-generator.php` | 589 | OpenAI API - Generazione descrizioni AI |
| `class-ai-enhancements.php` | 487 | Estrazione metadata da descrizioni AI |
| `class-golden-prompt-manager.php` | 379 | Gestione Golden Prompt personalizzato |

### Import System (7 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-import-unified.php` | 1056 | **Import page unificata** - 4 modalità in tab |
| `class-simple-import.php` | - | Import singolo video |
| `class-bulk-import.php` | 1061 | Import multiplo da lista URL |
| `class-rss-importer.php` | 428 | Auto-import da RSS feed |
| `class-youtube-importer.php` | - | Legacy YouTube importer |
| `class-vimeo-api.php` | 159 | Vimeo API wrapper |
| `class-dailymotion-api.php` | 159 | Dailymotion API wrapper |

### Queue System (3 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-queue.php` | 1136 | **Core queue system** - Processing pipeline |
| `class-queue-dashboard.php` | 670 | Queue admin page con stats real-time |
| `class-ai-queue.php` | 53 | AI descriptions queue (batch processing) |

### Video APIs (3 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-youtube-api.php` | - | YouTube Data API v3 wrapper |
| `class-youtube-chapters.php` | - | Estrazione capitoli video |
| `class-unified-importer.php` | - | Importer unificato multi-source |

### Frontend & Shortcodes (6 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-video-wall.php` | - | **[ipv_video_wall]** shortcode + AJAX filters |
| `class-video-wall-admin.php` | - | Video Wall settings page |
| `class-video-wall-settings.php` | - | Video Wall configurazione |
| `class-coming-soon.php` | 570 | **[ipv_coming_soon]** shortcode - Video premiere |
| `class-shortcodes.php` | - | Shortcodes registry |
| `class-video-frontend.php` | - | Embed YouTube player in post content |
| `class-theme-compatibility.php` | - | Template system con override support |

### Integrations (4 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-elementor-widgets.php` | 55 | Elementor widgets loader |
| `class-elementor-templates.php` | 1021 | 3 Elementor widgets (Grid, Player, Wall) |
| `class-gutenberg-blocks.php` | 222 | Gutenberg blocks registry |
| `class-rest-api.php` | 258 | Custom REST API endpoints |

### Analytics & SEO (5 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-analytics.php` | 823 | **Analytics dashboard** - YouTube stats tracking |
| `class-video-seo.php` | - | SEO metadata (schema.org, Open Graph) |
| `class-video-sitemap.php` | - | Video sitemap XML generation |
| `class-performance.php` | 272 | Performance optimizations |
| `class-video-list-columns.php` | - | Custom admin columns |

### Admin Tools (5 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-bulk-tools.php` | 1444 | **Bulk operations** - Transcript/AI regen |
| `class-bulk-operations.php` | 461 | Bulk actions handler |
| `class-cpt-bulk-actions.php` | 360 | CPT custom bulk actions |
| `class-duplicate-checker.php` | 468 | Duplicate video detection |
| `class-diagnostics.php` | 762 | **System diagnostics** - Connection test |

### Taxonomy & Language (3 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-taxonomy-manager.php` | 1027 | Tassonomie (Categorie, Relatori, Tag) |
| `class-speaker-rules.php` | - | Auto-detection relatori |
| `class-language-manager.php` | 1029 | **Multi-lingua** - 6 lingue |

### License & Updates (3 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-license-manager-client.php` | 854 | **License activation** - Client-side |
| `class-remote-updater.php` | 139 | Remote updates system |
| `class-admin-info.php` | 39 | Admin info widget |

### Utilities (5 file)

| File | Righe | Descrizione |
|------|-------|-------------|
| `class-qr-generator.php` | 199 | QR code generator |
| `class-telegram.php` | - | Telegram notifications |
| `class-full-pipeline.php` | 38 | Full pipeline executor |
| `class-ipv-markdown-full.php` | 28 | Markdown parser wrapper |
| `class-wp-cli.php` | - | WP-CLI commands (3 commands) |

### Elementor Widgets (3 file)

| File | Descrizione |
|------|-------------|
| `elementor-widgets/video-grid-widget.php` | Grid widget per Elementor |
| `elementor-widgets/video-player-widget.php` | Player widget per Elementor |
| `elementor-widgets/video-wall-widget.php` | Wall widget per Elementor |

### Views (1 file)

| File | Descrizione |
|------|-------------|
| `views/rss-settings.php` | RSS settings page template |

---

## 🖥️ Vendor Server (17 file PHP)

### Core System (3 file)

| File | Descrizione |
|------|-------------|
| `ipv-pro-vendor.php` | Main plugin file - Bootstrap |
| `includes/class-vendor-core.php` | Core initialization |
| `includes/class-auto-configurator.php` | Auto-configuration helper |

### API Gateway (4 file)

| File | Descrizione |
|------|-------------|
| `includes/class-api-gateway.php` | **🔒 API Keys storage** - SupaData, OpenAI, YouTube |
| `api/endpoints/class-gateway-endpoints.php` | Gateway REST endpoints |
| `api/endpoints/class-youtube-endpoints.php` | YouTube API proxy |
| `api/endpoints/class-updates-endpoints.php` | Remote updates endpoints |

### License System (3 file)

| File | Descrizione |
|------|-------------|
| `includes/class-license-manager.php` | **License CRUD** - Activation, validation |
| `api/endpoints/class-license-endpoints.php` | License REST endpoints |
| `includes/class-credits-manager.php` | Credits tracking & reset |

### WooCommerce (2 file)

| File | Descrizione |
|------|-------------|
| `includes/class-woocommerce-integration.php` | Order → License generation |
| `includes/class-plans-manager.php` | Plans configuration |

### Admin & Portal (3 file)

| File | Descrizione |
|------|-------------|
| `includes/class-admin-dashboard.php` | Admin dashboard page |
| `includes/class-admin-status-page.php` | System status page |
| `includes/class-customer-portal.php` | Customer My Account portal |

### Utilities (2 file)

| File | Descrizione |
|------|-------------|
| `includes/class-webhook-handler.php` | WooCommerce webhooks |
| `includes/class-remote-updates-server.php` | Updates server logic |

---

## 📦 Assets

### Plugin Client

```
assets/
├── css/
│   ├── admin.css           # Admin styles (Bootstrap-based)
│   ├── video-wall.css      # Video Wall frontend
│   └── coming-soon.css     # Coming Soon widget
└── js/
    ├── admin.js            # Admin scripts (AJAX, queue, etc.)
    ├── video-wall.js       # Video Wall filters & pagination
    └── coming-soon.js      # Coming Soon countdown
```

### Vendor Server

```
admin/
└── assets/
    ├── css/
    │   └── admin.css       # Vendor admin styles
    └── js/
        └── admin.js        # Vendor admin scripts
```

---

## 🗄️ Database Tables

### Plugin Client (1 tabella)

```sql
wp_ipv_prod_queue
  - id, video_id, url, status, source, priority
  - attempts, error_message, created_at, processed_at
```

### Vendor Server (5 tabelle)

```sql
wp_ipv_licenses
  - id, license_key, customer_email, plan, status
  - credits_total, credits_remaining, reset_date
  - max_activations, product_id, order_id

wp_ipv_activations
  - id, license_id, site_url, site_name
  - activated_at, last_check

wp_ipv_api_logs
  - id, license_key, endpoint, video_id
  - response_code, credits_used, created_at

wp_ipv_transcript_cache
  - id, video_id, transcript
  - created_at, expires_at

wp_ipv_usage_stats
  - id, date, license_key
  - transcripts_count, descriptions_count, youtube_calls_count
```

---

## 🌍 Languages (6 lingue)

```
languages/
├── ipv-production-system-pro-it_IT.po  # Italiano
├── ipv-production-system-pro-en_US.po  # English
├── ipv-production-system-pro-fr_FR.po  # Français
├── ipv-production-system-pro-de_DE.po  # Deutsch
├── ipv-production-system-pro-es_ES.po  # Español
└── ipv-production-system-pro-pt_PT.po  # Português
```

---

## 📋 Templates

```
templates/
├── single-ipv_video.php    # Single video template
└── archive-ipv_video.php   # Video archive template
```

**Override:** I temi possono sovrascrivere copiando in `wp-content/themes/THEME/ipv-production-system-pro/`

---

## 🔧 Configuration Files

### Plugin Client

```
ipv-production-system-pro/
├── README.md               # User documentation
├── CHANGELOG.md            # Version history
└── languages/              # Translation files
```

### Vendor Server

```
ipv-pro-vendor/
├── README.md               # Setup guide
├── database-schema.sql     # Database schema
└── .htaccess               # Security rules
```

---

## 📊 File Statistics

### Plugin Client

- **Total Files**: 67 PHP + 6 PO + 2 templates + assets
- **Total Lines**: ~23,000 (PHP only)
- **Largest File**: `class-bulk-tools.php` (1,444 lines)
- **Core Files**: 10
- **Feature Files**: 57

### Vendor Server

- **Total Files**: 17 PHP + SQL schema
- **Core Components**: 8
- **API Endpoints**: 4
- **Admin/Portal**: 5

### Combined Codebase

- **Total PHP Files**: 84
- **Total Lines**: ~30,000
- **Languages**: 6
- **Database Tables**: 6
- **REST Endpoints**: 12+

---

## 🎯 File Locations Quick Reference

### Frequently Modified Files

```bash
# API Keys (VENDOR)
ipv-pro-vendor/includes/class-api-gateway.php

# Server URL (CLIENT)
ipv-production-system-pro/includes/class-api-client.php

# Main Menu (CLIENT)
ipv-production-system-pro/includes/class-menu-manager.php

# Queue Logic (CLIENT)
ipv-production-system-pro/includes/class-queue.php

# License Logic (VENDOR)
ipv-pro-vendor/includes/class-license-manager.php

# WooCommerce Integration (VENDOR)
ipv-pro-vendor/includes/class-woocommerce-integration.php
```

### Key Entry Points

```bash
# Plugin activation (CLIENT)
ipv-production-system-pro.php::activate()

# Queue processing (CLIENT)
class-queue.php::process_queue()

# License activation (VENDOR)
class-license-manager.php::activate_license()

# API Gateway request (VENDOR)
class-api-gateway.php::get_transcript()
```

---

**Generato il:** 2025-12-14
**Totale file catalogati:** 84 PHP + 6 lingue + 2 templates
