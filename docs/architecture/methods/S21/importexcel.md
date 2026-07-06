# importexcel — Methoden-Doku
**Datei:** `importexcel.php` · **LOC:** 147 · **Subsystem:** S21 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozedurales Entry-Script (keine Klasse). Es importiert eine CSV-Datei, um die **Activity-Completion** von Usern fuer Buchungsoptionen zu setzen. Der gesamte Flow — Permission-Check, Upload, CSV-Parsing, DB-Update und Completion-State-Berechnung — laeuft im Controller-Top-Level. Kollaborateure: `singleton_service` (Booking-Instanz per cmid), `completion_info` (Core-Completion), `importexcel_form` (Upload-Form), `$DB` (Tabelle `booking_answers`). Ziel-Redirect: `view.php`.

## Request-/Permission-Flow
1. `required_param('id', PARAM_INT)` = Course-Module-ID; `$PAGE->set_url(...)`.
2. `get_course_and_cm_from_cmid($id)` -> `require_course_login($course, false, $cm)`.
3. `$PAGE->activityheader->disable()`; `groups_get_activity_groupmode($cm)` (Ergebnis ungenutzt).
4. Booking-Instanz via `singleton_service::get_instance_of_booking_by_cmid` (sonst `invalid_parameter_exception`); `context_module::instance` (sonst `moodle_exception('badcontext')`).
5. `require_capability('mod/booking:updatebooking', $context)` — Gate.
6. Seiten-Setup (Title/Heading/Navbar/Layout) und Instanziierung `importexcel_form`.

## Verarbeitungslogik (`$mform->get_data()`-Zweig)
- **Zweck:** Liest den Datei-Inhalt via `$mform->get_file_content('excelfile')`, splittet per `explode(PHP_EOL, ...)` in Zeilen und parst jede Zeile mit `str_getcsv`.
- **Header-Erkennung:** Iteriert die erste Zeile und ordnet die Spaltenpositionen `OptionID`/`UserID`/`CourseCompleted` zu (`trim($value)`-Vergleich). Fehlt eine Spalte (`pos == -1`), Redirect mit `wrongfile`.
- **Zeilen-Update:** Pro Datenzeile (`count >= 3`) `$DB->get_record('booking_answers', ['bookingid' => $cm->instance, 'userid' => ..., 'optionid' => ...])`; bei Treffer wird `completed` aus der CSV-Spalte gesetzt, `timemodified = time()`, `$DB->update_record(..., false)`.
- **Completion-Berechnung:** `count_records` der completed-Answers des Users im Booking; je nach Schwelle `$booking->settings->enablecompletion` wird `$completion->update_state($cm, COMPLETION_INCOMPLETE|COMPLETE, $userid)` gerufen.
- **Abschluss:** Redirect mit `importfinished` (5s). Else-Zweig (kein Submit): `$OUTPUT->header()` + Heading + `$mform->display()`.
- **Seiteneffekte:** schreibt `booking_answers` (`completed`, `timemodified`), aendert Core-Completion-State, sendet Redirects/HTTP-Output.
- **Bewertung:** C — funktionsfaehig, aber mehrere Schwachstellen (siehe Resümee): ungetrimmte/uncastete CSV-Werte, `PHP_EOL`-Split, N-Queries pro Zeile.

## Bewertungs-Resümee
Solider Grundablauf mit korrektem Capability-Gate und instanz-gebundener Answer-Selektion (`bookingid => $cm->instance` verhindert Cross-Instance-Treffer). Schwaechen:
- **Zeilenenden:** `explode(PHP_EOL, $csvfile)` nutzt das Server-EOL (`\n`); eine auf Windows erstellte CSV (`\r\n`) hinterlaesst ein `\r` an jedem Feldende. Der Header wird per `trim()` toleriert, die **Datenwerte** (`completed`) jedoch nicht — `completed` kann so als `"1\r"` gespeichert werden (Z.72/114, P3).
- **Ungeprueftes `completed`:** `$user->completed = $line[$completedpos]` uebernimmt den Roh-CSV-Wert ohne Cast/Whitelist; beliebiger String landet im int-Feld (Z.114, P3, Daten-Integritaet).
- **Per-Zeilen-Queries:** je Datenzeile ein `get_record` + ggf. `update_record` + `count_records` -> bei grossen Imports N+1-/Massenschreib-Last ohne Transaktion (Z.106–132, P2).
- `groups_get_activity_groupmode` (Z.45) ist totes Setup (Ergebnis ungenutzt).
Klassen-Score **C / P2**.
