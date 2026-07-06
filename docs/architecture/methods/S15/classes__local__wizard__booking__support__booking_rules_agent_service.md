# booking_rules_agent_service — Methoden-Doku
**Datei:** `classes/local/wizard/booking/support/booking_rules_agent_service.php` · **LOC:** 650 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
Support-Service fuer die AI-/Agent-gesteuerten Booking-Rules-Aufgaben (`bookingextension_agent`). Kapselt das Auflisten, Aufloesen (per ID/Query/Fuzzy), Erstellen und Aktualisieren von Booking-Rules ueber dieselbe Handler-Pipeline wie das dynamische AJAX-Formular (`rules_info::set_data_for_form` + `rules_info::save_booking_rule`). Kollaborateure: `templaterule` (Template-Records), `booking_rules` (kontextbezogene Rule-Liste), `rules_info` (Form-Daten/Speichern), `context_module`, `moodle_url`, `core_text`, `get_string_manager`. Zustandslos (keine Properties), arbeitet rein funktional auf uebergebenen IDs/Kontexten.

## Methoden

### `get_module_contextid(int $cmid): int` — public
- **Zweck:** Liefert die Modul-Context-ID zu einer Booking-cmid.
- **Parameter/Rueckgabe:** `$cmid` → Context-ID (int).
- **Seiteneffekte:** DB-Read via `get_coursemodule_from_id('booking', ...)` (MUST_EXIST → wirft bei fehlend); `context_module::instance()` (Cache/DB).
- **Aufrufkette:** vom Agent-Skill/Task zur Kontextaufloesung; ruft Core-Course-Module-API.
- **Bewertung:** A — schlank, klar.

### `build_rules_link(int $cmid): string` — public
- **Zweck:** Baut den Edit-Link (`edit_rules.php`) fuer das Booking-Modul; ohne cmid Site-Level-Overview.
- **Parameter/Rueckgabe:** `$cmid` → URL-String.
- **Seiteneffekte:** keine (nur `moodle_url`-Konstruktion).
- **Aufrufkette:** Output-Aufbereitung fuer Agent-Antworten.
- **Bewertung:** A.

### `list_templates(): array` — public
- **Zweck:** Listet verfuegbare Rule-Templates (builtin = negative ID, saved = positive ID), nach Name sortiert; ID 0 wird ausgefiltert.
- **Rueckgabe:** Array von `{templateid, name, source}`.
- **Seiteneffekte:** `templaterule::get_template_rules()` (DB-Read der gespeicherten Templates + builtin-Liste).
- **Aufrufkette:** von `resolve_template`; extern fuer Listenausgabe.
- **Bewertung:** A — inkl. inline usort-Closure (trivial).

### `resolve_template(int $templateid = 0, string $templatequery = ''): array` — public
- **Zweck:** Loest ein Template per ID oder Freitext-Query auf: exakte Treffer → contains → Fuzzy-Similarity-Pick → Ambiguitaets-Fallback. Liefert `status` ok/error/ambiguity.
- **Parameter/Rueckgabe:** ID oder Query → Status-Array (`template` bzw. `candidates`).
- **Seiteneffekte:** indirekt via `list_templates` (DB-Read); keine Writes.
- **Aufrufkette:** Agent-Preflight/Clarification-Flow; ruft `normalize_template_lookup_token`, `score_template_similarity`.
- **Bewertung:** C — ~108 LOC, mehrstufige Matching-Heuristik mit Magic-Thresholds (0.62 / 0.08, slice 12), mehrere verschachtelte Closures, gemischte Verantwortung (Lookup + Scoring-Orchestrierung + Fallback-Formatierung). Smell: booking_rules_agent_service.php:98-205 (Methodenlaenge / verschachtelte Bedingungen / Magic Numbers).

### `normalize_template_lookup_token(string $value): string` — private
- **Zweck:** Normalisiert ein Token (lowercase, Nicht-Buchstaben/Ziffern → Space, Whitespace kollabieren) fuer robustes Matching.
- **Seiteneffekte:** keine (preg_replace, core_text).
- **Aufrufkette:** von `resolve_template`, `score_template_similarity`.
- **Bewertung:** A.

### `score_template_similarity(string $query, string $name): float` — private
- **Zweck:** Berechnet generischen Aehnlichkeitswert [0,1] aus Token-Overlap (Jaccard) und String-Similarity (`similar_text`), gewichtet 0.35/0.65.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `resolve_template`; ruft `normalize_template_lookup_token`, `similar_text`.
- **Bewertung:** B — etwas dicht, aber gekapselt; Gewichtungs-Magic-Numbers dokumentiert.

### `list_rules_for_context(int $contextid, bool $activeonly = false): array` — public
- **Zweck:** Listet (optional nur aktive) Rules eines Modul-Kontexts, normalisiert + nach Name sortiert.
- **Seiteneffekte:** `booking_rules::get_list_of_saved_rules_by_context()` (DB-Read).
- **Aufrufkette:** von `resolve_rule`; ruft `normalize_rule_record`.
- **Bewertung:** A.

