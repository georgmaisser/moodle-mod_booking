# book_all_students_task — Methoden-Doku
**Datei:** `classes/task/book_all_students_task.php` · **LOC:** 67 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`book_all_students_task` ist ein duenner Adhoc-Task-Adapter (`extends \core\task\adhoc_task`), der die fachliche Massen-Buchung an `mod_booking\local\book_all_students::execute()` delegiert. Er enthaelt keine eigene Domaenenlogik, sondern liest `optionid` aus den `custom_data`, setzt einen System-`$PAGE`-Kontext (weil manche Booking-Condition-Checks `$PAGE->url` auch im Task-Kontext lesen) und protokolliert das Ergebnis-DTO via `mtrace`. Persistenz: keine eigene; indirekt ueber `book_all_students`. Kollaborateure: `book_all_students`, `$PAGE`, `context_system`, `moodle_url`.

## Methoden

### `public function get_name(): string` — public
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Tasks (`bookallstudents`). **Seiteneffekte:** `get_string()`. **Rueckgabe:** string. **Bewertung:** A.

### `public function execute(): void` — public
- **Zweck:** Liest `optionid` aus `get_custom_data()`, setzt System-Kontext + Dummy-URL auf `$PAGE`, ruft `book_all_students::execute((int)$optionid)` und tracet das Ergebnis (booked/waitinglist/skipped/failed/stoppedforcapacity). **Seiteneffekte:** `$PAGE->set_context(context_system::instance())`, `$PAGE->set_url(...)`, delegierte DB-Schreibvorgaenge in `book_all_students`, `mtrace`. Wirft `\coding_exception` bei fehlendem `optionid`. **Rueckgabe:** void. **Bewertung:** A — sauberer Adapter; defensiver int-Cast und expliziter Guard. Der globale `$PAGE`-Kontext-Set ist ein bewusster Workaround fuer Condition-Checks und korrekt kommentiert.

## Bewertungs-Resümee
Minimaler, gut dokumentierter Adhoc-Adapter ohne eigene Geschaeftslogik; Guard, int-Cast und Ergebnis-Tracing sind vorhanden. Keine funktionalen Schwaechen. Klassen-Score **A / P3**.
