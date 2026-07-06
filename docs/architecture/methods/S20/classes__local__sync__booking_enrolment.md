# booking_enrolment — Methoden-Doku

**Datei:** `classes/local/sync/booking_enrolment.php` · **LOC:** 1241 · **Subsystem:** S20 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S20_sync.md)

## Klassenueberblick
Statische Service-Klasse fuer die Synchronisation von Kohorten-/Gruppen-Mitgliedschaft auf Buchungsoptions-Enrolments. Verwaltet `booking_sync_rules` (CRUD, Aktivierung, Cache), reagiert auf Mitgliedschafts-Events (`process_source_membership`, async via Adhoc-Task), fuehrt das eigentliche Enrol/Unenrol ueber `booking_option::user_submit_response`/`user_delete_response` aus und protokolliert jeden Versuch in `booking_sync_attempts`. Kollaborateure: `singleton_service`, `mod_booking\booking_option`, `bo_info`, `cache_helper`, `\core\task\manager`. Reine static-Utility ohne Instanzzustand — breite Verantwortung (Form-Parsing, Persistenz, Availability-Check, Enrolment, Audit, Cache) in einer Klasse gebuendelt.

## Methoden

### `save_rules_from_form(int $optionid, stdClass $fromform): int` — public static
- **Zweck:** Upsert je ausgewaehlter Kohorte/Gruppe aus dem Subscription-Formular; no-op wenn `syncenabled` leer.
- **Parameter/Rueckgabe:** optionid + Formdaten (cohortids/groupids/syncenrolaction/...); gibt Anzahl gespeicherter Rules zurueck.
- **Seiteneffekte:** Liest/schreibt `booking_sync_rules` (insert/update), `cache_helper::purge_by_event('setbacksyncrules')`; nutzt `$USER`, `$DB`.
- **Aufrufkette:** Aus Subscription-Form-Handler (UI). Ruft nur DB/Cache.
- **Bewertung:** B. Upsert-Block fuer existing/new ist intern dupliziert und teilt Felder mit `save_single_rule`. Knapp 66 LOC, akzeptabel.

### `save_single_rule(int $optionid, stdClass $data): int` — public static
- **Zweck:** Einzelne Rule per Webservice/Form anlegen oder aktualisieren, mit Duplikat- und Quellenpruefung; gibt Rule-ID zurueck.
- **Parameter/Rueckgabe:** optionid + data (sourcetype/sourceid/sync*/ruleid); Rule-ID.
- **Seiteneffekte:** Liest/schreibt `booking_sync_rules`, ruft `source_exists`, `cache_helper::purge_by_event`; `$USER`. Wirft `moodle_exception` bei ungueltigen Params/Duplikat.
- **Aufrufkette:** Webservice/Edit-Form. Ruft `source_exists`.
- **Bewertung:** C. ~84 LOC mit drei nahezu identischen Persist-Bloecken (update-by-id / update-existing / insert) — starkes internes Duplikat, dazu Felder-Dopplung gegen `save_rules_from_form`.

### `get_rule_for_option(int $optionid, int $ruleid): ?stdClass` — public static
- **Zweck:** Eine Rule laden und mit Source-Name/Label anreichern.
- **Seiteneffekte:** Liest `booking_sync_rules`, `cohort`/`groups` (Namen); `get_string`.
- **Aufrufkette:** UI/Webservice fuer Edit-Vorbefuellung.
- **Bewertung:** B. Klein, klar; Enrichment-Logik wiederholt sich in `get_rules_for_option`/`get_recent_attempts_for_option`.

### `delete_rule(int $optionid, int $ruleid, string $mode = MANUALIZE): array` — public static
- **Zweck:** Rule loeschen und rule-eigene Answers je nach Modus behandeln (manualize / keep-orphan / unenrol-soft-delete).
- **Rueckgabe:** `['affected' => int]`.
- **Seiteneffekte:** Transaktion; liest `booking_sync_rules`/`booking_answers`, update `booking_answers` (syncruleid=0), `booking_option::booking_history_insert`, `user_delete_response`, `log_attempt`, `delete_records` + Cache-Purge; `singleton_service`.
- **Aufrufkette:** Webservice/Delete-Handler. Ruft `singleton_service`, `booking_option`, `log_attempt`.
- **Bewertung:** C. ~90 LOC, switch mit drei Modi und gemischter Verantwortung (Transaktion, History-Audit, Unenrol, Loeschen) in einer Methode; Schachtelung im UNENROL-Zweig.

