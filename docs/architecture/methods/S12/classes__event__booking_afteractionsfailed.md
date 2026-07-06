# booking_afteractionsfailed — Methoden-Doku
**Datei:** `classes/event/booking_afteractionsfailed.php` · **LOC:** 78 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`booking_afteractionsfailed` ist ein Standard-Moodle-Logevent (`extends \core\event\base`), das gefeuert wird, wenn die nach einer Buchung auszufuehrenden After-Actions (Folgeaktionen) fehlschlagen. Es dient rein der Diagnose/Protokollierung im Moodle-Logstore und traegt keine eigene Persistenz; die Eventdaten (`objectid`, `userid`, `contextinstanceid`) werden vom Ausloeser via `create()` gesetzt. Keine `validate_data()`-Implementierung — der Event ist tolerant gegenueber fehlenden `other`-Feldern. Kollaborateure: Moodle-Event-Framework, `get_string`, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Pflicht-Metadaten des Events. **Seiteneffekte:** belegt `$this->data['crud']='d'` (delete), `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Bewertung:** B — `crud='d'` ist semantisch fragwuerdig: ein fehlgeschlagener After-Action-Lauf ist keine Loeschoperation; uebliche Konvention waere `'u'` oder `'r'`. Funktional unkritisch, da `crud` nur Klassifizierung ist.

### `public static function get_name()` — public static
- **Zweck:** Anzeigename des Events fuer die Logstore-UI. **Rueckgabe:** `get_string('bookingafteractionsfailed', 'booking')`. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Menschenlesbare Beschreibung fuer den Logeintrag. **Rueckgabe:** statischer englischer Satz mit interpoliertem `$this->userid` und `$this->objectid`. **Bewertung:** B — hartkodiertes Englisch (kein `get_string`), aber bei Diagnose-Events im Plugin durchgaengig so gehandhabt.

### `public function get_url()` — public
- **Zweck:** Verlinkt den Logeintrag auf die Booking-Aktivitaetsansicht. **Rueckgabe:** `moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Diagnose-Event ohne Persistenz und ohne `validate_data`. Einzige Schwaeche ist das semantisch unpassende `crud='d'` und die hartkodierte Beschreibung. Verhalten korrekt und risikolos. Klassen-Score **B / P3**.
