# S21 — entry_scripts

## Zweck & Grenzen

Dieses Subsystem umfasst alle **Top-Level-PHP-Dateien direkt im Plugin-Root** `/mod/booking/`
(nicht rekursiv). Es sind die HTTP-Einstiegspunkte (Pages, Actions, AJAX-Endpunkte,
Download-Endpunkte) sowie die Moodle-Plugin-Callback-Dateien (`lib.php`, `locallib.php`,
`version.php`, `settings.php`, `mod_form.php`).

Tatsächlich gefunden: **76 Dateien** (`ls *.php`, Top-Level). Die Aufgabenstellung nannte 74
Einstiegspunkte; die Differenz erklärt sich dadurch, dass `lib.php`, `locallib.php`, `version.php`,
`settings.php`, `mod_form.php` keine HTTP-Einstiegspunkte im engeren Sinn sind, sondern
Plugin-Infrastruktur, und einige `*_form.php`/`*.class.php` reine Formular-Klassendateien sind.

**Grenzen:** Die Skripte enthalten kaum eigene Geschäftslogik. Sie sind durchweg **Controller im
Page-Controller-Stil**: Parameter einlesen (`required_param`/`optional_param`), Login/Capability
prüfen, `$PAGE` konfigurieren, eine Domänen-/Output-/Table-/Form-Klasse instanziieren, rendern. Die
eigentliche Logik liegt außerhalb dieses Subsystems (in `classes/`): `singleton_service`,
`booking_option`, `booking`, `output\*`-Renderables, `table\*`-wunderbyte_tables, `form\*`-DynamicForms.

## Position im Gesamtsystem

Diese Dateien sind die **äußerste Schicht** (Web-Tier). Sie werden vom Browser/Moodle-Router direkt
aufgerufen. Typischer Aufrufgraph:

```
HTTP Request
  → <entry>.php (config.php, require_login, capability, $PAGE setup)
      → singleton_service::get_instance_of_*()        (Domänenobjekte, S-Core)
      → mod_booking\output\* (Renderable)  → renderer (S-output)
      → mod_booking\table\*  (wunderbyte_table)        (S-tables)
      → mod_booking\form\*   (DynamicForm/moodleform)  (S-forms)
      → booking_option::update() / ::delete_*()        (Mutationen, S-Core)
  → echo $OUTPUT->header()/footer()
```

Zentrale, **außerhalb dieses Scopes** liegende Kollaborateure (siehe notes): `singleton_service`,
`booking`, `booking_option`, `booking_answers`, `message_controller`, `signinsheet_generator`,
`checklist_generator`, alle `output\*`-Renderables, alle `table\*`-Klassen, alle `form\*`-Klassen,
`utils\wb_payment`, `local_wunderbyte_table\wunderbyte_table`, `local_shopping_cart`.

## Schlüsselkonzepte

- **Page-Controller-Muster:** jede Datei = ein Endpunkt. Reihenfolge: params → `get_course_and_cm_from_cmid` → `require_course_login` → `context_module::instance` → `require_capability` → `$PAGE->set_url/title/heading` → render.
- **Drei Generationen von UI:**
  1. *Legacy-prozedural* (`index.php`, `category.php`, `tag.php`, `categories.php`, `otherbooking.php`): `html_table`/`html_writer` direkt.
  2. *moodleform-basiert* (`importexcel.php`, `confirmactivity.php`, `sendmessage.php`, `categoryadd.php`, `instancetemplateadd.php`): klassisches `$mform->get_data()`.
  3. *DynamicForm/AMD-basiert* (`editoptions.php`, `importoptions.php`, `edit_rules.php`, `edit_campaigns.php`, `semesters.php`, `pricecategories.php`, `teacherunavailability.php`, `slotteacherassignments.php`): Container-`div` + `js_call_amd`, Form lädt sich per WS nach.
