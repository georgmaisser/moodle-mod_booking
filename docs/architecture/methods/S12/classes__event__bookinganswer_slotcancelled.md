# bookinganswer_slotcancelled — Methoden-Doku
**Datei:** `classes/event/bookinganswer_slotcancelled.php` · **LOC:** 100 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_slotcancelled` ist das Gegenstueck zu `bookinganswer_slotbooked`: ein Moodle-Logevent (`\core\event\base`), das beim Stornieren einer Slot-Buchungsantwort ausgeloest wird. Strukturell identisch zum slotbooked-Event, lediglich `crud = 'u'` (update statt create) und die verwendeten Lang-Strings unterscheiden sich. Keine eigene Persistenz; Bezug zur Antwort ueber `objecttable = 'booking_answers'`/`objectid`. Kollaborateure: Moodle-Event-API, `get_string`, `report.php`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'u'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Name ueber `get_string('slot_cancelled_event_name', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibung aus `$a` (`adminid` = `userid`, `userid` = `relateduserid`, `optionid`, `baid` = `objectid`, `slotcount`) via `get_string('slot_cancelled_event_description', ...)`. **Seiteneffekte:** keine; defensives `?? 0` + int-Cast. **Rueckgabe:** string. **Bewertung:** B — gleiche `adminid`/`userid`-Benennungsumkehr wie im slotbooked-Event.

### `public function get_url()` — public
- **Zweck:** Log-URL `/mod/booking/report.php?id=<contextinstanceid>&optionid=<optionid>`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Verlangt nach `parent::validate_data()` gesetztes `relateduserid` und `data['other']['optionid']`. **Seiteneffekte:** wirft `coding_exception`. **Rueckgabe:** void. **Bewertung:** A.

## Bewertungs-Resümee
Praktisch eine 1:1-Kopie von `bookinganswer_slotbooked` mit getauschtem `crud`-Flag und anderen Lang-Strings — saubere, aber redundante Klasse (typisches Event-Duplikationsmuster in Moodle). Funktional korrekt. Klassen-Score **B / P3**.
