# create_rule_from_template_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/create_rule_from_template_skill.php` · **LOC:** 462 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_skills.md)

## Klassenueberblick
Agent-Skill (`booking_skill_base` + `skill_trigger_provider_interface`) fuer die Aktion `mod_booking.create_rule_from_template`: erzeugt eine Buchungsregel aus einem (ggf. eingebauten) Regel-Template ueber die serverseitige Rules-Form-Pipeline. Die eigentliche Fachlogik liegt in einem optional aufgeloesten Kollaborateur `booking_rules_agent_service` (`$this->ruleservice`); der Skill orchestriert nur Schema, Trigger, Preflight-Template-Aufloesung (inkl. Konfidenz-/Ambiguitaets-Behandlung) und Execute-Mapping. Erbt Capability-/Kontext-Helfer (`require_native_capability`, `resolve_cmid_from_context_or_cmid`, `build_task_debug_message`) von der Basisklasse.

## Methoden

### `__construct()` — public
- **Zweck:** Setzt via `parent::__construct(false, R2, ['mod/booking:editbookingrules'])` Read-only=false, Risikoklasse R2, Capability; loest danach den Rules-Service auf.
- **Parameter/Rueckgabe:** keine / —.
- **Seiteneffekte:** Instanziiert ueber `resolve_rule_service()` einen Service (kein DB/Cache direkt).
- **Aufrufkette:** vom Skill-Registry/Discovery beim Bootstrap; ruft `resolve_rule_service`.
- **Bewertung:** A — schlank.

### `resolve_rule_service(): ?object` — private
- **Zweck:** Service-Locator: probiert drei Kandidaten-Klassennamen durch (`class_exists` + `try new`), liefert erste funktionierende Instanz oder null.
- **Parameter/Rueckgabe:** keine / `object|null`.
- **Seiteneffekte:** keine; verschluckt `\Throwable` still (continue).
- **Aufrufkette:** nur aus Konstruktor.
- **Bewertung:** C — Smell `create_rule_from_template_skill.php:53` Service-Locator mit hartkodierter Klassennamen-Liste + leerem catch (Discovery-Robustheit erkauft mit verstecktem Failure-Modus); schwer testbar.

### `get_name(): string` — public
- **Zweck:** Liefert `TASK_NAME`.
- **Bewertung:** A (trivial).

### `get_schema(): array` — public
- **Zweck:** Statisches JSON-Schema (version/description/example_utterances/properties: templateid, templatequery, question, rulename, isactive, outputlang) fuer den Planner/LLM.
- **Parameter/Rueckgabe:** keine / array.
- **Seiteneffekte:** ruft `is_read_only()` (Basis). Sonst reine Konstante.
- **Aufrufkette:** vom Agent-Schema-Provider.
- **Bewertung:** B — lang (~53 LOC) aber reine deklarative Daten, ein Verantwortungsbereich; LLM-Steuertext im Schema eingebettet.

### `get_message_triggers(): array` — public
- **Zweck:** Liefert ein NL-Trigger-Objekt (id/description/examples) zur Intent-Erkennung "Buchungsregel anlegen".
- **Seiteneffekte:** keine (statische Daten; ein Beispiel doppelt gelistet).
- **Aufrufkette:** ueber `skill_trigger_provider_interface` vom Trigger-Sammler.
- **Bewertung:** A — deklarativ.

### `check_structure(array $input): array` — public
- **Zweck:** Bewusst permissive Strukturpruefung; gibt immer `valid:true` zurueck (Template-Wahl wird im Preflight als Clarification behandelt).
- **Bewertung:** A (bewusst trivial, kommentiert).

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Vorvalidierung: Capability-Check, Service-Verfuegbarkeit, Aufloesung von Template (per id oder query inkl. Fallback auf `question`/`userquery`/`rulename`), Behandlung von error/ambiguity-Status mit Auto-Select fuer Confirmation-Intents oder Clarification mit Kandidatenliste.
- **Parameter/Rueckgabe:** input + cmid + userid / `preflight_result_v2` (ok mit angereichertem `prepared`-Input bzw. invalid mit issues).
- **Seiteneffekte:** liest Kontext via `resolve_cmid_from_context_or_cmid`; `require_native_capability` (Capability-Check); ruft `ruleservice->list_templates()` und `->resolve_template()` (DB-Reads im Service). Keine Writes.
- **Aufrufkette:** von der Agent-Preflight-Phase; ruft `try_autoselect_confirmation_template`.
- **Bewertung:** D — Smell `create_rule_from_template_skill.php:190` ~128 LOC, hohe zyklomatische Komplexitaet (mehrere status-Verzweigungen error/ambiguity/missing), duplizierte Kandidaten-Listen-Bauschleifen (Zeilen 231-238 und 292-299 nahezu identisch), gemischte Verantwortung (Eingabe-Normalisierung + Resolver-Orchestrierung + Clarification-Formatierung). Kandidat fuer Extraktion in Hilfsmethoden.

### `try_autoselect_confirmation_template(string $templatequery, string $rulename, array $candidates): ?array` — private
- **Zweck:** Task-lokale Disambiguierung: erkennt Confirmation-Intent (Needle-Match auf normalisiertem Text) und waehlt aus Kandidaten das passende "confirm booking"/"booking confirmation"-Template.
- **Parameter/Rueckgabe:** query, rulename, candidates / `array|null`.
- **Seiteneffekte:** keine (rein), ruft `normalize_intent_text`.
- **Aufrufkette:** nur aus `preflight` (zweimal).
- **Bewertung:** C — Smell `create_rule_from_template_skill.php:331` hartkodierte englische Needles ("booking confirmation"…) -> sprachabhaengige Heuristik, fragil/nicht i18n; ansonsten klar strukturiert.

### `normalize_intent_text(string $value): string` — private
- **Zweck:** Lowercase + Entfernen von Nicht-Alphanumerik (Unicode) zur Match-Normalisierung.
- **Seiteneffekte:** keine (zwei `preg_replace`).
- **Aufrufkette:** aus `try_autoselect_confirmation_template`.
- **Bewertung:** A — kleine, reine Util.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Regelerzeugung aus: Kontext-Id holen, overrides (rulename/isactive) bauen, `ruleservice->create_rule_from_template()` aufrufen, Erfolg/Fehler in standardisiertes Result-Array (status/detail/usermessage/resultid/debugmessage/…) mappen, Rules-Link anhaengen.
- **Parameter/Rueckgabe:** input + cmid + userid / array (Skill-Result).
- **Seiteneffekte:** ueber Service: DB-Write (Regel-Insert) in `create_rule_from_template`; Reads `get_module_contextid`, `build_rules_link`. Direkt keine.
- **Aufrufkette:** von der Agent-Execute-Phase; ruft `build_task_debug_message` (Basis).
- **Bewertung:** B — ~60 LOC, etwas repetitive Result-Array-Konstruktion (drei Rueckgabe-Varianten), aber klare lineare Logik und saubere Delegation an den Service.

### Triviale Akzessoren
`get_name` (S. 79) — siehe oben (gebuendelt deklarativ/trivial).
