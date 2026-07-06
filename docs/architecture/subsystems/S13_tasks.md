# S13 — tasks

## Zweck & Grenzen

Das Subsystem bündelt die 19 Cron-Einheiten von mod_booking unter `classes/task/`. Es enthält
sowohl **Scheduled Tasks** (periodisch via `db/tasks.php` registriert) als auch **Adhoc Tasks**
(einmalig per `\core\task\manager::queue_adhoc_task()` aus Buchungslogik, Rules-Engine und
Kampagnen enqueued). Funktional decken sie vier Aufgabengebiete ab:

- **Mail-Versand** (Legacy-Templates und Rule-getriebene Mails): `send_confirmation_mails`,
  `send_completion_mails`, `send_notification_mails`, `send_reminder_mails`, `send_mail_by_rule_adhoc`.
- **Enrolment & Buchungs-Lifecycle**: `enrol_bookedusers_tocourse`, `book_all_students_task`,
  `confirm_bookinganswer_by_rule_adhoc`, `process_source_membership_adhoc`, `assign_competency`,
  `remove_activity_completion`, `check_answers`.
- **Cleanup & Cache-Pflege**: `clean_booking_db`, `cleanup_invalid_scheduled_mails`,
  `purge_campaign_caches`, `delete_conditions_from_bookinganswer_by_rule_adhoc`.
- **Recalc / Datums-Pflege**: `recalculate_prices`, `task_adhoc_reset_optiondates_for_semester`,
  `finalize_template_course`.

**Grenze:** Die Task-Klassen sind durchweg **dünne Adapter** auf `\core\task\*_task`. Die fachliche
Logik liegt fast immer in extern referenzierten Service-/Domänenklassen (`message_controller`,
`booking_option`, `singleton_service`, `rules_info`, `price`, `checkanswers`, `book_all_students`,
`booking_enrolment`, `competencies`, `dates_handler`, `scheduledmails`). Diese gehören NICHT zum
Scope, werden aber als Kollaborateure benannt.

## Position im Gesamtsystem

Die Tasks sind die **asynchrone Ausführungsschicht** zwischen dem Moodle-Cron und der
Booking-Domäne. Eingänge:

- **Scheduled** (`db/tasks.php`): `remove_activity_completion` (jede Minute), `enrol_bookedusers_tocourse`
  (jede Minute), `send_reminder_mails` (stündlich :07), `send_notification_mails` (täglich 07:30),
  `clean_booking_db` (So 03:42), `cleanup_invalid_scheduled_mails` (täglich 02:00).
- **Adhoc**: aus der **Rules-Engine** (`booking_rules`-Aktionen erzeugen `send_mail_by_rule_adhoc`,
  `confirm_bookinganswer_by_rule_adhoc`, `delete_conditions_from_bookinganswer_by_rule_adhoc`), aus
  **Kampagnen** (`purge_campaign_caches`), aus der **Buchungsabwicklung** (`send_confirmation_mails`,
  `send_completion_mails`, `assign_competency`, `check_answers`, `book_all_students_task`,
  `process_source_membership_adhoc`), aus der **Semester-/Template-Verwaltung**
  (`task_adhoc_reset_optiondates_for_semester`, `finalize_template_course`, `recalculate_prices`).

Die Tasks lesen/schreiben direkt in `booking_*`-Tabellen und triggern Events (S aus dem Event-/Rules-
Subsystem), die wiederum neue Adhoc-Tasks erzeugen können (Feedback-Schleife Rules ↔ Tasks).

## Schlüsselkonzepte

- **Adapter-Pattern**: `get_name()` (Lang-String) + `execute()` (delegiert). Adhoc-Tasks lesen
  `$this->get_custom_data()` als `stdClass` und validieren Pflichtfelder defensiv.
- **Rule-Re-Validation vor Versand**: Die drei `*_by_rule_adhoc`-Tasks laden bei Ausführung den
  aktuellen `booking_rules`-Record neu, vergleichen `rulejson` gegen den zur Enqueue-Zeit
  gespeicherten Stand und rufen `rule->check_if_rule_still_applies(...)`. Bei Abweichung wird der
  Versand stillschweigend abgebrochen (Schutz gegen veraltete/geänderte Regeln).
