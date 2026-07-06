# S06 — booking_rules

## Zweck & Grenzen

Das Subsystem `booking_rules` ist die event-getriebene Automatisierungs-Engine von
mod_booking nach dem Muster **„Wenn X, dann (für Empfängerkreis Z) Y"**. Eine *Rule*
besteht immer aus drei orthogonalen Bausteinen, die über ein gemeinsames `rulejson`
in der Tabelle `booking_rules` serialisiert werden:

1. **Rule (Trigger)** — *wann* wird ausgelöst? Reaktion auf ein Moodle-Event
   (`rule_react_on_event`) oder zeitbasiert relativ zu einem Datumsfeld
   (`rule_daysbefore`, `rule_specifictime`).
2. **Condition (Empfängerauswahl)** — *wer* ist betroffen? Liefert per SQL-Fragment
   die `userid`s (Teilnehmer, Trainer, Event-User, Vorgesetzte, Custom-Profilfeld-Match …).
3. **Action (Wirkung)** — *was* passiert? Versand von E-Mails (sofort/Intervall/Kopie),
   Bestätigung von Buchungsantworten, Löschen von Bedingungen aus Buchungsantworten.

Grenzen: Das Subsystem **erzeugt nur Adhoc-Tasks** (`\mod_booking\task\*_by_rule_adhoc`)
und delegiert die eigentliche Zustellung/Wirkung an diese Tasks (außerhalb des Scope,
in `classes/task/`). Es persistiert/rendert die Regel-Definitionen, baut die Form-UI
zusammen, sammelt Events während eines Requests und triggert die Ausführung. Die
**Platzhalter-Ersetzung** (`placeholders_info`), die **Listen-Renderer**
(`output\ruleslist`), die **Template-Registry** (`local\templaterule`) und der
**pro-Option-Opt-out** (`option\fields\applybookingrules`) liegen außerhalb dieses
Scope, werden aber zentral genutzt.

## Position im Gesamtsystem

```
Moodle-Event (observer)        Form (rule_form / settings)        Cron / Save
        │                              │                                │
        ▼                              ▼                                ▼
rules_info::collect_rules_for_execution   rules_info::add_rules_to_mform   rules_info::execute_rules_for_*
        │  (sammelt in $rulestoexecute)        │ (Rule+Condition+Action)        │
        ▼                              ▼                                ▼
rules_info::filter_rules_and_execute   conditions_info / actions_info   booking_rule::execute()
        │  (cancelrules-Auflösung)                                         │
        ▼                                                                  ▼
booking_rule::execute()  ──►  condition::execute() baut SQL  ──►  action::execute() queued Adhoc-Task
```

- **Eingang Event**: Der Event-Observer ruft `rules_info::collect_rules_for_execution()`
  pro Event auf; am Request-Ende führt `filter_rules_and_execute()` die nicht
  gegenseitig stornierten Regeln aus.
- **Eingang Zeit/Save**: `execute_rules_for_option()`, `execute_rules_for_user()`,
  `execute_booking_rules()` (re-applizieren `rule_daysbefore`/`rule_specifictime`).
- **Discovery**: `*_info`-Klassen instanzieren Rules/Conditions/Actions per `glob()` über
  ihre Verzeichnisse plus Klassen aus `bookingextension_*`-Subplugins (Extension-Point).

## Schlüsselkonzepte

- **Drei-Schichten-rulejson**: Ein einziges JSON-Objekt trägt `rulename`/`ruledata`,
  `conditionname`/`conditiondata`, `actionname`/`actiondata`, dazu zur Laufzeit
  `datafromevent` (serialisierter Event) und `intervaldata`/`confirmdata`
  (Ketten-Zustand). `save_*`-Methoden schreiben nur in dieses JSON; allein die Rule
  schreibt am Ende per `save_rule()` in die DB.
- **SQL-Komposition**: Rule baut ein `$sql`-stdClass-Skelett (`select/from/where/sort`),
  Condition mutiert es per Referenz und hängt einen User-Join + `uniqueid`-CONCAT an.
  Ergebnis ist eine Record-Liste mit `optionid/cmid/userid` (+ optional `optiondateid`,
  `baid`, `datefield`, `secondstonotify`, Payment-Felder).
