# index — Methoden-Doku
**Datei:** `index.php` · **LOC:** 107 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozedurales Entry-Script (keine Klasse). Die Standard-Moodle-Index-Seite eines Moduls: listet **alle Booking-Instanzen eines Kurses**, nach Kurs-Sections gruppiert, und zeigt pro Instanz die eigenen Buchungen des aktuellen Users. Kollaborateure: `get_fast_modinfo` (modinfo/Sections), `singleton_service` (Booking-Instanz per cmid), `booking::get_user_booking($USER)`, `html_table`/`html_writer`.

## Request-/Permission-Flow
1. `required_param('id', PARAM_INT)` = Kurs-ID; `$PAGE->set_url(...)`.
2. `$DB->get_record('course', ..., MUST_EXIST)` -> `require_login($course)`; Layout `incourse`.
3. Title/Heading/Navbar + `$OUTPUT->header()` + Heading **werden bereits ausgegeben**.
4. **Erst danach** `context_course::instance` + `require_capability('mod/booking:choose', $context)`.
5. `get_coursemodules_in_course('booking', $course->id)` — bei leer: `notice(...)` mit Rueck-Link.
6. `course_format_uses_sections` + `get_fast_modinfo`; bei Sections `get_section_info_all()`.

## Render-Schleife (`foreach $modinfo->instances['booking']`)
- **Zweck:** Baut eine `html_table` mit Section-Trennzeilen und je Instanz einem Link plus der `<ul>`-Liste der eigenen Buchungen.
- Ueberspringt nicht-sichtbare cms (`!$cm->uservisible`). Bei Section-Wechsel wird eine Trennzeile mit `get_section_name` eingefuegt.
- Pro Instanz: `context_module::instance($cm->id)` (Ergebnis ungenutzt im Schleifenkoerper), `singleton_service::get_instance_of_booking_by_cmid`, `$booking->get_user_booking($USER)`.
- Baut `$numberofbookings` als HTML-`<ul>` aus `$b->text`; Link `view.php?id=$cm->id` (dimmed bei `!$cm->visible`).
- **Seiteneffekte:** reiner HTML-Output; keine Schreibvorgaenge.
- **Bewertung:** B — funktional, aber N+1 und Capability-nach-Header (siehe Resümee).

## Bewertungs-Resümee
Klassische Modul-Index-Seite, korrekt section-gruppiert und uservisible-gefiltert. Schwaechen:
- **N+1 in der Render-Schleife:** je Booking-Instanz `get_instance_of_booking_by_cmid` + `get_user_booking($USER)` (jeweils DB-Last) — bei Kursen mit vielen Booking-Instanzen unguenstig (Z.80–81, P3; singleton_service cached zwar Instanzen, `get_user_booking` aber pro Aufruf).
- **Capability nach Output:** `require_capability('mod/booking:choose')` (Z.46) steht **nach** `$OUTPUT->header()`/Heading (Z.42–43) — bei fehlender Berechtigung wird die Exception erst nach bereits gesendetem Seitenkopf geworfen (kosmetisch, Z.42–46, P3).
- **Unescaped `$b->text`:** `"<li>{$b->text}</li>"` (Z.87) wird ohne `format_string`/`s()` ausgegeben; der Option-Text ist zwar admin-gepflegt, aber inkonsistent zum `format_string($cm->name)` direkt darunter (Z.87, P3).
- `context_module::instance($cm->id)` (Z.79) im Schleifenkoerper ist totes Setup (Ergebnis ungenutzt).
Klassen-Score **B / P3**.
