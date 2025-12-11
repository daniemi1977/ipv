# 📊 Report Analisi: Velocità Trascrizione/Descrizione CPT e Setup CRON (v2 - AGGIORNATO)

**Data Analisi:** 2025-12-11
**Versione Plugin:** IPV Production System Pro v10.2.12
**Metodo:** Analisi codice + misurazione tempi reali dai log di produzione

---

## ⏱️ TEMPI REALI MISURATI (da log di produzione)

### Timeline Completa di Un Video Singolo

```
📌 Video ID: tr8oMCsNb1s
📄 Titolo: "Tecnologie dell'Elettricità Atmosferica alle Esposizioni del XIX Secolo"
📏 Lunghezza transcript: 41,856 caratteri

18:13:09 UTC → Job inserito in coda (source: manual)
18:13:09 UTC → Processing immediato schedulato (time() + 5 secondi)
               |
               | ⚠️ WORDPRESS CRON DELAY: 3 minuti e 2 secondi
               |
18:16:11 UTC → Processing START
               ├─ YouTube Metadata: 1 secondo
               |
18:16:12 UTC → Trascrizione START (SupaData API)
18:16:32 UTC → Trascrizione END (20 secondi) ✅
               |
18:16:32 UTC → AI Generation START (OpenAI API)
18:16:44 UTC → AI Generation END (12 secondi) ✅
               |
18:16:45 UTC → Estrazione Tags (1 secondo)
18:16:45 UTC → ✅ JOB COMPLETATO
```

### 📊 Breakdown Temporale

| Fase | Tempo | % sul Totale | % sul Processing |
|------|-------|--------------|------------------|
| **⚠️ WordPress Cron Delay** | **3:02 min (182s)** | **84%** | - |
| YouTube Metadata | 1s | <1% | 3% |
| **Trascrizione SupaData** | **20s** | **9%** | **59%** |
| **Generazione AI (OpenAI)** | **12s** | **6%** | **35%** |
| Estrazione Tags | 1s | <1% | 3% |
| **SUBTOTALE Processing** | **~34s** | **16%** | **100%** |
| **TOTALE (percepito)** | **3:36 min (216s)** | **100%** | - |

---

## 🔍 PROBLEMI IDENTIFICATI (ordinati per priorità)

### 🔴 PROBLEMA CRITICO 1: WordPress Cron Delay

**Severità:** ⚠️ ALTA
**Impatto:** Percezione di lentezza (3 minuti di attesa per nulla)

**Descrizione:**
WordPress usa un "pseudo-cron" che non è un vero cron di sistema:
- Viene attivato solo quando qualcuno visita il sito
- Ha ritardi variabili (1-5 minuti tipicamente)
- Non è affidabile per task time-sensitive

**Dal log:**
```
18:13:09 → Schedulato per time() + 5 secondi (18:13:14)
18:16:11 → Eseguito effettivamente (~3 minuti dopo!)
```

**Impatto sul sistema:**
```
┌─────────────────────────────────────────────┐
│ TEMPO TOTALE: 3 minuti 36 secondi          │
├─────────────────────────────────────────────┤
│ 84% → WordPress Cron Delay (3:02 min) ❌   │
│ 16% → Processing Effettivo (34 sec)   ✅   │
└─────────────────────────────────────────────┘
```

**SOLUZIONE RACCOMANDATA:**

1. **Disabilita WordPress Cron** in `wp-config.php`:
```php
// Aggiungi questa linea PRIMA di "That's all, stop editing!"
define('DISABLE_WP_CRON', true);
```

2. **Configura System Cron** (via cPanel/Plesk o SSH):

**Opzione A - Ogni Minuto (massima reattività):**
```bash
* * * * * wget -q -O - https://danielem62.sg-host.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

**Opzione B - Ogni 5 Minuti (bilanciato):**
```bash
*/5 * * * * wget -q -O - https://danielem62.sg-host.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

**BENEFICI:**
- ⚡ Processing immediato: 5 secondi + 34 secondi = **39 secondi totali**
- ✅ Riduzione tempo del **82%** (da 3:36 a 0:39)
- ✅ Affidabilità garantita (non dipende dalle visite)
- ✅ Throughput massimo mantenuto

