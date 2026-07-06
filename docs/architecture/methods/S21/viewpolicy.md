# viewpolicy — Methoden-Doku
**Datei:** `viewpolicy.php` · **LOC:** 55 · **Subsystem:** S21 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Das Skript zeigt die Buchungs-Policy (`bookingpolicy`) einer Booking-Instanz als eigenstaendige Seite an — typischerweise aus einem Policy-Link/Popup heraus aufgerufen. Eingabe ist `id` (Course-Module-ID). Kollaborateure: `singleton_service` (booking-Instanz per cmid), Moodle-Core (`$PAGE`, `$OUTPUT`, `require_login`, `context_course`, `format_text`). Kein direkter DB-Zugriff; die Policy stammt aus `$booking->settings->bookingpolicy`.

## Request-/Permission-Flow
1. **Eingabe (Z.29):** `required_param('id', PARAM_INT)` → `$cmid`.
2. **Instanz-Load + Tags (Z.31–32):** `singleton_service::get_instance_of_booking_by_cmid($cmid)`; `$booking->apply_tags()` ersetzt Tag-Platzhalter in den Settings.
3. **Kontext (Z.34):** `context_course::instance($booking->settings->course)`.
4. **Kurs/CM-Resolution (Z.36):** `get_course_and_cm_from_cmid($cmid)`.
5. **Auth (Z.38):** `require_login($course->id, false)` — Login + Kurszugang erforderlich (`false` = autologinguest aus).
6. **Seitenkopf (Z.40–46):** `set_url` / `set_title` / `set_heading` (`bookingpolicy`-String), `$OUTPUT->header()` + `$OUTPUT->heading(..., 2)`.
7. **Inhalt (Z.48–52):** `$OUTPUT->box_start('generalbox', 'tag-blogs')`, `format_text($booking->settings->bookingpolicy)`, `box_end()`.
8. **Ausgabe (Z.54):** `$OUTPUT->footer()`.

## Bewertung einzelner Stellen
- **Reihenfolge Auth vs. Arbeit (Z.31–38):** `$booking` wird geladen und `apply_tags()` ausgefuehrt, **bevor** `require_login` aufgerufen wird (Z.38). Da `get_instance_of_booking_by_cmid` lediglich Instanz-Settings laedt (keine personenbezogenen Daten) und die eigentliche HTML-Ausgabe erst nach `require_login` erfolgt, ist das funktional unkritisch — sauberer waere dennoch, `require_login` vor jeglicher Datenarbeit zu setzen. **Bewertung:** B-Detail (Reihenfolge), kein echter Leak.
- **`$context` ungenutzt (Z.34):** Der erzeugte `context_course` wird im weiteren Verlauf nicht verwendet (kein `require_capability`, kein `format_text`-Context). Toter Wert; `format_text` laeuft ohne expliziten Kontext-/Options-Parameter. **Bewertung:** B — harmlose Cruft, leicht entfernbar.
- **`format_text` ohne Optionen (Z.50):** Policy-HTML wird mit Default-Optionen gefiltert (Cleaning aktiv), was fuer beliebigen Admin-eingegebenen Policy-Text angemessen ist. **Bewertung:** A.
- **Auth-Modell (Z.38):** `require_login($course->id, false)` schuetzt die Seite gegen Gast-/Anonymzugriff; angemessen fuer eine Policy-Anzeige. **Bewertung:** A.

## Bewertungs-Resümee
Sehr schlankes Anzeige-Skript ohne eigene Logik: Instanz laden, Tags anwenden, Policy via `format_text` in einer Box ausgeben. Schwaechen rein kosmetisch — `require_login` erst nach dem Instanz-Load/`apply_tags` (Z.38 nach Z.31–32) und ein ungenutzter `$context` (Z.34). Keine funktionalen oder Sicherheits-Bugs. Klassen-Score **A / P3**.
