# get_option_details_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/get_option_details_skill.php` · **LOC:** 704 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Readonly-Agent-Skill (`mod_booking.get_option_details`, Risk-Klasse R0) zum Auslesen von Detailinformationen einer oder mehrerer Buchungsoptionen. Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface`. Loest Optionen aus optionid/optionids/optionquery auf (Modul- und System-Kontext), waehlt Standard- und Custom-Felder selektiv aus und baut eine `observation_full`-JSON-Payload fuer nachgelagerte LLM-Reasoning-Schritte. Kollaborateure: `singleton_service` (Option-Settings), `booking_skill_support` (Query-Resolution, Option-Links), `observation_time` (TZ-Formatierung), `preflight_result_v2`, `$DB` (System-Kontext-Titelsuche).

## Methoden

### `get_schema(): array` — public
- **Zweck:** Liefert das JSON-Schema des Skills (Properties, Beispiel-Utterances, prompt_meta) fuer Planner/Discovery.
- **Parameter/Rueckgabe:** keine / grosses Schema-Array, durchgereicht durch `enrich_schema_with_prompt_meta()`.
- **Seiteneffekte:** keine (deklarativ).
- **Aufrufkette:** vom Agent-Framework (Skill-Registry/Discovery) gerufen; ruft `is_read_only()` + `enrich_schema_with_prompt_meta()` (Basisklasse).
- **Bewertung:** B — lang (~68 LOC), aber rein deklarative Konfiguration ohne Logik.

### `check_structure(array $input): array` — public
- **Zweck:** Validiert Eingabeform; verlangt mindestens optionid ODER optionids ODER optionquery und prueft Array-Typen.
- **Rueckgabe:** `{valid, errors[], ambiguities[]}`.
- **Seiteneffekte:** keine; `localized_string()` (Basisklasse) fuer Fehlertext.
- **Aufrufkette:** von `preflight()`.
- **Bewertung:** A — fokussiert, klar.

### `preflight(array $input, int $contextid, int $userid): preflight_result_v2` — public
- **Zweck:** Readonly-Preflight; validiert Struktur und reicht Input unveraendert durch.
- **Seiteneffekte:** keine (Kontext bewusst NICHT zu cmid aufgeloest).
- **Aufrufkette:** vom Agent-Executor vor `execute()`; ruft `check_structure()`.
- **Bewertung:** B — klare Mapping-Logik (Errors → VALIDATION_ERROR-Issues).

### `execute(array $input, int $contextid, int $userid): array` — public
- **Zweck:** Hauptpfad: loest Ziel-Option-IDs auf, laedt je Option Settings + Info, selektiert Standard-/Custom-Felder, baut Capability-Snapshot, erzeugt usermessage mit Option-Links und observation_full-Payload.
- **Parameter/Rueckgabe:** Input + Kontext / Result-Array (status, detail, usermessage, observation_full, optiondetails[], detail_capabilities, debugmessage).
- **Seiteneffekte:** Reads via `singleton_service::get_instance_of_booking_option_settings()` (booking_option_settings, gecacht) und `$settings->return_booking_option_information()`; indirekt DB-Read ueber `resolve_target_option_ids()`. Keine Writes.
- **Aufrufkette:** vom Agent-Executor; ruft `resolve_cmid_from_context_or_cmid`, `get_output_language`, `normalize_requested_fields`, `normalize_customfield_keys`, `resolve_target_option_ids`, `build_option_capability_snapshot`, `select_standard_fields`, `select_custom_fields`, `booking_skill_support::build_option_link_for_output`, `build_observation_full`, `build_task_debug_message`.
- **Bewertung:** C — ~125 LOC (Z.267-392), gemischte Verantwortung: Input-Normalisierung, Loop mit Aggregation der available_customfields, Label-Bau und drei separate Result-Konstruktionen (zwei Error-Branches + Erfolg) inline. Kandidat zum Aufteilen (z.B. `build_single_option_detail()`, `build_error_result()`). Smell: get_option_details_skill.php:267-392 (Laenge + mehrere Verantwortlichkeiten).

