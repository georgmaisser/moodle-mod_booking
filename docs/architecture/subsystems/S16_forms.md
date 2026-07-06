# S16 — forms

## Zweck & Grenzen

Das Subsystem S16 bündelt die **Moodle-Formularschicht** von mod_booking: 46 Form-Klassen unter
`classes/form/` plus den URL-Prefill-Helfer `classes/local/customform_prefill.php`. Die ganz
überwiegende Mehrheit sind `core_form\dynamic_form`-Subklassen (AJAX-Modalformulare), eine Handvoll
sind klassische `\moodleform`-Seitenformulare.

**Verantwortung:** Definition von Formularfeldern, serverseitige Validierung, Lade-/Speichervorgänge
(`set_data_for_dynamic_submission` / `process_dynamic_submission`), Kontext- und Capability-Prüfung
für AJAX-Submits. Die Formulare sind die UI-nahe Schicht zwischen JS-Modals/Seiten und den
fachlichen Service-/Info-Klassen.

**Grenzen / NICHT in S16:**
- Die eigentliche Geschäftslogik der großen Domänen (Optionsfelder, Rules, Campaigns, Subbookings,
  Actions, Certificate-Conditions, Slotbooking-Services) liegt in eigenen Subsystemen. Die Formulare
  hier delegieren via `*_info::*`-Orchestratoren bzw. `*_service`-Klassen (siehe Cross-Refs).
- `option_form` definiert KEINE Felder selbst, sondern ruft `fields_info::instance_form_definition()`
  (Subsystem Option-Fields). S16 ist hier nur die dünne `dynamic_form`-Hülle.

## Position im Gesamtsystem

```
JS-Modal / *.php-Seite
   │  (ajax: core_form/dynamicform  oder  $mform->display())
   ▼
S16 Form-Klasse  ──set_data──►  *_info / *_service / DB   (Vorbefüllung)
   │  validation()
   │  process_dynamic_submission()
   ▼
Service-/Info-Schicht  (rules_info, campaigns_info, subbookings_info, actions_info,
   fields_info, slot_update_service, booking_enrolment, certificate_conditions, ...)
   ▼
DB / Cache / Events
```

Typische Aufrufer: `edit_rules.php`, `edit_campaigns.php`, `editoptions.php`, `report2.php`,
`semesters.php`, `pricecategories.php`, `subscribeusers.php`, `teacherunavailability.php`,
`view.php` (Prepage-Modals) sowie zugehörige `*.mustache`/AMD-Module.

## Schlüsselkonzepte

- **dynamic_form-Lebenszyklus:** `definition()` → `definition_after_data()` (optional) →
  `set_data_for_dynamic_submission()` (Vorbefüllung) → `validation()` →
  `process_dynamic_submission()` (Persistenz). Pflicht-Hooks: `get_context_for_dynamic_submission()`,
  `check_access_for_dynamic_submission()`, `get_page_url_for_dynamic_submission()`.
- **Delegations-Pattern (Mehrheit):** Form ist Hülle, Felder & Speichern kommen aus
  `*_info`-Klassen (`add_*_to_mform`, `set_data_for_form`, `save_*`, `delete_*`). Validierung bleibt
  teils im Form (typabhängige Switch-Blöcke).
- **Cache-als-Transport:** Prepage-/Conditionforms (`bookingpolicy_form`, `customform_form`,
  `additionalperson_form`, `slotbooking_form`) schreiben Nutzereingaben NICHT in die DB, sondern in
  MUC-Caches (`conditionforms`, `subbookingforms`, `customformstore`, `slotbookingstore`), die später
  beim Buchungsvorgang ausgewertet werden.
- **Repeat-Elements-CRUD:** `dynamicsemestersform`, `dynamicholidaysform`, `pricecategories_form`,
  `customfield`, `modaloptiondateform` nutzen `repeat_elements()` und führen Insert/Update/Delete
  direkt im Form aus (diff gegen DB-Bestand).
- **Slot-Familie:** `slotbooking_form` ist Basisklasse, `slotupdate_form` erbt davon
  (Input-Layer geteilt, Submit-Half ersetzt). `teacherunavailability_form` und
  `slotteacherassignments_form` sind eigenständige Slot-Formulare mit direkter DB-Persistenz.
- **Zwei-Pass-Confirm:** `slotupdate_form` und die `sync_rule_*`/`modal_confirmcancel`-Formulare
  liefern in der ersten Runde eine Zusammenfassung/Impact und committen erst nach Bestätigung.

## Datenfluss

