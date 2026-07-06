# confirmactivity — Methoden-Doku
**Datei:** `confirmactivity.php` · **LOC:** 94 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Die Seite erlaubt Lehrenden/Berechtigten, fuer eine Booking-Option diejenigen gebuchten User als „Aktivitaet abgeschlossen" zu markieren, die entweder eine bestimmte Aktivitaet abgeschlossen oder ein bestimmtes Badge/Zertifikat erhalten haben. Persistenz indirekt ueber `booking_option::confirmactivity()`. Kollaborateure: `singleton_service`, `mod_booking\form\confirmactivity` (moodleform), `mod_booking\utils\db` (Lookup der betroffenen User), `booking_check_if_teacher`.

## Request-/Permission-Flow
1. Params: `id` (cmid), `optionid` (Z.33-34).
2. `get_course_and_cm_from_cmid($id)` + `require_login($course, true, $cm)` (Z.36-38).
3. Kontext: `context_module::instance($cm->id)`, `$PAGE->set_context` (Z.41-42).
4. Booking-Option via `singleton_service::get_instance_of_booking_option($id, $optionid)` (Z.44).
5. **Zugriffskontrolle (Z.49-53):** Wenn der User KEIN Teacher der Option ist (`booking_check_if_teacher`), muss er `mod/booking:readresponses` ODER `moodle/site:accessallgroups` besitzen, sonst `moodle_exception('nopermissions', ...)`.
6. Formular instanziieren (Z.55).

## Code-Abschnitte (statt Methoden)

### Cancel-Zweig (Z.57-58)
`$mform->is_cancelled()` → `redirect($backurl)` zurueck auf `report.php`.

### Submit-Verarbeitung (Z.59-83)
- **Zweck:** Wertet `$fromform->whichtype` aus und bestaetigt fuer die ermittelte User-Menge die Aktivitaet.
- **Case 0 (Activity, Z.61-69):** Bei gesetzter `activity` werden via `db::getusersactivity($fromform->activity, $optionid, true)` die User ermittelt, die diese Aktivitaet abgeschlossen haben; pro User `$bookingoption->confirmactivity($user)`.
- **Case 1 (Badges, Z.71-79):** Bei gesetzter `certid` werden via `db::getusersbadges($fromform->certid, $optionid)` die Badge-Empfaenger ermittelt; pro User `$bookingoption->confirmactivity($user)`.
- **Seiteneffekte:** Schleifen-Aufrufe von `booking_option::confirmactivity()` (setzt Completion); danach `redirect($backurl, get_string('sucesfullcompleted', 'booking'))`.
- **Bewertung:** B — sauberer CSRF-Schutz ueber moodleform (`get_data()`/sesskey), Lookup ist in `utils\db` gekapselt. Pro-User-Schleife ist hier akzeptabel (Completion-Setzung ist inhaerent per-User). Hinweis: Die `redirect`-Zeile (Z.82) ist im Original um eine Ebene eingerueckt, liegt aber korrekt im `else if`-Block hinter dem `switch` (laeuft fuer beide Cases). Sprachstring-Key `sucesfullcompleted` ist falsch geschrieben (kosmetisch).

### Render (Z.85-94)
`set_url`/`set_title`/`set_heading`, Header, Heading mit `$bookingoption->option->text`, `display()`, Footer.

## Auffaelligkeiten
- **Z.44 / Z.55 (P3): Parameter-Semantik.** `get_instance_of_booking_option($id, $optionid)` erhaelt `$id` (= cmid) als ersten Parameter; das Form bekommt `bookingid => $bookingoption->booking->id`. Funktioniert, da die Option-Factory cmid erwartet, aber die doppelte Bedeutung von `$id` (cmid) ist leicht zu verwechseln.
- Keine N+1-/Datenverlust-Probleme erkennbar.

## Bewertungs-Resümee
Klar strukturiertes Bestaetigungs-Skript mit korrekter, gestaffelter Zugriffskontrolle (Teacher ODER readresponses/accessallgroups) und gekapseltem User-Lookup. Schwaechen rein kosmetisch (Einrueckung der redirect-Zeile, Tippfehler im Sprachstring). Klassen-Score **B / P3**.