- **Adhoc-Task-Indirektion**: Actions persistieren nichts direkt, sondern queuen via
  `\core\task\manager::reschedule_or_queue_adhoc_task()`. Der Task ruft beim Lauf
  `booking_rule::check_if_rule_still_applies()` zurück (Re-Validierung gegen aktuelle
  Daten, Testmode-SQL ohne Zeitfilter).
- **Interval-/Confirm-Ketten**: `send_mail_interval` und `confirm_bookinganswer`
  implementieren ein „one-user-at-a-time"-Muster über `counter` + `usersalreadytreated`
  im rulejson; der zweite User triggert eine Repeat-Task, die die Regel gegen eine
  frische Wartelisten-Query neu ausführt (fängt Nachzügler ein).
- **cancelrules**: Eine Regel kann beim Event andere Regeln im selben Request
  stornieren (`ruledata->cancelrules`), aufgelöst in `filter_rules_and_execute()` über
  die statischen Akkumulatoren `$rulestoexecute`/`$rulestocancel`.
- **Kontext-Vererbung**: Regeln gelten je `contextid` (1 = System global, sonst
  CONTEXT_MODULE). Auswahl per `context->path`-LIKE bzw. Pfad-Array-Filter, sodass
  System-Regeln auf alle Module durchschlagen.
- **DB-Dialekt-Verzweigungen**: Mehrere Conditions enthalten getrennte
  Postgres-/MySQL-/MariaDB-SQL-Zweige (JSON_TABLE vs. LATERAL, String-Split).

## Datenfluss

**Speichern (Form → DB):** `rules_info::save_booking_rule($data)` holt Rule/Condition/Action
per Name, ruft `condition->save_condition()` und `action->save_action()` (füllen
`$data->rulejson`), zuletzt `rule->save_rule()` (INSERT/UPDATE in `booking_rules`).
Direkt danach `execute_booking_rules($ruleid)` (re-queued zeitbasierte Tasks).

**Event-getrieben:** Event → `collect_rules_for_execution()` dekodiert Event-Daten,
ermittelt `optionid` (aus `other.optionid`/`objectid`/`itemid`), holt kontext+
eventname-gefilterte Regeln, merged Companion-Interval-Regeln für späte
Wartelisten-Beitritte, schreibt `datafromevent` ins rulejson und sammelt in
`$rulestoexecute`. Am Request-Ende `filter_rules_and_execute()` → `rule->execute()`.

**Zeit-getrieben:** `rule_daysbefore`/`rule_specifictime`::`execute()` → `apply_rule()`-Gate
→ `get_records_for_execution()` baut Datums-SQL (mit DST-korrektem `strtotime`,
optiondate-`daystonotify`-Override) → Condition-SQL → pro Record `nextruntime`
berechnen → `action->execute()` queued `send_mail_by_rule_adhoc` mit `nextruntime`.

