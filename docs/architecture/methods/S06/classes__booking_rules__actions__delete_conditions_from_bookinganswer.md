# delete_conditions_from_bookinganswer — Methoden-Doku
**Datei:** `classes/booking_rules/actions/delete_conditions_from_bookinganswer.php` · **LOC:** 157 · **Subsystem:** S06 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`delete_conditions_from_bookinganswer` implementiert `booking_rule_action`. Die Action selbst traegt keine konfigurierbaren Form-Felder; sie dient als duenner Trigger, der pro betroffenem Nutzer/Antwort einen Adhoc-Task `delete_conditions_from_bookinganswer_by_rule_adhoc` queued. Dieser Task entfernt (laut Index) `condition_customform`-Daten aus `booking_answers.json`, wenn ein Loesch-Flag gesetzt ist. Persistenz: serialisiert lediglich Name/Actionname in `rulejson` (`save_action`); die eigentliche Mutation passiert im Adhoc-Task. Kollaborateure: `delete_conditions_from_bookinganswer_by_rule_adhoc`, `\core\task\manager`, `$DB` (deklariert, aber faktisch ungenutzt in dieser Klasse).

## Methoden

### `public function set_actiondata(stdClass $record)` — public
- **Zweck:** Delegiert an `set_actiondata_from_json($record->rulejson)`. **Bewertung:** A.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Setzt `$this->rulejson`. **Seiteneffekte:** dekodiert `$json` in `$jsonobject` — diese lokale Variable wird jedoch nie verwendet. **Bewertung:** B — toter `json_decode`-Aufruf (Z.60), funktional harmlos, aber irrefuehrend.

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck/Seiteneffekte:** No-op („No need to render anything here"). **Bewertung:** A.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** `get_string('deletedatafrombookinganswer', 'mod_booking')`. **Bewertung:** B — `$localized` wird ignoriert (Familien-Inkonsistenz).

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** Immer `true` — die Action ist universell kompatibel und erscheint im Dropdown. **Bewertung:** A.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Schreibt `name`, `actionname` und ein leeres `actiondata`-Objekt in `$data->rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`; deklariert `global $DB`, nutzt es aber nicht. **Bewertung:** B — funktioniert; ungenutztes `global $DB` und leeres `actiondata` (kein konfigurierbarer State).

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck/Seiteneffekte:** No-op. **Bewertung:** A.

### `public function execute(stdClass $record)` — public
- **Zweck:** Baut die Task-Custom-Data (rulename, ruleid, rulejson, userid, optionid, cmid, **baid**, optional optiondateid), setzt den User und `next_run_time` aus `$record->nextruntime` und queued/reschedules `delete_conditions_from_bookinganswer_by_rule_adhoc`. **Seiteneffekte:** deklariert `global $DB` (ungenutzt); `set_custom_data/set_userid/set_next_run_time`; `\core\task\manager::reschedule_or_queue_adhoc_task`. **Rueckgabe:** void. **Bewertung:** B — geradlinige Task-Erzeugung. Setzt zwingend `$record->baid` (Booking-Answer-ID) voraus; fehlt sie, greift Moodle auf ein undefiniertes Property-Notice und ein unvollstaendiges Task-Payload zu. Korrekt: `reschedule_or_queue` verhindert Task-Duplikate bei identischen Daten.

### Triviale Properties
`$actionname`, `$rulejson` (null), `$ruleid` (null) als Werte-Halter.

## Bewertungs-Resümee
Klassisch duenne Trigger-Action der booking_rules-Familie: keine Form, keine eigene Mutation, reines Adhoc-Task-Enqueue. Auffaelligkeiten sind kosmetisch — toter `json_decode` in `set_actiondata_from_json`, ungenutzte `global $DB`-Deklarationen und der ignorierte `$localized`-Parameter. Die Abhaengigkeit von `$record->baid` ist eine implizite Vertragsbedingung, aber kein eigenstaendiger Bug. Klassen-Score **B / P3**.