- **wunderbyte_table-Download-Endpunkte:** `download.php`, `download_report2.php`, `download_optiondates_teachers_report.php` rekonstruieren die Tabelle aus `instantiate_from_tablecache_hash($encodedtable)` und exportieren sie.
- **PRO-Gating:** viele Admin-Seiten prüfen `wb_payment::pro_version_is_activated()` und zeigen sonst `infotext:prolicensenecessary`.
- **Slotbooking-Cluster (2026):** `moveslot.php`, `rebookslot.php`, `slotcalendar.php`, `slotrules.php`, `slotteacherassignments.php`, `teacherunavailability.php` — neuer Feature-Bereich, teils noch dünn/host-only.
- **AJAX/JSON-Endpunkte:** `rating_rest.php`, `search_sync_sources.php`, `sync_diagnostics.php` geben JSON aus.
- **Redirect-/Action-only:** `bookingredirect.php`, `link.php`, `moveoption.php`, `bulk_book_handler.php`, `unsubscribe.php`, `recalculateprices.php`.

## Datenfluss

Lese-Pfad (z.B. `view.php`): cmid → `singleton_service::get_instance_of_booking_by_cmid` → `output\view`
→ `renderer::render_view` → HTML. Mutations-Pfad (z.B. `editoptions.php`/`edit_optiontemplates.php`):
Form-Submit → `booking_option::update($fromform, $context)` → Draft-Files speichern → redirect.
Download-Pfad: `encodedtable`-Hash → wunderbyte_table → `printtable(20, true)` → CSV/XLSX. Task-Pfad
(`recalculateprices.php`, `bulk_book_handler.php`): Adhoc-Task queuen → redirect.

## Dateien & Klassen

