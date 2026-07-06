# semesters_holidays — Methoden-Doku
**Datei:** `classes/output/semesters_holidays.php` · **LOC:** 99 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`semesters_holidays` ist ein Renderable/Templatable-DTO (`mod_booking\output`), das die drei bereits vorgerenderten Formulare der Semester-/Feiertags-Settingsseite (Semester-Form, Feiertags-Form, Semesterwechsel-Form) buendelt und zusaetzlich die bestehenden Semester- und Feiertags-Records als base64-kodiertes JSON fuers Frontend (JS-Initialisierung) bereitstellt. Persistenz: liest read-only aus `booking_semesters` und `booking_holidays`. Kollaborateure: `$DB`, Mustache-Template, begleitendes AMD-JS, das die kodierten Daten auswertet.

## Methoden

### `public function __construct(string $renderedsemestersform, string $renderedholidaysform, string $renderedchangesemesterform)` — public
- **Zweck:** Uebernimmt die drei vorgerenderten Formular-HTML-Strings und laedt die bestehenden Semester/Feiertage zur JS-Vorbelegung. **Seiteneffekte:** `$DB->get_records('booking_semesters')` und `$DB->get_records('booking_holidays')`; kodiert beide via `base64_encode(json_encode(...))` in `existingsemesters`/`existingholidays`. **Bewertung:** A — klares Buendeln; die base64+JSON-Kodierung ist ein bewusster Transport-Mechanismus, um HTML-/Quoting-Probleme bei der Einbettung in Data-Attribute/Template zu vermeiden.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Stellt die vier Kernfelder bereit und ergaenzt `renderedchangesemesterform` nur dann, wenn dieses nicht leer ist (Template kann konditional rendern). **Seiteneffekte:** keine. **Rueckgabe:** assoziatives Array. **Bewertung:** A — saubere konditionale Ausgabe.

### Triviale Properties
`renderedsemestersform`, `renderedholidaysform`, `renderedchangesemesterform`, `existingsemesters`, `existingholidays` (alle string, Z.41–54) als Werte-Halter.

## Bewertungs-Resümee
Schlanker, gut verstaendlicher Buendel-DTO mit zwei read-only DB-Lesungen und durchdachter konditionaler Template-Ausgabe. Keine funktionalen Probleme. Klassen-Score **A / P3**.
