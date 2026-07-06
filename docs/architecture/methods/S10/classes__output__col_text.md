# col_text — Methoden-Doku
**Datei:** `classes/output/col_text.php` · **LOC:** 63 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_text` ist das minimalste Renderable/Templatable-DTO der Spalten-Familie: es haelt einen einzelnen Text-String und reicht ihn ans Mustache-Template weiter. Keine Persistenz, keine Kollaborateure.

## Methoden

### `public function __construct(string $text)` — public
- **Zweck:** Speichert den uebergebenen Text in `$this->text`.
- **Seiteneffekte:** keine.
- **Bewertung:** A — reiner Werte-Halter.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `['text' => $this->text]` fuer das Template zurueck.
- **Seiteneffekte:** keine.
- **Rueckgabe:** Array mit dem Text.
- **Bewertung:** A — trivialer Passthrough.

## Bewertungs-Resümee
Trivialstes Spalten-DTO ohne funktionale Auffaelligkeiten. Text wird roh durchgereicht (Escaping obliegt dem Template). Klassen-Score **A / P3**.
