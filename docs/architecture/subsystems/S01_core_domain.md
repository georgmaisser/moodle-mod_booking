# S01 — core_domain

## Zweck & Grenzen

Das Subsystem **core_domain** bildet den Kern der Domaenenschicht von `mod_booking`. Es kapselt die zentralen
fachlichen Objekte des Plugins:

- **Instanz** (`booking`, `booking_settings`) — eine Buchungsaktivitaet in einem Kurs (Course Module).
- **Option** (`booking_option`, `booking_option_settings`) — eine einzelne buchbare Veranstaltung/Kurs.
- **Buchungsantworten** (`booking_answers` + Scope-Hierarchie) — wer hat was mit welchem Status gebucht.
- **Termine / Semester** (`dates`, `semester`, `calendar`, `ical`, `local/calendar`, `local/optiondates`).
- **Kern-Infrastruktur** (`singleton_service` als zentraler Objekt-Cache/Factory, `booking_context_helper`,
  `booking_utils`, `permissions`, `places`, DTO-/Hilfsklassen).

Grenzen: Dieses Subsystem ist die *Datenheimat* des Plugins. Es kennt Persistenz (DB-Tabellen, MUC-Caches),
Statuslogik und Aggregation. Es ist **nicht** verantwortlich fuer Rendering (output/templates), Formulare
(option/fields, dynamicform), Verfuegbarkeitsbedingungen (`bo_availability`), Preise/Warenkorb (shopping_cart),
Regeln (`booking_rules`) oder Webservices — diese liegen in eigenen Subsystemen, werden hier aber stark
referenziert (siehe *Position im Gesamtsystem*). Faktisch sind `booking` und `booking_option` God-Objekte, die
weit ueber reine Domaenenlogik hinausgreifen (Enrolment, Messaging, Kalender, SQL-Filter-Builder).

## Position im Gesamtsystem

```
                 singleton_service  (zentrale Factory + Prozess-Cache + MUC-Anbindung)
                        |  liefert/cached
   +--------------------+-------------------------------------------------+
   |            |              |                |                |         |
booking   booking_settings  booking_option  booking_option_   booking_   semester
(Instanz)   (DTO)           (Verhalten)      settings (DTO)    answers
   |                            |                |                |
   |  nutzt                     | nutzt          | laedt          | aggregiert
   v                            v                v                v
bo_availability, teachers_handler, option/fields, customfield, dates_handler,
booking_rules, subbookings, booking_campaigns, local_entities, shopping_cart,
output/description, calendar/ical, wunderbyte_table
```

- **Eingang:** Alle hoeheren Schichten (view.php, Webservices, Tasks, Rules, Renderer) holen ihre Domaenenobjekte
  fast ausschliesslich ueber `singleton_service::get_instance_of_*`. Direktes `new booking_option(...)` ist die
  Ausnahme.
- **Ausgang:** `booking_option` orchestriert Buchung (`user_submit_response`), Enrolment (`enrol_user`),
  Warteliste (`sync_waiting_list`), Messaging (`sendmessage_*`), Kalender (`calendar`/`calendar_helper`) und
  Events (`event\*`).
- **DTO-Trennung:** `*_settings`-Klassen sind lesende, gecachte Datencontainer; `booking`/`booking_option` sind
  die verhaltenstragenden Klassen.

## Schluesselkonzepte

- **Settings-DTO vs. Verhaltensobjekt:** `booking_option_settings`/`booking_settings` halten den vollstaendigen,
  aufgeloesten Zustand einer Option/Instanz (inkl. Sessions, Teacher, Customfields, Entity, Bild, Preise) und
  werden in MUC-Caches (`bookingoptionsettings`, `cachedbookinginstances`) gehalten. `booking_option`/`booking`
  fuehren Aktionen aus.
- **singleton_service:** Prozessweiter Identity-Map-/Factory-Layer. Verhindert Mehrfach-Instanziierung gleicher
  Objekte pro Request und bildet die Brueckenschicht zu den MUC-Caches. Faktisch ein globaler Service-Locator.
- **booking_answers + Scopes:** `booking_answers` aggregiert pro Option die Antworten (gebucht / Warteliste /
  reserviert / geloescht / Notification) und berechnet Belegung/Status. Die `scopes/`-Hierarchie liefert
  SQL + Tabellenspalten fuer verschiedene Aggregationsebenen (Option, Instanz, Kurs, System, Bestaetigungs-Workflow).
- **Status-Modell:** `waitinglist` (0=gebucht, 1=Warteliste, 2=reserviert/Warenkorb) und `status` (Praesenz:
  participated=6, excused=7, noshow=3 etc.) sind die zentralen Diskriminatoren in `booking_answers`.
- **Termine als optiondates:** Jede Sitzung ist ein `booking_optiondates`-Datensatz; Kalender-/iCal-Erzeugung
  haengt daran. `dates` ist die Form-/Parsing-Schicht, `calendar`/`calendar_helper` die Persistenz in `{event}`.
- **JSON-Sidecar-Felder:** Sowohl `booking` als auch `booking_option` speichern Zusatzkonfiguration in einem
  `json`-Feld (`add_data_to_json`/`get_value_of_json_by_key`).

