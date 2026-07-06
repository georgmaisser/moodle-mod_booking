# bulk_update_options_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/bulk_update_options_skill.php` · **LOC:** 414 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Skill-Definition fuer den Agent-Task `mod_booking.bulk_update_options`: aktualisiert mehrere Buchungsoptionen in einem Zug, indem dieselben gemeinsamen Mutationsfelder auf alle gematchten Optionen angewendet werden. Erweitert `booking_skill_base` und implementiert `queue_identity_provider_interface` (Dedup) sowie `skill_trigger_provider_interface` (Message-Trigger). Bewusst als duenne Spiegelung von `update_option` gehalten: Validierung, Verifikation und Persistenz delegieren an gemeinsame Helfer (`option_input_verification`, `option_schema_definition`, `booking_skill_support`, `booking_skill_mutation_execute_service`), damit keine parallele Implementierung entsteht (Thread-206-Regression vermieden). Kern-Logik liegt in `preflight()` (Ziel-Aufloesung + DB-Checks) und `execute()` (Service-Delegation).

## Methoden

### `__construct()` — public
- **Zweck:** Setzt Skill-Metadaten via `parent::__construct`: nicht read-only, Risikoklasse R2, benoetigte Capability `mod/booking:addeditownoption`.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** keine (nur Property-Init in Basisklasse).
- **Aufrufkette:** Skill-Instanziierung durch Registry/Katalog.
- **Bewertung:** A — trivialer Konstruktor.

### `get_name(): string` — public
- **Zweck:** Liefert den Task-Namen (`TASK_NAME`).
- **Seiteneffekte:** keine. **Bewertung:** A.

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut eine stabile, normalisierte Geschaeftsidentitaet zur Queue-Deduplikation: extrahiert/uniquet/sortiert option-IDs (aus `optionids` + `resolvedoptionids`), normalisiert `optionquery`, entfernt nicht-identitaetsrelevante Keys und gibt `selection` + `changes` strukturiert zurueck.
- **Parameter:** `$input` Roh-Skill-Input. **Rueckgabe:** `array<string,mixed>` (task_family/selection/changes).
- **Seiteneffekte:** keine (pure); nutzt `normalize_identity_query`/`normalize_identity_value` der Basisklasse.
- **Aufrufkette:** vom Queue-/Dedup-Layer (Interface) aufgerufen.
- **Bewertung:** B — ~45 LOC, zwei aehnliche Int-Filter-Schleifen (leichte Duplizierung 64-76), aber klar und pure.

### `get_example_input(): array` — public
- **Zweck:** Repraesentatives FLAT-Beispiel fuer den Construction-Phase-Katalog; bewusst ohne `{changes:[...]}`-Envelope (Thread-206-Hinweis im Docblock).
- **Seiteneffekte:** keine. **Bewertung:** A — Daten-Literal mit wichtigem Vertragskommentar.

### `verify_persisted_option_state(array $input, object $settings): array` — public
- **Zweck:** Delegiert Feld-Verifikation an `option_input_verification::verify_common_fields`, identisch zu `update_option` (geteilte Logik via mutation_execute_service).
- **Seiteneffekte:** Liest Option-State indirekt ueber Helfer. **Bewertung:** A — reine Delegation.

### `get_schema(): array` — public
- **Zweck:** Liefert das Task-Schema (Version, Beschreibung, readonly-Flag, Fallback-String-Keys, Beispiel-Utterances, Properties = Ziel-Selektoren + `option_schema_definition::common_properties()`).
- **Seiteneffekte:** keine. **Bewertung:** A — deklaratives Schema, gut kommentiert.

### `get_message_triggers(): array` — public
- **Zweck:** Definiert drei Message-Trigger (apply_to_all bestaetigt / Selektion via Query / via optionids) fuer das Trigger-Provider-Interface.
- **Seiteneffekte:** keine. **Bewertung:** A — Daten-Literal.

### `check_structure(array $input): array` — public
- **Zweck:** Reine Strukturvalidierung ohne DB: mindestens ein Ziel-Selektor (optionids/optionquery/apply_to_all) muss vorhanden sein; danach gemeinsame Mutationsstruktur via `validate_common_mutation_structure`.
- **Rueckgabe:** `{valid:bool, errors:[]}`.
- **Seiteneffekte:** keine (pure); `get_string`.
- **Aufrufkette:** vor `preflight()` durch Engine.
- **Bewertung:** B — ~23 LOC, mehrere Early-Returns, aber lesbar.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Validierung + DB-Aufloesung: cmid-Resolve, Capability-Check, Ziel-Selektion (inkl. Preview-Fallback-IDs), Validierung expliziter optionids gegen Instanz, Aufloesung von Preview-Query-Referenzen in konkrete IDs, Ablehnung von `bookusersquery`, abschliessend Service-Preflight. Befuellt `prepared_input.optionids` damit `execute()` keine Bulk-Aufloesung mehr braucht.
- **Parameter:** Roh-Input, cmid, userid. **Rueckgabe:** `preflight_result_v2`.
- **Seiteneffekte:** DB-Reads — `$DB->record_exists('booking_options', ...)`, `get_coursemodule_from_id('booking', ...)`; Reads via `booking_skill_support::resolve_last_preview_option_ids_for_user_for_execute` / `resolve_bulk_option_ids_for_execute` / `is_last_preview_selection_reference`; nutzt globales `$DB`.
- **Aufrufkette:** Engine-Preflight-Phase; ruft `apply_service_preflight` (Basisklasse) und mutiert `$preparedinput`.
- **Bewertung:** C — ~90 LOC, gemischte Verantwortung (Cap-Check + 4 verschiedene Selektions-/Validierungspfade + Issue-Sammlung), mehrere verschachtelte Early-Returns; SQL-naher Direkt-`record_exists` in Schleife (`bulk_update_options_skill.php:293`). Smell: Laenge + Pfad-Vielfalt → Kandidat fuer Extraktion von Resolver-Helfern.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert ein kontextuelles Guidance-Pack (Trigger-Phrasen + Bullet-Guidance) zur Steuerung des Planner-Prompts bei Bulk-Absichten.
- **Seiteneffekte:** keine. **Bewertung:** A — Daten-Literal.

### `execute(array $preparedinput, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Mutation aus, indem an `booking_skill_mutation_execute_service::execute` delegiert wird; reichert Ergebnis mit lokalisierten User-/Debug-Messages + outputlang an; Error-Fallback-Struktur bei Nicht-Array-Resultat.
- **Parameter:** vorbereiteter Input (mit resolved optionids), cmid, userid. **Rueckgabe:** `array` (status/usermessage/outputlang/debugmessage/...).
- **Seiteneffekte:** DB-Writes indirekt ueber `booking_skill_mutation_execute_service` (Options-Updates, Cache-Purges in der Service-Schicht, nicht hier sichtbar); `$this->support` weitergereicht.
- **Aufrufkette:** Engine-Execute-Phase nach erfolgreichem Preflight.
- **Bewertung:** B — ~30 LOC, klare Delegation; leichte Verzweigung fuer Success/Error-Form.

## Zusammenfassung
Insgesamt eine saubere, deklarativ gepraegte Skill-Klasse mit einer einzigen schwergewichtigen Methode (`preflight`). Single-Responsibility weitgehend gewahrt durch konsequente Delegation an gemeinsame Helfer; Hauptverbesserungspotenzial liegt im Zerlegen von `preflight()`.
