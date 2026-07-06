# diagnose_user_booking_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/diagnose_user_booking_skill.php` · **LOC:** 1001 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Read-only Agent-Skill (`mod_booking.diagnose_user_booking`, Risk R0), das fuer EINE Person einen verbosen, strukturierten Buchungs-Statusbericht erstellt — entweder option-fokussiert (tiefe Einzeldiagnose inkl. empfangener Nachrichten) oder instanzweit (Aggregat ueber alle Buchungen). Kollaborateure: `booking_answers` (gecachte Lese-API statt direkter DB), `singleton_service` (Settings/Answers), `booking_skill_support` (User-/Option-Resolver, Link-Builder), Logstore-Reader-API (`\core\log\sql_reader`) fuer Nachrichten- und Zertifikatsfeld-Historie, `tool_certificate\certificate` (optional), sowie `observation_time` zur Timestamp-Formatierung. Erbt von `booking_skill_base`, implementiert `skill_trigger_provider_interface`.

## Methoden

### `__construct()` — public
- **Zweck:** Setzt read-only + Risk-Klasse R0 via `parent::__construct(true, skill_risk_class::R0)`.
- **Seiteneffekte:** keine. **Bewertung:** A (trivial).

### `get_name(): string` — public
- **Zweck/Rueckgabe:** Liefert `self::TASK_NAME`. **Bewertung:** A.

### `get_required_native_capabilities(): array` — public
- **Zweck/Rueckgabe:** `['mod/booking:readresponses']` (Gate-2-Cap zum Lesen fremder Antworten). **Bewertung:** A.

### `get_schema(): array` — public
- **Zweck:** Baut JSON-Schema des Skills (Beschreibung, Beispiel-Utterances, Properties userquery/userid/optionid/optionquery/includemessages/outputlang) und reichert per `enrich_schema_with_prompt_meta()` an.
- **Rueckgabe:** Schema-Array. **Seiteneffekte:** keine (reine Datenstruktur).
- **Aufrufkette:** Engine/Discovery liest Schema. **Bewertung:** B (lang durch eingebettete Strings, aber rein deklarativ, ~64 LOC reiner Daten-Literals).

### `get_message_triggers(): array` — public
- **Zweck/Rueckgabe:** Deklarative Trigger-Definition (id/description/examples) fuer Discovery. **Seiteneffekte:** keine. **Bewertung:** A (deklarativ).

### `get_contextual_prompt_packs(): array` — public
- **Zweck/Rueckgabe:** Deklarative Guidance-Packs (Trigger-Keywords DE/EN + Guidance-Zeilen). **Seiteneffekte:** keine. **Bewertung:** A (deklarativ; Mini-Smell: Duplikat `'no email received'` in der Trigger-Liste, Zeilen 230/231 — harmlos).

### `check_structure(array $input): array` — public
- **Zweck:** Validiert, dass userid ODER userquery gesetzt ist; sonst lokalisierter Fehler.
- **Rueckgabe:** `{valid, errors[], ambiguities[]}`. **Seiteneffekte:** keine. **Bewertung:** A.

### `preflight(array $input, int $contextid, int $userid): preflight_result_v2` — public
- **Zweck:** Ruft `check_structure`, mappt Fehler auf `VALIDATION_ERROR`-Issues (`needs_clarification`) → `preflight_result_v2::invalid`, sonst `::ok($input)`.
- **Seiteneffekte:** keine. **Aufrufkette:** Engine-Preflight. **Bewertung:** A.

### `execute(array $input, int $contextid, int $userid): array` — public
- **Zweck:** Orchestriert die Diagnose: cmid aus Kontext, Zieluser aufloesen, optionalen Optionsfokus aufloesen, in option- vs. instanzweiten Report verzweigen, Detail-Message lokalisieren, Option-/User-Links anhaengen, Timestamps humanisieren, Ergebnis-Array bauen.
- **Parameter/Rueckgabe:** Input + Kontext → Ergebnis-Array (status/detail/usermessage/observation_full/resultid/previewoptionids/debugmessage).
- **Seiteneffekte:** indirekt Reads ueber `build_*_report` (booking_answers, Logstore, tool_certificate); statische Calls `booking_skill_support::build_option_link_for_output`/`format_user_links`. Keine Writes.
- **Aufrufkette:** Engine-Executor → ruft `resolve_target_userid`, `resolve_focus_optionid`, `build_option_report`/`build_userwide_report`, `humanize_report_timestamps`, `build_observation_full`, `error_result`.
- **Bewertung:** C — ~66 LOC, gemischte Verantwortung (Resolve + Branch + Praesentation/Link-Bau + Debug); akzeptabel als Skill-Entrypoint, aber Praesentationslogik (Link-Anhang Z.349-356) koennte ausgelagert werden.

