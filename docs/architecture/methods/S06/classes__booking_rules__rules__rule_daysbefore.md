# rule_daysbefore — Methoden-Doku
**Datei:** `classes/booking_rules/rules/rule_daysbefore.php` · **LOC:** 497 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`rule_daysbefore` implementiert das Interface `booking_rule` und realisiert den Regeltyp „X Tage vor/nach einem gewaehlten Datumsfeld etwas tun". Die Klasse kapselt Formularaufbau (`add_rule_to_mform`/`set_defaults`), Persistenz als JSON in `booking_rules` (`save_rule`/`set_ruledata`) sowie die eigentliche Ausfuehrungslogik: Sie baut dynamisch ein SQL ueber `booking_options`/`booking_optiondates`, delegiert Nutzer-Filterung an eine `condition` (`conditions_info`) und die Aktion an eine `action` (`actions_info`). Zentrale Kollaborateure: `singleton_service`, `applybookingrules`, `bo_info`, `context`, `core_component`.

## Methoden

### `set_ruledata_from_json(string $json)` — public
- **Zweck:** Befuellt `$name`, `$days`, `$datefield` aus einem JSON-String.
- **Parameter/Rueckgabe:** JSON-String; void. Cast `(int)` auf days.
- **Seiteneffekte:** Nur Objekt-State; keine DB/Cache. `json_decode` ohne Fehlerpruefung.
- **Aufrufkette:** von `set_ruledata`; extern aus Engine zur Hydrierung.
- **Bewertung:** B — knapp, aber keine Null-/Schema-Validierung von `$ruleobj->ruledata`.

### `add_rule_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, array $ajaxformdata = [])` — public
- **Zweck:** Fuegt die Formularelemente des Regeltyps hinzu (Tage-Select, Datumsfeld-Select inkl. optionalem shopping_cart-Eintrag).
- **Parameter/Rueckgabe:** mform/repeateloptions per Referenz; void.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; `class_exists('local_shopping_cart\shopping_cart')`-Probe (Cross-Plugin-Kopplung); viele `get_string`-Calls; `booking::get_array_of_days_before_and_after(-30, 30)` (statischer Call).
- **Aufrufkette:** vom Rule-Formular-Renderer (booking_rules-Form).
- **Bewertung:** C — 46 LOC, gemischte Verantwortung (Datenliste + UI), ungenutztes `global $DB` (rule_daysbefore.php:103), hartkodierte Feldliste. Smell: `rule_daysbefore.php:102`.

### `save_rule(stdClass &$data): int` — public
- **Zweck:** Serialisiert Formulardaten in JSON und schreibt/aktualisiert den `booking_rules`-Datensatz.
- **Parameter/Rueckgabe:** Formdaten per Referenz; gibt `$ruleid` zurueck.
- **Seiteneffekte:** DB-Write `booking_rules` (`update_record`/`insert_record`); setzt `$this->ruleid` beim Insert; `core_component::get_component_from_classname`. Liest `$data->rulejson` optional.
- **Aufrufkette:** vom Rule-Save-Flow der Engine.
- **Bewertung:** C — 37 LOC, mehrere Fallback-Operatoren (`?? false`, `?? 0`), gemischtes Aufbauen von `$jsonobject` und `$record` mit Teil-Ueberschneidung (useastemplate doppelt). Direkter DB-Schreibpfad in der Rule-Klasse. Smell: `rule_daysbefore.php:163`.

### `execute(int $optionid = 0, int $userid = 0)` — public
- **Zweck:** Fuehrt die Regel aus: ermittelt betroffene Records, instanziiert die Action und ruft sie pro Record mit berechneter `nextruntime` auf.
- **Parameter/Rueckgabe:** optionid/userid; void.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (Lesen/Cache), `applybookingrules::apply_rule` (Guard), `actions_info::get_action` (Factory), `$action->execute($record)` (kann Tasks/Notifications anstossen). `strtotime` fuer DST-korrekte Tagesberechnung.
- **Aufrufkette:** ruft `get_records_for_execution`, `should_skip_for_selflearningcourse`; selbst aus Event-/Cron-Trigger der Rules-Engine.
- **Bewertung:** B — klar strukturiert, mutiert aber `$this->days` in der Schleife (Seiteneffekt auf Objekt-State, `rule_daysbefore.php:249`), statische God-Calls.