**Task-Lauf (außerhalb Scope):** Adhoc-Task ruft `rule->check_if_rule_still_applies()`;
nur bei Match wird die Mail tatsächlich versandt (Platzhalter aufgelöst).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| booking_rule.php | `booking_rule` (Interface) | Vertrag Rule/Trigger | 103 | 8 | A | P3 |
| booking_rule_condition.php | `booking_rule_condition` (Interface) | Vertrag Condition | 96 | 8 | A | P3 |
| booking_rule_action.php | `booking_rule_action` (Interface) | Vertrag Action | 97 | 8 | A | P3 |
| booking_rules.php | `booking_rules` | Repository/Renderer für gespeicherte Regeln, Kontext-Auflösung | 189 | 6 | B | P2 |
| rules_info.php | `rules_info` | Zentraler Orchestrator: Discovery, Save, Event-Sammlung, Filter & Execute | 687 | 16 | D | P1 |
| conditions_info.php | `conditions_info` | Discovery + Form-Aggregation für Conditions | 153 | 3 | B | P2 |
| actions_info.php | `actions_info` | Discovery + Form-Aggregation für Actions | 149 | 3 | B | P2 |
| rules/rule_react_on_event.php | `rule_react_on_event` | Event-Trigger-Rule, größter Event-Adapter | 679 | 11 | D | P1 |
| rules/rule_daysbefore.php | `rule_daysbefore` | Zeit-Trigger (Tage vor/nach Datumsfeld) | 497 | 9 | C | P2 |
| rules/rule_specifictime.php | `rule_specifictime` | Zeit-Trigger (Sekunden vor/nach), Nachfolger v. daysbefore | 534 | 9 | C | P2 |
| actions/send_mail.php | `send_mail` | Action: sofortiger Mailversand (+ical) via Adhoc | 243 | 9 | B | P2 |
| actions/send_mail_interval.php | `send_mail_interval` | Action: gestaffelter Mailversand (Warteliste-Kette) | 279 | 9 | C | P1 |
| actions/send_copy_of_mail.php | `send_copy_of_mail` | Action: Kopie einer Event-Mail an Empfängerkreis | 235 | 9 | C | P2 |
| actions/confirm_bookinganswer.php | `confirm_bookinganswer` | Action: WL-Bestätigung als one-at-a-time-Kette | 240 | 11 | C | P1 |
| actions/delete_conditions_from_bookinganswer.php | `delete_conditions_from_bookinganswer` | Action: Bedingungen aus booking_answer löschen | 157 | 9 | B | P3 |
| conditions/select_users.php | `select_users` | Cond: explizit gewählte User-IDs | 202 | 8 | B | P3 |
| conditions/select_student_in_bo.php | `select_student_in_bo` | Cond: Teilnehmer nach Buchungsstatus | 227 | 8 | C | P2 |
| conditions/select_teacher_in_bo.php | `select_teacher_in_bo` | Cond: Trainer der Option | 159 | 8 | B | P3 |
| conditions/select_responsible_contact_in_bo.php | `select_responsible_contact_in_bo` | Cond: verantwortliche Kontakte (CSV-Split, DB-Dialekt) | 199 | 8 | C | P2 |
| conditions/select_user_from_event.php | `select_user_from_event` | Cond: Auslöser-/betroffener User des Events | 261 | 9 | C | P2 |
| conditions/select_user_shopping_cart.php | `select_user_shopping_cart` | Cond: User aus Ratenzahlung (shopping_cart JSON-SQL) | 313 | 8 | D | P1 |
| conditions/select_users_from_userfield_of_eventuser.php | `select_users_from_userfield_of_eventuser` | Cond: User-IDs aus Profilfeld des Event-Users | 218 | 8 | C | P2 |
| conditions/select_deputy_of_supervisor.php | `select_deputy_of_supervisor` | Cond: Stellvertreter aus Vorgesetzten-Profilfeld | 248 | 8 | C | P2 |
| conditions/select_booking_manager.php | `select_booking_manager` | Cond: Booking-Manager der Instanz | 171 | 8 | B | P3 |
| conditions/enter_userprofilefield.php | `enter_userprofilefield` | Cond: Match gegen eingegebenen Profilfeld-Wert | 248 | 8 | C | P2 |
| conditions/match_userprofilefield.php | `match_userprofilefield` | Cond: Profilfeld == Optionsfeld | 248 | 8 | C | P2 |
| rules/templates/ruletemplate_bookingoption_booked.php | `ruletemplate_bookingoption_booked` | Seed-Template (id 1) | 94 | 2 | A | - |
| rules/templates/ruletemplate_confirmwaitinglist.php | `ruletemplate_confirmwaitinglist` | Seed-Template (id 2) | 94 | 2 | A | - |
| rules/templates/ruletemplate_daysbeforestart.php | `ruletemplate_daysbeforestart` | Seed-Template (id 3) | 91 | 2 | A | - |
| rules/templates/ruletemplate_trainerreminderbeforestart.php | `ruletemplate_trainerreminderbeforestart` | Seed-Template (id 4) | 89 | 2 | A | - |
| rules/templates/ruletemplate_userstorno.php | `ruletemplate_userstorno` | Seed-Template (id 5) | 94 | 2 | A | - |
| rules/templates/ruletemplate_courseupdate.php | `ruletemplate_courseupdate` | Seed-Template (id 6) | 94 | 2 | A | - |
| rules/templates/ruletemplate_userpoll.php | `ruletemplate_userpoll` | Seed-Template (id 7) | 92 | 2 | A | - |
| rules/templates/ruletemplate_trainerpoll.php | `ruletemplate_trainerpoll` | Seed-Template (id 8) | 90 | 2 | A | - |
| rules/templates/ruletemplate_bookingoptioncompleted.php | `ruletemplate_bookingoptioncompleted` | Seed-Template (id 9) | 94 | 2 | A | - |
| rules/templates/ruletemplate_usercancellation.php | `ruletemplate_usercancellation` | Seed-Template (id 10) | 94 | 2 | A | - |
| rules/templates/ruletemplate_sessionreminders.php | `ruletemplate_sessionreminders` | Seed-Template (id 11) | 84 | 2 | A | - |
| rules/templates/ruletemplate_trainercancellation.php | `ruletemplate_trainercancellation` | Seed-Template (id 12) | 92 | 2 | A | - |
| rules/templates/ruletemplate_paymentconfirmation.php | `ruletemplate_paymentconfirmation` | Seed-Template (id 13) | 94 | 2 | A | - |
| rules/templates/ruletemplate_bookingoptionuncompleted.php | `ruletemplate_bookingoptionuncompleted` | Seed-Template (id 14) | 87 | 2 | A | - |
| rules/templates/ruletemplate_optiondatesteacheradded.php | `ruletemplate_optiondatesteacheradded` | Seed-Template (id 15) | 88 | 2 | A | - |
| rules/templates/ruletemplate_optiondatesteacherdeleted.php | `ruletemplate_optiondatesteacherdeleted` | Seed-Template (id 16) | 88 | 2 | A | - |

