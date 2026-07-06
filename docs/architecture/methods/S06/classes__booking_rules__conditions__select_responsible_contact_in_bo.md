# select_responsible_contact_in_bo — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_responsible_contact_in_bo.php` · **LOC:** 199 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_responsible_contact_in_bo` implementiert `booking_rule_condition` und bestimmt als Empfaenger die im Buchungsoptionsfeld `responsiblecontact` hinterlegten User. Da dieses Feld eine kommaseparierte CSV-Liste von User-IDs enthaelt (leer, eine oder mehrere), muss `execute()` die CSV auf Zeilen aufspalten — dialektspezifisch fuer Postgres (`regexp_split_to_table` via `JOIN LATERAL`) bzw. MySQL/MariaDB (`SUBSTRING_INDEX`-Trick mit Zahlen-Union). Persistenz: nur ein minimaler JSON-Blob (`rulejson`) mit dem Conditionsnamen, keine eigene Tabelle, keine Konfigparameter. Kollaborateure: `$DB` (`get_dbfamily`, `sql_concat`), Form-API, der Regel-Executor (mutiert `$sql`/`$params`).

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Erlaubt die Kombination mit jedem Regeltyp. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Delegiert `rulejson` aus dem DB-Record an `set_conditiondata_from_json()`. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Speichert das JSON in `$rulejson` (die Condition hat keine konfigurierbaren Parameter). **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Rendert nur ein statisches Beschreibungs-Element (keine Eingabe noetig). **Seiteneffekte:** mform-Element. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter/roher Conditionsname. **Seiteneffekte:** `get_string()`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt nur den Conditionsnamen ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`; `global $DB` ungenutzt. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt `bookingruleconditiontype`. **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Spaltet das CSV-Feld `bo.responsiblecontact` zeilenweise auf und JOINt jede enthaltene User-ID an die Regel-Query; baut `uniqueid` (inkl. `bod.id` falls Optiondates verwendet werden) und `userid` ins Select; optionaler `userid`-Filter. **Seiteneffekte:** `$DB->get_dbfamily`, `$DB->sql_concat`; mutiert `$sql->select`/`->from`/`->where`; setzt ggf. `$params['userid2']`. **Rueckgabe:** void; wirft `moodle_exception` bei unbekanntem DB-Typ. **Bewertung:** C — dialektsauber und parametrisiert, aber der MySQL/MariaDB-Zweig deckelt die Aufspaltung hart auf `$maxsplit = 20`: Buchungsoptionen mit mehr als 20 verantwortlichen Kontakten verlieren auf MySQL/MariaDB die ueberzaehligen Eintraege still (auf Postgres unbegrenzt). Der Zweig laedt zudem `booking_options` ein zweites Mal (`bo2`), weil MariaDB 10.6 kein `JOIN LATERAL` kann — vertretbarer, aber teurer Workaround. Kein Optiondate-JOIN-Alias-Guard: `bod.id` wird im uniqueid nur referenziert, wenn `optiondate` bereits im Select stand.

## Bewertungs-Resümee
Solide, dialektbewusste CSV-Split-Condition mit sauberer Parametrisierung. Kernschwaeche ist die harte 20er-Obergrenze im MySQL/MariaDB-Pfad (potentieller stiller Empfaenger-Verlust bei sehr vielen verantwortlichen Kontakten) sowie der doppelte `booking_options`-Scan als MariaDB-Workaround. Klassen-Score **C / P2**.
