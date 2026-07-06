# update_option_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/update_option_skill.php` · **LOC:** 479 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
`update_option_skill` ist die Agent-Skill-Definition fuer `mod_booking.update_option` (Risikoklasse R2, Capability `mod/booking:addeditownoption`). Sie deklariert Schema, Beispiel-Input, Message-Trigger und kontextuelle Prompt-Packs fuer das LLM, validiert Eingaben strukturell (`check_structure`) und tief (`preflight` mit DB-Option-Resolution), und delegiert die eigentliche Mutation an `booking_skill_mutation_execute_service` (`execute`). Hauptkollaborateure: `booking_skill_base` (Basis), `booking_skill_support` (Option-Resolution/Preview), `option_schema_definition`/`option_input_verification` (Feld-Vertraege), `privacy_anonymizer` und `preflight_result_v2`.

## Methoden

### `__construct()` — public
- **Zweck:** Ruft Basis-Konstruktor mit R2-Risikoklasse und Capability-Liste auf.
- **Parameter/Rueckgabe:** keine.
- **Seiteneffekte:** keine (nur Property-Init via parent).
- **Aufrufkette:** Skill-Registry instanziiert; ruft `booking_skill_base::__construct`.
- **Bewertung:** A. Trivial; voll-qualifizierter `skill_risk_class::R2`-Inline-FQN leicht unschoen, aber harmlos.

### `get_name(): string` — public
- **Zweck:** Liefert Task-Namen-Konstante `mod_booking.update_option`.
- **Seiteneffekte:** keine. **Bewertung:** A (trivial).

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut Dedup-Identitaet fuer die Queue: Trennt Ziel (optionid/query/when) von den eigentlichen Aenderungen und normalisiert beides.
- **Parameter:** `$input` Roh-Skill-Input. **Rueckgabe:** `array{task_family,target,changes}`.
- **Seiteneffekte:** keine (rein funktional); nutzt Helfer `normalize_identity_query`/`normalize_identity_value` (Basis).
- **Aufrufkette:** vom Queue-/Dedup-Mechanismus (Interface `queue_identity_provider_interface`).
- **Bewertung:** B. Klar, aber Schluessel-Blacklist als Inline-Array; ~30 LOC, akzeptabel.

### `get_example_input(): array` — public
- **Zweck:** Repraesentatives Beispiel-Input fuer den Construction-Phase-Katalog (inkl. `headerimage_token`), ueberschreibt Basis-Default.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `get_schema(): array` — public
- **Zweck:** Liefert JSON-aehnliches Skill-Schema (Beschreibung, readonly-Flag, Confirm/Taskcall-String-Keys, Beispiel-Utterances, Properties gemerged mit `option_schema_definition::common_properties()`).
- **Rueckgabe:** Schema-Array. **Seiteneffekte:** keine (liest `is_read_only()`).
- **Aufrufkette:** Selection-/Construction-Phase des Agenten.
- **Bewertung:** B. Grosses Literal-Array (~45 LOC), aber reine Deklaration — kein Logik-Smell.

### `get_message_triggers(): array` — public
- **Zweck:** Deklarative Trigger-Liste (Header-Image, Preview-Kontext, exakte/ambige Resolution) fuer Skill-Routing.
- **Seiteneffekte:** keine. **Bewertung:** A (reine Deklaration).

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert 3 kontextuelle Guidance-Packs (mutation_flow, header_image_attachment, multi_option_disambiguation) mit Triggern + Anweisungstexten fuers LLM.
- **Seiteneffekte:** keine. **Bewertung:** B. ~64 LOC reines Daten-Literal; lang, aber inhaltlich kohaerent und ohne Logik.

### `check_structure(array $input): array` — public
- **Zweck:** Pure Strukturvalidierung — verlangt optionid ODER optionquery und delegiert Rest an `validate_common_mutation_structure`.
- **Rueckgabe:** `array{valid,errors}`. **Seiteneffekte:** keine DB (nur `get_string`).
- **Aufrufkette:** ruft Basis-Helfer; gerufen von der Skill-Pipeline vor preflight.
- **Bewertung:** A. Knapp und sauber.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Validierung: cmid-Aufloesung, Capability-Check, Option-Resolution (Anon-Token-Skip, Preview-Referenz, Query-Resolution, explizite optionid-Pruefung gegen Instanz), dann Service-Preflight. Schreibt `optionid`/`optionids` in prepared_input.
- **Parameter:** Input, cmid, userid. **Rueckgabe:** `preflight_result_v2` (ok/invalid).
- **Seiteneffekte:** DB-READS: `$DB->record_exists('booking_options', ...)`, `get_coursemodule_from_id('booking', ...)`; indirekt DB ueber `booking_skill_support::resolve_single_option` / `resolve_last_preview_option_ids_for_user_for_execute`. Keine Writes. Liest `global $DB`.
- **Aufrufkette:** ruft `require_native_capability`, `get_output_language`, mehrere `booking_skill_support`-Statics, `apply_service_preflight`; gerufen von der Skill-Pipeline.
- **Bewertung:** C. ~133 LOC, tief verschachtelte if/else-Kaskade (Resolution-Branches), gemischte Verantwortung (Cap-Check + Resolution + Instanz-Verifikation + Issue-Bau). Issue-Arrays repetitiv. Smell update_option_skill.php:302-435.

### `verify_persisted_option_state(array $input, object $settings): array` — public
- **Zweck:** Post-Mutation-Verifikation; delegiert komplett an `option_input_verification::verify_common_fields`.
- **Seiteneffekte:** liest `$settings` (in-memory). **Bewertung:** A (reiner Delegator).

### `execute(array $preparedinput, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt Mutation via `booking_skill_mutation_execute_service` aus, ergaenzt Debug-Message; Fallback-Fehlerstruktur bei Nicht-Array-Resultat.
- **Rueckgabe:** Ergebnis-Array (status/...). **Seiteneffekte:** DELEGIERT Mutation (DB-Writes auf booking_options u.a. im Service); instanziiert Service neu pro Call.
- **Aufrufkette:** ruft `resolve_cmid_from_context_or_cmid`, `build_task_debug_message`, `localized_string`; gerufen von Executor/Pipeline.
- **Bewertung:** B. Sauberer Delegator; Service-Instanziierung inline (kein DI), aber dem Skill-Muster entsprechend.

## Bewertungs-Zusammenfassung
Die Klasse ist groesstenteils deklarativ (Schema/Trigger/Packs) plus zwei Logik-Methoden. Single-Responsibility ist gut gewahrt (Mutation ausgelagert). Einziger nennenswerter Hotspot ist `preflight` (Laenge + Verschachtelung). Keine Bugs gefunden.
