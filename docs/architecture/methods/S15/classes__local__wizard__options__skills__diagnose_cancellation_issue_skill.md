# diagnose_cancellation_issue_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/diagnose_cancellation_issue_skill.php` · **LOC:** 907 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Read-only Agent-Skill (R0) fuer die Diagnose `mod_booking.diagnose_cancellation_issue`: erklaert, warum ein (eigener oder fremder) Nutzer eine Buchungsoption nicht stornieren kann. Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface`. Kollaborateure: `bo_info`/`cancelmyself` (Availability-Conditions), `singleton_service` (Settings/Answers), `booking_option`/`booking` (JSON-Config), `booking_skill_support` (User-/Option-Resolution, Link-Bau), `preflight_result_v2`. Kern ist die Klassifikation des hoechsten blockierenden Conditions plus eine lange Kette von Einzel-Checks, die lokalisierte Begruendungszeilen erzeugt.

## Methoden

### `get_schema(): array` — public
- **Zweck:** Liefert das Task-Schema (Beschreibung, readonly-Flag, Beispiel-Utterances, Property-Definitionen question/optionquery/optionid/userquery/targetuserid/outputlang).
- **Parameter/Rueckgabe:** keine / grosses Definitions-Array.
- **Seiteneffekte:** keine.
- **Aufrufkette:** vom Skill-Discovery/Planner gerufen.
- **Bewertung:** B — reines Daten-Array, ~50 LOC aber flach und ohne Logik.

### `get_message_triggers(): array` — public
- **Zweck:** Statische Liste von Trigger-Beispielen fuer das Intent-Matching.
- **Seiteneffekte:** keine. **Aufrufkette:** Interface `skill_trigger_provider_interface`.
- **Bewertung:** A — triviales Konstanten-Array.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert Guidance-Pack (Trigger-Phrasen + Anweisungen) zur direkten Task-Ausfuehrung ohne Rueckfrage.
- **Seiteneffekte:** keine. **Aufrufkette:** Guidance-Injection in den Construction-Prompt.
- **Bewertung:** A — statisches Array.

### `check_structure(array $input): array` — public
- **Zweck:** Strukturvalidierung: erzwingt entweder `question` oder eine Option-Referenz (optionquery/optionid), delegiert User-Ref-Validierung.
- **Rueckgabe:** `{valid:bool, errors:array}`.
- **Seiteneffekte:** keine (DB nur indirekt via `localized_string`).
- **Aufrufkette:** aufgerufen von `preflight()`; ruft `validate_target_user_reference()`.
- **Bewertung:** B — klar, kurz.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Preflight-Validierung: cmid-Aufloesung, Instance-Scope-Guard, Strukturcheck, Aufloesung von Diagnose-User (inkl. Cross-User-Permission) und Option-Id; sammelt errors/ambiguities und reicht angereicherten Input weiter.
- **Rueckgabe:** `preflight_result_v2::ok($preparedinput)` oder `::invalid(...)`.
- **Seiteneffekte:** DB-Reads via resolve_*-Methoden und Capability-Check.
- **Aufrufkette:** Engine-Preflight; ruft `resolve_cmid_from_context_or_cmid`, `require_booking_instance_scope`, `check_structure`, `resolve_diagnostic_user`, `can_analyze_other_user`, `resolve_option_id`, `build_preflight_issues`.
- **Bewertung:** C — ~48 LOC, verschachtelte Status-Auswertung; dupliziert die User/Option-Resolution-Logik, die `execute()` ein zweites Mal ausfuehrt (preflight:218/232 vs execute:295/327). Mehrere Verantwortungen in einer Methode.

### `build_preflight_issues(array $messages): array` — private
- **Zweck:** Mappt freie Meldungstexte auf strukturierte Preflight-Issue-Arrays (Code/Severity/Message).
- **Seiteneffekte:** keine. **Aufrufkette:** von `preflight()`.
- **Bewertung:** A — kurz, klar.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Haupt-Diagnoselauf: Diagnose-User aufloesen, Permission pruefen, Option aufloesen, Settings/Answers/Conditions laden, Enrollment/Visibility/Cancel-Config sammeln und in `reasoncontext` buendeln, Begruendungszeilen erzeugen, Diagnose-Result + Debug zurueckgeben.
- **Rueckgabe:** grosses Result-Array (status/detail/diagnosis/reasons/debugmessage ...).
- **Seiteneffekte:** Viele DB-Reads: `booking_options` (`$DB->get_field` Z.347), `get_coursemodule_from_id`, `context_course::instance`, `is_enrolled`, `get_config('booking','coolingoffperiod')`, `booking_option::get_value_of_json_by_key`, `booking::get_value_of_json_by_key`, `cancelmyself::apply_coolingoff_period`, `bo_info::get_condition_results`, diverse singleton_service-Lookups. Keine Writes (read-only Skill).
- **Aufrufkette:** Engine-Executor; ruft `resolve_*`, `can_analyze_other_user`, `resolve_highest_blocking_condition`, `build_reason_lines`, `build_task_debug_message`, `booking_skill_support::build_option_link_for_output`.
- **Bewertung:** D — ~173 LOC (Z.282-455). Stark gemischte Verantwortung (Resolution + Datensammlung + Config-Aggregation + Result-Bau), viele direkte statische God-Calls und DB-Zugriffe inline, dupliziert User/Option-Resolution aus preflight. Klarer Refactor-Kandidat (Aggregation in eigenen Collector/DTO auslagern).