- **Legacy-Mail-Gate**: `send_confirmation_mails`, `send_completion_mails`, `send_notification_mails`,
  `send_reminder_mails` brechen ab, wenn `get_config('booking','uselegacymailtemplates')` leer ist.
- **Self-rescheduling via Exception**: `finalize_template_course` wirft `moodle_exception`, um den
  Adhoc-Task mit Standard-Backoff erneut einzuplanen, solange der Async-Course-Copy noch läuft.
- **Repeat-Mechanik**: `send_mail_by_rule_adhoc` / `confirm_bookinganswer_by_rule_adhoc` re-triggern
  bei `$taskdata->repeat` die Rule erneut (`rule->execute()`), statt selbst zu versenden
  (Intervall-Versand `send_mail_interval`).
- **Debug-Fallback**: Bei Exceptions in den Rule-Tasks wird, falls `bookingdebugmode` gesetzt, ein
  `booking_debug`-Event getriggert statt zu werfen.
- **Cache-Pflege**: mehrere Tasks rufen `cache_helper::purge_by_event(...)` bzw.
  `booking_option::purge_cache_for_*` und `singleton_service::destroy_*`.

## Datenfluss

1. **Rule-Mail**: Rule-Aktion enqueued `send_mail_by_rule_adhoc` mit `optionid/userid/ruleid/rulejson/
   cmid/...` → Cron → Rule-Reload + JSON-Vergleich + `check_if_rule_still_applies` → bei `repeat`
   `rule->execute()`, sonst `message_controller(SEND_NOW, CUSTOM_MESSAGE,...)->send_or_queue()`.
2. **Enrolment (scheduled)**: `enrol_bookedusers_tocourse` selektiert Optionen mit `enrolmentstatus<1`
   und gestartetem Kurs → pro Option `booking_option::get_all_users_booked()` → `enrol_user()` →
   setzt `enrolmentstatus=1` (außer Elective).
3. **Kampagne**: `purge_campaign_caches` purged Caches, zerstört Campaign-Singletons, iteriert
   limitierte Optionen, vergleicht Buchungsstand vs. Pre-/Post-Transition-Maxanswers und triggert
   ggf. `sync_waiting_list()` + `check_if_free_to_book_again()`.
4. **Reminder (scheduled)**: `send_reminder_mails` liest Optionen/Optiondates mit `daystonotify`,
   prüft Zeitfenster, ruft `bookingoption->sendmessage_notification(...)`, setzt `sent`/`sent2`/
   `sentteachers`-Flags und triggert `reminder*_sent`-Events.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| assign_competency.php | assign_competency | Adhoc-Task (Adapter) | 72 | 2 | A | P3 |
| book_all_students_task.php | book_all_students_task | Adhoc-Task (Adapter) | 67 | 2 | A | - |
| check_answers.php | check_answers | Adhoc-Task (Adapter) | 77 | 2 | A | P3 |
| clean_booking_db.php | clean_booking_db | Scheduled-Task (Cleanup) | 78 | 2 | B | P3 |
| cleanup_invalid_scheduled_mails.php | cleanup_invalid_scheduled_mails | Scheduled-Task (Adapter) | 67 | 2 | A | - |
| confirm_bookinganswer_by_rule_adhoc.php | confirm_bookinganswer_by_rule_adhoc | Adhoc-Task (Rule, fachlich) | 239 | 2 | D | P1 |
| delete_conditions_from_bookinganswer_by_rule_adhoc.php | delete_conditions_from_bookinganswer_by_rule_adhoc | Adhoc-Task (Rule, fachlich) | 189 | 2 | C | P2 |
| enrol_bookedusers_tocourse.php | enrol_bookedusers_tocourse | Scheduled-Task (Enrol, fachlich) | 127 | 2 | C | P2 |
| finalize_template_course.php | finalize_template_course | Adhoc-Task (Re-Enrol, fachlich) | 160 | 2 | C | P2 |
| process_source_membership_adhoc.php | process_source_membership_adhoc | Adhoc-Task (Adapter) | 55 | 2 | A | - |
| purge_campaign_caches.php | purge_campaign_caches | Adhoc-Task (Cache+Recalc, fachlich) | 159 | 2 | C | P2 |
| recalculate_prices.php | recalculate_prices | Adhoc-Task (Recalc) | 105 | 2 | B | P3 |
| remove_activity_completion.php | remove_activity_completion | Scheduled-Task (Completion, fachlich) | 94 | 2 | C | P2 |
| send_completion_mails.php | send_completion_mails | Adhoc-Task (Mail-Adapter) | 100 | 2 | B | P3 |
| send_confirmation_mails.php | send_confirmation_mails | Adhoc-Task (Mail, fachlich) | 157 | 2 | C | P2 |
| send_mail_by_rule_adhoc.php | send_mail_by_rule_adhoc | Adhoc-Task (Rule-Mail, fachlich) | 203 | 2 | D | P1 |
| send_notification_mails.php | send_notification_mails | Scheduled-Task (Mail, fachlich) | 164 | 2 | C | P2 |
| send_reminder_mails.php | send_reminder_mails | Scheduled-Task (Mail, fachlich) | 279 | 5 | C | P2 |
| task_adhoc_reset_optiondates_for_semester.php | task_adhoc_reset_optiondates_for_semester | Adhoc-Task (Adapter) | 73 | 2 | A | - |