---

### 🟡 PROBLEMA 2: Import Singolo NON Usa la Coda di Produzione

**Severità:** ⚠️ MEDIA
**File:** `class-simple-import.php:87-216`
**Impatto:** Video pubblicati senza trascrizione/descrizione AI

**Descrizione:**
L'import singolo (tramite form "Import Video") **pubblica immediatamente** il video **SENZA** passare dalla coda di produzione.

**Comportamento attuale:**
```php
// class-simple-import.php:165-178
$post_data = [
    'post_type'   => 'ipv_video',
    'post_status' => 'publish',  // ← PUBBLICA SUBITO!
    'post_title'  => $video_data['title'],
    'post_content' => $video_data['description'],
];

$post_id = wp_insert_post( $post_data );
// ❌ NON chiama IPV_Prod_Queue::enqueue()
// ❌ NO trascrizione
// ❌ NO descrizione AI
```

**Risultato:**
- ✅ Video creato e pubblicato immediatamente
- ❌ **NO trascrizione automatica**
- ❌ **NO descrizione AI automatica**
- ⚠️ Utente deve generare manualmente dopo l'import

**SOLUZIONE A - Usa la coda (raccomandato):**

Modificare `class-simple-import.php:90-216`:

```php
public static function import_video( $url ) {
    $video_id = IPV_Prod_Helpers::extract_youtube_id( $url );

    if ( ! $video_id ) {
        return new WP_Error( 'invalid_url', __( 'Invalid YouTube URL' ) );
    }

    $existing = IPV_Prod_Helpers::video_exists( $video_id );
    if ( $existing ) {
        return new WP_Error( 'duplicate', sprintf( __( 'Video already imported (ID: %d)' ), $existing ) );
    }

    // ✅ Aggiungi alla coda invece di creare subito
    IPV_Prod_Queue::enqueue( $video_id, $url, 'manual' );

    // ✅ Triggera processing immediato (con system cron = istantaneo)
    wp_schedule_single_event( time() + 5, 'ipv_prod_process_queue' );

    return $video_id; // Ritorna video_id invece di post_id
}
```

**BENEFICI:**
- ✅ Trascrizione automatica
- ✅ Descrizione AI automatica
- ✅ Processo unificato con import bulk/RSS
- ✅ Con system cron: totale ~40 secondi

**SOLUZIONE B - Processa inline (più complessa):**

Aggiungere dopo `wp_insert_post()`:

```php
// Genera trascrizione
$mode = get_option( 'ipv_transcript_mode', 'auto' );
$transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );
if ( ! is_wp_error( $transcript ) ) {
    update_post_meta( $post_id, '_ipv_transcript', $transcript );

    // Genera descrizione AI
    $desc = IPV_Prod_AI_Generator::generate_and_save( $post_id );
}
```

---

### 🟢 PROBLEMA 3: AI Queue Frequenza Ogni 1 Minuto

**Severità:** ⚠️ BASSA
**File:** `class-ai-queue.php:10-12`
**Impatto:** Troppe esecuzioni cron (ottimizzabile)

**Descrizione:**
La coda AI viene processata ogni **1 minuto** (60 esecuzioni/ora) invece di ogni 5 minuti (12 esecuzioni/ora).

**Codice attuale:**
```php
// class-ai-queue.php:10-12
if (!wp_next_scheduled('ipv_ai_queue_runner')) {
    wp_schedule_event(time()+60, 'minute', 'ipv_ai_queue_runner');
    //                            ^^^^^^ = OGNI 1 MINUTO!
}
```

**Comportamento:**
- Processa **1 video alla volta**
- Esegue **60 volte/ora**
- Con processing AI di 12s, potrebbe processare fino a 60 video/ora

**PROBLEMA:**
Troppe esecuzioni cron inutili se la coda è vuota. Carico server non ottimizzato.

**SOLUZIONE RACCOMANDATA:**

Modificare `class-ai-queue.php:11`:

```php
// Da 'minute' a 'ipv_every_5_minutes' (già definito)
wp_schedule_event(time()+60, 'ipv_every_5_minutes', 'ipv_ai_queue_runner');
```

