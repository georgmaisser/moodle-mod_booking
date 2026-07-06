# teacher — Methoden-Doku
**Datei:** `teacher.php` · **LOC:** 64 · **Subsystem:** S21 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse) fuer die oeffentliche Lehrer-Profilseite. Delegiert die gesamte Datenaufbereitung und das Rendering an das Output-DTO `mod_booking\output\page_teacher` und den Plugin-Renderer (`render_teacherpage`). Persistenz: keine direkt; `page_teacher` laedt Lehrerstammdaten/Optionen. Kollaborateure: `page_teacher`, `mod_booking`-Renderer, `$PAGE`, `$OUTPUT`.

## Request-/Permission-Flow
1. **Login (optional gated):** `require_login(0, false)` wird **nur** ausgefuehrt, wenn die Config `booking/teachersnologinrequired` leer/aus ist. Bei gesetztem Flag ist die Seite ohne Login erreichbar (bewusste oeffentliche Profilseite). `phpcs:ignore moodle.Files.RequireLogin.Missing` dokumentiert die Absicht.
2. **Kontext:** `context_system::instance()` (wirft `badcontext` bei Fehlschlag), `$PAGE->set_context`.
3. **Parameter:** `teacherid` (required PARAM_INT).
4. **Page-Setup:** URL, Title/Heading=`get_string('teacher')`, Navbar, Pagelayout=base, Body-Class `page-mod-booking-teacher`.
5. **Rendering:** Header → Link „alle Lehrer anzeigen" (`teachers.php`) → `new page_teacher($teacherid)` → `$PAGE->get_renderer('mod_booking')->render_teacherpage($data)` → Footer.

## Bewertung der Logik
- **Seiteneffekte:** Nur HTML-Ausgabe; keine Schreibzugriffe.
- **Bewertung:** A — minimaler, sauberer View-Controller; saemtliche Logik liegt im DTO/Renderer (gute Trennung). **Kein Capability-Check** und keine Validierung, dass `teacherid` ueberhaupt ein Lehrer ist — das ist hier **per Design** (oeffentliche Profilseite, vgl. CLASS_INDEX „Oeffentliche Lehrer-Profilseite"); etwaige Sichtbarkeitsregeln muessen in `page_teacher` liegen. Der „alle Lehrer"-Link wird per String-Konkatenation gebaut (kein `moodle_url`), funktional aber unkritisch.

## Bewertungs-Resümee
Vorbildlich schlanker Entry-Point, der konsequent ans Output-DTO delegiert. Offenheit der Seite ist intendiert und konfigurierbar. Klassen-Score **A / —**.
