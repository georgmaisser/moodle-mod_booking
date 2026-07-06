# list_option_properties_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/list_option_properties_skill.php` · **LOC:** 268 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`list_option_properties_skill` ist ein read-only Agent-Skill (`booking.list_option_properties`, Risk-Class R0), der dem LLM die unterstuetzten Buchungsoptions-Eigenschaften beschreibt — Selbstbeschreibung der Felder, die bei Create/Update gesetzt werden koennen. Erbt von `booking_skill_base` (mod_booking-seitige Skill-Basis) und implementiert `skill_trigger_provider_interface` (Trigger-/Guidance-Beitraege fuer Discovery). Statt in die Engine-Registry zu greifen, liest `execute()` die Schemas der Schwester-Skills `create_option_skill` und `update_option_skill` direkt aus (gleiches Plugin), vereinigt deren Property-Listen und filtert nach `scope`. Keine Persistenz, keine DB-Schreibzugriffe. Kollaborateure: `create_option_skill`/`update_option_skill` (Schema-Quelle), `booking_skill_support` (Label-Lokalisierung), Basisklassen-Helfer (`resolve_cmid_from_context_or_cmid`, `require_booking_instance_scope`, `localized_string`, `get_output_language`).

## Methoden

### `public function __construct()` — public
- **Zweck:** Registriert den Skill als read-only (erstes Argument `true`) mit Risk-Class R0. **Seiteneffekte:** delegiert an `parent::__construct(true, skill_risk_class::R0)`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Liefert den Task-Namen `mod_booking.list_option_properties`. **Rueckgabe:** `self::TASK_NAME`. **Bewertung:** A.

### `public function get_schema(): array` — public
- **Zweck:** Beschreibt das Eingabeschema (optionale `question`, `scope`, `outputlang`) und markiert `readonly` ueber `is_read_only()`. **Seiteneffekte:** keine. **Bewertung:** A — rein deklarativ.

### `public function get_message_triggers(): array` — public
- **Zweck:** Liefert ein Trigger-Beispielset („Which fields can I set when creating a new booking option?" etc.) fuer die Discovery-/Embedding-Schicht. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert kontextuelle Guidance (Trigger-Phrasen + Anweisungen), damit der Planner diesen Skill bei Feld-/Property-Fragen waehlt. **Seiteneffekte:** keine. **Bewertung:** B — der Trigger-Eintrag `'which fields'` ist doppelt aufgefuehrt (Z.112 und Z.113); funktional harmlos, aber redundant.

### `public function check_structure(array $input): array` — public
- **Zweck:** Strukturpruefung; gibt bewusst immer `valid:true` zurueck, weil `scope` ohnehin durch `normalize_scope()` normalisiert wird. **Seiteneffekte:** keine. **Rueckgabe:** `{valid:true, errors:[], ambiguities:[]}`. **Bewertung:** A — bewusste, dokumentierte Soft-Validation (LLM-Halluzinationen werden gefangen statt hart geblockt).

### `private function normalize_scope(array $input): string` — private
- **Zweck:** Normalisiert `scope` (lowercase/trim) auf eine Whitelist `all|create|update|shared`; alles andere faellt auf `all` zurueck. **Seiteneffekte:** keine. **Rueckgabe:** normalisierter Scope-String. **Bewertung:** A.

### `protected function run_preflight(array $input, int $cmid, int $userid): array` — protected
- **Zweck:** Preflight fuer den read-only Task: aufloesen des cmid aus Kontext, Instanz-Scope-Guard, danach `check_structure` und unveraendertes Durchreichen via `pass()`. **Seiteneffekte:** `resolve_cmid_from_context_or_cmid`, `require_booking_instance_scope` (kann Guard-Result zurueckgeben). **Rueckgabe:** `pass($input)` oder `invalid($issues)`. **Bewertung:** B — die VALIDATION_ERROR-Schleife ist toter Pfad, da `check_structure` nie `valid:false` liefert; defensiv, aber nie erreicht.

### `public function execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Baut die Property-Liste: instanziiert `create_option_skill` und `update_option_skill`, vereinigt deren `properties`-Schluessel (`array_unique`+`sort`), filtert nach Scope und liefert pro Feld name/label/type/description sowie `increate`/`inupdate`/`requiredon*`-Flags; haengt eine lokalisierte Zusammenfassung an. **Seiteneffekte:** Instanziiert zwei Schwester-Skills (kein DB-Write); `booking_skill_support::get_localized_property_label_for_output`, `localized_string`, `build_task_debug_message`. **Rueckgabe:** Result-Array `{status:'executed', detail, usermessage, properties[], debugmessage, resultid:null}`. **Bewertung:** B — sauberer, deterministischer Aggregator; `$question` (Z.202) wird gelesen, aber nie verwendet (toter Lokalwert); `outputlang` wird genutzt. Die Direkt-Instanziierung der Schwester-Skills haelt den Skill bewusst frei von Engine-Internas.

## Bewertungs-Resümee
Sauberer, gut dokumentierter read-only Selbstbeschreibungs-Skill ohne Persistenz und ohne Sicherheitsoberflaeche (R0, Instanz-Scope-Guard). Schwaechen rein kosmetisch: doppelter Trigger-Eintrag, toter VALIDATION_ERROR-Pfad in `run_preflight`, ungenutzte `$question`-Variable in `execute`. Klassen-Score **B / P3**.
