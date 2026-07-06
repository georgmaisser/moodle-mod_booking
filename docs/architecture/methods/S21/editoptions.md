# editoptions — Methoden-Doku
**Datei:** `editoptions.php` · **LOC:** 132 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point zum Editieren/Erstellen einer Buchungsoption ueber das DynamicForm `option_form`. Anders als die Legacy-Speicher-Skripte persistiert diese Seite NICHT selbst — sie rendert nur die Form (Submission laeuft per AJAX/DynamicForm ueber `set_data_for_dynamic_submission` und das AMD-Modul). Der Hauptwert liegt in der dreifach-verschachtelten Capability-Pruefung. Kollaborateure: `singleton_service`, `option_form`, `wb_payment`, `booking_check_if_teacher`, Renderer/`$OUTPUT`, AMD `mod_booking/dynamiceditoptionform`.

## Ablauf (Request-/Permission-Flow)

### Parameter-Aufnahme (Z.34-44)
- **Zweck:** `id` (cmid, required), `optionid` (required), optionale `copyoptionid`, `createfromoptiondates`, `confirm`, `sesskey`, `mode`. `returnurl` mit Fallback auf `view.php?id=cmid`, validiert via `PARAM_LOCALURL`. **Seiteneffekte:** keine. **Bewertung:** A — `PARAM_LOCALURL` schuetzt vor Open-Redirect.

### Login + Page-Setup (Z.46-61)
- **Zweck:** `get_course_and_cm_from_cmid($cmid)`, `require_course_login`, PAGE-URL, Activity-Header off, Pagelayout `admin`, Body-Class `limitedwidth`; setzt `bookingid` aus `cm->instance` und `groupmode` (Letzteres ungenutzt). **Seiteneffekte:** Login-Gate, `$PAGE`-Mutation, DB-Reads. **Bewertung:** B — `$groupmode` (Z.61) wird berechnet, aber nirgends verwendet (toter Aufruf).

### Instanz-/Context-Pruefung (Z.63-69)
- **Zweck:** `booking`-Singleton + `context_module`. **Seiteneffekte:** wirft `invalid_parameter_exception` / `moodle_exception('badcontext')`. **Bewertung:** A.

### Dreifaches Capability-Gate (Z.71-88)
- **Zweck:** Zugriff erlaubt, wenn EINES gilt: (a) `mod/booking:updatebooking`; (b) `mod/booking:addeditownoption` UND `booking_check_if_teacher($optionid)` (eigene Option); (c) `mod/booking:addoption` UND `empty($optionid)` (Neuanlage). Sonst `moodle_exception('nopermissions')`. **Seiteneffekte:** ggf. DB-Read in `booking_check_if_teacher`. **Bewertung:** A — sauber dokumentiertes, korrektes Least-Privilege-Gate.

### Option-Settings-Konsistenz (Z.91-97)
- **Zweck:** Normalisiert `optionid < 0` auf 0 (Neuanlage), laedt `booking_option_settings` und verifiziert, dass `settings->cmid` zum aufgerufenen `cmid` passt (Cross-Instance-Schutz). **Seiteneffekte:** Singleton-Load (ggf. DB). **Bewertung:** A — verhindert Editieren einer Option ueber den falschen Course-Module-Context (`badcontext`).

### Form-Bau + Rendering (Z.99-132)
- **Zweck:** Baut `$params` (cmid, id=optionid, optionid, bookingid, copyoptionid, returnurl), instanziiert `option_form` und ruft `set_data_for_dynamic_submission()`. Gibt Header aus, bei vorhandener `optionid` einen „youareediting"-Alert (+ optionalen Non-PRO-Hinweis), rendert die Form in `#editoptionsformcontainer`, laedt das AMD-Init mit `$params` und gibt den Footer aus. **Seiteneffekte:** Echo HTML, `js_call_amd`. **Bewertung:** B — `$settings` wird Z.93 und erneut Z.118 (bei `!empty($optionid)`) geladen (Singleton, daher gecacht — minimal); reines Rendering, kein direkter Persistenz-Pfad hier.

## Bewertungs-Resümee
Saubere, sicherheitsbewusste Edit-Seite: `PARAM_LOCALURL`-Returnurl, dreifaches Least-Privilege-Capability-Gate und Cross-Instance-Context-Check. Persistenz delegiert vollstaendig an das DynamicForm (kein Save-Logik-Risiko im Skript). Kleinmaengel: ungenutzter `$groupmode`, doppelter Settings-Load. Keine funktionalen Bugs. Klassen-Score **B / P3**.
