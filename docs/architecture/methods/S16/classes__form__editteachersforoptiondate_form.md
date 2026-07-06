# editteachersforoptiondate_form — Methoden-Doku
**Datei:** `classes/form/editteachersforoptiondate_form.php` · **LOC:** 438 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_*.md)

## Klassenueberblick
`editteachersforoptiondate_form` ist ein Moodle `\core_form\dynamic_form` (Modal/AJAX), mit dem pro Optiondate (Termin) die anwesenden bzw. vertretenden Lehrenden gepflegt werden, inkl. Begruendung (`reason`) und optionaler Honorar-Abzuege (`deductions`). Kollaborateure: `singleton_service` (Option-Settings/User), `cache_helper` (Journal-Cache), die Events `optiondates_teacher_added`/`optiondates_teacher_deleted` (fuer Booking-Rules) sowie direkte `$DB`-Zugriffe auf `booking_optiondates_teachers`, `booking_optiondates`, `booking_odt_deductions`, `booking_teachers`, `user`.

## Methoden

### `get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Modul-Kontext aus dem AJAX-Formdata-`cmid`.
- **Parameter/Rueckgabe:** keine / `context_module`.
- **Seiteneffekte:** liest `$this->_ajaxformdata['cmid']`.
- **Aufrufkette:** vom dynamic_form-Framework + intern von `check_access_for_dynamic_submission`.
- **Bewertung:** A.

### `check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Capability-Gate; wirft `moodle_exception`, wenn keine der 4 Editier-/View-Capabilities vorhanden ist.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** `has_capability`-Checks; throw.
- **Aufrufkette:** Framework vor Submission.
- **Bewertung:** B — die `(... ) == false`-Konstruktion ist umstaendlich (idiomatischer waere `!(...)`), aber funktional korrekt.

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt Formular-Defaults vor Anzeige: cmid/optionid/optiondateid, `reason` und je Lehrenden den bestehenden Deduction-Status/Reason.
- **Parameter/Rueckgabe:** keine / void (ruft `$this->set_data`).
- **Seiteneffekte:** liest `booking_optiondates.reason`, `booking_odt_deductions`; `singleton_service::get_instance_of_booking_option_settings`; dynamische Property-Namen `deduction-teacherid-<id>` / `deductionreason-teacherid-<id>`.
- **Aufrufkette:** Framework beim Form-Render.
- **Bewertung:** B — N+1 DB-Reads in der teacher-Schleife (`get_record` pro Lehrenden), bei wenigen Lehrenden tolerabel; sonst sauber.

### `process_dynamic_submission()` — public
- **Zweck:** Persistiert die Aenderungen nach Submit: Diff bestehender vs. gewaehlter Lehrender (delete/insert), `reason`-Update am Optiondate sowie Anlegen/Update/Delete der Deductions je Lehrenden.
- **Parameter/Rueckgabe:** keine / gibt `$data` zurueck.
- **Seiteneffekte:** sehr viele — `$DB->get_records/insert_record/delete_records/update_record` auf `booking_optiondates_teachers`, `booking_optiondates`, `booking_odt_deductions`; triggert Events `optiondates_teacher_deleted` / `optiondates_teacher_added` (context_system, mit cmid/optiondateid im `other`); mehrfach `cache_helper::purge_by_event('setbackcachedteachersjournal')`; nutzt `$USER`.
- **Aufrufkette:** Framework nach erfolgreicher Validation.
- **Bewertung:** D — 130 LOC, gemischte Verantwortung (3 unabhaengige Persistenz-Domaenen: teachers, reason, deductions) in einer Methode; tiefe Schachtelung (bis 4 Ebenen, z.B. `editteachersforoptiondate_form.php:195-220`); Cache-Purge mehrfach redundant im Loop (`:207`, `:219`, `:235`) statt einmal am Ende; dynamische String-Property-Keys erschweren Testbarkeit. Duplizierter Kommentar `:159-160`.

### `definition()` — public
- **Zweck:** Baut das mform: hidden Felder (cmid/optionid/optiondateid/teachers), Autocomplete `teachersforoptiondate` (mit AJAX-Selector + valuehtmlcallback), `reason`-Textfeld und — bei Cap `canreviewsubstitutions` — den Deduction-Header mit pro-Lehrenden Checkbox+Reason.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** roher SQL-Bau `SELECT ... FROM {user} WHERE id $insql` (`:262`), `$DB->get_records_sql`, `$DB->get_record` je Lehrenden im Deduction-Block (`:337` N+1), `$OUTPUT->render_from_template('mod_booking/form-user-selector-suggestion')`, `singleton_service`.
- **Aufrufkette:** Framework beim Form-Aufbau.
- **Bewertung:** D — 125 LOC, gemischte Verantwortung (Datenbeschaffung + UI-Aufbau + handgebautes SQL + Template-Rendering); N+1 in der `deductableteachers`-Pruefung (`:337`); inline Closure als 6. "Methode".

### `valuehtmlcallback` (anonyme Closure in `$options`, `:297`) — Closure
- **Zweck:** Rendert die Anzeige eines bereits gewaehlten Lehrenden im Autocomplete via Template; Fallback `choose...` bei leerem Wert.
- **Parameter/Rueckgabe:** `$value` / HTML-String.
- **Seiteneffekte:** `singleton_service::get_instance_of_user`, `$OUTPUT->render_from_template`; nutzt `global $OUTPUT`.
- **Aufrufkette:** vom autocomplete-Element zur Render-Zeit.
- **Bewertung:** B — kompakt; Code-Duplikat zum Detail-Mapping in `definition()` (`:266` vs. `:303`).

### `validation($data, $files): array` — public
- **Zweck:** Validiert: `reason`-Laenge ≤250; Pflicht-`reason` wenn keine Lehrenden oder wenn Termin-Lehrende von Option-Lehrenden abweichen (Vertretung); Deduction-Reason Pflicht wenn Checkbox aktiv.
- **Parameter/Rueckgabe:** `$data`, `$files` / `$errors`-Array.
- **Seiteneffekte:** liest `booking_teachers` (get_fieldset_select), `singleton_service`-Settings.
- **Aufrufkette:** Framework nach Submit, vor `process_dynamic_submission`.
- **Bewertung:** B — klar strukturiert; der sort+Vergleich der teacher-Listen ist etwas implizit, aber ok.

### `get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Report-URL (`optiondates_teachers_report.php`) mit cmid/optionid.
- **Parameter/Rueckgabe:** keine / `moodle_url`.
- **Seiteneffekte:** liest AJAX-Formdata / optional_param.
- **Aufrufkette:** Framework.
- **Bewertung:** A.

## Notes
- Kein triviale-Akzessoren-Block noetig (keine reinen Getter/Setter).
- Hauptlasten sind `process_dynamic_submission` (D) und `definition` (D): beide ueberlang, mit gemischten Verantwortlichkeiten, N+1-Reads und im Falle von `process` redundanten Cache-Purges. Kein funktionaler Bug, aber refactoring-relevant (Extraktion der 3 Persistenz-Domaenen bzw. der User-Datenbeschaffung).
