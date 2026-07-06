# search_options_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/search_options_skill.php` · **LOC:** 373 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Definiert den Agent-Skill `mod_booking.search_options` (R0, readonly): Schema/Triggers/Guidance fuer die LLM-Auswahl plus Preflight und Ausfuehrung der Optionssuche. Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface`. Die eigentliche Such- und Linklogik ist an den God-Helper `booking_skill_support` (statische Calls) und an `core_text` ausgelagert; die Klasse uebersetzt nur zwischen Agent-Input und strukturiertem Ergebnis-Payload. Hauptverantwortung: Skill-Metadaten + Orchestrierung der Suche.

## Methoden

### `__construct()` — public
- **Zweck:** Registriert Skill als readonly mit Risikoklasse `R0` ueber `parent::__construct(true, skill_risk_class::R0)`.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Instanziierung durch Skill-Registry des Agents.
- **Bewertung:** A (trivial).

### `get_name(): string` — public
- **Zweck:** Liefert Task-Konstante `mod_booking.search_options`.
- **Rueckgabe:** string. **Seiteneffekte:** keine.
- **Aufrufkette:** Skill-Discovery/Routing.
- **Bewertung:** A.

### `get_schema(): array` — public
- **Zweck:** Baut das JSON-Schema (Beschreibung, Beispiel-Utterances, Properties query/outputlang/limit/when) und reichert es via `enrich_schema_with_prompt_meta()` an.
- **Rueckgabe:** array. **Seiteneffekte:** keine (statisches Array-Literal).
- **Aufrufkette:** Selection-Phase des Planners (Embeddings/Prompt-Katalog).
- **Bewertung:** B — langes deklaratives Array (~47 Z.), aber rein deskriptiv, kein Smell.

### `get_message_triggers(): array` — public
- **Zweck:** Liefert zwei Message-Trigger-Definitionen (exact-title / temporal filter).
- **Rueckgabe:** array. **Seiteneffekte:** keine.
- **Aufrufkette:** Trigger-Provider-Interface fuer Guidance-Injection.
- **Bewertung:** A.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert Trigger-Wortliste + Guidance-Saetze fuer die Prompt-Anreicherung dieses Skills.
- **Rueckgabe:** array. **Seiteneffekte:** keine.
- **Aufrufkette:** Guidance-Injection (enrich_construction_catalog_entry).
- **Bewertung:** B — Guidance-String in Z.145-146 enthaelt einen literalen Zeilenumbruch im Quelltext (eingebettete Newline + Einrueckung im Heredoc-losen String); kosmetisch unsauber, kein Funktionsbug. Smell: `search_options_skill.php:145`.

### `check_structure(array $input): array` — public
- **Zweck:** Validiert, dass `query` (falls gesetzt) ein String ist; baut lokalisierte Fehlermeldung.
- **Parameter:** `$input`. **Rueckgabe:** `{valid,errors,ambiguities}`.
- **Seiteneffekte:** `localized_string`/Sprachaufloesung (lesend).
- **Aufrufkette:** von `preflight()`.
- **Bewertung:** A.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Readonly-Preflight: cmid aus Kontext aufloesen, Instanz-Scope-Guard, Strukturpruefung; bei Fehlern `invalid()`-Issues, sonst `ok($input)`.
- **Parameter:** input/cmid/userid (userid ungenutzt). **Rueckgabe:** `preflight_result_v2`.
- **Seiteneffekte:** liest Kontext/Instanz-Scope (ueber Basisklasse).
- **Aufrufkette:** Engine-Preflight-Stufe; ruft `resolve_cmid_from_context_or_cmid`, `require_booking_instance_scope`, `check_structure`.
- **Bewertung:** B — Standard-Boilerplate; Issue-Aufbau leicht repetitiv, aber klar.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Suche aus: exact-title-Short-Circuit, sonst Kandidaten-Preview-Suche; baut strukturierte Optionsliste + Observation-Payload + Debug-Message.
- **Parameter:** input (query/question/when/outputlang/limit), cmid, userid (ungenutzt). **Rueckgabe:** Result-Array (status/detail/usermessage/observation_full/options/previewoptionids/...).
- **Seiteneffekte:** `global $DB`; DB-Read `booking_options.text` (Z.233); mehrere statische God-Calls an `booking_skill_support` (find_existing_options_by_exact_title, build_option_link_for_output, search_option_candidates_for_preview); `get_string` (bookingextension_agent). Keine Writes/Events/Cache.
- **Aufrufkette:** Engine-Executor (readonly); ruft private `extract_exact_title_query`, `build_observation_full`.
- **Bewertung:** C — ~104 LOC (Z.208-312), mehrere Verantwortungen (Input-Parsing, Exact-Match-Pfad, Fuzzy-Pfad, Result-Mapping, Debug-Building) in einer Methode; drei nahezu identische Result-Array-Konstruktionen (Duplikation); statische God-Calls; direkter `$DB`-Zugriff trotz Helper-Schicht. Smells: `search_options_skill.php:208` (Laenge/mixed responsibility), `:233` (Inline-DB-Read), `:242`/`:266`/`:302` (dupliziertes Result-Array). `$question` (Z.216) wird gelesen, aber nie verwendet (Dead Var).

### `build_observation_full(string $usermessage, array $structuredoptions): string` — private
- **Zweck:** Normalisiert Optionen und haengt JSON-Payload an die Usermessage fuer Folge-Reasoning an.
- **Parameter:** usermessage, structuredoptions. **Rueckgabe:** string (mit Fallback auf usermessage bei json_encode-Fehler).
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** A — klar, mit Fehler-Fallback.

### `extract_exact_title_query(string $query): string` — private static
- **Zweck:** Extrahiert eine exakte Titelabsicht aus NL-Query (Quotes oder `title is ...`).
- **Parameter:** query. **Rueckgabe:** string (leer wenn kein Titel-Intent).
- **Seiteneffekte:** keine; `core_text::strtolower`, zwei `preg_match`.
- **Aufrufkette:** von `execute()`.
- **Bewertung:** B — heuristisches Regex-Parsing von NL (fragil/sprachgebunden auf "title"), aber gekapselt und klein.

## Triviale Akzessoren
keine reinen Getter/Setter ausser den oben gebuendelten Metadaten-Methoden.