### `resolve_highest_blocking_condition(array $conditionresults): array` — private
- **Zweck:** Gibt den letzten (hoechstprioren) Eintrag der sortierten Condition-Results zurueck.
- **Seiteneffekte:** keine. **Aufrufkette:** von `execute()`.
- **Bewertung:** A — trivial.

### `build_reason_lines(array $conditionresults, array $highestblocker, array $bookinginformation, array $reasoncontext, object $settings, int $diagnosticuserid, int $currentuserid, string $lang=''): array` — private
- **Zweck:** Erzeugt die menschenlesbaren Begruendungszeilen ueber eine lange Kette von Checks (Enrollment, Visibility, instance/option disablecancel, canceluntil-Deadlines, Preis ohne Cart, Activity-Completion, notbooked/reserved, cancancelbook, effective Deadline, Shopping-Cart-Policy, Waitinglist-Confirmation, Cooling-off, Cancel-Button-verfuegbar); dedupliziert und faellt auf "keine Gruende" zurueck.
- **Seiteneffekte:** DB-Reads via singleton_service (`get_instance_of_booking_answers`, `is_activity_completed`, `get_usersonwaitinglist`), externer Call `local_shopping_cart\shopping_cart::allowed_to_cancel_for_item`, `json_decode`.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** E — ~232 LOC (Z.489-720). Sehr lange Methode, tiefe Verschachtelung (Z.657-694 vierfach), Self/Other-String-Auswahl massiv dupliziert, gemischte Datenbeschaffung und Praesentation. Hauptkandidat fuer Zerlegung in per-Check-Strategien.

### `validate_target_user_reference(array $input, string $lang=''): array` — private
- **Zweck:** Soll User-Referenz vorvalidieren, ist aber bewusst ein No-op (gibt immer leere errors/ambiguities zurueck; harte Resolution erst in execute).
- **Seiteneffekte:** keine. **Aufrufkette:** von `check_structure()`.
- **Bewertung:** C — toter/leerer Validator (Z.736-742 zwei Return-Pfade liefern dasselbe leere Ergebnis); `$ambiguities` nie befuellt. Irrefuehrend; entweder entfernen oder implementieren.

### `resolve_diagnostic_user(array $input, int $currentuserid, string $lang=''): array` — private
- **Zweck:** Bestimmt den zu diagnostizierenden User: explizite targetuserid (mit Existenz-/deleted-Check) > userquery (via `resolve_single_user`) > aktueller User.
- **Seiteneffekte:** DB-Read `user` (`record_exists`).
- **Aufrufkette:** von `preflight()` und `execute()`; ruft `booking_skill_support::resolve_single_user`.
- **Bewertung:** B — klar, vertretbare Laenge.

### `can_analyze_other_user(int $cmid, int $userid): bool` — private
- **Zweck:** Capability-Check `mod/booking:bookforothers` im Modul-Kontext.
- **Seiteneffekte:** `context_module::instance`, `has_capability`.
- **Aufrufkette:** von `preflight()`/`execute()`.
- **Bewertung:** A — trivial.

### `validate_option_reference(array $input, int $cmid, string $lang=''): array` — private
- **Zweck:** Validiert Option-Referenz (optionid/optionquery/last-option) und liefert errors/ambiguities.
- **Seiteneffekte:** DB-Reads via `booking_skill_support::resolve_single_option`.
- **Aufrufkette:** WIRD NIRGENDS AUFGERUFEN — toter Code (Resolution laeuft komplett ueber `resolve_option_id`).
- **Bewertung:** D — toter Code (~31 LOC, Z.817-847), dupliziert Teile von `resolve_option_id`. Entfernen.

### `resolve_option_id(array $input, int $cmid, int $userid, string $lang=''): array` — private
- **Zweck:** Loest die Ziel-Option auf: explizite optionid (Instance-Zugehoerigkeits-Check, Fallback auf Query bei stale id) > "last option"-Preview-Referenz > optionquery-Suche; liefert ok/ambiguity/error.
- **Seiteneffekte:** DB-Reads `booking_options` (`record_exists`), `get_coursemodule_from_id`; `booking_skill_support::resolve_single_option` / `resolve_last_preview_option_ids_for_user_for_execute`.
- **Aufrufkette:** von `preflight()`/`execute()`.
- **Bewertung:** B — etwas verzweigt aber nachvollziehbar; sinnvolle Fallbacks.

### Triviale Akzessoren
- `__construct()` (Z.49) — setzt readonly=true + Risikoklasse R0 via parent. **A**
- `get_name(): string` (Z.58) — gibt `TASK_NAME` zurueck. **A**
- `get_other_user_permission_error_message(string $lang=''): string` (Z.805) — Einzeiler, lokalisierter Fehlertext. **A**