**Beispiel option_form (Buchungsoption editieren):**
1. `definition()` ermittelt Kontext aus cmid/optionid, ruft
   `fields_info::instance_form_definition($mform, $formdata)` — alle Felder kommen aus Option-Fields.
2. `set_data_for_dynamic_submission()` → `fields_info::set_data($data)`.
3. `validation()` → `fields_info::validation()`.
4. `process_dynamic_submission()` → `booking_option::update($data, $context)`.

**Beispiel slotbooking_form (Prepage-Slotwahl):**
1. `definition()` baut Picker-DTO via `slot_dto::build_picker_slots()`, embedet `slot_calendar_data`
   als Hidden-Field (WS-identisches Payload), rendert je View-Mode (fixed/calendar/list/userdefined).
2. `validation()` prüft Auswahlregeln + `slot_availability::evaluate_slot_for_user()`.
3. `process_dynamic_submission()` normalisiert Auswahl, persistiert in `slotbookingstore` (Cache).

**Beispiel teacherunavailability_form (direkte DB-Persistenz):**
- `process_dynamic_submission()` öffnet Transaktion, löscht/insert `booking_teacher_unavailability`
  scope-abhängig (system/instance/option), berechnet (Un)Verfügbarkeit aus Checkbox-/Hidden-Selektion.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| actions/actionsform.php | actionsform | Form (dynamic, Delegation→actions_info) | 152 | 8 | B | P3 |