## Datenfluss

1. **Lesen:** Aufrufer ruft `singleton_service::get_instance_of_booking_option_settings($optionid)`. Service
   prueft Prozess-Cache → MUC-Cache (`bookingoptionsettings`) → konstruiert `booking_option_settings`, das in
   `set_values()` ~30 DB-Reads/Joins ausfuehrt (Sessions, Teacher, Customfields, Entity, Bild, Subbookings) und
   sich selbst in den Cache schreibt.
2. **Buchen:** `booking_option::user_submit_response()` → `write_user_answer_to_db()` (Insert in
   `{booking_answers}`) → `after_successful_booking_routine()` (Enrolment, Kalender, Messaging, Events) →
   Cache-Invalidierung via `purge_cache_for_option`/`purge_cache_for_answers`.
3. **Aggregieren:** `booking_answers` wird ueber `singleton_service::get_instance_of_booking_answers($settings)`
   geholt (Cache `bookinganswers`), berechnet Belegung und Userlisten. Reporting-Tabellen nutzen die
   `scopes/`-Klassen fuer scope-spezifisches SQL.
4. **Invalidierung:** Aenderungen rufen `booking_option::purge_cache_for_option()` /
   `singleton_service::destroy_*` und `cache_helper::purge_*`, plus Cross-Node-Broadcast
   (`broadcast_answer_caches`).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| classes/booking.php | `booking` | Domaenenobjekt (Instanz) | 2391 | ~50 | D | P1 |
| classes/booking_option.php | `booking_option` | Domaenenobjekt (Option) | 5279 | ~90 | E | P0 |
| classes/booking_option_settings.php | `booking_option_settings` | DTO (Option-State) | 1754 | ~33 | C | P2 |
| classes/booking_settings.php | `booking_settings` | DTO (Instanz-State) | 597 | 4 | B | P3 |
| classes/booking_answers/booking_answers.php | `booking_answers` | Service/Aggregator | 1606 | ~45 | D | P1 |
| classes/booking_answers/scope_base.php | `scope_base` | Basis (Scope-Strategy) | 215 | 8 | B | P3 |
| classes/booking_answers/scope_base_options.php | `scope_base_options` | Basis (aggregiert) | 107 | 3 | B | - |
| classes/booking_answers/scope_base_answers.php | `scope_base_answers` | Basis (nicht-aggregiert) | 126 | 3 | B | - |
| classes/booking_answers/scopes/option.php | `option` | Scope (Option) | 438 | 6 | C | P2 |
| classes/booking_answers/scopes/alloptions.php | `alloptions` | Scope (alle Optionen) | 289 | – | C | P3 |
| classes/booking_answers/scopes/optionstoconfirm.php | `optionstoconfirm` | Scope (Confirm-Workflow) | 473 | 8 | C | P2 |
| classes/booking_answers/scopes/optionstoconfirmreduced.php | `optionstoconfirmreduced` | Scope | 113 | – | B | - |
| classes/booking_answers/scopes/supervisorteam.php | `supervisorteam` | Scope | 159 | – | B | - |
| classes/booking_answers/scopes/supervisorteamreduced.php | `supervisorteamreduced` | Scope | 134 | – | B | - |
| classes/booking_answers/scopes/instance.php | `instance` | Scope (Instanz) | 178 | – | B | - |
| classes/booking_answers/scopes/instanceanswers.php | `instanceanswers` | Scope | 206 | – | B | - |
| classes/booking_answers/scopes/course.php | `course` | Scope (Kurs) | 178 | – | B | - |
| classes/booking_answers/scopes/courseanswers.php | `courseanswers` | Scope | 207 | – | B | - |
| classes/booking_answers/scopes/system.php | `system` | Scope (System) | 176 | – | B | - |
| classes/booking_answers/scopes/systemanswers.php | `systemanswers` | Scope | 208 | – | B | - |
| classes/booking_answers/scopes/optiondate.php | `optiondate` | Scope (Termin) | 313 | 4 | C | P3 |
| classes/dates.php | `dates` | Form/Parsing (Termine) | 1038 | ~14 | C | P2 |
| classes/semester.php | `semester` | DTO/Lookup (Semester) | 156 | 4 | A | - |
| classes/calendar.php | `calendar` | Service (Kalender→{event}) | 466 | 4 | C | P2 |
| classes/ical.php | `ical` | Service (iCal-Erzeugung) | 567 | ~13 | C | P3 |
| classes/all_userbookings.php | `all_userbookings` | Renderer (table_sql) | 1061 | ~33 | C | P2 |
| classes/mybookings_table.php | `mybookings_table` | Renderer (table_sql) | 127 | 4 | A | - |
| classes/singleton_service.php | `singleton_service` | Factory/Service-Locator | 958 | ~45 | C | P1 |
| classes/booking_context_helper.php | `booking_context_helper` | Util (Context-Fix) | 61 | 1 | A | - |
| classes/booking_utils.php | `booking_utils` | Util (gemischt) | 616 | ~16 | C | P2 |
| classes/places.php | `places` | DTO (Wert-Objekt) | 80 | 1 | A | - |
| classes/elective.php | `elective` | Service (Wahlpflicht/Credits) | 698 | ~22 | C | P2 |
| classes/permissions.php | `permissions` | Util (Capability-Check) | 53 | 1 | B | P3 |
| classes/coursecategories.php | `coursecategories` | Service (Kategorie-Reports) | 218 | 3 | C | P3 |
| classes/local/calendar/calendar_helper.php | `calendar_helper` | Service (Event-Verwaltung) | 230 | 5 | B | - |
| classes/local/optiondates/optiondate_answer.php | `optiondate_answer` | DTO/Repo (Termin-Antwort) | 166 | 8 | B | - |