| Datei | Klasse/Funktion | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| version.php | (plugin meta) | Meta | 36 | 0 | A | - |
| bookingredirect.php | (script) | Action/Redirect | 48 | 0 | B | - |
| customfieldsettings.php | (script) | Page/Admin | 49 | 0 | A | - |
| customfield.php | (script) | Page/Admin | 53 | 0 | A | - |
| viewpolicy.php | (script) | Page | 54 | 0 | A | - |
| bookinginstancetemplatessettings.php | (script) | Page/Table | 59 | 0 | B | - |
| option_date_template.php | (script) | Page | 61 | 0 | B | - |
| teacher.php | (script) | Page | 64 | 0 | A | - |
| download_report2.php | (script) | Download | 65 | 0 | B | - |
| rating_rest.php | (script) | AJAX/JSON | 68 | 0 | C | P3 |
| search_sync_sources.php | (script) | AJAX/JSON | 69 | 0 | A | - |
| sync_diagnostics.php | (script) | AJAX/JSON | 69 | 0 | A | - |
| tag.php | (script) | Page | 70 | 0 | B | - |
| importexcel_form.php | importexcel_form | Form | 71 | 2 | A | - |
| download_optiondates_teachers_report.php | (script) | Download | 73 | 0 | B | - |
| pricecategories.php | (script) | Page/DynForm | 73 | 0 | A | - |
| bulk_book_handler.php | (script) | Action/Task | 75 | 0 | A | - |
| category.php | (script) | Page | 75 | 0 | C | P3 |
| performance.php | (script) | Page | 75 | 0 | A | - |
| download.php | (script) | Download | 76 | 0 | B | - |
| instancetemplatessettings.php | (script) | Page/Table | 77 | 0 | B | - |
| importoptions.php | (script) | Page/DynForm | 79 | 0 | A | - |
| edit_campaigns.php | (script) | Page/DynForm | 80 | 0 | A | - |
| subscribeusersactivity.php | (script) | Page/Form | 80 | 0 | B | - |
| optionformconfig.php | (script) | Page | 81 | 0 | B | - |
| sendmessageform.class.php | mod_booking_sendmessage_form | Form | 85 | 1 | B | - |
| moveslot.php | (script) | Page/Slot | 86 | 0 | B | - |
| rebookslot.php | (script) | Page/Slot | 86 | 0 | B | - |
| tagtemplatesadd_form.php | tagtemplatesadd_form | Form | 86 | 3 | B | - |
| slotcalendar.php | (script) | Page/Slot | 87 | 0 | B | - |
| viewconfirmation.php | (script) | Page | 90 | 0 | A | - |
| enrollink.php | (script) | Page | 91 | 0 | B | - |
| instancetemplateadd.php | (script) | Page/Form | 92 | 0 | B | - |
| mybookings.php | (script) | Page | 92 | 0 | B | - |
| categories.php | (script) | Page | 94 | 0 | C | P3 |
| confirmactivity.php | (script) | Page/Form | 94 | 0 | B | - |
| optiontemplatessettings.php | (script) | Page/Table | 94 | 0 | C | P3 |
| tagtemplatesadd.php | (script) | Page/Form | 96 | 0 | B | - |
| slotteacherassignments.php | (script) | Page/Slot/DynForm | 97 | 0 | B | - |
| tagtemplates.php | (script) | Page | 97 | 0 | B | - |
| semesters.php | (script) | Page/DynForm | 98 | 0 | B | - |
| recalculateprices.php | mod_booking (ns script) | Action/Task | 103 | 0 | B | - |
| moveoption.php | (script) | Action/Page | 105 | 0 | C | P3 |
| otherbookingaddrule.php | (script) | Page/Form | 106 | 0 | C | P3 |
| categoriesform.class.php | mod_booking_categories_form | Form | 107 | 2 | B | - |
| index.php | (script) | Page | 107 | 0 | B | - |
| scheduledmails.php | (script) | Page (debug) | 107 | 0 | C | P3 |
| edit_certificateconditions.php | (script) | Page/DynForm | 109 | 0 | B | - |
| otherbookingaddrule_form.php | otherbookingaddrule_form | Form | 113 | 3 | C | P3 |
| unsubscribe.php | (script) | Action | 114 | 0 | B | - |
| teachers.php | (script) | Page | 116 | 0 | C | P3 |
| categoryadd.php | (script) | Page/Form/Action | 121 | 0 | C | P3 |
| link.php | (script) | Action/Redirect | 122 | 0 | C | P3 |
| teacherunavailability.php | (script) | Page/Slot/DynForm | 124 | 0 | B | - |
| teachers_form.php | mod_booking_teachers_form | Form | 126 | 1 | C | P3 |
| edit_optiontemplates.php | (script) | Page/Form/Action | 130 | 0 | C | P2 |
| edit_rules.php | (script) | Page/DynForm | 132 | 0 | B | - |
| editoptions.php | (script) | Page/DynForm | 132 | 0 | B | - |
| otherbooking.php | (script) | Page | 135 | 0 | C | P3 |
| importexcel.php | (script) | Page/Form/Action | 147 | 0 | C | P2 |
| optiondates_teachers_report.php | (script) | Page/Table | 162 | 0 | C | P3 |
| sendmessage.php | send_custom_message() | Page/Form/Action | 168 | 1 | C | P2 |
| availabilityconditions.php | (script) | Page/Admin/Action | 169 | 0 | C | P3 |
| optionview.php | (script) | Page | 173 | 0 | D | P2 |
| subbooking_timetabletest.php | (script) | Page (test/dead) | 182 | 0 | D | P2 |
| locallib.php | (legacy lib funcs) | Lib | 202 | 7 | C | P3 |
| view.php | (script) | Page (Haupt) | 224 | 0 | C | P2 |
| teachers_instance_report.php | (script) | Page/Table | 246 | 0 | D | P2 |
| teacher_performed_units_report.php | (script) | Page/Table | 258 | 0 | D | P2 |
| slotrules.php | (script) | Page/Form/Action/Slot | 333 | 0 | D | P1 |
| report2.php | (script) | Page (BookingsTracker) | 482 | 0 | D | P1 |
| subscribeusers.php | (script) | Page/Action | 583 | 0 | E | P1 |
| report.php | (script) | Page/Action (god) | 1596 | 0 | E | P0 |
| mod_form.php | mod_booking_mod_form | Form (Instanz) | 1781 | 9 | E | P0 |
| settings.php | (admin settings tree) | Admin | 2788 | 0 | E | P0 |
| lib.php | (Moodle callbacks) | Lib (god) | 2941 | 40+ | E | P0 |

