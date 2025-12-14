# 🏗️ IPV Production System Pro - Architettura Completa

**Versione Plugin**: 10.2.14
**Versione Vendor**: 1.3.18
**Data Analisi**: 2025-12-14

---

## 📋 Indice

1. [Panoramica Sistema](#panoramica-sistema)
2. [Architettura Client-Server](#architettura-client-server)
3. [Plugin Client (WordPress)](#plugin-client-wordpress)
4. [Vendor Server (SaaS)](#vendor-server-saas)
5. [Database Schema](#database-schema)
6. [API Endpoints](#api-endpoints)
7. [Workflow Completo](#workflow-completo)
8. [Deployment](#deployment)

---

## 📊 Panoramica Sistema

**IPV Production System Pro** è un sistema **SaaS** completo per la produzione di video YouTube, composto da:

- **Plugin Client** (WordPress): 67 file PHP, ~23.000 righe di codice
- **Vendor Server** (WordPress): 17 file PHP, gestione licenze e API gateway
- **Architettura**: Client-Server con API REST
- **Monetizzazione**: WooCommerce + Subscriptions

### Funzionalità Principali

✅ **Import video multi-fonte** (YouTube, Vimeo, Dailymotion)
✅ **Trascrizioni AI automatiche** (SupaData con rotazione chiavi)
✅ **Descrizioni AI** (OpenAI + Golden Prompt personalizzabile)
✅ **Video Wall frontend** con filtri AJAX e paginazione
✅ **Sistema a coda** con elaborazione background (CRON)
✅ **Analytics YouTube** con aggiornamento automatico
✅ **Multilingua** (IT, EN, FR, DE, ES, PT, RU)
✅ **Elementor + Gutenberg** widgets
✅ **WP-CLI commands** per system cron
✅ **Sistema licenze** con crediti mensili

---

## 🔄 Architettura Client-Server

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT (WordPress Site)                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  IPV Production System Pro Plugin (v10.2.14)           │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │ │
│  │  │ Import       │  │ Queue System │  │ Video Wall   │ │ │
│  │  │ Manager      │  │ (CRON)       │  │ Frontend     │ │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘ │ │
│  │                                                          │ │
│  │  ┌────────────────────────────────────────────────────┐ │ │
│  │  │          API Client (class-api-client.php)         │ │ │
│  │  │  - License validation                              │ │ │
│  │  │  - Transcript requests (NO API keys)               │ │ │
│  │  │  │  - AI description requests (NO API keys)          │ │ │
│  │  │  - YouTube data requests (NO API keys)             │ │ │
│  │  └────────────────────────────────────────────────────┘ │ │
│  └──────────────────────────┬─────────────────────────────┘ │
└─────────────────────────────┼───────────────────────────────┘
                              │
                              │ HTTPS REST API
                              │ Authorization: Bearer LICENSE_KEY
                              │
┌─────────────────────────────┼───────────────────────────────┐
│                             ▼                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │      API Gateway (class-api-gateway.php)               │ │
│  │  ┌──────────────────────────────────────────────────┐ │ │
│  │  │  🔒 PROTECTED API KEYS (server-side only)        │ │ │
│  │  │  - YOUTUBE_API_KEY                               │ │ │
│  │  │  - SUPADATA_API_KEY_1/2/3 (rotation)             │ │ │
│  │  │  - OPENAI_API_KEY                                │ │ │
│  │  └──────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │       License Manager (class-license-manager.php)      │ │
│  │  - License activation/deactivation                     │ │
│  │  - Credits tracking & validation                       │ │
│  │  - Monthly reset (CRON)                                │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │    WooCommerce Integration (Subscriptions)             │ │
│  │  - Trial: €0 (10 video)                                │ │
│  │  - Starter: €19.95/mese (25 video)                     │ │
│  │  - Professional: €49.95/mese (100 video)               │ │
│  │  - Business: €99.95/mese (500 video)                   │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                               │
│              SERVER (WordPress + WooCommerce)                 │
│                  IPV Pro Vendor (v1.3.18)                     │
└───────────────────────────────────────────────────────────────┘
```

### Vantaggi Architettura SaaS

1. **🔒 Sicurezza API Keys**: Le chiavi API (YouTube, SupaData, OpenAI) NON sono mai distribuite al client
2. **💰 Monetizzazione**: Sistema crediti mensili con reset automatico
3. **📊 Controllo Centralizzato**: Tutte le chiamate API passano dal server (analytics, limits)
4. **🔄 Updates Automatici**: Remote updates dal server
5. **🛡️ Protezione Licenze**: Validazione server-side, impossibile bypassare

---

## 💻 Plugin Client (WordPress)

### Struttura Directory

```
ipv-production-system-pro/
├── ipv-production-system-pro.php (32KB - Main file)
├── README.md
├── CHANGELOG.md
├── assets/
│   ├── css/
│   └── js/
├── includes/ (61 file PHP, ~23K righe)
│   ├── Core Classes
│   │   ├── class-api-client.php (15KB)
│   │   ├── class-logger.php
│   │   ├── class-helpers.php (11KB)
│   │   └── class-cpt.php (38KB)
│   ├── AI & Transcriptions
│   │   ├── class-supadata.php
│   │   ├── class-ai-generator.php (17KB)
│   │   ├── class-ai-enhancements.php (14KB)
│   │   └── class-golden-prompt-manager.php (11KB)
│   ├── Import System
│   │   ├── class-import-unified.php (31KB)
│   │   ├── class-simple-import.php
│   │   ├── class-bulk-import.php (31KB)
│   │   ├── class-rss-importer.php (12KB)
│   │   ├── class-youtube-importer.php
│   │   ├── class-vimeo-api.php
│   │   └── class-dailymotion-api.php
│   ├── Queue System
│   │   ├── class-queue.php (34KB)
│   │   ├── class-queue-dashboard.php (20KB)
│   │   └── class-ai-queue.php
│   ├── Admin Interface
│   │   ├── class-menu-manager.php (47KB) ⭐
│   │   ├── class-dashboard.php (18KB)
│   │   ├── class-settings-unified.php
│   │   └── class-tools.php
│   ├── Frontend
│   │   ├── class-video-wall.php
│   │   ├── class-video-wall-admin.php
│   │   ├── class-video-frontend.php
│   │   ├── class-coming-soon.php (17KB)
│   │   ├── class-shortcodes.php
│   │   └── class-theme-compatibility.php
│   ├── Integrations
│   │   ├── class-elementor-widgets.php
│   │   ├── class-elementor-templates.php (30KB)
│   │   ├── class-gutenberg-blocks.php
│   │   └── class-rest-api.php
│   ├── Analytics & SEO
│   │   ├── class-analytics.php (24KB)
│   │   ├── class-video-seo.php
│   │   ├── class-video-sitemap.php
│   │   └── class-youtube-chapters.php
│   ├── Utilities
│   │   ├── class-bulk-tools.php (43KB)
│   │   ├── class-duplicate-checker.php (14KB)
│   │   ├── class-diagnostics.php (22KB)
│   │   ├── class-performance.php
│   │   ├── class-qr-generator.php
│   │   └── class-telegram.php
│   ├── License & Updates
│   │   ├── class-license-manager-client.php (25KB)
│   │   └── class-remote-updater.php
│   ├── WP-CLI
│   │   └── class-wp-cli.php
│   ├── Views
│   │   └── rss-settings.php
│   └── Elementor Widgets
│       ├── video-grid-widget.php
│       ├── video-player-widget.php
│       └── video-wall-widget.php
├── languages/ (6 lingue)
│   ├── ipv-production-system-pro-it_IT.po
│   ├── ipv-production-system-pro-en_US.po
│   ├── ipv-production-system-pro-fr_FR.po
│   ├── ipv-production-system-pro-de_DE.po
│   ├── ipv-production-system-pro-es_ES.po
│   └── ipv-production-system-pro-pt_PT.po
└── templates/
    ├── single-ipv_video.php
    └── archive-ipv_video.php
```

### 🎯 Classi Chiave

#### 1. **Menu Manager** (`class-menu-manager.php` - 47KB)

Sistema menu centralizzato con UX ottimizzata:

```
📊 Dashboard
📋 Tutti i Video
📥 Importa Video (4 modalità in tab)
   ├── 📹 Singolo Video
   ├── 📦 Lista URL (bulk)
   ├── 📺 Da Canale YouTube
   └── 📡 Feed RSS (auto)
⏳ Coda Elaborazione
────────────────────
📁 Categorie
👤 Relatori
🏷️ Tag
────────────────────
⚙️ Impostazioni (4 tab)
   ├── Generali
   ├── Automazione
   ├── AI & Prompt
   └── Licenza
🔧 Strumenti (3 tab)
   ├── Diagnostica
   ├── Operazioni Bulk
   └── Pulizia
```

#### 2. **Queue System** (`class-queue.php` - 34KB)

Pipeline di elaborazione automatica:

```php
// Tabella: wp_ipv_prod_queue
CREATE TABLE wp_ipv_prod_queue (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  video_id varchar(20),      -- YouTube video ID
  url varchar(500),          -- Full URL
  status varchar(20),        -- pending/processing/done/failed
  source varchar(50),        -- manual/bulk/rss/channel
  priority int DEFAULT 5,
  attempts int DEFAULT 0,
  error_message text,
  created_at datetime,
  processed_at datetime
);
```

**Workflow:**
1. Video aggiunto alla coda
2. CRON esegue ogni 5 minuti (`ipv_every_5_minutes`)
3. Elabora 3 video per batch
4. Per ogni video:
   - Download metadata YouTube (titolo, thumbnail, durata, views)
   - Trascrizione SupaData (~20 secondi)
   - Descrizione AI OpenAI (~12 secondi)
   - Estrazione metadata (categorie, tag, relatori)
   - Pubblicazione post

**Performance:** ~34 secondi per video (con system cron: ~39 secondi totali)

#### 3. **API Client** (`class-api-client.php` - 15KB)

Gestisce comunicazione con il server:

```php
class IPV_Prod_API_Client {
    const SERVER_URL = 'https://your-server.com';

    // Endpoints
    public static function get_transcript($video_id) {
        return self::post('/wp-json/ipv-vendor/v1/transcript', [
            'video_id' => $video_id
        ]);
    }

    public static function generate_ai_description($title, $transcript) {
        return self::post('/wp-json/ipv-vendor/v1/description', [
            'title' => $title,
            'transcript' => $transcript,
            'golden_prompt' => get_option('ipv_golden_prompt')
        ]);
    }

    public static function get_youtube_video_data($video_id) {
        return self::post('/wp-json/ipv-vendor/v1/youtube/video-data', [
            'video_id' => $video_id
        ]);
    }

    // License validation
    public static function validate_license() {
        $license_key = get_option('ipv_license_key');
        return self::post('/wp-json/ipv-vendor/v1/license/validate', [
            'license_key' => $license_key,
            'site_url' => home_url()
        ]);
    }
}
```

#### 4. **Import Unified** (`class-import-unified.php` - 31KB)

4 modalità di import:

```php
// 1. Singolo Video
add_submenu_page('ipv-import', 'Singolo', ...);

// 2. Lista URL (Bulk)
$urls = explode("\n", $_POST['youtube_urls']);
foreach ($urls as $url) {
    IPV_Prod_Queue::enqueue($video_id, $url, 'bulk');
}

// 3. Da Canale YouTube
$videos = IPV_Prod_YouTube_API::get_channel_videos($channel_id, $max);
foreach ($videos as $video) {
    IPV_Prod_Queue::enqueue($video['id'], ...);
}

// 4. RSS Auto-Import
wp_schedule_event(time(), 'hourly', 'ipv_prod_rss_import');
// Controlla RSS feed → importa nuovi video automaticamente
```

#### 5. **Video Wall** (`class-video-wall.php`)

Frontend con filtri AJAX:

```php
// Shortcode
[ipv_video_wall]
[ipv_video_wall show_filters="yes" per_page="12" columns="3"]
[ipv_video_wall category="tutorial" speaker="john"]

// Altri shortcode disponibili
[ipv_coming_soon] / [ipv_in_programma]  // Video premiere
[ipv_video id="123"]                     // Player singolo
[ipv_grid category="tutorial"]           // Griglia semplice
[ipv_search]                             // Form ricerca
[ipv_stats]                              // Box statistiche
```

### 🔧 WP-CLI Commands

```bash
# Process queue immediately
wp ipv-prod queue run

# Update YouTube data (views, thumbnails)
wp ipv-prod youtube update

# Ensure CRON is scheduled
wp ipv-prod cron ensure
```

### ⏰ CRON Schedule

```php
// System CRON (raccomandato)
*/5 * * * * wp ipv-prod queue run --path=/var/www/html
0 * * * *   wp ipv-prod youtube update --path=/var/www/html
*/30 * * * * wp ipv-prod cron ensure --path=/var/www/html

// WP CRON (fallback)
ipv_prod_process_queue      → ogni 5 minuti
ipv_prod_update_youtube_data → ogni ora
```

---

## 🖥️ Vendor Server (SaaS)

### Struttura Directory

```
ipv-pro-vendor/
├── ipv-pro-vendor.php (main file)
├── README.md
├── database-schema.sql
├── .htaccess (security rules)
├── api/
│   └── endpoints/
│       ├── class-gateway-endpoints.php
│       ├── class-license-endpoints.php
│       ├── class-updates-endpoints.php
│       └── class-youtube-endpoints.php
├── admin/
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── views/
└── includes/
    ├── class-vendor-core.php
    ├── class-api-gateway.php ⭐
    ├── class-license-manager.php ⭐
    ├── class-credits-manager.php
    ├── class-plans-manager.php
    ├── class-woocommerce-integration.php
    ├── class-remote-updates-server.php
    ├── class-webhook-handler.php
    ├── class-customer-portal.php
    ├── class-admin-dashboard.php
    └── class-auto-configurator.php
```

### 🎯 Classi Chiave

#### 1. **API Gateway** (`class-api-gateway.php`)

Protegge le API keys server-side:

```php
class IPV_Vendor_API_Gateway {
    // 🔒 PROTECTED - Never distributed to client
    const YOUTUBE_API_KEY = 'AIza...';
    const SUPADATA_API_KEY_1 = 'sd_...';
    const SUPADATA_API_KEY_2 = 'sd_...';
    const SUPADATA_API_KEY_3 = 'sd_...';
    const OPENAI_API_KEY = 'sk-proj_...';

    // Rotation mode (configurable in wp_options)
    private static $rotation_mode = 'round_robin'; // or 'fixed'

    public static function get_transcript($video_id, $license_key) {
        // 1. Validate license
        if (!self::validate_license($license_key)) {
            return ['error' => 'Invalid license'];
        }

        // 2. Check credits
        if (!self::has_credits($license_key)) {
            return ['error' => 'No credits'];
        }

        // 3. Call SupaData API (with rotation)
        $key = self::get_next_supadata_key();
        $result = self::call_supadata($video_id, $key);

        // 4. Deduct credit
        self::deduct_credit($license_key, 'transcript');

        // 5. Log usage
        self::log_api_call($license_key, 'transcript', $video_id);

        return $result;
    }

    private static function get_next_supadata_key() {
        if (self::$rotation_mode === 'round_robin') {
            $index = get_option('ipv_supadata_rotation_index', 0);
            $keys = [
                self::SUPADATA_API_KEY_1,
                self::SUPADATA_API_KEY_2,
                self::SUPADATA_API_KEY_3
            ];
            $key = $keys[$index % count($keys)];
            update_option('ipv_supadata_rotation_index', $index + 1);
            return $key;
        }

        // Fixed mode: try keys in order until one works
        return self::SUPADATA_API_KEY_1;
    }
}
```

#### 2. **License Manager** (`class-license-manager.php`)

Gestisce licenze e crediti:

```php
// Table: wp_ipv_licenses
CREATE TABLE wp_ipv_licenses (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_key varchar(64) UNIQUE,
  customer_email varchar(255),
  plan varchar(50),           -- trial/starter/professional/business
  status varchar(20),         -- active/expired/cancelled
  credits_total int,          -- 10/25/100/500
  credits_remaining int,
  reset_date date,
  max_activations int,        -- 1/1/3/10
  product_id bigint(20),
  order_id bigint(20),
  created_at datetime,
  updated_at datetime
);

// Table: wp_ipv_activations
CREATE TABLE wp_ipv_activations (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_id bigint(20),
  site_url varchar(255),
  site_name varchar(255),
  activated_at datetime,
  last_check datetime
);

// Table: wp_ipv_api_logs
CREATE TABLE wp_ipv_api_logs (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_key varchar(64),
  endpoint varchar(100),      -- transcript/description/youtube
  video_id varchar(20),
  response_code int,
  credits_used int,
  created_at datetime
);
```

#### 3. **WooCommerce Integration**

Creazione automatica licenze all'acquisto:

```php
class IPV_Vendor_WooCommerce_Integration {
    public function on_order_completed($order_id) {
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            // Check if it's an IPV license product
            if ($product->get_meta('_ipv_is_license_product') === 'yes') {
                $plan = $product->get_meta('_ipv_plan');
                $credits = $product->get_meta('_ipv_credits_monthly');
                $max_sites = $product->get_meta('_ipv_max_activations');

                // Generate license
                $license_key = $this->generate_license_key();

                // Save to database
                global $wpdb;
                $wpdb->insert($wpdb->prefix . 'ipv_licenses', [
                    'license_key' => $license_key,
                    'customer_email' => $order->get_billing_email(),
                    'plan' => $plan,
                    'status' => 'active',
                    'credits_total' => $credits,
                    'credits_remaining' => $credits,
                    'reset_date' => date('Y-m-01', strtotime('+1 month')),
                    'max_activations' => $max_sites,
                    'product_id' => $product->get_id(),
                    'order_id' => $order_id,
                    'created_at' => current_time('mysql')
                ]);

                // Send email with license key
                $this->send_license_email($order->get_billing_email(), $license_key);
            }
        }
    }

    // CRON: Reset credits first day of month
    public function reset_monthly_credits() {
        global $wpdb;
        $wpdb->query("
            UPDATE {$wpdb->prefix}ipv_licenses
            SET credits_remaining = credits_total,
                reset_date = DATE_ADD(reset_date, INTERVAL 1 MONTH)
            WHERE status = 'active'
            AND reset_date <= CURDATE()
        ");
    }
}

// Schedule
wp_schedule_event(strtotime('first day of next month 02:00'), 'monthly', 'ipv_vendor_reset_credits');
```

### 📡 REST API Endpoints

```php
// Health check
GET /wp-json/ipv-vendor/v1/health
→ { "status": "ok", "version": "1.3.18" }

// License endpoints
POST /wp-json/ipv-vendor/v1/license/activate
Body: { license_key, site_url, site_name }
→ { success: true, data: { plan, credits_remaining, ... } }

POST /wp-json/ipv-vendor/v1/license/validate
Body: { license_key, site_url }
→ { valid: true, credits: 25, plan: "starter" }

GET /wp-json/ipv-vendor/v1/license/info
Headers: Authorization: Bearer LICENSE_KEY
→ { plan, credits_total, credits_remaining, reset_date, activations: [...] }

// API Gateway endpoints (protected by license)
POST /wp-json/ipv-vendor/v1/transcript
Headers: X-License-Key: LICENSE_KEY
Body: { video_id }
→ { transcript: "..." }

POST /wp-json/ipv-vendor/v1/description
Headers: X-License-Key: LICENSE_KEY
Body: { title, transcript, golden_prompt }
→ { description: "..." }

POST /wp-json/ipv-vendor/v1/youtube/video-data
Headers: X-License-Key: LICENSE_KEY
Body: { video_id }
→ { title, description, thumbnail, duration, views, ... }

// Remote updates
GET /wp-json/ipv-vendor/v1/plugin-info
Query: ?license_key=xxx&slug=ipv-production-system-pro
→ { version, download_url, changelog, ... }

POST /wp-json/ipv-vendor/v1/check-update
Body: { license_key, current_version }
→ { new_version, download_url, package }
```

---

## 💾 Database Schema

### Plugin Client Tables

```sql
-- Queue elaborazione
CREATE TABLE wp_ipv_prod_queue (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  video_id varchar(20) NOT NULL,
  url varchar(500) NOT NULL,
  status varchar(20) DEFAULT 'pending',
  source varchar(50),
  priority int DEFAULT 5,
  attempts int DEFAULT 0,
  error_message text,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  processed_at datetime,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
);
```

### Vendor Server Tables

```sql
-- Licenze
CREATE TABLE wp_ipv_licenses (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_key varchar(64) UNIQUE NOT NULL,
  customer_email varchar(255) NOT NULL,
  plan varchar(50) NOT NULL,
  status varchar(20) DEFAULT 'active',
  credits_total int NOT NULL,
  credits_remaining int NOT NULL,
  reset_date date NOT NULL,
  max_activations int DEFAULT 1,
  product_id bigint(20),
  order_id bigint(20),
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_license_key (license_key),
  INDEX idx_email (customer_email),
  INDEX idx_status (status)
);

-- Attivazioni siti
CREATE TABLE wp_ipv_activations (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_id bigint(20) NOT NULL,
  site_url varchar(255) NOT NULL,
  site_name varchar(255),
  activated_at datetime DEFAULT CURRENT_TIMESTAMP,
  last_check datetime,
  FOREIGN KEY (license_id) REFERENCES wp_ipv_licenses(id) ON DELETE CASCADE,
  UNIQUE KEY unique_activation (license_id, site_url)
);

-- Log chiamate API
CREATE TABLE wp_ipv_api_logs (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  license_key varchar(64),
  endpoint varchar(100),
  video_id varchar(20),
  response_code int,
  credits_used int DEFAULT 1,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_license (license_key),
  INDEX idx_created (created_at)
);

-- Cache trascrizioni
CREATE TABLE wp_ipv_transcript_cache (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  video_id varchar(20) UNIQUE NOT NULL,
  transcript text,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  expires_at datetime,
  INDEX idx_video_id (video_id),
  INDEX idx_expires (expires_at)
);

-- Statistiche uso
CREATE TABLE wp_ipv_usage_stats (
  id bigint(20) PRIMARY KEY AUTO_INCREMENT,
  date date NOT NULL,
  license_key varchar(64),
  transcripts_count int DEFAULT 0,
  descriptions_count int DEFAULT 0,
  youtube_calls_count int DEFAULT 0,
  UNIQUE KEY unique_daily_stat (date, license_key)
);
```

---

## 🔄 Workflow Completo

### 1️⃣ Acquisto Licenza (Cliente)

```
Cliente → WooCommerce Product "IPV Pro - Starter" (€19.95/mese)
    ↓
Pagamento completato (Stripe/PayPal)
    ↓
Hook: woocommerce_order_status_completed
    ↓
IPV_Vendor_WooCommerce_Integration::on_order_completed()
    ↓
Genera license_key unica
    ↓
INSERT INTO wp_ipv_licenses (
  license_key = 'ABC123...',
  plan = 'starter',
  credits_total = 25,
  credits_remaining = 25,
  reset_date = '2025-01-01'
)
    ↓
Email automatica con license_key + download link plugin
```

### 2️⃣ Attivazione Plugin (Cliente)

```
Cliente installa plugin sul suo WordPress
    ↓
Admin → IPV Videos → Settings → License
    ↓
Inserisce license_key + clicca "Attiva"
    ↓
POST /wp-json/ipv-vendor/v1/license/activate
  Body: {
    license_key: "ABC123...",
    site_url: "https://cliente.com",
    site_name: "Il mio sito"
  }
    ↓
Server verifica:
  ✓ License exists?
  ✓ Status = active?
  ✓ Activations < max_activations?
    ↓
INSERT INTO wp_ipv_activations
    ↓
Response: {
  success: true,
  data: {
    plan: "starter",
    credits_remaining: 25,
    reset_date: "2025-01-01"
  }
}
    ↓
Plugin salva license_key in wp_options
    ↓
✅ Attivazione completata
```

### 3️⃣ Import Video (Utente finale)

```
Utente → Admin → IPV Videos → Importa Video
    ↓
Inserisce URL: https://youtube.com/watch?v=ABC123
    ↓
IPV_Prod_Simple_Import::import_video()
    ↓
Estrae video_id = "ABC123"
    ↓
IPV_Prod_Queue::enqueue(
  video_id = "ABC123",
  url = "https://youtube.com/watch?v=ABC123",
  source = "manual"
)
    ↓
INSERT INTO wp_ipv_prod_queue (
  video_id, url, status='pending', source='manual'
)
    ↓
✅ "Video aggiunto alla coda!"
```

### 4️⃣ Elaborazione Queue (CRON ogni 5 minuti)

```
System CRON: */5 * * * * wp ipv-prod queue run
    ↓
IPV_Prod_Queue::process_queue()
    ↓
SELECT * FROM wp_ipv_prod_queue
WHERE status='pending'
ORDER BY priority DESC, created_at ASC
LIMIT 3  -- Batch size
    ↓
Per ogni video:
    ↓
    [1] Download Metadata YouTube
        ↓
        IPV_Prod_API_Client::get_youtube_video_data("ABC123")
        ↓
        POST /wp-json/ipv-vendor/v1/youtube/video-data
        Headers: X-License-Key: ABC123...
        Body: { video_id: "ABC123" }
        ↓
        Server:
          1. Valida license
          2. Chiama YouTube Data API v3 (con chiave server)
          3. Response: { title, description, thumbnail, duration, views }
        ↓
        Plugin salva:
          - _ipv_yt_title
          - _ipv_yt_thumbnail_url
          - _ipv_yt_duration_seconds
          - _ipv_yt_view_count
    ↓
    [2] Genera Trascrizione (~20 secondi)
        ↓
        IPV_Prod_API_Client::get_transcript("ABC123")
        ↓
        POST /wp-json/ipv-vendor/v1/transcript
        Headers: X-License-Key: ABC123...
        Body: { video_id: "ABC123" }
        ↓
        Server:
          1. Valida license
          2. Verifica credits_remaining > 0
          3. Chiama SupaData API (rotazione chiavi)
          4. Deduce 1 credito
          5. Log API call
        ↓
        Response: { transcript: "Full transcript..." }
        ↓
        Plugin salva: _ipv_transcript
    ↓
    [3] Genera Descrizione AI (~12 secondi)
        ↓
        $golden_prompt = get_option('ipv_golden_prompt')
        ↓
        IPV_Prod_API_Client::generate_ai_description(
          title: "Video title",
          transcript: "Full transcript...",
          golden_prompt: $golden_prompt
        )
        ↓
        POST /wp-json/ipv-vendor/v1/description
        Headers: X-License-Key: ABC123...
        Body: { title, transcript, golden_prompt }
        ↓
        Server:
          1. Valida license
          2. Verifica credits (NO deduct - incluso in transcript)
          3. Chiama OpenAI API (GPT-4)
          4. Usa Golden Prompt personalizzato
        ↓
        Response: { description: "# Riassunto\n\n..." }
        ↓
        Plugin salva: _ipv_ai_description
    ↓
    [4] Estrai Metadata
        ↓
        IPV_Prod_AI_Enhancements::extract_and_save_metadata(post_id)
        ↓
        Analizza descrizione AI:
          - Cerca sezione 🗂️ ARGOMENTI → Crea/Assegna categorie
          - Cerca sezione 👤 OSPITI → Crea/Assegna relatori
          - Cerca hashtags → Crea/Assegna tag
    ↓
    [5] Pubblica Post
        ↓
        wp_update_post([
          'ID' => post_id,
          'post_status' => 'publish'
        ])
    ↓
    UPDATE wp_ipv_prod_queue
    SET status='done', processed_at=NOW()
    WHERE id=...
    ↓
    do_action('ipv_video_imported', post_id, video_id)
    ↓
    ✅ Video pubblicato!
```

### 5️⃣ Reset Crediti Mensile (CRON Server)

```
Server CRON: 0 2 1 * * (primo giorno mese, ore 02:00)
    ↓
wp cron event run ipv_vendor_reset_credits
    ↓
IPV_Vendor_Credits_Manager::reset_monthly_credits()
    ↓
UPDATE wp_ipv_licenses
SET credits_remaining = credits_total,
    reset_date = DATE_ADD(reset_date, INTERVAL 1 MONTH)
WHERE status = 'active'
  AND reset_date <= CURDATE()
    ↓
Email automatica ai clienti:
"I tuoi crediti sono stati ripristinati: 25/25"
    ↓
✅ Reset completato
```

---

## 🚀 Deployment

### Server Requirements

**Vendor Server (SaaS):**
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- WooCommerce 8.0+
- WooCommerce Subscriptions
- SSL Certificate (HTTPS obbligatorio)
- CRON job access

**Client Sites:**
- WordPress 6.0+
- PHP 7.4+ (8.0+ raccomandato)
- WP-CLI (opzionale, per system cron)

### Installation Steps

#### 1. Setup Vendor Server

```bash
# 1. Upload plugin
cd /var/www/html/wp-content/plugins/
unzip ipv-pro-vendor-v1.3.18.zip

# 2. Configure API keys
nano ipv-pro-vendor/includes/class-api-gateway.php
# Inserisci:
#   - YOUTUBE_API_KEY
#   - SUPADATA_API_KEY_1/2/3
#   - OPENAI_API_KEY

# 3. Activate plugin
wp plugin activate ipv-pro-vendor

# 4. Verify database tables created
wp db query "SHOW TABLES LIKE 'wp_ipv_%'"
# Dovrebbe mostrare 5 tabelle

# 5. Setup WooCommerce products
# Admin → Products → Add New
# Crea 4 prodotti: Trial, Starter, Professional, Business

# 6. Setup CRON
crontab -e
# Add:
0 2 1 * * /usr/bin/php /var/www/html/wp-cron.php > /dev/null 2>&1
```

#### 2. Configure Client Plugin

```bash
# 1. Upload plugin to client site
cd /var/www/client/wp-content/plugins/
unzip ipv-production-system-pro-v10.2.14.zip

# 2. Configure server URL
nano ipv-production-system-pro/includes/class-api-client.php
# Set: const SERVER_URL = 'https://your-vendor-server.com';

# 3. Activate plugin
wp plugin activate ipv-production-system-pro

# 4. Activate license
# Admin → IPV Videos → Settings → License
# Insert license_key from WooCommerce order

# 5. Setup system CRON (recommended)
crontab -e -u www-data
# Add:
*/5 * * * * wp ipv-prod queue run --path=/var/www/client --quiet
0 * * * *   wp ipv-prod youtube update --path=/var/www/client --quiet
*/30 * * * * wp ipv-prod cron ensure --path=/var/www/client --quiet
```

### Security Checklist

✅ **Vendor Server:**
- [ ] SSL certificate installed (HTTPS)
- [ ] API keys configured in `class-api-gateway.php`
- [ ] `.htaccess` protections in place
- [ ] WP Debug disabled in production
- [ ] Database regular backups
- [ ] File permissions: 644 (files), 755 (dirs)

✅ **Client Plugin:**
- [ ] Server URL configured correctly
- [ ] License activated
- [ ] WP-CLI available (for system cron)
- [ ] CRON jobs scheduled
- [ ] File permissions correct

### Monitoring

```bash
# Check server health
curl https://your-server.com/wp-json/ipv-vendor/v1/health

# Check client queue status
wp ipv-prod queue run --dry-run

# Check CRON schedules
wp cron event list | grep ipv

# Monitor API logs (server)
wp db query "SELECT * FROM wp_ipv_api_logs ORDER BY created_at DESC LIMIT 10"

# Check credits usage (server)
wp db query "SELECT license_key, plan, credits_remaining, credits_total FROM wp_ipv_licenses"
```

---

## 📊 Performance Metrics

### Processing Times (misurati)

- **Import singolo**: ~34 secondi
  - Metadata YouTube: ~2s
  - Trascrizione SupaData: ~20s
  - Descrizione AI OpenAI: ~12s
  - Estrazione metadata: <1s

- **System CRON overhead**: +5 secondi
  - Totale con system cron: ~39 secondi

### Throughput

- **WP CRON** (ogni 5 minuti): 12 video/ora (batch size 1)
- **System CRON** (ogni 5 minuti): 36 video/ora (batch size 3)

### Database Size Estimates

- **1000 video**: ~50MB
- **10000 video**: ~500MB
- **Queue table**: trascurabile (<1MB)

---

## 🔧 Troubleshooting

### Common Issues

**1. "License non valida"**
```bash
# Verifica license key
wp db query "SELECT * FROM wp_ipv_licenses WHERE license_key='ABC123'"

# Verifica attivazione
wp db query "SELECT * FROM wp_ipv_activations WHERE license_id=1"

# Test validate API
curl -X POST https://server.com/wp-json/ipv-vendor/v1/license/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"ABC123","site_url":"https://client.com"}'
```

**2. "No credits available"**
```bash
# Check credits
wp db query "SELECT credits_remaining FROM wp_ipv_licenses WHERE license_key='ABC123'"

# Manual reset (emergency)
wp db query "UPDATE wp_ipv_licenses SET credits_remaining=25 WHERE license_key='ABC123'"
```

**3. Queue non processa**
```bash
# Check CRON
wp cron event list | grep ipv_prod_process_queue

# Manual run
wp ipv-prod queue run

# Check queue status
wp db query "SELECT * FROM wp_ipv_prod_queue WHERE status='pending'"
```

**4. API errors**
```bash
# Check server logs
tail -f /var/log/apache2/error.log | grep ipv

# Check API logs
wp db query "SELECT * FROM wp_ipv_api_logs WHERE response_code != 200 ORDER BY created_at DESC LIMIT 20"
```

---

## 📈 Roadmap & Next Steps

### Suggested Improvements

1. **Performance**
   - [ ] Implement Redis cache for transcripts
   - [ ] Parallelize API calls where possible
   - [ ] Add CDN for thumbnails

2. **Features**
   - [ ] Multi-language transcripts
   - [ ] Video chapters extraction
   - [ ] Auto-posting to social media
   - [ ] Advanced analytics dashboard

3. **Security**
   - [ ] Rate limiting per license
   - [ ] IP whitelist for activations
   - [ ] 2FA for admin dashboard

4. **UX**
   - [ ] Real-time queue progress (WebSocket)
   - [ ] Mobile app for monitoring
   - [ ] Slack/Discord notifications

---

## 📝 Conclusioni

**IPV Production System Pro** è un sistema SaaS completo e ben architettato per la produzione automatizzata di contenuti video YouTube.

### Punti di Forza

✅ **Architettura client-server** sicura (API keys protette)
✅ **Sistema licenze robusto** con WooCommerce
✅ **Queue system efficiente** con CRON
✅ **Code quality** elevata (~23K righe ben organizzate)
✅ **Documentazione completa**
✅ **Multi-lingua** (6 lingue)
✅ **Estensibile** (hooks, filters, WP-CLI)

### Metriche Tecniche

- **67 file PHP** nel plugin client
- **17 file PHP** nel vendor server
- **~23.000 righe** di codice
- **5 tabelle database** server
- **1 tabella database** client (queue)
- **12+ REST API endpoints**
- **6 lingue** supportate
- **Performance**: 36 video/ora (system cron)

---

**Generato il:** 2025-12-14
**Versione documento:** 1.0
**Autore analisi:** Claude (Anthropic)