| actions/deleteactionsform.php | deleteactionsform | Form (Delete-Confirm) | 116 | 7 | B | - |
| campaignsform.php | campaignsform | Form (Delegation→campaigns_info) | 155 | 7 | B | P3 |
| certificateconditionsform.php | certificateconditionsform | Form (Filter/Condition/Action-Blocks) | 232 | 9 | B | P3 |
| condition/bookingpolicy_form.php | bookingpolicy_form | Form (Conditionform, Cache-Transport) | 162 | 7 | B | P3 |
| condition/customform_form.php | customform_form | Form (dynamische Custom-Felder) | 425 | 9 | D | P2 |
| condition/slotbooking_form.php | slotbooking_form | Form (Slot-Picker, Basisklasse) | 759 | 22 | C | P2 |
| condition/slotupdate_form.php | slotupdate_form | Form (erbt slotbooking_form, Update-Booking) | 283 | 7 | B | P3 |
| confirmactivity.php | confirmactivity | Form (klassisch moodleform) | 92 | 2 | C | P3 |
| csvimport.php | csvimport | Form (CSV-Import via Callback) | 242 | 8 | B | P3 |
| customfield.php | customfield | Form (klassisch, Config-CRUD in get_data) | 222 | 3 | D | P2 |
| deletecampaignform.php | deletecampaignform | Form (Delete-Confirm) | 126 | 7 | B | - |
| deletecertificateconditionform.php | deletecertificateconditionform | Form (Delete-Confirm) | 112 | 7 | B | - |
| deleteruleform.php | deleteruleform | Form (Delete-Confirm) | 140 | 7 | B | - |
| dynamicchangesemesterform.php | dynamicchangesemesterform | Form (queued adhoc task) | 199 | 8 | B | P3 |
| dynamicdeputyselect.php | dynamicdeputyselect | Form (Deputy-Zuweisung, externe Plugins) | 320 | 11 | D | P2 |
| dynamicholidaysform.php | dynamicholidaysform | Form (Repeat-CRUD booking_holidays) | 263 | 8 | C | P3 |
| dynamicoptiondateform.php | dynamicoptiondateform | Form (Datums-Serie via dates_handler) | 202 | 8 | B | P3 |
| dynamicsemestersform.php | dynamicsemestersform | Form (Repeat-CRUD booking_semesters) | 292 | 8 | C | P3 |
| editteachersforoptiondate_form.php | editteachersforoptiondate_form | Form (Teacher/Deduction-CRUD + Events) | 438 | 8 | C | P2 |
| importoptions_form.php | importoptions_form | Form (klassisch, CSV-Upload) | 98 | 2 | B | - |
| instancetemplateadd_form.php | instancetemplateadd_form | Form (klassisch, 1 Feld) | 57 | 1 | A | - |
| modal_confirmcancel.php | modal_confirmcancel | Form (Option cancel/undo) | 155 | 7 | B | P3 |
| modal_editteacherdescription.php | modal_editteacherdescription | Form (Teacher-Description-Editor) | 174 | 7 | B | P3 |
| modal_send_custom_message.php | modal_send_custom_message | Form (Bulk-Message + Events + Attachment) | 345 | 8 | C | P2 |
| modaloptiondateform.php | modaloptiondateform | Form (Custom-Optiondates Repeat) | 222 | 9 | B | P3 |
| option_form_bulk.php | option_form_bulk | Form (Bulk-Field-Edit, Reflection-artig) | 341 | 11 | C | P2 |
| option_form.php | option_form | Form (Hülle→fields_info) | 240 | 9 | B | P3 |
| optiondates/modal_change_notes.php | modal_change_notes | Form (Notes pro optiondate/option) | 276 | 8 | C | P2 |
| optiondates/modal_change_status.php | modal_change_status | Form (Presence-Status, Dup. v. notes) | 283 | 8 | C | P2 |
| pricecategories_form.php | pricecategories_form | Form (Repeat-CRUD via Handler) | 264 | 9 | B | P3 |
| rulesform.php | rulesform | Form (Delegation→rules_info, große Validation) | 367 | 9 | C | P2 |
| send_mail_to_teachers.php | send_mail_to_teachers | Form (Bulk-Mail an Teacher) | 179 | 7 | B | P3 |
| slotrules_page_form.php | slotrules_page_form | Form (klassisch, Slot-Rule-Editor) | 203 | 3 | B | P3 |
| slotteacherassignments_form.php | slotteacherassignments_form | Form (Student↔Teacher-DB-Mapping) | 431 | 17 | C | P2 |
| subbooking/additionalperson_form.php | additionalperson_form | Form (Subbooking, Cache-Transport) | 283 | 11 | C | P2 |
| subbookingsdeleteform.php | subbookingsdeleteform | Form (Delete-Confirm) | 131 | 7 | B | - |
| subbookingsform.php | subbookingsform | Form (Delegation→subbookings_info) | 158 | 8 | B | P3 |
| subscribe_cohort_or_group_form.php | subscribe_cohort_or_group_form | Form (klassisch, Cohort/Group) | 114 | 2 | B | - |
| subscribeusersactivity.php | subscribeusersactivity | Form (klassisch, Transfer-Auswahl) | 99 | 2 | B | - |
| sync_rule_activate_form.php | sync_rule_activate_form | Form (Sync-Rule aktivieren + Impact) | 167 | 7 | B | P3 |
| sync_rule_delete_form.php | sync_rule_delete_form | Form (Sync-Rule löschen + Mode) | 172 | 7 | B | P3 |
| sync_rule_form.php | sync_rule_form | Form (Sync-Rule erstellen/edit) | 215 | 7 | B | P3 |
| teacher_performed_units_report_form.php | teacher_performed_units_report_form | Form (klassisch, Datumsfilter) | 82 | 2 | A | - |
| teachers_instance_report_form.php | teachers_instance_report_form | Form (klassisch, Teacher-Auswahl) | 93 | 2 | A | - |
| teacherunavailability_form.php | teacherunavailability_form | Form (Slot-(Un)Verfügbarkeit, DB+Transaktion) | 884 | 22 | D | P2 |
| local/customform_prefill.php | customform_prefill | Helper (URL→customform-Cache-Prefill) | 315 | 10 | B | P3 |

Score-Heuristik: A=best … E=schlecht. P0–P3 = Refactor-Prio. Triviale Delete-Confirm-/Filter-Formulare
ohne Schuld erhalten Prio `-`.

---

### Nicht-triviale Klassen — Methoden-Inventar

#### slotbooking_form (condition/slotbooking_form.php, 759 LOC) — Basisklasse Slot-Picker
Verantwortung: Prepage-Modal zur Slot-Auswahl. Rendert je nach `slotconfig->slot_type`
(fixed/userdefined) und `booking_interface` (list/calendar) unterschiedliche UIs, embedet
WS-identisches Picker-Payload, validiert gegen `slot_availability`, persistiert in `slotbookingstore`.
Kollaborateure: `slot_dto`, `slot_availability`, `slotbookingstore`, `singleton_service`,
`booking_option_settings->slotconfig`.
- `protected get_context_for_dynamic_submission(): context` — System-Kontext.
- `protected check_access_for_dynamic_submission(): void` — `mod/booking:conditionforms`.
- `public set_data_for_dynamic_submission(): void` — lädt Cache-Auswahl, baut Default-DTO.
- `public process_dynamic_submission(): stdClass` — normalisiert Auswahl/Teacher-Selektion, schreibt Cache.
- `public definition(): void` — **groß**: Hidden-Felder + View-Mode-Verzweigung (4 Branches, mehrere early returns).
- `public validation($data, $files): array` — **groß**: userdefined vs. fixed, max-slots, Teacher-Required, `evaluate_slot_for_user`.
- `protected get_page_url_for_dynamic_submission(): moodle_url`
- `private static get_open_slots(int, int): array` — Wrapper über DTO.
- `private static to_open_slots(array): array` — DTO→flache Slot-Form.
- `private static get_custom_duration_options(?object): array` / `get_default_custom_duration(...)` — userdefined Dauer.
- `private static get_custom_open_days(int, int): array` — **~100 LOC** Kalenderberechnung für userdefined Slots.
- `private static time_to_seconds(string): int` / `parse_days_of_week(string): array` — Helpers.
Schuld: `definition()`/`validation()` mit mehreren tiefen Verzweigungen und early-returns; HTML/Style
inline (`html_writer::div` mit Inline-`style`), `get_custom_open_days` mischt Verfügbarkeits-Eval mit
Kalenderaufbau (slotbooking_form.php:618-716).