### `apply_rule_to_current_members(int $ruleid): array` — public static
- **Zweck:** Eine aktive Rule retroaktiv gegen aktuelle Source-Mitglieder anwenden (enrol fehlende, unenrol nicht mehr passende).
- **Rueckgabe:** `['enrolattempted', 'unenrolattempted']`.
- **Seiteneffekte:** Liest `booking_sync_rules`, `user` (Batch), `booking_answers`; ruft `get_source_member_ids`, `enrol_user_by_rule_with_user_cache`, `unenrol_user_by_rule`.
- **Aufrufkette:** Aus `activate_rule(retroactive)`, Cron/Task.
- **Bewertung:** B. Batch-Fetch gegen N+1 ist bewusst optimiert; ~50 LOC, vertretbar.

### `source_exists(string $sourcetype, int $sourceid): bool` — public static
- **Zweck:** Existenzpruefung Kohorte/Gruppe. Liest `cohort`/`groups`. Aufgerufen von `save_single_rule`. **Bewertung:** A.

### `get_source_member_ids(string $sourcetype, int $sourceid): array` — public static
- **Zweck:** Alle Mitglieder-User-IDs einer Quelle. Liest `cohort_members`/`groups_members`. Aufgerufen von `apply_rule_to_current_members`. **Bewertung:** A.

### `get_rules_for_option(int $optionid): array` — public static
- **Zweck:** Alle Rules einer Option, mit Source-Namen angereichert, MUC-gecacht.
- **Seiteneffekte:** `cache::make('mod_booking','syncrules')` get/set; bei Miss liest `booking_sync_rules`, `cohort`/`groups` (Batch).
- **Aufrufkette:** UI-Anzeige der Rules.
- **Bewertung:** C. ~60 LOC; manuelle Cache-Logik + zweimaliges Iterieren der Rules + Enrichment-Duplikat zu `get_rule_for_option`. Funktional ok, aber lang und vermischt Caching/Persistenz/Praesentation.

### `update_rule_settings(int $ruleid, array $updates): bool` — public static
- **Zweck:** Whitelist-Update einzelner Rule-Felder. Schreibt `booking_sync_rules`, Cache-Purge; `$USER`. Aufgerufen u.a. von `activate_rule`. **Bewertung:** A. Sauberes Allow-List-Pattern.

### `activate_rule(int $optionid, int $ruleid, bool $retroactive = false): array` — public static
- **Zweck:** Rule aktivieren (isenabled=1) und optional sofort retroaktiv anwenden.
- **Seiteneffekte:** Liest `booking_sync_rules`; ruft `update_rule_settings`, ggf. `apply_rule_to_current_members`. Wirft bei fehlender Rule.
- **Bewertung:** A. Kurz, delegiert sauber.

### `disable_rules_for_option(int $optionid): int` — public static
- **Zweck:** Alle aktiven Rules einer Option deaktivieren. Schreibt `booking_sync_rules`, Cache-Purge; `$USER`. **Bewertung:** A.

### `process_source_membership(string $sourcetype, int $sourceid, int $userid, bool $membershipadded): void` — public static
- **Zweck:** Auf Mitgliedschaftsaenderung reagieren: passende aktive Rules laden und enrol/unenrol triggern.
- **Seiteneffekte:** Liest `booking_sync_rules`; ruft `enrol_user_by_rule`/`unenrol_user_by_rule`.
- **Aufrufkette:** Aus Adhoc-Task `process_source_membership_adhoc` (gequeued via `queue_source_membership_sync`).
- **Bewertung:** A. Schlanker Dispatcher.

