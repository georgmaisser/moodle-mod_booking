# option_date_template — Methoden-Doku
**Datei:** `option_date_template.php` · **LOC:** 61 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Host-Seite fuer das Optiondate-Template-DynamicForm: rendert lediglich ein Geruest (Link „Load form" + leere Form-Region) und laedt das AMD-Modul `mod_booking/dynamicform2`, das das eigentliche DynamicForm per AJAX nachlaedt. Kollaborateure: `singleton_service` (Booking-Instanz), Core `$PAGE`/`$OUTPUT`, `html_writer`.

## Request-/Permission-Flow
1. `require_once config.php` + `locallib.php`.
2. Pflichtparameter `id` (cmid) und `optionid` (`PARAM_INT`); `sesskey` als `optional_param(... PARAM_INT)`.
3. Setzt System-Kontext und Seiten-URL; loest Kurs/CM via `get_course_and_cm_from_cmid($id)` auf; `require_course_login($course, false)`.
4. Holt die Booking-Instanz per `singleton_service::get_instance_of_booking_by_cmid`; wirft `invalid_parameter_exception` bei Fehlschlag.
5. `context_module::instance($cm->id)` (wirft `badcontext`); Capability-Gate `mod/booking:manageoptiontemplates`, sonst `moodle_exception('nopermissions', ...)`.
6. Gibt Header aus, rendert einen `loadform`-Link (`data-action`) und eine leere `data-region="form"`-Div, ruft `js_call_amd('mod_booking/dynamicform2')`, dann Footer.

## Bewertung der Logik
- **Bewertung:** B — Permission-Kette korrekt (Course-Login + Modul-Capability). Schwaechen sind kosmetisch/i18n: der Link-Text `'Load form'` ist hartkodiert statt `get_string`; der ausgelesene `$sesskey` wird nirgends verwendet (toter Parameter); `$optionid` wird zwar verlangt, aber im PHP-Pfad nicht genutzt (an das JS/Form ueber die URL weitergereicht).
- Auskommentierter Code (`$form->set_data_for_dynamic_submission();`) mit phpcs-ignore — Altlast.

## Findings
- Keine funktionalen Bugs. Nur i18n-/Dead-Param-Kosmetik (Link-String hartkodiert, ungenutzter `$sesskey`).

## Bewertungs-Resümee
Schmale, korrekt abgesicherte Host-Seite, die die Logik vollstaendig ins DynamicForm/JS verlagert. Klassen-Score **B / P3**.