#### slotupdate_form (condition/slotupdate_form.php, 283 LOC) — erbt slotbooking_form
Verantwortung: „Update booking" (Move/Cancel/Change einer gebuchten Antwort). Erbt Input-Layer,
ersetzt Submit-Half via `slot_update_service::plan()/apply()` (Zwei-Pass-Confirm).
Kollaborateure: `slot_update_service`, `slot_mover`, `slot_change_policy`.
- `protected check_access_for_dynamic_submission(): void` — Self-Service vs. Manager-Gate (`moveslotsself`/`moveslots`/`updatebooking`).
- `public definition(): void` — Hidden-Felder VOR `parent::definition()` (wegen parent early-returns).
- `public set_data_for_dynamic_submission(): void` — current/locked-Keys vorselektieren, Picker-Snapshot überschreiben.
- `public validation($data, $files): array` — `slot_update_service::plan()` blocking checks.
- `public process_dynamic_submission(): stdClass` — Pass 1 needsconfirm, Pass 2 commit.
- `private static require_booking_lib(): void` / `extract_keys($selection): array` — Helpers.
Score B: sauber strukturierte Subklasse, gute Doku.

#### teacherunavailability_form (teacherunavailability_form.php, 884 LOC) — größte Datei
Verantwortung: Teacher markiert Slots als (un)verfügbar, scope-abhängig (system/instance/option),
direkte DB-CRUD in `booking_teacher_unavailability` mit Transaktion.
Kollaborateure: `slot_availability`, `singleton_service`, `booking_check_if_teacher`, direktes `$DB`.
- `protected get_context_for_dynamic_submission(): context` / `check_access_for_dynamic_submission(): void` (Teacher-of-option ODER manage-Cap).
- `public set_data_for_dynamic_submission(): void` — baut Slot-Entries, vorselektiert aus DB-(Un)Verfügbarkeit.
- `public process_dynamic_submission(): stdClass` — **~130 LOC**: Transaktion, scope-Delete (incl. `get_in_or_equal`), Insert pro Slot-Key.
- `public definition(): void` — scope/markmode/viewmode-Selects, calendar/list-Rendering.
- `public validation($data, $files): array`
- `private get_formdata(): array` — ajax/customdata-Brücke.
- `private normalize_scope/markmode/viewmode(string): string` — Enum-Guards.
- `private get_bookingid_for_option(int): int`
- `private get_slot_option_records(int): array` / `get_all_slot_option_records(): array` — slot-Optionen je Instanz/global.
- `private get_scope_target_optionids(string,int,int): array`
- `private get_slot_entries(array): array` — Slot-Range-Aufbau (6 Wochen rückwärts/18 vorwärts).
- `private has_submitted_selection(array): bool` / `extract_selected_slot_keys(array,array): array`
- `private get_unavailable_key_set(array,int,string,int): array` — DB-Lookup + Overlap-Match.
Schuld: Größte Form-Klasse, vereint Form-UI + SQL + Transaktion + Scope-Algorithmik; `process` und
`set_data` duplizieren den `$effectivedata`-Aufbau (teacherunavailability_form.php:141-152 vs. 226-235).
Direkte `$DB`-CRUD statt Service-Schicht. **P2**.

