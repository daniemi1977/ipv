# 🚨 TROUBLESHOOTING: Error "unauthorized" su SupaData

## 📋 Sintomi

Quando provi a generare una trascrizione su un video del client, vedi:

```
❌ Error: unauthorized
```

**Nota:** La copertina del video viene scaricata correttamente, ma la trascrizione fallisce.

---

## 🔍 Causa Root (95% dei casi)

**Il server hosting (SiteGround, Bluehost, etc) rimuove l'header `Authorization` dalle richieste HTTP.**

### Perché Succede?

1. **Copertina Funziona**: Il client scarica la thumbnail direttamente da YouTube, non passa dal server vendor
2. **Trascrizione Fallisce**: La trascrizione DEVE passare dal server vendor per:
   - Usare le API key SupaData (protette sul server)
   - Scalare i crediti dal piano utente
   - Validare la licenza

Quando il client chiama il server vendor, invia la license key nell'header `Authorization: Bearer YOUR_KEY`.

**Ma molti hosting rimuovono questo header per "sicurezza"**, quindi il server vendor non riceve la license key e risponde: `401 Unauthorized`.

---

## ✅ SOLUZIONE 1: Modifica .htaccess (PRIORITARIA)

### Step 1: Accedi al Server

**Non sul client**, ma sul **server dove hai installato IPV Pro Vendor**.

- Se usi **SiteGround**: Site Tools → File Manager
- Se usi **cPanel**: File Manager
- Se usi **FTP**: FileZilla, Cyberduck, etc.

### Step 2: Trova .htaccess

```
Cartella: public_html/
File: .htaccess
```

Se non esiste, crealo.

### Step 3: Aggiungi Regole RewriteEngine

