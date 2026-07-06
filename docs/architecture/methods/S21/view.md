# view.php — Methoden-Doku
**Datei:** `view.php` · **LOC:** 224 · **Subsystem:** S21 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Haupt-Einstiegspunkt (kein Klassenkontext) des Plugins: zeigt die Tabelle aller Buchungsoptionen einer Instanz. Das Skript erzwingt Kurs-Login + `mod/booking:view`, triggert das `course_module_viewed`-Event, rendert optional eine Organizer-„business_card", Attachments und Tags, baut die eigentliche Optionsliste ueber das DTO/Renderer `mod_booking\output\view` und packt sie zusammen mit einem optionalen KI-Tab (`bookingextension_agent`) in eine Bootstrap-Tab-Komponente. Kollaborateure: `singleton_service` (booking/settings by cmid), `output\view`, `output\business_card`, `local\htmlcomponents`, `renderer`, `core_tag_tag`, `get_file_storage`, optional `core_plugin_manager` + `bookingextension_agent\local\wizard\aiready`. Reiner Render-Pfad mit einer Nebenwirkung: `course_module_viewed`-Event und `checkautocreate`.

## Request-/Permission-Flow
1. **Z.33–48 — Bootstrap + Params:** config.php + comment/lib.php; `require_login(0,false)` (kein Gast-Autologin); Params `id` (cmid, Pflicht), `optionid`, `download`, `whichview`; `get_course_and_cm_from_cmid`; `require_course_login`; Modul-Kontext.
2. **Z.50 — Capability:** `require_capability('mod/booking:view', $context)`.
3. **Z.54–77 — Legacy-Mail-Gate:** Fuer Site-Admins, wenn `uselegacymailtemplates` aktiv und `legacymailremovalacknowledged` leer (und nicht Behat): Seite bricht mit Warn-Notification + Settings-Link ab (`exit`). Erzwingt eine Migrations-Quittung.
4. **Z.86–87 — Instanz + Autocreate:** `singleton_service::get_instance_of_booking_by_cmid`; `$booking->checkautocreate()` (kann Optionen anlegen — siehe Bewertung).
5. **Z.91–96 — Event:** `course_module_viewed::create(...)` mit Course-Snapshot, `trigger()`.
6. **Z.98–110 — Page-Setup:** Settings by cmid, Title/URL/Navbar/Heading, pagelayout `incourse`, Body-Klasse.
7. **Z.116–126 — Organizer-Card:** Bei gesetztem `organizatorname` (als int interpretiert → userid) `new business_card(...)` → `render_business_card`.
8. **Z.128–160 — Attachments:** `get_area_files('myfilemanager', $bookingsettings->id)`; bei `count > 1` Links pro Datei mit Filesize > 0 (Pluginfile-URL).
9. **Z.162–166 — Tags:** bei `$CFG->usetags` `core_tag_tag::get_item_tags` + `tag_list`.
10. **Z.176–213 — Haupttabelle + Tabs:** `new view($cmid,$whichview,$optionid)` → `render_view`; Erkennung von `bookingextension_agent` via `core_plugin_manager` (try/catch); optionaler KI-Tab (`aiready->export_for_template` + Template `aiinstructions`); `htmlcomponents::render_bootstrap_earmarks($tabs, ...)`.
11. **Z.215–224 — Footer:** optionales Wunderbyte-Logo (sofern nicht `turnoffwunderbytelogo`), `$OUTPUT->footer()`.

## Bewertung einzelner Stellen
- **Z.87 — `checkautocreate()` im GET-View:** Ein nominell lesender Seitenaufruf kann ueber `checkautocreate` schreibend Optionen erzeugen. Seiteneffekt in einem reinen Anzeige-Pfad; bei haeufigen Views potenziell wiederholt evaluiert. Bewusst so etabliert, aber Geruch. **Bewertung:** C / P2.
- **Z.122 — `organizatorname` als userid-Cast:** `(int)$bookingsettings->organizatorname` — das Feld traegt semantisch einen Namen, wird hier aber als Userid interpretiert. Funktioniert nur, weil das Feld tatsaechlich eine Userid speichert; fragiles Naming. **Bewertung:** C / P3.
- **Z.179–191 — Agent-Extension-Probing:** Defensiv mit `class_exists` + try/catch um `core_plugin_manager`; faellt sauber auf „nicht installiert" zurueck. Robust. **Bewertung:** B.
- **Z.204 — `aiready((int)$context->id, $USER->id)`:** Korrekter int-Cast der Context-Property (vgl. strict_types-Erfahrung). **Bewertung:** A.
- **Z.119 — `' $organizerhtml'`:** Fuehrendes Leerzeichen in der Zuweisungszeile (kosmetischer Whitespace-Lapsus). **Bewertung:** — (kosmetisch).
- **Z.176–177 — Render-Aggregation:** Die eigentliche Schwergewichts-Logik liegt im `output\view` (separat als D/P1 bewertet); dieses Skript bleibt duenner Orchestrator. **Bewertung:** C / P2.

## Bewertungs-Resümee
Zentraler, gut strukturierter Einstiegspunkt mit korrekter Login-/Capability-Absicherung, sauberem optionalem Agent-Tab-Probing und ordentlichem Attachment-/Tag-Rendering. Hauptkritik: der schreibende `checkautocreate`-Seiteneffekt in einem View-GET und die als userid umgedeutete `organizatorname`-Property. Die Render-Schwere liegt in `output\view`, nicht hier. Klassen-Score **C / P2**.
