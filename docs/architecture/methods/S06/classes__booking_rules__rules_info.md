# rules_info — Methoden-Doku
**Datei:** `classes/booking_rules/rules_info.php` · **LOC:** 687 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
Statische Fassade/Orchestrator fuer das Booking-Rules-Subsystem. Zustaendig fuer (a) Rule-Discovery (Dateisystem-Glob + Extension-Namespaces), (b) Aufbau des Rule-Formulars inkl. Conditions/Actions, (c) Persistenz-Trigger (delegiert an die jeweilige `rule`-Instanz), (d) das eventgetriebene Sammeln, Filtern (Cancel-Logik) und verzoegerte Ausfuehren von Rules am Request-Ende. Kollaborateure: `booking_rules` (DB-Reader), `conditions_info`, `actions_info`, `templaterule`, `singleton_service`, die konkreten `rule_*`-Klassen sowie adhoc-Tasks. Haelt drei statische Request-Sammelpuffer (`$rulestoexecute`, `$rulestocancel`, `$eventstoexecute`). Reine God-Class-Tendenz: gemischte Verantwortung (Form + Discovery + Eventbus + Persistenz) ueber ausschliesslich statische Methoden, daher schwer testbar.

## Methoden

### `add_rules_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, ?array &$ajaxformdata = null): void` — public static
- **Zweck:** Baut den Rule-Editor-Teil des mform: Rule-Typ-Select, Name, Template-Auswahl, Active-Checkbox; delegiert dann an die gewaehlte Rule sowie an Conditions- und Actions-Formulare.
- **Parameter:** `$mform` (per Ref), `$repeateloptions` (per Ref), `$ajaxformdata` optional.
- **Rueckgabe:** void.
- **Seiteneffekte:** Keine DB/Cache-Writes; liest `has_capability(...,context_system)`; modifiziert mform-Elemente und `$repeateloptions`. Ruft `self::get_rules()`, `self::get_rule()`, `templaterule::get_template_rules()`, `conditions_info::add_conditions_to_mform()`, `actions_info::add_actions_to_mform()`, `$rule->add_rule_to_mform()`.
- **Aufrufkette:** Aus Rule-Edit-Form (dynamic form) gerufen.
- **Bewertung:** C — ~107 LOC (76-182), gemischte Verantwortung (Selectaufbau + Capability-Gate + Delegation), viel imperativer mform-Boilerplate. Smell: `rules_info.php:76` (Laenge >80 LOC, mehrere Aufgaben in einer Methode).

### `get_rules(): array` — public static
- **Zweck:** Instanziiert alle verfuegbaren Rule-Klassen — Core via `glob()` ueber das `rules/`-Verzeichnis plus alle `bookingextension_*`-Plugins via Namespace-Scan.
- **Rueckgabe:** Array von Rule-Instanzen.
- **Seiteneffekte:** Dateisystem-Glob (`$CFG->dirroot/.../rules/*.php`), `class_exists`, `new` pro Datei; `core_plugin_manager::instance()->get_plugins_of_type()`, `core_component::get_component_classes_in_namespace()`. Keine DB.
- **Aufrufkette:** Von `add_rules_to_mform`, sowie potenziell von Aufrufern, die alle Rule-Typen brauchen.
- **Bewertung:** C — Klassenname-aus-Dateiname-Konvention + Reflection/Glob; instanziiert ALLE Klassen nur fuer Metainfo (Kosten). Duplizierte Discovery-Logik mit `get_rule`. Smell: `rules_info.php:188` (Glob/Reflection-Discovery, Duplikat zu get_rule).

### `get_rule(string $rulename)` — public static
- **Zweck:** Liefert eine einzelne Rule-Instanz per Klassenkurzname; sucht zuerst im Core-Namespace, dann in allen Extension-Plugins.
- **Parameter:** `$rulename` Kurz-Klassenname.
- **Rueckgabe:** Rule-Instanz oder `null`.
- **Seiteneffekte:** `class_exists` + `new`; iteriert Extension-Plugins via `core_plugin_manager`. Keine DB.
- **Aufrufkette:** Zentral — von set_data_for_form, save_booking_rule, execute_* , collect_rules_for_execution, filter etc.
- **Bewertung:** C — String-Concatenation von Klassennamen (fragiles `new $classname`), Discovery-Duplikat zu `get_rules`. Smell: `rules_info.php:228` (dynamische Klassenkonstruktion, Duplikat).

