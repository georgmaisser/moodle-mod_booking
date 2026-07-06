# update_rule_from_template_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/update_rule_from_template_skill.php` · **LOC:** 346 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`update_rule_from_template_skill` ist ein mutierender Agent-Skill (`mod_booking.update_rule_from_template`, Risk-Class R2), der eine bestehende kontext-lokale Booking-Rule aktualisiert — optional durch erneutes Anwenden eines Templates — und Felder wie `rulename`/`isactive` ueberschreibt. Erbt von `booking_skill_base`, implementiert `skill_trigger_provider_interface`. Die eigentliche Domaenenlogik liegt nicht im Skill, sondern in einem optional aufgeloesten `booking_rules_agent_service` (Property `$ruleservice`), der ueber mehrere Kandidaten-Klassennamen tolerant geladen wird, damit Task-Discovery auch ohne installierten Service nicht bricht. Persistenz: indirekt ueber den Rule-Service (Tabelle `booking_rules`); der Skill selbst schreibt nicht. Kollaborateure: `booking_rules_agent_service` (resolve_rule/resolve_template/update_rule_from_template/get_module_contextid/build_rules_link), Basisklassen-Helfer (`resolve_cmid_from_context_or_cmid`, `require_native_capability`, `invalid`/`pass`, `build_task_debug_message`). Capability-Gate: `mod/booking:editbookingrules`.

## Methoden

### `public function __construct()` — public
- **Zweck:** Registriert den Skill als nicht-readonly (R2) mit erforderlicher Capability `mod/booking:editbookingrules` und loest sofort den Rule-Service auf. **Seiteneffekte:** `parent::__construct(false, R2, [...])`, `resolve_rule_service()` (Klassen-Instanziierung). **Bewertung:** A.

### `private function resolve_rule_service(): ?object` — private
- **Zweck:** Loest den Rule-Service tolerant auf: probiert drei Kandidaten-FQCNs durch, instanziiert den ersten existierenden, faengt `Throwable` pro Kandidat ab und gibt sonst `null` zurueck. **Seiteneffekte:** `class_exists`, `new $classname` (Konstruktor-Seiteneffekte des Service). **Rueckgabe:** Service-Objekt oder null. **Bewertung:** B — robuste „graceful degradation" fuer Discovery; das Verschlucken jeglicher Konstruktor-Exception (`continue`) kann echte Fehler maskieren, ist hier aber bewusst.

### `public function get_name(): string` — public
- **Zweck:** Liefert `mod_booking.update_rule_from_template`. **Bewertung:** A.

### `public function get_schema(): array` — public
- **Zweck:** Deklariert Beschreibung, Beispieleingaben, Fallback-String-Keys und optionale Properties (`ruleid`/`rulequery`/`templateid`/`templatequery`/`rulename`/`isactive`/`outputlang`). **Seiteneffekte:** keine. **Bewertung:** A — rein deklarativ.

### `public function get_message_triggers(): array` — public
- **Zweck:** Ein Trigger-Eintrag, der „bestehende Rule per id/name modifizieren, optional via Template" beschreibt. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function check_structure(array $input): array` — public
- **Zweck:** Verlangt mindestens `ruleid` oder eine nicht-leere `rulequery`; sonst `valid:false` mit Hinweis. **Seiteneffekte:** keine. **Rueckgabe:** `{valid, errors}`. **Bewertung:** A.

### `protected function run_preflight(array $input, int $cmid, int $userid): array` — protected
- **Zweck:** Tiefer Preflight: cmid aufloesen, native Capability pruefen, Service-Verfuegbarkeit pruefen, dann ueber den Service Rule und (falls templateid/templatequery gesetzt) Template aufloesen; bei error/ambiguity strukturierte Issues (inkl. Kandidatenliste) zurueckgeben, sonst aufgeloeste `ruleid`/`templateid` in `prepared` schreiben. **Seiteneffekte:** `require_native_capability`, `ruleservice->get_module_contextid/resolve_rule/resolve_template` (DB-Lesezugriffe ueber den Service). **Rueckgabe:** `pass($prepared)` oder `invalid($issues)`. **Bewertung:** A — gruendliche Aufloesung mit Ambiguitaets-Kandidaten und Capability-Gate vor jeder Mutation.

### `public function execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Mutation aus: prueft Service-Verfuegbarkeit, sammelt Overrides (`rulename`/`isactive`), ruft `update_rule_from_template($contextid, ruleid, templateid, $overrides)` und baut bei Erfolg eine Bestaetigung mit Rule-Name, id und Rules-Link; bei `status != ok` ein `failed`-Result. **Seiteneffekte:** `ruleservice->get_module_contextid/update_rule_from_template/build_rules_link` (DB-Schreibzugriff im Service), `build_task_debug_message`. **Rueckgabe:** Result-Array `{status:'executed'|'failed', detail, usermessage, resultid, rule?, link?, debugmessage}`. **Bewertung:** B — korrekter Mutationspfad; `isactive` wird ueber `array_key_exists` statt `isset` ausgelesen (erlaubt bewusst explizites `false`), `rulename` ueber `isset`; `execute` verlaesst sich darauf, dass `run_preflight` `ruleid`/`templateid` bereits aufgeloest hat (bei Direktaufruf ohne Preflight wuerde `ruleid=0` durchgereicht).

## Bewertungs-Resümee
Solider R2-Mutations-Skill mit klarer Trennung Skill/Service, Capability-Gate, Ambiguitaets-Handling und graceful Service-Degradation. Schwaechen rein robustheitsbezogen: das pauschale Verschlucken von Konstruktor-Exceptions in `resolve_rule_service` kann echte Fehler verbergen, und `execute` ist auf vorgelagerten Preflight angewiesen. Funktional unkritisch. Klassen-Score **B / P3**.
