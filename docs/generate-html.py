#!/usr/bin/env python3
"""
IPV Production System Pro - Markdown to HTML Converter
Converts MANUALE-UTENTE.md to a beautiful HTML that can be printed to PDF
"""

import re
import os

def markdown_to_html(md_file, html_file):
    """Convert markdown to HTML with print-friendly CSS"""

    # Read markdown
    with open(md_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Simple markdown conversions
    html_content = content

    # Headers
    html_content = re.sub(r'^# (.+)$', r'<h1>\1</h1>', html_content, flags=re.MULTILINE)
    html_content = re.sub(r'^## (.+)$', r'<h2>\1</h2>', html_content, flags=re.MULTILINE)
    html_content = re.sub(r'^### (.+)$', r'<h3>\1</h3>', html_content, flags=re.MULTILINE)
    html_content = re.sub(r'^#### (.+)$', r'<h4>\1</h4>', html_content, flags=re.MULTILINE)

    # Bold
    html_content = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', html_content)

    # Italic
    html_content = re.sub(r'\*(.+?)\*', r'<em>\1</em>', html_content)

    # Inline code
    html_content = re.sub(r'`([^`]+)`', r'<code>\1</code>', html_content)

    # Code blocks
    html_content = re.sub(
        r'```(\w+)?\n(.*?)```',
        r'<pre><code>\2</code></pre>',
        html_content,
        flags=re.DOTALL
    )

    # Links
    html_content = re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'<a href="\2">\1</a>', html_content)

    # Lists
    lines = html_content.split('\n')
    in_list = False
    result_lines = []

    for line in lines:
        # Unordered list
        if line.strip().startswith('- ') or line.strip().startswith('* ') or line.strip().startswith('✅'):
            if not in_list:
                result_lines.append('<ul>')
                in_list = True
            item = line.strip()[2:] if line.strip()[0] in ['-', '*'] else line.strip()
            result_lines.append(f'<li>{item}</li>')
        # Ordered list
        elif re.match(r'^\d+\.\s', line.strip()):
            if not in_list:
                result_lines.append('<ol>')
                in_list = True
            item = re.sub(r'^\d+\.\s', '', line.strip())
            result_lines.append(f'<li>{item}</li>')
        else:
            if in_list:
                if '</ul>' not in result_lines[-1] and '</ol>' not in result_lines[-1]:
                    result_lines.append('</ul>' if '<ul>' in '\n'.join(result_lines[-10:]) else '</ol>')
                in_list = False
            result_lines.append(line)

    html_content = '\n'.join(result_lines)

    # Horizontal rules
    html_content = re.sub(r'^---$', r'<hr>', html_content, flags=re.MULTILINE)

    # Tables
    def convert_table(match):
        lines = match.group(0).strip().split('\n')
        if len(lines) < 2:
            return match.group(0)

        # Parse header
        headers = [cell.strip() for cell in lines[0].split('|') if cell.strip()]

        # Skip separator line

        # Parse rows
        rows = []
        for line in lines[2:]:
            cells = [cell.strip() for cell in line.split('|') if cell.strip()]
            if cells:
                rows.append(cells)

        # Generate HTML table
        html = '<table>\n<thead>\n<tr>\n'
        for header in headers:
            html += f'<th>{header}</th>\n'
        html += '</tr>\n</thead>\n<tbody>\n'
        for row in rows:
            html += '<tr>\n'
            for cell in row:
                html += f'<td>{cell}</td>\n'
            html += '</tr>\n'
        html += '</tbody>\n</table>\n'
        return html

    html_content = re.sub(
        r'\|[^\n]+\|\n\|[-:\s|]+\|\n(\|[^\n]+\|\n)+',
        convert_table,
        html_content
    )

    # Paragraphs
    html_content = re.sub(r'\n\n', '</p>\n<p>', html_content)

    # Wrap in HTML structure
    html_template = f"""<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPV Production System Pro - Manuale Utente v9.0.0</title>
    <style>
        @media print {{
            @page {{
                margin: 2cm;
                size: A4;
            }}
            body {{
                font-size: 10pt;
            }}
            h1 {{
                page-break-before: always;
                font-size: 24pt;
            }}
            h1:first-of-type {{
                page-break-before: avoid;
            }}
            h2 {{
                page-break-after: avoid;
                font-size: 18pt;
            }}
            pre, code {{
                page-break-inside: avoid;
            }}
            a {{
                color: #0066cc;
                text-decoration: none;
            }}
        }}

        body {{
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }}

        h1 {{
            color: #1a1a1a;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 10px;
            margin-top: 40px;
            font-size: 32px;
        }}

        h2 {{
            color: #0066cc;
            margin-top: 30px;
            font-size: 24px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }}

        h3 {{
            color: #333;
            margin-top: 20px;
            font-size: 18px;
        }}

        h4 {{
            color: #555;
            margin-top: 15px;
            font-size: 16px;
        }}

        code {{
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 2px 6px;
            font-family: "Courier New", Consolas, monospace;
            font-size: 0.9em;
            color: #c7254e;
        }}

        pre {{
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
            margin: 15px 0;
        }}

        pre code {{
            background: none;
            border: none;
            padding: 0;
            color: #333;
        }}

        table {{
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }}

        th, td {{
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }}

        th {{
            background: #0066cc;
            color: white;
            font-weight: bold;
        }}

        tr:nth-child(even) {{
            background: #f9f9f9;
        }}

        ul, ol {{
            margin: 15px 0;
            padding-left: 30px;
        }}

        li {{
            margin: 5px 0;
        }}

        hr {{
            border: none;
            border-top: 2px solid #ddd;
            margin: 30px 0;
        }}

        a {{
            color: #0066cc;
            text-decoration: none;
        }}

        a:hover {{
            text-decoration: underline;
        }}

        strong {{
            color: #000;
        }}

        .header {{
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            border-radius: 10px;
        }}

        .header h1 {{
            color: white;
            border: none;
            margin: 0;
            font-size: 36px;
        }}

        .header p {{
            margin: 10px 0 0 0;
            font-size: 18px;
        }}

        .print-button {{
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }}

        .print-button:hover {{
            background: #0052a3;
        }}

        @media print {{
            .print-button {{
                display: none;
            }}
            .header {{
                background: #0066cc;
            }}
        }}
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Stampa / Salva PDF</button>

    <div class="header">
        <h1>IPV Production System Pro</h1>
        <p>Manuale Utente Completo - Versione 9.0.0</p>
        <p>Dicembre 2024</p>
    </div>

    <p>{html_content}</p>
</body>
</html>"""

    # Write HTML
    with open(html_file, 'w', encoding='utf-8') as f:
        f.write(html_template)

    return html_file

