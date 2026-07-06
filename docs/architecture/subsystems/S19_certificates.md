# S19 — certificates

## Zweck & Grenzen

Das Subsystem kapselt die **Zertifikatsausstellung** in mod_booking auf Basis des Moodle-Admin-Tools `tool_certificate`. Es deckt zwei eng verwandte, aber technisch getrennte Aufgaben ab:

1. **Zertifikatsausstellung** (`certificateclass`): Befüllt eine `tool_certificate`-Vorlage mit Buchungsoptions-Daten (Titel, Lehrkräfte, Termine, Dauer, Custom-Fields, Kompetenzen) und stellt ein PDF-Issue aus; löst danach das Event `certificate_issued` aus. Enthält außerdem die Logik „erfüllen die Pflichtoptionen die Voraussetzung?" (`required_options_fulfilled`).
2. **Regelwerk Zertifikatsbedingungen** (`certificate_conditions/`): Ein generisches, pluggables **Filter → Condition (Logic) → Action**-Framework. Admin-konfigurierte Regeln (Tabelle `booking_cert_cond`) werden beim Event `bookingoption_completed` evaluiert; bei Treffer wird die Action ausgeführt (aktuell ausschließlich `createcertificate`, die wiederum `certificateclass::issue_certificate` aufruft).

**Grenzen:** Das Subsystem rendert keine UI selbst (Renderer/Form/Page liegen außerhalb des Scopes: `output\certificateconditionslist`, `form\certificateconditionsform`, `edit_certificateconditions.php`). Es definiert die Datenmodelle und Evaluations-/Ausstellungslogik. Die `evaluate`-/`execute_action`-Aufrufe werden vom `observer` getriggert (außerhalb Scope). `tool_certificate` ist eine optionale Abhängigkeit — der Code guardet überall mit `class_exists('tool_certificate\\certificate')`.

## Position im Gesamtsystem

```
bookingoption_completed (Event)
        │
        ▼
mod_booking\observer  ──►  certificate_conditions::evaluate_certificate_conditions($event,$userid,$optionid)
                                   │  (lädt aktive booking_cert_cond-Records)
                                   ▼
                           evaluate_single_condition()
                                   │  filters_info / conditions_info / actions_info  (Discovery via core_component)
                                   │  filter->evaluate()  &&  condition->evaluate()
                                   ▼  (bei Treffer)
                           action createcertificate->execute_action()
                                   │
                                   ▼
                           certificateclass::issue_certificate()
                                   │  tool_certificate\template->issue_certificate()/create_issue_file()
                                   ▼
                           Event certificate_issued
```

Daneben Direktpfad (ohne Regelwerk): `booking_option` / Reports können `certificateclass::issue_certificate` direkt aufrufen; die Vorlage und Ablaufdaten werden dann aus dem JSON der Buchungsoption (`get_value_of_json_by_key`) gezogen.

**Externe Kollaborateure (außerhalb Scope):** `mod_booking\observer`, `mod_booking\booking_option`, `mod_booking\booking_option_settings`, `mod_booking\singleton_service`, `mod_booking\option\dates_handler`, `mod_booking\customfield\booking_handler`, `mod_booking\placeholders\placeholders\customfields`, `mod_booking\event\certificate_issued`, `mod_booking\event\bookingoption_completed`, `mod_booking\output\certificateconditionslist` + `output\renderer`, `core_competency\competency`, `tool_certificate\template`, `tool_certificate\certificate`. Forms/Page: `form\certificateconditionsform`, `form\deletecertificateconditionform`, `edit_certificateconditions.php`.

## Schlüsselkonzepte