**Se il file esiste già**, aggiungi queste righe **IN CIMA** (dopo `RewriteEngine On` se c'è):

```apache
RewriteEngine On

# CRITICAL: Preserva Authorization header
# Fix per SiteGround, Bluehost, GoDaddy e altri hosting
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]

# Fallback per X-License-Key header
RewriteCond %{HTTP:X-License-Key} ^(.*)
RewriteRule ^(.*) - [E=HTTP_X_LICENSE_KEY:%1]
```

**Se il file NON esiste**, crea `.htaccess` con questo contenuto:

```apache
# IPV Pro Vendor - Apache Configuration
<IfModule mod_rewrite.c>
RewriteEngine On

# CRITICAL: Preserva Authorization header
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]

# Fallback per X-License-Key header
RewriteCond %{HTTP:X-License-Key} ^(.*)
RewriteRule ^(.*) - [E=HTTP_X_LICENSE_KEY:%1]
</IfModule>

# Protezione directory
<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>
```

### Step 4: Salva e Riprova

1. Salva `.htaccess`
2. Torna sul **client**
3. Vai su un video → "Rigenera Trascrizione"
4. ✅ Dovrebbe funzionare!

---

## ✅ SOLUZIONE 2: Aggiorna a Server v1.3.2

Se hai ancora problemi dopo aver modificato `.htaccess`, aggiorna il server vendor:

### Download v1.3.2
```
https://github.com/daniemi1977/ipv/raw/claude/get-recent-uploads-01V9asSqzYj32qNmxnS6wSyY/ipv-pro-vendor-v1.3.2.zip
```

### Cosa Include v1.3.2?

1. **`.htaccess` incluso nel plugin** - Si installa automaticamente
2. **Debug logging avanzato** - Vedi esattamente cosa arriva al server
3. **Fallback `REDIRECT_HTTP_AUTHORIZATION`** - Supporto per hosting particolari
4. **Logging dettagliato WP_DEBUG** - Per troubleshooting avanzato

### Come Installare

```bash
Sul server (NON client):
1. WordPress Admin → Plugin
2. Disattiva IPV Pro Vendor v1.3.1
3. Elimina v1.3.1
4. Carica ipv-pro-vendor-v1.3.2.zip
5. Attiva v1.3.2
6. Verifica che .htaccess sia presente nella cartella plugin
```

---

## ✅ SOLUZIONE 3: Abilita Debug Logging

Se continua a non funzionare, abilita il debug per vedere esattamente cosa sta succedendo.

### Step 1: Abilita WP_DEBUG sul Server

Modifica `wp-config.php` sul **server** (NON client):

```php
// Trova questa riga:
define( 'WP_DEBUG', false );

// Sostituisci con:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
```

### Step 2: Riprova Trascrizione

Sul client, prova a generare una trascrizione.

### Step 3: Controlla Log

Sul server, vai su:
```
/wp-content/debug.log
```

Cerca queste righe:

#### Se .htaccess NON Funziona:
```
=== IPV VENDOR DEBUG - License Validation ===
HTTP_AUTHORIZATION: NULL
HTTP_X_LICENSE_KEY: NULL
Request Param license_key: NULL
REDIRECT_HTTP_AUTHORIZATION: NULL
IPV VENDOR ERROR: License key mancante dopo tutti i fallback!
Possibile causa: .htaccess non configurato per preservare Authorization header
```

**Soluzione:** Il `.htaccess` non sta funzionando. Verifica:
- È nella cartella giusta? (`public_html/.htaccess`)
- Le regole sono in cima al file?
- Il server usa Apache? (se usa Nginx, serve configurazione diversa)

#### Se .htaccess Funziona:
```
=== IPV VENDOR DEBUG - License Validation ===
HTTP_AUTHORIZATION: Bearer abcd1234xyz...
HTTP_X_LICENSE_KEY: NULL
Request Param license_key: NULL
REDIRECT_HTTP_AUTHORIZATION: NULL
IPV VENDOR: License key trovata: abcd1234...xyz
```

**Soluzione:** Il `.htaccess` funziona! Se continua a dare unauthorized, il problema è:
- License key non valida
- License key scaduta
- License key non attivata per quel dominio

---

## ✅ SOLUZIONE 4: Verifica Licenza sul Client

Anche se vedi la license key configurata, prova a riattivarla.

### Step 1: Disattiva Licenza

Sul **client**:
```
WordPress Admin → IPV Videos → Licenza → Disattiva Licenza
```

### Step 2: Riattiva Licenza

```
WordPress Admin → IPV Videos → Licenza
→ Inserisci license key
→ Clicca "Attiva Licenza"
→ Verifica che mostri "Licenza Attiva: 🟢"
```

### Step 3: Riprova

Vai su un video → "Rigenera Trascrizione"

---

## 🧪 Test con Diagnostica Tool (Client v10.0.8+)

Se hai installato **client v10.0.8 o superiore**, usa il tool diagnostica:

### Come Usare

```
Client → WordPress Admin → IPV Videos → Diagnostica → Esegui Diagnostica
```

### Cosa Controlla

1. ✅ **Configurazione Locale** - License key e server URL
2. ✅ **Server Raggiungibilità** - Server risponde? Quale versione?
3. ✅ **Validazione Licenza** - Licenza valida?
4. ✅ **Crediti Disponibili** - Crediti rimasti?
5. ℹ️ **Suggerimenti** - Come risolvere ogni errore

### Interpretazione Risultati

#### Scenario A: Tutto Verde
```
✅ Configurazione Locale
✅ Server Raggiungibilità (v1.3.2)
✅ Validazione Licenza
✅ Crediti Disponibili: 142/200

🔧 Prossimi Passi
✅ Tutto OK! Il sistema è configurato correttamente.
```

**Se diagnostica è OK ma trascrizione fallisce:**
→ Problema è l'header Authorization bloccato
→ Applica SOLUZIONE 1 (.htaccess)

#### Scenario B: License Key Mancante
```
❌ Configurazione Locale
   ❌ License key NON configurata!
```

**Soluzione:** IPV Videos → Licenza → Inserisci license key

#### Scenario C: Server Non Raggiungibile
```
❌ Server Raggiungibilità
   ❌ Errore connessione: cURL error 28
```

**Soluzione:**
- Verifica server URL corretto
- Verifica che IPV Pro Vendor sia attivo sul server

#### Scenario D: Licenza Non Valida
```
❌ Validazione Licenza
   ❌ Licenza NON VALIDA (401 Unauthorized)
```

**Soluzione:**
- Controlla che license key sia corretta
- Verifica che licenza sia attivata per questo dominio
- Contatta vendor per attivazione

---

## 📊 Checklist Completa

Prima di chiedere supporto, verifica:

### Sul Server (dove hai IPV Pro Vendor)

- [ ] IPV Pro Vendor v1.3.2 installato e attivo
- [ ] File `.htaccess` presente in `public_html/` con regole RewriteEngine
- [ ] WP_DEBUG abilitato per vedere log
- [ ] Controllato `/wp-content/debug.log` per errori
- [ ] WooCommerce attivo e funzionante

### Sul Client (dove hai IPV Production System Pro)

- [ ] IPV Production System Pro v10.0.8+ installato
- [ ] License key configurata in IPV Videos → Licenza
- [ ] Server URL configurato in IPV Videos → Impostazioni → Server
- [ ] Eseguita diagnostica (se v10.0.8+) → Tutto ✅ verde
- [ ] Licenza mostra "Licenza Attiva: 🟢"

### Test Finale

- [ ] Vai su un video esistente
- [ ] Clicca "Rigenera Trascrizione"
- [ ] Se fallisce, controlla `debug.log` sul server
- [ ] Copia log e invia a supporto

---

## 🛠️ Troubleshooting Avanzato

### Hosting Specifici

#### SiteGround
```apache
# In .htaccess, usa ESATTAMENTE questo formato:
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

#### Nginx (se non usi Apache)

Il `.htaccess` NON funziona su Nginx. Devi modificare la configurazione Nginx:

```nginx
location / {
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
    fastcgi_pass_header Authorization;
}
```

**Contatta il tuo hosting per applicare questa configurazione.**

#### Cloudflare

Se usi Cloudflare, verifica che non stia bloccando gli header:

1. Cloudflare Dashboard
2. Regole → Transform Rules
3. Verifica che Authorization header non sia rimosso

---

## 📞 Supporto

Se hai provato TUTTE le soluzioni sopra e continua a non funzionare:

### 1. Raccogli Informazioni

```
Client:
- Versione: [v10.0.8 o...?]
- License key configurata: [Sì/No]
- Server URL configurato: [URL]
- Diagnostica eseguita: [Screenshot]

Server:
- Versione: [v1.3.2 o...?]
- Hosting: [SiteGround, Bluehost, etc]
- .htaccess presente: [Sì/No]
- WP_DEBUG abilitato: [Sì/No]
- Log (ultimi 50 righe): [Copia da debug.log]
```

### 2. Screenshot

- Screenshot diagnostica client (se v10.0.8+)
- Screenshot errore console browser (F12 → Console)
- Screenshot Network tab (F12 → Network → Filter XHR → Click trascrizione)

### 3. Report Issue

https://github.com/daniemi1977/ipv/issues

Allega:
- Informazioni raccolte sopra
- Screenshot
- Log debug.log

---

## 🎯 Caso Reale: SiteGround

**Problema:**
```
Client su dominio1.com (SiteGround)
Server su dominio2.com (SiteGround)
Errore: unauthorized su SupaData
```

**Soluzione Applicata:**

1. **Server** - Accesso via Site Tools → File Manager
2. **Navigato a:** `public_html/.htaccess`
3. **Modificato** aggiungendo in cima:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTP:Authorization} ^(.*)
   RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
   ```
4. **Salvato** e chiuso
5. **Client** - Riprova trascrizione
6. ✅ **Funziona!**

**Tempo risoluzione:** 2 minuti

---

## 📚 Risorse Utili

### Link Download

- **Client v10.0.8**: https://github.com/daniemi1977/ipv/raw/claude/get-recent-uploads-01V9asSqzYj32qNmxnS6wSyY/ipv-production-system-pro-v10.0.8.zip
- **Server v1.3.2**: https://github.com/daniemi1977/ipv/raw/claude/get-recent-uploads-01V9asSqzYj32qNmxnS6wSyY/ipv-pro-vendor-v1.3.2.zip

### Documentazione

- CHANGELOG-v10.0.8.md - Client diagnostic tool
- CHANGELOG-v1.3.2.md - Server .htaccess fix
- TROUBLESHOOTING-AUTHORIZATION-ERROR.md - Questo file

### Testing

Per verificare se l'header Authorization viene preservato:

```bash
curl -H "Authorization: Bearer test123" https://your-server.com/wp-json/ipv-vendor/v1/health
```

Se vedi nel log:
```
HTTP_AUTHORIZATION: Bearer test123
```
→ ✅ Funziona!

Se vedi:
```
HTTP_AUTHORIZATION: NULL
```
→ ❌ .htaccess non funziona

---

## ✅ Summary Rapido

**Problema:** `Error: unauthorized` su SupaData

**Causa:** Hosting rimuove header `Authorization`

**Fix Rapido (90% casi):**
1. Server → Modifica `.htaccess`
2. Aggiungi regole RewriteEngine
3. Riprova trascrizione
4. ✅ Funziona!

**Se non funziona:**
1. Aggiorna server a v1.3.2
2. Abilita WP_DEBUG
3. Controlla log
4. Segui troubleshooting avanzato

**Tempo stimato:** 5-10 minuti

---

**Versione Documento:** 1.0
**Data:** 9 Dicembre 2024
**Compatibile con:**
- Client: v10.0.8+
- Server: v1.3.2+
