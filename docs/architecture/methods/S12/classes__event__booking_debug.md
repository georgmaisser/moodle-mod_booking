# booking_debug — Methoden-Doku
**Datei:** `classes/event/booking_debug.php` · **LOC:** 72 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`booking_debug` ist ein Hilfs-/Diagnose-Logevent (`extends \core\event\base`), das nur im `bookingdebugmode` gefeuert wird, um beliebige Eventdaten zur Fehlersuche in den Logstore zu schreiben. Keine eigene Persistenz, kein `validate_data()`. Besonderheit: `get_description()` serialisiert die komplette `$this->data`-Struktur via `json_encode` in den Log. Kollaborateure: Moodle-Event-Framework, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten. **Seiteneffekte:** `crud='u'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking'`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Anzeigename. **Rueckgabe:** hartkodierter String `"Debug Booking"` (kein `get_string`). **Bewertung:** B — als reines Debug-Werkzeug akzeptabel, aber nicht uebersetzbar.

### `public function get_description()` — public
- **Zweck:** Liefert den gesamten Eventzustand fuer die Fehlersuche. **Seiteneffekte:** `json_encode($this->data)`. **Rueckgabe:** `"We got the following data: <json>"`. **Bewertung:** B — bewusst breite Datenausgabe; im Debugmodus gewollt, kann aber sensible Felder in den Logstore schreiben (siehe Resümee). Funktional korrekt.

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Teilnehmer-Verwaltung. **Rueckgabe:** `moodle_url('/mod/booking/subscribeusers.php', ['id' => $this->contextinstanceid, 'optionid' => $this->objectid])`. **Bewertung:** A.

## Bewertungs-Resümee
Reiner Debug-Event mit absichtlich verbosem `json_encode`-Dump des gesamten Eventzustands. Das ist im `bookingdebugmode` gewollt, sollte aber nicht produktiv aktiviert sein, da der Logstore so potenziell sensible Daten persistiert. Hartkodierter Name nicht uebersetzbar. Funktional unkritisch. Klassen-Score **B / P3**.
