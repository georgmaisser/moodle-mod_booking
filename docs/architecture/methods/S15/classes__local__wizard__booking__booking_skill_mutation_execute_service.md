# booking_skill_mutation_execute_service — Methoden-Doku

**Datei:** `classes/local/wizard/booking/booking_skill_mutation_execute_service.php` · **LOC:** 1449 · **Subsystem:** S15 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S15_wizard_booking.md)

## Klassenueberblick
Ausfuehrungs-Service fuer mutierende Booking-KI-Tasks (create_option, update_option, update_option_trainer, bulk_update_options). Nimmt flaches `$input` aus dem Agent-Planner, baut daraus das `$data`-Template fuer `booking_option::update()`, persistiert eine oder viele Optionen und verifiziert deterministisch den real persistierten Zustand. Kollaborateure: `booking_skill_support` (zentraler Helfer fuer Resolver/Parsing/Verifikation), `booking_mutation_validation`, `option_input_verification`, `booking_option`, `singleton_service`, `attachment_token_service` (Header-Bild), sowie die vier `*_skill::TASK_NAME`-Konstanten. Klar dominiert von einer einzigen, ueberlangen `execute()`-Methode.

## Methoden

### `execute(string $taskname, array $input, int $cmid, int $userid, booking_skill_support $support): ?array` — public
- **Zweck:** Haupt-Dispatch & Ausfuehrung aller mutierenden Tasks. Mappt das gesamte flache Input-Feldset (Texte, Slots, Preise, Lehrer, Kurs-/Kohorten-/Kompetenz-/Userprofil-/Customform-Verfuegbarkeitsbedingungen, Buchungsfenster, Sichtbarkeit) auf `$data` und ruft Persist+Verify.
- **Parameter:** Taskname, flaches Input-Array, cmid, userid, Support-Helfer (Param `$support` wird im Rumpf nicht verwendet — Dead Param).
- **Rueckgabe:** `array{status,detail,resultid,...}` oder `null`, wenn Taskname nicht zustaendig.
- **Seiteneffekte:** `require_once lib.php`; liest `booking_options` direkt via `$DB->get_records` (Bulk-No-Match-Kandidaten, Z.699); `get_coursemodule_from_id`; `context_module::instance`; schreibt Optionen via `persist_and_verify_single_option` → `booking_option::update()` (DB-Write `booking_options` + Folge-Tabellen, Events durch Core); bucht Nutzer via `booking_skill_support::book_users_via_bookit_for_execute`; Preview-/Last-Option-State via `booking_skill_support::remember_last_option_for_user_for_execute`; nutzt globals `$CFG,$USER,$DB`.
- **Aufrufkette:** Einstiegspunkt aus der Wizard-/Agent-Skill-Schicht (mutierende Booking-Skills). Ruft fast alle privaten Methoden der Klasse + zahlreiche statische `booking_skill_support`-Methoden.
- **Bewertung:** E — God-Method ~873 LOC (49–921), eine Verantwortung „alles". Tiefe Schachtelung, ~30 nahezu identische `if(!empty(...))`-Feld-Mapping-Bloecke (Verfuegbarkeitsbedingungen Z.327–648 sind struktureller Klon), inline-`$DB`-Query + langer Prompt-String (Z.697–728), inline Closure `$linklist` (Z.772). Gemischte Verantwortung: Mapping + Resolving + Persistenz + Praesentation/Observation-Texte. Klarer Refactoring-Kandidat (Feld-Mapper extrahieren).

### `persist_and_verify_single_option(string $taskname, \stdClass $data, int $optionid, array $input, context_module $context): array` — private
- **Zweck:** Single Source of Truth: klont `$data`, persistiert genau eine Option und verifiziert die angeforderten Felder.
- **Rueckgabe:** `array{optionid:int,warnings:string[]}`.
- **Seiteneffekte:** DB-Write `booking_options` (+Folge) via `booking_option::update()` (laesst Exceptions durch); Verifikation via `booking_skill_support::verify_persisted_option_state_for_skill_for_execute` (Re-Read).
- **Aufrufkette:** Aus `execute()` (Single- und Bulk-Pfad).
- **Bewertung:** A — kompakt, klarer Vertrag, DRY-Kern fuer Single+Bulk.

### `flatten_changes_envelope(array $input): array` — private
- **Zweck:** Defensive Normalisierung: flacht ein `{changes:[{field,value}]}`-Envelope auf Top-Level, ohne bestehende Keys zu ueberschreiben (Thread-206 Silent-No-op-Fix).
- **Rueckgabe:** modifiziertes Input-Array (`changes` entfernt).
- **Seiteneffekte:** keine.
- **Aufrufkette:** Erste Zeile von `execute()`.
- **Bewertung:** A — klein, gut dokumentiert.

