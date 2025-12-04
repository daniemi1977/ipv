# IPV Production System Pro - Translation Files

## File di Traduzione / Translation Files

Questa directory contiene i file di traduzione per il plugin IPV Production System Pro.

### File Inclusi

- **ipv-production-system-pro.pot** - Template di traduzione (Portable Object Template)
  - Contiene tutte le stringhe in inglese pronte per la traduzione
  - Usato come base per creare nuove traduzioni

- **ipv-production-system-pro-it_IT.po** - Traduzione italiana (Portable Object)
  - Contiene tutte le traduzioni in italiano
  - File leggibile e modificabile

## Come Compilare i File .po in .mo

Per utilizzare le traduzioni, devi compilare il file `.po` in formato `.mo` (Machine Object).

### Opzione 1: Usando msgfmt (Linux/Mac)

```bash
# Installa gettext se non è già installato
# Ubuntu/Debian:
sudo apt-get install gettext

# macOS:
brew install gettext

# Compila il file .po in .mo
msgfmt languages/ipv-production-system-pro-it_IT.po -o languages/ipv-production-system-pro-it_IT.mo
```

### Opzione 2: Usando WP-CLI

```bash
# Se hai WP-CLI installato
wp i18n make-mo languages/
```

### Opzione 3: Usando Poedit (Windows/Mac/Linux)

1. Scarica e installa [Poedit](https://poedit.net/)
2. Apri il file `ipv-production-system-pro-it_IT.po`
3. Clicca su "File" → "Compila in MO"
4. Salva nella stessa directory `languages/`

### Opzione 4: Usando Loco Translate (WordPress Plugin)

1. Installa il plugin "Loco Translate" in WordPress
2. Vai su "Loco Translate" → "Plugins"
3. Seleziona "IPV Production System Pro"
4. Il plugin compilerà automaticamente i file

## Testare le Traduzioni

Dopo aver compilato il file `.mo`:

1. Assicurati che WordPress sia configurato per l'italiano:
   - Vai su `Impostazioni` → `Generali`
   - Imposta "Lingua del sito" su "Italiano"

2. Il plugin caricherà automaticamente le traduzioni italiane

3. Per testare in inglese:
   - Cambia la lingua del sito in "English (United States)"
   - Il plugin mostrerà tutte le stringhe in inglese

## Aggiungere Nuove Lingue

Per creare una traduzione in un'altra lingua:

```bash
# 1. Crea un nuovo file .po dalla template
msginit -i languages/ipv-production-system-pro.pot \
        -o languages/ipv-production-system-pro-LOCALE.po \
        -l LOCALE

# Esempio per spagnolo:
msginit -i languages/ipv-production-system-pro.pot \
        -o languages/ipv-production-system-pro-es_ES.po \
        -l es_ES

# 2. Traduci il file .po con Poedit o un editor di testo

# 3. Compila in .mo
msgfmt languages/ipv-production-system-pro-es_ES.po \
       -o languages/ipv-production-system-pro-es_ES.mo
```

## Formato dei File

### Locale Format

I file di traduzione seguono il formato WordPress standard:

- **it_IT** - Italiano (Italia)
- **en_US** - English (United States)
- **es_ES** - Español (España)
- **fr_FR** - Français (France)
- **de_DE** - Deutsch (Deutschland)

### Naming Convention

```
{plugin-text-domain}-{locale}.{extension}

Esempi:
- ipv-production-system-pro-it_IT.po
- ipv-production-system-pro-it_IT.mo
- ipv-production-system-pro-es_ES.po
- ipv-production-system-pro-es_ES.mo
```

## Aggiornare le Traduzioni

Se aggiungi nuove stringhe al plugin:

1. Rigenera il file `.pot`:
   ```bash
   wp i18n make-pot . languages/ipv-production-system-pro.pot
   ```

2. Aggiorna i file `.po` esistenti:
   ```bash
   msgmerge --update languages/ipv-production-system-pro-it_IT.po \
                     languages/ipv-production-system-pro.pot
   ```

3. Traduci le nuove stringhe nel file `.po`

4. Ricompila in `.mo`

## Struttura Directory

```
languages/
├── README.md                                    (questo file)
├── ipv-production-system-pro.pot               (template)
├── ipv-production-system-pro-it_IT.po          (italiano - sorgente)
├── ipv-production-system-pro-it_IT.mo          (italiano - compilato)
└── ipv-production-system-pro-{locale}.po/mo    (altre lingue)
```

## Note Tecniche

- **Text Domain**: `ipv-production-system-pro`
- **Domain Path**: `/languages`
- **Base Language**: English (codice sorgente)
- **Stringhe Tradotte**: ~150+ stringhe nell'interfaccia admin

## Supporto

Per problemi o domande sulle traduzioni:
- Repository: https://github.com/daniemi1977/ipv
- Issues: https://github.com/daniemi1977/ipv/issues
