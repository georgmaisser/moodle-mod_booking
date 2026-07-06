# S20 — sync_enrolment

## Zweck & Grenzen

Dieses Subsystem bündelt alles, was Nutzer in eine Buchungsoption (und mittelbar
in den verbundenen Moodle-Kurs) hinein- oder herausbringt — abseits des
regulären, vom Nutzer selbst initiierten Buchungsweges:

- **Membership-Sync** (`local\sync\booking_enrolment`): Regelbasiertes Spiegeln
  von Kohorten-/Gruppen-Mitgliedschaft auf Buchungsantworten (enrol/unenrol),
  inkl. Bedingungs-Policy, Audit-Log und Cron-Anbindung.
- **enrollink** (`enrollink`): „Lizenz-Bundle"-Mechanik — ein Nutzer bucht n
  Plätze und verteilt Selbst-Einschreibe-Links (erlid) an weitere Nutzer, die
  sich darüber autoenrolen.
- **connectedcourse** (`local\connectedcourse`): Anbindung/Erzeugung des mit
  einer Option verbundenen Moodle-Kurses (wählen, neu anlegen, aus Template
  kopieren).
- **User-/Subscriber-Selektoren** (6 Klassen): `user_selector_base`-Ableitungen
  für die Admin-UI „Nutzer buchen / entfernen" bzw. „Lehrkräfte abonnieren".
- **Kompetenzen** (`local\competencies\competencies_handler`): Lese-Cache für
  Nutzerkompetenzen, von Availability-Conditions zur Zugangsentscheidung genutzt.

**Grenzen:** Die eigentliche Buchungsmechanik (`booking_option::user_submit_response`,
`user_delete_response`, `booking_answers`-Schreiben, `booking_history`) liegt
außerhalb dieses Subsystems (S «booking_option»-Kern); S20 ruft sie nur auf.
Ebenso die Availability-Bewertung (`bo_availability\bo_info`) und das
Singleton-Caching (`singleton_service`).

## Position im Gesamtsystem

```
Cohort/Group-Events ─► booking_enrolment (Rules) ─► booking_option::user_submit_response/user_delete_response
   (observer)            │  Tasks: process_source_membership_adhoc                 │
                         └─► booking_sync_rules / _attempts (DB)                   ▼
Buchungs-UI ─► booking_potential/existing_user_selector ─► booking_answers   booking_answers + booking_history
Lehrer-UI   ─► potential/existing_subscriber_selector ──► booking_teachers
Kauf n Plätze ─► enrollink (erlid-Bundle) ─► enrollink.php (Self-Enrol) ─► user_submit_response (AUTOENROL)
Optionsformular ─► connectedcourse ─► core backup/restore + core_course_external (Kurs anlegen/kopieren)
Availability-Conditions ◄─ competencies_handler (Lesecache competency_usercomp)
```

## Schlüsselkonzepte

- **Sync-Regel (`booking_sync_rules`)**: Tupel (bookingoptionid, sourcetype
  cohort|group, sourceid) mit Flags `syncenrol`/`syncunenrol`, `conditionpolicy`
  (RESPECT=0 / OVERRIDE=1) und `isenabled`. Eine Regel „besitzt" die von ihr
  erzeugten Antworten über `booking_answers.syncruleid`.
- **Ownership / Safe-Unenrol**: Unenrol erfolgt nur, wenn die Antwort durch genau
  diese Regel angelegt wurde (`is_sync_owned_by_rule`), sonst
  `REASON_BLOCKED_NOT_SYNC_OWNED`. Manuelle Buchungen werden nie automatisch entfernt.
- **conditionpolicy**: RESPECT prüft Availability via `bo_info` und blockt sonst;
  OVERRIDE bucht per `MOD_BOOKING_BO_SUBMIT_STATUS_BOOKOTHEROPTION_FORCE`.