### `resolve_target_userid(array $input): int` — private
- **Zweck:** Liefert explizite userid oder loest userquery via `booking_skill_support::resolve_single_user`; 0 wenn nicht aufloesbar.
- **Seiteneffekte:** statischer Resolver-Call (DB-Read intern). **Bewertung:** A.

### `resolve_focus_optionid(array $input, int $cmid, int $actinguserid): int` — private
- **Zweck:** Liefert explizite optionid oder loest optionquery (modul-scoped) via `booking_skill_support::resolve_single_option`; 0 wenn keiner/nicht aufloesbar.
- **Seiteneffekte:** statischer Resolver-Call. **Bewertung:** A (Parameter `$actinguserid` ungenutzt — minimal).

### `build_option_report(int $cmid, int $optionid, int $targetuserid, bool $includemessages): array` — private
- **Zweck:** Baut den option-fokussierten Report ueber `booking_answers` (Status, completed, active/previous/cancelled answers, completion-Zeit, optional Nachrichten, Zertifikate, Zertifikatsfeld-Aenderungszeitpunkt).
- **Rueckgabe:** Report-Array. **Seiteneffekte:** Reads via `singleton_service` (option_settings, answers, booking_settings_by_cmid), `booking_option::get_value_of_json_by_key`, Logstore (ueber `read_received_messages`/`certificate_field_last_change`), tool_certificate.
- **Aufrufkette:** von `execute`; ruft `summarize_answer(_collection)`, `status_label`, `resolve_message_window_start`, `read_received_messages`, `collect_user_certificates`, `certificate_issued_for_completion`, `certificate_field_last_change`.
- **Bewertung:** C — ~64 LOC; gemischte Verantwortung (Status + Messages + Cert-Logik + Timing-Heuristik) und doppelter Fetch `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)` (Z.471 und Z.492). Cache greift, aber redundant.

### `build_userwide_report(int $cmid, int $targetuserid, bool $includemessages): array` — private
- **Zweck:** Instanzweiter Report: eine gecachte `booking_answers::get_all_answers_for_user`-Abfrage ueber 5 Statusparams, aggregiert Totals (active/completed/waitinglist/reserved/previously_booked/cancelled) und baut bis MAX_OPTIONS_USERWIDE Per-Option-Eintraege; optional Nachrichten + Zertifikate.
- **Rueckgabe:** Report-Array. **Seiteneffekte:** Reads via `booking_answers`, `singleton_service::get_instance_of_booking_option_settings` pro Zeile (N+1 ueber MUC-Cache), Logstore, tool_certificate.
- **Aufrufkette:** von `execute`; ruft `status_label`, `extract_customform_fields`, `resolve_message_window_start`, `read_received_messages`, `collect_user_certificates`.
- **Bewertung:** D — ~91 LOC (>80), tief verschachtelter switch innerhalb Schleife + Per-Zeilen-`singleton_service`-Call (N+1, durch Cache abgefedert); klarer Kandidat fuer Zerlegung (Aggregation vs. Per-Option-Mapping trennen).

### `resolve_message_window_start(array $report): int` — private
- **Zweck:** Bestimmt unteren Zeitfenster-Rand fuer Logstore: erste Buchungserstellung, aber nie aelter als 12 Monate (`strtotime('-12 months')`). Option-scoped leitet aus active/previous/cancelled-Timestamps ab.
- **Seiteneffekte:** keine (nur `strtotime`). **Bewertung:** B (~26 LOC, leicht verzweigt aber klar).

