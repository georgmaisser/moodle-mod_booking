# booking_rule — Methoden-Doku
**Datei:** `classes/booking_rules/booking_rule.php` · **LOC:** 103 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`booking_rule` ist das `interface` (trotz „Base class"-Wording im Doc-Block), das jeder Trigger-Rule-Typ (z.B. `rule_react_on_event`, `rule_daysbefore`) implementieren muss. Es definiert den vollstaendigen Lebenszyklus einer Regel: Formular-Aufbau, Namens-Abfrage, Serialisierung nach/aus JSON (`rulejson` in Tabelle `booking_rules`), Default-Vorbelegung beim Form-Load, Ausfuehrung und die nachgelagerte Re-Validierung beim Adhoc-Task-Ablauf. Keine eigene Persistenz/State — reiner Vertrag. Kollaborateure: `MoodleQuickForm` (Form-Layer), `stdClass`-Records aus `booking_rules`, die Implementierungen koppeln dann an `conditions_info`/`actions_info` und den Adhoc-Task-Layer.

## Methoden

### `public function add_rule_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, array $ajaxformdata = [])` — public
- **Zweck:** Haengt die regel-spezifischen Formularelemente in das uebergebene mform; `$repeateloptions` per Referenz fuer wiederholbare Elemente. **Seiteneffekte:** mutiert `$mform`/`$repeateloptions`. **Bewertung:** A — klarer Form-Vertrag.

### `public function get_name_of_rule(bool $localized = true): string` — public
- **Zweck:** Liefert den (optional lokalisierten) Anzeigenamen des Regel-Typs. **Rueckgabe:** string. **Bewertung:** A.

### `public function save_rule(stdClass &$data)` — public
- **Zweck:** Schreibt die regel-spezifischen Felder als JSON in `$data->rulejson` (per Referenz). **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt `$data` mit den aus `$record` (DB-`booking_rules`) dekodierten Defaults fuer den Form-Load. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_ruledata(stdClass $record)` — public
- **Zweck:** Laedt JSON aus einem DB-Record in das Objekt (i.d.R. via `set_ruledata_from_json($record->rulejson)`). **Seiteneffekte:** Property-Mutation in der Implementierung. **Bewertung:** A.

### `public function set_ruledata_from_json(string $json)` — public
- **Zweck:** Laedt die Regel-Properties direkt aus einem JSON-String. **Seiteneffekte:** Property-Mutation. **Bewertung:** A.

### `public function execute(int $optionid = 0, int $userid = 0)` — public
- **Zweck:** Fuehrt die Regel aus — i.d.R. Ermittlung der betroffenen Datensaetze (Condition) und Queuing der zugehoerigen Action(s)/Adhoc-Tasks. **Seiteneffekte:** in der Implementierung DB-Reads + Task-Queueing. **Bewertung:** A.

### `public function check_if_rule_still_applies(int $optionid, int $userid, int $nextruntime, int $optiondateid = 0): bool` — public
- **Zweck:** Wird beim Ablauf eines Adhoc-Tasks aufgerufen, um zu pruefen, ob die Regel zum Ausfuehrungszeitpunkt noch gilt (Schutz gegen veraltete/obsolete geplante Mails). **Rueckgabe:** bool. **Bewertung:** A — wichtiger Konsistenz-Hook gegen Race/Stale-Tasks.

## Bewertungs-Resümee
Schlanker, vollstaendiger Lebenszyklus-Vertrag fuer Trigger-Rules; der `check_if_rule_still_applies`-Hook ist konzeptionell stark (entkoppelt Planung von Ausfuehrung). Einzige Doku-Schwaeche: Class-Doc spricht von „Base class"/„extend this interface", obwohl es ein reines Interface ist. Funktional kein Risiko. Klassen-Score **A / P3**.
