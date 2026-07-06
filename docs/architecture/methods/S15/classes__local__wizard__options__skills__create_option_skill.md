# create_option_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/create_option_skill.php` · **LOC:** 1501 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`create_option_skill` ist die Agent-Skill-Definition fuer `mod_booking.create_option`. Sie liefert Schema, Trigger, Guidance-Packs und Identity fuer die Queue-Dedup, normalisiert LLM-Eingaben auf kanonische Keys, fuehrt eine reine Strukturpruefung (`check_structure`) und eine DB-gestuetzte Tiefpruefung (`preflight`, inkl. Duplikat-Titel/Signatur, Slot-Sanity, Platzhalter, Service-Validierung) durch und delegiert die eigentliche Persistenz an `booking_skill_mutation_execute_service`. Kollaborateure: `booking_skill_support`, `booking_mutation_validation`, `option_schema_definition`, `option_input_verification`, `preflight_result_v2`, Basisklasse `booking_skill_base`. Dient als Basis fuer die spezialisierten Subklassen (slotbooking/selflearning), die viele Methoden ueber `static::TASK_NAME`-Verzweigungen wiederverwenden — was die Datei deutlich aufblaeht.

## Methoden

### `__construct()` — public
- **Zweck:** Konfiguriert Basisklasse mit Risk-Class R2 und Capability `mod/booking:addoption`.
- **Seiteneffekte:** keine. **Bewertung:** A (trivial).

### `get_name(): string` — public
- **Zweck/Rueckgabe:** Liefert `self::TASK_NAME`. **Bewertung:** A.

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut Dedup-Identity aus Titel + normalisiertem Start/Endzeitpunkt (Fallback auf erstes optiondates-Item).
- **Rueckgabe:** assoc Array (task/text/coursestarttime/courseendtime).
- **Seiteneffekte:** keine DB; ruft `normalize_create_option_input`, `strip_create_targeting_fields`, `booking_skill_support::normalize_identity_datetime`.
- **Aufrufkette:** vom Queue-Identity-Provider-Mechanismus (Interface). **Bewertung:** B (klar, leicht verschachtelt).

### `get_schema(): array` — public
- **Zweck:** Liefert Task-Schema; merged common_properties, entfernt Visibility-Felder, beschneidet bei General-create auf Whitelist von ~14 Feldern, enriched via `enrich_schema_with_prompt_meta`.
- **Seiteneffekte:** keine. **Aufrufkette:** Engine-Schema-Discovery; intern von `get_supported_property_names`, `build_supported_property_reference`.
- **Bewertung:** B — lange Inline-Description-Strings, aber gut kommentiert; ~65 LOC.

### `get_message_triggers(): array` — public
- **Zweck:** Liefert statische Trigger-Beschreibungen fuer Skill-Routing. **Seiteneffekte:** keine. **Bewertung:** B (reine Datenliste, lang).

### `normalize_create_option_input(array $input, array &$appliedaliases = []): array` — private static
- **Zweck:** Mappt zahlreiche LLM-Alias-Keys (title/name/limit/teacher/...) auf kanonische Keys; Trim-Key-Normalisierung, Fuzzy-Teacher-Heuristik (Regex), Alias-Cleanup, optiondates-Normalisierung.
- **Parameter:** `$appliedaliases` by-ref protokolliert angewandte Mappings. **Rueckgabe:** normalisiertes Input-Array.
- **Seiteneffekte:** keine DB; ruft `is_placeholder_value`, `normalize_optiondate_items`.
- **Aufrufkette:** zentral von build_queue_business_identity, check_structure, preflight, build_unknown_property_observation.
- **Bewertung:** D — ~95 LOC, mehrere ineinandergeschachtelte foreach-Schleifen mit Regex-Heuristik, gemischte Verantwortung (Key-Trim + Alias-Map + Fuzzy-Teacher + optiondates), schwer testbar. Smell: `create_option_skill.php:217`.

### `normalize_optiondate_items(array $optiondates): array` — private static
- **Zweck:** Normalisiert Alias-Keys innerhalb jedes optiondates-Items (start/end/date+start_time-Komposition), filtert auf kanonische Felder.
- **Seiteneffekte:** keine. **Aufrufkette:** von normalize_create_option_input.
- **Bewertung:** C — ~56 LOC, viele wiederholte array_key_exists-Bloecke (Duplikation zu normalize_create_option_input-Alias-Logik). Smell: `create_option_skill.php:320`.

### `build_create_option_retry_message(array $appliedaliases, array $unknownprops, array $missingprops, bool $includeenlabelkeymap): string` — private
- **Zweck:** Baut kompakte Retry-Anweisung (kanonische Keys) fuer fehlgeschlagene Validierung; verzweigt nach `static::TASK_NAME`.
- **Seiteneffekte:** keine; ruft `get_supported_property_names`. **Aufrufkette:** von check_structure, preflight, build_unknown_property_observation.
- **Bewertung:** C — ~52 LOC, viele bedingte String-Aggregationen, Skill-spezifische slotbooking-Sonderzweige in der Basisklasse (mixed concern). Smell: `create_option_skill.php:387`.