- **Delete-Modi** beim Regel-Löschen: `manualize` (syncruleid→0),
  `keeporphanruleid` (Antworten unangetastet), `unenrolsoftdelete`
  (Soft-Delete der Antworten).
- **Audit**: Jeder Versuch erzeugt eine `booking_sync_attempts`-Zeile (reasoncode
  REASON_*), erfolgreiche Mutationen zusätzlich `booking_history`-Einträge mit
  `syncaction`-Metadaten.
- **erlid-Bundle**: `booking_enrollink_bundles` (Hash erlid, places, baid,
  optionid, courseid) + `booking_enrollink_items` (konsumierte Plätze pro User).
  Freie Plätze = places − konsumierte Items.
- **user_selector_base**: Moodle-Kern-Suchcontrols; Ableitungen liefern
  `find_users($search)` mit gruppierten Ergebnissen.

## Datenfluss

**Membership-Sync (Event-getrieben):** Ein Kohorten-/Gruppen-Observer ruft
`booking_enrolment::queue_source_membership_sync()` → Adhoc-Task
`process_source_membership_adhoc` → `process_source_membership()` lädt alle aktiven
Regeln für (sourcetype, sourceid) und ruft je nach added/removed
`enrol_user_by_rule` / `unenrol_user_by_rule`. Retroaktiv:
`activate_rule(retroactive=true)` → `apply_rule_to_current_members()` (Batch-User-Fetch
+ `enrol_user_by_rule_with_user_cache`).

**enrollink:** Kauf mit `enrolusersaction`-Customform → `trigger_enrolbot_actions()`
legt Bundle an und feuert `enrollink_triggered`. Eingeladener Nutzer öffnet
`enrollink.php?erlid=…` → `enrollink::enrol_user()` prüft Blocking
(`enrolment_blocking`), Login/Guest, Doppel-Enrol, ruft `user_submit_response`
(AUTOENROL) und `add_consumed_item()` (dekrementiert Bundle-Antwort-Plätze).

**connectedcourse:** Optionsformular ruft `handle_user_choice()` →
wählen / `create_new_course_in_category` / `create_course_from_template_course`
(async backup/restore + Finalizer-Task).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| classes/local/sync/booking_enrolment.php | `local\sync\booking_enrolment` | Service (Sync-Engine) | 1241 | 21 | C | P1 |
| classes/enrollink.php | `enrollink` | Service/Domänenobjekt (Bundle) | 695 | 24 | C | P2 |
| classes/local/connectedcourse.php | `local\connectedcourse` | Service (Kurs-Provisioning) | 428 | 7 | C | P2 |
| classes/booking_user_selector_base.php | `booking_user_selector_base` | Form/Selector (abstrakt) | 122 | 3 | B | - |
| classes/booking_existing_user_selector.php | `booking_existing_user_selector` | Form/Selector | 123 | 2 | B | P3 |
| classes/booking_potential_user_selector.php | `booking_potential_user_selector` | Form/Selector | 172 | 2 | C | P3 |
| classes/subscriber_selector_base.php | `subscriber_selector_base` | Form/Selector (abstrakt) | 84 | 2 | B | - |
| classes/existing_subscriber_selector.php | `existing_subscriber_selector` | Form/Selector | 55 | 1 | A | - |
| classes/potential_subscriber_selector.php | `potential_subscriber_selector` | Form/Selector | 165 | 5 | B | - |
| classes/local/competencies/competencies_handler.php | `local\competencies\competencies_handler` | Service/Util (Cache) | 139 | 4 | B | - |

### `local\sync\booking_enrolment` (booking_enrolment.php)

**Verantwortung:** Zentrale Sync-Engine. CRUD von Sync-Regeln, Auflösung der
Quell-Mitglieder, regelbasiertes enrol/unenrol mit Bedingungs-/Ownership-Logik,
Audit-Logging und Cache-Invalidierung. Reine statische Service-Klasse.