### assign_competency
- `public get_name(): \lang_string|string` — Lang-String `assigncompetency`.
- `public execute()` — validiert `cmid/optionid/userid` aus Custom-Data, delegiert an
  `competencies::assign_competencies()`.

### book_all_students_task
- `public get_name(): string` — Lang-String `bookallstudents`.
- `public execute(): void` — fordert `optionid`, setzt `$PAGE`-Context/URL (System) wegen
  Condition-Checks, delegiert an `book_all_students::execute()`, `mtrace` der Result-Zähler.

### check_answers
- `public get_name(): \lang_string|string` — Lang-String `taskcheckanswers`.
- `public execute()` — nur wenn `unenroluserswithoutaccessareyousure` UND
  `unenroluserswithoutaccess` gesetzt → `checkanswers::process_booking_option(optionid, check, action, userid)`.

### clean_booking_db
- `public get_name()` — Lang-String `taskcleanbookingdb`.
- `public execute()` — löscht verwaiste `booking_optiondates_teachers`- und `booking_teachers`-Records
  via `delete_records_select` mit Subselects, purged `setbackcachedteachersjournal`.
  Schuld: roher Subquery-SQL ohne Indexschutz (`clean_booking_db.php:61`,`:65`); TODO-Marker `:76`.

### cleanup_invalid_scheduled_mails
- `public get_name()` — Lang-String `taskcleanupinvalidscheduledmails`.
- `public execute()` — delegiert vollständig an `scheduledmails::cleanup_invalid_tasks_in_context(1)`
  (hartkodierter `contextid=1`/System), `mtrace` der Statistik.

### confirm_bookinganswer_by_rule_adhoc
- `public get_name()` — Lang-String `taskconfirmbookinganswerbymailbyruleadhoc`.
- `public execute()` — **lange fachliche Methode** (~175 LOC). Reload Rule, JSON-Vergleich,
  `check_if_rule_still_applies`, Repeat-Branch, dann je nach `confirmationonnotification` und
  `price::get_price`: entweder `option->user_submit_response(...)` (preisfrei) oder
  `booking_option::write_user_answer_to_db(...)` (Confirmation-Status) inkl. Exklusiv-Modus
  (`==2`): Un-Confirm aller anderen Wartelisten-User. Exception → optional `booking_debug`-Event.
  Schuld: Monster-`execute` mit tiefer Verschachtelung und mehreren Verantwortlichkeiten
  (`confirm_bookinganswer_by_rule_adhoc.php:64-238`), direkte `booking_answers`-DB-Reads/Writes
  (`:135`,`:171`,`:188`,`:198`), keine Unit-Tests sichtbar.

