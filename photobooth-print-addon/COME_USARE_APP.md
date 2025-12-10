# 📸 Come Usare PhotoBooth Printer App

## 🎯 Cos'è Questa App?

**PhotoBooth Printer App** è un'applicazione COMPLETA di Photo Booth con stampa integrata!

Non devi più usare Apple Photo Booth - questa app fa TUTTO:
- ✅ Cattura foto dalla webcam
- ✅ Mostra anteprima live
- ✅ Countdown prima dello scatto
- ✅ **Stampa DIRETTAMENTE dopo lo scatto!**
- ✅ Interfaccia grafica semplice
- ✅ Tutto in italiano

---

## 🚀 Installazione SUPER FACILE

### 1. Scarica i File

Scarica tutto da GitHub o copia la cartella `photobooth-print-addon`

### 2. Installa Dipendenze

**Opzione A: Automatica**

Doppio click su: `START_PHOTOBOOTH.command`

✅ Fatto! L'app si apre automaticamente

**Opzione B: Manuale**

Apri Terminale e digita:

```bash
cd ~/Downloads/photobooth-print-addon/
pip3 install -r requirements.txt
python3 photobooth_app.py
```

---

## 🎨 Come Usare l'App

### Interfaccia

Quando apri l'app vedi:

```
┌─────────────────────────────────────────────────┐
│         📸 PhotoBooth Printer                   │
├─────────────────────┬───────────────────────────┤
│                     │                           │
│   ANTEPRIMA LIVE    │    📷 Scatta Foto         │
│   (dalla webcam)    │                           │
│                     │    📸 Scatta e Stampa     │
│                     │                           │
│                     │    🖨️ Stampa Ultima       │
│                     │                           │
│                     │    ⚙️ Impostazioni        │
│                     │                           │
│                     │    [Info stampante]       │
└─────────────────────┴───────────────────────────┘
```

### Bottoni

1. **📷 Scatta Foto**
   - Conta alla rovescia (3, 2, 1...)
   - Scatta la foto
   - Salva nella cartella Pictures
   - NON stampa (devi cliccare "Stampa Ultima")

2. **📸 Scatta e Stampa** ⭐ PIÙ USATO
   - Conta alla rovescia
   - Scatta la foto
   - **STAMPA AUTOMATICAMENTE!**
   - Perfetto per eventi!

3. **🖨️ Stampa Ultima**
   - Ristampa l'ultima foto scattata
   - Utile se vuoi più copie

4. **⚙️ Impostazioni**
   - Cambia stampante
   - Numero di copie
   - Formato carta
   - Auto-stampa ON/OFF
   - Countdown (secondi)

---

## 🎉 Uso Tipico per Feste/Eventi

### Setup (Una Volta)

1. **Apri l'app**: Doppio click su `START_PHOTOBOOTH.command`

2. **Configura**: Clicca ⚙️ Impostazioni
   - Scegli la stampante
   - Copie: 1
   - Formato: 4x6 (foto) o Letter
   - ✅ Auto-stampa: ATTIVO
   - Countdown: 3 secondi
   - Clicca "Salva"

3. **Testa**: Clicca "Scatta e Stampa" per testare

### Durante la Festa

1. **Lascia l'app aperta** in modalità fullscreen (se vuoi)

2. **Gli ospiti**:
   - Si posizionano davanti alla webcam
   - Vedono se stessi nell'anteprima
   - Cliccano "Scatta e Stampa"
   - Contano insieme: 3... 2... 1... 📸
   - **La foto si stampa automaticamente!** 🖨️

3. **Tu**:
   - Non devi fare NIENTE!
   - L'app fa tutto automaticamente
   - Controlla solo carta e inchiostro

---

## ⚙️ Impostazioni Dettagliate

### Stampante
- Scegli quale stampante usare
- "Default" = stampante di sistema

### Copie
- Quante copie stampare per foto
- Consiglio: 1 (gli ospiti possono chiedere extra)

