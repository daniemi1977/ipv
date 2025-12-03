<?php
/**
 * IPV Production System Pro - Translation Automation Script
 *
 * This script helps automate the translation process by:
 * 1. Scanning PHP files for Italian strings
 * 2. Wrapping them with __() or _e()
 * 3. Generating translation mappings
 *
 * Usage: php tools/translate-strings.php
 *
 * @package IPV_Production_System_Pro
 * @version 9.0.0
 */

// Configuration
$plugin_dir = dirname( __DIR__ );
$includes_dir = $plugin_dir . '/includes';
$textdomain = 'ipv-production-system-pro';

// Common Italian to English translations
$translations = [
    // Admin UI
    'Impostazioni' => 'Settings',
    'Salva modifiche' => 'Save Changes',
    'Salva' => 'Save',
    'Annulla' => 'Cancel',
    'Elimina' => 'Delete',
    'Modifica' => 'Edit',
    'Aggiungi' => 'Add',
    'Carica' => 'Upload',
    'Scarica' => 'Download',
    'Cerca' => 'Search',
    'Filtra' => 'Filter',
    'Aggiorna' => 'Update',
    'Ripristina' => 'Reset',

    // Status
    'Attivo' => 'Active',
    'Disattivo' => 'Inactive',
    'In corso' => 'In Progress',
    'Completato' => 'Completed',
    'Errore' => 'Error',
    'Successo' => 'Success',
    'Fallito' => 'Failed',
    'Avviso' => 'Warning',

    // Video specific
    'Video' => 'Video',
    'Trascrizione' => 'Transcription',
    'Descrizione' => 'Description',
    'Durata' => 'Duration',
    'Visualizzazioni' => 'Views',
    'Mi piace' => 'Likes',
    'Commenti' => 'Comments',
    'Data pubblicazione' => 'Publication Date',
    'Canale' => 'Channel',
    'Tag' => 'Tags',

    // Actions
    'Importa' => 'Import',
    'Esporta' => 'Export',
    'Elabora' => 'Process',
    'Genera' => 'Generate',
    'Rigenera' => 'Regenerate',
    'Sincronizza' => 'Synchronize',
    'Aggiorna dati' => 'Update Data',

    // Messages
    'Operazione completata con successo' => 'Operation completed successfully',
    'Si è verificato un errore' => 'An error occurred',
    'Nessun risultato trovato' => 'No results found',
    'Sei sicuro?' => 'Are you sure?',
    'Caricamento in corso' => 'Loading',
    'Attendere prego' => 'Please wait',

    // Queue/Process
    'Coda' => 'Queue',
    'In coda' => 'Queued',
    'Elaborazione' => 'Processing',
    'Prossima esecuzione' => 'Next execution',

    // Dashboard
    'Video Pubblicati' => 'Published Videos',
    'Video in Bozza' => 'Draft Videos',
    'Totale Video' => 'Total Videos',
    'Statistiche' => 'Statistics',
];

echo "=== IPV Production System Pro - Translation Automation ===\n\n";

// Step 1: Find all PHP files
echo "[1] Scanning PHP files...\n";
$files = glob( $includes_dir . '/*.php' );
echo "Found " . count( $files ) . " files\n\n";

// Step 2: Generate translation mapping file
echo "[2] Generating translation mapping...\n";
$mapping_file = $plugin_dir . '/tools/translation-map.json';
file_put_contents( $mapping_file, json_encode( $translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
echo "✓ Mapping saved to: tools/translation-map.json\n\n";

// Step 3: Scan for untranslated strings (Italian patterns)
echo "[3] Scanning for Italian strings that need translation...\n";
$italian_patterns = [
    '/["\']([A-ZÀÈÉÌÒÙ][a-zàèéìòù]+(?:\s+[a-zàèéìòù]+){1,5})["\']/u',  // Italian sentences
];

$untranslated = [];
foreach ( $files as $file ) {
    $content = file_get_contents( $file );
    $filename = basename( $file );

    // Skip already translated strings
    if ( strpos( $content, '__(' ) || strpos( $content, '_e(' ) || strpos( $content, '_x(' ) ) {
        continue;
    }

    foreach ( $italian_patterns as $pattern ) {
        if ( preg_match_all( $pattern, $content, $matches ) ) {
            foreach ( $matches[1] as $string ) {
                if ( !isset( $untranslated[$string] ) ) {
                    $untranslated[$string] = [];
                }
                $untranslated[$string][] = $filename;
            }
        }
    }
}

// Step 4: Generate report
echo "Found " . count( $untranslated ) . " unique strings needing translation\n\n";

if ( !empty( $untranslated ) ) {
    echo "[4] Generating translation report...\n";
    $report_file = $plugin_dir . '/tools/translation-report.txt';
    $report = "=== Strings Needing Translation ===\n\n";

    foreach ( $untranslated as $string => $files_list ) {
        $report .= "String: \"{$string}\"\n";
        $report .= "Files: " . implode( ', ', array_unique( $files_list ) ) . "\n";
        if ( isset( $translations[$string] ) ) {
            $report .= "Suggested: \"{$translations[$string]}\"\n";
        }
        $report .= "\n";
    }

    file_put_contents( $report_file, $report );
    echo "✓ Report saved to: tools/translation-report.txt\n\n";
}

// Step 5: Instructions
echo "=== Next Steps ===\n\n";
echo "1. Review: tools/translation-report.txt\n";
echo "2. Add missing translations to tools/translation-map.json\n";
echo "3. Run: php tools/apply-translations.php (to auto-apply)\n";
echo "4. Generate .pot: wp i18n make-pot . languages/ipv-production-system-pro.pot\n";
echo "5. Create .po: msginit -i languages/ipv-production-system-pro.pot -o languages/ipv-production-system-pro-it_IT.po -l it_IT\n\n";

echo "=== Summary ===\n";
echo "✓ Translation mapping created\n";
echo "✓ " . count( $untranslated ) . " strings need translation\n";
echo "✓ Ready for manual review\n\n";