### `build_observation_full(string $detailmessage, array $details, array $detailcapabilities): string` — private
- **Zweck:** Haengt JSON-serialisierte Detail-Payload an die Detailnachricht fuer Reasoning-Schritte.
- **Seiteneffekte:** keine; `json_encode` mit Fallback auf detailmessage bei Fehlschlag.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** A — kurz, robust (Encode-Guard).

### `normalize_requested_fields(array $fields): array` — private
- **Zweck:** Normalisiert angeforderte Standard-Felder (lowercase/trim), expandiert `all_standard`, filtert auf SUPPORTED_STANDARD_FIELDS, dedupliziert.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** A — klar, Whitelist-basiert.

### `normalize_customfield_keys(array $keys): array` — private
- **Zweck:** Trimmt und dedupliziert Custom-Field-Shortnames.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** A — trivial-klar.

### `select_standard_fields(array $info, array $requestedfields, bool $includesessions): array` — private
- **Zweck:** Mappt angeforderte Standard-Felder aus dem booking_option_information-Array, mit TZ-Formatierung fuer Zeitfelder und Sessions-Gate.
- **Seiteneffekte:** keine; `observation_time::format()` fuer canceluntil/coursestarttime/courseendtime.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** B — langer switch (~40 LOC, Z.466-508), aber flach und je Case trivial; akzeptables Mapping.

### `select_custom_fields(object $settings, bool $includecustomfields, array $customfieldkeys): array` — private
- **Zweck:** Liefert verarbeitete Custom-Field-Werte aus `customfieldsfortemplates`, optional gefiltert nach Keys (case-insensitive Lookup-Map).
- **Seiteneffekte:** keine (liest Property des bereits geladenen Settings-Objekts).
- **Aufrufkette:** von `execute()`.
- **Bewertung:** B — zwei Pfade (alle vs. gefiltert) + Lookup-Aufbau, noch gut lesbar.

### `build_option_capability_snapshot(object $settings): array` — private
- **Zweck:** Baut kompakte Capability-Metadaten (supported_standard_fields + verfuegbare Custom-Fields mit key/label/type) fuer Folge-Detailabfragen.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** B — kurz, klar.

### `resolve_target_option_ids(array $input, int $cmid, int $userid, int $maxitems): array` — private
- **Zweck:** Ermittelt Ziel-Option-IDs aus optionid/optionids/optionquery; im Modul-Kontext scoped (last-reference-Preview oder single-option-Resolve), im System-Kontext cross-instance Titelsuche.
- **Seiteneffekte:** Reads indirekt ueber `booking_skill_support::resolve_last_preview_option_ids_for_user_for_execute`/`resolve_single_option` (Modul) bzw. `resolve_option_ids_for_system_context` (DB).
- **Aufrufkette:** von `execute()`; ruft `booking_skill_support::*` und `resolve_option_ids_for_system_context`.
- **Bewertung:** C — ~50 LOC (Z.605-656) mit tiefer Verschachtelung (cmid-if → query-if → last-ref-if → foreach). Mehrere Quellpfade in einer Methode; extrahierbar. Smell: get_option_details_skill.php:622-652 (verschachtelte Verzweigung Modul/System + last-reference).

### `resolve_option_ids_for_system_context(string $query, int $limit): array` — private
- **Zweck:** System-Kontext-Fallback: numerischer String → direkte ID-Existenzpruefung; sonst case-insensitive Titelsuche ueber alle `booking_options`.
- **Seiteneffekte:** DB-Reads: `$DB->record_exists('booking_options')`, `$DB->get_records_sql()` mit `sql_like`/`sql_like_escape` (portabler Limit ueber $limitnum).
- **Aufrufkette:** von `resolve_target_option_ids()`.
- **Bewertung:** B — eigenhaendiger SQL-Bau, aber sauber parametrisiert/escaped und bewusst portabel; gut dokumentiert.

### Triviale Akzessoren
- `__construct()` — ruft `parent::__construct(true, R0)` (readonly, Risk R0).
- `get_name(): string` — gibt `TASK_NAME` zurueck.
- `get_message_triggers(): array` — statische Trigger-Definition.
- `get_contextual_prompt_packs(): array` — statische Guidance-Packs.
Alle vier: deklarativ/trivial, Score A.