### `set_data_for_form(object &$data): object` — public static
- **Zweck:** Befuellt das Formdata-Objekt zum Editieren einer bestehenden Rule (oder eines Template-Records bei negativer ID) inkl. Condition/Action/Rule-Defaults.
- **Parameter:** `$data` (per Ref, erwartet `->id`).
- **Rueckgabe:** Object (gecastetes `$data`).
- **Seiteneffekte:** DB-Read `booking_rules` (per id); bei id<0 `templaterule::get_template_record_by_id()`; `json_decode(rulejson)`; ruft `conditions_info::get_condition`, `actions_info::get_action`, `self::get_rule`, jeweils `->set_defaults`.
- **Aufrufkette:** Aus Rule-Edit-Form set_data.
- **Bewertung:** B — fokussiert, lineare Verantwortung; leichte Mischung aus Template- und DB-Pfad, aber lesbar. Kein Null-Check auf `$record`/`$rule`/`$condition`.

### `save_booking_rule(stdClass &$data): int` — public static
- **Zweck:** Persistiert eine Rule: laesst Condition/Action ihre Werte in den rulejson-Key schreiben, ruft `rule->save_rule()` (eigentlicher DB-Write) und triggert anschliessend die Ausfuehrung.
- **Parameter:** `$data` (per Ref).
- **Rueckgabe:** `$ruleid` (int).
- **Seiteneffekte:** Indirekt DB-Write ueber `$rule->save_rule()` (Tabelle `booking_rules`); ruft `self::execute_booking_rules($ruleid)`. Setzt `$data->id`.
- **Aufrufkette:** Aus Rule-Edit-Form Submit.
- **Bewertung:** B — schlanke Delegation, klare Reihenfolge-Kommentare; vertraut auf `get_rule/get_condition/get_action` ohne Null-Pruefung.

### `execute_booking_rules(int $ruleid = 0): void` — public static
- **Zweck:** Laedt Rules aus DB (alle oder per id) und fuehrt sie unmittelbar aus.
- **Parameter:** `$ruleid` optional (0 = alle).
- **Seiteneffekte:** DB-Read `booking_rules`; `$rule->set_ruledata()` + `$rule->execute()` (deren Effekte umfassen Tasks/Mails).
- **Aufrufkette:** Von `save_booking_rule`; ggf. extern.
- **Bewertung:** B — kompakt, klar; vollstaendige Tabellen-Iteration bei `$ruleid=0` potenziell teuer.

### `execute_rules_for_option(int $optionid, int $userid = 0): void` — public static
- **Zweck:** Nach Option-Aenderung/Buchung reapplizierbare (nicht-eventbasierte) Rules des Option-Kontexts ausfuehren.
- **Parameter:** `$optionid`, `$userid` optional.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings()`; `context_module::instance($cmid)`; DB-Read via `booking_rules::get_list_of_saved_rules_by_context()`; `$rule->set_ruledata` + `$rule->execute`. Skippt inaktive und `rule_react_on_event`.
- **Aufrufkette:** Nach Option add/update bzw. Buchung.
- **Bewertung:** B — fokussiert; `global $DB` deklariert aber ungenutzt (Cruft). Frueher return bei fehlendem cmid sauber.

### `execute_rules_for_user(int $userid): void` — public static
- **Zweck:** Nach User-Add/Update zeitbasierte Rules (`rule_daysbefore`, `rule_specifictime`) ausfuehren.
- **Parameter:** `$userid`.
- **Seiteneffekte:** DB-Read `booking_rules` per `get_records_list` (rulename-Filter); `$rule->set_ruledata` + `$rule->execute(0,$userid)`.
- **Aufrufkette:** Nach User-Event.
- **Bewertung:** B — klein, klar.

### `delete_rule(int $ruleid): void` — public static
- **Zweck:** Loescht eine Rule aus der DB.
- **Seiteneffekte:** DB-Write (delete) `booking_rules`.
- **Bewertung:** A — Trivial-Wrapper.

### `collect_rules_for_execution(\core\event\base $event): void` — public static
- **Zweck:** Event-Observer-Einstieg: ermittelt aus dem Event die optionid + zugehoerige Rules (kontextbasiert + Companion-Intervall-Rules), reichert deren rulejson mit Eventdaten an und legt ausfuehrungsbereite Rules in den statischen Puffer `$rulestoexecute`.
- **Parameter:** `$event`.
- **Seiteneffekte:** `$event->get_data()`; DB-Read via `booking_rules::get_list_of_saved_rules_by_context()`; `json_decode`/`json_encode` der rulejson; befuellt `self::$rulestoexecute`. Ruft `self::proceed_with_event`, `self::get_companion_interval_rules_for_waitinglist_join`, `self::get_rule`.
- **Aufrufkette:** Aus dem Moodle-Event-Observer (events.php) gerufen; spaeter konsumiert von `filter_rules_and_execute`.
- **Bewertung:** C — ~60 LOC mit verschachtelter optionid-Aufloesung (3 Fallbacks), JSON-Roundtrip pro Record und tiefer Bedingungs-Schachtelung. Kommentar `// THIS is the place...` deutet auf historisch gewachsene Stelle. Smell: `rules_info.php:414` (gemischte Verantwortung: Event-Parsing + Rule-Matching + Pufferaufbau, JSON-Mutation).

