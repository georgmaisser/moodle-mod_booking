# rule_react_on_event — Methoden-Doku
**Datei:** `classes/booking_rules/rules/rule_react_on_event.php` · **LOC:** 679 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`rule_react_on_event` implementiert das Interface `booking_rule` und ist der ereignisgetriebene Regeltyp des mod_booking-Regelsystems: Eine konfigurierte Regel "reagiert" auf ein ausgewaehltes Moodle-Event (z. B. `bookingoption_booked`) und stoesst nach Pruefung von Bedingung/Filter die hinterlegte Action an. Sie kapselt Form-Aufbau (`add_rule_to_mform`), Persistenz als JSON in `booking_rules` (`save_rule`/`set_defaults`/`set_ruledata*`), die eigentliche Ausfuehrung (`execute` → `get_records_for_execution` → Action) sowie die Re-Validierung bei der Adhoc-Task-Ausfuehrung (`check_if_rule_still_applies`). Kollaborateure: `conditions_info`, `actions_info`, `booking_rules`, `singleton_service` (booking_option_settings/answers), `applybookingrules`, Plugin-Manager (bookingextension/shoppingcart).

## Methoden

### `set_ruledata(stdClass $record): void` — public
- **Zweck:** Laedt ruleid/isactive aus DB-Record und delegiert JSON-Parsing an `set_ruledata_from_json`.
- **Parameter/Rueckgabe:** `$record` (DB-Zeile aus booking_rules); kein Rueckgabewert.
- **Seiteneffekte:** Nur Objekt-State (`$this->ruleid`, `$this->ruleisactive`).
- **Aufrufkette:** Vom Regel-Loader (`rules_info`) beim Hydratisieren gerufen; ruft `set_ruledata_from_json`.
- **Bewertung:** A — trivialer Delegator.

### `set_ruledata_from_json(string $json): void` — public
- **Zweck:** Dekodiert JSON-String und setzt `name`, `boevent`, `intervaldata`.
- **Seiteneffekte:** Objekt-State; kein DB.
- **Aufrufkette:** Von `set_ruledata` und extern (Template/Test).
- **Bewertung:** B — kein Guard fuer fehlgeschlagenes `json_decode`/fehlende Felder (`$ruleobj->name` koennte Notice werfen), sonst schlank.

### `add_rule_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, array $ajaxformdata = []): void` — public
- **Zweck:** Baut die Formularelemente der Regel (Event-Auswahl, Bedingung, after_completion, abzubrechende Regeln) inkl. dynamischer Event-Liste aus Core, bookingextension-Plugins und optional local_shopping_cart.
- **Parameter/Rueckgabe:** Referenzen auf mform/repeateloptions, ajaxformdata (contextid); void.
- **Seiteneffekte:** Liest `core_plugin_manager`/`core_component` (Plugin-Discovery), `require_once` local/shopping_cart/lib.php, ruft `booking_rules::get_list_of_saved_rules_by_context` (DB-Read booking_rules), `get_list_of_booking_events()` / `get_list_of_shoppingcart_events()`; globales `$CFG`.
- **Aufrufkette:** Vom Rule-Editor-Form (`rules_info::add_rules_to_form` o. ae.) gerufen.
- **Bewertung:** D — 165 LOC, mehrere Verantwortungen vermischt (Whitelist-Pflege, 3 Plugin-Discovery-Pfade, Form-Bau); hartcodierte `$allowedeventkeys`-Whitelist (Z.117-141) + Sonderfall shoppingcart inline; potenzielle undefinierte `$scallowedevents`/`$allowedevents`-Initialisierung. Smell: rule_react_on_event.php:114-279 (lange Methode, gemischte Verantwortung, hartcodierte Listen).

### `get_name_of_rule(bool $localized = true): string` — public
- **Zweck:** Liefert lokalisierten oder technischen Regelnamen.
- **Bewertung:** A — Einzeiler.

### `save_rule(stdClass &$data): int` — public
- **Zweck:** Serialisiert Formdaten zu rulejson und insert/update in `booking_rules`; gibt ruleid zurueck.
- **Seiteneffekte:** DB-Write `booking_rules` (`insert_record`/`update_record`); setzt `$this->ruleid` bei Insert; global `$DB`.
- **Aufrufkette:** Vom Rule-Save-Flow (`rules_info`); Gegenstueck zu `set_defaults`.
- **Bewertung:** B — geradlinig, aber JSON-Felder-Mapping (Z.306-322) ist boilerplate-lastig und dupliziert Feldnamen, die in mehreren Methoden (set_defaults/execute) wiederkehren.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Form-Defaults aus persistiertem rulejson.
- **Seiteneffekte:** Nur Datenobjekt; kein DB.
- **Aufrufkette:** Form-Init (`rules_info`).
- **Bewertung:** B — spiegelt save_rule (Feldname-Duplikation), kein Guard fuer fehlerhaftes JSON.

