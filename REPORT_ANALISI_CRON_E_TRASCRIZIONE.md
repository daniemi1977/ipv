# 📊 Report Analisi: Velocità Trascrizione/Descrizione CPT e Setup CRON

**Data Analisi:** 2025-12-11
**Versione Plugin:** IPV Production System Pro v10.2.12

---

## 🔍 PROBLEMI CRITICI TROVATI

### ❌ PROBLEMA 1: Import Singolo NON Usa la Coda di Produzione

**File:** `class-simple-import.php`
**Righe:** 87-216

**Descrizione:**
L'import singolo (tramite form "Import Video") **pubblica immediatamente** il video **SENZA** passare dalla coda di produzione. Questo significa che:

- ✅ Il video viene creato e pubblicato subito
- ❌ **NON viene generata la trascrizione** automaticamente
- ❌ **NON viene generata la descrizione AI** automaticamente
- ⚠️ L'utente deve generare manualmente trascrizione e descrizione dopo l'import

**Codice Problematico:**
```php
// class-simple-import.php:165-168
$post_data = [
    'post_type'   => 'ipv_video',
    'post_status' => 'publish',  // ← PUBBLICA SUBITO!
    'post_title'  => $video_data['title'] ?? 'Video YouTube ' . $video_id,
    'post_content' => $video_data['description'] ?? '',
];

$post_id = wp_insert_post( $post_data );
// ← NON chiama mai IPV_Prod_Queue::enqueue()!
```

**Soluzione Raccomandata:**
Modificare `class-simple-import.php` per aggiungere il video alla coda invece di pubblicarlo subito:

```php
// INVECE DI wp_insert_post() direttamente:
IPV_Prod_Queue::enqueue( $video_id, $url, 'manual' );
```

---

### ⚠️ PROBLEMA 2: AI Queue Troppo Veloce (Ogni 1 Minuto)

**File:** `class-ai-queue.php`
**Righe:** 10-12, 23-38

**Descrizione:**
La coda AI viene processata **ogni 1 minuto** e processa **solo 1 video alla volta**. Questo causa:

- ⚡ **Troppi cron job** (60 esecuzioni/ora invece di 12)
- 🐌 **Processamento lento** (1 video/minuto)
- 💸 **Possibile sovraccarico server** (chiamate API troppo frequenti)

**Codice Problematico:**
```php
// class-ai-queue.php:10-12
if (!wp_next_scheduled('ipv_ai_queue_runner')) {
    wp_schedule_event(time()+60, 'minute', 'ipv_ai_queue_runner');
    //                            ^^^^^^ = OGNI 1 MINUTO!
}

// class-ai-queue.php:23-38
public static function run(){
    $q = get_option(self::OPTION_KEY, []);
    if(empty($q)) return;

    $post_id = array_shift($q); // ← SOLO 1 VIDEO ALLA VOLTA!
    update_option(self::OPTION_KEY,$q);

    // Genera descrizione AI...
}
```

**Velocità Attuale:**
- ⏱️ 1 video ogni 1 minuto = **60 video/ora**
- 🔄 60 esecuzioni cron/ora

**Velocità Raccomandata:**
- ⏱️ 3 video ogni 5 minuti = **36 video/ora** (simile alla coda principale)
- 🔄 12 esecuzioni cron/ora

**Soluzione Raccomandata:**
```php
// Cambia da 'minute' a 'ipv_every_5_minutes'
if (!wp_next_scheduled('ipv_ai_queue_runner')) {
    wp_schedule_event(time()+60, 'ipv_every_5_minutes', 'ipv_ai_queue_runner');
}

// Processa 3 video invece di 1
public static function run(){
    $q = get_option(self::OPTION_KEY, []);
    if(empty($q)) return;

    $batch_size = 3;
    for ($i = 0; $i < $batch_size && !empty($q); $i++) {
        $post_id = array_shift($q);
        // Processa video...
    }
    update_option(self::OPTION_KEY,$q);
}
```

---

## ✅ CONFIGURAZIONE CRON CORRETTA (Coda Principale)

**File:** `ipv-production-system-pro.php`
**File:** `class-queue.php`

### Setup CRON Principale