(Methoden „–" bei reinen Scope-Subklassen: kleine Spezialisierungen von `return_users_table` /
`return_sql_for_booked_users` / `return_cols_for_tables`.)

---

### `booking` (classes/booking.php)

**Verantwortung:** Repraesentiert eine Buchungsinstanz (Course Module). Haelt `id`, `cmid`, `context`,
`settings` und liefert Listen/SQL fuer Optionen sowie zahlreiche statische Helfer (SQL-Filter-Builder,
JSON-Sidecar, Konstanten-Arrays). Faktisch God-Objekt mit Mix aus Instanz-State und globaler Utility.

**Kollaborateure:** `singleton_service`, `booking_settings`, `booking_option`, `wunderbyte_table`,
`bo_info`, `teachers_handler`, `customfield\booking_handler`.
**Persistenz:** `{booking}`, `{booking_options}`; Cache `cachedbookinginstances`. **Extension-Points:**
`is_elective()`/`uses_credits()` schalten Subsystem-Features; statische SQL-Builder fuer Filter-Frameworks.

**Methoden-Inventar (Auswahl):**
- `__construct(int $cmid, ?course_modinfo $cm)` — laedt Settings via singleton_service.
- `get_context()` / `get_url_params()` / `get_pagination_setting(): int` — public, triviale Accessoren.
- `static load_users/load_courses/load_teachers_for_webservice(string $query)` — Autocomplete-Suchen fuer WS.
- `get_all_options(...)` / `get_all_options_count()` / `static get_all_optionids($bookingid)` — Optionslisten.
- `get_active_optionids(...)` / `get_my_bookingids(...)` — gefilterte Optionsmengen (public).
- `show_maxperuser($user)` / `get_user_booking_count($user): int` / `get_user_booking($user)` — Userlimits.
- `get_bookingoptions_fields(bool $download)` / `get_manage_responses_fields()` — Spaltendefinitionen (gross).
- `checkautocreate()` — Auto-Erzeugung von Optionen aus Templates.
- `is_elective()` / `uses_credits()` — Feature-Flags.
- `static get_options_filter_sql(...)` (1164) / `apply_wherearray(...)` / `get_all_options_sql(...)` /
  `static get_all_options_of_teacher_sql(...)` — zentrale SQL-Builder (lang, komplex).
- `static return_array_of_entity_dates(array $areas)` / `return_sql_for_options_dates()` /
  `get_sql_for_fieldofstudy(...)` / `return_sql_for_event_logs(...)` — weitere SQL-Builder.
- `static add_data_to_json/remove_key_from_json/get_value_of_json_by_key(...)` — JSON-Sidecar-Helfer.
- `static booking_instance_get_changes($old,$new)` — Diff fuer Rules/Events.
- `static purge_cache_for_booking_instance_by_cmid(...)` — Cache-Invalidierung.
- `static get_possible_presences()/get_array_of_possible_views()/...` — Konstanten-Arrays (Enums).
- `static convert_prices_to_number_format(...)` / `is_valid_booking_cmid(int): bool` /
  `check_required_custom_fields(...)` (protected).

---

### `booking_option` (classes/booking_option.php)

**Verantwortung:** Zentrales God-Objekt fuer eine Buchungsoption. Verantwortet Buchen/Stornieren,
Warteliste-Sync, Enrolment, Gruppen, Praesenz/Notes, Messaging, Kalender, Favoriten, Loeschen/Verschieben,
Cache-Invalidierung und statische Update-/Lookup-Helfer. **Groesster Hotspot des Plugins.**

**Kollaborateure:** nahezu alle Subsysteme — `booking_answers`, `booking_option_settings`,
`bo_info`/`bo_availability\conditions\*`, `booking_rules\rules_info`, `subbookings`, `option\dates_handler`,
`option\fields\*`, `local_entities`, `calendar`/`calendar_helper`, `completion_info`, `core\task\manager`,
`event\*`, `confirmationworkflow\confirmation`.
**Persistenz:** `{booking_options}`, `{booking_answers}`, `{booking_teachers}`, `{booking_userevents}`,
`{booking_other}`, `{bookingdetails}`, user-favorites (preferences); Caches `bookingoptionsettings`,
`bookingoptionsanswers`, `bookinganswers`.
**Extension-Points:** `static update(...)` als kanonischer Mutations-Eingang; `trigger_updated_event`;
`bo_actions`, `option\fields\*` als Feld-Plugins.

