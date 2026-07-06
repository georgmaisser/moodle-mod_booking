# col_text_with_description — Methoden-Doku
**Datei:** `classes/output/col_text_with_description.php` · **LOC:** 85 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_text_with_description` ist ein reines Renderable/Templatable-Werte-DTO fuer eine Text-Spalte mit Optionstitel, Titel-Prefix und Beschreibung. Es haelt nur Konstruktor-uebergebene Werte vor und reicht sie ans Template weiter. Keine Persistenz, keine Kollaborateure.

## Methoden

### `public function __construct(int $optionid, string $text, string $titleprefix, string $description)` — public
- **Zweck:** Speichert `optionid`, `text` (Optionstitel), `titleprefix` und `description` in die gleichnamigen Properties.
- **Seiteneffekte:** keine.
- **Bewertung:** A — reiner Werte-Halter.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Baut das Template-Array mit `optionid`/`text`/`description`; `titleprefix` wird nur bei nicht-leerem Wert ergaenzt.
- **Seiteneffekte:** keine.
- **Rueckgabe:** assoziatives Array fuer das Mustache-Template.
- **Bewertung:** A — schlicht und korrekt; das konditionale Hinzufuegen des Prefix vermeidet leere Template-Knoten.

### Triviale Properties
Vier oeffentliche Properties (`optionid`, `text`, `titleprefix`, `description`, Z.39–49) als Werte-Halter.

## Bewertungs-Resümee
Triviales, korrektes DTO ohne Auffaelligkeiten. Beschreibung/Text werden hier roh durchgereicht (Escaping/`format_text` liegt beim Aufrufer bzw. Template). Klassen-Score **A / P3**.