### `read_received_messages(int $courseid, int $targetuserid, int $optionid, int $windowstart): array` — private
- **Zweck:** Liest empfangene Booking-Nachrichten aus dem Standard-Logstore (gebunden auf courseid + relateduserid + Message-Event-Namen + Zeitfenster, optional objectid), via Reader-API mit Hard-Limit + Truncation-Detection.
- **Rueckgabe:** `{window_start, limit, count, truncated, messages[]}`. **Seiteneffekte:** Logstore-Read (`get_log_manager()->get_readers`, `get_events_select`); `$DB->get_in_or_equal` zum Param-Bau (kein direkter Query auf Logtabelle). `global $DB`.
- **Aufrufkette:** von `build_option_report`/`build_userwide_report`.
- **Bewertung:** C — ~61 LOC, manueller SQL-Select-String-Bau + Param-Merge + try/catch um `get_name()`; vertretbar (Reader-API kapselt Query), aber Select-Bau + Event-Mapping gemischt.

### `status_label(int $statuscode): string` — private
- **Zweck:** Mappt MOD_BOOKING_STATUSPARAM_*-Konstante auf stabiles Label. **Seiteneffekte:** keine. **Bewertung:** A (reiner switch).

### `summarize_answer(?object $answer): ?array` — private
- **Zweck:** Reduziert einen Answer-Datensatz auf report-sichere Felder (Status, completed, Zeiten, places, customform). **Seiteneffekte:** keine. **Bewertung:** A.

### `summarize_answer_collection($answers): array` — private
- **Zweck:** Normalisiert Objekt/Array/null zu Liste und mappt jedes via `summarize_answer`. **Seiteneffekte:** keine. **Bewertung:** A.

### `extract_customform_fields(object $answer): array` — private
- **Zweck:** Extrahiert skalare, nicht-leere `customform_*`-Properties des Answer-Records. **Seiteneffekte:** keine. **Bewertung:** A.

### `certificate_issued_for_completion(array $certificates, int $templateid, int $completiontime): bool` — private
- **Zweck:** Prueft, ob fuer das Template ein Zertifikat zum/ nach dem Completion-Zeitpunkt ausgestellt wurde (verhindert, dass alte book-again-Issues ein fehlendes aktuelles Zertifikat maskieren).
- **Seiteneffekte:** keine. **Bewertung:** A.

### `certificate_field_last_change(int $optionid, int $courseid): ?int` — private
- **Zweck:** Liefert timecreated der juengsten `bookingoption_updated`-Loganderung, die das Feld `certificate` betrifft (sonst null), via Logstore-Reader; dekodiert `other`-JSON und scannt `changes[]`.
- **Rueckgabe:** Unix-Timestamp|null. **Seiteneffekte:** Logstore-Read mit CERT_LOG_SCAN_LIMIT.
- **Aufrufkette:** nur von `build_option_report` (im Fehlend-Zertifikat-Fall). **Bewertung:** C — ~43 LOC, Logstore-Select-Bau + JSON-Decode + doppelt verschachtelte Schleife; eigenstaendig aber gemischt (Query + Change-Scan).

### `collect_user_certificates(int $targetuserid, int $configuredtemplateid): array` — private
- **Zweck:** Sammelt die via tool_certificate ausgestellten Zertifikate des Users (resilient: leer wenn Plugin fehlt); markiert ggf. das konfigurierte Template als ausgestellt/abgelaufen.
- **Rueckgabe:** Struktur mit availability/globally_enabled/certificates[]/count etc. **Seiteneffekte:** `get_config('booking','certificateon')`, externer Call `\tool_certificate\certificate::get_issues_for_user` (DB-Read), `format_string`.
- **Aufrufkette:** von beiden build_*_report. **Bewertung:** B (~45 LOC, klar, externer optionaler Call sauber gegated).

### `humanize_report_timestamps(array $report): array` — private
- **Zweck:** Rekursiver reiner Output-Pass: konvertiert nur Keys aus OBSERVATION_TIMESTAMP_KEYS via `observation_time::format`. **Seiteneffekte:** keine. **Bewertung:** A.

### `build_observation_full(string $detailmessage, array $report): string` — private
- **Zweck:** Haengt JSON-serialisierten Report an die Detail-Message fuer Synchronizer-Reasoning. **Seiteneffekte:** keine. **Bewertung:** A.

### `error_result(string $message, array $input, array $debugextra): array` — private
- **Zweck:** Baut einheitliches Fehler-Ergebnis-Array inkl. Debug-Message. **Seiteneffekte:** keine. **Bewertung:** A.
