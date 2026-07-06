# analyze_rules_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/analyze_rules_skill.php` · **LOC:** 400 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
`analyze_rules_skill` ist ein readonly (R0) Agent-Skill, der Booking-Rules und deren Benachrichtigungsverhalten im aktuellen Kontext analysiert und natuerlichsprachlich auflistet. Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface`. Kollaborateure: ein optional aufgeloester `booking_rules_agent_service` (DB-Lesen via `list_rules_for_context`/`list_templates`/`build_rules_link`), `preflight_result_v2` (Preflight-Vertrag), `core_text` (Lowercasing). Die Klasse ist im wesentlichen ein Definitions-/Schema-Container plus eine grosse `execute()`-Such-/Serialisierungs-Logik.

## Methoden

### `__construct()` — public
- **Zweck:** Initialisiert Basis (readonly=true, R0) und loest den optionalen Rules-Service auf.
- **Parameter/Rueckgabe:** keine / —.
- **Seiteneffekte:** ruft `resolve_rule_service()` (kann Service-Objekt instanziieren); setzt `$this->ruleservice`.
- **Aufrufkette:** Skill-Discovery/Registry instanziiert; ruft `parent::__construct`, `resolve_rule_service`.
- **Bewertung:** A — schlank.

### `resolve_rule_service(): ?object` — private
- **Zweck:** Sucht ueber eine Kandidatenliste von Klassennamen den ersten existierenden Rules-Service und instanziiert ihn, ohne Task-Discovery zu brechen.
- **Parameter/Rueckgabe:** keine / Service-Objekt oder `null`.
- **Seiteneffekte:** `class_exists`-Checks, Objektkonstruktion in try/catch.
- **Aufrufkette:** nur aus `__construct`.
- **Bewertung:** B — defensive Service-Locator-Lookup. Leichter Smell: hartkodierte 3-fache Klassennamen-Liste (analyze_rules_skill.php:52-56) als verstecktes Kopplungs-/Konfigurations-Detail; stiller `catch` schluckt Fehler.

### `get_name(): string` — public
- **Zweck:** Liefert Task-Namen (`mod_booking.analyze_rules`).
- **Bewertung:** A (trivial).

### `get_schema(): array` — public
- **Zweck:** Liefert das deklarative Task-Schema (Beschreibung, readonly-Flag, Fallback-String-Keys, Beispieleingaben, Properties query/active_only/limit/include_templates/outputlang).
- **Parameter/Rueckgabe:** keine / grosses Array-Literal.
- **Seiteneffekte:** liest `$this->is_read_only()`.
- **Aufrufkette:** Engine/Planner zur Skill-Beschreibung.
- **Bewertung:** B — reines Daten-Literal, aber ~50 LOC; akzeptabel da deklarativ.

### `get_message_triggers(): array` — public
- **Zweck:** Liefert NL-Trigger (Beschreibung + Beispiel-Utterances) fuer das Intent-Routing.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Discovery/Embeddings-Schicht.
- **Bewertung:** A — deklaratives Literal.

### `check_structure(array $input): array` — public
- **Zweck:** Strukturvalidierung ohne DB; gibt hier immer `valid=true` zurueck.
- **Bewertung:** A (no-op Validator, bewusst).

### `user_can_analyze_system_rules(int $userid): bool` — private
- **Zweck:** Prueft, ob der User systemweite Booking-Rules ansehen darf (Fallback fuer globalen Einstieg ohne Instanz).
- **Seiteneffekte:** `has_capability('mod/booking:editbookingrules', context_system)` — Capability-Read.
- **Aufrufkette:** aus `preflight` und `execute`.
- **Bewertung:** A — klar gekapselt.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Readonly-Preflight: cmid aufloesen, Service-Verfuegbarkeit pruefen, Scope-Guard (Instanz noetig vs. System-Fallback), Struktur validieren, Input unveraendert durchreichen.
- **Parameter/Rueckgabe:** input/cmid/userid / `preflight_result_v2` (invalid|ok).
- **Seiteneffekte:** `resolve_cmid_from_context_or_cmid` (Kontext-Read), `user_can_analyze_system_rules` (Capability-Read), `require_booking_instance_scope`.
- **Aufrufkette:** Engine-Preflight; ruft Basis-Helfer + check_structure.
- **Bewertung:** B — ~35 LOC mit verschachtelter Guard-Bedingung (Zuweisung-in-if `($guard = ...)` analyze_rules_skill.php:218), aber lesbar.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Kernlogik: contextid bestimmen (Modul vs. System-Fallback vs. Scope-Rueckfrage), Rules laden, nach Keyword filtern (mit Fallback-auf-alle), limitieren, optional Templates filtern, Summary-Text + serialisierte Rule-Zeilen + Edit-Link bauen und Result-Array zurueckgeben.
- **Parameter/Rueckgabe:** input/cmid/userid / Result-Array (`status`, `detail`, `usermessage`, `observation_full`, `rules`, `templates`, `link`, `debugmessage`).
- **Seiteneffekte:** ueber `$this->ruleservice`: `get_module_contextid`, `list_rules_for_context` (DB-Read Booking-Rules), `list_templates` (DB-Read), `build_rules_link`; `context_system::instance()`; `build_no_instance_scope_result`; `build_task_debug_message`.
- **Aufrufkette:** Engine-Executor; delegiert an Rules-Service.
- **Bewertung:** D — ~153 LOC, mehrere Verantwortungen gemischt (Kontextaufloesung + Such-/Filterlogik + Template-Filter + Textserialisierung + Result-Bau). Verschachtelte foreach/if-Kette (analyze_rules_skill.php:286-309), inline String-Building der Rule-Zeilen (344-379). Sollte in resolve_context / filter_rules / render_summary zerlegt werden.

### `array_filter`-Closure (Template-Filter) — anonym (statisch), in `execute`
- **Zweck:** Filtert Templates per Lowercase-Substring-Match gegen den Needle.
- **Bewertung:** A — kleine reine Closure (analyze_rules_skill.php:323-326).

### Triviale Akzessoren
- `get_name()`, `check_structure()` siehe oben (effektiv trivial/no-op).

## Anmerkungen
- Keine echten Bugs gefunden; reiner Readonly-Skill.
- Stiller `catch (\Throwable)` in `resolve_rule_service` verbirgt Konstruktionsfehler (bewusst fuer Discovery-Robustheit, aber undiagnostizierbar).
- Hauptlast in `execute()` (Score D) treibt Klassen-Score auf C: Single-Responsibility verletzt, gute Testbarkeit nur ueber den Service-Mock; Extraktionspotenzial vorhanden.
