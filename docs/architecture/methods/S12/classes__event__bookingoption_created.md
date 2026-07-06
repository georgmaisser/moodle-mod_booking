# bookingoption_created — Methoden-Doku
**Datei:** `classes/event/bookingoption_created.php` · **LOC:** 79 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_created` ist ein Moodle-Standard-Event (`\core\event\base`), das beim Anlegen einer Buchungsoption ausgeloest wird. Es traegt keine eigene Persistenz; die Event-Daten landen ueber den Moodle-Eventbus im Logstore. `objecttable` ist `booking_options`, `crud = 'c'`, `edulevel = LEVEL_TEACHING`. Kollaborateure: `get_string` (Sprachpaket `booking`), `moodle_url` (Verlinkung auf `report.php`). Konsumenten: Logging, Event-Observer und `mod_booking`-Rules.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Pflicht-Metadaten des Events (`crud='c'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`). **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A — Boilerplate exakt nach Moodle-Event-Contract.

### `public static function get_name()` — public static
- **Zweck:** Liefert den uebersetzten Anzeigenamen des Events fuer das Log-UI. **Seiteneffekte:** `get_string('bookingoptioncreated', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert die englische Klartext-Beschreibung (`User with id ... created 'booking option' with id ...`). **Seiteneffekte:** keine; interpoliert `$this->userid`/`$this->objectid`. **Rueckgabe:** string. **Bewertung:** A — fest englisch (Moodle-Konvention fuer Log-Descriptions).

### `public function get_url()` — public
- **Zweck:** Deep-Link zum Report der angelegten Option. **Seiteneffekte:** Konstruiert `moodle_url('/mod/booking/report.php', ['id' => contextinstanceid, 'optionid' => objectid])`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

## Bewertungs-Resümee
Reines Standard-Event ohne Logik oder Persistenz; vier kanonische Pflichtmethoden, alle korrekt. Keine funktionalen Schwachstellen. Klassen-Score **B / P3**.