if __name__ == '__main__':
    script_dir = os.path.dirname(os.path.abspath(__file__))
    md_file = os.path.join(script_dir, 'MANUALE-UTENTE.md')
    html_file = os.path.join(script_dir, 'MANUALE-UTENTE.html')

    print("=" * 60)
    print("IPV Production System Pro - Generatore HTML")
    print("=" * 60)
    print()

    if not os.path.exists(md_file):
        print(f"❌ Errore: File {md_file} non trovato")
        exit(1)

    print(f"📄 File sorgente: {md_file}")
    print(f"🌐 File destinazione: {html_file}")
    print()
    print("⚙️  Conversione in corso...")

    output = markdown_to_html(md_file, html_file)

    file_size = os.path.getsize(output)
    print()
    print("=" * 60)
    print("✅ HTML generato con successo!")
    print("=" * 60)
    print()
    print(f"📊 Dimensione: {file_size:,} bytes")
    print(f"📍 Percorso: {output}")
    print()
    print("🖨️  Per creare il PDF:")
    print()
    print("  Metodo 1 (Browser):")
    print("    1. Apri il file HTML nel browser")
    print("    2. Clicca sul pulsante 'Stampa / Salva PDF' oppure premi Ctrl+P")
    print("    3. Seleziona 'Salva come PDF'")
    print("    4. Salva il file")
    print()
    print("  Metodo 2 (Online):")
    print("    1. Visita https://www.sejda.com/html-to-pdf")
    print("    2. Carica il file HTML")
    print("    3. Scarica il PDF")
    print()
    print("🎉 Pronto!")
    print("=" * 60)