### delete_conditions_from_bookinganswer_by_rule_adhoc
- `public get_name()` — Lang-String `deletedatafrombookingansweradhoc`.
- `public execute()` — Reload Rule (`rulename === 'days_before'`), JSON-Vergleich, Re-Validation,
  liest Answer aus Cache (`singleton_service::get_instance_of_booking_answers`), entfernt
  `condition_customform` aus dem JSON wenn User-/Admin-Lösch-Flag gesetzt, `update_record` +
  `bookinganswercustomformconditions_deleted`-Event. Exception → optional `booking_debug`.
  Schuld: JSON-Manipulation + DB-Write + Event in einer Methode (`:120-163`); `$USER`-Zugriff in
  Cron-Kontext (`:149`).

### enrol_bookedusers_tocourse
- `public get_name()` — Lang-String `taskenrolbookeduserstocourse`.
- `public execute()` — selektiert Optionen mit `enrolmentstatus<1` und vergangenem/leerem
  `coursestarttime`, holt pro Option `booking_option`-Singleton, iteriert
  `get_all_users_booked()`, prüft Elective-Reihenfolge (`elective::check_if_allowed_to_inscribe`),
  `enrol_user()`, setzt `enrolmentstatus=1` (außer Elective). Schuld: verschachtelte Doppelschleife
  mit DB-Update pro User (`enrol_bookedusers_tocourse.php:99-124`); TODO-Kommentar zu fehlendem
  Bool-Return von `enrol_user` (`:113`).

### finalize_template_course
- `public get_name()` — Lang-String `taskfinalizetemplatecourse`.
- `public execute()` — prüft via `backup_controllers`/`task_adhoc`-Join, ob Async-Copy noch läuft →
  wirft `moodle_exception` zum Reschedule; entfernt sonst alle Tags vom Kopier-Kurs und re-enrolt
  gebuchte User, Responsible-Contacts und Teacher (spiegelt `booking_option::update`-Nachsave-Logik).
  Schuld: dupliziert Enrolment-Logik aus mehreren anderen Klassen (`finalize_template_course.php:129-155`),
  roher Backup-Join-SQL (`:83-88`).

### process_source_membership_adhoc
- `public get_name(): string` — Lang-String `taskprocesssourcemembershipsyncadhoc`.
- `public execute(): void` — validiert `sourcetype/sourceid/userid`, delegiert an
  `booking_enrolment::process_source_membership(...)` (Cohort/Group-Sync).

### purge_campaign_caches
- `public get_name()` — Lang-String `taskpurgecampaigncaches`.
- `public execute()` — purged `setbackoptionstable`/`setbackoptionsettings`/`setbackprices`
  (erstes optional via `skipsetbackoptionstable`); bei `campaignid` in Custom-Data: zerstört
  Campaign-Singletons, iteriert alle Optionen mit `maxanswers>0`, berechnet Pre-Transition-Kapazität
  (Start vs. Ende via `limitfactor`), triggert bei freigewordenen Plätzen `sync_waiting_list()` +
  `booking_option::check_if_free_to_book_again()`. Schuld: Schleife über ALLE limitierten Optionen
  systemweit, mit Per-Option-Singleton-Auf-/Abbau (`purge_campaign_caches.php:104-155`) — potenziell
  teuer bei vielen Optionen.

### recalculate_prices
- `public get_name()` — Lang-String `taskrecalculateprices`.
- `public execute()` — pro Instanz (`cmid` aus Custom-Data) über alle Optionen und Preiskategorien:
  `price::calculate_price_with_bookingoptionsettings()` + `price::add_price()` (überspringt
  `priceformulaoff`). Schuld: keine Behandlung leerer `cmid` (`recalculate_prices.php:69`).

### remove_activity_completion
- `public get_name()` — Lang-String `taskremoveactivitycompletion`.
- `public execute()` — SQL-Join über `booking_answers`/`booking_options`/`booking`, findet
  abgeschlossene Antworten älter als `removeafterminutes`, setzt `completed=0`, aktualisiert
  `completion_info->update_state(COMPLETION_INCOMPLETE)`. Schuld: Per-Row-Re-Queries von course/
  booking/cm in der Schleife (`remove_activity_completion.php:72-77`), N+1.