#### slotteacherassignments_form (slotteacherassignments_form.php, 431 LOC)
Verantwortung: Student→Teacher(Examiner)-Zuordnung für Slotbooking-Optionen, DB-Mapping
`booking_slot_student_teacher`.
Kollaborateure: `singleton_service`, `user_get_users_by_id`, `get_enrolled_users`, direktes `$DB`.
- Standard-Hooks (`get_context`/`check_access`/`page_url`).
- `public set_data_for_dynamic_submission(): void` — Vorbelegung pro Student (assigned ODER Default-Teacher).
- `public process_dynamic_submission(): stdClass` — Transaktion: delete-all + reinsert.
- `public definition(): void` — pro Student ein Autocomplete (ajax `form_teachers_selector`).
- `public validation($data,$files): array` — leer.
- `private get_option_settings(): ?booking_option_settings` / `get_slot_context(): ?context_module`.
- `private get_teacher_and_student_ids(): array` — Teacher-Pool aus `slotconfig->teacher_pool`, Studenten aus Enrolment.
- `private get_assigned_by_student(array): array` — bestehendes Mapping.
- `private static field_name(int,int): string` — **toter Helper** (nicht verwendet, slotteacherassignments_form.php:411).
- `private get_formdata(): array`.
Schuld: ungenutzte `field_name()`; direkte DB-CRUD; valuehtmlcallback-Closure dupliziert
editteachersforoptiondate_form. **P2**.

#### customform_form (condition/customform_form.php, 425 LOC)
Verantwortung: Rendert die in der `customform`-Availability-Condition definierten dynamischen
Felder (static/checkbox/shorttext/select/url/mail/delete/enrolusersaction), schreibt Eingaben in
`customformstore`-Cache.
Kollaborateure: `customform` (return_formelements), `customformstore`, `singleton_service`,
`booking_answers`.
- `protected get_context_for_dynamic_submission(): context` / `check_access...(): void`.
- `public static require_userid_access(int,int): void` — bookforothers-Gate für Fremd-User-Daten.
- `public set_data_for_dynamic_submission(): void` — Cache→Felder.
- `public process_dynamic_submission(): stdClass` — Cache schreiben.
- `public definition(): void` — **~240 LOC, sehr groß**: verschachtelter `switch($formtype)`, select-Branch
  parst CSV-Zeilen mit magischen Index-Positionen (`$linearray[0..4]`: key, label, limit, price, allowedusers).
- `public validation($data,$files): array` — delegiert an `customformstore::validation`.
- `protected get_page_url_for_dynamic_submission(): moodle_url`.
Schuld: Magische Array-Indizes in der select-Verarbeitung ohne benannte Struktur
(customform_form.php:222-288); tiefe Verschachtelung; Mischung aus Rendering, Verfügbarkeits- und
Preis-Logik im definition(). **P2**.

#### editteachersforoptiondate_form (editteachersforoptiondate_form.php, 438 LOC)
Verantwortung: Teacher-Zuordnung + Substitutions-Deductions pro optiondate, mit Event-Trigger und
Cache-Purge.
Kollaborateure: direkter `$DB`, `optiondates_teacher_added/deleted`-Events, `cache_helper`,
`singleton_service`.
- Standard-Hooks.
- `public set_data_for_dynamic_submission(): void` — Reason + Deductions aus DB.
- `public process_dynamic_submission()` — **~130 LOC**: diff add/delete Teacher (Events), Reason-Update,
  Deduction insert/update/delete; mehrfach `cache_helper::purge_by_event('setbackcachedteachersjournal')`.
- `public definition()` — Autocomplete (ajax) + Deduction-Header (cap-gated).
- `public validation($data,$files)` — Reason-Pflicht bei fehlendem/Substitut-Teacher.
- `protected get_page_url_for_dynamic_submission(): moodle_url`.
Schuld: lange `process`-Methode mit verschachtelten DB-Operationen + wiederholten Cache-Purges
(editteachersforoptiondate_form.php:148,174,207,219,235). **P2**.

#### option_form_bulk (option_form_bulk.php, 341 LOC)
Verantwortung: Bulk-Bearbeitung ausgewählter Felder über mehrere Buchungsoptionen; baut die Feldwahl
aus `option\fields`-Namespace + Customfields, appliziert pro Option via `booking_option::update()`.
Kollaborateure: `core_component::get_component_classes_in_namespace`, `fields_info`, `booking_handler`,
`booking_option`, `booking::get_all_cmids`.
- `public definition()` — Whitelist `$includedclasses`, no-submit-Button, Hidden-Carry der gewählten Felder.
- `public definition_after_data()` — appliziert `instance_form_definition` der gewählten Klassen; **entfernt
  Header-Elemente per Schleife über `$mform->_elements`** (Hack, option_form_bulk.php:193-197).
