# page_allteachers — Methoden-Doku
**Datei:** `classes/output/page_allteachers.php` · **LOC:** 164 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`page_allteachers` ist das renderable/templatable DTO der Uebersichtsseite *aller* Lehrenden (`teachers.php`). Es laedt im Konstruktor die User-Records einer Liste von teacherids und transformiert sie in `export_for_template()` in ein Mustache-taugliches Array (Stammdaten, Profilbild, E-Mail unter Sichtbarkeitsregeln, Messaging-Verfuegbarkeit, diverse Links). Persistenz: keine eigene (liest `user`-Records). Kollaborateure: `$DB`, `$PAGE`/`$USER`/`$CFG`, `\user_picture`, `booking_answers::number_actively_booked`, Geschwister-DTO `page_teacher` (statischer Messaging-Check), `moodle_url`, diverse `get_config('booking', ...)`-Settings.

## Methoden

### `public function __construct(array $teacherids)` — public
- **Zweck:** Laedt fuer jede uebergebene teacherid den vollstaendigen `user`-Record und sammelt ihn in `$this->listofteachers`. **Seiteneffekte:** je teacherid ein `$DB->get_record('user', ['id' => ...])`. Nicht existente IDs werden stillschweigend uebersprungen. **Bewertung:** C — klassisches N+1: ein Einzel-Query pro Lehrperson statt eines `get_records_list('user','id',$teacherids)`. Auf der „alle Lehrenden"-Seite koennen das viele Queries sein.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Baut das Render-Array: setzt `canedit` (nur wenn `mod/booking:updatebooking` am System-Context), iteriert ueber `listofteachers` und legt pro Lehrperson Name, Anfangsbuchstabe (`orderletter`), gekuerzte Beschreibung (`substr(...,0,300)`), optional Langbeschreibung, Profilbild-URL, E-Mail (unter Sichtbarkeitskaskade), Messaging-Flag, Profil-Edit-URL sowie Detail-/Message-Links an. **Seiteneffekte:** ggf. `$PAGE->set_context(context_system::instance())`; `format_text`/`strip_tags`; `new \user_picture(...)->get_url($PAGE)`; mehrere `has_capability`-Pruefungen (mal gegen `$context` = system, mal gegen `$PAGE->context`); `get_config('booking', 'teachersshowemails'|'bookedteachersshowemails'|'alwaysenablemessaging')`; pro Lehrperson ggf. `booking_answers::number_actively_booked($USER->id, $teacher->id)`; `page_teacher::teacher_messaging_is_possible($teacher->id)`. **Rueckgabe:** Array mit `canedit`, `teachers[]`. **Bewertung:** C — funktional korrekt, aber pro Zeile potenziell mehrere DB-Roundtrips (number_actively_booked, teacher_messaging_is_possible mit eigenem INTERSECT-Query). Capability-Basis inkonsistent (`$context` vs. `$PAGE->context`), die in der Praxis identisch sind, aber den Code fragil machen. `substr` ueber bereits `format_text`-/`strip_tags`-verarbeiteten Text kann Multibyte-Zeichen zerschneiden (kein `core_text::substr`).

### Triviale Properties
`public $listofteachers = []` (Z.45) als reiner Sammelcontainer.

## Bewertungs-Resümee
Sauberes, gut kommentiertes Listen-DTO mit korrekter E-Mail-Sichtbarkeitslogik. Hauptschwaeche ist die Performance: N+1-User-Load im Konstruktor und mehrere Per-Zeilen-Queries (Messaging-Check, aktive Buchungen) in `export_for_template`. Funktional unkritisch. Klassen-Score **B / P3**.
