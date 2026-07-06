# booking_skill_base — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/booking_skill_base.php` · **LOC:** 1224 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
Abstrakte Basisklasse fuer alle mod_booking-Agent-Skills (erbt von `bookingextension_agent\local\wizard\base_skill`). Stellt gemeinsame Infrastruktur bereit: Schema/Prompt-Metadaten, Beispiel-Eingaben, Risk-Class-/Native-Capability-Deklaration (Gate 2), Eingabe-Validierung ohne DB, Scope-Guards fuer Nicht-Booking-Kontexte, Preflight-Bruecke zum Validation-Service, Observation-/Debug-Text-Bau und Preview-Bereitstellung. Delegiert die eigentliche Ausfuehrung an `booking_skill_support` (geteilte Singleton-Instanz) bzw. `booking_skill_mutation_execute_service` und nutzt `singleton_service`, `booking_mutation_validation`, `preflight_result_v2` sowie `booking_option_preview_renderer`.

Auffaellig: ~480 LOC der Datei sind zwei grosse statische Konfigurations-Arrays (`$promptmeta`, `$exampleinput`) — ein zentraler Hardcode-Katalog ALLER Skill-Namen direkt in der Basisklasse. Das ist der Hauptgrund fuer Groesse, Kopplung und Klassen-Score C: die Basisklasse kennt jeden konkreten Skill namentlich (verletzt Open/Closed; jeder neue Skill erfordert Edit hier).

## Methoden

### `__construct(bool $readonly, string $riskclass, array $nativecapabilities = [])` — public
- **Zweck:** Konstruktor; validiert Risk-Class, setzt Native-Capabilities, initialisiert geteilte `booking_skill_support`-Singleton.
- **Parameter:** readonly-Flag, Risk-Class-String, optionale native Moodle-Capabilities (Gate 2).
- **Rueckgabe:** —
- **Seiteneffekte:** wirft `\coding_exception` bei ungueltiger Risk-Class; setzt/erzeugt statische `self::$sharedsupport` (prozessweit geteilte Instanz); ruft `parent::__construct`.
- **Aufrufkette:** Von jedem konkreten Skill-Constructor (`parent::__construct`). Ruft `skill_risk_class::is_valid`, `new booking_skill_support`.
- **Bewertung:** B — kompakt; einziger Smell ist die statische Shared-Instanz (Test-Isolations-Risiko), aber bewusst als Caching gewaehlt.

### `get_required_native_capabilities(): array` — public
- **Zweck/Rueckgabe:** Liefert die im Constructor deklarierten nativen Capabilities (Gate 2). Reiner Getter.
- **Seiteneffekte:** keine. **Aufrufkette:** vom Engine-`native_capability_guard`. **Bewertung:** A.

### `get_name(): string` — public abstract
- **Zweck:** Vertrag: konkrete Skills liefern ihren Task-Namen. **Bewertung:** A (abstrakt).

### `get_schema(): array` — public
- **Zweck:** Default-Schema-Geruest (version/description/readonly/properties leer). Von Subklassen ueberschrieben.
- **Seiteneffekte:** keine (liest `is_read_only()`). **Aufrufkette:** Skill-Registry/Prompt-Bau. **Bewertung:** A.

### `get_example_input(): array` — public
- **Zweck:** Liefert Beispiel-Eingabe aus statischem `$exampleinput`-Katalog anhand `get_name()`.
- **Rueckgabe:** Beispiel-Array oder `[]`. **Seiteneffekte:** keine. **Bewertung:** B — funktional simpel, aber an den zentralen Hardcode-Katalog gekoppelt (siehe Klassenueberblick).

### `enrich_schema_with_prompt_meta(array $schema): array` — protected
- **Zweck:** Injiziert `prompt_meta` (input_fields_for_prompt/anchor_fields) aus statischem `$promptmeta` in das Schema, wenn nicht bereits gesetzt.
- **Parameter/Rueckgabe:** Schema rein/raus (angereichert oder unveraendert). **Seiteneffekte:** keine.
- **Aufrufkette:** von `get_schema()` der Subklassen. **Bewertung:** B — sauberer Helper, Kopplung an Hardcode-Katalog.

### `enrich_legacy_option_result(array $result, array $input, int $cmid, string $action): array` — protected
- **Zweck:** Ergaenzt ein Option-Mutationsergebnis um optionid, bookingid und eine `observation_full`-Textzusammenfassung.
- **Parameter:** result, input, cmid, action; **Rueckgabe:** angereichertes result.
- **Seiteneffekte:** **DB-Read** via `get_coursemodule_from_id('booking', $cmid)` (course_modules); ruft `build_legacy_option_observation`.
- **Aufrufkette:** von create/update-Option-Skills nach Ausfuehrung. **Bewertung:** B — moderat, klare Aufgabe, mehrere Fallback-Quellen fuer optionid sind etwas unuebersichtlich.