- `private apply_instance_form_definition(&$mform,$formdata,$classname)`.
- `public validation()` — leer.
- Standard-Hooks; `check_access` ist **leer** (option_form_bulk.php:254).
- `public set_data_for_dynamic_submission(): void`.
- `public process_dynamic_submission()` → `save_options`.
- `public static save_options(stdClass, int[]): void` — Test-Hook; Template-Fallback-cmid-Logik.
Schuld: hartkodierte Feld-Whitelist; Zugriff auf privates `$mform->_elements`; leeres `check_access`. **P2**.

#### dynamicdeputyselect (dynamicdeputyselect.php, 320 LOC)
Verantwortung: Auswahl/Speicherung von „Deputies" in einem Custom-User-Profile-Field, inkl.
Rollen-Enrol/Unenrol als Supervisor.
Kollaborateure: **externe Plugins** (`local_taskflow` Config `supervisorrole`,
`bookingextension_confirmation_supervisor` Config), `singleton_service`, `profile_save_custom_fields`,
`role_assign/role_unassign`, direkter SQL.
- `public definition()` — Autocomplete `form_users_selector`.
- `public process_dynamic_submission()` → `update_user_field`.
- `public set_data_for_dynamic_submission(): void` — bestehende Deputies aus Profilfeld.
- `private update_user_field($value)` — parst IDs aus Anzeigestring (`preg_match '/\(ID:\s*(\d+)\)/'`).
- `private enrol_deputies(string,array)` — diff add/delete, Rollen-(Un)Zuweisung, **roher Mehrzeilen-SQL** mit LIKE.
- Standard-Hooks.
- `public static get_display_deputies_data(): array` — Anzeige-Daten via `confirmbooking`-Klasse (class_exists-Gate).
Schuld: starke Kopplung an zwei Fremd-Plugins; ID-Extraktion aus Anzeige-String (fragil,
dynamicdeputyselect.php:122-129); LIKE-basierter SQL gegen serialisierte Profilfeld-Daten. **P2**.

#### modal_send_custom_message (modal_send_custom_message.php, 345 LOC)
Verantwortung: Custom-Nachricht an gebuchte User aus report2; sendet via `message_controller`,
triggert `custom_message_sent`/`custom_bulk_message_sent`-Events, verarbeitet Datei-Anhang.
Kollaborateure: `message_controller`, Events, `singleton_service`, `cache_helper`, file-API.
- `private get_possible_recipients_for_custom_message(int): array` — gebuchte User (SQL).
- `public definition()` / `validation()` / Standard-Hooks.
- `public set_data_for_dynamic_submission(): void` — Vorselektion aus checkedids, Draft-Itemid.
- `public process_dynamic_submission()` — **~120 LOC**: Anhang-Draft→Tempfile, Send-Loop, Bulk-Event-Heuristik (≥3 User & ≥75%).
Schuld: lange process-Methode mit Datei-, Mess- und Event-Logik gebündelt. **P2**.

#### modal_change_notes / modal_change_status (optiondates/, 276/283 LOC)
Verantwortung: Notes bzw. Presence-Status für Buchungen pro optiondate/option-Scope ändern.
Kollaborateure: `optiondate_answer`, `singleton_service`, `booking_option::edit_notes`/`changepresencestatus`.
Beide nahezu identisch (Hooks, `process_dynamic_submission` mit `switch($scope)`, `get_page_url`-Logik
1:1 dupliziert). Schuld: **starke Duplizierung zwischen den zwei Klassen** (process/page-url fast
identisch). **P2** (gemeinsame Basisklasse anbieten).

#### customfield (customfield.php, 222 LOC) — klassisch moodleform
Verantwortung: Verwaltung der Booking-Customfields als **Plugin-Config** (`set_config`/`unset_config`),
nicht als Tabelle.
- `public definition()` — repeat_elements für Feldnamen/Typ/Optionen/Delete.
- `public validation()` — delegiert an parent.
- `public get_data()` — **Geschäftslogik im Getter**: Delete-Config, Auto-Naming via 300-Iterations-Loop
  (`customfield_0..299`), `set_config` je Feld, Event `custom_field_changed`.
Schuld: Persistenz-/Delete-Logik in `get_data()` statt in process-Schritt; Config-as-Storage;
300er-Loop für freien Config-Namen (customfield.php:195-201). **P2**.