## Persistenz

Die Entry-Scripts greifen teils direkt per `$DB` auf folgende Tabellen zu (Anti-Pattern, sollte in
Domänenklassen liegen):

- `booking`, `booking_options`, `booking_optiondates`, `booking_optiondates_teachers`,
  `booking_answers`, `booking_teachers`, `booking_category`, `booking_tags`, `booking_other`,
  `booking_ratings`, `booking_customfields`, `booking_instancetemplate`, `booking_slot_rule`,
  `booking_slot_rule_price` (direkte CRUD u.a. in `categoryadd.php`, `tagtemplatesadd.php`,
  `otherbookingaddrule.php`, `optiontemplatessettings.php`, `instancetemplatessettings.php`,
  `importexcel.php`, `slotrules.php`, `unsubscribe.php`, `rating_rest.php`).
- Moodle-Core: `course`, `course_modules`, `modules`, `user`, `cohort`, `groups`, `tag_instance`,
  `task_adhoc`.
- **Config** (`get_config('booking', …)`/`set_config`): u.a. `uselegacymailtemplates`,
  `teachersnologinrequired`, `availabilityconditionsettings`, `allteacherspagebookinginstances`,
  `defaultpriceformula`, `globalcurrency`, `bookingstracker`. `settings.php` definiert den gesamten
  Admin-Settings-Baum (`admin_setting_*`).
- **Caches:** `cache_helper::purge_by_event('setbackeventlogtable')` (sendmessage.php),
  `booking_option::purge_cache_for_answers` (unsubscribe.php), Table-Cache `cachedteachersjournal`
  (optiondates_teachers_report.php), wunderbyte_table tablecache-Hash (download*-Endpunkte).
- **User Preferences:** `teachersinstancereport_teacherid`, `unitsreport_filterstartdate/enddate`,
  `bookingstrackerviewtype`.

## Extension-Points

- **Moodle-Plugin-Hooks (`lib.php`):** `booking_add_instance`, `booking_update_instance`,
  `booking_delete_instance`, `booking_supports`, `booking_pluginfile`, `booking_extend_settings_navigation`,
  `booking_myprofile_navigation`, `booking_get_coursemodule_info`, `mod_booking_cm_info_view`,
  Grading-/Rating-/Comment-Callbacks, `booking_reset_course_form_*`.
- **Instanz-Formular:** `mod_booking_mod_form` (`mod_form.php`) erweitert `moodleform_mod`
  (Completion-Rules, `data_preprocessing`, `validation`, `data_postprocessing`).
- **Fremd-Plugin-Integration:** `view.php` bindet optional `bookingextension_agent` (AI-Tab) ein;
  `optionview.php`/`mybookings.php` integrieren `local_shopping_cart`.
- **AMD/DynamicForm:** zahlreiche Seiten hängen JS-Module per `js_call_amd` ein und delegieren
  Validierung/Persistenz an `\mod_booking\form\*`-DynamicForms (Engine bleibt in `classes/`).
- **Admin External Pages:** `admin_externalpage_setup(...)` registriert Seiten im Admin-Baum
  (definiert in `settings.php`).

## Bekannte Schulden (→ Blueprint)

**P0 — God-Files / Hotspots:**
- `report.php:1` — **1596 LOC** prozeduraler Controller, mischt Param-Parsing, SQL-WHERE-Bau
  (`$addsqlwhere`, `report.php:114-132`), PDF-Generierung, mehrere POST-Aktionen
  (`$_POST['deleteusers']` etc., `report.php:329-459`), Direktzugriff `$_SERVER`/`$_POST`. Kaum
  testbar; SQL-Stringbau im Controller. **Aufteilen** in Action-Handler + Service.
