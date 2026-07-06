# slotteacherassignments — Methoden-Doku
**Datei:** `slotteacherassignments.php` · **LOC:** 97 · **Subsystem:** S21 · **Klassen-Score:** B / —
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse, keine Funktionen). Hostet das DynamicForm `mod_booking\form\slotteacherassignments_form` zur Zuordnung von Lehrenden (Examiner) zu Studierenden im Slotbooking. Die Persistenz (Tabelle `booking_slot_student_teacher`) liegt im Form (`process_dynamic_submission`); dieses Skript ist nur Host/Permission-Gate und Render-Rahmen. Kollaborateure: `config.php`/`locallib.php`, `singleton_service::get_instance_of_booking_option`, `booking_check_if_teacher`, `context_module`, `\mod_booking\form\slotteacherassignments_form`, `$PAGE`/`$OUTPUT`/`html_writer`.

## Ablauf (Request-/Permission-Flow)
- **Eingangsparameter:** `id` (cmid, `required_param PARAM_INT`), `optionid` (`required_param PARAM_INT`).
- **Aufloesung:** `get_course_and_cm_from_cmid` → `require_course_login` → `context_module::instance($cm->id)`; laedt `bookingoption` via `singleton_service` und prueft `booking_check_if_teacher($bookingoption->settings)`.
- **Auth-Gate (Z.38-44):** `$canmanage = is_siteadmin() || has_capability('mod/booking:manageslotunavailability') || has_capability('mod/booking:updatebooking')`. Ist weder `canmanage` noch `isteacherofoption` erfuellt, erzwingt `require_capability('mod/booking:manageslotunavailability')` einen Abbruch. Damit duerfen entweder Manager:innen ODER Lehrende der Option zuordnen.
- **Form-Lifecycle:** `slotteacherassignments_form` wird mit `customdata {id, optionid}` instanziiert. `is_cancelled()` → Redirect auf `report.php`. Bei `get_data()` wird `process_dynamic_submission()` ausgefuehrt und mit `$result->message` (oder Default-String) per `NOTIFY_SUCCESS` auf `$baseurl` redirected.
- **Rendering:** Header, Heading, Zurueck-Link, Optionstitel (`format_string($bookingoption->option->text ?? '')`), Beschreibungstext; `set_data_for_dynamic_submission()` + `$form->render()`; Footer.
- **Seiteneffekte:** DB-Schreibzugriff indirekt ueber `process_dynamic_submission()`; ansonsten nur Ausgabe + Redirects.
- **Bewertung:** B — solider Host. Das Zwei-Wege-Auth-Gate (Manager ODER Option-Lehrer) ist bewusst gestaltet und korrekt fail-closed (Default-`require_capability`). Leichte Redundanz: Bei `$data = $form->get_data()` wird `$data` nur als Truthy-Guard genutzt und nicht weiterverwendet, da das Form intern erneut auf die Submission zugreift — kein Bug, aber doppelte Datenbeschaffung.

## Bewertungs-Resümee
Schlanker, korrekt abgesicherter DynamicForm-Host. Permission-Logik durchdacht (Lehrende der Option ohne Manage-Cap zugelassen, sonst Cap-Pflicht), keine eigene Persistenz. Klassen-Score **B / —**.