### `apply_legacy_create_visibility_if_requested(array $input, int $optionid, int $cmid, int $userid): string` — protected
- **Zweck:** Setzt nach Option-Erstellung eine explizit angeforderte Sichtbarkeit per Folge-Update-Aufruf.
- **Rueckgabe:** Fehlertext (leer = ok).
- **Seiteneffekte:** **DB-Write indirekt** — erzeugt `booking_skill_mutation_execute_service` und fuehrt `update_option_skill::TASK_NAME` aus (Option-Update); ruft statisch `booking_skill_support::normalize_visibility_input`.
- **Aufrufkette:** von create_option-Skill. **Bewertung:** C — Smell: mischt Validierung, Service-Instanziierung und Status-Auswertung; sekundaerer Mutations-Roundtrip (`booking_skill_base.php:619-655`); statischer Support-Call. Gemischte Verantwortung.

### `build_legacy_option_observation(int $optionid, array $input, array $result, string $action): string` — private
- **Zweck:** Baut menschenlesbaren Observation-String fuer Option-Mutationen (title/type/maxanswers/invisible).
- **Seiteneffekte:** **Read** via `singleton_service::get_instance_of_booking_option_settings` (gecachte Option-Settings); try/catch schluckt Fehler.
- **Aufrufkette:** von `enrich_legacy_option_result`. **Bewertung:** B — linearer String-Bau, ok; viele isset-Verzweigungen aber flach.

### `validate_common_mutation_structure(array $input, bool $allowbookusersquery = true): array` — protected
- **Zweck:** DB-freie Strukturvalidierung gemeinsamer Mutationsfelder (visibility, optiondatesmode, Datums-/Zeitfelder, optiondates, bookusersquery).
- **Rueckgabe:** eindeutige Fehlerliste (lang-strings).
- **Seiteneffekte:** keine DB; ruft statisch `booking_skill_support::normalize_visibility_input/parse_datetime/extract_optiondates` und mehrere `get_string`.
- **Aufrufkette:** von preflight() der Mutations-Skills. **Bewertung:** C — Smell: mehrere statische God-Calls + verschachtelte Schleife mit Platzhalter/Override-Sonderfaellen (`booking_skill_base.php:728-741`); gebuendelte heterogene Validierungen in einer Methode.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Standard-Ausfuehrungspfad: cmid aus Kontext aufloesen, an `support->execute` delegieren.
- **Seiteneffekte:** delegiert (Support kann DB schreiben). **Aufrufkette:** vom Executor; ruft `resolve_cmid_from_context_or_cmid` + `support->execute`. **Bewertung:** A.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Default leere contextual prompt packs (von Subklassen ueberschrieben). **Bewertung:** A.

### `verify_persisted_option_state(array $input, object $settings): array` — public
- **Zweck:** Default-No-op-Hook fuer Post-Mutation-Verifikation (Subklassen-Override). **Bewertung:** A.

### `apply_service_preflight(string $taskname, array $preparedinput, int $cmid, int $userid, array $existingissues = [], string $lang = ''): preflight_result_v2` — protected
- **Zweck:** Fuehrt Service-Level-Preflight (`booking_mutation_validation::validate_common`) aus und mappt Errors/Ambiguities in einen `preflight_result_v2`.
- **Rueckgabe:** `preflight_result_v2::invalid(...)` oder `::ok($preparedinput)`.
- **Seiteneffekte:** delegiert an Validation-Service (kann DB lesen). Parameter `$userid`/`$lang` ungenutzt.
- **Aufrufkette:** von preflight() der Mutations-Skills. **Bewertung:** C — Smell: ungenutzte Parameter (`$userid`, `$lang`) (`booking_skill_base.php:798-805`); verschachteltes Issue-Mapping mit Index-Korrelation zwischen issue_codes und errors ist fragil.

### `resolve_cmid_from_context_or_cmid(int $contextid): int` — protected
- **Zweck:** Loest cmid strikt aus einer Modul-Kontext-ID auf (sonst 0).
- **Seiteneffekte:** `\context::instance_by_id(..., IGNORE_MISSING)` (Kontext-Lookup/Cache). **Aufrufkette:** von `execute()` und preflight. **Bewertung:** A.

### `require_booking_instance_scope(int $resolvedcmid): ?preflight_result_v2` — protected
- **Zweck:** Preflight-Guard: gibt Clarification zurueck, wenn keine Booking-Instanz im Scope (cmid 0), sonst null.
- **Seiteneffekte:** `get_string`. **Aufrufkette:** von preflight() der Skills. **Bewertung:** A.