### send_completion_mails
- `public get_name()` — Lang-String `tasksendcompletionmails`.
- `public execute()` — Legacy-Gate, dann `message_controller(SEND_NOW, COMPLETED,...)->send_or_queue()`;
  wirft `coding_exception` bei fehlender Custom-Data.

### send_confirmation_mails
- `public get_name()` — Lang-String `tasksendconfirmationmails`.
- `public execute()` — Legacy-Gate; baut Confirmation-Mail aus `$taskdata` (userto/userfrom/subject/
  messagetext/html/ics-Attachment), `email_to_user()` mit try/catch (SMTP), löscht Attachment nur
  wenn kein weiterer Adhoc-Task es referenziert (`customdata LIKE`), triggert `message_sent`-Event
  + purged `setbackeventlogtable`. Schuld: direkter `email_to_user` statt `message_controller`,
  Attachment-Lebenszyklus via String-LIKE auf `task_adhoc.customdata` (`send_confirmation_mails.php:106`),
  tiefe Verschachtelung.

### send_mail_by_rule_adhoc
- `public get_name()` — Lang-String `tasksendmailbyruleadhoc`.
- `public execute()` — **fachlich, ~140 LOC**: Reload Rule, JSON-Vergleich inkl. cmid-Drift,
  Abbruch-Heuristik je nach `rulename` (für `rule_daysbefore`/`rule_specifictime` immer abort,
  sonst Detail-Vergleich `actiondata`/`ruledata`), `check_if_rule_still_applies` mit optionalem
  `optiondateid`, Repeat-Branch (`rule->execute()`), sonst `message_controller(SEND_NOW,
  CUSTOM_MESSAGE,...)->send_or_queue()` mit Raten-/Preis-Parametern. Exception → optional
  `booking_debug`. Schuld: hohe Methodenkomplexität, duplizierte Rule-Reload-Logik mit
  `confirm_bookinganswer_by_rule_adhoc` (`send_mail_by_rule_adhoc.php:75-201`), 14-Argument-
  `message_controller`-Konstruktion (`:152-168`).

### send_notification_mails
- `public get_name()` — Lang-String `tasksendnotificationmails`.
- `public execute()` — Legacy-Gate; iteriert `booking_answers` mit `waitinglist=NOTIFYMELIST`:
  löscht abgelaufene Einträge (Option vorbei) + purged Answers-Cache, prüft `fullybooked`, baut
  Option-/Unsubscribe-URLs, versendet via `message_controller`. Schuld: `return` statt `continue`
  in der Schleife bei vergangener Option bricht die GESAMTE Schleife ab
  (`send_notification_mails.php:106`,`:111`) — möglicher Bug.

### send_reminder_mails
- `public get_name()` — Lang-String `tasksendremindermails`.
- `public execute()` — Legacy-Gate; verarbeitet Optionen mit `daystonotify`/`daystonotify2`,
  setzt `sent`/`sent2`-Flags, triggert `reminder1_sent`/`reminder2_sent`; ruft danach
  `send_session_notifications()` und (PRO) `send_teacher_notifications()`.
- `private send_session_notifications()` — Optiondates mit `daystonotify`, setzt `sent`-Flag.
- `private send_teacher_notifications()` — Optionen mit `daystonotifyteachers`, setzt
  `sentteachers`-Flag, triggert `reminder_teacher_sent`.
- `private send_notification(int $messageparam, stdClass $record, int $daystonotify): bool` —
  Zeitfenster-Check, holt `booking_option`-Singleton, ruft `sendmessage_notification` je nach
  Message-Param (Session/Teacher/Participant). Schuld: drei nahezu identische SQL+Loop+Flag-Blöcke
  (`send_reminder_mails.php:77-128`,`:148-169`,`:179-220`) — strukturelle Duplizierung; größte
  Datei im Subsystem.

### task_adhoc_reset_optiondates_for_semester
- `public get_name()` — Lang-String `taskadhocresetoptiondatesforsemester`.
- `public execute()` — delegiert an `dates_handler::change_semester(cmid, semesterid)`, purged
  `setbackoptionstable`/`setbackoptionsettings`.

## Persistenz