#### rulesform (rulesform.php, 367 LOC)
Verantwortung: Rule-Editor (dynamic), delegiert Felder/Speichern an `rules_info`, große
typabhängige `validation()`.
Kollaborateure: `rules_info`, `templaterule`, direkter `$DB` (Template-Records).
- Standard-Hooks; `check_access` per `contextid` + `editbookingrules`.
- `public definition()` / `definition_after_data()` (Template-Override-Logik).
- `public process_dynamic_submission()` → `rules_info::save_booking_rule`.
- `public set_data_for_dynamic_submission(): void` — Template/Existing-Branches.
- `public validation($data,$files)` — **groß**: Switch über bookingruletype/conditiontype/actiontype,
  Platzhalter-`{#...}{/...}`-Prüfung.
- `private prepare_ajaxformdata(array&)` — JSON-Decode der Rule.
Schuld: Validation kennt konkrete Rule-/Condition-/Action-Typnamen (Wissen verteilt zwischen Form und
rules_info). **P2**.

#### customform_prefill (local/customform_prefill.php, 315 LOC) — Helper
Verantwortung: Mappt `prefill_*`-URL-Parameter (optionview) auf `customformstore`-Cache-Werte, damit
Customform-Felder vorbefüllt erscheinen.
Kollaborateure: `customform::return_formelements`, `customformstore`, `clean_param`, `optional_param`.
- `public static is_enabled(): bool` — Setting `customformprefillenabled`.
- `public static prefill_from_request(booking_option_settings,int): bool`.
- `public static build_prefill_data(...): stdClass`.
- `private static get_prefill_params_from_request(...): array`.
- `private static get_identifier_for_formelement(...)` / `find_prefill_key_for_formelement(...)`.
- `private static sanitize_prefill_value(...)` / `get_optional_param_type(...)` / `sanitize_select_prefill_value(...)`.
- `private static normalize_prefill_key(string): string`.
Score B: gut gekapselt, typsicher; einzige neuere Datei mit klarer Single-Responsibility.

### Triviale / Delegations-Formulare (gebündelt)
- **Delete-Confirm-Formulare** (`deleteactionsform`, `deletecampaignform`, `deletecertificateconditionform`,
  `deleteruleform`, `subbookingsdeleteform`): identisches Muster — Hidden-id + HTML-Warnung + `*_info::delete_*`.
- **Delegations-Formulare** (`actionsform`, `campaignsform`, `subbookingsform`, `certificateconditionsform`,
  `option_form`): Felder/Save kommen aus `*_info`/`fields_info`; Form ist Hülle + Validierung.
- **Klassische Filter/Report-Formulare** (`teacher_performed_units_report_form`,
  `teachers_instance_report_form`, `subscribe_cohort_or_group_form`, `subscribeusersactivity`,
  `confirmactivity`, `importoptions_form`, `instancetemplateadd_form`): einfache `\moodleform` mit
  `definition()`+`validation()`.
- **sync_rule_form / sync_rule_activate_form / sync_rule_delete_form**: Sync-Enrolment-Regeln,
  delegieren komplett an `local\sync\booking_enrolment` (save/activate/delete/Impact). Sauber.
- **Repeat-CRUD-Formulare** (`dynamicsemestersform`, `dynamicholidaysform`, `modaloptiondateform`,
  `pricecategories_form`): repeat_elements + direkte/handler-basierte DB-Diff-CRUD.

## Persistenz

**Direkte DB-Tabellen (von Form-Klassen geschrieben/gelesen):**
- `booking_teacher_unavailability` — teacherunavailability_form (Transaktion).
- `booking_slot_student_teacher` — slotteacherassignments_form (Transaktion).
- `booking_optiondates_teachers`, `booking_odt_deductions`, `booking_optiondates` — editteachersforoptiondate_form.
- `booking_holidays` — dynamicholidaysform.
- `booking_semesters` — dynamicsemestersform.
- `booking_customfields` + Plugin-Config (`set_config`/`unset_config`) — customfield.
- `booking_rules`, `booking_cert_cond`, `booking_subbooking_options`, `booking_sync_rules`,
  `booking_answers`, `user`, `user_info_data/field` — diverse Forms lesend bzw. via prepare/impact.
- `task_adhoc` (geprüft/queued) — dynamicchangesemesterform.

**Caches (MUC):**
- `mod_booking/conditionforms` — bookingpolicy_form.
- `mod_booking/subbookingforms` — additionalperson_form.
- `customformstore` (App-Cache) — customform_form, customform_prefill.
- `slotbookingstore` (App-Cache) — slotbooking_form, slotupdate_form.
- Cache-Purges: `setbackcachedteachersjournal`, `setbacksemesters`, `setbackbookedusertable`,
  `setbackoptionsettings`, `setbackeventlogtable`.

