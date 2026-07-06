# instancetemplateadd — Methoden-Doku
**Datei:** `instancetemplateadd.php` · **LOC:** 92 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozedurales Entry-Script (keine Klasse). Speichert die **aktuelle Booking-Instanz als wiederverwendbares Instanz-Template** (Tabelle `booking_instancetemplate`). Das Anlegen ist gated: nur das **erste** Template ist gratis, weitere erfordern eine aktivierte PRO-Lizenz. Kollaborateure: `instancetemplateadd_form` (Name-Eingabe), `wb_payment::pro_version_is_activated`, `$DB` (Tabellen `booking_instancetemplate`, `course_modules`, `booking`), `core\output\notification`.

## Request-/Permission-Flow
1. `required_param('id', PARAM_INT)` = cmid; `$PAGE->set_url(...)`.
2. `get_course_and_cm_from_cmid($id)` -> `require_course_login($course, false, $cm)`.
3. `$PAGE->activityheader->disable()`; `context_module::instance($cm->id)` (sonst `moodle_exception('badcontext')`).
4. `require_capability('mod/booking:manageoptiontemplates', $context)` — Gate.
5. Seiten-Setup (Title/Heading/Navbar `saveinstanceastemplate`, Layout `standard`).
6. Instanziierung `instancetemplateadd_form`; `$DB->get_records('booking_instancetemplate')` -> `$numberoftemplates`.

## Submit-Logik (`$mform->get_data()`-Zweig)
- **Zweck:** Bei gueltigem Submit wird das Template nur dann angelegt, wenn `wb_payment::pro_version_is_activated()` **oder** `$numberoftemplates == 0` (erstes Template gratis).
- Bei erlaubtem Anlegen: `$DB->get_record('course_modules', ['id' => $id], 'instance')` -> `$DB->get_record('booking', ['id' => $instance->instance])`; neues `stdClass` mit `name = $data->name` und `template = json_encode((array) $booking)`; `$DB->insert_record('booking_instancetemplate', ...)`; Redirect mit `instancesuccessfullysaved` (5s).
- Bei verweigertem Anlegen: Redirect mit `instancenotsavednovalidlicense` als `NOTIFY_ERROR` (1s).
- Cancel-Zweig: Redirect zu `view.php` (0s). Else-Zweig: `$OUTPUT->header()` + `$mform->display()`.
- **Seiteneffekte:** schreibt `booking_instancetemplate`; Redirects/HTTP-Output.
- **Bewertung:** B — Logik korrekt, aber Template-Serialisierung grob (siehe Resümee).

## Bewertungs-Resümee
Klares Gate (Capability + PRO/erstes-Template), sauberer Form-Lifecycle. Anmerkungen:
- **Roh-Serialisierung des kompletten Booking-Records:** `json_encode((array) $booking)` (Z.76) schreibt den vollstaendigen DB-Record inkl. `id`, `timecreated`, `course`, `timemodified` ins Template; beim spaeteren Anwenden muessen diese instanz-/zeit-spezifischen Felder wieder herausgefiltert werden, sonst drohen stale IDs — fragiler Vertrag (Z.76, P3).
- **Redundanter Re-Fetch:** `course_modules`/`booking` werden erneut per `$DB->get_record` geladen (Z.71–72), obwohl `$cm` (mit `$cm->instance`) bereits aus `get_course_and_cm_from_cmid` vorliegt — vermeidbare Doppel-Queries (P3).
- `$defaultvalues = new stdClass()` (Z.88) im Else-Zweig ist totes Setup (nie an `$mform->set_data` uebergeben).
- Race: `$numberoftemplates`-Pruefung (Z.62) und `insert_record` (Z.78) sind nicht atomar; zwei gleichzeitige „erste" Speichervorgaenge ohne PRO koennten beide durchgehen — praktisch vernachlaessigbar (P3).
Klassen-Score **B / P3**.
