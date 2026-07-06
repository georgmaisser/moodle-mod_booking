# bookingoption_deleted — Methoden-Doku
**Datei:** `classes/event/bookingoption_deleted.php` · **LOC:** 77 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_deleted` ist ein Moodle-Standard-Event (`\core\event\base`), ausgeloest beim Loeschen einer Buchungsoption. Keine eigene Persistenz; Daten gehen ueber den Eventbus in den Logstore. `objecttable='booking_options'`, `crud='d'`, `edulevel=LEVEL_TEACHING`. Kollaborateure: `get_string` (Sprachpaket `booking`), `moodle_url`. Da die Option zum Zeitpunkt der Beschreibung bereits geloescht ist, verlinkt `get_url` bewusst auf die Instanz-Ansicht (`view.php`) statt auf einen Option-Report.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='d'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`). **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Uebersetzter Anzeigename. **Seiteneffekte:** `get_string('bookingoptiondeleted', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Klartext-Beschreibung (`User with id ... deleted 'booking option' with id ...`). **Seiteneffekte:** keine; interpoliert `userid`/`objectid`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Instanz-Ansicht (statt auf die geloeschte Option). **Seiteneffekte:** `moodle_url('/mod/booking/view.php', ['id' => contextinstanceid])`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A — bewusst auf `view.php`, da der Option-Report nach dem Loeschen ins Leere zeigte.

## Bewertungs-Resümee
Kanonisches Delete-Event, vier Pflichtmethoden, korrekt und ohne Logik. Sinnvolle URL-Wahl gegenueber dem created-Event. Keine Befunde. Klassen-Score **B / P3**.