### Interfaces

**`booking_rule`** (booking_rule.php) — Vertrag aller Trigger-Rules. Methoden:
`add_rule_to_mform()`, `get_name_of_rule(bool):string`, `save_rule(stdClass&)`,
`set_defaults()`, `set_ruledata(record)`, `set_ruledata_from_json(json)`,
`execute(optionid,userid)`, `check_if_rule_still_applies(optionid,userid,nextruntime,optiondateid):bool`.

**`booking_rule_condition`** (booking_rule_condition.php) — Vertrag der Empfängerauswahl.
`can_be_combined_with_bookingruletype(string):bool`, `add_condition_to_mform()`,
`get_name_of_condition()`, `save_condition(stdClass&):void`, `set_defaults()`,
`set_conditiondata()`, `set_conditiondata_from_json()`, `execute(stdClass&$sql, array&$params):void`.

**`booking_rule_action`** (booking_rule_action.php) — Vertrag der Wirkung.
`add_action_to_mform()`, `get_name_of_action()`, `is_compatible_with_ajaxformdata(array)`,
`save_action(stdClass&)`, `set_defaults()`, `set_actiondata()`, `set_actiondata_from_json()`,
`execute(stdClass $record)`.

### `rules_info` (rules_info.php) — Orchestrator

Statische Sammelpunkte: `$rulestoexecute`, `$rulestocancel`, `$eventstoexecute`.

- `add_rules_to_mform(mform&, repeateloptions&, ajaxformdata&)` (static) — baut das
  komplette Regel-Formular (Name, Template-Select, Aktiv-Flag, Rule-Typ) und delegiert
  an `conditions_info`/`actions_info`. ~rules_info.php:76.
- `get_rules()` / `get_rule(name)` (static) — instanziert alle Rule-Klassen per `glob()`
  über `rules/*.php` plus `bookingextension_*`-Namespace-Scan. :188 / :228.
- `set_data_for_form(data&)` (static) — lädt Record (DB oder negativer Template-Id via
  `templaterule`), ruft `set_defaults()` von Condition/Action/Rule. :249.
- `save_booking_rule(data&):int` (static) — siehe Datenfluss Speichern. :289.
- `execute_booking_rules(ruleid=0)` (static) — lädt Records, `set_ruledata` + `execute`. :315.
- `execute_rules_for_option(optionid,userid)` / `execute_rules_for_user(userid)` (static) —
  re-appliziert nicht-event-basierte Regeln (skip `rule_react_on_event`). :340 / :380.