### `execute(int $optionid = 0, int $userid = 0): void` — public
- **Zweck:** Kernausfuehrung der Regel: ermittelt optionid (inkl. Sonderfall payment_confirmed/Cart), prueft `applybookingrules::apply_rule`, filtert bookingoption_updated-Changes via Config, holt Records und ruft die Action je Record.
- **Seiteneffekte:** `get_config('booking','limitchangestrackinginrules')`; ruft `applybookingrules::apply_rule` (DB-Read), `actions_info::get_action`, `get_records_for_execution` (DB-Read), `$action->execute($record)` (Action-Seiteneffekte: Adhoc-Tasks/Mail); mutiert `$this->rulejson`.
- **Aufrufkette:** Vom Event-Observer/`rules_info::execute_rules_for_option` gerufen; ruft `ruleevent_excluded_via_config`, `get_records_for_execution`.
- **Bewertung:** D — 76 LOC, hohe zyklomatische Komplexitaet, drei verschachtelte Sonderfaelle (Cart-Lookup, Config-Change-Filter, Action-Loop) in einer Methode; statischer God-Call `applybookingrules::apply_rule`. Smell: rule_react_on_event.php:361-436 (gemischte Verantwortung, tiefe Schachtelung Z.372-413).

### `check_if_rule_still_applies(int $optionid, int $userid, int $nextruntime, int $optiondateid = 0): bool` — public
- **Zweck:** Re-Validierung bei Adhoc-Task-Ausfuehrung: prueft Aktiv-Flag, Zeitfenster und Buchungs-Bedingung (FULLYBOOKED etc.).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`/`...booking_answers` (cached Reads); kein Write.
- **Aufrufkette:** Von Action-Adhoc-Tasks vor finaler Ausfuehrung; ruft `rule_still_in_time`, `$ba->is_fully_booked*`.
- **Bewertung:** C — switch ueber Bedingungs-Konstanten mit redundantem if/return-true/false-Muster (Z.471-490 liesse sich zu Boolean-Returns verdichten); sonst klar. Smell: rule_react_on_event.php:468-491 (verbose Branch-Duplikation).

### `rule_still_in_time(object $ruledata, object $bookingoption): bool` — private static
- **Zweck:** Prueft ob courseendtime + aftercompletion-Tage noch nicht ueberschritten ist.
- **Seiteneffekte:** keine (nur `time()`).
- **Aufrufkette:** Von `check_if_rule_still_applies`.
- **Bewertung:** B — kleine reine Funktion; `(int)$x ?? 0` (Z.512) ist redundant/irrefuehrend (Cast liefert nie null), aber harmlos.

### `get_records_for_execution(int $optionid, int $userid = 0): array` — public
- **Zweck:** Baut die SQL (booking_options × course_modules × modules) und laesst die Condition ihren SQL-Teil ergaenzen; liefert Records (optionid, cmid, ...).
- **Seiteneffekte:** DB-Read via `$DB->get_records_sql` (booking_options/course_modules/modules); ruft `conditions_info::get_condition` + `$condition->execute($sql,$params)`.
- **Aufrufkette:** Von `execute`; wird laut Doc auch beim Validity-Check wiederverwendet.
- **Bewertung:** C — manueller SQL-Bau per stdClass-Fragmenten (`$sql->select/from/where/sort`) und String-Konkatenation (Z.573-578); ORDER-BY ohne Leerzeichen-Risiko mitigiert, aber Concatenation-Pattern ist fehleranfaellig und an Condition-Klassen gekoppelt. Smell: rule_react_on_event.php:551-580 (SQL-Bau, Kopplung an Condition-SQL-Mutation).

### `ruleevent_excluded_via_config(string $fieldname, array $changes): bool` — private
- **Zweck:** Entscheidet pro Change-Fieldname (text/teachers/dates/address/entities/customfields ...) ob die Aenderung laut Tracking-Config von der Regelausloesung ausgeschlossen wird.
- **Seiteneffekte:** mehrere `get_config('booking', ...)`-Reads; keine Writes.
- **Aufrufkette:** Von `execute` (Change-Filter-Loop).
- **Bewertung:** D — 85 LOC, grosser switch mit verschachtelter dates-Sonderlogik (Z.613-653: zwei geschachtelte Schleifen ueber old/newvalue, mehrere Config-Reads, gemischte Rueckgabe-Semantik true=excluded). Wiederholte `get_config`-Aufrufe statt gebuendelt; address/entities/location mappen identisch (Duplikat). Smell: rule_react_on_event.php:594-678 (lange Methode, tiefe Schachtelung, Duplikat-Cases).

## Triviale Akzessoren
Konstruktorlose Klasse; Properties (`$ruleid`, `$rulename`, `$name`, `$rulejson`, `$boevent`, `$intervaldata`, `$ruleisactive`) werden direkt in den oben dokumentierten Settern befuellt, keine separaten Getter/Setter-Methoden.
