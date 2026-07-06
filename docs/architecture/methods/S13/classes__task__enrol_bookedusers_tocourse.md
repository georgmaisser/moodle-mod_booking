# enrol_bookedusers_tocourse — Methoden-Doku
**Datei:** `classes/task/enrol_bookedusers_tocourse.php` · **LOC:** 127 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`enrol_bookedusers_tocourse` ist ein `\core\task\scheduled_task`, der periodisch alle Buchungsoptionen sucht, deren verknuepfter Moodle-Kurs bereits begonnen hat (`coursestarttime < now` oder NULL) und die noch nicht abgearbeitet wurden (`enrolmentstatus < 1`), und die gebuchten User in den jeweiligen Kurs einschreibt. Danach wird `enrolmentstatus` der Option auf `1` gesetzt (ausser bei Electives). Persistenz: liest `booking_options`, schreibt `booking_options.enrolmentstatus`; Enrolment-Nebenwirkungen ueber `booking_option::enrol_user()`. Kollaborateure: `$DB`, `singleton_service`, `elective` (Reihenfolge-Pruefung bei iselective+enforceorder).

## Methoden

### `public function get_name()` — public
- **Zweck:** Sichtbarer Task-Name. **Seiteneffekte:** `get_string('taskenrolbookeduserstocourse', 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Enrolt fuer jede faellige Option alle gebuchten User in den Zielkurs und markiert die Option als abgearbeitet. **Seiteneffekte:** `$DB->get_records_select_menu('booking_options', ...)`; pro Option `get_coursemodule_from_instance`, Option-Singleton, `get_all_users_booked()`, pro User `booking_option::enrol_user()` und ggf. `$DB->set_field_select('booking_options', 'enrolmentstatus', '1', ...)`; viele `mtrace`-Ausgaben. **Bewertung:** C — funktioniert, aber mehrere Maengel:
  - **Stale-`$cm`-Bug (Z.66–76):** Ist `$bookingid` falsy, wird der `else`-Zweig betreten, der nur ein `mtrace` ausgibt — `$cm` bleibt unveraendert. War in einer vorigen Schleifeniteration ein gueltiges `$cm` gesetzt, ist `empty($cm->id)` hier **nicht** leer, der `continue` (Z.75) greift nicht, und die Verarbeitung laeuft mit dem *fremden* `$cm` der Vor-Option weiter (falscher cmid an `get_instance_of_booking_option`). In der Praxis selten, da `bookingid` als FK quasi immer gesetzt ist, aber ein echter Korrektheits-Bug. **(P2)**
  - **Redundante Status-Schreibvorgaenge (Z.120–123):** Das `set_field_select(... 'enrolmentstatus', '1' ...)` steht **innerhalb** der User-Schleife, setzt aber ein Option-weites Feld. Bei N gebuchten Usern werden N identische UPDATEs ausgefuehrt (idempotent, aber verschwenderisch). Gehoert hinter die User-Schleife. **(P3)**
  - **Per-Option-Lookups (N+1):** `get_coursemodule_from_instance` und die Singleton-Aufloesung laufen je Option einzeln; bei vielen faelligen Optionen summiert sich das. Da Scheduled-Batch, tolerierbar. **(P3)**
  - **Kein Enrol-Erfolgs-Check:** Das `Todo` (Z.113) merkt an, dass `enrol_user()` keinen Erfolg zurueckmeldet; ein gescheitertes Enrolment markiert die Option dennoch als erledigt (`enrolmentstatus = 1`), wird also nicht erneut versucht.
  - **`enforceorder` ungenutzt fuer Nicht-Elective:** Variable wird nur im `iselective`-Pfad ausgewertet (Z.102–109); fuer den Standardfall ohne Belang.

## Bewertungs-Resümee
Zweckmaessiger Scheduled-Task fuer verzoegertes Kurs-Enrolment ab Kursstart, mit Elective-Reihenfolge-Beruecksichtigung. Wesentlicher Schwachpunkt ist der Stale-`$cm`-Pfad bei fehlender `bookingid`; dazu redundante Per-User-Status-Updates und das fehlende Enrol-Erfolgs-Feedback. Keine Daten-Loss-Gefahr, aber potentielle Fehl-Enrolments im pathologischen Fall. Klassen-Score **C / P2**.
