# 🔧 Risoluzione Problemi - PhotoBooth Printer

## ❌ Problema: La Stampa Automatica Non Parte

### 🔍 Diagnosi Automatica (PROVA PRIMA QUESTO)

1. **Apri Terminale** (Applicazioni → Utility → Terminale)

2. **Vai nella cartella dell'app**:
```bash
cd ~/Library/Application\ Support/PhotoBooth\ Printer/
```
   Se non funziona, prova:
```bash
cd ~/Downloads/ipv-*/photobooth-print-addon/
```

3. **Esegui lo script diagnostico**:
```bash
python3 diagnostic.py
```

4. **Leggi i risultati** e segui i suggerimenti

---

## ✅ Checklist Manuale

Controlla TUTTI questi punti:

### 1. ✓ L'app è in esecuzione?

**Come verificare:**
- Apri **Monitor Attività** (Applicazioni → Utility)
- Cerca "python" o "photobooth"
- Deve esserci un processo attivo

**Se non c'è:**
- Apri PhotoBooth Printer
- Clicca "Start"
- Aspetta che si apra il Terminale

---

### 2. ✓ Hai cliccato "Start"?

**Passaggi corretti:**
1. Apri PhotoBooth Printer.app
2. Clicca il bottone **"Start"**
3. Si apre una finestra del Terminale
4. Il Terminale dice: "Waiting for new photos to print..."

**Se il Terminale NON si apre:**
- L'app potrebbe non avere i permessi
- Prova da Terminale manualmente (vedi sotto)

---

### 3. ✓ La cartella Photo Booth esiste?

**Controlla questo percorso:**
```
~/Pictures/Photo Booth Library/Pictures
```

**Come verificare:**
1. Apri **Finder**
2. Vai in **Immagini** (Pictures)
3. Cerca la cartella **"Photo Booth Library"**
4. Dentro deve esserci **"Pictures"**

**Se NON esiste:**
- Apri Photo Booth
- Scatta almeno 1 foto
- La cartella verrà creata automaticamente

---

### 4. ✓ Le foto sono NUOVE?

**IMPORTANTE:** Il monitor rileva solo foto scattate **DOPO** aver cliccato Start!

**Test corretto:**
1. Clicca "Start" in PhotoBooth Printer
2. Aspetta che dica "Waiting for new photos..."
3. SOLO ORA apri Photo Booth
4. Scatta una foto NUOVA
5. Aspetta 2-3 secondi
6. La foto dovrebbe stampare

**Se non funziona:**
- Le foto vecchie NON vengono stampate
- Solo le nuove foto vengono rilevate

---

### 5. ✓ La stampante funziona?

**Test manuale:**
1. Apri un'immagine qualsiasi
2. Clicca File → Stampa
3. Prova a stampare normalmente

**Se non stampa neanche così:**
- Il problema è la stampante, non l'app
- Controlla che sia accesa
- Controlla che ci sia carta
- Verifica i driver in Preferenze → Stampanti

---

### 6. ✓ La configurazione è OK?

**Controlla il file di configurazione:**
```bash
cat ~/.photobooth-printer-config.json
```

**Deve contenere:**
```json
{
    "enabled": true,
    "printer_name": "TuaStampante",
    "photo_booth_path": "~/Pictures/Photo Booth Library/Pictures",
    ...
}
```

**Se "enabled" è false:**
```bash
python3 photobooth_printer.py enable
```

---

### 7. ✓ Hai installato watchdog?

**Verifica:**
```bash
python3 -c "import watchdog; print('OK')"
```

**Se dice errore:**
```bash
pip3 install watchdog
```

---

## 🐛 Debug Avanzato

### Controlla i Log

**Apri il file di log:**
```bash
tail -f ~/Library/Logs/photobooth-printer.log
```

**Cosa cercare:**
- `✓ Started monitoring`: OK, sta monitorando
- `New photo detected`: OK, ha trovato la foto
- `Successfully queued print job`: OK, ha stampato
- `Error`: Problema! Leggi l'errore

**Log comune:**
```
2025-12-10 10:30:00 - Starting Photo Booth Printer monitor
2025-12-10 10:30:00 - Monitoring: /Users/tuonome/Pictures/Photo Booth Library/Pictures
2025-12-10 10:30:15 - New photo detected: foto.jpg
2025-12-10 10:30:16 - Successfully queued print job for: foto.jpg
```

---

### Avvio Manuale da Terminale

Se l'app non funziona, prova manualmente:

```bash
# Vai nella cartella
cd ~/Library/Application\ Support/PhotoBooth\ Printer/

# Controlla la configurazione
python3 photobooth_printer.py status

# Avvia il monitor
python3 photobooth_printer.py start
```

**Vantaggi:**
- Vedi tutti i messaggi di errore
- Più facile da debuggare
- Controllo completo

---

### Test Rapido

**Crea un file test:**
```bash
# Mentre il monitor è attivo, crea un file di test
touch ~/Pictures/Photo\ Booth\ Library/Pictures/test_$(date +%s).jpg
```

**Cosa dovrebbe succedere:**
- Il monitor rileva il file
- Prova a stamparlo
- Vedi messaggi nel log

---

## 🔧 Soluzioni ai Problemi Comuni

### "Permission denied"

**Soluzione:**
1. Vai in **Preferenze di Sistema**
2. **Sicurezza e Privacy**
3. **Privacy** tab
4. **Accesso completo al disco**
5. Aggiungi **Terminale** e **Python**
6. Riavvia tutto

---

### "No printers found"

**Soluzione:**
1. **Preferenze di Sistema** → **Stampanti e Scanner**
2. Clicca **"+"** per aggiungere stampante
3. Seleziona la tua stampante
4. Clicca **"Aggiungi"**
5. Riapri PhotoBooth Printer e riconfigura

---

### "watchdog not installed"

**Soluzione:**
```bash
pip3 install --user watchdog
```

Se non funziona:
```bash
python3 -m pip install --user watchdog
```

---

### "File not found: photobooth_printer.py"

**Soluzione - L'app non ha copiato il file:**

```bash
# Trova il file originale
cd ~/Downloads/ipv-*/photobooth-print-addon/

# Copia nella posizione corretta
mkdir -p ~/Library/Application\ Support/PhotoBooth\ Printer/
cp photobooth_printer.py ~/Library/Application\ Support/PhotoBooth\ Printer/

# Riprova
cd ~/Library/Application\ Support/PhotoBooth\ Printer/
python3 photobooth_printer.py setup
```

---

### Il monitor parte ma non stampa

**Possibili cause:**

1. **Path sbagliato**
   - Controlla che Photo Booth salvi davvero in `~/Pictures/Photo Booth Library/Pictures`
   - Alcune versioni usano percorsi diversi

2. **Solo file nuovi**
   - Ricorda: vengono stampate SOLO le foto scattate DOPO aver avviato il monitor

3. **Estensioni file**
   - Photo Booth salva in `.jpg`
   - Il monitor cerca `.jpg`, `.jpeg`, `.png`
   - Se Photo Booth usa altro formato, dobbiamo aggiornare il codice

4. **Stampante non risponde**
   - Test: `lp ~/Desktop/test.jpg`
   - Se non stampa, problema CUPS/stampante

---

## 📞 Aiuto Ulteriore

Se hai provato TUTTO e non funziona ancora:

1. **Esegui lo script diagnostico**:
   ```bash
   python3 diagnostic.py
   ```

2. **Salva il log**:
   ```bash
   cat ~/Library/Logs/photobooth-printer.log > debug.txt
   ```

3. **Apri un issue su GitHub** con:
   - Output dello script diagnostico
   - Contenuto del file debug.txt
   - Versione di macOS
   - Modello di stampante

---

## ✅ Se Funziona Tutto Ma Vuoi Migliorare

### Auto-start su Login

Per far partire automaticamente il monitor all'accesso:

```bash
cd ~/Downloads/ipv-*/photobooth-print-addon/
chmod +x setup_autostart.sh
./setup_autostart.sh
```

### Cambia Impostazioni

```bash
python3 photobooth_printer.py setup
```

### Vedi Stato

```bash
python3 photobooth_printer.py status
```

---

## 🎯 Quick Reference

| Comando | Cosa Fa |
|---------|---------|
| `python3 diagnostic.py` | Diagnosi completa |
| `python3 photobooth_printer.py setup` | Configurazione |
| `python3 photobooth_printer.py start` | Avvia monitor |
| `python3 photobooth_printer.py status` | Mostra stato |
| `python3 photobooth_printer.py enable` | Abilita stampa |
| `python3 photobooth_printer.py disable` | Disabilita stampa |
| `tail -f ~/Library/Logs/photobooth-printer.log` | Guarda log live |

---

**Hai risolto? Facci sapere! 🎉**
**Ancora problemi? Apri un issue su GitHub! 🐛**
