# teacherunavailability.php — Methoden-Doku
**Datei:** `teacherunavailability.php` · **LOC:** 124 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Einstiegspunkt (kein Klassenkontext) der Slotbooking-Feature: rendert die Seite zum Verwalten von Lehrer-Unavailability-Bloecken. Das Skript validiert Parameter, prueft die Berechtigung (manage-slot-unavailability ODER updatebooking ODER „eigener Teacher"), instanziiert das `teacherunavailability_form` (DynamicForm) im Server-Render-Pfad und initialisiert das AMD-Modul `mod_booking/teacherUnavailability` ueber das umschliessende Container-Div mit `data-*`-Attributen. Kollaborateure: `singleton_service`, `booking_option`, `booking_check_if_teacher`, `core_user`, `teacherunavailability_form`, Modul-Kontext + Capabilities. Reiner Render-/Bootstrap-Pfad — Mutationen passieren spaeter via Form/Webservice, nicht hier.

## Request-/Permission-Flow
1. **Z.29–39 — Bootstrap + Params:** config.php + locallib.php; Pflichtparameter `id` (cmid), `optionid`; optionale `scopeoptionid`, `teacherid`, `date`, `scope`, `markmode`, `viewmode` (PARAM_ALPHAEXT bzw. PARAM_INT).
2. **Z.41–45 — Kontext:** `get_course_and_cm_from_cmid($id,'booking')`, `require_course_login($course,false,$cm)`, Modul-Kontext, `singleton_service::get_instance_of_booking_option($cm->id,$optionid)`.
3. **Z.47–54 — Berechtigung:** `$isteacherofoption = booking_check_if_teacher($bookingoption->settings)`; `$canmanageunavailability = manageslotunavailability || updatebooking`. Wenn weder verwalten-darf noch Teacher der Option → `require_capability('mod/booking:manageslotunavailability')` (harter Abbruch).
4. **Z.56–62 — Teacher-Default + Self-Guard:** Leerer `teacherid` → eigener `$USER->id`. Darf-nicht-verwalten + fremder Teacher → `moodle_exception('nopermissions', ...)` mit String `slot_error_editownonly` (nicht-Manager duerfen nur eigene Bloecke bearbeiten).
5. **Z.64–67 — Default-Datum + Teacher-Load:** Leeres `date` → `time()`; `core_user::get_user($teacherid,'*',MUST_EXIST)`.
6. **Z.69–83 — Page-Setup:** Base-URL mit allen State-Params, separate `report.php`-Reporturl; URL/Title/Heading/Context.
7. **Z.85–93 — Render-Header:** `$OUTPUT->header()`, Headings, `slot_teacher_unavailability_for` mit `fullname($teacher)`.
8. **Z.95–120 — Form:** `formparams` aus allen State-Werten; `new teacherunavailability_form(null,null,'post','',[],true,$formparams)`; `set_data_for_dynamic_submission()`; Rendering in ein Div mit `data-*`-Attributen (inkl. `data-reporturl`).
9. **Z.122–124 — JS-Init:** `js_call_amd('mod_booking/teacherUnavailability','init',[$formcontainerid])`; Footer.

## Bewertung einzelner Stellen
- **Z.47–62 — Berechtigungskette:** Korrekt geschichtet (Manager-Override vor Self-Only). Der strikte Vergleich `$teacherid !== (int)$USER->id` (Z.60) ist typsicher, da `teacherid` aus PARAM_INT/optional_param bereits int ist. **Bewertung:** A.
- **Z.105 — DynamicForm im Server-Render:** Server-seitiges Vorab-Rendern des DynamicForms (mit `set_data_for_dynamic_submission`) ist das etablierte Muster, damit die Seite ohne JS einen Initialzustand zeigt. Sauber. **Bewertung:** B.
- **Z.34/72/98/113 — `scopeoptionid` Pass-Through:** Wird durchgereicht, aber hier nur in URL/Form/Datenattribute gespiegelt; die eigentliche Scope-Aufloesung liegt im Form/Service. Keine Validierung gegen die Option an dieser Stelle — bewusst delegiert. **Bewertung:** B / P3.

## Bewertungs-Resümee
Solides, defensiv abgesichertes Bootstrap-Skill mit klarer Capability-/Self-Only-Logik und sauberem DynamicForm-+AMD-Anbindungsmuster. Keine direkten Mutationen, keine erkennbare Sicherheits-/Datenverlust-Luecke. Klassen-Score **B / P3**.
