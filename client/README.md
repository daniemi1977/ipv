# IPV Production System Pro v1.0

> Sistema professionale per gestione video YouTube con AI

![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)

## Panoramica

IPV Production System Pro e' il plugin client definitivo per content creator YouTube. Importa video, genera trascrizioni automatiche, crea descrizioni ottimizzate con AI e presenta i contenuti con un bellissimo Video Wall.

## Funzionalita' Principali

### Import Multi-Sorgente
- Import singolo video da URL
- Import bulk (lista URL)
- Import da canale YouTube
- Import automatico da RSS feed
- Supporto YouTube, Vimeo, Dailymotion

### Trascrizioni Automatiche
- Trascrizione AI di alta qualita'
- Supporto multi-lingua
- Capitoli automatici con timestamp
- Estrazione argomenti e ospiti

### Descrizioni AI
- Generazione automatica con GPT-4
- Golden Prompt personalizzabile
- 18 sezioni configurabili
- Hashtag strategici
- Link social integrati

### Video Wall
- Layout responsive (grid, masonry, slider)
- Filtri AJAX in tempo reale
- Ricerca avanzata
- Paginazione infinita
- Animazioni moderne
- Widget Elementor

### Sicurezza
- Encryption AES-256 per Golden Prompt
- Comunicazione HTTPS con vendor
- License key binding per dominio

## Shortcode Disponibili

```
[ipv_video_wall]              - Video wall completo con filtri
[ipv_coming_soon]             - Video in anteprima/premiere
[ipv_video id="123"]          - Player singolo responsive
[ipv_grid limit="12"]         - Griglia video semplice
[ipv_search]                  - Form ricerca avanzata
[ipv_stats]                   - Box statistiche
```

## Requisiti

- WordPress 6.0+
- PHP 8.0+
- Licenza attiva IPV Pro
- Connessione al server vendor

## Installazione

1. **Upload**: Carica il file ZIP in WordPress > Plugin > Aggiungi nuovo
2. **Attiva**: Attiva il plugin
3. **Licenza**: Vai su IPV Videos > Licenza e inserisci la tua license key
4. **Configura**: Vai su IPV Videos > Impostazioni per personalizzare

## Configurazione Licenza

1. Acquista una licenza su aiedintorni.it
2. Vai su IPV Videos > Licenza
3. Inserisci la license key (formato: XXXXX-XXXXX-XXXXX)
4. Clicca "Attiva Licenza"

## Golden Prompt

1. Vai su IPV Videos > AI & Prompt
2. Compila i dati del tuo canale:
   - Nome canale e handle YouTube
   - Link social (Telegram, Facebook, Instagram, ecc.)
   - Sponsor (opzionale)
3. Seleziona le sezioni da includere (18 checkbox)
4. Clicca "Genera Golden Prompt"

## Crediti e Piani

I crediti vengono consumati per:
- Trascrizione video: 1 credito
- Generazione descrizione AI: 1 credito

| Piano | Crediti/Anno | Prezzo |
|-------|--------------|--------|
| Trial | 5 gratis | Gratis |
| Basic | 300 | 110 EUR/anno |
| Pro | 600 | 220 EUR/anno |
| Business | 1200 | 330 EUR/anno |

## Meta Keys Reference

| Costante | Meta Key | Descrizione |
|----------|----------|-------------|
| `META_VIDEO_ID` | `_ipv_video_id` | YouTube video ID |
| `META_TRANSCRIPT` | `_ipv_transcript` | Trascrizione video |
| `META_AI_DESCRIPTION` | `_ipv_ai_description` | Descrizione AI |
| `META_YT_DURATION_SEC` | `_ipv_yt_duration_seconds` | Durata in secondi |
| `META_YT_VIEW_COUNT` | `_ipv_yt_view_count` | Visualizzazioni |

## Hooks & Filters

```php
// Dopo import video
do_action( 'ipv_video_imported', $post_id, $video_id );

// Modifica AI prompt
add_filter( 'ipv_golden_prompt', function( $prompt, $title, $transcript ) {
    return $prompt . "\n\nIstruzioni extra...";
}, 10, 3 );
```

## Changelog

### v1.0.0 (2025-12-20)
- Prima release commerciale stabile
- Import multi-sorgente (YouTube, Vimeo, Dailymotion)
- Trascrizioni AI automatiche
- Descrizioni con Golden Prompt (18 sezioni)
- Video Wall responsive con filtri AJAX
- Widget Elementor
- Encryption AES-256 per sicurezza
- Multi-lingua (7 lingue)
- Setup Wizard guidato

## Supporto

- Documentazione: https://ipv-production-system.com/docs
- Email: support@ipv-production-system.com
- GitHub: https://github.com/daniemi1977/ipv/issues

## Licenza

GPL v2 or later

---

Made with love by IPV Team