**Kollaborateure:** `singleton_service` (option/settings), `booking_option`
(`user_submit_response`, `user_delete_response`, `booking_history_insert`,
`option_allows_booking_for_user`), `bo_availability\bo_info` (Bedingungsprüfung),
`task\process_source_membership_adhoc`, `cache_helper`/`cache` (syncrules),
Kern-Tabellen cohort/groups.

**Persistenz:** `booking_sync_rules`, `booking_sync_attempts`, liest/schreibt
`booking_answers` (syncruleid), Cache `mod_booking/syncrules` (Event
`setbacksyncrules`). Liest cohort_members/groups_members/cohort/groups/user.

**Konstanten:** CONDITION_POLICY_RESPECT/OVERRIDE, ACTION_ENROL/UNENROL,
REASON_* (ok/blocked_condition/blocked_capacity/blocked_invalid/
blocked_not_sync_owned/already_enrolled), DELETE_MODE_* (manualize/
keeporphanruleid/unenrolsoftdelete).

**Methoden-Inventar (alle static public, außer vermerkt):**
- `save_rules_from_form(int $optionid, stdClass $fromform): int` — upsert mehrerer Regeln aus Formular (cohortids/groupids); no-op wenn syncenabled leer.
- `save_single_rule(int $optionid, stdClass $data): int` — upsert/Validierung einer Regel, Duplikat-/Quellen-Check, gibt ruleid.
- `get_rule_for_option(int $optionid, int $ruleid): ?stdClass` — eine Regel inkl. Quellnamen/Label.
- `delete_rule(int $optionid, int $ruleid, string $mode): array{affected}` — löscht Regel; behandelt eigene Antworten je Delete-Modus (Transaktion).
- `apply_rule_to_current_members(int $ruleid): array` — retroaktiver Abgleich gegen aktuelle Mitglieder (Batch-Enrol + Unenrol verwaister Owned-Antworten).
- `source_exists(string, int): bool` — Existenzprüfung cohort/group.
- `get_source_member_ids(string, int): int[]` — Mitglieder-IDs einer Quelle.
- `get_rules_for_option(int $optionid): array` — Regeln einer Option, mit Cache + Batch-Anreicherung der Quellnamen.
- `update_rule_settings(int $ruleid, array $updates): bool` — Whitelist-Update (syncenrol/syncunenrol/conditionpolicy/isenabled).
- `activate_rule(int, int, bool $retroactive=false): array` — Regel aktivieren, optional sofort anwenden.
- `disable_rules_for_option(int $optionid): int` — alle Regeln einer Option deaktivieren.
- `process_source_membership(string, int, int, bool): void` — Event-Reaktion: matching Regeln enrol/unenrol.
- `queue_source_membership_sync(string, int, int, bool): void` — Adhoc-Task einreihen (async, off-request).
- `enrol_user_by_rule(stdClass $rule, int $userid): void` — Enrol eines Users gemäß Regel (Policy/Conditions/Capacity, Logging+History).
- `unenrol_user_by_rule(stdClass $rule, int $userid): void` — Unenrol nur bei Rule-Ownership, Soft-Delete via user_delete_response + History.
- `enrol_user_by_rule_with_user_cache(stdClass, int, ?stdClass $user=null): void` — wie enrol_user_by_rule, mit vorgeladenem User (Loop-Optimierung); **fast vollständige Kopie** von `enrol_user_by_rule`.
- `is_sync_owned_by_rule(stdClass $rule, int $userid): bool` — Antwort gehört dieser Regel?
- `has_active_booking_answer(int $optionid, int $userid): bool` — aktive Antwort (booked/waiting/reserved) vorhanden?
- `log_attempt(int, int, int, string $action, string $reasoncode, string $msg=''): void` — Audit-Insert in booking_sync_attempts.
- `get_recent_attempts_for_option(int $optionid, int $limit=20): array` — letzte Versuche mit User-/Regel-/Quellnamen (JOIN-SQL).

### `enrollink` (enrollink.php)

