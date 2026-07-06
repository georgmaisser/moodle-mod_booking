# update_option_trainer_skill — Methoden-Doku

**Datei:** `classes/local/wizard/options/skills/update_option_trainer_skill.php` · **LOC:** 374 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Dedizierter Agent-Skill `mod_booking.update_option_trainer`, der ausschliesslich die Trainer-/Lehrer-Zuweisung einer bestehenden Buchungsoption setzt oder ersetzt (keine sonstigen Optionsfelder). Erbt von `booking_skill_base` und implementiert `queue_identity_provider_interface` (Dedup-Identitaet fuer Job-Queue) sowie `skill_trigger_provider_interface` (NL-Trigger). Delegiert die eigentliche Mutation an `booking_skill_mutation_execute_service` und nutzt `booking_skill_support` zur Optionsaufloesung sowie `privacy_anonymizer` fuer Anon-Token-Erkennung. Klar fokussierte Single-Responsibility-Klasse; Hauptkomplexitaet liegt in `preflight`.

## Methoden

### `__construct()` — public
- **Zweck:** Initialisiert Basisklasse mit Risikoklasse R2, nicht-readonly, Capability `mod/booking:addeditownoption`.
- **Parameter/Rueckgabe:** keine / —.
- **Seiteneffekte:** keine (nur `parent::__construct`).
- **Aufrufkette:** Instanziierung durch Skill-Registry/Engine.
- **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Liefert Task-Name-Konstante `mod_booking.update_option_trainer`.
- **Rueckgabe:** string. **Seiteneffekte:** keine.
- **Bewertung:** A (trivial).

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut normalisierte Geschaeftsidentitaet (Ziel-Option + Trainer-Selektor) zur Deduplizierung gleichartiger Queue-Jobs.
- **Parameter:** `$input` Roh-Eingabe. **Rueckgabe:** strukturiertes Array (`task_family`, `target`, `trainer_selector`).
- **Seiteneffekte:** keine (rein, normalisiert via `normalize_identity_query` aus Basisklasse; lowercase/trim/intval-Filter).
- **Aufrufkette:** Queue-Dedup-Schicht (Interface `queue_identity_provider_interface`).
- **Bewertung:** A — saubere reine Normalisierung.

### `get_schema(): array` — public
- **Zweck:** Liefert JSON-aehnliches Parameter-/Beschreibungs-Schema des Skills fuer Planner/Prompt.
- **Rueckgabe:** Array (version, description, properties, prompt_meta). **Seiteneffekte:** keine.
- **Aufrufkette:** Planner/Selection-Phase, Schema-Export.
- **Bewertung:** B — langes statisches Array-Literal (~50 Zeilen), aber rein deklarativ und gut lesbar.

### `get_message_triggers(): array` — public
- **Zweck:** Liefert NL-Trigger-Definition (Keywords + Beispielsaetze) fuer Embedding/Discovery.
- **Rueckgabe:** Array. **Seiteneffekte:** keine.
- **Aufrufkette:** Skill-Discovery/Embedding-Aufbau (Interface `skill_trigger_provider_interface`).
- **Bewertung:** A (deklarativ).

### `check_structure(array $input): array` — public
- **Zweck:** Reine Struktur-Validierung ohne DB: erzwingt Ziel (optionid/optionquery), mind. einen Trainer-Selektor, Array-Typ bei teacherids, Whitelist erlaubter Keys.
- **Parameter:** `$input`. **Rueckgabe:** `{valid:bool, errors:string[]}`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Engine vor preflight.
- **Bewertung:** B — Hardcodierte englische Fehlertexte (Z.193, 197, 212) neben einem `get_string`-Aufruf (Z.188); Inkonsistenz bei Lokalisierung, aber Logik klar. Allowedkeys-Liste dupliziert `filter_prepared_input`.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Validierung: Capability-Check, cmid-Aufloesung, Anon-Token-Shortcut, Options-Aufloesung per Query oder Existenzpruefung per ID, dann Delegation an Service-Preflight.
- **Parameter:** `$input`, `$cmid`, `$userid`. **Rueckgabe:** `preflight_result_v2`.
- **Seiteneffekte:** DB-Reads: `get_coursemodule_from_id('booking', …)`, `$DB->record_exists('booking_options', …)`; indirekt `booking_skill_support::resolve_single_option` (DB). Nutzt `global $DB`.
- **Aufrufkette:** Engine-Preflight-Phase; ruft Basisklassen-Helfer (`require_native_capability`, `apply_service_preflight`, `filter_prepared_input`, `localized_string`) und `booking_skill_support`.
- **Bewertung:** C — ~73 LOC, mehrere Verantwortungen (Cap, Anon-Branch, Query-Resolve, ID-Resolve, Issue-Aufbau) und mehrstufige Verschachtelung; statischer God-Call `booking_skill_support::resolve_single_option`; `global $DB`. Smell: `update_option_trainer_skill.php:231`.

### `execute(array $preparedinput, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt Trainer-Mutation aus via `booking_skill_mutation_execute_service`, ergaenzt Debug-Message, behandelt Nicht-Array-Resultat als Fehler.
- **Parameter/Rueckgabe:** prepared input / Ergebnis-Array (`status`, `detail`, `resultid`, `debugmessage`).
- **Seiteneffekte:** DB-Writes indirekt ueber den Service (Trainer-Zuweisung); Instanziiert `booking_skill_mutation_execute_service` lokal (new in Methode).
- **Aufrufkette:** Engine-Execute-Phase nach erfolgreichem Preflight.
- **Bewertung:** B — kompakt, klare Delegation; minimaler Smell durch `new`-Instanziierung des Service in der Methode (Testbarkeit).

### `verify_persisted_option_state(array $input, object $settings): array` — public
- **Zweck:** Post-Mutation-Verifikation der Trainerfelder.
- **Rueckgabe:** Array. **Seiteneffekte:** keine direkt (delegiert an `option_input_verification::verify_common_fields`).
- **Aufrufkette:** Post-Mutation-Verifikation der Engine.
- **Bewertung:** A — reine Delegation.

### `filter_prepared_input(array $input): array` — private
- **Zweck:** Whitelist-Filter auf trainer-relevante Felder.
- **Rueckgabe:** gefiltertes Array. **Seiteneffekte:** keine.
- **Bewertung:** B — funktional sauber, aber Allowedkeys-Liste dupliziert die aus `check_structure` (DRY-Verstoss).