- **Drei-Säulen-Pattern (Filter/Condition/Action):** Jede Säule hat ein Interface (`filter_interface`, `certificate_conditions_interface`, `action_interface`) und eine Discovery-Klasse (`filters_info`, `conditions_info`, `actions_info`), die per `core_component::get_component_classes_in_namespace` alle Implementierungen findet und per Kurznamen instanziiert. Konfiguration wird je Säule als JSON-Spalte im Record persistiert (`filterjson`, `logicjson`, `actionjson`).
- **JSON-Konfiguration mit Diskriminator:** Jedes JSON trägt einen Typmarker (`filtername` / `conditionname` / `actionname`), über den die passende Klasse rehydriert wird (`set_*_from_json`).
- **Items-Tabelle für M:N:** `booking_cert_cond_item` verknüpft eine Condition mit beliebig vielen Buchungsoptionen (`area='bookingoption'`). Sowohl `bookingoption` (Editor-Seite) als auch `taggedoptions` (Option-Form-Seite) schreiben in dieselbe Items-Tabelle.
- **Zwei Condition-Varianten:** `bookingoption` (Option-Auswahl direkt im Condition-Editor) und `taggedoptions` (Option „taggt" sich selbst über das Booking-Option-Formular an eine bestehende Condition). Beide nahezu identisch in `evaluate()`.
- **Idempotenz:** `actions_info::certificate_already_issued()` verhindert Doppelausstellung (außer Config `issuemultiplecertificates`).
- **Optionale Abhängigkeit:** `tool_certificate` darf fehlen — alle Einstiegspunkte sind ge-guarded; ohne `get_config('booking','certificateon')` passiert nichts.

## Datenfluss

**Speichern einer Bedingung** (`certificate_conditions::save_certificate_condition`): Form-Data → je Säule `save_filter/save_condition/save_action` serialisiert in `data->filterjson/conditionjson/actionjson` → in `booking_cert_cond` (insert/update) → Items separat via `save_items_for_condition`. Statische Caches (`$condition`, `$optiontargets`) werden invalidiert.

**Evaluierung & Ausstellung:** siehe Diagramm oben. `evaluate_single_condition` rehydriert Filter/Condition/Action aus JSON, ruft `set_logicdata($record)` (lädt Items aus DB), prüft `filter->evaluate && condition->evaluate`, baut `actioncontext` und ruft `action->execute_action`. `createcertificate` prüft Idempotenz und ruft `certificateclass::issue_certificate`.

**Ausstellung im Detail** (`issue_certificate`): Template laden → Ablaufdatum berechnen (`toolCertificate::calculate_expirydate`) → Daten-Array aus Option-Settings + Custom-Fields + Kompetenzen + Condition-Infos zusammenbauen → `singleton_service::set_temp_values_for_certificates` (für Placeholder-Auflösung) → `template->issue_certificate` + `create_issue_file` (PDF) → Temp-Values zurücksetzen → Event `certificate_issued` mit `required_options`-Daten triggern.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| local/certificateclass.php | `certificateclass` | Service (Ausstellung) | 417 | 8 | C | P2 |
| local/certificate_conditions/certificate_conditions.php | `certificate_conditions` | Service/Repository + Orchestrator | 355 | 12 | C | P2 |
| local/certificate_conditions/conditions/bookingoption.php | `bookingoption` | Condition | 352 | 9 | C | P2 |
| local/certificate_conditions/conditions/taggedoptions.php | `taggedoptions` | Condition | 270 | 9 | C | P2 |
| local/certificate_conditions/actions/createcertificate.php | `createcertificate` | Action | 231 | 10 | B | P3 |
| local/certificate_conditions/filters/userprofilefield.php | `userprofilefield` | Filter | 223 | 9 | B | P3 |
| local/certificate_conditions/option_conditions_info.php | `option_conditions_info` | Helper (Option-Form-Integration) | 201 | 5 | B | P3 |
| local/certificate_conditions/actions_info.php | `actions_info` | Discovery + Idempotenz-Check | 143 | 4 | B | P3 |
| local/certificate_conditions/filters_info.php | `filters_info` | Discovery + Form-Selector | 137 | 3 | B | P3 |
| local/certificate_conditions/certificate_conditions_interface.php | `certificate_conditions_interface` | Interface | 115 | 9 | A | - |
| local/certificate_conditions/conditions_info.php | `conditions_info` | Discovery + Form-Selector | 111 | 3 | A | - |
| local/certificate_conditions/filter_interface.php | `filter_interface` | Interface | 105 | 9 | A | - |
| local/certificate_conditions/action_interface.php | `action_interface` | Interface | 105 | 9 | A | - |
| README.md | — | Doku (veraltet) | — | — | D | P3 |

### `certificateclass` (local/certificateclass.php)
Reine Static-Utility-Klasse für die PDF-Zertifikatsausstellung und die Pflichtoptions-Prüfung. Stark gekoppelt an `singleton_service`, `booking_option`, `tool_certificate` und mehrere Domänen-Helper.

Methoden-Inventar:
- `public static issue_certificate(int $optionid, int $userid, int $completeddate=0, int $templateid=0, ?int $expirydatetype=null, ?int $expirydateabsolute=null, ?int $expirydaterelative=null, ?stdClass $condition=null): int` — Zentrale Ausstellung: lädt Vorlage, baut Daten, stellt Issue + PDF aus, triggert `certificate_issued` (`certificateclass.php:58`). Lang (~126 Z.), viele Verantwortlichkeiten.
- `private static get_required_options_data(booking_option_settings $settings, int $userid): array` — Sammelt Completion-Status der Pflicht-Optionen für das Event (`:195`).
- `private static return_competencies_for_certificate(string $competencies): string` — Kompetenz-IDs → Shortname-Liste via `core_competency\competency` (`:239`).
- `private static return_teachers_for_certificate(array $teachers): string` — Lehrkräfte als `<br />`-Liste (`:262`).
- `private static return_duration_for_certificate(object $settings): string` — Dauer aus Sessions/Kurszeiten (`:278`).
- `private static return_sessions_for_certificate(array $sessions): string` — Termine via `dates_handler::prettify_optiondates_start_end` (`:309`).
- `private static return_timeawarded_for_certificate(booking_option_settings $settings, int $userid, int $completeddate): string` — Awarded-Datum (Completion/timemodified) (`:331`).
- `public static required_options_fulfilled(booking_option_settings $settings, int $userid): bool` — Alle/eine Pflichtoption(en) abgeschlossen? Modus aus Options-JSON (`:362`).
- `public static one_required_option_fulfilled(array $requiredoptions, int $userid): bool` — Mindestens eine Pflichtoption abgeschlossen (`:404`).

### `certificate_conditions` (local/certificate_conditions/certificate_conditions.php)
Zentrale Helper-/Repository-Klasse: CRUD auf `booking_cert_cond`, Form-Data-Hydration, Caching der Option-Targets und Orchestrierung der Evaluierung. Mischt Persistenz, Rendering-Delegation und Evaluations-Orchestrierung — God-Class-Tendenz.

Methoden-Inventar:
- `public static get_rendered_list_of_saved_conditions(int $contextid=1, bool $enableaddbutton=true): string` — Delegiert Rendering an `output\certificateconditionslist` (`:45`).
- `public static get_list_of_saved_conditions(int $contextid=0): array` — Records aus `booking_cert_cond`, mit Static-Cache `$condition` (`:61`).
- `public static delete_conditions_by_context(int $contextid): void` — Löscht alle Conditions eines Kontexts (außer System) (`:77`).
- `public static delete_condition(int $id): void` — Löscht Condition + Items, invalidiert Caches (`:93`).
- `public static option_is_targeted_by_condition(int $optionid): bool` — Cache-gestützte Prüfung, ob Option in einer Condition referenziert ist (`:107`).
- `private static build_option_targets_cache(): void` — Baut `$optiontargets` aus `booking_cert_cond_item` (`:120`).
- `public static set_data_for_form(object &$data): object` — Hydratisiert Form-Daten aus Record + delegiert `set_defaults` an Säulen (`:146`).
- `public static save_certificate_condition(stdClass &$data): int` — Persistiert Condition; serialisiert je Säule, insert/update (`:187`).
- `public static save_items_for_condition(int $conditionid, stdClass $data): void` — Delegiert Items-Persistenz an Condition (`:241`).
- `public static evaluate_certificate_conditions(object $event, int $userid, int $optionid): void` — Public-Einstieg vom Observer (`:256`).
- `public static evaluate_certificate_conditions_with_result(...): bool` — Iteriert aktive Records, gibt Trigger-Status zurück (`:272`).
- `private static evaluate_single_condition(stdClass $record, stdClass $eventcontext, int $userid, int $optionid): bool` — Rehydriert+evaluiert Filter/Condition, führt Action aus (`:303`).
- `public static reset_caches(): void` — Cache-Reset (Tests) (`:351`).

### `bookingoption` (conditions/bookingoption.php)
Condition: prüft, ob (genügend) ausgewählte Buchungsoptionen vom User abgeschlossen wurden. Option-Auswahl via Autocomplete-Form mit AJAX-Selector. Implementiert `certificate_conditions_interface`.

Methoden-Inventar (Interface-Vertrag):
- `add_logic_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata=null)` — Autocomplete-Optionsauswahl (mit `valuehtmlcallback`-Render) + Requiredcount-Feld; löst cmid aus contextid auf (`:59`).
- `get_name_of_logic(bool $localized=true): string` — Label (`:132`).
- `save_condition(stdClass &$data): void` — `conditionname`+`requiredcount` → `conditionjson` (`:142`).
- `save_items(int $conditionid, stdClass $data): void` — Optionsauswahl → `booking_cert_cond_item` (delete+insert) (`:162`).
- `set_defaults(stdClass &$data, stdClass $record)` — Lädt Option-IDs (DB) + requiredcount (JSON) in Form (`:198`).
- `set_logicdata(stdClass $record): void` — Lädt `$optionids` aus Items + requiredcount aus JSON (`:234`).
- `set_conditiondata_from_json(string $json): void` — requiredcount aus JSON (`:269`).
- `execute(stdClass &$sql, array &$params): void` — leerer SQL-Stub (`:283`).
- `evaluate(stdClass $context): bool` — Bei `bookingoption_completed`: zählt abgeschlossene Kandidaten ≥ requiredcount (`:291`).
- `validate(array $data): array` — Pflichtfeld-Validierung (`:340`).

### `taggedoptions` (conditions/taggedoptions.php)
Condition-Variante für den umgekehrten Weg: die Buchungsoption „hängt sich" über ihr eigenes Formular an eine bestehende Condition (Tagging). `evaluate`/`set_*` weitgehend identisch zu `bookingoption` (Code-Duplikat). Implementiert `certificate_conditions_interface`.

Methoden-Inventar: wie `bookingoption`, abweichend:
- `add_logic_to_mform(...)` — nur Requiredcount-Feld, keine Optionsauswahl (`:59`).
- `save_condition(...)` — `conditionname='taggedoptions'` (`:95`).
- `save_items(int $conditionid, stdClass $data): void` — Schreibt pro `$data->conditions` einen Item-Link für `$data->optionid` (delete+insert je Ziel-Condition); ignoriert den `$conditionid`-Parameter (`:115`).
- `evaluate`, `set_logicdata`, `set_defaults`, `set_conditiondata_from_json`, `execute`, `get_name_of_logic`, `validate` — analog `bookingoption` (`:142`–`:269`).

### `createcertificate` (actions/createcertificate.php)
Einzige Action: stellt über `certificateclass::issue_certificate` ein Zertifikat aus. Hält Template-ID + Ablaufdaten als Properties. Implementiert `action_interface`.

Methoden-Inventar:
- `add_action_to_mform(...)` — Template-Select + `toolCertificate::add_expirydate_to_form` (`:66`).
- `private static get_available_certificate_templates(): array` — Templates aus `tool_certificate_templates` (`:85`).
- `get_name_of_action(bool $localized=true): string` — Label (`:108`).
- `save_action(stdClass &$data): void` — certid+expiry → `actionjson` (`:118`).
- `set_defaults(stdClass &$data, stdClass $record)` — Form-Defaults aus JSON (`:135`).
- `set_actiondata(stdClass $record): void` — leerer Stub (`:151`).
- `set_actiondata_from_json(string $json): void` — Properties aus JSON (`:160`).
- `execute(stdClass &$sql, array &$params): void` — leerer SQL-Stub (`:177`).
- `execute_action(stdClass $context, stdClass $condition): void` — Idempotenz-Check + `certificateclass::issue_certificate` (`:188`).
- `validate(array $data): array` — certid-Pflicht (`:222`).

### `userprofilefield` (filters/userprofilefield.php)
Einziger Filter: vergleicht ein User-Profilfeld mit einem konfigurierten Wert (`=` oder `~`/contains). Implementiert `filter_interface`.

Methoden-Inventar:
- `add_filter_to_mform(...)` — Profilfeld-Select + Wert-Textfeld (`:58`).
- `private static get_available_profile_fields(): array` — Felder aus `user_info_field` (`:79`).
- `get_name_of_filter(bool $localized=true): string` — Label (`:98`).
- `save_filter(stdClass &$data): void` — filtername/field/value → `filterjson` (`:108`). Hinweis: `operator` wird gespeichert nicht, beim Lesen aber erwartet.
- `set_defaults(...)` / `set_filterdata(stdClass $record)` (leer) / `set_filterdata_from_json(string $json)` — Hydration (`:123`,`:138`,`:148`).
- `execute(stdClass &$sql, array &$params): void` — leerer Stub (`:164`).
- `evaluate(stdClass $context): bool` — Vergleicht `$user->profile[$field]` über `singleton_service::get_instance_of_user` (`:174`).
- `validate(array $data): array` — field+value Pflicht (`:210`).

### `option_conditions_info` (option_conditions_info.php)
Helper zur Integration der Condition-Anzeige/Tagging im Booking-Option-Formular. Enthält eigene Roh-SQL-Joins.

Methoden-Inventar:
- `add_static_info_to_mform(MoodleQuickForm &$mform, array $formdata): void` — Tagging-Autocomplete + Liste der referenzierenden Conditions (mit Links zur Editor-Seite) (`:40`).
- `private static get_condition_infos_targeting_option(int $optionid): array` — Roh-SQL Join `booking_cert_cond_item`×`booking_cert_cond`×`context`, baut Edit-URLs (`:90`).
- `public static get_all_taggedoptions_conditions(): array` — Conditions vom Typ `taggedoptions` (JSON-Decode-Filterung) (`:139`).
- `public static get_tagged_condition_ids_for_option(int $optionid): array` — Roh-SQL `LIKE '%"conditionname":"taggedoptions"%'` (`:164`).
- `public static save_tagged_conditions_from_option_form(array $formdata): void` — Delegiert an `taggedoptions::save_items` (`:188`).

### `actions_info` (actions_info.php)
Discovery der Action-Klassen + Idempotenz-Helper.
- `add_actions_to_mform(...)` — Action-Selector + delegiert Felder (`:37`).
- `get_actions(): array` — Instanzen via `core_component` (`:90`).
- `get_action(string $name)` — Instanz per Kurzname (`:108`).
- `certificate_already_issued(int $conditionid, int $certid, int $userid): bool` — Prüft `tool_certificate_issues` per JSON-`conditionid` (`:126`).

### `filters_info` (filters_info.php)
Discovery der Filter-Klassen + Selector (inkl. „norestriction"-Option und optionalem `is_compatible_with_ajaxformdata`-Skip).
- `add_filters_to_mform(...)` (`:37`), `get_filters()` (`:113`), `get_filter(string $name)` (`:130`).

### `conditions_info` (conditions_info.php)
Discovery der Condition-Klassen + Selector.
- `add_conditions_to_mform(...)` (`:37`), `get_conditions()` (`:86`), `get_condition(string $name)` (`:104`).

### Interfaces (`certificate_conditions_interface`, `filter_interface`, `action_interface`)
Definieren die jeweiligen Säulen-Verträge: `add_*_to_mform`, `get_name_of_*`, `save_*`, `set_defaults`, `set_*data`, `set_*data_from_json`, `execute` (SQL-Stub), `evaluate`/`execute_action`, `validate`. `certificate_conditions_interface` zusätzlich `set_logicdata` und `save_items`. Sauber, klein, A.

## Persistenz

**DB-Tabellen:**
- `booking_cert_cond` — Bedingungs-Records: `contextid`, `name`, `isactive`, `useastemplate`, `filterjson`, `logicjson`, `actionjson`, `timecreated`, `timemodified`. (Spalte heißt `logicjson`, das Feld-Mapping nutzt aber teils `conditionname`/`logicname` — siehe Schulden.)
- `booking_cert_cond_item` — M:N Condition↔Buchungsoption: `conditionid`, `itemid` (optionid), `component='mod_booking'`, `area='bookingoption'`, `configjson` (Placeholder), `sortorder` (Placeholder).
- `tool_certificate_templates` (lesend) — Vorlagenauswahl.
- `tool_certificate_issues` (lesend + via Tool schreibend) — ausgestellte Zertifikate; `data`-JSON enthält `conditionid` (Idempotenz).

**Buchungsoptions-JSON (`booking_option::get_value_of_json_by_key`):** `certificate`, `expirydatetype`, `expirydateabsolute`, `expirydaterelative`, `certificaterequiresotheroptions`, `certificaterequiredoptionsmode`.

**Config (`get_config('booking', …)`):** `certificateon` (Master-Schalter), `issuemultiplecertificates` (Idempotenz-Override).

**Caches:** Static-Properties `certificate_conditions::$condition` und `::$optiontargets` (kein MUC; per `reset_caches`/Mutationen invalidiert). `singleton_service::set_temp_values_for_certificates` setzt transiente Werte für Placeholder-Auflösung während der Ausstellung.

## Extension-Points

- **Drei Säulen-Interfaces** (`filter_interface`, `certificate_conditions_interface`, `action_interface`): Neue Filter/Conditions/Actions werden allein durch Ablage einer Klasse im jeweiligen Namespace (`local\certificate_conditions\{filters|conditions|actions}`) registriert — automatische Discovery via `core_component::get_component_classes_in_namespace`. Kein zentrales Register nötig.
- **Optionaler Filter-Skip:** `filters_info` ruft optionales `is_compatible_with_ajaxformdata()` auf, wenn vorhanden (Method-Exists-Hook).
- **Event-getrieben:** Reagiert auf `bookingoption_completed`; emittiert `certificate_issued`.
- **`useastemplate`-Spalte** persistiert, aber im gelesenen Code nirgends ausgewertet (vorgesehener, ungenutzter Extension-Point).

## Bekannte Schulden (→ Blueprint)

- **README veraltet/irreführend:** Beschreibt `logics/`, `logic_interface.php`, `logics_info.php`, `set_logicdata_from_json`, `execute_action($context)` (1 Param) und Namespaces ohne `local\` — alles weicht vom Ist-Code ab (`conditions/`, `certificate_conditions_interface`, `set_conditiondata_from_json`, `execute_action($context,$condition)`). Risiko für Fremd-Devs. `README.md`.
- **Terminologie-Inkonsistenz Logic↔Condition:** Spalte `logicjson`, Methode `set_logicdata`, aber `conditionname`/`set_conditiondata_from_json`; `set_data_for_form` muss beide Marker abdecken (`certificate_conditions.php:164,168`). Fragiles Doppel-Naming.
- **Code-Duplikat `bookingoption` vs. `taggedoptions`:** `evaluate`, `set_logicdata`, `set_defaults`, `set_conditiondata_from_json` nahezu identisch (`conditions/bookingoption.php:291` ≈ `conditions/taggedoptions.php:218`). Gemeinsame Basisklasse fehlt.
- **`taggedoptions::save_items` ignoriert `$conditionid`-Parameter** und schreibt stattdessen pro `$data->conditions` — abweichende Semantik vom Interface-Vertrag, verwirrend (`conditions/taggedoptions.php:115`).
- **`userprofilefield` Operator nie gespeichert:** `save_filter` schreibt kein `operator`-Feld (`filters/userprofilefield.php:108`), `set_filterdata_from_json` liest `operator` (`:152`) → effektiv immer Default `=`, `~`-Pfad toter Code.
- **`certificateclass::issue_certificate` zu groß / zu viele Verantwortungen** (~126 Z., Datenaufbau + Custom-Fields + Issue + PDF + Event): schwer testbar, viele statische God-Calls (`singleton_service`, `booking_handler`, `customfields`) (`certificateclass.php:58`). Variable `$conditionfields` nur conditional gesetzt, später via `?? []` benutzt (`:142`,`:151`).
- **`condition->id` ohne Null-Guard:** in `issue_certificate` wird `$condition->id` mehrfach genutzt (`:143`,`:153`) obwohl `$condition` nullable ist (set via `?? 0`/`?? []` teils, aber Zeile 143/144 unter `!empty($condition)` ok — `:153` nutzt `$condition->id ?? 0` korrekt). Inkonsistente Nullsicherheit.
- **Tote/Leere Interface-Methoden:** `execute(stdClass &$sql, array &$params)` ist in allen Implementierungen ein leerer Stub (Legacy SQL-Builder, nie benutzt) — Interface-Ballast (`*_interface.php`, jeweils alle Implementierungen).
- **Roh-SQL mit JSON-`LIKE`** in `option_conditions_info::get_tagged_condition_ids_for_option` (`%"conditionname":"taggedoptions"%`) — DB-portabel fragil, nicht indexierbar (`option_conditions_info.php:164`).
- **`certificate_conditions::$condition`-Cache nur bei `$contextid != 0` genutzt**, sonst stets frischer DB-Hit; `get_records('booking_cert_cond')` ohne `isactive`-Filter in `get_list_of_saved_conditions` aber mit in `evaluate_*` — uneinheitlich (`certificate_conditions.php:61,283`).
- **Static-only Caches** statt MUC: in langlebigen Prozessen/Cron evtl. veraltet; nur `reset_caches` für Tests vorgesehen.