- `lib.php:1` — **2941 LOC**, 40+ globale Funktionen (`booking_add_instance` 741-998,
  `booking_update_instance` 999+, `booking_extend_settings_navigation` 1372+). Typische Moodle-lib,
  aber sehr groß und mit viel Logik die in Domänenklassen gehörte.
- `settings.php:1` — **2788 LOC** linearer Admin-Settings-Baum; schwer zu navigieren, viele
  `set_updatedcallback`-Closures.
- `mod_form.php:141` — `definition()` ist sehr lang; Klasse 1781 LOC, viel Verzweigung.

**P1:**
- `subscribeusers.php:1` — **583 LOC**, mehrere Aktionen (subscribe/unsubscribe/sync-toggle/cohort),
  `(bool) $subscribesuccess = false;` (subscribeusers.php:62 — wirkungsloser Cast), Policy-Agree-Flow,
  user-selector + booked_users-Render in einem Skript.
- `report2.php:1` — **482 LOC** mit 5-fach dupliziertem Scope-Aufbau (System/Course/Instance/Option/
  Optiondate, report2.php:40-385); stark redundante `moodle_url`-Konstruktion.
- `slotrules.php:1` — **333 LOC** Controller baut Regel-Tabelle inkl. Preis-Subqueries und
  Weekday-Mapping inline (slotrules.php:249-329); Persistenz-Mapping (slotrules.php:162-204) gehört in
  `slot_rule_manager`.

**P2:**
- `optionview.php:140` — eigener TODO-Kommentar: „The following lines change the context of the PAGE
  object … This needs to be fixed."; Login-/Policy-/Capability-Logik stark verschachtelt
  (optionview.php:100-133).