**Methoden-Inventar (Auswahl der nicht-trivialen):**
- `__construct(int $cmid, int $optionid)` / `static create_option_from_optionid(int, ?int): ?booking_option`.
- `static trigger_updated_event(...)` — feuert `bookingoption_updated`.
- `user_submit_response(...)` (1288) — Haupt-Buchungseinstieg.
- `static write_user_answer_to_db(...)` (1667) — Insert/Update Antwort.
- `after_successful_booking_routine(...)` (1954) — Post-Booking (Enrol/Kalender/Messaging/Events).
- `sync_waiting_list($syncshared, $optionupdated)` (994) — Warteliste-Nachrueck-Logik (kritisch, Concurrency).
- `user_delete_response(...)` (727) / `delete_responses(...)` / `delete_responses_activitycompletion()` —
  Stornierung.
- `user_confirm_response(stdClass): bool` / `change_booking_answer_waitinglist_status(...)`.
- `enrol_user(...)` (2222) / `unenrol_user($userid)` / `enrol_user_coursestart($userid)` — Enrolment.
- `create_group(...)` / `static generate_group_data(...)` / `sourcecoursegroup_unenrol_actions(...)` (private).
- `transfer_users_to_otheroption(int, array)` / `move_option_otherbookinginstance($targetcmid)`.
- `delete_booking_option()` (2561) — vollstaendiges Loeschen inkl. Abhaengigkeiten.
- `changepresencestatus(...)` / `edit_notes(...)` / `toggle_user_completion(...)` / `user_completed_option()`.
- `send_confirm_message(...)` / `sendmessage_pollurl(...)` / `sendmessage_notification(...)` /
  `sendmessage_completed(...)` — Messaging.
- `get_teachers()/get_all_users()/get_all_users_booked()/get_all_users_on_waitinglist()` — Userlisten.
- `get_text_depending_on_status(booking_answers, ?int)` / `get_user_status_string(...)` — Statustexte.
- `return_array_of_sessions(...)` / `render_customfield_data(...)` / `render_meeting_fields(...)` (private) /
  `show_conference_link(?int): bool` — Termin-/Meeting-Rendering (eigentlich Output-Concern).
- Favoriten: `static toggle_notify_user`, `user_has_favorite`, `toggle_favorite_user`,
  `get_user_favorite_optionids`, `set_user_favorite_optionids` (private), `normalize_favorite_optionids` (private).
- `static cancelbookingoption(...)` / `return_cancel_until_date($optionid)` / `option_allows_booking_for_user(...)`.
- `static get_consumed_quota(int)` / `check_if_free_to_book_again(...)` / `is_blocked_by_campaign(...)` /
  `has_price_set(...)`.
- Cache: `static purge_cache_for_option`, `purge_cache_for_answers`, `refresh_answers_for_option`,
  `broadcast_answer_caches`.
- `static update(...)` (4866) — kanonischer Update-Pfad (form-style Params, Draft-Areas).
- `recreate_date_series(int $semesterid)` / `create_booking_options_from_optiondates()` /
  `copytotemplate()` / `confirmactivity(?int)`.
- JSON-Sidecar: `static add_data_to_json/get_data_from_json/remove_key_from_json/get_value_of_json_by_key`.
- Lookups: `static get_cmid_from_optionid`, `load_booking_options`, `load_booking_options_filtered`,
  `get_customfield_settings`, `create_truly_unique_option_identifier`, `get_mailto_link_for_partipants`.
- `static booking_history_insert(...)` / `status_bookinganswer_deleted(object): string`.

---

### `booking_option_settings` (classes/booking_option_settings.php)

**Verantwortung:** Lesendes, gecachtes DTO mit dem vollstaendig aufgeloesten Zustand einer Option (Felder,
Sessions, Sessioncustomfields, Teacher, ResponsibleContact, Bild-URL, Customfields, Entity, Subbookings,
Elective-Kombinationen, JSON-Daten, Attachments). Konstruktor → `set_values()` fuehrt die Aggregation aus.

**Kollaborateure:** `singleton_service`, `dates_handler`, `entitiesrelation_handler`, `booking_handler`
(customfields), `subbookings_info`, `campaigns_info`, `bo_subinfo`.
**Persistenz:** liest `{booking_options}`, `{booking_optiondates}`, `{booking_teachers}`, customfield-Tabellen,
files; Cache `bookingoptionsettings`. **Extension-Points:** `return_sql_for_customfield/_custom_profile_field/
_teachers/_imagefiles` als wiederverwendbare SQL-Bausteine fuer Filter-Frameworks.

**Methoden-Inventar (Auswahl):**
- `__construct(int $optionid, ?stdClass $dbrecord)` — Cache-/DB-Aufloesung.
- `set_values(...)` (private, 383) — grosse Aggregations-Methode (Hotspot, viele DB-Reads).
- private `load_*`: `load_sessions_from_db`, `load_sessioncustomfields_from_db`, `load_subpluginssettings`,
  `load_slot_config_from_db`, `load_teachers_from_db`, `load_responsiblecontactuser`, `load_teacherids_from_db`,
  `load_imageurl_from_db`, `load_customfields`, `localize_customfields_for_templates`, `load_entity`,
  `load_subbookings`, `load_elective_combinations`, `load_data_from_json`, `load_attachments`.