**Events getriggert:**
`optiondates_teacher_added/deleted`, `custom_message_sent`, `custom_bulk_message_sent`,
`custom_field_changed`, `booking_option::trigger_updated_event` (actionsform).

## Extension-Points

- **`core_form\dynamic_form`** — alle dynamischen Formulare implementieren das Standard-Interface
  (5 Pflicht-Hooks). Externe Aufrufer rendern via `core_form/dynamicform` AMD.
- **Vererbung:** `slotbooking_form` → `slotupdate_form` (einziger Form-Subklassen-Punkt; Input-Layer
  wiederverwendbar).
- **Delegations-Verträge zu Info-/Service-Klassen** (außerhalb S16, aber von hier konsumiert):
  `fields_info`, `rules_info`, `campaigns_info`, `subbookings_info`, `actions_info`,
  `certificate_conditions`/`filters_info`/`conditions_info`/`actions_info`,
  `slot_update_service`/`slot_mover`/`slot_change_policy`/`slot_availability`/`slot_dto`,
  `local\sync\booking_enrolment`, `pricecategories_handler`, `dates_handler`, `slot_rule_manager`.
- **AJAX-Selector-Callbacks** (autocomplete `ajax`-Option): `mod_booking/form_teachers_selector`,
  `form_users_selector`, `form_sync_source_selector`, `core/form-cohort-selector`.
- **Plugin-Hooks (optional):** dynamicdeputyselect bindet via `class_exists`/`get_config` optional
  `bookingextension_confirmation_supervisor` und `local_taskflow` ein.

## Bekannte Schulden (→ Blueprint)

**P2 (Refactor empfohlen):**
1. **teacherunavailability_form (884 LOC):** vereint Form-UI + SQL + Transaktion + Scope-Algorithmik.
   Persistenz/Scope-Logik in einen `slot_unavailability_service` auslagern; `$effectivedata`-Aufbau
   ist zwischen `set_data`/`process` dupliziert (:141-152 / :226-235).
2. **slotbooking_form (759 LOC):** `definition()`/`validation()` mit tiefen View-Mode-Verzweigungen;
   `get_custom_open_days` (:618-716) mischt Verfügbarkeits-Eval mit Kalenderaufbau. Inline-`style`-Strings.
3. **customform_form (:222-288):** magische Array-Indizes `$linearray[0..4]` in der select-Verarbeitung;
   Rendering + Verfügbarkeit + Preis im definition() vermengt.
4. **customfield (:161-221):** Geschäftslogik (Config-CRUD, 300er-Auto-Naming-Loop, Event) in `get_data()`
   statt im Submit-Schritt; Config-as-Storage.
5. **dynamicdeputyselect:** harte Kopplung an `local_taskflow` + `bookingextension_confirmation_supervisor`;
   fragile ID-Extraktion aus Anzeigestring (:122-129); LIKE-SQL gegen serialisierte Profilfelder.
6. **modal_change_notes ↔ modal_change_status:** ~90% Duplizierung (process/get_page_url); gemeinsame
   Basisklasse anbieten.
7. **option_form_bulk:** Zugriff auf privates `$mform->_elements` zum Header-Entfernen (:193-197);
   leeres `check_access_for_dynamic_submission()` (:254); hartkodierte Feld-Whitelist (:77-111).
8. **editteachersforoptiondate_form / modal_send_custom_message:** überlange `process`-Methoden mit
   DB/Event/Datei-Logik; wiederholte Cache-Purges.
9. **slotteacherassignments_form:** toter Helper `field_name()` (:411); valuehtmlcallback-Closure
   dupliziert editteachersforoptiondate_form.
10. **rulesform:** `validation()` kennt konkrete Rule-/Condition-/Action-Typen (Wissen verteilt
    zwischen Form und rules_info).

**P3 (kleinere Schulden):**
- Wiederkehrendes Boilerplate `$this->_customdata ?? $this->_ajaxformdata` und `get_formdata()`-Brücken
  könnten in ein gemeinsames Trait/Basisklasse.
- `confirmactivity` nutzt veraltete `utils\db`-Helper; `subscribeusersactivity`/`confirmactivity` ohne
  explizite `check_access` (klassische moodleform, Page-seitig geschützt).
- Mehrere Formulare ohne `setType()` für Hidden-Felder (Moodle-4.5-Debug-Warnungen, vgl. Fix in
  rulesform.php:54).
