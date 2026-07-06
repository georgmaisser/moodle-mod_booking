# booking_rule_action — Methoden-Doku
**Datei:** `classes/booking_rules/booking_rule_action.php` · **LOC:** 97 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`booking_rule_action` ist das `interface` (Doc-Block sagt irrefuehrend „Base class") fuer die Wirkungs-Seite einer Buchungsregel — also was passiert, nachdem eine Condition die Empfaenger ermittelt hat (z.B. `send_mail`, `send_mail_interval`, `confirm_bookinganswer`). Es definiert Form-Aufbau, Namen, Kompatibilitaetscheck gegen die Ajax-Formdaten, JSON-Serialisierung in den gemeinsamen `rulejson`-Blob (Tabelle `booking_rules`) und die `execute`-Wirkung, die typischerweise einen Adhoc-Task einreiht. Kein eigener State — reiner Vertrag. Kollaborateure: `MoodleQuickForm`, `booking_option_settings` (importiert, von Implementierungen genutzt), `stdClass`-Records, Adhoc-Task-Layer (`\core\task\manager`).

## Methoden

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck:** Haengt die action-spezifischen Formularelemente (Betreff, Template, ical, Intervall …) ans mform. **Seiteneffekte:** mutiert `$mform`/`$repeateloptions`. **Bewertung:** A.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** Liefert den (optional lokalisierten) Anzeigenamen der Action. **Rueckgabe:** string. **Bewertung:** A — fehlende Typdeklarationen am Parameter/Rueckgabe (vgl. `booking_rule::get_name_of_rule`, das typisiert ist) sind eine kleine Inkonsistenz.

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** Filtert, ob die Action mit den aktuell im Formular gewaehlten Daten (z.B. Regel-Typ) kombinierbar ist. **Rueckgabe:** bool. **Bewertung:** A — Mechanismus fuer dynamische Action/Rule-Kombinierbarkeit.

### `public function save_action(stdClass &$data)` — public
- **Zweck:** Serialisiert die Action-Felder als JSON nach `$data->rulejson`. **Seiteneffekte:** mutiert `$data`. **Rueckgabe:** laut Doc-Block string, der gemeinsame Blob wird aber via Referenz uebergeben. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt `$data` mit aus `$record->rulejson` dekodierten Action-Defaults fuer den Form-Load. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_actiondata(stdClass $record)` — public
- **Zweck:** Laedt die Action-Daten aus einem DB-Record (i.d.R. via `set_actiondata_from_json`). **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Laedt die Action-Properties direkt aus einem JSON-String. **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function execute(stdClass $record)` — public
- **Zweck:** Fuehrt die Wirkung fuer einen einzelnen Empfaenger-`$record` aus (i.d.R. Adhoc-Task queuen). **Seiteneffekte:** in der Implementierung Task-Queueing. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer Action-Vertrag, gut symmetrisch zu `booking_rule`/`booking_rule_condition`. Einzige Mini-Schwaechen: fehlende Typdeklarationen bei `get_name_of_action`/`is_compatible_with_ajaxformdata` und die „Base class"-Wording-Inkonsistenz im Doc-Block. Funktional unkritisch. Klassen-Score **A / P3**.
