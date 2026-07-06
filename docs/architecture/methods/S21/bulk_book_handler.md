# bulk_book_handler — Methoden-Doku
**Datei:** `bulk_book_handler.php` · **LOC:** 75 · **Subsystem:** S21 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Stellt fuer eine Buchungsoption die Adhoc-Task `book_all_students_task` in die Queue (bucht alle Studierenden des Kurses in die Option), zeigt eine Erfolgsmeldung und leitet zur Optionsansicht zurueck. Kollaborateure: `singleton_service`, `booking_option`, `core\task\manager`, `book_all_students_task` (S13), Core `get_coursemodule_from_id`, `require_login`, `require_capability`.

## Request-/Permissions-Flow
1. `require_once ../../config.php`.
2. Params: `optionid` (PARAM_INT, required), `sesskey` (PARAM_RAW, required).
3. `confirm_sesskey($sesskey)` → sonst `moodle_exception('invalidsesskey')` (**CSRF-Schutz vorhanden**).
4. `singleton_service::get_instance_of_booking_option_settings($optionid)` — leer/ohne id → `invalidobjectid`.
5. `booking_option::create_option_from_optionid($optionid)` — leer → `invalidobjectid`.
6. `get_coursemodule_from_id('booking', $bookingoption->cmid, 0, false, MUST_EXIST)` + `$course = $bookingoption->booking->course`.
7. `require_login($course, false, $cm)`; `context_module::instance($cm->id)`; Page-Context/-URL setzen.
8. `require_capability('mod/booking:bookallstudents', $context)` — **Autorisierung vorhanden**.
9. `book_all_students_task` mit `set_custom_data((object)['optionid' => $optionid])` → `manager::queue_adhoc_task($task)`.
10. `redirect(view.php?id=cmid, bookallstudentsqueued, null, NOTIFY_SUCCESS)`.

## Bewertung der Einzelschritte
- **Validierung (Z.34–46):** sesskey-Check vor jeder Mutation, plus doppelte Existenzpruefung (Settings + Option) mit klaren Exceptions. **Bewertung:** A.
- **Context/Login (Z.49–58):** Login wird gegen den korrekten Kurs+CM erzwungen und die Capability `bookallstudents` gegen den Modul-Context geprueft — saubere Reihenfolge (Object-Resolve → Login → Cap). **Bewertung:** A.
- **Queueing (Z.61–63):** delegiert die teure Massenbuchung korrekt an eine Adhoc-Task statt sie synchron im Request zu fahren (vermeidet Timeout/N+1 im HTTP-Pfad). **Bewertung:** A.

## Bewertungs-Resümee
Vorbildlicher mutierender Entry-Point: sesskey + Existenzpruefungen + Capability-Check, und die eigentliche Arbeit wird asynchron als Adhoc-Task verarbeitet. Keine funktionalen Maengel. Klassen-Score **A / -**.
