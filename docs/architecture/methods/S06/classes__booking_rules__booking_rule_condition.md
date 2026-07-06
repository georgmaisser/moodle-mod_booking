# booking_rule_condition — Methoden-Doku
**Datei:** `classes/booking_rules/booking_rule_condition.php` · **LOC:** 96 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`booking_rule_condition` ist das `interface` (Doc-Block sagt irrefuehrend „Base class") fuer die Empfaenger-Auswahl einer Buchungsregel — die Bedingung, die bestimmt, welche User/Records von der Action betroffen sind (z.B. „User profile field == option field"). Zentrale Besonderheit: `execute` baut nicht selbst Empfaenger zusammen, sondern **mutiert ein per Referenz uebergebenes SQL-Skelett** (`select`/`from`/`where`/`sort`) und die `$params`, das die Rule danach gemeinsam ausfuehrt. Daneben: Kombinierbarkeits-Gate gegen den Regel-Typ, Form-Aufbau, Name und JSON-Serialisierung in den gemeinsamen `rulejson`-Blob. Kein eigener State. Kollaborateure: `MoodleQuickForm`, `stdClass` (SQL-Skelett), `$DB`-Konsum in der Rule.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Gate, ob diese Condition mit einem bestimmten Rule-Typ (z.B. `rule_daysbefore`, `rule_react_on_event`) kombiniert werden darf. **Rueckgabe:** bool. **Bewertung:** A — verhindert sinnlose Condition/Rule-Paarungen im UI.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Haengt die condition-spezifischen Formularelemente ans mform. **Seiteneffekte:** mutiert `$mform`/`$ajaxformdata`. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Liefert den (optional lokalisierten) Anzeigenamen der Condition. **Rueckgabe:** string. **Bewertung:** A — Parameter/Rueckgabe untypisiert (kleine Inkonsistenz zu den typisierten Nachbarn).

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert die Condition-Felder als JSON in `$data->rulejson`. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt `$data` mit aus `$record->rulejson` dekodierten Condition-Defaults fuer den Form-Load. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt die Condition-Daten aus einem DB-Record (i.d.R. via `set_conditiondata_from_json`). **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Laedt die Condition-Properties direkt aus einem JSON-String. **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Reichert das gemeinsame SQL-Skelett (`$sql`) und `$params` per Referenz um die Empfaenger-Filterlogik an; die Rule fuehrt das Statement danach selbst aus. **Seiteneffekte:** mutiert `$sql`/`$params`. **Bewertung:** A — das Referenz-Mutations-Pattern ist effizient (eine zusammengesetzte Query statt N Einzelabfragen), erfordert aber Disziplin der Implementierungen bei Platzhalter-Namen.

## Bewertungs-Resümee
Klar geschnittener Condition-Vertrag mit durchdachter Kollaboration: `execute` baut ein gemeinsames SQL statt eigener Empfaengerlisten, das Kombinierbarkeits-Gate haelt das Konfig-UI konsistent. Mini-Schwaechen: untypisiertes `get_name_of_condition` und „Base class"-Wording. Funktional unkritisch. Klassen-Score **A / P3**.
