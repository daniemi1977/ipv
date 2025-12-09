# 🎨 IPV Production System Pro - Client v10.0.16

**Data**: 9 Dicembre 2024
**Tipo**: YouTube Data API Fix
**Compatibilità**: Server v1.3.10

---

## ✅ MODIFICHE PRINCIPALI

### 1. 🔧 Fix YouTube Data API v3 - Formato Risposta Server

**Problema**:
I video importati mostravano solo l'ID invece del titolo reale. I dati strutturali del video (thumbnails, duration, views, likes) erano mancanti. La lista dei video risultava incompleta.

**Causa Root**:
Il server ha un endpoint `/youtube/video-data` funzionante che ritorna un formato custom:
```json
{
  "success": true,
  "video_data": {
    "video_id": "...",
    "title": "...",
    "thumbnail_default": "...",
    "duration": "PT1H30M",
    "view_count": 1234,
    ...
  }
}
```

Ma il client si aspettava il formato raw di YouTube API v3:
```json
{
  "items": [{
    "id": "...",
    "snippet": {...},
    "contentDetails": {...},
    "statistics": {...}
  }]
}
```

**Soluzione**:
- ✅ Modificato `class-youtube-api.php` line 64: controlla `$response['video_data']` invece di `$data['items'][0]`
- ✅ Aggiunto nuovo metodo `parse_server_video_data()` (lines 270-314)
- ✅ Converte formato custom server in formato interno atteso
- ✅ Gestisce tutti i campi metadata: title, thumbnails, duration, views, likes, comments