- `view.php:52-77` — Legacy-Mail-Acknowledgement-Block hartcodiert im Haupt-Controller (mit eigenem
  „can be removed"-Kommentar).
- `edit_optiontemplates.php:83-105` — `booking_option::update` + Draft-File-Handling inline dupliziert
  (auch in editoptions/anderen); flüchtige Template-Speicherlogik.
- `importexcel.php:82-137` — CSV-Parsing + Completion-Update direkt im Controller (`$DB->update_record`
  pro Zeile), kein Service.
- `teachers_instance_report.php` / `teacher_performed_units_report.php` — je ~250 LOC mit **doppeltem
  SQL-Block** (Anzeige vs. Download, fast identisch); DB-Cast-Ausdrücke (`cast(... as decimal)`) inline.

**P3 / kleinere Schulden:**
- `rating_rest.php:52-59` — Exception-Schlucken zur Duplikat-Erkennung (`catch { $isinserted = true; }`),
  rohes `IFNULL(AVG…)` (MySQL-spezifisch).
- `category.php:57` / `categories.php` — `get_records_select('booking', 'categoryid LIKE "%…%"')`:
  ungeschützter LIKE-String-Bau (SQL-Injection-/Korrektheitsrisiko, da `categoryid` numerisch
  als LIKE behandelt wird).
- `teachers.php:59-100` — Direkt-SQL für Teacher-Aggregation im Controller.
- `subbooking_timetabletest.php:1` — reine Test-/Demo-Seite mit hartcodiertem JSON; wirkt wie
  **toter Code** im Produktiv-Root, nur `require_login()`-geschützt.
- `scheduledmails.php:34` — nur unter `DEBUG_DEVELOPER` sinnvoll, sonst Warnhinweis; Duplikat zu
  `edit_rules.php`-Tab-Logik.
- `moveoption.php:66` — englischer Klartext-String statt `get_string` in Fehlerausgabe.
- `otherbookingaddrule_form.php:87` — falscher Sprachstring `savenewtagtemplate` (Copy-Paste aus
  Tag-Form); `validation()`/`get_data()` sind No-op-Overrides.

## Methoden-Inventar (nicht-triviale Klassen)

### `mod_booking_mod_form` (mod_form.php:50) — extends `moodleform_mod`
Instanz-Einstellungsformular einer Booking-Aktivität.
- `public show_sub_categories($catid, $dashes='', $options=[])` — rekursiver Aufbau der Kategorie-Auswahl.
- `public add_completion_rules()` — Completion-Bedingungen (enablecompletion) hinzufügen.
- `public completion_rule_enabled($data)` — prüft, ob eine Completion-Regel aktiv ist.
- `public definition()` — gesamter Formularaufbau (sehr lang, ab Zeile 141).
- `public data_preprocessing(&$defaultvalues)` (1511) — Defaults aufbereiten vor Anzeige.
- `public validation($data, $files)` (1703) — Formularvalidierung.
- `public data_postprocessing($data)` (1746) — Nachbearbeitung nach Submit.
- `public get_data()` (1772) — Daten holen/anreichern.
- (+ Standard-`moodleform_mod`-Overrides)

### `lib.php` (Moodle-Callback-Funktionen, Auswahl)
- `booking_add_instance($booking)` (741) — neue Instanz anlegen.
- `booking_update_instance($booking)` (999) — Instanz aktualisieren.
- `booking_delete_instance($id)` (2407) — Instanz löschen.
- `booking_supports($feature)` (596) — Feature-Flags.
- `booking_pluginfile(...)` (476) — Datei-Serving (Bilder/Attachments).
- `booking_extend_settings_navigation(...)` (1372) — Navigationsknoten (inkl. PRO-Gating-Klassen).
- `booking_check_if_teacher($optionoroptionid=null, $userid=0)` (1798) — Lehrer-Check (breit genutzt von Entry-Scripts).
- `booking_generatenewnumbers(...)` (1906), `booking_activitycompletion[_teachers](...)` (1853/1984) — Completion-/Nummern-Helfer.
- Grading/Rating/Comment-Callbacks (`booking_get_user_grades` 2034, `booking_rate` 2316, `booking_rating_validate` 2232, `booking_comment_validate` 687 …).
- Utility: `is_json` (2763), `db_is_at_least_mariadb_106_or_mysql_8` (2888), `mod_booking_cm_info_view` (2733).

### `locallib.php` (Legacy-Helfer)
- `booking_confirm_booking($optionid,$user,$cm,$url)` (47) — Bestätigungsseite rendern.
- `booking_updatestartenddate($optionid)` (75) — Start/Enddatum aus Optiondates ableiten + Rules triggern.
- `get_rendered_customfields($optiondateid)` (111) — Custom-Fields einer Session als HTML.
- `get_rendered_eventdescription(...)` (132) — Beschreibung je Kontext (ICAL/Mail/Calendar/Web).
- `optiondate_duplicatecustomfields($old,$new)` (172) — Custom-Fields duplizieren.
- `booking_getoptionstatus($starttime=0,$endtime=0)` (190) — Statuslabel (aktiv/beendet/nicht gestartet).

### `mod_booking_sendmessage_form` (sendmessageform.class.php:36), `importexcel_form` (importexcel_form.php:36), `mod_booking_categories_form` (categoriesform.class.php:36), `mod_booking_teachers_form` (teachers_form.php:40), `tagtemplatesadd_form` (tagtemplatesadd_form.php:36), `otherbookingaddrule_form` (otherbookingaddrule_form.php:36)
Klassische `moodleform`-Klassen, jeweils mit `definition()` (Feldaufbau) und teils `validation()`
(oft No-op) bzw. `get_data()` (Editor-Feld-Flattening). `categories_form` zusätzlich
`show_sub_categories()` (rekursive Select-Optionen). `teachers_form` baut Lehrer-Checkbox-Liste mit
Completion-Buttons.

### `sendmessage.php::send_custom_message(...)` (sendmessage.php:101)
Globale Funktion im Entry-Script: erzeugt pro User einen `message_controller`, triggert
`custom_message_sent`, erkennt Bulk-Versand (≥3 User & ≥75%) und triggert dann
`custom_bulk_message_sent`.
