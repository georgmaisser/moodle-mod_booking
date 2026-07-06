# viewconfirmation — Methoden-Doku
**Datei:** `viewconfirmation.php` · **LOC:** 91 · **Subsystem:** S21 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Das Skript rendert eine Bestaetigungsseite, nachdem ein Nutzer eine Buchungsoption gebucht oder sich auf die Warteliste gesetzt hat. Eingabe sind `id` (Course-Module-ID) und `optionid`; Ausgabe ist eine Standard-Moodle-Seite mit dem instanzspezifischen, ueber Platzhalter gerenderten Bestaetigungstext. Kollaborateure: `singleton_service` (booking_option / booking_option_settings / booking_answers), `placeholders\placeholders_info` (Platzhalter-Rendering), Moodle-Core (`$PAGE`, `$OUTPUT`, `require_course_login`, `context_module`). Kein eigener DB-Zugriff ausserhalb der Singleton-Services.

## Request-/Permission-Flow
1. **Eingabe (Z.34–35):** `required_param('id', PARAM_INT)` → `$cmid`, `required_param('optionid', PARAM_INT)` → `$optionid`. Beide Pflicht; fehlend ⇒ Core-Exception.
2. **Page-URL (Z.37–41):** `$PAGE->set_url(...)` mit beiden Params.
3. **Kurs/CM-Resolution (Z.43):** `get_course_and_cm_from_cmid($cmid)`.
4. **Auth (Z.45):** `require_course_login($course, false, $cm)` — Zugang nur fuer im Kurs eingeschriebene/berechtigte Nutzer (`false` = autologinguest aus).
5. **Activity-Header (Z.48):** `$PAGE->activityheader->disable()` (Moodle-4.0+-Konvention: Instanzbeschreibung nur auf view.php).
6. **Option-Resolution (Z.50–52):** `singleton_service::get_instance_of_booking_option($cmid, $optionid)`; bei Fehlschlag `invalid_parameter_exception`.
7. **Kontext (Z.54–56):** `context_module::instance($cmid)`; bei Fehlschlag `moodle_exception('badcontext')`.
8. **Seitenkopf (Z.58–64):** Navbar/Title/Heading/Pagelayout `standard`, `$OUTPUT->header()` + `$OUTPUT->heading(...)`.
9. **Buchungsstatus (Z.66–81):** Laedt `booking_option_settings` + `booking_answers`, vergleicht `$USER->id` gegen `get_usersonlist()`/`get_usersonwaitinglist()`. Gebucht ⇒ `viewconfirmationbooked`-String; Warteliste ⇒ `viewconfirmationwaiting`-String; sonst ⇒ `notbooked`-Fehlertext + Continue-Button zur Kursseite + Footer + `return`.
10. **Platzhalter-Rendering (Z.82–86):** `placeholders_info::render_text($text, $cmid, $optionid)` in try/catch; bei `moodle_exception` wird die Exception-Message als Text ausgegeben.
11. **Ausgabe (Z.88–90):** Text echo + `$OUTPUT->footer()`.

## Bewertung einzelner Stellen
- **Auth-Modell (Z.45):** `require_course_login` mit `$cm` ist korrekt; Sichtbarkeit der Aktivitaet wird mitgeprueft. **Bewertung:** A.
- **Status-Gate (Z.72–81):** Nutzer sehen den Bestaetigungstext nur, wenn sie tatsaechlich auf Liste oder Warteliste stehen — sinnvolle Eingrenzung, kein Leak fremder Daten (`$USER->id`-basiert). **Bewertung:** A.
- **Exception-als-Text-Fallback (Z.84–86):** Bei Render-Fehler wird `$e->getMessage()` direkt als Seiteninhalt ausgegeben. Funktional unkritisch (kein roher HTML-Inject ueber Platzhalter erwartet), aber eine Exception-Message als nutzersichtbarer Inhalt ist stilistisch fragwuerdig (Information-Disclosure-Geschmaeckle). **Bewertung:** B-Detail, nicht sicherheitskritisch.
- **Reihenfolge Header vs. Status (Z.63 vs. Z.77):** `$OUTPUT->header()` wird vor der `notbooked`-Verzweigung ausgegeben; der Fehlerpfad gibt danach sauber Error-Text + Continue-Button + Footer aus und `return`t — korrekt geschlossen.

## Bewertungs-Resümee
Schlankes, korrekt abgesichertes Entry-Skript: Pflichtparameter, `require_course_login` inkl. CM, Status-gebundene Anzeige des eigenen Buchungsstatus, Platzhalter-Rendering mit Catch. Einzige Schwaeche ist das Ausgeben der Exception-Message als Seiteninhalt (Z.85) — kosmetisch/Info-Disclosure-Geschmaeckle, nicht funktional gefaehrlich. Klassen-Score **A / P3**.
