# IPV Production System Pro v5.0

**Sistema di produzione avanzato per "Il Punto di Vista"**

![Version](https://img.shields.io/badge/version-5.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)

---

## 🎉 Novità v5.0 - MAJOR UPDATE!

### ✨ Tassonomie Intelligenti con Auto-Popolamento
- **Relatori**: Identifica automaticamente relatori e ospiti da titolo e descrizione
- **Argomenti**: Categorizza automaticamente per tematica (UFO, Spiritualità, Esoterismo, ecc.)
- **Anno**: Filtra video per anno di pubblicazione
- **Auto-Tagger**: Popola automaticamente le tassonomie analizzando il contenuto

### 🎨 Integrazione Elementor Completa
- Widget Elementor personalizzati per video
- Compatibilità con temi **Influencers** e **WoodMart**
- Editing visuale con Elementor

### 🌐 Video Wall con Filtri Avanzati
- Filtri per Anno, Relatore, Argomento
- Ricerca testuale live
- Paginazione AJAX
- Layout responsive (2/3/4 colonne)
- Shortcode: `[ipv_video_wall]`

### 🤖 Cron Manager Migliorato
- **Download Automatico**: RSS auto-import configurabile
- **Trascrizione Automatica**: Trova video senza trascrizione e la genera
- **Generazione SEO Automatica**: Crea descrizioni AI per video con trascrizione
- Dashboard cron con stato e controlli

### 📝 Golden Prompt v5 con Separatori Uniformi
- Separatori uniformi: `━━━━━━━━━━━━━━━━━━━━━`
- Prompt ottimizzato 350+ righe
- Struttura coerente per tutte le sezioni
- Output professionale e copy-paste ready

---

## 📋 Funzionalità Principali

### Importazione Video
- **Singola**: Importa video YouTube manualmente
- **Bulk Import**: Importa multipli video dal canale
- **RSS Auto-Import**: Monitora feed RSS per nuovi video

### Elaborazione AI
- **Trascrizione**: SupaData API con rotazione multi-key
- **Descrizione AI**: OpenAI GPT-4o con Golden Prompt
- **Auto-Tagging**: Identificazione automatica relatori e argomenti

### Gestione Contenuti
- Custom Post Type `video_ipv`
- Tassonomie: Relatori, Argomenti, Anno
- Meta boxes completi per dati YouTube
- Colonne admin personalizzate

### Frontend
- Video Wall con filtri avanzati
- Widget Elementor (Video Player, Grid, Slider)
- Template single-video personalizzabile
- Compatibilità temi Influencers/WoodMart

---

## 🚀 Installazione

1. Scarica il file ZIP del plugin
2. Vai in WordPress → Plugin → Aggiungi Nuovo → Carica Plugin
3. Seleziona il file ZIP e clicca "Installa Ora"
4. Attiva il plugin
5. Vai in **IPV Production → Impostazioni** e configura le API

---

## ⚙️ Configurazione API

### YouTube Data API v3
1. Google Cloud Console → Crea progetto
2. Abilita "YouTube Data API v3"
3. Crea credenziali API Key
4. Inserisci in Impostazioni plugin

### SupaData API
1. Registrati su [supadata.ai](https://supadata.ai)
2. Ottieni API key
3. Inserisci in Impostazioni (supporta multi-key, una per riga)

### OpenAI API
1. Vai su [platform.openai.com](https://platform.openai.com)
2. Crea API key
3. Inserisci in Impostazioni

---

## 📦 Shortcode e Widget

### Shortcode Video Wall

```php
[ipv_video_wall per_page="12" columns="3" show_filters="yes"]
```

**Parametri:**
- `per_page`: Video per pagina (default: 12)
- `columns`: Numero colonne 2/3/4 (default: 3)
- `show_filters`: Mostra filtri yes/no (default: yes)

### Widget Elementor

1. **IPV Video Player**: Embed singolo video YouTube
2. **IPV Video Grid**: Griglia video con filtri
3. **IPV Video Slider**: Slider video automatico

---

## 🎯 Tassonomie

### Relatori (`ipv_relatore`)
Identifica automaticamente:
- Nomi nel formato "Nome Cognome" nei titoli
- Pattern "con", "ospite", "intervista a"
- Titoli accademici (Dr., Prof., Dott.)

### Argomenti (`ipv_argomento`)
Categorie automatiche:
- UFO, Disclosure, Extraterrestri
- Spiritualità, Meditazione, Consapevolezza
- Esoterismo, Alchimia, Tarocchi
- Geopolitica, Economia, Storia
- E altro...

### Anno (`ipv_anno`)
Auto-popolato dalla data di pubblicazione YouTube

---

## 🔄 Gestione Cron

Il plugin gestisce automaticamente 4 cron job:

| Cron | Frequenza | Descrizione |
|------|-----------|-------------|
| **Process Queue** | Ogni minuto | Elabora video in coda |
| **RSS Import** | Configurabile | Auto-import da feed RSS |
| **Auto-Transcribe** | Ogni 15 min | Genera trascrizioni mancanti |
| **Auto-Generate Desc** | Ogni 15 min | Crea descrizioni AI mancanti |

Tutti i cron sono visibili e controllabili dalla Dashboard.

---

## 🎨 Compatibilità Temi

### Influencers Theme
- Supporto completo Elementor
- Stili ottimizzati per layout magazine
- Video wall integrato nella homepage

### WoodMart Theme
- Widget WPBakery compatibili
- Integrazione con shop layout
- Sidebar video personalizzabile

### Temi Custom
Il plugin include template fallback per qualsiasi tema WordPress.

---

## 📊 Metadati Salvati

Ogni video salva automaticamente:

- `_ipv_video_id`: YouTube Video ID
- `_ipv_youtube_url`: URL completo
- `_ipv_yt_title`: Titolo originale YouTube
- `_ipv_yt_description`: Descrizione originale
- `_ipv_yt_published_at`: Data pubblicazione
- `_ipv_yt_channel_title`: Nome canale
- `_ipv_yt_tags`: Array tag YouTube
- `_ipv_yt_thumbnail_url`: URL thumbnail
- `_ipv_yt_duration_formatted`: Durata (HH:MM:SS)
- `_ipv_yt_view_count`: Visualizzazioni
- `_ipv_yt_like_count`: Like
- `_ipv_yt_comment_count`: Commenti
- `_ipv_transcript`: Trascrizione completa
- `_ipv_ai_description`: Descrizione AI generata
- `_ipv_source`: Fonte (manual/rss/bulk)

---

## 🛠️ Struttura File

```
ipv-production-system-pro-v5/
├── ipv-production-system-pro.php   # File principale
├── README.md
├── assets/
│   ├── css/
│   │   ├── admin.css               # Stili admin
│   │   └── video-wall.css          # Stili video wall
│   └── js/
│       ├── admin.js                # Script admin
│       └── video-wall.js           # Script video wall
├── includes/
│   ├── class-ai-generator.php      # OpenAI + Golden Prompt v5
│   ├── class-auto-tagger.php       # Auto-popolamento tassonomie
│   ├── class-bulk-import.php       # Import massivo
│   ├── class-cpt.php               # Custom Post Type
│   ├── class-cron-manager.php      # Gestione cron migliorata
│   ├── class-logger.php            # Logging
│   ├── class-queue.php             # Coda elaborazione
│   ├── class-rss-importer.php      # Auto-import RSS
│   ├── class-settings.php          # Impostazioni
│   ├── class-supadata.php          # SupaData API
│   ├── class-taxonomies.php        # Tassonomie migliorate
│   ├── class-theme-compat.php      # Compatibilità temi
│   ├── class-video-list-columns.php
│   ├── class-video-wall.php        # Video wall con filtri
│   ├── class-youtube-api.php       # YouTube Data API
│   └── class-youtube-importer.php
├── elementor/
│   ├── class-elementor-integration.php
│   └── widgets/
│       ├── video-grid-widget.php
│       ├── video-player-widget.php
│       └── video-slider-widget.php
└── templates/
    ├── dashboard.php               # Template dashboard
    └── single-video_ipv.php        # Template singolo video
```

---

## 🔧 Requisiti

- WordPress 5.8+
- PHP 7.4+
- API Keys: SupaData, OpenAI, YouTube Data API v3
- Temi supportati: Qualsiasi tema WordPress (ottimizzato per Influencers e WoodMart)
- Plugin consigliati: Elementor (opzionale)

---

## 📝 Changelog

### v5.0.0 (Novembre 2024)
- ✨ Tassonomie intelligenti (Relatori, Argomenti, Anno)
- 🤖 Auto-Tagger con AI per popolamento automatico
- 🌐 Video Wall con filtri avanzati (anno, relatore, argomento)
- 🎨 Integrazione Elementor con 3 widget
- 🔄 Cron Manager migliorato (4 cron automatici)
- 📝 Golden Prompt v5 con separatori uniformi
- 🎯 Compatibilità temi Influencers e WoodMart
- 🚀 Performance ottimizzate

### v4.5 (Precedente)
- Bulk Import e YouTube Data API completa

---

## 💡 Utilizzo

### 1. Importa Video

**Manuale:**
IPV Production → Importa Video → Inserisci URL YouTube

**Automatico:**
IPV Production → Auto-Import RSS → Configura feed e frequenza

### 2. Video Wall in Homepage

Aggiungi shortcode in una pagina:

```
[ipv_video_wall per_page="12" columns="3" show_filters="yes"]
```

**Oppure con Elementor:**
Trascina il widget "IPV Video Grid" nella pagina

### 3. Gestione Tassonomie

Le tassonomie si popolano automaticamente, ma puoi:
- Modificarle manualmente nell'editor del video
- Aggiungere nuovi termini dalla sidebar
- Filtrare video per tassonomia nell'admin

### 4. Monitoraggio Cron

IPV Production → Dashboard → Vedi stato cron in tempo reale

---

## 🆘 Supporto

Per supporto e segnalazione bug:
- Email: info@ilpuntodivista.it
- Website: https://www.ilpuntodivista.it

---

## 👨‍💻 Autore

**Daniele / IPV**
- Il Punto di Vista Official
- Made with ❤️ for truth seekers

---

## 📄 Licenza

Proprietario - IPV Production System Pro
Copyright © 2024 Il Punto di Vista

---

**Il Punto di Vista** - *La verità oltre l'apparenza*