- `render_list_of_teachers()` / `get_title_with_prefix(): string` — Darstellungshelfer (Output-Concern im DTO).
- `generate_*_url(int)` (private) — Editoption/Manageresponses/Bookingstracker/Optiondatesteachers-Links.
- `return_settings_as_stdclass()` — Serialisierung.
- `get_booking_option_properties(): array` — Property-Liste.
- `static return_sql_for_customfield/_custom_profile_field/_teachers/_imagefiles(...)` — SQL-Builder.
- `return_booking_option_information(...)` / `return_subbooking_option_information(int, ?object)`.

---

### `booking_settings` (classes/booking_settings.php)

**Verantwortung:** Lesendes, gecachtes DTO fuer eine Buchungsinstanz (Name, Intro, Mail-/Template-Settings,
Booking-Manager, Defaults). Schlanker als `booking`. **Kollaborateure:** `singleton_service`.
**Persistenz:** `{booking}`; Cache `cachedbookinginstances`.
**Methoden:** `__construct(int $cmid)`, `set_values(...)` (private), `load_bookingmanageruser_from_db(string)`
(private), `return_settings_as_stdclass(): stdClass`.

---

### `booking_answers` (classes/booking_answers/booking_answers.php)

**Verantwortung:** Aggregiert und cached die Antworten einer Option. Stellt Userlisten nach Status, Belegung,
Userstatus, Overlap-Pruefung, Max-Bookings-Pruefung und zahlreiche statische Zaehl-/SQL-Helfer bereit.

**Kollaborateure:** `singleton_service`, `booking_option_settings`, `bo_info`, `customform`, Scope-Klassen
(`return_class_for_scope`).
**Persistenz:** `{booking_answers}`, `{booking_subbooking_answers}`; Cache `bookinganswers`,
`bookingoptionsanswers`.
**Extension-Points:** `return_class_for_scope(string): scope_base` — Strategy-Dispatch in `scopes/`.

**Methoden-Inventar (Auswahl):**
- `__construct($bookingoptionsettings)` — laedt/aggregiert Antworten.
- Userlisten: `get_answers/get_users/get_usersonlist/get_usersonwaitinglist/get_usersreserved/`
  `get_usersdeleted/get_userstonotify/get_userspreviouslybooked()` — gebuendelte Accessoren.
- `user_status(int): int` / `user_status_as_string(int): string` — Buchungsstatus eines Users.
- `user_booked(int)/user_on_notificationlist(int)/return_place_on_waitinglist(int)/`
  `user_get_last_active_booking(int)` — Status-Lookups.
- `is_overlapping(int, bool): array` / `static check_overlap(...)` (private) — Terminkollision.
- `exceeds_max_bookings(int, array, string): array` — Limitpruefung.
- `is_fully_booked(): bool` / `is_fully_booked_on_waitinglist(): bool` — Belegung.
- `delete_answer_record(...)` / `reactivate_latest_previouslybooked(int): bool`.
- `return_all_booking_information(int)` / `static add_availability_info_texts_to_booking_information(array&)`.
- `static get_instance_from_optionid($optionid)` / `return_class_for_scope(string): scope_base`.
- `static count_places(array): int` / `number_actively_booked(int, int)` / `count_answers_of_user(...)` /
  `count_allanswers_of_user(...)` / `count_previous_bookings(int): int`.
- `return_sql_for_booked_users(...)` / `get_all_answers_for_user(...)` /
  `get_all_answers_for_user_cached(...)` (private) / `return_sql_to_get_answers(...)` (private static).
- Subbooking: `subbooking_user_status(int, int)`.

---

### Scope-Hierarchie (classes/booking_answers/scope_base*.php, scopes/)

**Verantwortung:** Strategy-Pattern fuer die Reporting-Sicht auf Antworten ueber verschiedene
Aggregationsebenen. Jede Scope-Klasse liefert SQL (`return_sql_for_booked_users`), Spaltendefinitionen
(`return_cols_for_tables`), `wunderbyte_table`-Aufbau (`return_users_table`) und Capability-Pruefung
(`has_capability_in_scope`).

Vererbungsbaum:
```
scope_base
├── scope_base_options   (aggregierte Optionssicht)
│   ├── instance / course / system
├── scope_base_answers   (nicht-aggregierte Antwortsicht)
│   ├── instanceanswers / courseanswers / systemanswers
├── option
│   ├── alloptions
│   └── optionstoconfirm   (Bestaetigungs-Workflow)
│       ├── optionstoconfirmreduced
│       └── supervisorteam
│           └── supervisorteamreduced
└── optiondate
```

- `scope_base` — Basismethoden + `has_capability_in_scope` (default System-Context), `get_wherepart`,
  `join_customfields`, `static return_classname()`, `show_download_button`.
- `scope_base_options`/`scope_base_answers` — `get_selectpart/get_endpart/return_cols_for_tables`.
- `option` — Option-Scope mit echtem `return_users_table`, optionspezifische Cap-Pruefung
  (`updatebookingoption` im Module-Context).