- `delete_rule(ruleid)` (static) — DELETE aus `booking_rules`. :401.
- `collect_rules_for_execution(\core\event\base $event)` (static) — Kern des
  Event-Eingangs: Fremd-Component-Gate, optionid-Auflösung, Companion-Merge,
  `datafromevent`-Injektion, Sammeln. :414.
- `filter_rules_and_execute()` (static) — cancelrules-Auflösung + Ausführung. :480.
- `get_companion_interval_rules_for_waitinglist_join()` (private static) — Reuse von
  freetobookagain-Ketten bei spätem WL-Beitritt. :545.
- `option_is_fully_booked()` / `interval_rule_has_active_tasks()` (private static) —
  Helfer für Companion-Logik (instanziert/zerstört Singletons, scannt Adhoc-Tasks). :590/:608.
- `proceed_with_event()` (private static) — Whitelist für `local_shopping_cart`-Events. :639.
- `events_to_execute()` / `destroy_singletons()` (static) — deferred Events ausführen
  bzw. statische Akkumulatoren zurücksetzen. :668 / :682.

### `booking_rules` (booking_rules.php) — Repository

- `get_rendered_list_of_saved_rules(contextid, enableaddbutton)` (static) — rendert
  `output\ruleslist`. :55.
- `get_list_of_saved_rules(contextid=0)` (static) — UNION-Query (Module + System-Kontext),
  cached statisch in `$rules`. :73.
- `get_list_of_saved_rules_by_optionid(optionid, eventname)` (static) — Auflösung über
  `singleton_service` → Kontext. :110.
- `get_list_of_saved_rules_by_context(contextid, eventname)` (static) — Pfad-Array-Filter
  (Kontext-Vererbung) + optionaler eventname-Filter. :129.
- `delete_rules_by_context(contextid)` (static) — Massenlöschung (System-Kontext-Bremse). :158.
- `booking_matches_rulecontext(bookingcmid, contextid)` (static) — Match-Prüfung. :180.

### `conditions_info` / `actions_info` — Discovery + Form

- `conditions_info::add_conditions_to_mform()` — filtert per
  `can_be_combined_with_bookingruletype()` und rendert gewählte Condition. :47.
- `conditions_info::get_conditions()` / `get_condition(name)` — glob+Extension-Scan. :96/:136.
- `actions_info::add_actions_to_mform()` — filtert per `is_compatible_with_ajaxformdata()`,
  `array_reverse` der Auswahl, rendert passende Action. :47.
- `actions_info::get_actions()` / `get_action(name)` — glob (ohne Extension-Scan!). :108/:137.

### Rules (Trigger)

**`rule_react_on_event`** (rule_react_on_event.php) — Reagiert auf Moodle-/Subplugin-/
shopping_cart-Events. Konstanten `ALWAYS/FULLYBOOKED/NOTFULLYBOOKED/FULLWAITINGLIST/
NOTFULLWAITINGLIST` als Zustands-Bedingung. Pflegt eine **Whitelist** erlaubter
Event-Keys (`$allowedeventkeys`, :117). Felder: `boevent`, `intervaldata`, `ruleisactive`.
Methoden: Standard-Interface plus `get_records_for_execution(optionid,userid)` (baut
Options-SQL ohne Datumsfilter), `rule_still_in_time()` (private static),
`ruleevent_excluded_via_config()` (private; wertet `limitchangestrackinginrules`-Settings
für `bookingoption_updated`-Änderungen aus). `execute()` enthält Sonderfall
payment_confirmed-Cart-Auflösung und Change-Tracking-Filter. :361.

**`rule_daysbefore`** (rule_daysbefore.php) — Trigger N Tage vor/nach Datumsfeld
(`coursestarttime/courseendtime/optiondatestarttime/bookingopeningtime/
bookingclosingtime/selflearningcourseenddate/installmentpayment`). `execute()`
berechnet `nextruntime` per DST-sicherem `strtotime`. `get_records_for_execution()`
baut datefield-spezifisches SQL inkl. optiondate-`daystonotify`-Override (CASE) und
selflearning-JSON-Key-Auflösung über `bo_info::check_for_sqljson_key_in_object`.
`should_skip_for_selflearningcourse()` (private). `check_if_rule_still_applies()`
re-validiert über Testmode-SQL. :43.

