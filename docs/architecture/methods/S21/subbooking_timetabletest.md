# subbooking_timetabletest — Methoden-Doku
**Datei:** `subbooking_timetabletest.php` · **LOC:** 182 · **Subsystem:** S21 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse, keine Funktionen) — eine reine **Test-/Demo-Seite** (Header: "Testfile for timetable", 2022, Thomas Winkler). Sie rendert ein **hartkodiertes JSON-Template** ueber `mod_booking/subbooking/timeslottable`, ohne jeglichen Bezug zu echten Daten (keine optionid, keine DB). Dient offensichtlich nur der visuellen Entwicklung des Timeslot-Templates. Kollaborateure: `config.php`, `\context_system`, `require_login`, `$PAGE`/`$OUTPUT`, `json_decode`.

## Ablauf (Request-/Permission-Flow)
- **Eingangsparameter:** `del` (`optional_param PARAM_INT`, Default 0) — **wird eingelesen, aber nirgends verwendet** (toter Parameter, Z.27).
- **Auth-Gate:** `$context = \context_system::instance()`, `$PAGE->set_context($context)`, `require_login()`. **Es gibt keine Capability-Pruefung** — die Seite ist fuer jeden eingeloggten Nutzer erreichbar.
- **Page-Setup:** `set_url`, `set_title/set_heading` auf die fixe Konstante `"timetabletest"`.
- **Daten:** Ein ~140-zeiliges, fest im Quelltext eingebettetes JSON (Tage 29.11-01.12, Slots 08:00-22:00, drei Lokationen "Halle1-3" mit `free`/`price`/`currency`/`area`/`component`/`itemid`) wird via `json_decode($json)` zu einem `stdClass` und an das Mustache-Template `mod_booking/subbooking/timeslottable` uebergeben.
- **Rendering:** Header → `render_from_template(...)` → Footer.
- **Seiteneffekte:** Nur Ausgabe; keine DB-/Schreibzugriffe.
- **Bewertung:** D — funktionsfaehig als Demo, aber im Produktiv-Webroot ist eine ungeschuetzte (nur `require_login`) Test-Seite mit Beispieldaten unnoetiger Ballast. Der `del`-Parameter ist toter Code (suggeriert eine nie implementierte Delete-Aktion). Kein direkter Sicherheits-/Datenschaden, aber Code-Hygiene/Angriffsflaeche.

## Bewertungs-Resümee
Leftover-Entwicklungs-/Demo-Seite mit hartkodiertem Template-JSON und ohne Capability-Gate, die produktiv ausgeliefert wird. Sollte aus dem Release entfernt oder hinter ein Debug-/Dev-Gate gestellt werden; der `del`-Parameter ist toter Code. Klassen-Score **D / P2**.
