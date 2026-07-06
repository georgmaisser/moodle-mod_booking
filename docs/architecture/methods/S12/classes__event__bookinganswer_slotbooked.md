# bookinganswer_slotbooked — Methoden-Doku
**Datei:** `classes/event/bookinganswer_slotbooked.php` · **LOC:** 100 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_slotbooked` ist ein Moodle-Logevent (`\core\event\base`), das ausgeloest wird, wenn fuer einen Slot (Slotbooking-Feature, S14) eine Buchungsantwort angelegt wird. Es traegt keine eigene Persistenz; das Event referenziert die geloggte Antwort ueber `objecttable = 'booking_answers'` und `objectid` (die booking-answer-id, im Code als `baid` bezeichnet). Nutzlast wird ueber `data['other']` (`optionid`, `slotcount`) sowie `relateduserid` (der gebuchte User) und `userid` (der ausloesende Admin/Lehrende) transportiert. Kollaborateure: Moodle-Event-/Logging-API, `get_string` fuer Name/Beschreibung, `report.php` als Log-URL-Ziel.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'c'` (create), `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Mutiert `$this->data`. **Bewertung:** A — Standard-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename ueber `get_string('slot_booked_event_name', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Baut die Log-Beschreibung aus einem `$a`-Objekt (`adminid` = `userid`, `userid` = `relateduserid`, `optionid`, `baid` = `objectid`, `slotcount`) und gibt `get_string('slot_booked_event_description', ...)` zurueck. **Seiteneffekte:** keine; liest defensiv mit `?? 0` und int-Cast aus `data['other']`. **Rueckgabe:** string. **Bewertung:** B — die Benennung `adminid`/`userid` invertiert die Standard-Event-Semantik (`userid` = Akteur), ist aber im Strings-Template konsistent gemeint.

### `public function get_url()` — public
- **Zweck:** Liefert die im Log verlinkte URL `/mod/booking/report.php?id=<contextinstanceid>&optionid=<optionid>`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt nach `parent::validate_data()`, dass `relateduserid` und `data['other']['optionid']` gesetzt sind. **Seiteneffekte:** wirft `coding_exception` bei fehlenden Pflichtfeldern. **Rueckgabe:** void. **Bewertung:** A — saubere Pflichtfeld-Pruefung; `slotcount` bleibt optional.

## Bewertungs-Resümee
Schlankes, korrekt strukturiertes create-Event fuers Slotbooking; defensive Auslesung der Nutzlast und Pflichtfeld-Validierung. Einzige Schwaeche ist die etwas verwirrende `adminid`/`userid`-Benennung gegenueber der Core-Konvention. Funktional unkritisch. Klassen-Score **B / P3**.
