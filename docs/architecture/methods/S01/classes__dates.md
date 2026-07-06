# dates — Methoden-Doku
**Datei:** `classes/dates.php` · **LOC:** 1038 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`mod_booking\dates` ist die Form-/Persistenz-Schicht fuer Optiondates (Kurstermine einer Buchungsoption). Sie baut zur Laufzeit die wiederholbaren Termin-Bloecke ins `MoodleQuickForm` (`definition_after_data`/`set_data`), liest abgeschickte Termine aus den Formularwerten (`get_list_of_submitted_dates`) und persistiert/synchronisiert sie inkl. Change-Tracking (`save_optiondates_from_form`). Kollaborateure: `singleton_service`, `option\optiondate`, `option\dates_handler`, `option\time_handler`, `customfield\optiondate_cfields`, `local_entities\entitiesrelation_handler`, `semester`, `booking`. Die Klasse mischt Form-Aufbau, Importer-Sonderlogik, Entity-/Customfield-Verdrahtung und DB-Schreibzugriffe in wenigen sehr grossen statischen Methoden — hohe Kopplung, schwer testbar.

## Methoden

### `__construct()` — public
- **Zweck:** Leerer Konstruktor.
- **Bewertung:** A. Triviale/leere Methode (Klasse wird de facto rein statisch genutzt).

### `definition_after_data(MoodleQuickForm &$mform, array $formdata): void` — public static
- **Zweck:** Baut nach dem Befuellen mit Defaults die kompletten Termin-Formularelemente auf: Semester-/Wochentag-Serie, datescounter (hidden), Slotbooking-Warnung, Loeschen-NoSubmit-Buttons und je Termin einen Collapsible-Block; verschiebt sie anschliessend an die richtige Stelle.
- **Parameter:** `$mform` (per Referenz, wird mutiert), `$formdata` (bookingid/id/optionid/slot_type ...). **Rueckgabe:** void.
- **Seiteneffekte:** Liest `$mform->_defaultValues` (interne mform-Property). Liest Settings via `singleton_service::get_instance_of_booking_settings_by_bookingid` und `get_instance_of_booking_option_settings`. Registriert NoSubmit-Buttons, addElement/hideIf am Form. Kein DB direkt, aber Settings-Lookups treffen Cache/DB. Delegiert an `get_list_of_submitted_dates`, `add_dates_to_form`, `add_no_dates_yet_to_form`, `move_form_elements_to_the_right_place`, `semester::get_semesters_id_name_array`.
- **Aufrufkette:** Wird vom Option-Form (`definition_after_data` der mform-Subklasse / fields_info) gerufen. Ruft die genannten Helfer.
- **Bewertung:** D. ~157 LOC (classes/dates.php:71-228), gemischte Verantwortung (Semester-Serie + Slotbooking + Delete-Handling + Default-Befuellung), tiefe Verschachtelung/Bedingungslogik, Zugriff auf private mform-Interna (`_defaultValues`). Smell: classes/dates.php:71 (Methodenlaenge, mixed concern), :89-98 (komplexe optiontype/slot_type-Ableitung dupliziert mit set_data).

### `set_data(stdClass &$defaultvalues): stdClass` — public static
- **Zweck:** Bereitet die Default-Werte des Formulars auf: legt Single-Date aus diversen Feldnamen an, behandelt "Terminserie erstellen", Webservice-Import-Merge (`mergeparam`), Add-/Delete-Date-NoSubmit, und projiziert Sessions+Entities+Customfields auf indizierte Formularfelder.
- **Parameter:** `$defaultvalues` (per Referenz mutiert). **Rueckgabe:** dasselbe (mutierte) `$defaultvalues`.
- **Seiteneffekte:** **DB-WRITE:** `$DB->delete_records_select('event', ...)` (loescht Kalender-`event`-Eintraege per `uuid LIKE optionid-%`, classes/dates.php:318). Liest Option-Settings via `singleton_service`. Erzeugt `entitiesrelation_handler`, ruft `get_entityid_by_instanceid`. Ruft `dates_handler::get_optiondate_series`, `optiondate_cfields::set_data`. Mutiert zahlreiche dynamische Properties (`MOD_BOOKING_FORM_*`, `LOCAL_ENTITIES_FORM_*`).
- **Aufrufkette:** Vom Option-Form `set_data`/`data_preprocessing`-Pfad. Ruft `parse_date_with_format`, `dates_handler`, `entitiesrelation_handler`, `optiondate_cfields`.
- **Bewertung:** E. ~245 LOC (classes/dates.php:235-480), groesste Methode der Datei. Mehrere voneinander unabhaengige Verantwortungen (Single-Date, Serie, Import-Merge, Add/Delete, Session-Projektion) in einer Methode; direkter DB-Delete auf core `event`-Tabelle in einer "set_data"-Form-Methode (Seiteneffekt an unerwarteter Stelle, classes/dates.php:318); inline-Closure (:286) fuer Session-Matching; `class_exists`-Guards mehrfach dupliziert. Hohe zyklomatische Komplexitaet, kaum isoliert testbar.

