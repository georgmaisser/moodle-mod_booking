# teachers_instance_report_table — Methoden-Doku
**Datei:** `classes/table/teachers_instance_report_table.php` · **LOC:** 477 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
Report-Tabelle (`extends table_sql`), die je Trainer:in einer Buchungsinstanz eine Uebersicht ueber gehaltene Kurse, summierte Einheiten, fehlende Stunden und Vertretungen rendert. Pro Tabellenspalte gibt es eine `col_*`-Methode, die in der Render-Schleife von `table_sql` aufgerufen wird. Kollaborateure: `dates_handler` (Datums-/Tagesformat), `moodle_url`, direktes `$DB` (mehrere Roh-SQLs), `get_config('booking', ...)`. Mischt stark Datenbeschaffung (SQL), Geschaeftslogik (Einheiten-Berechnung) und HTML-Praesentation in den Spaltenmethoden.

## Methoden

### `__construct(string $uniqueid, int $bookingid = 0, int $cmid = 0)` — public
- **Zweck:** Initialisiert die Tabelle, setzt baseurl/IDs, baut das wiederverwendete `missinghourssql`-Statement, ermittelt Einheitenlaenge und Sprach-spezifische Zahlentrennzeichen.
- **Parameter:** `$uniqueid` (eindeutige Tabellen-ID), `$bookingid`, `$cmid`. **Rueckgabe:** keine.
- **Seiteneffekte:** liest `$PAGE->url`; `get_config('booking', 'educationalunitinminutes')`; baut SQL-String mit `$DB->sql_concat`/`$DB->sql_group_concat` ueber `booking_optiondates`, `booking_options`, `booking_teachers`, `booking_optiondates_teachers` (kein Read hier, nur String-Bau).
- **Aufrufkette:** von Report-Seite (teachers_instance_report.php) instanziiert; ruft `parent::__construct`.
- **Bewertung:** C — vermischt Konstruktion mit SQL-Bau (`:79-96`); langer eingebetteter Roh-SQL-String, der spaeter in zwei Methoden geteilt genutzt wird. ~50 LOC.

### `col_lastname($values)` — public
- **Zweck:** Rendert Trainer:in-Namen mit Badge + Link auf `teacher_performed_units_report.php`; im Download nur Klartext-Nachname.
- **Parameter:** `$values` (Record-Objekt). **Rueckgabe:** `string` (HTML oder Klartext).
- **Seiteneffekte:** keine DB; baut inline HTML.
- **Aufrufkette:** von `table_sql`-Render-Schleife.
- **Bewertung:** C — inline HTML-String-Konkatenation mit hartkodiertem Pfad (`:131`), gemischte Verantwortung (Praesentation), aber kurz.

### `col_firstname($values)` — public
- **Zweck:** Gibt den Vornamen unveraendert zurueck.
- **Bewertung:** A — trivial.

### `set_units_courses_records(&$values)` — private
- **Zweck:** Performance-Helfer: laedt einmalig die Kurs-/Options-Records des Trainers nach `$values->unitsrecords` (Memoization per Referenz), damit `col_units_courses` und `col_sum_units` nicht doppelt querien.
- **Parameter:** `&$values` (Referenz, wird mutiert). **Rueckgabe:** keine.
- **Seiteneffekte:** `$DB->get_records_sql` Read auf `booking_teachers` JOIN `booking_options` (Filter teacherid/bookingid); setzt `$values->unitsrecords`.
- **Aufrufkette:** gerufen von `col_units_courses` und `col_sum_units`.
- **Bewertung:** B — sinnvolle Memoization, aber Roh-SQL und Mutation per Referenz; vertretbar.

### `col_units_courses($values)` — public
- **Zweck:** Rendert pro Kurs des Trainers Titel + Wochentag/Zeit + berechnete Einheiten als collapsible HTML-Block (bzw. Klartext im Download).
- **Parameter:** `$values`. **Rueckgabe:** `string`.
- **Seiteneffekte:** ueber `set_units_courses_records` indirekter DB-Read; `dates_handler::prepare_day_info`; `moodle_url`; `get_string`.
- **Aufrufkette:** Render-Schleife; ruft `set_units_courses_records`.
- **Bewertung:** D — ~65 LOC, tiefe Schachtelung (4 Ebenen if/foreach), vermischt Einheiten-Berechnung (`:203-209`) mit HTML-Bau, vielfache `is_downloading()`-Verzweigungen (Duplikat-Muster). Einheiten-Berechnung dupliziert sich in `col_sum_units`.

### `col_sum_units($values)` — public
- **Zweck:** Summiert die Einheiten ueber alle Kurse des Trainers und formatiert sie.
- **Parameter:** `$values`. **Rueckgabe:** `string` (formatierte Zahl, ggf. + Einheit-Label).
- **Seiteneffekte:** ueber `set_units_courses_records` indirekter DB-Read; `dates_handler::prepare_day_info`.
- **Aufrufkette:** Render-Schleife; ruft `set_units_courses_records`.
- **Bewertung:** C — Minuten/Einheiten-Berechnung (`:278-280`) dupliziert die Logik aus `col_units_courses`; sollte gemeinsamen Helfer nutzen.

### `col_missinghours($values)` — public
- **Zweck:** Listet Optionstermine, an denen der Trainer als zustaendig gefuehrt wird, aber nicht im optiondates_teachers eingetragen war (= fehlende Stunden), inkl. ggf. Abzugs-Eintrag.
- **Parameter:** `$values`. **Rueckgabe:** `string` (collapsible HTML / Klartext).
- **Seiteneffekte:** `$DB->get_records_sql($this->missinghourssql)` Read; pro Record zusaetzlicher `$DB->get_record('booking_odt_deductions', ...)` (N+1); `dates_handler::prettify_optiondates_start_end`; `moodle_url`.
- **Aufrufkette:** Render-Schleife.
- **Bewertung:** D — ~76 LOC, starke if/else-Duplizierung (HTML- vs. Download-Zweig fast identisch, `:330-367`), N+1-Query in der Schleife (`:339`), gemischte SQL+Berechnung+Praesentation.

### `col_substitutions($values)` — public
- **Zweck:** Ermittelt zu den fehlenden Stunden die tatsaechlichen Vertretungs-Trainer (in optiondates_teachers, aber nicht regulaere Teacher der Option) und rendert sie.
- **Parameter:** `$values`. **Rueckgabe:** `string` (collapsible HTML / Klartext).
- **Seiteneffekte:** `$DB->get_records_sql($this->missinghourssql)` Read; pro Record verschachtelter `$DB->get_records_sql` mit Subselect auf `booking_optiondates_teachers`/`booking_optiondates`/`user`/`booking_teachers` (N+1); `dates_handler::prettify_optiondates_start_end`; `moodle_url`.
- **Aufrufkette:** Render-Schleife.
- **Bewertung:** D — ~85 LOC, N+1 mit eingebettetem Roh-SQL in der Schleife (`:408-420`), erneute HTML/Download-Zweig-Duplizierung, hohe Schachtelungstiefe; klare gemischte Verantwortung.

## Triviale Akzessoren
- Properties (`$bookingid`, `$cmid`, `$unitlength`, `$decimalseparator`, `$thousandsseparator`, `$missinghourssql`) werden im Konstruktor gesetzt; keine eigenen Getter/Setter.
