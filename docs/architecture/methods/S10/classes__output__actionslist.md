# actionslist — Methoden-Doku
**Datei:** `classes/output/actionslist.php` · **LOC:** 85 · **Subsystem:** S10 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`actionslist` ist ein Renderable/Templatable-DTO, das die Liste der BO-Aktionen (boactions) einer Buchungsoption fuer ein Mustache-Template aufbereitet. Es haelt `cmid`, `optionid` und ein normalisiertes `actions`-Array. Keine Persistenz; reine View-Aufbereitung. Kollaborateure: `get_string` (Lokalisierung der Aktionsnamen), Renderer/Template.

## Methoden

### `public function __construct(int $cmid, int $optionid, $actions = [])` — public
- **Zweck:** Speichert `cmid`/`optionid` und normalisiert jedes uebergebene Action-Objekt: setzt `name` (aus `boactionname` mit Fallback `action_type`) und `localizedactionname` via `get_string($action->action_type, 'mod_booking')`, und legt es als Array in `$this->actions` ab. **Seiteneffekte:** `get_string(...)` pro Aktion; mutiert die uebergebenen Action-Objekte (by reference, da Objekte) vor dem Cast zu Array. **Bewertung:** A — kompakt und klar. Kleinrisiko: `get_string` ohne Existenzpruefung auf den `action_type`-Key; bei unbekanntem Typ wuerde Moodle eine `[[...]]`-Platzhalter-Warnung erzeugen, aber kein Fehler.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das flache Render-Array `['cmid', 'optionid', 'actions']` zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A.

### Triviale Properties
`$cmid`, `$optionid` (jeweils mit irrefuehrendem `= []`-Default initialisiert, obwohl ints; durch Konstruktor sofort ueberschrieben) und `$actions` (Z.39–46) als Werte-Halter.

## Bewertungs-Resümee
Standardkonformes Output-DTO ohne Logik-Risiken. Einzige Stilnotiz: die `int`-Properties sind mit `[]` vorbelegt (Doc-/Typ-Inkonsistenz), praktisch ohne Wirkung, da der Konstruktor sie setzt. Klassen-Score **A / -**.
