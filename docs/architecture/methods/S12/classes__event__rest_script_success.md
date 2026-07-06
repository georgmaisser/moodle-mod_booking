# rest_script_success — Methoden-Doku
**Datei:** `classes/event/rest_script_success.php` · **LOC:** 76 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`rest_script_success` (`extends \core\event\base`) ist das Erfolgs-Pendant zu `rest_script_failed`: Diagnose-Event, das die ordnungsgemaesse Ausfuehrung eines externen REST-Skripts signalisiert. Read-/Teaching-Level, keine eigene Persistenz (Logging-Framework), `objecttable = 'booking_options'`. Doc-Block annotiert ein `other`-Array (Modulinstanz) per `@property-read`, ohne `validate_data`-Erzwingung. Liefert `get_url` auf die Booking-Aktivitaetsansicht. Kollaborateure: `get_string('restscriptsuccess', ...)`, `moodle_url`, Logging-Pipeline, REST-Skript-Ausfuehrungspfad.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'r'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('restscriptsuccess', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Klartextbeschreibung mit `userid`, `objectid` und Kontext-ID. **Seiteneffekte:** keine; liest `$this->userid`, `$this->objectid` und `$this->context->id ?? 0`. **Rueckgabe:** string. **Bewertung:** A — verwendet den `?? 0`-Null-Guard fuer den Kontext, anders als das Failed-Pendant; defensiv korrekt.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Aktivitaetsansicht: `/mod/booking/view.php?id={contextinstanceid}`. **Seiteneffekte:** keine; konstruiert `moodle_url` aus `$this->contextinstanceid`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

## Bewertungs-Resümee
Konventionskonformer Diagnose-Event-Wrapper, in `get_description` defensiver als das Failed-Pendant (Kontext-Null-Guard). Kein `validate_data` trotz dokumentiertem `other`-Vertrag (unkritisch). Klassen-Score **B / P3**.
