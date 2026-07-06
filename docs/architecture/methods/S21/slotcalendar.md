# slotcalendar — Methoden-Doku
**Datei:** `slotcalendar.php` · **LOC:** 87 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse, keine Funktionen). Rendert die Slot-Kalender-Report-Seite fuer eine Booking-Option. Die Seite liefert nur den HTML-Container samt `data-*`-Attributen (Labels + cmid/optionid); die eigentlichen Slot-Daten werden client-seitig vom AMD-Modul `mod_booking/slotCalendarReport` ueber den Webservice `mod_booking_get_booked_slots` nachgeladen. Kollaborateure: `config.php`/`lib.php`, `get_course_and_cm_from_cmid`, `require_course_login`, `context_module`, `mod_booking\local\slotbooking\slot_dto`, `$PAGE`/`$OUTPUT`/`html_writer`.

## Ablauf (Request-/Permission-Flow)
- **Eingangsparameter:** `id` (cmid, `required_param PARAM_INT`), `optionid` (`required_param PARAM_INT`).
- **Aufloesung + Auth:** `get_course_and_cm_from_cmid($id, 'booking')` → `require_course_login($course, false, $cm)` → `context_module::instance($cm->id)` → `require_capability('mod/booking:view', $context)`. Reine Lesefreigabe, da die Seite nur Anzeige ist.
- **Daten-Gate:** `$hasbookedslots = !empty(slot_dto::build_report_slots($optionid, $id)['slots'])` — entscheidet nur, ob ueberhaupt ein Container gerendert wird; bei leerer Slotliste wird stattdessen eine `none`-Infobox ausgegeben.
- **Page-Setup:** `$PAGE->set_url/set_title/set_heading/set_context`; laedt das AMD-Modul `mod_booking/slotCalendarReport::init` mit der Container-id.
- **Rendering:** Header, Heading, Zurueck-Link auf `report.php`; bei vorhandenen Slots ein `div#booking-slot-calendar-report` mit allen Lokalisierungs-Labels als `data-*`-Attributen plus zwei leeren Regions-Divs (`slot-calendar-picker`, `slot-calendar-students`); Footer.
- **Seiteneffekte:** Nur Ausgabe (echo) und Page-Konfiguration; keine Schreibzugriffe.
- **Bewertung:** B — sauberer, schlanker Anzeige-Endpoint mit korrektem Capability-Gate. **Perf-Hinweis (P3, Z.41):** `slot_dto::build_report_slots(...)` baut den vollstaendigen Report-Datensatz auf, nur um daraus ein Boolean (`hasbookedslots`) abzuleiten — die Daten werden anschliessend ohnehin clientseitig per WS erneut geladen. Ein leichtgewichtiger `exists`-Check (z.B. `$DB->record_exists`) waere ausreichend und spart den Doppel-Aufbau.

## Bewertungs-Resümee
Duenner, korrekt abgesicherter View-Endpoint, der die schwere Arbeit an JS+Webservice delegiert. Einzige Schwaeche ist der unnoetig teure Voll-Build der Report-Slots zur reinen Leer-Pruefung. Klassen-Score **B / P3**.