- `optionstoconfirm` — bindet den Confirmation-Workflow (`limit_answers_by_confirmtion_workflow`,
  `get_whereneedtoconfirm_sql`) ueber eine Subplugin-Klasse ein (Extension-Point).
- `optiondate` — Termin-Scope (Praesenzlisten pro Sitzung).

---

### `dates` (classes/dates.php)

**Verantwortung:** Statische Form-/Parsing-Schicht fuer Optionstermine (Collapsibles im Optionsformular,
Submit-Parsing, Speichern der `booking_optiondates`). Trotz Klassennamen rein statisch genutzt.
**Kollaborateure:** `dates_handler`, `option\optiondate`, `time_handler`, `optiondate_cfields`,
`entitiesrelation_handler`, `MoodleQuickForm`.
**Persistenz:** `{booking_optiondates}` (via Handler).
**Methoden (Auswahl):** `static definition_after_data(MoodleQuickForm&, array)`,
`static set_data(stdClass&)`, `static data_preprocessing($d)`, `static get_list_of_submitted_dates(array)`,
`static save_optiondates_from_form(stdClass, stdClass&): array`, `add_date_as_collapsible(...)` (private),
`add_dates_to_form(...)` (private), `static timestamp_to_array(int)`, `parse_date_with_format(...)` (private),
`session_reminder_rule_exists(int): bool` (private).

---

### `semester` (classes/semester.php)

**Verantwortung:** Schlankes DTO/Lookup fuer Semester. Cache `cachedsemesters`. **Persistenz:**
`{booking_semesters}`. **Methoden:** `__construct(int $id)`, `set_values(...)` (private),
`static get_semesters_id_name_array()/get_semesters_identifier_name_array()/get_semester_with_highest_id()`.

---

### `calendar` (classes/calendar.php)

**Verantwortung:** Erzeugt/aktualisiert/loescht Moodle-`{event}`-Eintraege fuer Optionen, Optionstermine
und Teacher. Logik liegt **im Konstruktor** (Typ-Switch) — ungewoehnliches, schwer testbares Design.
**Kollaborateure:** `singleton_service`, `booking_utils`, `calendar_event`, `description_calendarevent`.
**Persistenz:** `{event}`, `{booking_userevents}`, `{booking_optiondates}`, `{booking_teachers}`.
**Konstanten:** `MOD_BOOKING_TYPEUSER=2 … TYPEOPTIONDATE=6`.
**Methoden:** `__construct($cmid,$optionid,$userid,$type,$optiondateid,$justbooked)`,
`static booking_option_add_to_cal(...)` (private), `static booking_optiondate_add_to_cal(...)`,
`static delete_booking_userevents_for_option(int,int)`.

---

### `ical` (classes/ical.php)

**Verantwortung:** Erzeugt iCal-Attachments (VEVENTs) fuer Buchungsbestaetigungen, basierend auf
mod_facetoface-Code. **Kollaborateure:** `description_ical`. **Methoden (Auswahl):**
`__construct($booking,$option,$user,$fromuser,$updated)`, `get_attachments($cancel): array`,
`get_vevents_from_optiondates()` (protected), `add_vevent(...)` (protected),
`generate_tempfile/generate_ical_string/generate_timestamp/escape` (protected),
`fold_line/fold_html_line(string,int): string`, `get_name()`, `get_times(): array`.

---

### `all_userbookings` (classes/all_userbookings.php)

**Verantwortung:** `table_sql`-Renderer fuer die Teilnehmerliste einer Option (Spalten fuer Status, Praesenz,
Rating, Notes, Slots, Zertifikate, Userbild). Liegt im `classes/`-Root statt im output-Subsystem.
**Kollaborateure:** `booking_option`, `slot_answer`, `customform`, `report_edit_bookingnotes`, `user_picture`.
**Methoden:** `__construct($uniqueid, booking_option, $cm, $optionid)`, `set_ratingoptions(...)`, viele
`col_*`-Methoden (protected, je 1 Spalte), `other_cols(...)`, `wrap_html_start/finish()`,
`get_certificates_for_row(stdClass): array` (private).

---

### `mybookings_table` (classes/mybookings_table.php)

**Verantwortung:** Schlanker `table_sql`-Renderer fuer „Meine Buchungen". **Methoden:** `__construct`,
`col_coursestarttime/col_text/col_name/col_status` (protected). Sauber, klein.

---

### `singleton_service` (classes/singleton_service.php)

**Verantwortung:** Zentraler Service-Locator/Identity-Map. Liefert und cached pro Request alle Domaenenobjekte
(booking, booking_option, *_settings, booking_answers, user, price, campaigns, cohort, entity, renderer …) und
ist die Brueckenschicht zu den MUC-Caches. Praktisch globaler State-Holder; wird von allen Subsystemen genutzt.
**Kollaborateure:** alle Domaenenklassen, `core_user`, `entitiesrelation_handler`.
**Persistenz:** indirekt ueber die Zielklassen + statische Arrays als Prozess-Cache.
**Methoden (Auswahl):**
- `static get_instance()` / `__construct()` (private) — Singleton selbst.
- `get_instance_of_booking_by_cmid/_by_bookingid/_by_optionid(...)`.
- `get_instance_of_booking_settings_by_cmid/_by_bookingid(...)`.
- `get_instance_of_booking_option(int,int)` / `get_instance_of_booking_option_settings($optionid, ?stdClass)`.
- `get_instance_of_booking_answers($settings)` / `get_answers_for_user/set_answers_for_user/`
  `destroy_answers_for_user(...)`.
