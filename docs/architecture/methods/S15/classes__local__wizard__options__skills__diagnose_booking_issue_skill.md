# diagnose_booking_issue_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/diagnose_booking_issue_skill.php` · **LOC:** 1140 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_agent_skills.md)

## Klassenueberblick
Read-only Agent-Skill (Risk-Class R0) fuer die Selbst-/Fremddiagnose von Buchungsproblemen ("Warum bin ich nicht gebucht / kann nicht buchen / bekam keine Mail?"). Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface`. Liefert das LLM-Schema, Trigger und Prompt-Packs, validiert/aufloest Option- und User-Referenzen (via `booking_skill_support`, `singleton_service`, `bo_info`) und baut strukturierte, lokalisierte Reason-/Kontext-Zeilen, die die Agent-Schicht narrativ ausgibt. Kollaborateure: `booking_skill_support`, `singleton_service`, `bo_info`, `booking_answers`, Moodle-Core (`is_enrolled`, `get_fast_modinfo`, `has_capability`).

## Methoden

### `__construct()` — public
- **Zweck:** Ruft `parent::__construct(true, skill_risk_class::R0)` — markiert Skill als read-only, Risk-Class R0.
- **Seiteneffekte/Aufrufkette:** keine; Instanziierung durch Skill-Registry. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Liefert `self::TASK_NAME` (`mod_booking.diagnose_booking_issue`). **Bewertung:** A (trivial).

### `get_schema(): array` — public
- **Zweck:** Liefert das LLM-Tool-Schema (Beschreibung, Beispielutterances, Property-Definitionen question/optionquery/optionid/userquery/targetuserid/issue/outputlang).
- **Rueckgabe:** statisches Array; ruft `$this->is_read_only()`. **Seiteneffekte:** keine.
- **Bewertung:** B — reines Daten-Literal, aber ~60 LOC Prompt-Text inline (Wartungslast, vermischt Prompt-Engineering mit Code).

### `get_message_triggers(): array` — public
- **Zweck:** Liefert task-spezifische Message-Trigger (id/description/examples) fuer das Discovery-Matching. Statisches Array, keine Seiteneffekte. **Bewertung:** A.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert Guidance-Pack (Trigger-Phrasen + Guidance-Bullets) das den Planner anweist, den Skill direkt als read-only task_call auszufuehren. Statisches Array. **Bewertung:** B — viel inline Prompt-Text/Trigger-Duplikate (`cannot book` doppelt Z.160/163), Wartungslast.

### `check_structure(array $input): array` — public
- **Zweck:** Reine Strukturvalidierung ohne DB. Hard-Fail nur wenn weder question noch optionref noch issue vorhanden.
- **Rueckgabe:** `{valid:bool, errors:[]}`; ruft `get_string`. **Seiteneffekte:** keine. **Aufrufkette:** Engine-Preflight-Stufe. **Bewertung:** A.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Validierung: Scope-Guard, question-Pflicht, Option-Referenz-Aufloesung (ueber `booking_skill_support::resolve_single_option`), schreibt resolved `optionid` in prepared_input.
- **Parameter/Rueckgabe:** input/cmid/userid → `preflight_result_v2::ok|invalid`.
- **Seiteneffekte:** DB-Reads indirekt via `resolve_single_option`; `resolve_cmid_from_context_or_cmid`, `require_booking_instance_scope`, `get_output_language`, `localized_string`.
- **Aufrufkette:** Engine vor execute(). **Bewertung:** C — ~60 LOC, tief verschachtelte if/else-Statuspruefung (Z.253-277), wiederholt das `($resolved['status'] ?? '')`-Pattern; ueberlappt logisch mit `validate_option_reference` (Duplikat). Smell: diagnose_booking_issue_skill.php:253.

### `execute(array $preparedinput, int $cmid, int $userid): array` — public
- **Zweck:** Hauptmethode. Loest User + Option auf, prueft cross-user-Permission, laedt Option-Settings/Availability-Conditions/Booking-Answers, Enrollment, Sichtbarkeit, Instanz-Restriktionen (disablebooking/maxperuser/banusernames), Activity-Visibility via modinfo, baut Consistency-Payload, Reason-Lines, Supplementary-Lines und finale strukturierte Diagnose-Antwort.
- **Seiteneffekte:** Viele DB-Reads (`booking_options.text`, `booking_answers` count, `user` username); `singleton_service::get_instance_of_booking_option_settings/_booking_answers/_booking_settings_by_cmid`; `bo_info::get_condition_results`; Core `get_coursemodule_from_id`, `context_course::instance`, `is_enrolled`, `get_fast_modinfo`; `globals $DB`. Kein Write.
- **Aufrufkette:** Engine-Executor; ruft fast alle privaten Helfer dieser Klasse.
- **Bewertung:** E — ~200 LOC, gemischte Verantwortung (User-Resolve, Permission, DB-Loads, Instanz-Checks, Modinfo, Payload-Bau, Response-Assembly) in einer Methode; viele direkte `$DB`/God-Calls; Step-5x-Bloecke (Z.359-424) gehoeren in einen `instance_context`-Builder. Smell: diagnose_booking_issue_skill.php:294, :377-396 (Banlist-Inline-DB), :404 (modinfo-Inline).

### `build_consistency_payload(array $input, int $resolveduserid, int $resolvedoptionid, string $resolvedoptionname): array` — private
- **Zweck:** Vergleicht angeforderte (LLM-gelieferte) vs. aufgeloeste IDs/Labels, erkennt User-/Option-Mismatch (insb. anonymisierte Identifier).
- **Seiteneffekte:** ruft `resolve_user_label` (DB), `looks_like_anonymized_identifier`, `core_text::strtolower`.
- **Bewertung:** C — ~58 LOC, zwei nahezu identische Mismatch-Bloecke (Z.532-549) als Duplikat-Kandidat; sonst gut lesbar. Smell: diagnose_booking_issue_skill.php:532.

### `resolve_user_label(int $userid): string` — private
- **Zweck:** Liefert anzeigbares User-Label (fullname → username → email).
- **Seiteneffekte:** DB-Read `user` (global $DB); ruft Core `fullname`. **Bewertung:** A.

### `looks_like_anonymized_identifier(string $query): bool` — private
- **Zweck:** Erkennt `anon_user_<n>`-Tokens via Regex. Rein, keine Seiteneffekte. **Bewertung:** A.

### `resolve_issue_type(array $input): string` — private
- **Zweck:** Bestimmt Issue-Typ (booking_status|cannot_book|missing_email) aus explizitem `issue`-Feld oder per Keyword-Matching im question-Text.
- **Seiteneffekte:** keine. **Bewertung:** C — ~40 LOC, drei aufeinanderfolgende Token-Listen-Loops (Duplikat-Muster), heuristisches Keyword-Matching schwer testbar/wartbar; default-Fallthrough auf `booking_status`. Smell: diagnose_booking_issue_skill.php:633.

### `resolve_diagnostic_user(array $input, int $currentuserid, string $lang = ''): array` — private
- **Zweck:** Loest Zieluser auf: targetuserid > userquery (via `booking_skill_support::resolve_single_user`) > aktueller User.
- **Seiteneffekte:** DB-Read `user` record_exists (global $DB); `localized_string`.
- **Bewertung:** B — ~45 LOC, klar; mehrfaches `($resolved['status'] ?? '')`-Pattern, aber vertretbar.

### `can_analyze_other_user(int $cmid): bool` — private
- **Zweck:** Prueft `mod/booking:bookforothers` im Modul-Kontext.
- **Seiteneffekte:** `context_module::instance`, `has_capability`. **Bewertung:** A.

### `get_other_user_permission_error_message(string $lang = ''): string` — private
- **Zweck:** Lokalisierte Permission-Denied-Meldung. Ruft `localized_string`. **Bewertung:** A (trivialer Wrapper).

### `validate_option_reference(array $input, int $cmid, string $lang = ''): array` — private
- **Zweck:** Prueft, ob genug Option-Info vorhanden (errors/ambiguities), via `resolve_single_option`.
- **Seiteneffekte:** DB-Read via `booking_skill_support`; `localized_string`.
- **Bewertung:** D — toter/redundanter Code: Logik ueberlappt nahezu vollstaendig mit `preflight()`-Option-Zweig; im Datei-Code kein interner Aufrufer erkennbar (potenziell ungenutzt). Smell: diagnose_booking_issue_skill.php:751.

### `resolve_option_id(array $input, int $cmid, int $userid, string $lang = ''): array` — private
- **Zweck:** Loest Ziel-Option auf: explizite optionid (mit Instanz-Check, Fallback auf Query) > "last option"-Preview-Referenz > optionquery-Suche.
- **Seiteneffekte:** DB-Read `booking_options` record_exists, `get_coursemodule_from_id`; `booking_skill_support::resolve_single_option/is_last_option_reference/resolve_last_preview_option_ids_for_user_for_execute`.
- **Aufrufkette:** von execute() Step 4. **Bewertung:** C — ~48 LOC, mehrere verschachtelte Status-Return-Pfade, count()-Verzweigung; teilweise Ueberlappung mit preflight. Smell: diagnose_booking_issue_skill.php:792.

### `build_reason_lines(string $issuetype, array $optionstats, array $conditionresults, $settings, bool $isselfdiagnosis, string $lang, array $instancecontext): array` — private
- **Zweck:** Baut die zentrale Liste konkreter, lokalisierter Diagnose-Gruende — Sichtbarkeit, instance-disablebooking, maxperuser, banned, Status-Zweig, cannot_book-Kapazitaet/Availability-Conditions, missing_email-Hinweise; dedupliziert am Ende.
- **Seiteneffekte:** instanziiert Availability-Condition-Klassen dynamisch (`$classname::instance()` / `new $classname()`), ruft `is_shown_in_mform`/`get_description_string`; viele `localized_string`-Calls.
- **Aufrufkette:** von execute() Step 7. **Bewertung:** E — ~245 LOC, hohe zyklomatische Komplexitaet, gemischte Issue-Typ-Branches, self/other-String-Auswahl ueberall dupliziert (jeweils ternary), dynamische Klassen-Instanziierung mit doppeltem try/catch (Z.1030-1038), tiefe Schachtelung im cannot_book-Block (Z.1008-1061). Aufspaltung pro Issue-Typ noetig. Smell: diagnose_booking_issue_skill.php:854, :1026 (Condition-Reflection-Loop).

### `build_supplementary_context_lines(bool $isselfdiagnosis, string $lang, array $instancecontext): array` — private
- **Zweck:** Baut nicht-entscheidende Kontext-Zeilen (derzeit nur Enrollment-Hinweis). Dedupliziert.
- **Seiteneffekte:** `localized_string`. **Bewertung:** A.

### Triviale Akzessoren
`get_name`, konstanter `get_message_triggers`, `get_other_user_permission_error_message` (Wrapper) — siehe oben, gebuendelt als trivial bewertet.