### `data_preprocessing($defaultvalues): void` — public static
- **Zweck:** Leerer Platzhalter (No-op).
- **Bewertung:** B. Leer; vermutlich Interface-/Konventions-Hook. Toter Code-Geruch, aber harmlos.

### `get_list_of_submitted_dates(array $formvalues): array` — public static
- **Zweck:** Liest aus den abgeschickten Formularwerten alle Termine (bis ~100) inkl. Start/Ende (auch als date-array), Entities und Customfields; nach `coursestarttime` sortiert. Liefert `[$dates, $highestindex]`.
- **Parameter:** `$formvalues` (Form-Array). **Rueckgabe:** `[array $dates, int $highestindex]`.
- **Seiteneffekte:** Keine DB-Writes. `class_exists`-Guard fuer Entities. Ruft `optiondate_cfields::get_list_of_submitted_cfields`, `make_timestamp` (Core). Inline-`usort`-Closure (:589).
- **Aufrufkette:** Gerufen von `definition_after_data` und `save_optiondates_from_form`. 
- **Bewertung:** C. ~93 LOC (classes/dates.php:499-592), PHP<8-Branch fuer Splat (:535) ist toter Ballast in aktueller Codebasis; verschachtelte if/else; baut grosses Array-Mapping mit gemischter Form-Parsing-Logik. Vertretbar, aber lang.

### `save_optiondates_from_form(stdClass $formdata, stdClass &$option): array` — public static
- **Zweck:** Synchronisiert die abgeschickten Termine mit den gespeicherten: berechnet save/update/delete-Mengen via `optiondate::compare_optiondates`, loescht/erzeugt Optiondates und liefert ein Change-Tracking-Array (`oldvalue`/`newvalue`).
- **Parameter:** `$formdata`, `$option` (per Referenz). **Rueckgabe:** Array der Changes (oder `[]`).
- **Seiteneffekte:** **DB-WRITE:** `optiondate::delete($optiondateid)` und `optiondate::save(...)` (Optiondate-Persistenz inkl. Folge-Events/Kalender). Liest Settings via `singleton_service`. `entitiesrelation_handler::get_instance_data`. Wirft `moodle_exception('savingoptiondatewentwrong')` bei Inkonsistenz. Respektiert `MOD_BOOKING_CLASSES_EXCLUDED_FROM_CHANGES_TRACKING` und `selflearningcourse`.
- **Aufrufkette:** Vom Option-Save-Pfad (booking_option update). Ruft `get_list_of_submitted_dates`, `optiondate::save/delete/compare_optiondates`, `optiondate_cfields::return_customfields_for_optiondate`.
- **Bewertung:** D. ~100 LOC (classes/dates.php:601-701), gemischte Verantwortung (Diff-Berechnung + Persistenz + Change-Tracking). Subtiler Geruch: `unset($olddates[$oldoptiondate->id])` (:629) entfernt aus einem Array, das spaeter als `$datestodelete` weiterverwendet wird — Kommentar im Code raeumt selbst Verwirrung ein ("unset here but we still need it"); `$memory`-Logik (:675-679) schwer nachvollziehbar.

### `add_date_as_collapsible(MoodleQuickForm &$mform, array &$elements, array $date, bool $expanded = false, array $formdata = []): void` — private static
- **Zweck:** Baut einen einzelnen ausklappbaren Termin-Block: Header-HTML aus Template, Start/Ende-`date_time_selector`, daystonotify (Legacy-Text vs. Rule-Override-Select vs. Hinweis), Entities-Form, Customfields, Apply/Delete-Buttongruppe, Schliess-HTML.
- **Parameter:** `$mform`, `$elements` (Ref, wird befuellt), `$date`, `$expanded`, `$formdata`. **Rueckgabe:** void.
- **Seiteneffekte:** `$OUTPUT->render_from_template` (mod_booking/option/option_collapsible_*). `get_config('booking','uselegacymailtemplates')`. Ruft `session_reminder_rule_exists` (DB-Read), `dates_handler::prettify_optiondates_start_end`, `time_handler::set_timeintervall`, `timestamp_to_array`, `booking::get_array_of_days_before_and_after`, `entitiesrelation_handler::instance_form_definition`, `optiondate_cfields::instance_form_definition`. Registriert NoSubmit-Buttons.
- **Aufrufkette:** Gerufen von `add_dates_to_form`. 
- **Bewertung:** D. ~138 LOC (classes/dates.php:714-852), lange Methode mit mehreren UI-Verantwortungen; drei-Wege-Branch fuer daystonotify; mischt Template-Rendering, Form-Elemente, Entities und Customfields. `$datearray` wird ohne vorherige Initialisierung als Array genutzt (:843, implizit), funktioniert aber.