- `get_instance_of_user(int, bool)` / `unset_instance_of_user/destroy_user(int)`.
- `get_instance_of_price($optionid)` / `get_price_category/get_pricecategory_for_user/set_price_category(...)`.
- `get_renderer(string)` / `get_all_campaigns/destroy_all_campaigns/reset_campaigns(...)`.
- `get_course(int)/get_cohort(int)/get_cohorts_of_user(int)/get_entity_by_id(int)`.
- Invalidierung: `destroy_booking_singleton_by_cmid/destroy_booking_option_singleton/`
  `destroy_booking_answers/destroy_booking_answers_for_user_in_booking_instance/destroy_instance`.
- Diverses: `get_index_number(...)`, `get_id_of_booking_module()`, `get_all_booking_instances()`,
  `get_customfield_field_by_shortname(string)`, `load_booking_image/set_booking_image(...)`,
  `*_temp_values_for_certificates(...)`.

---

### `booking_utils` (classes/booking_utils.php)

**Verantwortung:** Gemischte Utility-Sammlung (Dauer-Formatierung, Param-Generierung fuer Templates,
Change-Reaktionen/Events, Userevent-Sichtbarkeit, Kalender-Subscription-Link, Kohort-/Gruppen-Buchung,
Teacher-Namen). Klassischer Grab-Bag ohne klare Single Responsibility.
**Kollaborateure:** `cache_helper`, `bookingoption_updated`-Event, `singleton_service` (indirekt).
**Methoden (Auswahl):** `__construct(?object $booking, ?object $bookingoption)`,
`get_pretty_duration($s)` + `pretty_duration` (private), `generate_params(stdClass, ?stdClass): stdClass`,
`get_body(...)`, `react_on_changes($cmid,$context,$optionid,$changes)`, `prepare_changes_array(array)` (private),
`static booking_option_has_optiondates(int)`, `booking_hide_option_userevents/booking_show_option_userevents(int)`,
`booking_generate_calendar_subscription_link($user,$eventparam)`, `calendar_get_export_token(stdClass)` (private),
`static book_cohort_or_group_members(...)`, `static prepare_teachernames_arrays_for_optionids(array)`.

---

### `elective` (classes/elective.php)

**Verantwortung:** Logik fuer Wahlpflicht-Buchungen (Kombinationen, Credits, Buchbarkeit, Enrolment der
gebuchten User). **Kollaborateure:** `booking`, `booking_settings`, `booking_option_settings`,
`MoodleQuickForm`. **Persistenz:** `{booking_other}` (Kombinationen) + Cache.
**Methoden (Auswahl):** `instance_form_definition/validation/save`, `static instance_option_form_definition`,
`static option_form_set_data`, `static addcombinations/get_combine_array/load_combinations`,
`static check_if_allowed_to_inscribe`, `static return_credits_booked/left/selected`, `static show_credits_message`,
`static enrol_booked_users_to_course`, `static is_bookable/is_bookable_combination`,
`static return_sorted_array_of_options_from_cache/get_options_from_cache`.

---

### Kleinere Klassen

- **`booking_context_helper`** — eine statische Methode `fix_booking_page_context(moodle_page&, int $cmid)`,
  setzt $PAGE-Context defensiv (Shortcodes/Webservices). Sauber.
- **`places`** — reines Wert-Objekt (`maxanswers`, `available`, `maxoverbooking`, `overbookingavailable`).
- **`permissions`** — `static has_capability_anywhere($capability, $contextlevel)`: iteriert ALLE Kontexte
  einer Ebene → explizit als „expensive" markiert (potenzieller Perf-Hotspot).
- **`coursecategories`** — Kurskategorie-Reports mit grossem aggregierendem SQL; `local_urise`-Kopplung in
  `set_configured_booking_instances` (fremdes Plugin direkt referenziert).
- **`calendar_helper` (local/calendar)** — saubere statische Helfer zum Setzen/Loeschen/Updaten von
  Kalender-Events einer Option (`option_set_visibility_for_all_calendar_events`, `option_delete_*`,
  `option_optiondate_update_event`).
- **`optiondate_answer` (local/optiondates)** — DTO/Repository fuer Praesenz pro Sitzung
  (`save_record/get_record/delete_record/get_records_for_optiondate/add_or_update_status/notes`).

## Persistenz

**DB-Tabellen (gelesen/geschrieben in diesem Subsystem):**
`{booking}`, `{booking_options}`, `{booking_answers}`, `{booking_optiondates}`, `{booking_teachers}`,
`{booking_userevents}`, `{booking_other}`, `{booking_subbooking_answers}`, `{booking_semesters}`,
`{bookingdetails}`, sowie Moodle-Kern `{event}`, `{course_categories}`, customfield-Tabellen, `{user}`.