**Verantwortung:** Verwaltet „Lizenz-Bundles": ein zahlender Nutzer reserviert n
Plätze, generiert pro Bundle einen Hash (erlid) und ermöglicht Dritten das
Self-Enrolment über `enrollink.php`. Mischt Singleton-Instanz (pro erlid) mit
statischen Helfern für die Customform-`enrolusersaction`-Auswertung.

**Kollaborateure:** `singleton_service` (option/settings/answers/booking/user),
`booking_option::user_submit_response`, `bo_availability\bo_info` + `conditions\customform`,
`event\enrollink_triggered`, moodle_url/html_writer.

**Persistenz:** `booking_enrollink_bundles`, `booking_enrollink_items`, aktualisiert
`booking_answers` (places/json). Statischer Instanz-Cache `$instances`.

**Methoden-Inventar:**
- `get_instance(string $erlid): ?self` / `__construct` (private) / `destroy_instances(): bool` — Singleton-Verwaltung.
- `set_values(string $erlid): void` (private) — lädt Bundle+konsumierte Items, berechnet freeseats; setzt errorinfo bei Fehler.
- `free_places_left(): int` — freie Plätze (≥0).
- `get_bo_contextid(): int` — cmid der Option (Name irreführend: liefert cmid, nicht contextid).
- `enrol_user(int $userid): int` — Self-Enrol-Hauptpfad: Blocking/Login/Guest/Doppel-Check → user_submit_response (AUTOENROL); Statuscode.
- `add_consumed_item(int $userid, bool $initialuser=false): bool` — Item konsumieren, ggf. Bundle-Antwort-Plätze dekrementieren.
- `update_bookinganswer(string $erlid): bool` (private) — dekrementiert places der bundle-tragenden booking_answer.
- `get_enrollink_url/get_courselink_url/get_bookingdetailslink_url(): string` — URL-Bauer.
- `get_bookingoptiontitle(): string` — Optionstitel mit Präfix.
- `enrolment_blocking(): int` — Blocking-Statuscode (errorinfo/ungültig/keine Plätze).
- `get_readable_info($info): string` — lokalisierter String.
- `get_condition_block_description(int $userid): string` — Beschreibung der blockierenden Condition (setzt enrollink-Kontext-Flag).
- `get_courseid(): int` — Ziel-Kurs-ID.
- `create_enrollink($erlid): string` (static) — HTML-Link.
- `trigger_enrolbot_actions(int, int, object, object, int): bool` (static) — legt Bundle aus Customform-Antwort an, feuert enrollink_triggered.
- `enrolusersaction_applies(object): string` (static) — findet customform_enrolusersaction_*-Key.
- `enroluseraction_allows_enrolment(object, int): bool` (static) — prüft enroluserwhobookedcheckbox.
- `enrolmentstatus_waitinglist(booking_option_settings): bool` (static) — Warteliste vs. direkt.
- `is_initial_answer(object): bool` (static) — Initial-Antwort des Käufers?
- `get_erlid_from_baid(int): string` (static) — erlid zu booking_answer-id.
- `return_number_of_booked_licenses_from_booking_answer(object): int` (static) — Lizenzanzahl aus json.
- `update_number_of_booked_licenses_for_booking_answer(object, int): void` (static) — Lizenzanzahl in json/places aktualisieren.

### `local\connectedcourse` (connectedcourse.php)

**Verantwortung:** Erzeugt/verbindet den Moodle-Kurs einer Buchungsoption — wählen,
neu in Kategorie anlegen, oder asynchron aus Template-Kurs kopieren
(backup/restore via Core-Controller + Adhoc-Tasks). Findet getaggte Template-Kurse
für die Auswahl-UI.

**Kollaborateure:** Core `backup_controller`/`restore_controller`/`restore_dbops`,
`core\task\asynchronous_copy_task`, `mod_booking\task\finalize_template_course`,
`core_course_external` (Kategorien/Kurse anlegen), tag/course/context-Tabellen.