### `build_supported_property_reference(bool $withdescriptions, bool $labelstokey): string` — private
- **Zweck:** Baut Key->Label/Description-Referenz aus Schema + lokalisierten Labels.
- **Seiteneffekte:** keine DB; ruft get_schema, `booking_skill_support::get_localized_property_label_for_output_in_language`.
- **Aufrufkette:** in dieser Datei nicht referenziert (vermutlich von Sub/Basisklasse genutzt).
- **Bewertung:** B — sauber, ~40 LOC.

### `check_structure(array $input): array` — public
- **Zweck:** Reine Strukturpruefung (kein DB): normalisiert, strippt Targeting/Type-Noise, prueft Pflicht-Titel, common-mutation-structure, unbekannte Properties.
- **Rueckgabe:** `{valid, errors[], observation_full?}`.
- **Seiteneffekte:** keine DB; ruft normalize_create_option_input, strip_create_targeting_fields, resolve_requested_option_type, get_unknown_input_property_names, build_*_observation/retry, validate_common_mutation_structure (Basis).
- **Aufrufkette:** Engine-Preflight-Stufe. **Bewertung:** C — ~60 LOC, mehrere `static::TASK_NAME`-Sonderzweige (slot/normal) in Basisklasse, `$resolvedtype` berechnet aber teils ungenutzt. Smell: `create_option_skill.php:497`.

### `get_unknown_input_property_names(array $input): array` — private
- **Zweck:** Liefert Input-Keys ausserhalb des Schemas. **Seiteneffekte:** keine; ruft get_supported_property_names. **Bewertung:** A.

### `get_supported_property_names(): array` — private
- **Zweck:** Liefert sortierte Schema-Property-Namen. **Seiteneffekte:** keine (get_schema neu gebaut). **Bewertung:** B (get_schema-Recompute pro Aufruf, minor).

### `build_unknown_property_observation(array $unknownprops, array $input, array $missingprops = []): string` — private
- **Zweck:** Baut lange Observation fuer Schema-Mismatch-Retries.
- **Seiteneffekte:** keine; ruft normalize_create_option_input (nur fuer appliedaliases), build_create_option_retry_message.
- **Bewertung:** B — `$normalized` wird berechnet und sofort `unset` (nur Seiteneffekt auf $appliedaliases), leicht unsauber.

### `build_missing_fields_preflight_hint(array $missingrequired, array $confirmablewithoutfields = []): string` — private
- **Zweck:** Baut Preflight-Hinweistext fuer fehlende/bestaetigbare Felder.
- **Seiteneffekte:** keine. **Aufrufkette:** in dieser Datei nicht referenziert (potenziell tot bzw. nur Sub-Nutzung). **Bewertung:** B.

### `preflight(array $input, int $contextid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefpruefung ohne Persistenz: Capability-Gate, Normalisierung, Pflicht-Titel, Duplikat-Titel (single/multiple), Duplikat-Signatur (DB), Slot-Sanity, Platzhalter-Confirm, Location-Soft-Confirm, Service-Preflight; liefert ok/confirmable/invalid.
- **Seiteneffekte:** **DB-Read** `get_coursemodule_from_id` (booking cm) und `$DB->get_record('booking_options', ...)` fuer Signatur-Dedup; ruft `require_native_capability`, `booking_skill_support::find_existing_options_by_exact_title`, `booking_mutation_validation::validate_common`. Nutzt `global $DB`.
- **Aufrufkette:** Engine-Preflight nach check_structure. **Bewertung:** D — ~191 LOC, sehr lang, viele Verantwortlichkeiten (Cap, Dedup×2, Slot, Platzhalter, Location, Service), inline SQL-Dedup-Block mit `global $DB`, hartkodierte englische Messages (Zeilen 757-760) statt localized_string. Smell: `create_option_skill.php:659` (Laenge + gemischte Verantwortung + roher DB-Call + hardcoded Strings).

### `build_issue(string $code, string $question, array $remedies = []): array` — private static
- **Zweck:** Baut needs_confirmation-Issue-Payload. **Seiteneffekte:** keine. **Aufrufkette:** von validate_slotbooking_sanity. **Bewertung:** A.

### `has_any_key(array $input, array $keys): bool` — private static
- **Zweck:** true wenn einer der Keys existiert. **Aufrufkette:** von validate_type_specific_required_fields. **Bewertung:** A.

### `resolve_requested_option_type(array $input): string` — private static
- **Zweck:** Leitet Optionstyp (normal/selflearning/slotbooking/unknown) schema-getrieben aus normalisiertem Input ab.
- **Seiteneffekte:** keine. **Aufrufkette:** check_structure, preflight. **Bewertung:** B — klar, ~32 LOC.

