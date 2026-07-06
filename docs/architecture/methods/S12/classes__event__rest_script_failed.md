# rest_script_failed — Methoden-Doku
**Datei:** `classes/event/rest_script_failed.php` · **LOC:** 78 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`rest_script_failed` (`extends \core\event\base`) ist ein Diagnose-Event, das signalisiert, dass ein externes REST-Skript nicht ordnungsgemaess ausgefuehrt werden konnte. Read-/Teaching-Level, keine eigene Persistenz (Logging-Framework), `objecttable = 'booking_options'`. Doc-Block erwartet ein `other`-Array mit einer Modulinstanz, das per `@property-read` annotiert, aber nicht via `validate_data` erzwungen wird. Liefert `get_url` auf die Booking-Aktivitaetsansicht. Kollaborateure: `get_string('restscriptfailed', ...)`, `moodle_url`, Logging-Pipeline, der REST-Skript-Ausfuehrungspfad.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'r'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('restscriptfailed', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Klartextbeschreibung mit `userid`, `objectid` und `context->id`. **Seiteneffekte:** keine Writes; liest `$this->userid`, `$this->objectid`, `$this->context->id`. **Rueckgabe:** string. **Bewertung:** C — greift direkt auf `$this->context->id` zu, ohne den von der Schwesterklasse `rest_script_success` verwendeten `?? 0`-Guard. Falls das Event je ohne gesetzten `context` (nur mit `contextid`) erzeugt/rekonstruiert wird, kann der Zugriff auf die Property einen Fehler werfen — siehe Finding.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Aktivitaetsansicht: `/mod/booking/view.php?id={contextinstanceid}`. **Seiteneffekte:** keine; konstruiert `moodle_url` aus `$this->contextinstanceid`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

## Bewertungs-Resümee
Funktionaler Diagnose-Event-Wrapper. Einzige Schwaeche: ungeguardeter `$this->context->id`-Zugriff in `get_description`, der gegenueber dem defensiveren `rest_script_success` inkonsistent ist (P3, nur im Beschreibungs-/Logging-Pfad relevant). Kein `validate_data` trotz dokumentiertem `other`-Vertrag. Klassen-Score **B / P3**.