### Formato Carta
- **Letter** (8.5x11"): Carta normale
- **A4**: Carta europea
- **4x6**: Carta fotografica piccola (CONSIGLIATO)
- **5x7**, **8x10**: Carta foto grande

### Orientamento
- **Portrait**: Verticale (foto normali)
- **Landscape**: Orizzontale (foto panoramiche)

### Colore
- **Color**: Stampa a colori (normale)
- **Monochrome**: Bianco e nero (risparmia inchiostro)

### Auto-stampa
- **✓ Attivo**: "Scatta e Stampa" è il comportamento predefinito
- **✗ Disattivo**: Devi cliccare "Stampa Ultima" manualmente

### Countdown
- Secondi prima dello scatto (0-10)
- Consiglio: 3 secondi (tempo per posare)

---

## 📂 Dove Vengono Salvate le Foto?

Tutte le foto vengono salvate in:
```
~/Pictures/PhotoBooth Prints/
```

Nome file: `Photo_2025-12-10_14-30-45.jpg`

---

## 🔧 Risoluzione Problemi

### "Impossibile aprire la camera"

**Cause:**
- Webcam non collegata
- Camera usata da altra app
- Permessi non concessi

**Soluzioni:**
1. Chiudi altre app che usano la camera (Zoom, Skype, ecc.)
2. Vai in **Preferenze Sistema** → **Sicurezza** → **Camera**
3. Abilita l'accesso per Python/Terminale
4. Riavvia l'app

### "Dipendenze mancanti"

**Soluzione:**
```bash
pip3 install opencv-python pillow
```

### La stampa non funziona

**Controlla:**
1. Stampante accesa e collegata
2. Carta caricata
3. Driver installati
4. Test stampa manuale: Stampa un documento normale

### L'anteprima è congelata

**Soluzione:**
- Chiudi e riapri l'app
- Controlla che la webcam funzioni

### "Python 3 Required"

**Soluzione:**
1. Vai su https://www.python.org/downloads/
2. Scarica Python 3
3. Installa
4. Riavvia l'app

---

## 💡 Suggerimenti Pro

### Per Qualità Migliore

- Usa carta fotografica lucida 4x6"
- Buona illuminazione sulla scena
- Webcam HD (1080p se possibile)

### Per Eventi Grandi

- Prepara 2-3 risme di carta
- Controlla inchiostro PRIMA
- Testa tutto 1 ora prima
- Stampa 1 copia solo (risparmio)
- Tieni webcam a altezza viso

### Per Risparmiare

- Modalità bianco e nero
- Carta 4x6 invece di Letter
- 1 copia (extra su richiesta)

### Per Divertimento

- Countdown 5 secondi (più tempo per posare)
- Stampa 2 copie (una per ospite, una per ricordo)
- Carta grande Letter (più impatto)

---

## 🎯 Confronto con le 2 App

Ora hai **2 APPLICAZIONI** diverse:

### 1. **PhotoBooth Printer.app** (Monitor)
- Monitora Apple Photo Booth
- Stampa automaticamente
- Lavora in background
- Usa l'app Photo Booth di Apple

**Quando usarla:**
- Preferisci l'interfaccia di Apple Photo Booth
- Vuoi usare gli effetti di Photo Booth
- Già hai familiarità con Photo Booth

### 2. **photobooth_app.py** (App Completa) ⭐ NUOVO
- App standalone completa
- Interfaccia personalizzata
- Bottone "Scatta e Stampa" integrato
- **PIÙ SEMPLICE!**

**Quando usarla:**
- Vuoi tutto in un'app
- Bottone "Scatta e Stampa" più chiaro
- Eventi/feste dove serve semplicità
- **CONSIGLIATA PER LA MAGGIOR PARTE DEGLI USI**

---

## 🚀 Quick Start per Impazienti

```bash
# 1. Scarica tutto
cd ~/Downloads/photobooth-print-addon/

# 2. Installa
pip3 install opencv-python pillow

# 3. Avvia
python3 photobooth_app.py

# 4. Configura (click ⚙️ nell'app)

# 5. Usa (click 📸 Scatta e Stampa)

# 🎉 FATTO!
```

---

## ❓ FAQ

**D: Devo usare Apple Photo Booth?**
R: NO! Questa è un'app standalone completa.

**D: Posso usare webcam esterna?**
R: SÌ! Selezionala nelle impostazioni (camera_index in config).

**D: Quanto costa?**
R: GRATIS! Open source, licenza MIT.

**D: Funziona su Windows?**
R: No, solo macOS. Ma il codice può essere adattato.

**D: Posso modificarla?**
R: SÌ! È open source, modifica come vuoi!

**D: Serve internet?**
R: No, funziona offline (dopo installazione).

---

## 📞 Supporto

**Problemi?**
- Leggi `TROUBLESHOOTING.md`
- Esegui `diagnostic.py`
- Apri issue su GitHub

**Funziona?**
- ⭐ Stella su GitHub!
- 📢 Condividi con amici!
- 💬 Lascia feedback!

---

**Buon Divertimento! 📸🖨️🎉**

*L'app perfetta per feste, eventi, matrimoni, compleanni e tanto altro!*