```php
// ipv-production-system-pro.php:655-662
public function ensure_cron_scheduled() {
    // Coda principale: ogni 5 minuti
    if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
        wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
    }

    // Aggiornamento dati YouTube: ogni ora
    if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
        wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
    }
}
```

### Processing Batch

```php
// class-queue.php:85-95
protected static function get_pending_jobs( $limit = 3 ) {
    // ← CORRETTO: Prende 3 video alla volta
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at ASC LIMIT %d",
            'pending',
            $limit
        )
    );
}
```

**Velocità Attuale (Coda Principale):**
- ⏱️ 3 video ogni 5 minuti = **36 video/ora**
- 🔄 12 esecuzioni cron/ora
- ✅ **CONFIGURAZIONE OTTIMALE**

---

## 📋 PROCESSO COMPLETO (Quando Funziona Correttamente)

### Import tramite Coda (RSS, Bulk, ecc.)

```
1. Video aggiunto alla coda ← class-queue.php:enqueue()
   ↓
2. CRON ogni 5 minuti ← ipv_prod_process_queue
   ↓
3. Processa 3 video ← class-queue.php:process_queue()
   ↓
4. Per ogni video:
   a. Crea post WordPress (publish)
   b. Scarica metadata YouTube
   c. Genera TRASCRIZIONE ← IPV_Prod_Supadata::get_transcript()
   d. Genera DESCRIZIONE AI ← IPV_Prod_AI_Generator::generate_and_save()
   e. Estrae hashtag e relatori
   ↓
5. Video completo pubblicato ✅
```

**Tempo totale per video:** ~5-10 minuti (include trascrizione + AI)

---

### Import Singolo (ATTUALE - NON FUNZIONA BENE)

```
1. Form "Import Video" ← class-simple-import.php
   ↓
2. Crea e PUBBLICA SUBITO il video ❌
   ↓
3. NO trascrizione ❌
4. NO descrizione AI ❌
5. Video pubblicato SENZA contenuto elaborato ⚠️
```

**Tempo totale:** Immediato (ma senza trascrizione/AI)

---

## 🔧 INTERVALLI CRON CONFIGURATI

```php
// ipv-production-system-pro.php:343-353
$schedules['ipv_every_5_minutes'] = [
    'interval' => 5 * MINUTE_IN_SECONDS,  // = 300 secondi
    'display'  => 'Every 5 Minutes',
];

$schedules['ipv_every_15_minutes'] = [
    'interval' => 15 * MINUTE_IN_SECONDS, // = 900 secondi
    'display'  => 'Every 15 Minutes',
];
```

### Cron Job Attivi

| Job | Frequenza | Azione | File |
|-----|-----------|--------|------|
| `ipv_prod_process_queue` | Ogni 5 minuti | Processa coda principale (3 video) | `class-queue.php:98` |
| `ipv_ai_queue_runner` | ❌ Ogni 1 minuto | Processa AI queue (1 video) | `class-ai-queue.php:23` |
| `ipv_prod_update_youtube_data` | Ogni ora | Aggiorna dati YouTube | `class-queue.php:660` |

---

## 🎯 RACCOMANDAZIONI

### 1. FIX IMMEDIATO: Coda AI Ogni 5 Minuti

**File da modificare:** `class-ai-queue.php:11`

```php
// DA:
wp_schedule_event(time()+60, 'minute', 'ipv_ai_queue_runner');

// A:
wp_schedule_event(time()+60, 'ipv_every_5_minutes', 'ipv_ai_queue_runner');
```

### 2. FIX CRITICO: Import Singolo Usa Coda

**File da modificare:** `class-simple-import.php:90-216`

**OPZIONE A - Usa la coda (raccomandato):**
```php
public static function import_video( $url ) {
    $video_id = IPV_Prod_Helpers::extract_youtube_id( $url );

    if ( ! $video_id ) {
        return new WP_Error( 'invalid_url', __( 'Invalid YouTube URL', 'ipv-production-system-pro' ) );
    }

    $existing = IPV_Prod_Helpers::video_exists( $video_id );
    if ( $existing ) {
        return new WP_Error( 'duplicate', sprintf( __( 'Video already imported (ID: %d)', 'ipv-production-system-pro' ), $existing ) );
    }

    // Aggiungi alla coda invece di creare subito
    IPV_Prod_Queue::enqueue( $video_id, $url, 'manual' );

    // Triggera processing immediato (entro 5 secondi)
    wp_schedule_single_event( time() + 5, 'ipv_prod_process_queue' );

    return true; // oppure ritorna job_id
}
```

