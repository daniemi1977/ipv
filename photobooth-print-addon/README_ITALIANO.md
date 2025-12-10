# 📸🖨️ PhotoBooth Printer - Stampa Automatica per Photo Booth

**Stampa automaticamente le foto di Apple Photo Booth - Nessuna programmazione richiesta!**

---

## 🎯 Cos'è?

PhotoBooth Printer è un'**app per Mac** che stampa automaticamente le foto appena vengono scattate con Apple Photo Booth.

**Perfetto per:**
- 🎉 Feste e compleanni
- 💼 Eventi aziendali
- 🎓 Feste di laurea
- 💒 Matrimoni
- 🏢 Photo booth professionali
- 👨‍👩‍👧‍👦 Riunioni di famiglia

---

## ⚡ Installazione FACILE

### Metodo 1: Usa l'App (CONSIGLIATO)

1. **Scarica** la cartella `PhotoBooth Printer.app`
2. **Trascina** l'app nella cartella **Applicazioni**
3. **Apri** PhotoBooth Printer
4. **Configura** seguendo la guida
5. **Usa** cliccando "Start"!

✅ **Nessun terminale. Nessun codice. Solo click!**

### Metodo 2: Crea l'Installer (per sviluppatori)

```bash
./create_installer.sh
```

Questo crea un file `.dmg` o `.zip` che puoi distribuire.

---

## 📖 Documentazione

Abbiamo guide per tutti:

| Documento | Per Chi | Cosa Contiene |
|-----------|---------|---------------|
| **GUIDA_RAPIDA.md** | 🟢 Tutti | Guida super veloce, 3 minuti |
| **INSTALL_INSTRUCTIONS.md** | 🟢 Non programmatori | Istruzioni dettagliate con immagini |
| **README.md** (inglese) | 🔵 Programmatori | Documentazione tecnica completa |

**Inizia da qui** → [`GUIDA_RAPIDA.md`](GUIDA_RAPIDA.md)

---

## 🚀 Uso Veloce

### Prima Volta:
1. Apri **PhotoBooth Printer** da Applicazioni
2. Scegli la tua stampante
3. Conferma le impostazioni

### Ogni Volta:
1. Apri **PhotoBooth Printer** → Clicca **"Start"**
2. Apri **Photo Booth**
3. Scatta foto
4. **Stampa automatica!** 🎉

---

## 💡 Caratteristiche

- ✅ **Installazione con drag & drop** - Nessun terminale!
- ✅ **Interfaccia grafica** - Menu semplici e chiari
- ✅ **Configurazione guidata** - Ti aiutiamo passo passo
- ✅ **Stampa istantanea** - Non appena la foto è pronta
- ✅ **Personalizzabile** - Scegli copie, formato, orientamento
- ✅ **Multilingua** - Italiano e Inglese
- ✅ **Gratuito e Open Source** - MIT License

---

## 📋 Requisiti

- Mac con macOS 10.12 o superiore
- Apple Photo Booth (incluso in macOS)
- Python 3 (l'app ti dirà se serve installarlo)
- Una stampante configurata

---

## 🎛️ Configurazione

Durante la prima apertura scegli:

- **Stampante**: Quale stampante usare
- **Copie**: Quante copie stampare (1, 2, 3...)
- **Formato carta**: Letter, A4, 4x6, 5x7...
- **Orientamento**: Verticale o Orizzontale
- **Colore**: A colori o Bianco & Nero

Puoi cambiare tutto in qualsiasi momento dal menu **Settings**.

---

## 🆘 Problemi?

### Non si apre l'app?
- Click **destro** sull'app → **Apri**
- Oppure: Preferenze Sistema → Sicurezza → "Apri comunque"

### Chiede Python 3?
- L'app aprirà il sito per scaricarlo
- Installa Python 3, poi riapri l'app

### Non stampa?
- Hai cliccato "Start"?
- La stampante è accesa?
- C'è carta?

**Guida completa**: [`INSTALL_INSTRUCTIONS.md`](INSTALL_INSTRUCTIONS.md)

---

## 📂 Struttura File

```
photobooth-print-addon/
├── PhotoBooth Printer.app/      ← APP PRINCIPALE (drag to Applications!)
├── GUIDA_RAPIDA.md              ← INIZIA QUI! 🟢
├── INSTALL_INSTRUCTIONS.md      ← Guida installazione dettagliata
├── README_ITALIANO.md           ← Questo file
├── README.md                    ← Documentazione tecnica (inglese)
├── create_installer.sh          ← Crea installer .dmg/.zip
├── photobooth_printer.py        ← Script principale (interno all'app)
├── requirements.txt             ← Dipendenze Python
└── LICENSE                      ← Licenza MIT
```

---

## 🎬 Come Funziona?

1. **Monitora** la cartella di Photo Booth
2. **Rileva** quando viene salvata una nuova foto
3. **Invia** automaticamente alla stampante
4. **Stampa** la foto!

Tutto automatico. Zero click dopo aver premuto "Start".

---

## 🔧 Per Sviluppatori

### Installazione da Codice

```bash
# Clona il repo
git clone https://github.com/daniemi1977/ipv.git
cd ipv/photobooth-print-addon

# Installa dipendenze
pip3 install -r requirements.txt

# Usa l'app
open "PhotoBooth Printer.app"

# Oppure da terminale
python3 photobooth_printer.py setup
python3 photobooth_printer.py start
```

### Creare Installer

```bash
chmod +x create_installer.sh
./create_installer.sh
```

Questo crea `PhotoBooth_Printer_Installer.dmg` pronto per la distribuzione.

---

## 🌟 Contribuisci

Contributi benvenuti!

- 🐛 Segnala bug su [GitHub Issues](https://github.com/daniemi1977/ipv/issues)
- 💡 Suggerisci funzionalità
- 🔧 Invia Pull Request
- 📖 Migliora la documentazione
- 🌍 Traduci in altre lingue

---

## 📄 Licenza

MIT License - Vedi [LICENSE](LICENSE)

Libero di usare, modificare e distribuire!

---

## 👨‍💻 Autore

Creato per semplificare la stampa automatica di Photo Booth su macOS.

---

## 🙏 Supporto

Se PhotoBooth Printer ti è utile:

- ⭐ Dai una stella su [GitHub](https://github.com/daniemi1977/ipv)
- 📢 Condividi con amici
- ☕ Offrici un caffè (link PayPal/Ko-fi)
- 💬 Lascia un feedback

---

## 📞 Contatti

- **GitHub Issues**: https://github.com/daniemi1977/ipv/issues
- **Email**: [inserire email]

---

## 🎉 Inizia Ora!

1. **Scarica** PhotoBooth Printer.app
2. **Trascina** in Applicazioni
3. **Apri** e segui la guida
4. **Stampa** automaticamente! 📸🖨️

**Leggi la [GUIDA RAPIDA](GUIDA_RAPIDA.md) per iniziare in 3 minuti!**

---

**Buon Divertimento! 🎈**

*Versione 1.0 - Dicembre 2025*
