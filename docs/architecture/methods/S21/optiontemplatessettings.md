# optiontemplatessettings — Methoden-Doku
**Datei:** `optiontemplatessettings.php` · **LOC:** 94 · **Subsystem:** S21 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Listet alle Options-Templates (Booking-Options mit `bookingid = 0`) in einer `optiontemplatessettings_table` und behandelt zwei Aktionen: `copytotemplate` (aktuelle Option als Template anlegen) und `delete` (Template loeschen). Kollaborateure: `singleton_service` (Booking-Option), `optiontemplatessettings_table`, Core `$DB`/`$PAGE`/`$OUTPUT`, `context_module`.

## Request-/Permission-Flow
1. `require_once config.php` + `tablelib.php` + `adminlib.php`. Parameter: `id` (cmid, required), `optionid` (optional), `action` (optional, `PARAM_ALPHANUM`).
2. Modul-Kontext aus `id`; `get_course_and_cm_from_cmid`; `require_course_login($course, false, $cm)`; `activityheader->disable()`.
3. **Aktion `copytotemplate`** (Z.48-63): gated auf `action == 'copytotemplate'` **UND** `has_capability('mod/booking:manageoptiontemplates')` **UND** `confirm_sesskey()`. Laedt die Option, setzt urlparams, `apply_tags()`, `copytotemplate()`, dann `redirect` mit Erfolgsmeldung.
4. **Aktion `delete`** (Z.65-69): gated NUR auf `action === 'delete' && $optionid > 0`. Loescht direkt `$DB->delete_records('booking_options', ['id' => $optionid])`, entfernt `optionid` aus der URL, `redirect`.
5. Baut die Tabelle (`set_sql` ueber `{booking_options} bo` WHERE `bo.bookingid = 0`), `is_sortable = false`, URL/Title/Navbar, Header + Heading + `out(25, true)` + Footer.

## Bewertung der Logik
- **Bewertung:** D — die `copytotemplate`-Aktion ist mustergueltig abgesichert (Capability + sesskey), die **`delete`-Aktion dagegen gar nicht**: kein Capability-Check (nur `require_course_login`), kein `confirm_sesskey()`. Das ist zugleich eine fehlende Autorisierung und eine CSRF-Luecke auf einer destruktiven, datenverlierenden GET-Operation (siehe Findings).
- Zusaetzlich loescht der Delete-Pfad direkt per `$DB->delete_records('booking_options', ...)` statt ueber `booking_option::delete_booking_option()` — verwandte Datensaetze (z.B. `booking_optiondates`, Custom-Fields, Dateien) werden nicht aufgeraeumt. Bei Templates (`bookingid = 0`) ist die relationale Huelle kleiner, aber Orphans bleiben moeglich.
- Die SQL-Liste ist parametrisiert/fix (`bo.bookingid = 0`) — kein Injection-Risiko.

## Findings
- `optiontemplatessettings.php:65-69` — `delete`-Aktion ohne Capability-Pruefung und ohne `confirm_sesskey()`: jeder im Kurs eingeloggte User kann per praepariertem GET (`?id=..&action=delete&optionid=..`) ein Options-Template loeschen — fehlende Autorisierung + CSRF + Datenverlust (P1).
- `optiontemplatessettings.php:66` — direkter `$DB->delete_records('booking_options', ...)` umgeht `booking_option::delete_booking_option()`; verwandte Optiondate-/Custom-Field-/File-Records bleiben als Orphans zurueck (P3).

## Bewertungs-Resümee
Die Listenanzeige und der `copytotemplate`-Pfad sind solide, doch der ungesicherte `delete`-Pfad (keine Capability, kein sesskey, roher DB-Delete) ist eine echte Autorisierungs-/CSRF-/Datenverlust-Schwachstelle und dominiert die Bewertung. Klassen-Score **D / P1**.
