# booking_rulesexecutionfailed — Methoden-Doku
**Datei:** `classes/event/booking_rulesexecutionfailes.php` · **LOC:** 78 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`booking_rulesexecutionfailed` ist ein Standard-Moodle-Logevent (`extends \core\event\base`), der ausgeloest wird, wenn die Ausfuehrung einer oder mehrerer Booking-Rules (S06) fuer eine Option fehlschlaegt. Reines Diagnose-/Logging-Event ohne eigene Persistenz; Eventdaten (`objectid`, `contextinstanceid`) kommen vom Ausloeser. Kein `validate_data()`. Hinweis: Der Dateiname `booking_rulesexecutionfailes.php` enthaelt einen Tippfehler (fehlendes „d"), waehrend Klasse und String `booking_rulesexecutionfailed` korrekt geschrieben sind. Kollaborateure: Moodle-Event-Framework, `get_string`, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten. **Seiteneffekte:** `crud='u'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Bewertung:** B — Der Inline-Kommentar `// Meaning: d = delete.` stammt aus Copy-Paste und widerspricht dem tatsaechlichen Wert `'u'` (update); irrefuehrend, aber ohne Funktionswirkung.

### `public static function get_name()` — public static
- **Zweck:** Anzeigename. **Rueckgabe:** `get_string('booking_rulesexecutionfailed', 'booking')` — beachte den abweichenden Unterstrich-Praefix im String-Key gegenueber den anderen Fehler-Events. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Logbeschreibung. **Rueckgabe:** statischer englischer Satz mit `$this->objectid`. **Bewertung:** B — hartkodiertes Englisch (kein `get_string`).

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Aktivitaetsansicht. **Rueckgabe:** `moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Rule-Diagnose-Event. Kosmetische Maengel: Datei-Tippfehler im Namen, irrefuehrender `crud`-Kommentar, hartkodierte Beschreibung. Verhalten korrekt und risikolos. Klassen-Score **B / P3**.