### `build_no_instance_scope_result(int $resolvedcmid): ?array` — protected
- **Zweck:** Execute-Pfad-Pendant fuer R0-Readonly-Skills: liefert nicht-crashendes Ergebnis mit Liste zugaenglicher Booking-Instanzen + Planner-Anweisungen.
- **Rueckgabe:** vollstaendiges Ergebnis-Array oder null (wenn cmid>0).
- **Seiteneffekte:** ruft `list_accessible_booking_instances` (DB+Caps); diverse `get_string`, `moodle_url`.
- **Aufrufkette:** von execute() der Readonly-Skills. **Bewertung:** C — Smell: lang (~50 LOC) mit eingebetteter Praesentations-/Observation-Textgenerierung und Closure-Mapping (`booking_skill_base.php:887-936`); vermischt Datenbeschaffung, Markdown-Listenbau und Engine-Anweisungstexte.

### `list_accessible_booking_instances(): array` — protected
- **Zweck:** Listet fuer den User sichtbare Booking-Instanzen mit Links (gefiltert ueber can_access_course + uservisible).
- **Seiteneffekte:** **DB-Read** `$DB->get_records_sql` (JOIN {booking}/{course}); `can_access_course`, `get_fast_modinfo`, `format_string`, `moodle_url`. try/catch pro Kurs.
- **Aufrufkette:** von `build_no_instance_scope_result`. **Bewertung:** C — Smell: handgeschriebenes SQL in Skill-Basisklasse (`booking_skill_base.php:951-957`) + verschachtelte Schleifen mit Caps-Pruefung; potenziell N+1 (modinfo pro Kurs), laut Kommentar nur im seltenen No-Scope-Fall akzeptiert.

### `require_native_capability(string $capability, int $cmid, int $userid): ?preflight_result_v2` — protected
- **Zweck:** Gate-2-Helfer: erzwingt native Moodle-Capability am Modul-Kontext; gibt Clarification bei cmid<=0, Permission-Fehler bei fehlender Capability, sonst null.
- **Seiteneffekte:** `has_capability` auf `context_module::instance($cmid)`; `get_string`. **Aufrufkette:** von preflight() der Mutations-Skills. **Bewertung:** B — klar, leichte Verzweigung; ok.

### `build_task_debug_message(string $taskname, array $input, array $extra = []): string` — protected
- **Zweck:** Baut kurzen technischen Debug-String aus Task + Input (rekursiv geflachte Arrays, slice 5).
- **Seiteneffekte:** keine. Nutzt rekursive Closure `$flatten`.
- **Aufrufkette:** von Skills fuer Debug-Logging. **Bewertung:** B — moderat, rekursive Closure + Schleife, aber abgegrenzt.

### `get_output_language(array $input): string` — protected
### `normalize_identity_query(string $value): string` — protected
- (Beide trivial-naher String-Normalisierung; siehe Triviale Akzessoren.)

### `normalize_identity_value($value)` — protected
- **Zweck:** Rekursive Normalisierung eines Identity-Payloads (Listen array_values, Maps ksort, Strings trim) fuer stabiles Queue-Dedup-Hashing.
- **Seiteneffekte:** keine. **Aufrufkette:** von Dedup-/Queue-Logik. **Bewertung:** B — saubere Rekursion.

### `localized_string(string $identifier, $a = null, string $lang = ''): string` — protected
- **Zweck:** Liest Lang-String, optional in erzwungener Sprache via `get_string_manager()`.
- **Seiteneffekte:** keine (Lang-Lookup). **Bewertung:** A.

### `enforce_max_chars(string $text, int $maxchars): string` — protected
- **Zweck:** Erzwingt harte Maximallaenge (multibyte-sicher via core_text, mit Ellipsis).
- **Seiteneffekte:** keine. **Bewertung:** A.

### `remember_preview_options(array $optionids, int $cmid, int $userid): void` — public
- **Zweck:** Merkt sich Preview-Optionen-IDs (Feature "update those options") fuer Folge-Turn.
- **Seiteneffekte:** delegiert an statisch `booking_skill_support::remember_last_preview_options_for_user_for_execute` (User-Preference/Cache-Write). **Aufrufkette:** duck-typed vom Executor. **Bewertung:** B.

### `get_result_preview(array $resultentry, int $contextid, int $userid): ?array` — public
- **Zweck:** Liefert serverseitig gerenderten Preview-HTML-Block fuer ein ausgefuehrtes Booking-Ergebnis.
- **Seiteneffekte:** instanziiert `booking_option_preview_renderer` und ruft `render` (kann DB lesen/rendern). **Aufrufkette:** vom Executor (Preview-Datenvertrag). **Bewertung:** B — Sammeln/Dedupe der optionids + Render; ok.

## Triviale Akzessoren
- `get_required_native_capabilities()` — Getter (oben gelistet, A).
- `get_output_language(array $input): string` — trim des `outputlang`-Feldes; A.
- `normalize_identity_query(string $value): string` — lowercase/trim/Whitespace-Kollaps; A.
