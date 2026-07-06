# booking_failed — Methoden-Doku
**Datei:** `classes/event/booking_failed.php` · **LOC:** 78 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`booking_failed` ist ein Standard-Moodle-Logevent (`extends \core\event\base`), das ausgeloest wird, wenn ein Buchungsvorgang fuer eine Option fehlschlaegt. Zweck ist die Protokollierung/Diagnose im Logstore; eigene Persistenz gibt es nicht. Die Eventdaten (`objectid`, `userid`, `contextinstanceid`) liefert der Ausloeser via `create()`. Kein `validate_data()`. Strukturell identisch zu `booking_afteractionsfailed` (gleiches init, gleiche URL, nur anderer Name/Text). Kollaborateure: Moodle-Event-Framework, `get_string`, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten. **Seiteneffekte:** `crud='d'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Bewertung:** B — `crud='d'` (delete) passt nicht zu einem fehlgeschlagenen Buchungsversuch; nur Klassifizierung, daher unkritisch.

### `public static function get_name()` — public static
- **Zweck:** Anzeigename. **Rueckgabe:** `get_string('bookingfailed', 'booking')`. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Logbeschreibung. **Rueckgabe:** statischer englischer Satz mit `$this->userid` und `$this->objectid`. **Bewertung:** B — hartkodiertes Englisch (kein `get_string`), konsistent mit den uebrigen Fehler-Events.

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Aktivitaetsansicht. **Rueckgabe:** `moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

## Bewertungs-Resümee
Minimaler Diagnose-Event, faktisch ein Klon von `booking_afteractionsfailed` mit anderem Namen/Text. Schwaechen: `crud='d'` semantisch unpassend, Beschreibung hartkodiert. Funktional korrekt. Klassen-Score **B / P3**.
