# checklist_pdf — Methoden-Doku
**Datei:** `classes/checklist/checklist_pdf.php` · **LOC:** 111 · **Subsystem:** S17 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`checklist_pdf` ist ein duenner Adapter um Moodles `pdf` (TCPDF-Subklasse aus `lib/pdflib.php`). Er erweitert TCPDF nur um eine optionale Logo-Fusszeile aus einem gespeicherten `stored_file` und einen (leeren) Custom-Header-Hook. Keine eigene Persistenz; haelt lediglich das Datei-Objekt `$file`. Kollaborateur: `checklist_generator` (instanziiert die Klasse), `stored_file`-API (`get_filepath`, `get_filename`, `get_imageinfo`, `get_content`, `get_mimetype`). `require_once($CFG->libdir.'/pdflib.php')` laedt die Basisklasse.

## Methoden

### `public function footer()` — public
- **Zweck:** TCPDF-Override; zeichnet, wenn ein Footer-Bild gesetzt wurde, ein Logo am unteren Seitenrand (zentriert, Hoehe 15mm). **Seiteneffekte:** `SetY(-20)`, `Image('@'.$footerlogo, ...)` — rendert das Bild direkt aus dem Binaerinhalt. **Rueckgabe:** void. **Bewertung:** B — die Hoehenlogik ist ein No-op: `$h = 15` wird vor der `if`-Pruefung gesetzt und im `if ($imageinfo['height'] > 15)`-Zweig erneut auf `15` gesetzt; kleine Bilder werden also dennoch auf 15mm hochskaliert. `$filepath` wird berechnet, aber nie verwendet (toter lokaler Wert). Da `checklist_generator` `SetPrintFooter(false)` setzt, wird `footer()` im aktuellen Hauptpfad ohnehin nicht aufgerufen.

### `public function setfooterimage($file)` — public
- **Zweck:** Setzt das `stored_file`-Objekt fuer die Fusszeile. **Seiteneffekte:** schreibt `$this->file`. **Rueckgabe:** void. **Bewertung:** A — wird im Generator-Pfad allerdings nie aufgerufen, sodass das Footer-Feature derzeit ungenutzt ist.

### `protected function custom_page_header()` — protected
- **Zweck:** Platzhalter-Hook fuer kuenftige Header-Anpassungen. **Seiteneffekte:** keine. **Rueckgabe:** void. **Bewertung:** B — leerer Stub; weder von TCPDF noch intern aufgerufen (TCPDFs Hook heisst `Header()`), faktisch totes Geruest.

## Bewertungs-Resümee
Schmaler, ungefaehrlicher TCPDF-Adapter. Inhaltlich harmlose Schwaechen: die redundante `$h`-Hoehenlogik, der ungenutzte `$filepath` und der nie aufgerufene Header-Stub. Im aktuellen Aufrufpfad ist die Footer-Funktionalitaet inaktiv (Footer abgeschaltet, `setfooterimage` nie aufgerufen). Klassen-Score **A / —**.
