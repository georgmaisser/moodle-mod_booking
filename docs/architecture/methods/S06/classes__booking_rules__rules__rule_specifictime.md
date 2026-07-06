# rule_specifictime — Methoden-Doku
**Datei:** `classes/booking_rules/rules/rule_specifictime.php` · **LOC:** 534 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`rule_specifictime` implementiert das `booking_rule`-Interface und repraesentiert eine Regel, die zu einem definierten Zeitpunkt (n Sekunden vor/nach einem Datumsfeld der Buchungsoption) eine Aktion ausloest. Die Klasse kapselt Formular-Definition (`add_rule_to_mform`/`set_defaults`), Persistenz als JSON in `booking_rules` (`save_rule`/`set_ruledata*`) sowie die eigentliche Ausfuehrungslogik, die per dynamisch gebautem SQL die betroffenen Optionen/Optiondates ermittelt und an Condition + Action delegiert. Kollaborateure: `actions_info`, `conditions_info`, `singleton_service`, `applybookingrules`, `bo_info`, `context`.

## Methoden

### `set_ruledata(stdClass $record): void` — public
- **Zweck:** Uebernimmt ein DB-Record (`booking_rules`) in das Objekt und delegiert JSON-Parsing.
- **Parameter:** `$record` — Regel-Record. **Rueckgabe:** void.
- **Seiteneffekte:** Setzt `$this->ruleid/contextid/ruleisactive`; ruft `set_ruledata_from_json`. Keine DB/Cache/Events.
- **Aufrufkette:** Vom Rules-Loader/`booking_rules`-Infrastruktur beim Instanziieren.
- **Bewertung:** A — schmaler Loader.

### `set_ruledata_from_json(string $json): void` — public
- **Zweck:** Dekodiert den JSON-String und befuellt `name`, `seconds`, `datefield`.
- **Parameter:** `$json`. **Rueckgabe:** void.
- **Seiteneffekte:** `json_decode`; Property-Zuweisung mit Number-Guard (Fallback `seconds = 0`). Keine DB/Cache/Events.
- **Aufrufkette:** Aus `set_ruledata` sowie potenziell direkt zum Laden ohne DB-Record.
- **Bewertung:** B — robust gegen fehlende `seconds`, aber `$ruleobj->ruledata->datefield` (Z.99) ungeguardet (faellt bei kaputtem JSON).

### `add_rule_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, array $ajaxformdata = []): void` — public
- **Zweck:** Fuegt die Formularelemente der Regel hinzu (Datumsfeld-Auswahl, Dauer, vorher/nachher).
- **Parameter:** `$mform` (ref), `$repeateloptions` (ref), `$ajaxformdata`. **Rueckgabe:** void.
- **Seiteneffekte:** Mutiert `$mform`/`$repeateloptions`; bedingte Erweiterung um `installmentpayment` falls `local_shopping_cart\shopping_cart` existiert (`class_exists`-Probe). Viele `get_string`-Calls.
- **Aufrufkette:** Von der Rules-Editor-Form (`rules_info`/Form-Builder).
- **Bewertung:** B — ~54 LOC reine Formdefinition, gut lesbar; leichte Kopplung an Fremdplugin via `class_exists`-Feature-Detection.

### `get_name_of_rule(bool $localized = true): string` — public
- **Zweck:** Liefert lokalisierten oder technischen Regelnamen.
- **Bewertung:** A — trivial.

### `save_rule(stdClass &$data): int` — public
- **Zweck:** Baut den JSON-Datensatz aus Formdaten und persistiert ihn in `booking_rules` (insert/update).
- **Parameter:** `$data` (ref, Formdaten). **Rueckgabe:** `ruleid` (int).
- **Seiteneffekte:** DB-Write `booking_rules` (`update_record`/`insert_record`); setzt bei Insert `$this->ruleid`. Keine Cache-Purge/Events.
- **Aufrufkette:** Aus dem Speicherpfad der Rules-Editor-Form.
- **Bewertung:** C — `seconds`-Berechnung Z.196-198 hat fehlerhafte `??`-Operator-Praezedenz: `(int) $data->... ?? 1` bindet `(int)` vor `??`, der Default-Zweig greift faktisch nie und ist irrefuehrend (siehe notes). Sonst klare DB-Logik.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Formular-Defaults beim Editieren aus dem gespeicherten Record.
- **Parameter:** `$data` (ref), `$record`. **Rueckgabe:** void.
- **Seiteneffekte:** `json_decode`; mappt negative `seconds` -> vorher/nachher-Select + abs(Dauer). Keine DB/Cache/Events.
- **Aufrufkette:** Form-Initialisierung (Edit-Modus).
- **Bewertung:** B — sauber, ungeguardetes `$ruledata->datefield`/`$jsonobject->name` bei korruptem JSON.

