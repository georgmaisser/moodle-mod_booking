# S15 — wizard_ai

## Zweck & Grenzen

Das Subsystem `classes/local/wizard` ist die **mod_booking-seitige Schnittstelle zum
KI-Agenten** (`bookingextension_agent`). Es liefert dem Agenten eine Sammlung von
**Skills** (ausführbare KI-Werkzeuge wie „Buchungsoption anlegen", „Nutzer einbuchen",
„Buchungsproblem diagnostizieren"), zugehörige **Eingabe-Schemata/Validierung**,
**Lookup-/Mutation-Services** und **DTOs**. Es kapselt das gesamte Booking-Domänenwissen,
damit die Engine im fremden Plugin `bookingextension_agent` domänenagnostisch bleibt
(parse → validate → execute).

Die Verbindung zur Engine ist bewusst **lose gekoppelt**:
- mod_booking implementiert Interfaces aus `bookingextension_agent\local\wizard\interfaces\*`
  (z. B. `skill_provider_interface`, `skill_interface`, `skill_input_normalizer_interface`).
- Die Skills erweitern die Engine-Basisklasse `bookingextension_agent\local\wizard\base_skill`
  und liefern `preflight_result_v2`-Objekte zurück.
- Entdeckung erfolgt per Konvention/Duck-Typing (`skill_discovery::get_skill_instances('mod_booking')`,
  duck-typed `booking_readiness_provider`).

**Grenzen:** Dieses Subsystem enthält die Skills + Support/Services. Die eigentliche
Orchestrierung (Planner, Interpreter, Executor, Confirm-Loop, Prompts, Webservices) liegt
außerhalb in `bookingextension_agent`. Mehrere Skills delegieren über die Engine-Brücke
`bookingextension_agent\local\wizard\booking\booking_task_support` bzw. deren `tasks\*`
zurück, sodass „Task orchestriert, Service führt aus" gilt.

## Position im Gesamtsystem

```
bookingextension_agent (Engine)
   │  Interfaces / base_skill / preflight_result_v2 / skill_discovery
   │  booking_task_support, tasks\*, mutation_result_dto, attachment_token_service
   ▼
mod_booking\local\wizard            ← DIESES SUBSYSTEM
   ├─ skill_provider                 (Entrypoint: implementiert skill_provider_interface)
   ├─ options\skills\*               (konkrete Skills, extends booking_skill_base)
   ├─ booking\booking_skill_support  (God-Klasse: Domänen-Resolver/Validierung/Persistenz)
   ├─ booking\booking_skill_mutation_execute_service (zentraler Mutations-Executor)
   ├─ booking\support\*              (Validierung, Rules-Service, Slot-Normalizer)
   ├─ services\{lookup,mutation}\*   (Application-Services, DTO-orientiert)
   └─ dto\*                          (dünne Value Objects)
   ▼
mod_booking Kern (booking_option, singleton_service, bo_info, rules_info, booking_bookit …)
```

Der Agent ruft `skill_provider::get_skills()` → Skills geben Schema/Trigger/Prompt-Packs an die
Engine. Bei Ausführung läuft Skill → `booking_skill_support`/`booking_task_support` →
`booking_skill_mutation_execute_service` → mod_booking-Kern (`booking_option::update`, `booking_bookit`).

## Schlüsselkonzepte

- **Skill**: KI-Werkzeug. Erbt `booking_skill_base` (→ `base_skill`). Liefert `get_name()`,
  `get_schema()`, `check_structure()`, `preflight()`, `execute()`, optional `get_message_triggers()`
  (Trigger-Provider) und `get_contextual_prompt_packs()`.
- **Risk-Class / readonly / native capabilities**: jeder Skill deklariert im Konstruktor seine
  Risikoklasse (`skill_risk_class`), ob er readonly ist und welche nativen Capabilities er braucht
  (Gate-2-Autorisierung in der Engine).
- **Preflight vs. Execute**: Preflight validiert/disambiguiert (Lookups, Permissions, Rückfragen)
  ohne Schreiben; Execute schreibt und verifiziert (Postcondition-Check der persistierten Werte).
- **Resolver-Pattern**: Freitext-Queries (`teacherquery`, `coursequery`, `optionquery`,
  `rulequery`, `templatequery`) → eindeutige IDs, mit Status `ok|error|ambiguity` für die
  Clarification-Schleife.
- **DTO + Service**: `services\mutation\option_mutation_service` + `dto\*` bieten denselben
  Mutationskern für Tasks UND Services (Architektur-Tests vergleichen identische Ergebnisse).
- **Preview**: Skills liefern `get_result_preview()` als Daten; `booking_option_preview_renderer`
  rendert Karten serverseitig.
- **Slot-/Self-Learning-Normalisierung**: `slot_booking_normalizer` kanonisiert LLM-Input vor der
  Validierung, damit der Interpreter domänenfrei bleibt.

## Datenfluss

1. **Discovery**: Engine → `skill_provider::get_skills()` → `skill_discovery::get_skill_instances('mod_booking')`
   sammelt alle `options\skills\*`-Instanzen, sortiert nach Name.
2. **Prompt-Aufbau**: Engine zieht Schema (`get_schema()`), Trigger (`get_message_triggers()`),
   Prompt-Packs (`get_contextual_prompt_packs()`), Prompt-Meta (`booking_skill_base::$promptmeta`).
3. **Normalisierung**: `provider_skill_input_normalizer` → `slot_booking_normalizer` kanonisiert
   `create_option`/`update_option`-Input.
4. **Struktur-/Preflight-Validierung**: Skill → `booking_skill_base` →
   `booking_mutation_validation::validate_common()` + `booking_skill_support`-Resolver/Permission-Checks
   → `preflight_result_v2` (errors / ambiguities / issues / normalized_input).
5. **Execute (Mutation)**: Skill → `booking_skill_mutation_execute_service::execute()` →
   `booking_option::update()` / `booking_bookit` → Postcondition-Verifikation
   (`option_input_verification`) → Observation + Preview-Option-IDs.
6. **Execute (Read/Diagnose)**: Skill liest direkt via `singleton_service`, `bo_info`,
   `booking_answers` etc. und baut eine `observation_full`.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| booking/booking_skill_support.php | booking_skill_support | Service (God) | 2940 | ~85 | E | P0 |
| options/skills/create_option_skill.php | create_option_skill | Skill | 1501 | ~30 | D | P1 |
| booking/booking_skill_mutation_execute_service.php | booking_skill_mutation_execute_service | Service (Executor) | 1449 | 12 | E | P0 |
| options/skills/booking_skill_base.php | booking_skill_base | Skill-Basisklasse | 1224 | ~30 | D | P1 |
| options/skills/diagnose_booking_issue_skill.php | diagnose_booking_issue_skill | Skill | 1140 | ~21 | D | P1 |
| options/skills/diagnose_user_booking_skill.php | diagnose_user_booking_skill | Skill | 1001 | ~28 | D | P1 |
| options/skills/diagnose_cancellation_issue_skill.php | diagnose_cancellation_issue_skill | Skill | 907 | ~19 | D | P2 |
| options/skills/book_users_skill.php | book_users_skill | Skill | 741 | ~17 | C | P2 |
| options/skills/get_option_details_skill.php | get_option_details_skill | Skill | 704 | ~19 | C | P2 |
| booking/support/booking_rules_agent_service.php | booking_rules_agent_service | Service | 650 | 13 | C | P2 |
| options/skills/configure_booking_instance_skill.php | configure_booking_instance_skill | Skill | 625 | ~16 | C | P2 |
| options/skills/option_schema_definition.php | option_schema_definition | Schema-Provider | 530 | 1 | C | P3 |
| options/skills/update_option_skill.php | update_option_skill | Skill | 479 | ~13 | C | P3 |
| options/skills/create_rule_from_template_skill.php | create_rule_from_template_skill | Skill | 462 | ~10 | C | P3 |
| options/skills/bulk_update_options_skill.php | bulk_update_options_skill | Skill | 414 | ~12 | C | P3 |
| booking/support/booking_mutation_validation.php | booking_mutation_validation | Validator | 407 | 1 | D | P2 |
| options/skills/analyze_rules_skill.php | analyze_rules_skill | Skill | 400 | ~7 | B | P3 |
| options/skills/create_slotbooking_option_skill.php | create_slotbooking_option_skill | Skill | 377 | ~12 | B | P3 |
| options/skills/update_option_trainer_skill.php | update_option_trainer_skill | Skill | 374 | ~10 | B | P3 |
| options/skills/search_options_skill.php | search_options_skill | Skill | 373 | ~9 | B | P3 |
| options/skills/option_input_verification.php | option_input_verification | Verifier | 353 | ~5 | B | P3 |
| options/skills/update_rule_from_template_skill.php | update_rule_from_template_skill | Skill | 347 | ~9 | B | P3 |
| options/skills/add_price_category_skill.php | add_price_category_skill | Skill | 284 | ~8 | B | P3 |
| options/skills/list_option_properties_skill.php | list_option_properties_skill | Skill | 283 | ~7 | B | - |
| booking/support/slot_booking_normalizer.php | slot_booking_normalizer | Normalizer | 254 | 8 | B | P3 |
| options/skills/create_selflearning_option_skill.php | create_selflearning_option_skill | Skill | 177 | ~7 | B | - |
| services/mutation/option_mutation_service.php | option_mutation_service | Application-Service | 138 | 7 | A | - |
| skill_provider.php | skill_provider | Entrypoint/Provider | 126 | 6 | A | - |
| services/mutation/entity_mutation_service.php | entity_mutation_service | Application-Service (Stub) | 97 | 3 | B | P3 |
| booking_option_preview_renderer.php | booking_option_preview_renderer | Renderer | 94 | 1 | A | - |
| dto/create_option_input_dto.php | create_option_input_dto | DTO | 82 | 4 | A | - |
| dto/create_entity_input_dto.php | create_entity_input_dto | DTO | 82 | 4 | A | - |
| dto/bulk_update_options_input_dto.php | bulk_update_options_input_dto | DTO | 78 | 4 | A | - |
| dto/update_option_input_dto.php | update_option_input_dto | DTO | 78 | 4 | A | - |
| services/lookup/option_lookup_service.php | option_lookup_service | Application-Service | 75 | 2 | A | - |
| booking/booking_readiness_provider.php | booking_readiness_provider | Provider | 73 | 1 | B | - |
| booking/provider_skill_input_normalizer.php | provider_skill_input_normalizer | Adapter | 50 | 2 | A | - |
| booking/booking_skill_provider.php | booking_skill_provider | Deprecated-Wrapper | 29 | 0 | B | - |

### booking_skill_support (booking/booking_skill_support.php) — E / P0

Zentrale **God-Klasse** des Subsystems. Vereint Skill-Discovery, Schema-Lookup, Domänen-Resolver
(User/Course/Cohort/Competency/Option/Price), Datums-/Preis-/Sichtbarkeits-Normalisierung,
Permission-Validierung, Booking-Ausführung, Thread-Metadaten („letzte Option"/„letzte Preview"),
Lokalisierung und Output-Formatierung. Kollaborateure: `singleton_service`, `bo_info`,
`booking`, `booking_bookit`, `view`, `bookingoptions_wbtable`, `search_courses`, Engine
(`skill_discovery`, `conversation_store`). Persistenz: liest mod_booking-Tabellen, schreibt
Thread-Metadaten über `conversation_store`.

Methoden-Inventar (Auszug, ~85 meist `static`):
- `get_skill_names(): array` (public) — Liste aller Skill-Namen.
- `get_contextual_prompt_packs(): array` (public) — gebündelte Prompt-Packs der Skills.
- `get_skill_schema(string $taskname): array` (public) — Schema eines Skills.
- `check_structure(string $taskname, array $input, int $cmid): array` (public) — Struktur-Validierung.
- `execute(string $taskname, array $input, int $cmid, int $userid): array` (public) — Dispatch in Mutation/Read.
- `validate_update_field_permissions(array, int $contextid): array` (public static) — Feldgruppen-Rechtecheck.
- `parse_datetime(mixed): int|false` (public static) — flexibles Datums-Parsing.
- `normalize_temporal_input/normalize_visibility_input/validate_prices_input` (public static) — Eingabe-Normalisierung.
- `resolve_single_user/_course/_option`, `resolve_*_for_restriction`, `resolve_users_for_booking` (public static) — Freitext→ID-Resolver mit `ok|error|ambiguity`.
- `book_users_for_option(int, array, array): array` (public static) — Einbuchung via `booking_bookit`.
- `find_existing_options_by_exact_title`, `search_option_candidates_for_preview` (public static) — Option-Suche/Dedup.
- `is_last_option_reference/is_last_preview_selection_reference` + `remember_*`/`resolve_last_*` (static) — Thread-Memory für „die letzte Option".
- `validate_customform_elements`, `extract_optiondates`, `merge_existing_optiondates_with_new`, `apply_optiondates_to_update_data` (static) — Customform/Optiondates.
- `*_for_execute(...)` (public static) — Pass-through-Wrapper, die private Helfer für den Execute-Service freigeben (auffälliges Code-Smell: Sichtbarkeits-Duplikate).
- `get_localized_property_label*`, `build_option_link*`, `build_user_link`, `format_user_links` (static) — Output-Formatierung/Lokalisierung.
- Schuld: `booking_skill_support.php:50` God-Klasse 2940 LOC; `:2813-2937` ~12 `*_for_execute`-Wrapper als Sichtbarkeits-Workaround; gemischte Verantwortlichkeiten (Discovery+Resolver+Persistenz+Formatierung); fast nur statische API → schwer testbar/mockbar.

### booking_skill_mutation_execute_service (booking/booking_skill_mutation_execute_service.php) — E / P0

Zentraler **Mutations-Executor** für `create_option`, `update_option`, `update_option_trainer`,
`bulk_update_options`. Baut form-style `$data`, ruft `booking_option::update()`, bucht Nutzer,
appliziert Header-Image-Token (`attachment_token_service`), verifiziert Postconditions und baut
Observations. Kollaborateure: `booking_option`, `booking_skill_support`, `booking_mutation_validation`,
`option_input_verification`, die vier Skill-Klassen, `singleton_service`, `attachment_token_service`,
`context_user`.

Methoden-Inventar:
- `execute(string $taskname, array $input, int $cmid, int $userid, booking_skill_support $support): ?array` (public) — **Mammut-Methode ~700 LOC** (Z. 49–944): Preflight, Daten-Bau, Update, Booking, Verifikation, Observation.
- `persist_and_verify_single_option(...)` (private) — gemeinsamer Persist+Verify-Kern (Bulk≈Update).
- `flatten_changes_envelope(array): array` (private) — `{changes:[{field,value}]}`→flach.
- `build_verification_observation_fields(...)` (private) — deterministische Verifikations-Observation.
- `resolve_option_type_from_input(array): ?int` (private) — Optiontyp-Ableitung.
- `preflight_validate(string, array, int, int): array` (public) — Mutations-Preflight (Z. 1117).
- `is_self_reference_query/is_update_option_style_task` (private) — Query-/Task-Klassifizierung.
- `resolve_teacher_emails_from_ids(array): array` (private).
- `map_postcondition_failures(array, array, int): array` + `postcondition_family_issue_code(string): string` (private) — Postcondition→Issue-Code.
- `apply_headerimage_token_to_data(...)` (private) — gestagtes Draft-Bild in `$data`.
- Schuld: `booking_skill_mutation_execute_service.php:49` `execute()` ~700 LOC (extreme zyklomatische Komplexität); enge Kopplung an `booking_skill_support`-Statics; Header-Image/Booking/Verify-Belange vermischt.

### booking_skill_base (options/skills/booking_skill_base.php) — D / P1

Abstrakte **Basisklasse aller Booking-Skills** (extends `bookingextension_agent\...\base_skill`).
Hält geteilten `booking_skill_support`, Prompt-Meta-Map (`$promptmeta`), Standard-Implementierungen
für Schema-Anreicherung, Legacy-Result-Enrichment, Mutation-Struktur-Validierung, Preview,
Capability-Checks, Instanz-Scoping und Lokalisierung. Kollaborateure: `booking_skill_support`,
`booking_skill_mutation_execute_service`, `booking_mutation_validation`, `preflight_result_v2`,
`booking_option_preview_renderer`, `singleton_service`.

Methoden-Inventar (Auszug):
- `__construct(bool $readonly, string $riskclass, array $nativecapabilities=[])` — Skill-Metadaten.
- `get_required_native_capabilities(): array`, `get_schema(): array`, `get_example_input(): array` (public).
- `enrich_schema_with_prompt_meta(array): array` (protected) — Prompt-Meta in Schema.
- `enrich_legacy_option_result(...)`, `apply_legacy_create_visibility_if_requested(...)`, `build_legacy_option_observation(...)` — Result-Anreicherung.
- `validate_common_mutation_structure(array, bool): array` (protected) — Strukturprüfung für Mutationen.
- `execute(array, int, int): array` (public) — Default-Execute (delegiert an Support/Execute-Service).
- `verify_persisted_option_state(array, object): array` (public) — Postcondition-Hook (überschreibbar).
- `apply_service_preflight(...)`, `resolve_cmid_from_context_or_cmid(int)`, `require_booking_instance_scope(int)`, `build_no_instance_scope_result(int)`, `list_accessible_booking_instances()`, `require_native_capability(string, int, int)` (protected) — Scoping/Autorisierung.
- `build_task_debug_message(...)`, `get_output_language(array)`, `normalize_identity_query/value`, `localized_string(...)`, `enforce_max_chars(...)` (protected) — Utilities.
- `remember_preview_options(array, int, int)`, `get_result_preview(array, int, int): ?array` (public) — Preview.
- Schuld: `booking_skill_base.php:35` Basisklasse mit ~30 Methoden/1224 LOC (zu viele Verantwortlichkeiten für „Base"); statisch geteilter `$sharedsupport` (Z. 37) → versteckter Zustand.

### create_option_skill (options/skills/create_option_skill.php) — D / P1

Skill `mod_booking.create_option`. Großer Input-Normalisierer (Aliase, Optiondates, Slot/Self-Learning
Sanity), Schema/Trigger/Prompt-Packs, Preflight mit typ-spezifischen Pflichtfeldern und
Platzhalter-Erkennung, Execute über den Mutation-Execute-Service. Basisklasse von
`create_slotbooking_option_skill` und `create_selflearning_option_skill`.

Methoden-Inventar (Auszug): `get_name`, `build_queue_business_identity(array)`, `get_schema`,
`get_message_triggers`, `normalize_create_option_input(array, &$aliases)` (static),
`normalize_optiondate_items` (static), `build_create_option_retry_message`,
`build_supported_property_reference`, `check_structure(array)`, `get_unknown_input_property_names`,
`preflight(array,int,int): preflight_result_v2` (Z. 659, ~200 LOC), `validate_type_specific_required_fields`,
`validate_slotbooking_sanity`, `check_placeholder_values`, `verify_persisted_option_state`,
`execute(array,int,int)`, `resolve_cmid_from_context_or_cmid`.
Schuld: `create_option_skill.php:659` `preflight()` ~200 LOC; viele `private static`-Normalisierer
(1501 LOC); Vererbungs-Kopplung der Slot/Self-Learning-Subskills.

### diagnose_booking_issue_skill / diagnose_user_booking_skill / diagnose_cancellation_issue_skill — D / P1–P2

Read-only Diagnose-Skills (R0). Lösen Ziel-Nutzer/Option auf, prüfen Verfügbarkeits-/Cancel-Bedingungen
(`bo_info`), Buchungsstatus, Nachrichten und (bei `diagnose_user_booking`) `tool_certificate`-Daten,
und bauen lange erklärende `build_reason_lines`/`build_observation_full`. Kollaborateure:
`singleton_service`, `bo_info`, `booking_answers`, Certificate-API. Schuld: lange Report-Builder
(`diagnose_booking_issue_skill.php:854` `build_reason_lines`, `:1110` `build_supplementary_context_lines`);
~900–1140 LOC pro Skill, viel duplizierte User-/Option-Resolver-Logik zwischen den drei Skills
(`resolve_diagnostic_user`, `can_analyze_other_user`, `validate_option_reference`, `resolve_option_id`).

### book_users_skill (options/skills/book_users_skill.php) — C / P2

Skill `mod_booking.book_users`: Nutzer in eine Option einbuchen (auch „letzte Option"-Referenz).
Preflight mit Bedingungs-Blockern und Bestätigungs-Issues; Execute bucht via Support. Methoden u. a.
`build_queue_business_identity`, `extract_identity_users`, `preflight` (~160 LOC), `build_preflight_issues`,
`has_confirmation_issue`, `execute` (~145 LOC), `resolve_option_id`, `summarize_condition_descriptions`.
Schuld: `book_users_skill.php:283` `preflight` + `:488` `execute` lang; Bestätigungs-/Blocker-Logik komplex.

### get_option_details_skill (options/skills/get_option_details_skill.php) — C / P2

Read-only Skill `mod_booking.get_option_details`: liefert Standard-/Custom-Felder + Capability-Snapshot
einer/mehrerer Optionen, auch im System-Kontext (`resolve_option_ids_for_system_context`). Methoden u. a.
`select_standard_fields`, `select_custom_fields`, `build_option_capability_snapshot`,
`resolve_target_option_ids`, `build_observation_full`. Schuld: Feld-Selektionslogik umfangreich (704 LOC).

### configure_booking_instance_skill (options/skills/configure_booking_instance_skill.php) — C / P2

Skill `mod_booking.configure_booking_instance`: konfiguriert Instanz-Felder über eine
`CONFIGURABLE_FIELDS`-Whitelist (Z. 53). `changes:[{field,value}]`-Envelope, Typ-Validierung/Cast,
Update via `cm`-Update. Methoden: `execute_list_fields`, `execute_update`, `validate_field_value_type`,
`cast_value`, `format_value_for_summary`, `error_result`. Schuld: Whitelist + Cast-Logik inline,
sonst gut abgegrenzt.

### booking_rules_agent_service (booking/support/booking_rules_agent_service.php) — C / P2

Service für Rule-Skills (`analyze_rules`, `create_rule_from_template`, `update_rule_from_template`).
Nutzt dieselbe Pipeline wie das AJAX-Formular (`rules_info::set_data_for_form` + `save_booking_rule`).
Methoden: `get_module_contextid`, `build_rules_link`, `list_templates`, `resolve_template`
(inkl. Fuzzy-Similarity `score_template_similarity`/`normalize_template_lookup_token`),
`list_rules_for_context`/`list_active_rules_for_context`, `resolve_rule`,
`create_rule_from_template`, `update_rule_from_template`, `normalize_rule_record`,
`apply_handler_defaults_from_record`, `extract_rule_name_from_record`. Schuld: Template-Resolver mit
mehreren Match-Strategien; hartkodierte deutsche Fehlermeldungen (`:101`, `:312` — nicht via `get_string`).

### booking_mutation_validation (booking/support/booking_mutation_validation.php) — D / P2

Eine **statische Methode** `validate_common(array $input, int $cmid, string $taskname): array`
(~370 LOC) mit allen geteilten Mutations-Validierungen (Teacher, Permissions, Preise, Course/Cohort/
Competency/User-Restrictions, Datums-/Optiondates-Checks, Customform). Delegiert massiv an
`booking_skill_support`-Statics. Schuld: `booking_mutation_validation.php:38` Einzelmethode 370 LOC,
sehr hohe zyklomatische Komplexität, schwer testbar in Teilen.

### slot_booking_normalizer (booking/support/slot_booking_normalizer.php) — B / P3

Kanonisiert LLM-Input für `create_option`/`update_option` (Slotbooking + Self-Learning „kein Limit"→999999).
Methoden: `normalize`, `is_slotbooking_input`, `is_selflearning_input`, `collect_text_fields`,
`extract_max_duration_seconds`, `to_unix_timestamp`. Hinweis: `normalize()` prüft auf Tasknamen
`mod_booking.create_option`/`mod_booking.update_option` (Z. 52) — Konsistenz mit `TASK_NAME`-Konstanten beachten.

### option_input_verification (options/skills/option_input_verification.php) — B / P3

Postcondition-Verifikation persistierter Optionswerte. `verify_common_fields` (string-Liste) +
`verify_common_fields_structured` (deterministische Failure-Codes). Genutzt vom Execute-Service.

### option_mutation_service / option_lookup_service / entity_mutation_service (services/) — A–B

DTO-orientierte Application-Services. `option_mutation_service` spiegelt `validate_*`/`create_option`/
`update_option`/`bulk_update_options` über `booking_task_support` und mappt auf `mutation_result_dto`.
`option_lookup_service` kapselt `search_options`/`resolve_single_option`. `entity_mutation_service`
(Stub) prüft Dedup gegen `local_wb_entity`, gibt aber aktuell immer
`'Entity creation service not yet available in this context.'` zurück (`entity_mutation_service.php:67`)
— **toter/unfertiger Schreibpfad**.

### dto/* — A

Vier dünne Value Objects (`from_array`/`to_array`/`get`). `create_option_input_dto` und
`create_entity_input_dto` validieren Pflichtfeld (`text` bzw. `name`); `update_*`/`bulk_*` ohne Validierung.

### skill_provider (skill_provider.php) — A

Entrypoint. Implementiert `skill_provider_interface` + `skill_input_normalizer_provider_interface`.
`get_component()='mod/booking'`, `get_skills()` (Discovery+Sort), `get_contextual_prompt_packs()`,
`get_discovery_diagnostics()`, `get_issue_code_provider(): null`, `get_prompt_guidance(): []`,
`get_skill_input_normalizer()→provider_skill_input_normalizer`.

### booking_option_preview_renderer (booking_option_preview_renderer.php) — A

`render(array $payload, int $contextid, int $userid): string` — rendert Optionen als Karten
(`view`, `MOD_BOOKING_VIEW_PARAM_CARDS`); cmid kommt aus den Option-Settings (nicht aus WS-Kontext).

### booking_readiness_provider (booking/booking_readiness_provider.php) — B

Duck-typed vom Engine-`aiready`-Panel. `get_booking_statistics(int $cmid, int $bookingid): array`
liefert `num_options`/`num_booked`. Schuld: N+1-Schleife über alle Optionen (`:61`) für `num_booked`.

### booking_skill_provider (booking/booking_skill_provider.php) — B

Leerer **Deprecated-Wrapper** `extends booking_skill_support`. Hinweis: erweitert die God-Klasse als
Legacy-Alias; PHPDoc nennt fälschlich `@package bookingextension_agent`.

## Persistenz

- **Gelesen/geschrieben (mod_booking-Kern)**: Buchungsoptionen via `booking_option::update()`,
  Optiondates, Preise, Teachers, Customform — über `booking_skill_mutation_execute_service`/`booking_skill_support`.
- **booking_rules**: `booking_rules_agent_service` liest/schreibt `booking_rules` (über `rules_info`),
  liest `templaterule`-Templates.
- **local_wb_entity**: `entity_mutation_service` liest (Dedup) — `record_exists`.
- **user / cohort / competency / course**: gelesen in Resolvern (`booking_skill_support`, `booking_mutation_validation`).
- **Thread-Metadaten (Engine `conversation_store`)**: „letzte Option" / „letzte Preview-Option-IDs"
  pro User/Thread (`LAST_PREVIEW_OPTION_IDS_METADATA_KEY`).
- **Booking-Einbuchung**: `booking_bookit` (verbraucht Plätze; vgl. Concurrency-Domäne).
- **Zertifikate**: `tool_certificate`-Lesedaten in `diagnose_user_booking_skill`.
- Caches: indirekt `singleton_service` (Option-/Booking-Settings, Answers).

## Extension-Points

- **Implementierte Engine-Interfaces**: `skill_provider_interface`,
  `skill_input_normalizer_provider_interface`, `skill_input_normalizer_interface`,
  `skill_interface` (über `base_skill`), `skill_trigger_provider_interface` (viele Skills),
  optional `*_prompt_contract`-/`message_triggers`-Hooks.
- **Neue Skills**: Klasse unter `options/skills/` ableiten von `booking_skill_base`, `TASK_NAME` setzen,
  `get_name/get_schema/preflight/execute` implementieren → automatisch via `skill_discovery` entdeckt.
- **Duck-Typed Provider**: `booking_readiness_provider::get_booking_statistics()` (Readiness-Panel).
- **Prompt-Anreicherung**: `booking_skill_base::$promptmeta`, `get_contextual_prompt_packs()`,
  `get_message_triggers()`.
- **Postcondition-Hook**: `verify_persisted_option_state()` pro Skill überschreibbar.

## Bekannte Schulden (→ Blueprint)

- **P0 God-Klasse** `booking_skill_support.php` (2940 LOC, ~85 meist statische Methoden): aufspalten in
  Resolver-, Validierungs-, Persistenz-, Formatierungs-Module; die ~12 `*_for_execute`-Sichtbarkeits-Wrapper
  (`:2813–2937`) eliminieren.
- **P0 Mammut-Methode** `booking_skill_mutation_execute_service::execute()` (~700 LOC, `:49`): in
  Pipeline-Schritte (Daten-Bau / Update / Booking / Header-Image / Verifikation / Observation) zerlegen.
- **P1 Diagnose-Duplikation**: `resolve_diagnostic_user`/`can_analyze_other_user`/`validate_option_reference`/
  `resolve_option_id` nahezu identisch in drei Diagnose-Skills → in eine Foundation (diagnostics) ziehen.
- **P1 create_option_skill::preflight()** (~200 LOC) + viele `private static`-Normalisierer → extrahieren.
- **P2 booking_mutation_validation::validate_common()** (370-LOC-Einzelmethode): in Feld-Validator-Objekte zerlegen.
- **P3 Hartkodierte Strings** in `booking_rules_agent_service` (deutsche Meldungen ohne `get_string`).
- **Stub/Unfertig**: `entity_mutation_service::create_entity()` gibt immer „not yet available" zurück (`:67`).
- **Legacy**: `booking_skill_provider` als leerer Deprecated-Wrapper + falsches `@package`.
- **Testbarkeit**: durchgehend hoher Anteil statischer God-Calls (`booking_skill_support::*`) erschwert Mocking.
