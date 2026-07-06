# signin_pdf — Methoden-Doku
**Datei:** `classes/signinsheet/signin_pdf.php` · **LOC:** 115 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_*.md)

## Klassenueberblick
`signin_pdf` ist ein duenner Adapter um Moodles TCPDF-Wrapper (`extends pdf`, geladen via `require_once($CFG->libdir.'/pdflib.php')`). Er erweitert TCPDF um zwei fuer das Sign-in-Sheet benoetigte Faehigkeiten: einen Page-Break-Helper und eine eigene Fusszeile mit optionalem Logo-Bild. Persistenz: keine; haelt nur ein `stored_file`-Objekt im privaten Feld `$file`. Kollaborateure: TCPDF-Basismethoden (`checkPageBreak`, `SetY`, `Image`), Moodle-`stored_file`-API (`get_filepath`, `get_filename`, `get_imageinfo`, `get_content`, `get_mimetype`). Wird vom Sign-in-Sheet-Generator als PDF-Backend instanziiert.

## Methoden

### `public function go_to_newline($h)` — public
- **Zweck:** Erzwingt einen Zeilenumbruch bzw. Seitenumbruch unter Beruecksichtigung der Zellhoehe `$h`. **Seiteneffekte:** delegiert an TCPDF `checkPageBreak($h, null, true)` (kann eine neue Seite anlegen). **Rueckgabe:** `bool` — ob ein Seitenumbruch ausgeloest wurde. **Bewertung:** A — reiner Pass-through-Helper.

### `public function footer()` — public
- **Zweck:** Ueberschreibt den TCPDF-Footer-Hook; positioniert den Cursor 20 mm vom Unterrand und zeichnet — falls per `setfooterimage()` ein Logo gesetzt wurde — das Bild zentriert in die Fusszeile. **Seiteneffekte:** `SetY(-20)`; bei vorhandenem `$this->file` liest es Mime-Type (→ `$filetype` ohne `image/`-Praefix) und Roh-Content (`get_content()`) und ruft TCPDF `Image('@'.<binary>, ...)` mit fixer Hoehe `$h = 15`. **Bewertung:** A — funktioniert; zwei kosmetische Nuancen: (1) der Zweig `if ($imageinfo['height'] > 15) { $h = 15; }` ist ein No-op, da `$h` ohnehin bereits `15` ist (Z.76/78-80); (2) `$filepath` (Z.71) wird berechnet, aber nie verwendet. Kein funktionaler Fehler, da `Image('@'.$content)` direkt aus dem In-Memory-Content rendert.

### `public function setfooterimage($file)` — public
- **Zweck:** Setzt das `stored_file`-Logo, das `footer()` spaeter rendert. **Seiteneffekte:** weist `$this->file = $file` zu. **Rueckgabe:** `void`. **Bewertung:** A — trivialer Setter.

### Triviale Properties
Ein privates Feld `$file` (default `false`, Z.51) als Logo-Halter; via `setfooterimage()` gesetzt, in `footer()` als Truthiness-Gate genutzt.

## Bewertungs-Resümee
Minimaler, gut nachvollziehbarer TCPDF-Adapter ohne Persistenz und ohne riskante Logik. Einzige Auffaelligkeiten sind toter Code (No-op-`if`, ungenutztes `$filepath`) — rein kosmetisch. Klassen-Score **A / -**.