### `filter_rules_and_execute(): void` — public static
- **Zweck:** Verarbeitet die gesammelten Rules am Request-Ende: wendet die Cancel-/Exclude-Logik an (Rules koennen andere Rules abbrechen) und fuehrt die verbleibenden aktiven Rules aus.
- **Seiteneffekte:** Mutiert `self::$rulestoexecute` und `self::$rulestocancel`; `json_decode(rulejson)`; ruft `$rule->execute()`.
- **Aufrufkette:** Request-Shutdown / nach Event-Sammlung.
- **Bewertung:** C — ~54 LOC, zwei Schleifen mit doppelter `unset`-Buchfuehrung auf zwei Arrays gleichzeitig (`$rulestoexecute` lokal + `self::$rulestoexecute`), verschachtelte Cancel-Iteration, Variablen-Shadowing von `$rulearray`/`$key`. Fehleranfaellig. Smell: `rules_info.php:480` (komplexe Mutationslogik, doppelte Array-Buchfuehrung, Shadowing).

### `get_companion_interval_rules_for_waitinglist_join(int $contextid, int $optionid, string $eventname, array $data): array` — private static
- **Zweck:** Sonderfall: bei spaeten Wartelisten-Beitritten nach Drainen der urspruenglichen Free-to-book-again-Kette die Intervall-Rules (`send_mail_interval`/`confirm_bookinganswer`) erneut anwenden — sofern Option nicht voll und keine aktiven Tasks existieren.
- **Parameter:** contextid, optionid, eventname, data.
- **Rueckgabe:** Array von Rule-Records (mit gesetztem `forceexecuteoncurrentevent`/`forceuseridfromevent`).
- **Seiteneffekte:** DB-Read `booking_rules::get_list_of_saved_rules_by_context()`; `json_decode`; ruft `self::option_is_fully_booked`, `self::interval_rule_has_active_tasks`.
- **Aufrufkette:** Nur von `collect_rules_for_execution`.
- **Bewertung:** C — ~38 LOC, sehr spezifische Geschaeftslogik (Hardcodierte Action-/Condition-Namen, Eventname-Stringvergleich) in der Fassade; eng gekoppelt an die Booking-Race/Warteliste-Domaene. Smell: `rules_info.php:545` (domaenenspezifischer Sonderfall mit Magic-Strings im Orchestrator).

### `option_is_fully_booked(int $optionid): bool` — private static
- **Zweck:** Prueft via frische Booking-Answers, ob eine Option voll belegt ist (fail-closed: Exception => true).
- **Seiteneffekte:** `singleton_service::destroy_booking_answers()` (Cache-Invalidierung des Singletons), `get_instance_of_booking_option_settings`, `get_instance_of_booking_answers`. Try/catch um Throwable.
- **Bewertung:** B — kompakt; bewusstes fail-closed-Verhalten dokumentiert.

### `interval_rule_has_active_tasks(int $ruleid, int $optionid): bool` — private static
- **Zweck:** Prueft, ob bereits adhoc-Tasks (confirm_bookinganswer/send_mail) fuer dieselbe Rule+Option in der Queue liegen (Doppelausfuehrungs-Schutz).
- **Seiteneffekte:** `\core\task\manager::get_adhoc_tasks()` pro Taskklasse; liest `get_custom_data()`.
- **Bewertung:** B — klar; verschachtelte Schleife aber begrenzt (2 Taskklassen).

### `proceed_with_event(\core\event\base $event, array $data): bool` — private static
- **Zweck:** Entscheidet, ob ein Event eines Fremdplugins (aktuell nur `local_shopping_cart`) ueberhaupt Rules ausloesen darf (Whitelist von Eventnamen).
- **Rueckgabe:** bool.
- **Seiteneffekte:** Keine; reiner Switch + String-Matching.
- **Bewertung:** B — uebersichtlich; Magic-Strings, aber lokal begrenzt und kommentiert.

### `events_to_execute(): void` — public static
- **Zweck:** Fuehrt die gesammelten verzoegerten Event-Callables (`$eventstoexecute`) aus und leert den Puffer.
- **Seiteneffekte:** Ruft jedes Callable in `self::$eventstoexecute`; mutiert den Puffer.
- **Bewertung:** A — trivial.

### `destroy_singletons(): void` — public static
- **Zweck:** Setzt alle drei statischen Request-Puffer zurueck (Testbarkeit/Request-Reset).
- **Bewertung:** A — trivial.

### Triviale Akzessoren / statische Felder
- `$rulestoexecute`, `$rulestocancel`, `$eventstoexecute` (public static arrays) — Request-Sammelpuffer; werden von collect/filter/events/destroy verwaltet. Globaler veraenderlicher Zustand (Smell, aber bewusst als Request-Bus genutzt).