**Persistenz:** Schreibt course/course_categories indirekt über Core-APIs; liest
tag/tag_instance/course. Setzt `$newoption->courseid` / `$formdata->courseid`.

**Methoden-Inventar:**
- `create_course_from_template_course(stdClass &$newoption, stdClass &$formdata): void` (static) — async Kurs-Kopie aus Template (Backup+Restore-Controller, optional mit Usern/Rollen, Finalizer-Task).
- `handle_user_choice(stdClass &$newoption, stdClass &$formdata): void` (static) — Dispatch nach chooseorcreatecourse (0 nichts / 1 wählen / 2 neu / 3 Template).
- `retrieve_categoryid(stdClass &, stdClass &)` (private static) — Zielkategorie aus Config/Customfield/aktuell/Fallback.
- `create_new_course_in_category(stdClass &, stdClass &)` (private static) — leeren Kurs via core_course_external anlegen.
- `return_tagged_template_courses(string $query=''): array` (static) — getaggte Kurse (Config templatetags), Capability-/Enrol-Filter.
- `get_course_records($whereclause, $params): array` (protected static) — SQL-Helfer Kursliste.
- `clean_text($text): string` (private static) — Shortname-Normalisierung.

### User-Selektoren (Buchungs-UI)

`booking_user_selector_base` (abstrakt) hält bookingid/optionid/potentialusers/
course/cm und überschreibt `get_options()`/`set_potential_users()`.
Ableitungen:
- `booking_existing_user_selector::find_users($search)` — bereits gebuchte Nutzer
  (aus `potentialusers`, Institutions-Filter für Teacher); Gruppe „booked".
