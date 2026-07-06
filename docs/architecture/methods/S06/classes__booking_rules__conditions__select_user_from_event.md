# select_user_from_event — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_user_from_event.php` · **LOC:** 261 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_user_from_event` implementiert `booking_rule_condition` und bestimmt als Empfaenger entweder den Ausloeser eines Events (`userid`) oder den vom Event betroffenen User (`relateduserid`). Dazu restauriert `set_conditiondata_from_json()` das im JSON mitgefuehrte Event aus seinem Datensatz und extrahiert `userid`/`relateduserid`. Der ausgewaehlte Typ (`userfromeventtype`) wird im JSON-Blob `rulejson` persistiert; eigene Tabelle gibt es nicht. Die Klasse stellt zudem die statische Form-Helper-Methode `add_userselect_to_mform()` bereit, die auch von Schwester-Conditions (z.B. `select_deputy_of_supervisor`) wiederverwendet wird und „betroffener User" nur fuer Events anbietet, die `relateduserid` tatsaechlich setzen. Kollaborateure: `$DB` (`sql_concat`), die Moodle-Event-API (`::restore`/`get_data`), Form-API, Regel-Executor.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Verbietet die Kombination mit den eventlosen Typen `rule_daysbefore`/`rule_specifictime`. **Seiteneffekte:** keine. **Rueckgabe:** `false` fuer diese beiden, sonst `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Delegiert `rulejson` an `set_conditiondata_from_json()`. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Deserialisiert JSON, setzt `$userfromeventtype` und restauriert das Event (`$ruleobj->ruledata->boevent::restore((array)$ruleobj->datafromevent, [])`), um daraus `userid`/`relateduserid` zu lesen. **Seiteneffekte:** Property-Mutation; instanziiert ein Event-Objekt. **Bewertung:** C — kein Guard auf das Vorhandensein von `$ruleobj->ruledata->boevent`/`->datafromevent`; ein JSON ohne diese Felder (oder mit unbekannter Event-Klasse) wirft. Funktioniert nur im React-on-Event-Kontext, in dem das Event mitserialisiert wurde.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Leitet aus dem in der Form gewaehlten Event den reinen Eventnamen ab und ruft `add_userselect_to_mform()`. **Seiteneffekte:** mform-Elemente. **Bewertung:** A.

### `public static function add_userselect_to_mform(MoodleQuickForm &$mform, string $eventnameonly = '')` — public static
- **Zweck:** Rendert Beschreibung und das Typ-Select; bietet „betroffener User" (`relateduserid`) nur an, wenn das Event in der Whitelist `$eventssupportingrelateduserid` steht (oder kein Event vorgewaehlt ist); „Ausloeser" (`userid`) immer. **Seiteneffekte:** mform-Elemente. **Bewertung:** B — die Whitelist ist eine hartcodierte Liste von Event-Shortnames (teils mit, teils ohne fuehrenden Backslash bei den shopping_cart-Events); neue relateduserid-faehige Events muessen hier manuell nachgepflegt werden (Wartungsfalle, kein Defekt).

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter/roher Conditionsname. **Seiteneffekte:** `get_string()`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert Conditionsname und `userfromeventtype` (Default `'0'`) ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt `bookingruleconditiontype` und das Typ-Select aus dem JSON. **Seiteneffekte:** Property-Mutation. **Bewertung:** B — `$jsonobject->conditiondata->userfromeventtype` ohne Null-Guard.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Waehlt je nach `$userfromeventtype` `$this->userid` bzw. `$this->relateduserid` und JOINt genau diesen einen User (`JOIN {user} u ON u.id = :chosenuserid`) an die Regel-Query; baut `uniqueid` (inkl. `bod.id` bei Optiondates) und `userid` ins Select. **Seiteneffekte:** `$DB->sql_concat`; mutiert `$sql->select`/`->from`; setzt `$params['chosenuserid']`. **Rueckgabe:** void; wirft `moodle_exception` bei fehlendem/unbekanntem Typ. **Bewertung:** B — sauber parametrisiert. Ein `$chosenuserid` von 0 (z.B. relateduserid eines Events ohne betroffenen User) ergibt einen JOIN ohne Treffer — stiller Leerlauf statt Fehler, was hier gewollt ist.

## Bewertungs-Resümee
Kompakte Event-User-Condition mit sinnvoller statischer Form-Wiederverwendung und korrekter Parametrisierung. Schwaechen: fehlende Guards beim Event-Restore (wirft bei unvollstaendigem JSON) und eine hartcodierte, manuell zu pflegende relateduserid-Whitelist. Kein Daten-/Sicherheitsdefekt. Klassen-Score **C / P2**.