### `resolve_rule(int $contextid, int $ruleid = 0, string $rulequery = ''): array` — public
- **Zweck:** Loest Ziel-Rule per ID oder Query (exact/contains, ctype_digit→ID-Recursion) im Kontext auf; Status ok/error/ambiguity.
- **Seiteneffekte:** indirekt DB-Read via `list_rules_for_context`.
- **Aufrufkette:** Agent-Preflight; rekursiver Self-Call bei numerischer Query.
- **Bewertung:** B — ~56 LOC, klare Struktur, leichte Duplizierung des exact/contains-Patterns aus `resolve_template`.

### `create_rule_from_template(int $contextid, int $templateid, array $overrides = []): array` — public
- **Zweck:** Erstellt eine neue Rule aus einem builtin-Template (nur negative IDs) ueber die Form-Handler-Pipeline; setzt Name/Aktiv-Flag aus Overrides.
- **Seiteneffekte:** `templaterule::get_template_record_by_id` (DB-Read); `rules_info::set_data_for_form` + `rules_info::save_booking_rule` (DB-Write `booking_rules`, ggf. Events/Handler-Logik in rules_info); `$DB->get_record('booking_rules')` (Read). Nutzt `global $DB`.
- **Aufrufkette:** Agent-Skill „create rule"; ruft `apply_handler_defaults_from_record`, `normalize_rule_record`.
- **Bewertung:** C — ~50 LOC, viel imperative Seed-/Override-Verdrahtung gemischt mit DB-Persistenz und Fehler-Returns; Daten-Massaging am `$data`-stdClass eng an rules_info-Internals gekoppelt. Smell: booking_rules_agent_service.php:374-424 (gemischte Verantwortung Seed/Persist/Reload, magische Form-Felder).

### `update_rule_from_template(int $contextid, int $ruleid, int $templateid = 0, array $overrides = []): array` — public
- **Zweck:** Aktualisiert eine kontext-lokale Rule (Cross-Context-Guard), optional Re-Apply eines anderen Templates, Name-/Aktiv-Override; persistiert ueber die Pipeline.
- **Seiteneffekte:** `$DB->get_record('booking_rules')` (2× Read); `templaterule::get_template_record_by_id` (Read); `rules_info::set_data_for_form` (mehrfach) + `rules_info::save_booking_rule` (DB-Write `booking_rules`). `global $DB`.
- **Aufrufkette:** Agent-Skill „update rule"; ruft `extract_rule_name_from_record`, `apply_handler_defaults_from_record`, `normalize_rule_record`.
- **Bewertung:** D — ~78 LOC, hohe Verzweigung (Template-Reapply-Pfad, Name-Resolution, Aktiv-Flag, Cross-Context-Check), starke Duplizierung der Seed-Logik aus `create_rule_from_template`, eng an Form-Interna gekoppelt. Smell: booking_rules_agent_service.php:435-512 (Laenge/Verschachtelung/Duplikat zu create_rule_from_template).

### `list_active_rules_for_context(int $contextid): array` — public
- **Zweck:** Listet nur aktive (isactive=1) Rules eines Kontexts, normalisiert + sortiert.
- **Seiteneffekte:** `booking_rules::get_list_of_saved_rules_by_context()` (DB-Read).
- **Aufrufkette:** Agent-Listenausgabe; ruft `normalize_rule_record`.
- **Bewertung:** B — funktional nahezu identisch zu `list_rules_for_context($ctx, true)` (Duplikat, koennte delegieren). Smell: booking_rules_agent_service.php:523-542 (Duplikat zu list_rules_for_context).

### `normalize_rule_record(stdClass $record, int $currentcontextid = 0): array` — private
- **Zweck:** Mappt einen DB-Rule-Record auf das Task-Ausgabe-Array inkl. JSON-Decode, lokalisierter Rule/Condition/Action-Namen und kontextbezogenem Edit-Link (current/system/other).
- **Seiteneffekte:** `get_string_manager`/`get_string` (Lang-Lookups), `moodle_url`; keine Writes.
- **Aufrufkette:** von allen List-/Create-/Update-Methoden.
- **Bewertung:** C — ~57 LOC, viel repetitives Feld-/Lokalisierungs-Massaging mit `is_object($json)`-Guards und hartkodiertem Default-Component `bookingextension_agent`; mehrere parallele lname/localized-Tripel. Smell: booking_rules_agent_service.php:557-614 (lange, repetitive Mapping-Logik).

### Triviale Akzessoren / Helfer
- `apply_handler_defaults_from_record(stdClass $data, stdClass $record): void` (private, ~12 LOC) — setzt fehlende `bookingruletype/...conditiontype/...actiontype` aus Record/JSON; Score B.
- `extract_rule_name_from_record(stdClass $record): string` (private, ~6 LOC) — liest `json->name`; Score A.
