# IPV Golden Prompt Module

Modulo per la gestione dei Golden Prompts nel sistema IPV Pro Vendor.

## Versione
1.0.0

## Descrizione

Questo modulo permette di:
- Configurare Golden Prompts personalizzati per ogni licenza
- Auto-generare prompt basati su template con placeholder
- Inviare (push) i prompt ai siti client
- Memorizzare i prompt in modo sicuro (non visibili all'utente finale)

## Struttura File

```
golden-prompt-module/
├── golden-prompt-module.php       # File principale del modulo
├── includes/
│   ├── class-golden-prompt-manager.php   # Gestione logica
│   ├── class-golden-prompt-admin.php     # Pannello admin
│   └── class-golden-prompt-api.php       # REST API vendor
├── admin/
│   └── assets/
│       ├── css/
│       │   └── golden-prompt-admin.css
│       └── js/
│           └── golden-prompt-admin.js
└── client/
    └── class-golden-prompt-client.php    # File per plugin CLIENT
```

## Installazione

### 1. Plugin VENDOR (aiedintorni.it)

1. Copia la cartella `golden-prompt-module` in:
   ```
   wp-content/plugins/ipv-pro-vendor/modules/
   ```

2. Nel file `ipv-pro-vendor.php`, aggiungi dopo gli altri require:
   ```php
   // Golden Prompt Module
   require_once IPV_VENDOR_PATH . 'modules/golden-prompt/golden-prompt-module.php';
   ```

3. Attiva il modulo visitando: `wp-admin/admin.php?page=ipv-golden-prompt`

### 2. Plugin CLIENT (siti clienti)

1. Copia `client/class-golden-prompt-client.php` in:
   ```
   wp-content/plugins/ipv-production-system-pro/includes/
   ```

2. Nel file principale del plugin client, aggiungi:
   ```php
   require_once IPV_PRO_PATH . 'includes/class-golden-prompt-client.php';
   
   // Inizializza dopo l'attivazione della licenza
   add_action( 'plugins_loaded', function() {
       IPV_Pro_Golden_Prompt_Client::instance();
   }, 20 );
   ```

## Database

Il modulo crea automaticamente la tabella:

```sql
CREATE TABLE {prefix}_ipv_golden_prompts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id BIGINT UNSIGNED NOT NULL,
    config_json LONGTEXT NULL,
    golden_prompt LONGTEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY idx_license (license_id)
);
```

## Utilizzo

### Admin Panel

1. Vai a **IPV Vendor → ✨ Golden Prompt**
2. Vedrai l'elenco delle licenze attive
3. Clicca **⚙️ Configura** per configurare una licenza

### Configurazione Automatica

1. Compila i campi del form (nome canale, link, ecc.)
2. Clicca **💾 Genera e Salva Golden Prompt**
3. Il prompt viene generato automaticamente

### Configurazione Manuale

1. Vai al tab **📝 Prompt Manuale**
2. Incolla un Golden Prompt personalizzato
3. Clicca **💾 Salva Golden Prompt**

### Push al Client

1. Clicca **🚀 Push** nella lista licenze
2. O clicca il bottone Push nella pagina di configurazione
3. Il prompt viene inviato al sito client via API

## REST API

### Endpoint Vendor (GET)

```
GET /wp-json/ipv-vendor/v1/golden-prompt
Header: X-License-Key: XXXX-XXXX-XXXX-XXXX

Response:
{
    "success": true,
    "has_golden_prompt": true,
    "golden_prompt": "...",
    "updated_at": "2025-12-17 12:00:00",
    "hash": "abc123..."
}
```

### Endpoint Client (POST)

```
POST /wp-json/ipv-pro/v1/golden-prompt/sync
Header: X-License-Key: XXXX-XXXX-XXXX-XXXX
Body: {
    "golden_prompt": "...",
    "updated_at": "2025-12-17 12:00:00"
}
```

## Sicurezza

- Il Golden Prompt è memorizzato lato server (vendor)
- Il client riceve il prompt solo via API autenticata
- Il prompt è offuscato nel database client (base64 + reverse)
- L'utente finale NON può vedere il prompt

## Helper Functions (Client)

```php
// Ottieni il Golden Prompt (uso interno)
$prompt = ipv_get_golden_prompt();

// Verifica se esiste un Golden Prompt
if ( ipv_has_golden_prompt() ) {
    // ...
}
```

## Template Universale

Il template universale può essere modificato da:
**IPV Vendor → ✨ Golden Prompt → 📝 Modifica Template Universale**

### Placeholder Disponibili

| Placeholder | Descrizione |
|-------------|-------------|
| `{NOME_CANALE}` | Nome del canale YouTube |
| `{HANDLE_YOUTUBE}` | Handle YouTube (senza @) |
| `{NICCHIA}` | Nicchia/settore del canale |
| `{LINK_TELEGRAM}` | Link Telegram |
| `{LINK_FACEBOOK}` | Link Facebook |
| `{LINK_INSTAGRAM}` | Link Instagram |
| `{LINK_SITO}` | URL sito web |
| `{LINK_DONAZIONI}` | Link donazioni |
| `{SPONSOR_NOME}` | Nome sponsor |
| `{SPONSOR_DESCRIZIONE}` | Descrizione sponsor |
| `{SPONSOR_LINK}` | Link sponsor |
| `{EMAIL_BUSINESS}` | Email business |
| `{BIO_CANALE}` | Bio/descrizione canale |
| `{HASHTAG_CANALE}` | Hashtag principale |

## Changelog

### 1.0.0
- Release iniziale
- Auto-configuratore con placeholder
- Push al client via REST API
- Template universale modificabile
- Memorizzazione sicura lato client
