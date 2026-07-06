# subscribeusersactivity — Methoden-Doku
**Datei:** `subscribeusersactivity.php` · **LOC:** 80 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (kein Klassen-Deklarant). Die Seite erlaubt es einem Lehrenden/Manager, bereits gebuchte User von einer Buchungsoption in eine andere zu transferieren. Sie rendert das moodleform `mod_booking\form\subscribeusersactivity` (Auswahl der Quell-Option) und fuehrt bei Submit den Transfer ueber `booking_option::transfer_users_to_otheroption()` aus. Kollaborateure: `singleton_service`, `mod_booking\utils\db::getusersactivity()`, `booking_check_if_teacher()` (locallib), `$PAGE`/`$OUTPUT`.

## Request-/Permission-Flow
1. **Parameter:** `id` (cmid, `required_param`), `optionid` (`required_param`) — die *Ziel*-Option, in die transferiert wird.
2. **Auth:** `get_course_and_cm_from_cmid($id)` → `require_login($course, true, $cm)`; Kontext = `context_module`.
3. **Capability-Gate (Z.51-55):** wenn `booking_check_if_teacher($bookingoption->option)` *false* ist, muss eine von `mod/booking:subscribeusers` oder `moodle/site:accessallgroups` vorliegen, sonst `moodle_exception('nopermissions', ...)`. Teacher der Option umgehen das Capability-Gate.
4. **Form-Lifecycle (Z.57-69):**
   - `is_cancelled()` → Redirect zurueck nach `report.php`.
   - bei `get_data()`: `db::getusersactivity($id, $fromform->bookingoption, false)` ermittelt die zu transferierenden User der **Quell**-Option (`$fromform->bookingoption`), laedt die Quell-Option via `singleton_service` und ruft `transfer_users_to_otheroption($optionid, $totransfer)` auf; danach Redirect mit Erfolgsmeldung.
5. **Render (Z.71-80):** Header, Heading mit Option-Text, `$mform->display()`, Footer.

## Bewertung der Einzelschritte
- **Capability-Logik (Z.51-55):** Korrekt strukturiert, aber `accessallgroups` als alternatives Mutations-Recht ist semantisch grob (Gruppen-Sicht ≠ Transfer-Recht). Bewertung B.
- **Transfer (Z.61-69):** Der eigentliche Schreibvorgang verlaesst sich vollstaendig auf die Seiten-Guards; das Form hat (laut Quelle) keine eigene Capability-Pruefung. Da der Transfer auf der **Ziel**-Option-Berechtigung beruht, aber User aus der **Quell**-Option zieht, koennte ein User mit Recht nur auf der Zieloption fremde Quell-Optionen leeren — abhaengig davon, ob `transfer_users_to_otheroption` selbst prueft. Bewertung B (potenzieller Cross-Option-Scope, siehe Findings P3).
- **i18n:** `get_string('sucesfullytransfered', 'booking')` — Tippfehler im String-Key und Legacy-Component `'booking'` statt `mod_booking`. Kosmetisch.

## Bewertungs-Resümee
Schlanker, konventioneller Form-Entry-Point mit sauberem Form-Lifecycle und Pflicht-Login. Schwaechen: das Capability-Gate erlaubt `accessallgroups` als Mutations-Recht, der Transfer bezieht sich auf eine Quell-Option deren Berechtigung hier nicht erneut geprueft wird, und ein i18n-Tippfehler. Funktional unkritisch. Klassen-Score **B / P3**.