**Risultato**:
- ✅ Titoli video mostrano correttamente il titolo reale (non più l'ID)
- ✅ Thumbnails caricate correttamente (priorità: maxres > standard > high > medium > default)
- ✅ Durata video parsed da formato ISO 8601 (PT1H30M → 5400 secondi)
- ✅ Statistiche video (views, likes, comments) disponibili
- ✅ Lista video completa con tutti i metadati

---

## 📝 Modifiche Tecniche

### File: `class-youtube-api.php`

#### 1. Fix Response Check (Lines 53-76)

**Prima (v10.0.15)**:
```php
// v10.0.11 - SaaS: Call server API instead of using local API key
$api_client = IPV_Prod_API_Client::instance();
$response = $api_client->post( 'youtube/video-data', [
    'video_id' => $video_id
]);

if ( is_wp_error( $response ) ) {
    return $response;
}

$data = json_decode( $response['body'], true );

if ( empty( $data['items'][0] ) ) {
    return new WP_Error( 'ipv_youtube_not_found', 'Video non trovato o privato.' );
}

$item = $data['items'][0];
```

**Dopo (v10.0.16)**:
```php
// v10.0.16 - SaaS: Call server API instead of using local API key
$api_client = IPV_Prod_API_Client::instance();
$response = $api_client->get_youtube_video_data( $video_id );

if ( is_wp_error( $response ) ) {
    IPV_Prod_Logger::log( 'Errore YouTube API (server)', [ 'error' => $response->get_error_message() ] );
    return $response;
}

// v10.0.16 - Server returns custom format { success: true, video_data: {...} }
// NOT the raw YouTube API format with 'items'
if ( empty( $response['video_data'] ) ) {
    return new WP_Error( 'ipv_youtube_not_found', 'Video non trovato o privato.' );
}

$video_data = $response['video_data'];

// Convert server format to expected format
$result = self::parse_server_video_data( $video_data );
```

#### 2. Nuovo Metodo parse_server_video_data() (Lines 270-314)

```php
/**
 * Parse video data from server response format
 * v10.0.16 - Server returns flat format, convert to internal format
 */
protected static function parse_server_video_data( $video_data ) {
    // Parse duration
    $duration_seconds = self::parse_duration( $video_data['duration'] ?? 'PT0S' );

    // Build thumbnail URL and resolutions
    $thumbnail_url = '';
    $thumbnail_resolutions = [];

    // Priority: maxres > standard > high > medium > default
    foreach ( [ 'maxres', 'standard', 'high', 'medium', 'default' ] as $res ) {
        $key = 'thumbnail_' . $res;
        if ( ! empty( $video_data[ $key ] ) ) {
            if ( empty( $thumbnail_url ) ) {
                $thumbnail_url = $video_data[ $key ];
            }
            $thumbnail_resolutions[ $res ] = $video_data[ $key ];
        }
    }

    return [
        'video_id'            => $video_data['video_id'] ?? '',
        'title'               => $video_data['title'] ?? '',
        'description'         => $video_data['description'] ?? '',
        'published_at'        => $video_data['published_at'] ?? '',
        'channel_id'          => $video_data['channel_id'] ?? '',
        'channel_title'       => $video_data['channel_title'] ?? '',
        'tags'                => $video_data['tags'] ?? [],
        'category_id'         => $video_data['category_id'] ?? '',
        'thumbnail_url'       => $thumbnail_url,
        'thumbnail_resolutions' => $thumbnail_resolutions,
        'duration'            => $video_data['duration'] ?? '',
        'duration_seconds'    => $duration_seconds,
        'duration_formatted'  => self::format_duration( $duration_seconds ),
        'definition'          => $video_data['definition'] ?? '',
        'caption'             => $video_data['caption'] ?? 'false',
        'view_count'          => intval( $video_data['view_count'] ?? 0 ),
        'like_count'          => intval( $video_data['like_count'] ?? 0 ),
        'comment_count'       => intval( $video_data['comment_count'] ?? 0 ),
        'embed_html'          => '',
        'privacy_status'      => 'public',
    ];
}
```

---

## 🔄 Upgrade Path

### Da v10.0.15 → v10.0.16:

1. **Disattiva** v10.0.15
2. **Carica** `ipv-production-system-pro-v10.0.16-YOUTUBE-FIX.zip`
3. **Attiva** il plugin
4. **Test**:
   - Vai su IPV Videos → Importa Video
   - Inserisci un video ID YouTube
   - Click "Importa Video"
   - Verifica che:
     - ✅ Titolo video sia il titolo reale (non l'ID)
     - ✅ Thumbnail sia caricata correttamente
     - ✅ Durata video sia corretta
     - ✅ Statistiche (views, likes) siano presenti

---

## 🧪 Test

### Test 1: Import Singolo Video

1. Vai su IPV Videos → Importa Video
2. Inserisci video ID: `dQw4w9WgXcQ`
3. Click "Importa Video"
4. **Verifica**:
   - ✅ Titolo: "Rick Astley - Never Gonna Give You Up (Official Video)" (non `dQw4w9WgXcQ`)
   - ✅ Thumbnail presente e caricata
   - ✅ Durata: "3:33" (213 secondi)
   - ✅ Views, Likes, Comments presenti

### Test 2: Import da Coda

1. Aggiungi 3-5 video alla coda
2. Vai su IPV Videos → Coda
3. Click "Processa Ora"
4. **Verifica**:
   - ✅ Tutti i video processati con titoli corretti
   - ✅ Nessun errore nei log
   - ✅ Metadata completi per tutti i video

### Test 3: Import da Canale YouTube

1. Vai su IPV Videos → Importa → Tab "Da Canale"
2. Inserisci un Channel ID
3. Click "Importa dal Canale"
4. **Verifica**:
   - ✅ Video aggiunti alla coda
   - ✅ Processati automaticamente con titoli corretti
   - ✅ Thumbnails e metadata presenti

---

## ⚠️ Breaking Changes

**NESSUNO** - Completamente retrocompatibile.

- Gli utenti che hanno server v1.3.10 beneficiano del fix immediatamente
- Nessuna modifica richiesta lato server
- Formato risposta server rimane invariato

---

## 📊 Prima vs Dopo

| Aspetto | v10.0.15 | v10.0.16 |
|---------|----------|----------|
| **Titolo Video** | ❌ Mostra solo ID | ✅ Titolo reale |
| **Thumbnail** | ❌ Non caricata | ✅ Caricata correttamente |
| **Durata Video** | ❌ Mancante | ✅ Formattata (MM:SS) |
| **Views/Likes** | ❌ Mancanti | ✅ Presenti |
| **Lista Video Completa** | ❌ Incompleta | ✅ Tutti i metadata |
| **Compatibilità Server** | ✅ v1.3.10 | ✅ v1.3.10 |

---

## 🎯 Benefici

| Aspetto | Beneficio |
|---------|-----------|
| **UX Import Video** | ✅ Titoli leggibili invece di ID criptici |
| **Thumbnails** | ✅ Preview video corrette nell'elenco |
| **Statistiche** | ✅ Views/Likes visibili per decidere quali video importare |
| **Durata** | ✅ Informazione importante per gestione contenuti |
| **Affidabilità** | ✅ Dati completi per ogni video importato |

---

## 🎉 Risultato Finale

Dopo l'installazione di v10.0.16:

```
✅ Titoli video mostrano il titolo reale (non più l'ID)
✅ Thumbnails caricate correttamente
✅ Durata video formattata correttamente (MM:SS)
✅ Statistiche video (views, likes, comments) presenti
✅ Lista video completa con tutti i metadati
✅ Import singolo, da coda, da canale funzionano perfettamente
```

---

## 📥 Download

**File**: `ipv-production-system-pro-v10.0.16-YOUTUBE-FIX.zip`

**Link GitHub**:
```
https://github.com/daniemi1977/ipv/raw/claude/get-recent-uploads-01V9asSqzYj32qNmxnS6wSyY/ipv-production-system-pro-v10.0.16-YOUTUBE-FIX.zip
```

---

## 🆘 Troubleshooting

### Titoli Ancora Mostrano ID

**Verifica 1: Server Endpoint Disponibile**
1. Controlla server URL in IPV Videos → Impostazioni → Server
2. Deve essere: `https://aiedintorni.it`
3. Verifica licenza attiva

**Verifica 2: Licenza Attiva**
1. IPV Videos → Dashboard
2. Controlla status licenza: "Attiva"
3. Se non attiva, YouTube Data API non funzionerà

**Verifica 3: Log Errori**
```bash
tail -100 /wp-content/debug.log | grep "YouTube API"
```
Se vedi errori 401/403, problema di licenza.
Se vedi errori 500, problema server.

### Thumbnails Non Caricate

**Verifica 1: Permissions Upload**
1. Controlla permessi `/wp-content/uploads/`
2. Deve essere 755 e scrivibile da WordPress

**Verifica 2: Formato Thumbnail**
Il server ritorna 5 formati:
- `thumbnail_maxres` (1280x720) - preferito
- `thumbnail_standard` (640x480)
- `thumbnail_high` (480x360)
- `thumbnail_medium` (320x180)
- `thumbnail_default` (120x90)

Il plugin usa la risoluzione più alta disponibile.

---

**Versione**: 10.0.16
**Status**: ✅ PRONTO PER INSTALLAZIONE
**Autore**: IPV Team
**Repository**: https://github.com/daniemi1977/ipv