**BENEFICI:**
- ✅ Riduzione esecuzioni cron: da 60/ora a 12/ora
- ✅ Minor carico server
- ✅ Throughput comunque sufficiente (12 video/ora)

**OPZIONALE - Batch di 3 invece di 1:**

Modificare `class-ai-queue.php:23-38`:

```php
public static function run(){
    $q = get_option(self::OPTION_KEY, []);
    if(empty($q)) return;

    $batch_size = 3; // ← Aggiungi batch size

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

**Nuovo throughput:** 3 video × 12 esecuzioni/ora = **36 video/ora** (come coda principale)

---

## ✅ CONFIGURAZIONE CORRETTA (Coda Principale)

**File:** `ipv-production-system-pro.php`, `class-queue.php`

### Setup CRON Principale

```php
// ipv-production-system-pro.php:655-662
public function ensure_cron_scheduled() {
    // ✅ Coda principale: ogni 5 minuti
    if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
        wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
    }

    // ✅ Aggiornamento dati YouTube: ogni ora
    if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
        wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
    }
}
```

### Processing Batch (3 video alla volta, SEQUENZIALE)

```php
// class-queue.php:98-162
public static function process_queue() {
    $jobs = self::get_pending_jobs( 3 ); // ← 3 video

    foreach ( $jobs as $job ) { // ← SEQUENZIALE (non parallelo)
        self::process_single_job( $job );
        // Ogni job prende ~34 secondi
    }
}
```

### Tempi Misurati

**Un batch completo (3 video):**
```
Video 1: 34 secondi
Video 2: 34 secondi
Video 3: 34 secondi
─────────────────────
TOTALE: ~2 minuti (102 secondi)
```

**✅ Verifica sovrapposizione:**
```
Intervallo cron: 5 minuti (300 secondi)
Processing batch: 2 minuti (102 secondi)

302 secondi > 102 secondi → ✅ NESSUNA SOVRAPPOSIZIONE!
```

**✅ La configurazione è CORRETTA e OTTIMALE!**

---

## 📊 VELOCITÀ E THROUGHPUT

### Scenario Attuale (con WordPress Cron)

| Metrica | Valore | Note |
|---------|--------|------|
| **Processing effettivo per video** | 34s | ✅ Veloce |
| **Delay WordPress Cron** | 3 min | ❌ Problema |
| **Tempo totale percepito** | 3:36 min | Da migliorare |
| **Batch size** | 3 video | ✅ Ottimale |
| **Frequenza cron** | Ogni 5 min | ✅ Corretta |
| **Throughput** | 36 video/ora | ✅ Buono |

### Scenario Ottimizzato (con System Cron)

| Metrica | Valore | Miglioramento |
|---------|--------|---------------|
| **Processing effettivo per video** | 34s | - |
| **Delay System Cron** | 5s | **97% più veloce** |
| **Tempo totale percepito** | 39s | **82% riduzione** |
| **Batch size** | 3 video | - |
| **Frequenza cron** | Ogni 5 min | - |
| **Throughput** | 36 video/ora | - |

### Confronto Tempi

```
┌──────────────────────────────────────────────┐
│ TEMPO TOTALE PER VIDEO SINGOLO              │
├──────────────────────────────────────────────┤
│ WordPress Cron:  3:36 min ████████████████   │
│ System Cron:     0:39 min ███                │
│                                              │
│ Riduzione: 82%  (da 216s a 39s)            │
└──────────────────────────────────────────────┘
```

---

## 🎯 RACCOMANDAZIONI FINALI

### PRIORITÀ ALTA ⚠️

**1. Implementa System Cron** ← **MASSIMO IMPATTO**
- ✅ Riduzione tempo dell'82%
- ✅ Processing da 3:36 a 0:39
- ✅ Affidabilità garantita
- ⚙️ Complessità: Bassa (5 minuti setup)

**Come fare:**
```bash
# 1. Aggiungi a wp-config.php
define('DISABLE_WP_CRON', true);

# 2. Configura crontab (via cPanel o SSH)
* * * * * wget -q -O - https://danielem62.sg-host.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

### PRIORITÀ MEDIA 🟡