### `check_if_rule_still_applies(int $optionid, int $userid, int $nextruntime, int $optiondateid = 0): bool` — public
- **Zweck:** Prueft zum Adhoc-Task-Ausfuehrungszeitpunkt, ob die Regel noch gilt (aktiv, anwendbar, Runtime stimmt mit aktuellem SQL ueberein).
- **Parameter/Rueckgabe:** ids + erwartete nextruntime + optiondateid; bool.
- **Seiteneffekte:** mehrere Guards (`ruleisactive`, `applybookingrules::apply_rule`, selflearning-Skip), `singleton_service`, ruft `get_records_for_execution($testmode=true)`. Mutiert `$this->days`.
- **Aufrufkette:** ruft `get_records_for_execution`, `should_skip_for_selflearningcourse`; aus Adhoc-Task vor Ausfuehrung.
- **Bewertung:** D — 66 LOC, tiefe Schachtelung (if/else mit verschachtelten if + foreach + break/continue), dupliziertes `strtotime`-Runtime-Vergleichsmuster in beiden Zweigen (rule_daysbefore.php:313 vs 328), Flag-Toggling `$rulestillapplies` schwer nachvollziehbar (kann durch Reihenfolge der Records kippen). Smell: `rule_daysbefore.php:272`.

### `should_skip_for_selflearningcourse(object $settings, stdClass $jsonobject): bool` — private
- **Zweck:** True, wenn fuer Self-Learning-Kurse ein nur-Sortier-Datumsfeld genutzt wird (dann keine Reminder).
- **Parameter/Rueckgabe:** settings + json; bool.
- **Seiteneffekte:** keine; reine Pruefung mit `in_array(..., true)`.
- **Aufrufkette:** von `execute` und `check_if_rule_still_applies`.
- **Bewertung:** A — fokussiert, seiteneffektfrei, gut benannt.

### `get_records_for_execution(int $optionid = 0, int $userid = 0, bool $testmode = false, int $nextruntime = 0)` — public
- **Zweck:** Baut dynamisch das Such-SQL (abhaengig vom Datumsfeld), bindet Condition ein und liefert die betroffenen Records.
- **Parameter/Rueckgabe:** ids + testmode + nextruntime; array von Records (optionid, cmid, datefield, ggf. optiondateid/daystonotify).
- **Seiteneffekte:** `context::instance_by_id` (Lesen), `bo_info::check_for_sqljson_key_in_object` (Dialekt-JSON-SQL), `conditions_info::get_condition` + `$condition->execute($sql,...)` mutiert SQL/Params, **DB-Read** `$DB->get_records_sql` ueber `booking_options`/`course_modules`/`modules`/`context`/`booking_optiondates`.
- **Aufrufkette:** von `execute` und `check_if_rule_still_applies`.
- **Bewertung:** D — 126 LOC, manueller SQL-String-Aufbau per `stdClass`-Fragmenten + String-Konkatenation, `switch` mit drei Zweigen unterschiedlicher Spaltenstruktur, default-Zweig interpoliert `$ruledata->datefield` direkt in das SQL (`bo." . $ruledata->datefield`, rule_daysbefore.php:462/464) — Spaltenname stammt aus gespeichertem JSON, nur indirekt durch die Form-Whitelist begrenzt (potenzielle SQL-Injektion bei manipuliertem rulejson). Mehrfach dupliziertes `nowparam - 3600 + 86400*days`-Muster, gemischte Zustaendigkeit (SQL-Bau + Condition-Delegation + Query). Smell: `rule_daysbefore.php:370`.

### Triviale Akzessoren
- `set_ruledata(stdClass $record)` (public, ~6 LOC): kopiert ruleid/contextid/isactive aus Record, delegiert JSON an `set_ruledata_from_json`. Bewertung B.
- `get_name_of_rule(bool $localized = true): string` (public, 1 Ausdruck): liefert lokalisierten oder technischen Regelnamen. Bewertung A.
- `set_defaults(stdClass &$data, stdClass $record)` (public, ~12 LOC): dekodiert rulejson und setzt Form-Defaults; keine DB/Cache. Bewertung B.
- Trivial-Properties: `$rulename`, `$rulenamestringid`, `$contextid`, `$name`, `$rulejson`, `$ruleid`, `$days`, `$datefield`, `$ruleisactive`.
