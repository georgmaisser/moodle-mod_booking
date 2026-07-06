# moveoption — Methoden-Doku
**Datei:** `moveoption.php` · **LOC:** 106 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse) zum Verschieben einer Buchungsoption in eine andere Booking-Instanz desselben Kurses. Hat zwei Modi: ohne Ziel-cmid listet die Seite die uebrigen Booking-Instanzen des Kurses mit „Verschieben"-Buttons; mit Ziel-cmid fuehrt sie den Transfer ueber `booking_option::move_option_otherbookinginstance()` aus und meldet Erfolg/Fehler. Kollaborateure: `singleton_service`, `booking_option`, `get_all_instances_in_course`, `get_fast_modinfo`, `$OUTPUT`, core-Renderer.

## Request-/Permission-Flow
1. **Bootstrap (Z.27-34):** `config.php` + `lib.php`; Parameter `id` (cmid), `optionid` (required), `movetocmid` (optional, default 0); `require_sesskey()`.
2. **Page/Context (Z.36-49):** Page-URL, `get_course_and_cm_from_cmid`, `require_course_login`, Booking via `singleton_service::get_instance_of_booking_by_cmid` (sonst `invalid_parameter_exception`), Modul- + Kurs-Context.
3. **Permission-Gate (Z.50-52):** `mod/booking:updatebooking` im **Kurs**kontext, sonst `required_capability_exception`.
4. **Zweig A — Transfer (Z.56-70):** bei `targetcmid > 0` Option laden, `move_option_otherbookinginstance($targetcmid)`; leerer Rueckgabestring = Erfolg (Success-Notification), sonst Problem-Notification + Fehlerstring; jeweils „Weiter"-Button zurueck auf `view.php`.
5. **Zweig B — Auswahlliste (Z.71-101):** bei `targetcmid == 0` `get_all_instances_in_course('booking', $course, true)`; ohne andere Instanzen Hinweis-Heading; sonst je Instanz (ausser der aktuellen) ein „Verschieben"-Button mit angehaengtem `movetocmid` + `sesskey`, als `list-group` gerendert.
6. **Zweig C (Z.102-104):** unerreichbarer else-Zweig (`targetcmid` ist int) wirft `moodle_exception`.

- **Seiteneffekte:** verschiebt eine Option zwischen Instanzen (DB-Mutation in `move_option_otherbookinginstance`); Seiten-Output.
- **Bewertung:** C — sesskey + Capability korrekt geprueft. Schwaechen:
  - **API-Fehlbenutzung / toter Render (P3):** Z.79 `$OUTPUT->single_button($button)` uebergibt ein bereits konstruiertes `single_button`-Objekt an `single_button($url, $label, ...)`, dessen erster Parameter eine `moodle_url` erwartet; zudem wird das Resultat nicht ge-`echo`t. Im „keine anderen Instanzen"-Fall erscheint daher kein funktionierender Button.
  - **Variablen-Clobbering (P3):** Z.84 ueberschreibt die aeussere `$cm`-Variable in der Schleife (`$cm = $modinfo->get_cm(...)`); nach der Schleife nicht mehr benutzt, aber unsauber.
  - **Hardcodierte Strings (P3):** Z.66/75 englische Klartext-Meldungen statt `get_string` (i18n-Luecke).

## Bewertungs-Resümee
Funktionaler Move-Workflow mit korrekter sesskey-/Capability-Absicherung. Die Maengel sind kosmetisch bis klein-funktional (falsche single_button-API im leeren Zweig, `$cm`-Ueberschreibung, nicht uebersetzte Meldungen) und ohne Datenrisiko. Klassen-Score **C / P3**.
