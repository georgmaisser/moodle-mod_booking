# importoptions — Methoden-Doku
**Datei:** `importoptions.php` · **LOC:** 79 · **Subsystem:** S21 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozedurales Entry-Script (keine Klasse, im Namespace `mod_booking`). Es rendert die CSV-Import-Seite fuer **Buchungsoptionen**. Anders als `importexcel.php` enthaelt es keine eigene Parsing-/Persistenzlogik, sondern delegiert komplett an das **DynamicForm** `mod_booking\form\csvimport` (clientseitig via AMD-Modul `mod_booking/csvimport` gesteuert) und den `bookingoptionsimporter`. Kollaborateure: `singleton_service`, `bookingoptionsimporter::return_ajaxformdata`, `csvimport`-DynamicForm, `html_writer`, `$PAGE->requires->js_call_amd`.

## Request-/Permission-Flow
1. `required_param('id', PARAM_INT)` = cmid; `$PAGE->set_url(...)`.
2. `get_course_and_cm_from_cmid($id)` -> `require_course_login($course, false, $cm)`.
3. `$PAGE->activityheader->disable()`; `groups_get_activity_groupmode($cm)` (Ergebnis ungenutzt); `context_module::instance($cm->id)`.
4. `require_capability('mod/booking:importoptions', $context)` — Gate.
5. Seiten-Setup (Title aus Booking-Settings, Heading, Navbar, Layout `standard`).
6. `$PAGE->requires->js_call_amd('mod_booking/csvimport', 'init')` — laedt die client-seitige Import-UI.

## Form-Aufbau & Render
- **Zweck:** Baut die `ajaxformdata` aus `bookingoptionsimporter::return_ajaxformdata()` plus `cmid => $id` und instanziiert das DynamicForm `\mod_booking\form\csvimport(..., true, $ajaxformdata)`.
- `set_data_for_dynamic_submission()` initialisiert die Formdaten mit demselben Pfad wie der spaetere JS-Submit.
- Render: `$OUTPUT->header()` + Heading (`importcsvtitle`) + `html_writer::div($inputform->render(), '', ['id' => 'mbo_csv_import_form'])` + Footer.
- **Seiteneffekte:** reiner HTML-/JS-Output; **keine** DB-Schreibvorgaenge in diesem Script (die laufen erst beim AJAX-Submit des DynamicForms).
- **Bewertung:** A — saubere Delegation an das moderne DynamicForm/Importer-Framework; das Script ist nur noch ein Page-Wrapper.

## Bewertungs-Resümee
Schlankes, modernes Entry-Script: Permission-Gate korrekt, gesamte Import-Mechanik in das `csvimport`-DynamicForm + `bookingoptionsimporter` ausgelagert. Einzige Mini-Anmerkung: `groups_get_activity_groupmode($cm)` (Z.53) ist totes Setup (Ergebnis ungenutzt) — ein Copy-Paste-Relikt aus den aelteren Import-Scripts. Funktional unkritisch. Klassen-Score **A / -**.