**`rule_specifictime`** (rule_specifictime.php) — Nachfolger von daysbefore mit
Sekunden-Granularität (`duration`-Feld + before/after-Vorzeichen). Konvertiert
deprecated `days`-JSON zu Sekunden. Sonst strukturgleich zu daysbefore. :41.

### Actions

**`send_mail`** — queued `send_mail_by_rule_adhoc` mit subject/template/ical-Flags,
`nextruntime`; require_active_user-Gate. `execute()` :196.

**`send_mail_interval`** — gestaffelter Versand. `execute()` nutzt `counter` +
`intervaldata.usersalreadytreated` im rulejson; zweiter User setzt `repeat=1` und
verschiebt `nextruntime` um `interval*60`s; >1 → return. Erzeugt zusätzlich eine
`confirm_bookinganswer`-Action inline. :193.

**`send_copy_of_mail`** — nur kompatibel mit `custom_message_sent`/`custom_bulk_message_sent`
(`$compatibleevents`, `is_compatible_with_ajaxformdata()`). `set_actiondata_from_json()`
restauriert das Event (`boevent::restore`) und baut subject/message aus Event-`other`. :206.

**`confirm_bookinganswer`** — one-at-a-time WL-Bestätigungskette. Privates `rulejson`/
`counter`, `confirmdata.usersalreadytreated`. `execute()` lädt rulejson notfalls aus DB
nach, queued `confirm_bookinganswer_by_rule_adhoc` via `queue_task()` (private). Setter
`set_next_runtime_for_adhoc()`, `set_ruleid()`. Kein Formular, `is_compatible…`=false
(nur intern aus `send_mail_interval` genutzt). :148.

**`delete_conditions_from_bookinganswer`** — queued
`delete_conditions_from_bookinganswer_by_rule_adhoc` mit `baid`. :127.

### Conditions

Alle Conditions teilen das Muster `set_conditiondata_from_json` → `execute(&$sql,&$params)`
mutiert das SQL-Skelett und hängt einen User-Join + `uniqueid`-CONCAT an. Besonderheiten:

- `select_users` — `IN`-Liste expliziter User-IDs, AJAX-User-Picker. :178.
- `select_student_in_bo` — JOIN `booking_answers`, Status-Operator (`=`/`<=` für
  „booked+waitinglist"), `userstotreat`-Subquery aus Event, Sortierung für Intervall. :162.
- `select_teacher_in_bo` — JOIN `booking_teachers`. :131.
- `select_responsible_contact_in_bo` — CSV-Split von `bo.responsiblecontact` mit
  getrennten Postgres- (LATERAL/regexp) und MySQL/MariaDB-Zweigen (UNION-Zahlenreihe,
  max 20). :132.
- `select_user_from_event` — wählt `userid` (Auslöser) oder `relateduserid` (betroffen)
  aus restauriertem Event; statische `add_userselect_to_mform()` mit relateduserid-Whitelist
  (von zwei weiteren Conditions wiederverwendet). :230.
- `select_user_shopping_cart` — User aus offenen Ratenzahlungen
  (`local_shopping_cart_history`-JSON), nur Postgres/MySQL≥8/MariaDB≥10.6, getrennte
  JSON_TABLE-/LATERAL-Zweige; nur mit `rule_daysbefore`/`rule_specifictime` kombinierbar. :179.
- `select_users_from_userfield_of_eventuser` — User-IDs aus Profilfeld des Event-Users
  (Vorabquery + `IN`). :177.
- `select_deputy_of_supervisor` — Stellvertreter über zwei Profilfelder
  (Supervisor → Deputy-Subquery). :193.
- `select_booking_manager` — JOIN `booking.bookingmanager` (username) → `user`. :131.
- `enter_userprofilefield` — Match `user_info_data.data` gegen eingegebenen Wert (`=`/`~`). :197.
- `match_userprofilefield` — Match Profilfeld gegen Optionsfeld (`text/location/address`). :198.

### Templates (Seeds)

16 strukturidentische Klassen `ruletemplate_*` mit `public static $templateid`,
`public static $eventtype`, `get_name():string`, `return_template():object`. Sie liefern
ein vordefiniertes Regel-Record-Objekt (negative/positive `id`, fertiges `rulejson`) für
die Vorlagenauswahl. Konsumiert von `mod_booking\local\templaterule` (außerhalb Scope).
Zuordnung Trigger/Condition/Action (alle Action `send_mail`):

| Template (id) | eventtype | Condition | boevent / datefield |
|---|---|---|---|
| bookingoption_booked (1) | rule_react_on_event | select_user_from_event | bookingoption_booked |
| confirmwaitinglist (2) | rule_react_on_event | select_user_from_event | bookingoptionwaitinglist_booked |
| daysbeforestart (3) | rule_daysbefore | select_student_in_bo | (Datumsfeld) |
| trainerreminderbeforestart (4) | rule_daysbefore | select_teacher_in_bo | (Datumsfeld) |
| userstorno (5) | rule_react_on_event | select_user_from_event | bookinganswer_cancelled |
| courseupdate (6) | rule_react_on_event | select_student_in_bo | bookingoption_updated |
| userpoll (7) | rule_daysbefore | select_student_in_bo | (Datumsfeld) |
| trainerpoll (8) | rule_daysbefore | select_teacher_in_bo | (Datumsfeld) |
| bookingoptioncompleted (9) | rule_react_on_event | select_user_from_event | bookingoption_completed |
| usercancellation (10) | rule_react_on_event | select_student_in_bo | bookingoption_cancelled |
| sessionreminders (11) | rule_daysbefore | select_student_in_bo | optiondatestarttime |
| trainercancellation (12) | rule_react_on_event | select_teacher_in_bo | bookingoption_cancelled |
| paymentconfirmation (13) | rule_react_on_event | select_user_from_event | local_shopping_cart\payment_confirmed |
| bookingoptionuncompleted (14) | rule_react_on_event | select_user_from_event | bookingoption_uncompleted |
| optiondatesteacheradded (15) | rule_react_on_event | select_user_from_event | optiondates_teacher_added |
| optiondatesteacherdeleted (16) | rule_react_on_event | select_user_from_event | optiondates_teacher_deleted |

## Persistenz

- **Tabelle `booking_rules`** (db/install.xml): `id`, `contextid` (1=global/System),
  `rulename`, `rulejson` (TEXT, trägt die gesamte Rule/Condition/Action-Definition +
  Laufzeit-`datafromevent`/`intervaldata`/`confirmdata`), `eventname`
  (denormalisiert für schnellen Event-Filter), `useastemplate`, `isactive`. Nur ein
  Primärschlüssel, keine Indizes auf `contextid`/`eventname`/`rulename`.
- **Gelesene Tabellen (Condition-SQL):** `booking_options`, `booking_optiondates`,
  `booking_answers`, `booking_teachers`, `booking`, `user`, `user_info_data`,
  `user_info_field`, `course_modules`, `modules`, `context`, sowie
  `local_shopping_cart_history` (shopping_cart-Condition).
- **Caches:** statischer In-Memory-Cache `booking_rules::$rules` (Request-Lebensdauer);
  `singleton_service` für Options-Settings/Answers/User; **kein** MUC-Cache.
- **Adhoc-Tasks (erzeugt):** `send_mail_by_rule_adhoc`,
  `confirm_bookinganswer_by_rule_adhoc`, `delete_conditions_from_bookinganswer_by_rule_adhoc`
  (Definitionen in `classes/task/`, außerhalb Scope).

## Extension-Points

- **Datei-Discovery per Konvention**: Neue Rule/Condition/Action = neue PHP-Datei im
  jeweiligen Verzeichnis; Dateiname == Klassenname (`get_rules()`/`get_conditions()`/
  `get_actions()` per `glob()`). Action-Discovery scannt **keine** Extensions.
- **Subplugin-Hook `bookingextension_*`**: `get_rules()`/`get_condition()` scannen
  `rules\rules` bzw. `rules\conditions`-Namespaces installierter Extensions
  (core_component/core_plugin_manager).
- **Event-Whitelist-Hook**: `rule_react_on_event` ruft je Extension
  `bookingextension_<name>\<name>::get_allowedruleeventkeys()` ab und scannt deren
  `event`-Namespace.
- **Drei Interfaces** als formaler Erweiterungsvertrag.
- **Kombinationsmatrix** über `can_be_combined_with_bookingruletype()` (Condition) und
  `is_compatible_with_ajaxformdata()` (Action).
- **Settings-gesteuertes Verhalten**: `limitchangestrackinginrules` +
  `listento*change`-Configs steuern, welche `bookingoption_updated`-Änderungen Regeln auslösen.

## Bekannte Schulden (→ Blueprint)

**P1 — Orchestrator `rules_info` ist God-Klasse (687 LOC, 16 statische Methoden,
3 globale statische Akkumulatoren).** Vermischt Discovery, Persistenz, Event-Sammlung,
cancelrules-Filterung und shopping_cart-Spezialwissen (`proceed_with_event`
rules_info.php:639, Companion-Logik :545-628). Statischer Zustand
(`$rulestoexecute`/`$rulestocancel`) erschwert Tests und ist nebenläufigkeitsanfällig;
Reset nur über `destroy_singletons()`.

**P1 — `rule_react_on_event` (679 LOC) als Event-Adapter mit hartcodierten Whitelists.**
`$allowedeventkeys` (:117) und der Change-Tracking-Switch
`ruleevent_excluded_via_config()` (:594-678, ~85 LOC, tief verschachtelt, liest 6+
Configs) sind schwer wartbar; jedes neue Event/Feld erfordert Code-Edit.

**P1 — SQL-Injection-Oberfläche durch unparametrisierte Feldnamen.** Mehrere
Conditions/Rules interpolieren `datefield`/`optionfield` direkt in SQL
(`rule_daysbefore.php:462/464`, `rule_specifictime.php:500/502`,
`match_userprofilefield.php:203/208`). Werte stammen aus festen Form-Selects, sind
also faktisch begrenzt — aber es gibt keine Allowlist-Validierung an der SQL-Grenze.

**P1 — `select_user_shopping_cart` (313 LOC) mit großen, dialekt-duplizierten
JSON-SQL-Blöcken** (Postgres- und MySQL-Zweig nahezu redundant, je ~40 Zeilen
String-SQL, :204-311). Hohe Kopplung an `local_shopping_cart`-Datenmodell, kein
MariaDB-only-Zweig trotz Hinweis. Tippfehler `uniquid` (:227).

**P1 — Verstreute Interval-/Confirm-Ketten-Logik.** Das „one-user-at-a-time"-Muster ist
dreimal sehr ähnlich implementiert (`send_mail_interval::execute` :193,
`confirm_bookinganswer::execute` :148, Companion-Merge in `rules_info`); `counter`/
`usersalreadytreated`/`confirmdata` sind fragiler Zustand im rulejson. Hohe kognitive
Last, schwer testbar.

**P2 — Massive Code-Duplikation zwischen `rule_daysbefore` und `rule_specifictime`**
(~90% identisch: set_ruledata, save_rule, set_defaults, get_records_for_execution,
check_if_rule_still_applies, should_skip_for_selflearningcourse). specifictime ist der
Nachfolger; daysbefore wird parallel gepflegt.

**P2 — `uniqueid`-CONCAT-Hack in jeder Condition dupliziert** (string-`strpos`-Prüfung
auf `'optiondate'` im Select, z.B. select_users.php:185, select_student_in_bo.php:177
u.v.m.). Fragiler impliziter Vertrag zwischen Rule-Select und Condition.

**P2 — Inkonsistente Action-Discovery.** `actions_info::get_actions()` scannt im
Gegensatz zu Rules/Conditions **keine** Extensions — stilles Feature-Gap.

**P3 — Fehlende DB-Indizes** auf `booking_rules.contextid`/`eventname`; `get_list_of_saved_rules`
lädt bei jedem Event alle Regeln und filtert in PHP.

**Querschnitt — Testbarkeit:** Durchgehend statische God-Calls (`singleton_service`,
`booking_rules::`, `actions_info::`, `\core\task\manager`), direkte `$DB`/`$CFG`-Globals,
`json_decode` ohne Schema-Validierung. Für dieses Subsystem im Scope keine eigenen
Unit-Tests sichtbar (Tests liegen außerhalb in `tests/`).