### `validate_type_specific_required_fields(array $input, string $resolvedtype, array $overrides = []): array` — private static
- **Zweck:** Sammelt typ-abhaengige Pflichtfeld-Fehler (normal/selflearning/slotbooking).
- **Seiteneffekte:** viele `get_string(...)`-Calls. **Aufrufkette:** in dieser Datei NICHT aufgerufen — preflight kommentiert bewusst „no required-field preflight beyond title" (Zeile 772). Wirkt als toter/Sub-only Code in der Basisklasse.
- **Bewertung:** D — ~101 LOC, drei grosse typ-Bloecke, und im aktuellen Fluss ungenutzt (Dead-Code-Verdacht). Smell: `create_option_skill.php:934`.

### `validate_slotbooking_sanity(array $input): array` — private static
- **Zweck:** Weiche Sanity-Pruefung Slotdauer vs. Tagesfenster (1-Slot-Warnung). **Seiteneffekte:** keine; ruft parse_time_hhmm_to_minutes, build_issue. **Bewertung:** B.

### `parse_time_hhmm_to_minutes(string $hhmm): int` — private static
- **Zweck:** HH:MM -> Minuten seit Mitternacht (0 bei Parse-Fehler). **Bewertung:** A.

### `check_placeholder_values(array $input, array $overrides, string $resolvedtype = 'normal', string $lang = ''): array` — private static
- **Zweck:** Prueft Feldpaare/Einzelfelder auf Platzhalterwerte (0/''/null) und fordert Override-Bestaetigung; baut lokalisierte Fehlermeldungen.
- **Seiteneffekte:** `get_string_manager()->get_string(...)`. **Aufrufkette:** von preflight.
- **Bewertung:** C — ~100 LOC, tiefe Schachtelung (foreach/if bis 4 Ebenen), gemischte String/Array-Pfad-Logik. Smell: `create_option_skill.php:1099`.

### `is_placeholder_value($value): bool` — private static
- **Zweck:** true bei 0/'0'/''/null/leerem Array. **Bewertung:** A.

### `normalize_identity_string(string $value): string` — private static
- **Zweck:** Trim + Whitespace-Collapse + lowercase fuer Identity-Hashing. **Bewertung:** A. (Anmerkung: defekter/leerer Docblock bei Zeile 1228-1231 davor.)

### `is_non_empty_value(mixed $value): bool` — private
- **Zweck:** true wenn nicht-Platzhalter und getrimmt nicht leer. **Aufrufkette:** in dieser Datei nicht referenziert (Sub-only/tot?). **Bewertung:** B.

### `normalize_overrides(array $overrides): array` — private static
- **Zweck:** Override-Tokens lowercase/trim/dedupe. **Aufrufkette:** preflight. **Bewertung:** A.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert grossen statischen Guidance-Pack-Katalog (7 Packs mit Triggern + Guidance-Zeilen) fuer den Prompt.
- **Seiteneffekte:** keine. **Bewertung:** C — ~115 LOC reine Datenliteral-Methode; vertretbar als Konfig, blaeht aber Klasse stark auf. Smell: `create_option_skill.php:1273` (Laenge).

### `verify_persisted_option_state(array $input, object $settings): array` — public
- **Zweck:** Delegiert Post-Mutation-Feldverifikation an `option_input_verification::verify_common_fields`. **Seiteneffekte:** indirekt DB-Read in Delegat. **Bewertung:** A.

### `execute(array $preparedinput, int $contextid, int $userid): array` — public
- **Zweck:** Strippt Targeting/Slot/Selflearning-Felder, setzt optiontype=normal, delegiert an `booking_skill_mutation_execute_service::execute`, baut Debug-Message.
- **Seiteneffekte:** **schreibt** (ueber Service: Option-Erstellung); ruft resolve_cmid_from_context_or_cmid, build_task_debug_message, localized_string.
- **Aufrufkette:** Engine-Execute-Stufe nach erfolgreichem preflight. **Bewertung:** B — ~30 LOC, klar; Skill-spezifisches Feld-Stripping inline.

### `strip_create_targeting_fields(array $input): array` — private static
- **Zweck:** Entfernt update/bulk-Targeting-Keys (optionquery/optionid/...) und Framework-Adressierungs-Keys. **Bewertung:** A.

### `normalize_signature_timestamp($value): int` — private static
- **Zweck:** Normalisiert int/numerischen String -> positiver int (sonst 0) fuer Signatur-Dedup. **Bewertung:** A.

### `resolve_cmid_from_context_or_cmid(int $contextidorcmid): int` — protected
- **Zweck:** Akzeptiert legacy cmid und neue contextid; aufloest via `get_coursemodule_from_id` bzw. `context::instance_by_id`.
- **Seiteneffekte:** **DB-Read** (Coursemodule/Context-Lookup). **Aufrufkette:** preflight, execute. **Bewertung:** B.

## Anmerkungen
- Tote/ungenutzte Methoden in dieser konkreten Klasse (nur via Subklassen oder gar nicht genutzt): `validate_type_specific_required_fields` (preflight verzichtet bewusst darauf), `is_non_empty_value`, `build_missing_fields_preflight_hint`, `build_supported_property_reference`.
- Defekter Docblock bei Zeile 1228-1231 (verwaister `/**` ohne Inhalt vor `is_non_empty_value`).