**OPZIONE B - Aggiungi trascrizione/AI manualmente:**
```php
// Dopo wp_insert_post() alla riga 178, aggiungi:

// Genera trascrizione
$mode = get_option( 'ipv_transcript_mode', 'auto' );
$transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );
if ( ! is_wp_error( $transcript ) ) {
    update_post_meta( $post_id, '_ipv_transcript', $transcript );
}

// Genera descrizione AI
$desc = IPV_Prod_AI_Generator::generate_and_save( $post_id );
```

### 3. MIGLIORAMENTO: AI Queue Batch di 3

**File da modificare:** `class-ai-queue.php:23-38`

```php
public static function run(){
    $q = get_option(self::OPTION_KEY, []);
    if(empty($q)) return;

    $batch_size = 3; // Processa 3 video invece di 1

    for ($i = 0; $i < $batch_size && !empty($q); $i++) {
        $post_id = array_shift($q);
        update_option(self::OPTION_KEY,$q);

        $title = get_the_title($post_id);
        $trans = get_post_meta($post_id, '_ipv_transcript', true);
        if($trans){
            $ai = IPV_Prod_AI_Generator::generate_description($title,$trans);
            if(!is_wp_error($ai)){
                update_post_meta($post_id,'_ipv_ai_description',$ai);
            }
        }
    }
}
```

---

## 📊 VELOCITÀ CONFRONTO

### Configurazione Attuale

| Componente | Frequenza | Video/Batch | Video/Ora | Efficienza |
|------------|-----------|-------------|-----------|------------|
| Coda Principale | 5 min | 3 | 36 | ✅ OTTIMA |
| AI Queue | 1 min | 1 | 60 | ❌ TROPPO VELOCE |
| Import Singolo | Immediato | - | - | ❌ NO PROCESSING |

### Configurazione Raccomandata

| Componente | Frequenza | Video/Batch | Video/Ora | Efficienza |
|------------|-----------|-------------|-----------|------------|
| Coda Principale | 5 min | 3 | 36 | ✅ OTTIMA |
| AI Queue | 5 min | 3 | 36 | ✅ BILANCIATA |
| Import Singolo | Via coda | 1 | - | ✅ COMPLETO |

---

## 🔍 FILE ANALIZZATI

1. ✅ `ipv-production-system-pro.php` (v10.2.12)
2. ✅ `class-ai-queue.php`
3. ✅ `class-simple-import.php` (v9.1.0)
4. ✅ `class-queue.php`

---

## 📝 NOTE AGGIUNTIVE

### Import Manuale - Processing Immediato

Nel file `class-queue.php:61-66` c'è già un meccanismo per processing immediato:

```php
if ( $source === 'manual' ) {
    // Schedula processing immediato (entro 5 secondi)
    wp_schedule_single_event( time() + 5, 'ipv_prod_process_queue' );
    IPV_Prod_Logger::log( 'Processing immediato schedulato', [ 'video_id' => $video_id ] );
}
```

✅ **Questo è OTTIMO** - Ma funziona SOLO se `class-simple-import.php` usa `IPV_Prod_Queue::enqueue()` invece di `wp_insert_post()` diretto.

### Video in Premiere

Il sistema gestisce correttamente i video in premiere/programmazione:

```php
// class-queue.php:237-256
if ( $duration_seconds === 0 ) {
    update_post_meta( $post_id, '_ipv_premiere_pending', 'yes' );
    self::mark_as_skipped( $job->id, 'Video in premiere/programmazione' );
    return;
}
```

✅ **Gestione corretta** - I video vengono ri-processati quando disponibili.

---

## 🚀 PRIORITÀ IMPLEMENTAZIONE

1. **ALTA PRIORITÀ** - FIX Import Singolo (usa coda o aggiungi processing manuale)
2. **MEDIA PRIORITÀ** - FIX AI Queue frequenza (da 1 min a 5 min)
3. **BASSA PRIORITÀ** - MIGLIORAMENTO AI Queue batch (da 1 a 3 video)

---

**Fine Report**