- **Direkte DB-Tabellen**: `booking_options` (enrolmentstatus, sent/sent2/sentteachers, maxanswers,
  Preis-/Datums-Selects), `booking_answers` (Status/json/completed/waitinglist),
  `booking_optiondates`, `booking_optiondates_teachers`, `booking_teachers`, `booking_rules`,
  `booking` (daystonotify*), `course`/`course_modules`/`modules`, `task_adhoc`/`backup_controllers`
  (finalize_template_course), `user`.
- **Caches**: `cache_helper::purge_by_event` für `setbackcachedteachersjournal`, `setbackoptionstable`,
  `setbackoptionsettings`, `setbackprices`, `setbackeventlogtable`;
  `booking_option::purge_cache_for_option/for_answers`; `singleton_service::destroy_*`.
- **Custom-Data**: alle Adhoc-Tasks lesen ihren Zustand aus `get_custom_data()` (stdClass), kein
  eigener Tabellen-State.

## Extension-Points

- **`db/tasks.php`**: registriert die 6 Scheduled Tasks mit Cadence (admin-überschreibbar).
- **`\core\task\manager::queue_adhoc_task()`**: Standard-Moodle-Eingang; Booking-Domäne, Rules-Engine
  und Kampagnen enqueuen Adhoc-Tasks.
- **Rules-Engine-Kopplung**: `rules_info::get_rule()` + `rule->check_if_rule_still_applies()` /
  `rule->execute()` — neue Rule-Aktionen können diese drei `*_by_rule_adhoc`-Tasks wiederverwenden.
- **Legacy-Mail-Schalter** (`uselegacymailtemplates`) und diverse `get_config('booking', ...)`-Gates
  (z. B. `skipsetbackoptionstable`, `bookingdebugmode`) als Verhaltens-Toggles.
- **Events** als Sekundär-Extension: `message_sent`, `reminder1_sent`, `reminder2_sent`,
  `reminder_teacher_sent`, `bookinganswercustomformconditions_deleted`, `booking_debug`.

## Bekannte Schulden (→ Blueprint)

- **P1 — Rule-Adhoc-Monster & Duplizierung**: `confirm_bookinganswer_by_rule_adhoc::execute`
  (`:64-238`) und `send_mail_by_rule_adhoc::execute` (`:65-201`) teilen denselben Reload/JSON-
  Vergleich/Re-Validate-Boilerplate, sind aber copy-paste statt geteilte Basisklasse/Trait;
  beide mit überlangen `execute`-Methoden und vielen Verantwortlichkeiten. Kandidat für
  gemeinsame `abstract rule_adhoc_task`.
- **P2 — `send_notification_mails` Loop-Abbruch-Bug**: `return` statt `continue` bei vergangener/
  gelöschter Option (`send_notification_mails.php:106`,`:111`) bricht den gesamten Cron-Lauf ab —
  potenzieller Funktionsfehler, sollte verifiziert werden.
- **P2 — N+1/Per-Row-Queries**: `remove_activity_completion` (`:72-77`),
  `enrol_bookedusers_tocourse` (`:99-124`), `purge_campaign_caches` (`:104-155`,
  systemweite Schleife über alle limitierten Optionen). Skalierungsrisiko bei großen Instanzen.
- **P2 — `send_reminder_mails` Strukturduplizierung**: drei nahezu identische SQL+Loop+Flag-Blöcke
  (Participant/Session/Teacher), extraktionsreif.
- **P2 — `send_confirmation_mails` Attachment-Lebenszyklus**: Löschung via String-`LIKE` auf
  `task_adhoc.customdata` (`:106`) ist fragil; direkter `email_to_user` umgeht `message_controller`.
- **P3 — Roher Cleanup-SQL ohne Indexgarantie**: `clean_booking_db` Subselects (`:61`,`:65`),
  TODO-Marker (`:76`).
- **Querschnitt — fehlende Tests/Testbarkeit**: keine Unit-Tests im Scope sichtbar; statische
  God-Calls (`singleton_service::*`, `booking_option::*`, `price::*`) erschweren Mocking. Cron-Kontext
  greift teils auf `$USER`/`$PAGE` zu (`book_all_students_task.php:53`,
  `delete_conditions_..._adhoc.php:149`).
