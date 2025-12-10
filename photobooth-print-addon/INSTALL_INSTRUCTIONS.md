# 📸 PhotoBooth Printer - Istruzioni di Installazione

## Per Utenti Non Programmatori - FACILE! 🎉

### Metodo 1: Usa l'Installer (Più Facile)

1. **Scarica** il file `PhotoBooth_Printer_Installer.dmg` (o `.zip`)

2. **Apri** il file scaricato:
   - Se è un `.dmg`: fai doppio click
   - Se è un `.zip`: fai doppio click per estrarre

3. **Installa**: Trascina l'icona **PhotoBooth Printer** nella cartella **Applicazioni**

4. **Apri**: Vai in Applicazioni e fai doppio click su **PhotoBooth Printer**

5. **Prima configurazione**:
   - L'app ti chiederà di scegliere la stampante
   - Segui le istruzioni sullo schermo
   - È tutto guidato!

6. **Usa**:
   - Clicca "Start" nell'app
   - Apri Photo Booth
   - Scatta una foto
   - La foto viene stampata automaticamente! 🎉

### Metodo 2: Installazione Manuale

Se non hai il file `.dmg` o `.zip`, segui questi passaggi:

#### Requisiti:
- Mac con macOS 10.12 o superiore
- Python 3 installato (l'app ti dirà se ti serve)

#### Passi:

1. **Scarica tutti i file** di questo progetto
   - Clicca il bottone verde "Code" su GitHub
   - Scegli "Download ZIP"
   - Estrai lo ZIP

2. **Copia l'applicazione**:
   - Trova la cartella `PhotoBooth Printer.app`
   - Trascinala nella cartella **Applicazioni**

3. **Apri l'applicazione**:
   - Vai in Applicazioni
   - Fai doppio click su **PhotoBooth Printer**

4. **Se macOS dice "Non può essere aperto"**:
   - Vai in **Preferenze di Sistema** → **Sicurezza e Privacy**
   - Clicca "Apri comunque"
   - Oppure: click destro sull'app → "Apri"

5. **Configurazione**:
   - L'app ti guiderà nella configurazione
   - Scegli la tua stampante
   - Conferma le impostazioni

6. **Inizia a usarla**:
   - Clicca "Start"
   - Apri Photo Booth
   - Scatta foto - vengono stampate automaticamente!

---

## 🎯 Come Usare PhotoBooth Printer

### Avvio Veloce

1. **Apri** PhotoBooth Printer da Applicazioni
2. **Clicca** "Start"
3. **Scatta** foto con Photo Booth
4. **Stampa automatica**! ✅

### Menu dell'App

- **Start**: Avvia il monitoraggio e la stampa automatica
- **Settings** → **Status**: Vedi le impostazioni attuali
- **Settings** → **Reconfigure**: Cambia stampante o impostazioni
- **Quit**: Chiudi l'app

### Per Feste/Eventi

1. Apri PhotoBooth Printer e clicca "Start"
2. Lascia aperto Photo Booth
3. Gli ospiti scattano foto
4. Le foto vengono stampate subito!
5. Lascia l'app in esecuzione per tutta la festa

---

## ⚠️ Risoluzione Problemi

### "Python 3 Required"
- L'app ti dirà se ti serve Python 3
- Ti aprirà il sito per scaricarlo
- Installa Python 3, poi riprova

### "Nessuna stampante trovata"
- Vai in **Preferenze di Sistema** → **Stampanti e Scanner**
- Aggiungi la tua stampante
- Riapri PhotoBooth Printer

### La stampante non stampa
- Controlla che la stampante sia accesa
- Controlla che ci sia carta
- Prova a stampare un documento normale per testare

### Le foto non vengono stampate automaticamente
- Assicurati di aver cliccato "Start" nell'app
- Controlla che Photo Booth salvi le foto in `~/Pictures/Photo Booth Library/Pictures`
- Riavvia l'app

### "Impossibile aprire l'app"
1. Click destro sull'app
2. Scegli "Apri"
3. Clicca "Apri" nella finestra di avviso
4. Oppure: Preferenze di Sistema → Sicurezza → "Apri comunque"

---

## 🔧 Impostazioni Avanzate (Opzionale)

Se vuoi modificare manualmente le impostazioni:

1. Apri il **Finder**
2. Premi `Cmd + Shift + G`
3. Scrivi: `~/.photobooth-printer-config.json`
4. Apri il file con TextEdit
5. Modifica le impostazioni:
   - `copies`: numero di copie (1, 2, 3...)
   - `paper_size`: "Letter", "A4", "4x6"
   - `orientation`: "portrait" o "landscape"
   - `color_mode`: "color" o "monochrome"

---

## 📞 Supporto

### Hai bisogno di aiuto?

- **Email**: [inserisci email di supporto]
- **GitHub**: https://github.com/daniemi1977/ipv/issues

### File di Log

Se qualcosa non funziona, puoi controllare i log:

1. Apri **Console** (in Applicazioni → Utility)
2. Cerca: `photobooth-printer.log`

---

## ❌ Disinstallazione

Se vuoi rimuovere l'app:

1. Trascina **PhotoBooth Printer** dal cestino (dalla cartella Applicazioni)
2. Elimina il file di configurazione:
   - Apri Finder
   - Vai a `Home` (la tua cartella utente)
   - Premi `Cmd + Shift + .` per mostrare i file nascosti
   - Trova e elimina `.photobooth-printer-config.json`
3. Fatto!

---

## ✅ Checklist Rapida

- [ ] Ho scaricato l'app
- [ ] L'ho copiata in Applicazioni
- [ ] L'ho aperta (click destro → Apri se necessario)
- [ ] Ho configurato la stampante
- [ ] Ho cliccato "Start"
- [ ] Ho testato con Photo Booth
- [ ] Funziona! 🎉

---

**Divertiti con la stampa automatica delle foto! 📸🖨️**