**MUC-Caches:**
- `bookingoptionsettings` — `booking_option_settings`-Objekte.
- `cachedbookinginstances` — `booking_settings`-Objekte.
- `bookinganswers` / `bookingoptionsanswers` — `booking_answers`-Aggregate.
- `cachedsemesters` — `semester`-Objekte.
- Prozess-Cache: statische Arrays in `singleton_service`.

**Invalidierung:** `booking_option::purge_cache_for_option/purge_cache_for_answers/broadcast_answer_caches`,
`booking::purge_cache_for_booking_instance_by_cmid`, `singleton_service::destroy_*`, `cache_helper::purge_*`.

## Extension-Points

- **`singleton_service`** als zentraler Factory-/Cache-Eingang — jede neue Domaenenobjekt-Art wird hier
  angebunden.
- **`booking_option::update(...)`** — kanonischer Mutations-Eingang (form-style Params); `option\fields\*` und
  `bo_actions` haengen daran.
- **`booking_answers::return_class_for_scope()`** + `scope_base`-Vererbung — neue Reporting-Scopes via Subklasse.
- **`optionstoconfirm`** bindet einen Subplugin-Confirmation-Workflow ueber dynamisch instanziierte Klassen ein.
- **Statische SQL-Builder** (`booking::get_options_filter_sql`, `booking_option_settings::return_sql_for_*`)
  werden von Filter-/Table-Frameworks (wunderbyte_table) konsumiert.
- **Events** (`event\bookingoption_updated` u.a.) als Hooks fuer `booking_rules`.

## Bekannte Schulden (→ Blueprint)

- **`booking_option` (5279 LOC, ~90 Methoden) — P0 God-Object.** Vermischt Persistenz, Buchungslogik,
  Enrolment, Gruppen, Messaging, Kalender, Favoriten, Rendering und statische Utility. Methoden wie
  `user_submit_response` (booking_option.php:1288), `after_successful_booking_routine` (:1954),
  `sync_waiting_list` (:994), `delete_booking_option` (:2561), `update` (:4866) sind je fuer sich
  zerlegungsreif. Rendering (`render_meeting_fields` :3779, `return_array_of_sessions` :3607) gehoert in
  output. Kaum isoliert testbar wegen statischer God-Calls.
- **`booking` (2391 LOC) — P1.** Mischt Instanz-State mit globalen SQL-Buildern und Konstanten-Arrays.
  `get_options_filter_sql` (booking.php:1164) und `get_manage_responses_fields` (:895) sind ueberlang.
  Statische Helfer (JSON, Enums, SQL) gehoeren in eigene Helper/Repository-Klassen.
- **`singleton_service` (958 LOC) — P1.** Globaler Service-Locator/State-Holder → erschwert Tests
  (Prozess-Cache muss zwischen Tests via `destroy_instance` geleert werden) und versteckt Abhaengigkeiten.
  Mischt Caching-Verantwortung mit Domaenen-Lookups fuer fremde Subsysteme (price, campaigns, cohort, entity).
- **`booking_answers` (1606 LOC) — P1.** Aggregator + statische SQL-/Count-Helfer + Status-Strings vermischt;
  `get_all_answers_for_user_cached` (:1363) und `return_sql_to_get_answers` (:1477) komplex.
- **`booking_option_settings` (1754 LOC) — P2.** `set_values` (booking_option_settings.php:383) plus ~15
  private `load_*`-Methoden erzeugen einen DB-Read-Sturm pro Cache-Miss (N+1-Gefahr bei Massen-Rendering;
  vgl. Memory „Perf/DB-Call Regression"). Output-Methoden (`render_list_of_teachers`,
  `localize_customfields_for_templates`) gehoeren nicht ins DTO.
- **`calendar` — P2.** Gesamte Logik im Konstruktor (calendar.php:97) ueber Typ-Switch — schwer testbar,
  Seiteneffekte (DB-Writes) in Objektkonstruktion.
- **`all_userbookings` (1061 LOC) — P2.** Renderer im `classes/`-Root statt output-Subsystem; lange `col_*`-
  Kette mit eingebetteter Zertifikat-/Slot-Logik.
- **`dates` (1038 LOC) — P2.** Statische „Klasse" ohne Instanz-Identitaet; Form- und Parsing-Logik vermischt.
- **`booking_utils` — P2.** Grab-Bag ohne Single Responsibility; gemischte statische/Instanz-Methoden.
- **`permissions::has_capability_anywhere` (permissions.php:35) — P3.** Iteriert alle Kontexte einer Ebene;
  explizit „expensive" — Caching/Index-Strategie noetig falls auf Hot-Path.
- **`coursecategories` (P3)** koppelt direkt an Fremd-Plugin `local_urise`
  (coursecategories.php:202) — Layering-Verletzung.
- **`scope_base::join_customfields` (scope_base.php:182)** enthaelt eine fehlerhaft initialisierte
  `$counter`/`$filter`-Variable (undefinierte Variable bei erster Nutzung) — latenter Bug.