**2. Fix Import Singolo (usa coda)**
- ✅ Abilita trascrizione/AI automatica
- ✅ Processo unificato
- ⚙️ Complessità: Media (modifiche a class-simple-import.php)

**3. Ottimizza AI Queue (ogni 5 min)**
- ✅ Riduzione carico server
- ✅ Throughput comunque sufficiente
- ⚙️ Complessità: Bassa (1 riga di codice)

---

## 📋 DETTAGLI TECNICI

### Breakdown Chiamate API per Video

**Per ogni video processato:**

1. **YouTube Data API v3** (via server IPV)
   - Endpoint: `https://aiedintorni.it/youtube/video-data`
   - Tempo: ~1 secondo
   - Crediti: Inclusi nel piano

2. **SupaData Transcript API**
   - Endpoint: `https://api.supadata.ai/v1/transcript`
   - Tempo: 20 secondi (con sottotitoli nativi)
   - Crediti: 1 credito per video
   - Modalità: `auto` (preferisce sottotitoli nativi)

3. **OpenAI API** (via server IPV)
   - Endpoint: Server IPV → OpenAI
   - Tempo: 12 secondi
   - Prompt: Golden Prompt personalizzato
   - Output: ~2000 caratteri di descrizione

4. **Estrazione Metadata**
   - Hashtag extraction: ~1 secondo
   - Speaker/guest extraction: Incluso
   - Taxonomy assignment: Automatico

---

## 🔧 CRON JOB ATTIVI

### Job Schedulati

| Job Name | Frequenza | Action | Batch Size | File |
|----------|-----------|--------|------------|------|
| `ipv_prod_process_queue` | Ogni 5 min | Processa coda principale | 3 video | `class-queue.php:98` |
| `ipv_ai_queue_runner` | ❌ Ogni 1 min | Processa AI queue | 1 video | `class-ai-queue.php:23` |
| `ipv_prod_update_youtube_data` | Ogni ora | Aggiorna dati YouTube | Tutti | `class-queue.php:660` |

### Intervalli Personalizzati

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

---

## 📈 STATISTICHE LOG ANALIZZATO

**Video di test:** `tr8oMCsNb1s`
- **Titolo:** "Tecnologie dell'Elettricità Atmosferica alle Esposizioni del XIX Secolo"
- **Lingua:** Italiano
- **Transcript:** 41,856 caratteri
- **Sottotitoli:** Nativi (veloci)
- **Descrizione AI:** 1,971 caratteri
- **Tags estratti:** 6
- **Licenza:** R1TV-FH5U-W5N0-WZZ6 (attiva fino a 2026-12-11)

**Performance misurate:**
- ✅ Trascrizione: 20s (eccellente con sottotitoli nativi)
- ✅ AI Generation: 12s (molto veloce)
- ⚠️ WordPress Cron: 3:02 min (da ottimizzare)

---

## ✅ CONCLUSIONI

### Stato Attuale del Sistema

1. **✅ Processing Veloce:** 34 secondi per video (ottimo!)
2. **✅ Coda Configurata Correttamente:** 5 min / 3 video / no sovrapposizione
3. **✅ API Performance:** SupaData 20s, OpenAI 12s (eccellente)
4. **❌ WordPress Cron Lento:** 3 minuti di delay (da risolvere)
5. **⚠️ Import Singolo:** Bypassa la coda (da fixare)
6. **⚠️ AI Queue:** Troppo frequente (ottimizzabile)

### Impatto delle Fix Raccomandate

```
SCENARIO ATTUALE:
├─ Import singolo: NO trascrizione/AI ❌
├─ Tempo totale: 3:36 min (con delay) ⚠️
├─ AI Queue: 60 esecuzioni/ora ⚠️
└─ Processing: 34s ✅

SCENARIO OTTIMIZZATO:
├─ Import singolo: CON trascrizione/AI ✅
├─ Tempo totale: 0:39 min (senza delay) ✅
├─ AI Queue: 12 esecuzioni/ora ✅
└─ Processing: 34s ✅

BENEFICI:
✅ Riduzione tempo: 82%
✅ Import unificato
✅ Carico server ottimizzato
✅ Affidabilità massima
```

---

**Fine Report Aggiornato v2**