### `execute(int $optionid = 0, int $userid = 0): void` — public
- **Zweck:** Fuehrt die Regel aus: ermittelt betroffene Records, instanziiert die Action und ruft sie pro Record mit berechneter `nextruntime` auf.
- **Parameter:** `$optionid`, `$userid` (beide optional). **Rueckgabe:** void.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (Read/Cache); `applybookingrules::apply_rule` (Gate); ueber `get_records_for_execution` indirekter DB-Read; `actions_info::get_action(...)->execute($record)` triggert die eigentliche Aktion (Tasks/Notifications). Mutiert `$this->seconds` pro Record (Z.282).
- **Aufrufkette:** Von Cron/Event-Trigger der Rules-Engine; ruft `should_skip_for_selflearningcourse`, `get_records_for_execution`, Action.
- **Bewertung:** C — Mutation des Instanz-States `$this->seconds` innerhalb der Schleife (Z.282) ist ein verdeckter Seiteneffekt, der `check_if_rule_still_applies` teilt; statischer God-Call `actions_info::get_action`. Sonst kompakt.

### `check_if_rule_still_applies(int $optionid, int $userid, int $nextruntime, int $optiondateid = 0): bool` — public
- **Zweck:** Prueft bei Adhoc-Task-Ausfuehrung, ob die Regel/der Zeitpunkt noch gueltig ist (Re-Validierung nach moeglicher Aenderung).
- **Parameter:** ids + `$nextruntime` + optional `$optiondateid`. **Rueckgabe:** bool.
- **Seiteneffekte:** `applybookingrules::apply_rule`; `singleton_service`-Read; `get_records_for_execution(..., testmode=true)` DB-Read; mutiert `$this->seconds` (Z.354).
- **Aufrufkette:** Aus Adhoc-Task vor Aktionsausfuehrung.
- **Bewertung:** C — ~63 LOC, tiefe Verschachtelung (if/else mit innerer Schleife und break/continue), zwei parallele Vergleichszweige (optiondate vs. einfach) mit dupliziertem `oldnextruntime`-Vergleich; Lesbarkeit/Testbarkeit leiden. Kommentar-Typo "macht" (Z.335).

### `should_skip_for_selflearningcourse(object $settings, stdClass $jsonobject): bool` — private
- **Zweck:** Liefert true, wenn die Option ein Self-Learning-Kurs ist und das Datumsfeld dort nur Sortier-, kein Reminder-Feld ist.
- **Bewertung:** A — kleine, klare Pruedikatfunktion, geteilt von `execute`/`check_if_rule_still_applies`.

### `get_records_for_execution(int $optionid = 0, int $userid = 0, bool $testmode = false, int $nextruntime = 0): array` — public
- **Zweck:** Baut das (komplexe) SQL aus Regel-Datenfeld, Kontextpfad und Condition-Zusatz und liefert die betroffenen Records.
- **Parameter:** ids, `$testmode` (kein now-Filter), `$nextruntime`. **Rueckgabe:** Record-Array.
- **Seiteneffekte:** `context::instance_by_id` (Read); `bo_info::check_for_sqljson_key_in_object` (SQL-Fragment-Builder); `conditions_info::get_condition(...)->execute()` mutiert `$sql`/`$params`; DB-Read `$DB->get_records_sql` ueber `booking_options/course_modules/modules/context/booking_optiondates`.
- **Aufrufkette:** Aus `execute` und `check_if_rule_still_applies`; delegiert an Condition.
- **Bewertung:** D — ~134 LOC, gemischte Verantwortung (Param-Aufbau + Dialekt-Workarounds + 3-Zweig-SQL-Switch + Condition-Delegation + Query-Ausfuehrung). Im `default`-Zweig wird `$ruledata->datefield` **direkt in SELECT und WHERE interpoliert** (Z.500/502) statt gebunden — String stammt aus gespeichertem JSON/Form (PARAM_TEXT). Spaltenname-Whitelist passiert nur in der Form, nicht hier; sproede gegen kuenftige Felder. Postgres-Cast-Workarounds als Inline-SQL-Kommentare verstreut.

## Hinweis zu trivialen Properties
Properties `$rulename`, `$rulenamestringid`, `$contextid`, `$name`, `$rulejson`, `$ruleid`, `$seconds`, `$datefield`, `$ruleisactive` sind reine Zustandsfelder ohne eigene Akzessoren (direkter Public-Zugriff bei mehreren).
