# semesters — Methoden-Doku
**Datei:** `semesters.php` · **LOC:** 98 · **Subsystem:** S21 · **Klassen-Score:** B / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Admin-Seite zur Verwaltung von Semestern, Feiertagen und (optional) Semesterwechsel. Sie instanziiert drei DynamicForms, rendert sie ueber das `semesters_holidays`-DTO und haengt die zugehoerigen AMD-Module ein. Kollaborateure: `form\dynamicsemestersform`, `form\dynamicholidaysform`, `form\dynamicchangesemesterform`, `output\semesters_holidays`, Renderer `mod_booking`.

## Methoden
Keine Methoden — Top-Level-Request-Flow:

### Auth-/Setup-Phase (Z.37–49) — top-level
- **Zweck:** `require_login(0,false)` (kein Gast-Autologin), setzt System-Kontext, `require_capability('mod/booking:editsemesters', $context)`, PAGE-URL und Titel. **Seiteneffekte:** Login-/Capability-Erzwingung. **Bewertung:** A — korrektes System-Kontext-Gate.

### Form-Aufbau + Render (Z.51–73) — top-level
- **Zweck:** Gibt Header/Heading aus, baut Semester- und Feiertags-Form (`set_data_for_dynamic_submission` + `render`); die Semesterwechsel-Form nur wenn `id` (cmid) gesetzt ist, sonst leerer String. Rendert alle drei via `semesters_holidays`-DTO. **Seiteneffekte:** HTML-Ausgabe. **Bewertung:** B — die `$cmid` wird trotz System-Kontext-Seite als reines „Existenz-Flag" benutzt (steuert nur, ob die Wechsel-Form erscheint), ohne den cmid-Kontext erneut zu pruefen — fuer eine reine Render-Form unkritisch.

### JS-Wiring (Z.75–96) — top-level
- **Zweck:** Haengt die AMD-Init-Aufrufe fuer Semester-, Feiertags- und (bei `cmid`) Semesterwechsel-Form ein; reicht der Wechsel-Form `cmid` als `existingsemester`-Datenobjekt durch. **Seiteneffekte:** `$PAGE->requires->js_call_amd`. **Bewertung:** B — Variablen-Recycling (`$data` wird Z.72 als DTO und Z.88 als stdClass wiederverwendet) ist leicht verwirrend, aber harmlos da sequenziell.

### Footer (Z.98) — top-level
- **Zweck:** `$OUTPUT->footer()`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanke, korrekt abgesicherte Admin-Form-Seite (System-Kontext + `editsemesters`). Nur kosmetische Schwaechen (Variablen-Recycling von `$data`, cmid als bloomes Flag). Keine funktionalen Maengel. Klassen-Score **B / -**.