### `move_form_elements_to_the_right_place(MoodleQuickForm &$mform, array $elements): void` — private static
- **Zweck:** Verschiebt die nachtraeglich (in definition_after_data) erzeugten Elemente vor den `datesmarker`-Anker im Form, Wert erhaltend.
- **Seiteneffekte:** mform removeElement/insertElementBefore/setValue.
- **Aufrufkette:** Gerufen von `definition_after_data`.
- **Bewertung:** B. Klein (classes/dates.php:860-872), eine klare Aufgabe; greift auf mform-Element-API zu.

### `add_dates_to_form(MoodleQuickForm &$mform, array &$elements, array $dates, array $formdata): void` — private static
- **Zweck:** Fuegt fuer jede Date je ein hidden optiondateid-Feld und den Collapsible-Block hinzu; oeffnet bei frischem "add" den letzten Block; haengt den "Add date"-Button an.
- **Seiteneffekte:** addElement/registerNoSubmitButton/hideIf; liest `$mform->_defaultValues`. Ruft `add_date_as_collapsible`.
- **Aufrufkette:** Gerufen von `definition_after_data`.
- **Bewertung:** B. ~35 LOC (classes/dates.php:883-918), fokussiert; leichter Geruch durch `_defaultValues`-Zugriff.

### `add_no_dates_yet_to_form(MoodleQuickForm &$mform, array &$elements, array $dates, array $formdata, bool $allowoptiondates = true): void` — private static
- **Zweck:** Fallback-Aufbau wenn keine Termine vorhanden: "Datum nicht gesetzt"-Hinweis, Re-Registrierung der Delete-NoSubmit-Buttons, "Add date"-Button (jeweils nur wenn Optiondates erlaubt).
- **Seiteneffekte:** addElement/registerNoSubmitButton/hideIf; liest `_defaultValues`.
- **Aufrufkette:** Gerufen von `definition_after_data`.
- **Bewertung:** B. ~35 LOC (classes/dates.php:932-967), fokussiert.

### `timestamp_to_array(int $timestamp): array` — public static
- **Zweck:** Wandelt einen Unix-Timestamp in das verschachtelte Array-Format fuer `date_time_selector` (Tag/Monat/Jahr/Stunde/Minute), respektiert User-Timezone.
- **Seiteneffekte:** `core_date::get_user_timezone`. Keine DB.
- **Aufrufkette:** Von `add_date_as_collapsible`.
- **Bewertung:** A. Klein, rein, klar (classes/dates.php:975-986).

### `session_reminder_rule_exists(int $cmid): bool` — private static
- **Zweck:** Prueft, ob global (System-Context) oder fuer die Instanz eine Session-Reminder-Booking-Rule (`rule_daysbefore`/`rule_specifictime` mit `optiondatestarttime` im rulejson) existiert.
- **Seiteneffekte:** **DB-READ:** `$DB->get_records_sql` ueber `booking_rules` JOIN `context`.
- **Aufrufkette:** Von `add_date_as_collapsible`.
- **Bewertung:** C. Klein, aber handgebautes Raw-SQL mit String-Interpolation `{$DB->sql_like(...)}` (classes/dates.php:996-1006); Parameter sind gebunden, daher kein direktes Injection-Risiko, aber SQL-Bau in einer Form-Hilfsklasse ist ein Schichtbruch (gehoerte in ein Rules-Repository). Liefert nur bool, ignoriert das volle Result.

### `parse_date_with_format($datestring, $dateparseformat): int` — private static
- **Zweck:** Parst einen Datums-String via optionalem CSV-Importformat (`DateTime::createFromFormat`), Fallback `strtotime`, letzter Fallback `time()`.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Von `set_data` (Single-Date-Aufbau).
- **Bewertung:** B. Klein, defensiv (classes/dates.php:1026-1037); untypisierte Parameter.

### Inline-Closures (gebuendelt)
- `set_data` :286 — Session-Matching-Closure (`array_map`) fuer Serie-Recreation; `get_list_of_submitted_dates` :589 — `usort`-Vergleich nach coursestarttime. Beide klein, mit den jeweiligen Methoden bewertet.
