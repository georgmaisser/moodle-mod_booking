# select_teacher_in_bo — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_teacher_in_bo.php` · **LOC:** 159 · **Subsystem:** S06 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_teacher_in_bo` implementiert `booking_rule_condition` und waehlt als Empfaenger die Trainer einer Buchungsoption durch einen schlichten JOIN auf `booking_teachers`. Es ist die einfachste Variante der „select … in bo"-Familie: keine konfigurierbaren Parameter, nur ein minimaler JSON-Blob (`rulejson`) mit dem Conditionsnamen, keine eigene Tabelle. Kollaborateure: `$DB` (`sql_concat`), Form-API, Regel-Executor (mutiert `$sql`/`$params`).

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Erlaubt jede Regelkombination. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Delegiert `rulejson` an `set_conditiondata_from_json()`. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Speichert das JSON in `$rulejson` (keine Parameter zu lesen). **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Rendert nur ein statisches Beschreibungs-Element. **Seiteneffekte:** mform-Element. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter/roher Conditionsname. **Seiteneffekte:** `get_string()`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt nur den Conditionsnamen ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`; `global $DB` ungenutzt. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt `bookingruleconditiontype`. **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** JOINt `booking_teachers bt` an `bo.id` und liefert jeden Trainer als Empfaenger; baut `uniqueid` (inkl. `bod.id` falls Optiondates im Select) und `userid` ins Select; optionaler `userid`-Filter ueber `userid2`. **Seiteneffekte:** `$DB->sql_concat`; mutiert `$sql->select`/`->from`/`->where`; setzt ggf. `$params['userid2']`. **Rueckgabe:** void. **Bewertung:** B — kompakt und parametrisiert. Der optionale `$anduserid`-Filter wird per String an `$sql->where` angehaengt; das funktioniert nur, wenn der vorhandene WHERE-Block bereits eine Bedingung enthaelt (sonst entstuende ein nacktes `AND …`) — in der Regel-Engine ist das gegeben, bleibt aber eine implizite Annahme.

## Bewertungs-Resümee
Triviale, gut lesbare JOIN-Condition ohne Konfiguration. Einzig der per String angehaengte optionale `AND`-Filter setzt einen bestehenden WHERE-Kontext voraus. Kein funktionaler Defekt. Klassen-Score **B / P3**.