### `queue_source_membership_sync(string $sourcetype, int $sourceid, int $userid, bool $membershipadded): void` — public static
- **Zweck:** Sync-Arbeit als Adhoc-Task aus dem Request auslagern.
- **Seiteneffekte:** Validiert Inputs; erstellt/queued `mod_booking\task\process_source_membership_adhoc` via `core\task\manager::reschedule_or_queue_adhoc_task`.
- **Aufrufkette:** Aus Cohort/Group-Event-Observern.
- **Bewertung:** A.

### `enrol_user_by_rule(stdClass $rule, int $userid): void` — public static
- **Zweck:** User gemaess Rule in die Option einbuchen (Override = Force, sonst Availability-Check), mit Audit/History.
- **Seiteneffekte:** `singleton_service`, liest `user`/`booking_answers`, `bo_info::get_condition_results`/`is_available`, `option_allows_booking_for_user`, `user_submit_response`, `log_attempt`, `booking_history_insert`.
- **Aufrufkette:** Aus `process_source_membership`.
- **Bewertung:** D. ~160 LOC (708-867), tiefe Schachtelung im Respect-Zweig (Condition-ID-Aufbereitung), gemischte Verantwortung (Guard, Availability, Submit, Audit). **Nahezu identisches Duplikat** von `enrol_user_by_rule_with_user_cache`.

### `unenrol_user_by_rule(stdClass $rule, int $userid): void` — public static
- **Zweck:** User aus Option ausbuchen, sofern Answer von dieser Rule besessen; History-Eintrag mit syncaction.
- **Seiteneffekte:** `singleton_service`, ruft `is_sync_owned_by_rule`, `user_delete_response`; baut SQL `get_record_sql` auf `booking_answers`, `booking_history_insert`, `log_attempt`.
- **Aufrufkette:** Aus `process_source_membership`, `apply_rule_to_current_members`.
- **Bewertung:** C. ~74 LOC, handgeschriebenes SQL (920-929) wo `get_record` mit Status-Param genuegt; doppelter History-Eintrag-Workaround.

### `enrol_user_by_rule_with_user_cache(stdClass $rule, int $userid, ?stdClass $user = null): void` — public static
- **Zweck:** Wie `enrol_user_by_rule`, aber mit vorab geholtem User-Objekt fuer Schleifen.
- **Seiteneffekte:** identisch zu `enrol_user_by_rule` (nur User-Fetch optional uebersprungen).
- **Aufrufkette:** Aus `apply_rule_to_current_members`.
- **Bewertung:** E. ~163 LOC (958-1120) und fast zeilengleiche Kopie von `enrol_user_by_rule` — die einzige Differenz ist der optionale `$user`-Parameter. Reines Copy-Paste-Duplikat; jede Logikaenderung muss an zwei Stellen erfolgen.

### `is_sync_owned_by_rule(stdClass $rule, int $userid): bool` — public static
- **Zweck:** Prueft, ob Answer (optionid/userid/syncruleid) existiert. Liest `booking_answers`. **Bewertung:** A.

### `has_active_booking_answer(int $optionid, int $userid): bool` — public static
- **Zweck:** Prueft aktive Answer (booked/waitinglist/reserved). Baut `get_in_or_equal`-Klausel, `record_exists_select` auf `booking_answers`. **Bewertung:** B. Korrekt, leichter manueller SQL-Anteil.

### `log_attempt(int $syncruleid, int $bookingoptionid, int $userid, string $action, string $reasoncode, string $msg = ''): void` — public static
- **Zweck:** Versuch in `booking_sync_attempts` protokollieren. Insert-only. Vielfach aufgerufen. **Bewertung:** A.

### `get_recent_attempts_for_option(int $optionid, int $limit = 20): array` — public static
- **Zweck:** Letzte Sync-Versuche einer Option mit User-/Rule-/Source-Join fuer die Anzeige laden.
- **Seiteneffekte:** `get_records_sql` (Join `user`/`booking_sync_rules`/`cohort`/`groups`); reichert `rulesource`-Label an.
- **Aufrufkette:** UI-Report.
- **Bewertung:** B. Ein handgeschriebenes Join-SQL (vertretbar), Enrichment-Schleife klar; ~37 LOC.

## Triviale Akzessoren
Keine. Klasse besitzt nur Konstanten (Policy/Action/Reason/Delete-Mode) und statische Service-Methoden.