### `build_verification_observation_fields(string $taskname, int $optionid, array $input, string $executiondetail = ''): array` — private
- **Zweck:** Baut kompakte, deterministische Post-Mutation-Observation: re-liest frische Settings und fasst nur angeforderte Felder zusammen, plus Verifikations-Direktive an den Planner.
- **Rueckgabe:** `['observation_full' => string]` oder `[]` (best-effort).
- **Seiteneffekte:** `singleton_service::destroy_booking_option_singleton` + `get_instance_of_booking_option_settings` (Cache-Reset/Read); `option_input_verification::summarize_requested_state`; `debugging()` bei Fehler.
- **Aufrufkette:** Aus `execute()` Single-Erfolgspfad.
- **Bewertung:** B — etwas Praesentations-/Prompt-Logik im Service, aber gekapselt und fehlertolerant.

### `resolve_option_type_from_input(array $input): ?int` — private
- **Zweck:** Leitet Optionstyp (default/selflearning/slotbooking) aus Input ab (slot_enabled, optiontype-Synonyme, slot_*-Praefix-Heuristik).
- **Rueckgabe:** `int`-Konstante oder `null`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** `execute()` und `preflight_validate()`.
- **Bewertung:** A — reine Mapping-Funktion.

### `preflight_validate(string $taskname, array $input, int $cmid, int $userid): array` — public
- **Zweck:** Seiteneffektfreie (laut Doc) Vorvalidierung: cm-Existenz, gemeinsame Validierung, courseendtime-Ableitung, Option-Target-Aufloesung (preview/last/single), Lehrer-Resolving aus ids/query.
- **Rueckgabe:** `array{errors,ambiguities,normalized_input}`.
- **Seiteneffekte:** `get_coursemodule_from_id`; ruft Resolver `booking_skill_support::resolve_single_option`/`resolve_last_preview_option_ids_for_user_for_execute` (DB-Reads — Doc behauptet side-effect free, ist es bzgl. Reads nur lesend); global `$USER`.
- **Aufrufkette:** Aus `execute()` (Z.74); ggf. extern durch decision_service.
- **Bewertung:** C — ~95 LOC, mehrfach verschachtelte Resolver-Pfade; Doc-Aussage „side-effect free" leicht irrefuehrend (fuehrt DB-Reads/Resolver aus). Mittlere Komplexitaet, aber noch fokussiert.

### `is_self_reference_query(string $query): bool` — private
- **Zweck:** Erkennt Selbstbezug („me/ich/__current_user__"...) einer Lehrer-Query.
- **Rueckgabe:** bool. **Seiteneffekte:** keine (regex). **Aufrufkette:** `execute()`, `preflight_validate()`.
- **Bewertung:** A — klar, gut testbar.

### `resolve_teacher_emails_from_ids(array $teacherids): array` — private
- **Zweck:** Loest gueltige (nicht geloeschte/suspendierte) Trainer-E-Mails zu User-IDs auf.
- **Rueckgabe:** `string[]`. **Seiteneffekte:** DB-Read `user` via `get_records_list`; global `$DB`. **Aufrufkette:** `execute()`, `preflight_validate()`.
- **Bewertung:** A — sauber, defensiv.

### `is_update_option_style_task(string $taskname): bool` — private
- **Zweck:** Ob Task wie update_option (update/update_trainer) Ziel aufloest. **Rueckgabe:** bool. **Seiteneffekte:** keine.
- **Bewertung:** A.

### `map_postcondition_failures(array $warnings, array $input, int $optionid): array` — private
- **Zweck:** Mappt Verifikations-Warnungen auf strukturierte Postcondition-Failure-Records (feldweise Codes + generischer Rest).
- **Rueckgabe:** `array<int,array{code,message,evidence}>`.
- **Seiteneffekte:** `singleton_service::destroy_..._singleton` + Settings-Read; `option_input_verification::verify_common_fields_structured`. **Aufrufkette:** `execute()` Postcondition-Failure-Pfad.
- **Bewertung:** B — vertretbare Laenge, etwas Mischlogik (Re-Read + Dedupe).

### `postcondition_family_issue_code(string $taskname): string` — private
- **Zweck:** Familien-Issue-Code fuer Postcondition-Fehler. **Rueckgabe:** string-Konstante. **Seiteneffekte:** keine.
- **Bewertung:** A.

### `apply_headerimage_token_to_data(array $input, \stdClass &$data, int $userid, int $contextid): void` — private
- **Zweck:** Loest `headerimage_token` auf und legt eine User-Draft-Area an, sodass `booking_option::update()` das Bild ueber die Moodle File-API persistiert (mit `source`-Workaround als serialisiertes stdClass).
- **Seiteneffekte:** `attachment_token_service::resolve/invalidate`; `file_get_unused_draft_itemid`, `get_file_storage()->create_file_from_pathname` (File-API-Write in Draft-Area unter `$USER`); liest tmp-Datei; mutiert `$data` per Referenz; `debugging()` bei Fehler; global `$USER`.
- **Aufrufkette:** `execute()` Z.152.
- **Bewertung:** B — komplexer File-API-Tanz, aber gut dokumentiert und gekapselt; `$contextid` nur fuer Symmetrie (ungenutzt).

### Triviale Akzessoren
Keine reinen Getter/Setter/Konstruktoren in dieser Klasse (kein Konstruktor; alle Methoden tragen Logik).