- `booking_potential_user_selector::find_users($search)` — buchbare Nutzer:
  enrolled (oder „bookanyone"), Gruppenmodus-Filter, Ausschluss bereits in
  `booking_answers` (nicht-deleted). Nutzt `booking::booking_get_groupmembers_sql`,
  `booking_check_if_teacher`.

### Subscriber-Selektoren (Lehrkräfte-Abo)

`subscriber_selector_base` (abstrakt) hält optionid/context/currentgroup.
Ableitungen:
- `existing_subscriber_selector::find_users($search)` — Nutzer aus `booking_teachers` zur Option.
- `potential_subscriber_selector` — potenzielle Lehrkräfte; Felder
  forcesubscribed/existingsubscribers, `find_users`, `set_existing_subscribers`,
  `set_force_subscribed`, `get_options`.

### `local\competencies\competencies_handler`

**Verantwortung:** Lese-Cache für Nutzerkompetenzen (statisch + MUC), genutzt von
Availability-Conditions zur Zugangsentscheidung.

**Persistenz:** Liest `competency_usercomp`/`competency`. Caches
`mod_booking/usercompetenciescache`, `mod_booking/competenciesshortnamescache`
+ statische Arrays.

**Methoden (alle static):** `get_user_competency_ids(int, ?int $timestamp=null): array`,
`get_competency_shortname_by_id(int): string`,
`user_has_competency(int, int, ?int=null): bool`, `reset_caches(): void`.

## Persistenz

- **booking_sync_rules** — Sync-Regeln (bookingoptionid, sourcetype, sourceid,
  syncenrol, syncunenrol, conditionpolicy, isenabled, time*/user*).
- **booking_sync_attempts** — Audit aller Enrol/Unenrol-Versuche (syncruleid,
  bookingoptionid, userid, action, reasoncode, reasonmessage, timecreated).
- **booking_answers** — Spalte `syncruleid` markiert sync-erzeugte Antworten;
  `places`/`json` von enrollink mutiert.
- **booking_enrollink_bundles** — Bundle (erlid-Hash, optionid, courseid, baid, places).
- **booking_enrollink_items** — konsumierte Bundle-Plätze pro userid (consumed).
- **booking_teachers** — Quelle der Subscriber-Selektoren.
- **Kern-Tabellen (read):** cohort, cohort_members, groups, groups_members, user,
  competency, competency_usercomp, tag, tag_instance, course, course_categories.
- **Caches:** `syncrules` (Event `setbacksyncrules`), `usercompetenciescache`,
  `competenciesshortnamescache`; statische Instanz-/Wert-Caches in enrollink und
  competencies_handler.

## Extension-Points

- **Sync-Quelle als Strategie via `sourcetype`** ('cohort'|'group') — neuer Typ
  erfordert Erweiterung mehrerer hartcodierter switch/if-Stellen (source_exists,
  get_source_member_ids, get_rule_for_option, get_recent_attempts_for_option) —
  kein sauberer Plugin-Point, sondern verstreute Fallunterscheidung.
- **Adhoc-Tasks**: `process_source_membership_adhoc`, `finalize_template_course`
  (außerhalb Scope, hier gequeued).
- **Event**: `enrollink_triggered` (Rules/Subscriber können andocken).
- **user_selector_base**: Kern-Erweiterungspunkt; `find_users()` als Override.
- **conditionpolicy / DELETE_MODE_* / REASON_***: erweiterbare Konstanten-Enums.
- **bo_info::set_enrollink_context()**: Flag-Schalter, über den enrollink den
  Availability-Bewertungskontext beeinflusst.

## Bekannte Schulden (→ Blueprint)

- **Massive Code-Duplikation in `booking_enrolment`**: `enrol_user_by_rule`
  (enrollment.php:708-867) und `enrol_user_by_rule_with_user_cache`
  (booking_enrolment.php:958-1120) sind nahezu identische ~160-Zeilen-Kopien
  inkl. der kompletten Condition-Reason-Auswertung. Gemeinsamen Kern extrahieren.
  (P1)
- **`booking_enrolment` God-Service**: 1241 LOC, 21 statische Methoden, vermischt
  Rule-CRUD, Membership-Auflösung, Enrol-Mechanik, Audit und Cache. Schwer
  testbar (durchweg static + `global $DB`, `singleton_service`-Kopplung). Aufteilen
  in rule_repository / sync_executor / audit_log. (P1)
- **Verstreute sourcetype-Fallunterscheidung** (cohort/group) als wiederholte
  if/switch statt Quell-Strategie: booking_enrolment.php:262-268, :447-459,
  :468-482, :1213-1233. (P2)
- **enrollink mischt Singleton-Instanz und statische Customform-Helfer**;
  `get_bo_contextid()` (enrollink.php:130) liefert irreführend die cmid statt
  einer contextid. json-/places-Manipulation an booking_answers verteilt. (P2)
- **`enrol_user`-Signatur-Drift**: enrollink.php:177 ruft
  `user_submit_response($user,$bookingid,1,STATUS_AUTOENROL,VERIFIED,$erlid)` mit
  6 Args, während booking_enrolment 10 Args nutzt — fragile Positionsparameter
  derselben Kern-API. (P2)
- **connectedcourse**: zwei fast gleiche Shortname-Unique-Schleifen
  (connectedcourse.php:71-76 und :289-294) und Kategorie-Logik mit tief
  verschachteltem `retrieve_categoryid`; `clean_text` nur in einem der beiden
  Pfade angewandt. (P2)
- **SQL-Injection-Risiko-Geruch** in `booking_potential_user_selector`:
  optionid direkt in den SQL-String interpoliert
  (booking_potential_user_selector.php:128 `ba.optionid = {$this->options['optionid']}`)
  statt als Parameter. (P3)
- **Fehlende dedizierte Unit-Tests** für die Selektor-find_users-Pfade und die
  enrollink-Konsumlogik (nur indirekte Coverage). (P3)
- **enrollink-Konstrukt-Fehlerpfad** schluckt Exceptions still
  (enrollink.php:104-107, setzt nur errorinfo) — diagnostisch dünn. (P3)
