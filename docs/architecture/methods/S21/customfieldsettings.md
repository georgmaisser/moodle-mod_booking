# customfieldsettings — Methoden-Doku
**Datei:** `customfieldsettings.php` · **LOC:** 49 · **Subsystem:** S21 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Rendert die Admin-Seite zur Konfiguration der Booking-Custom-Fields ueber das DynamicForm-aehnliche `\mod_booking\form\customfield`. Eingebunden als externe Admin-Seite (`admin_externalpage_setup('modbookingcustomfield', ...)`), Pagelayout `report`. Kollaborateure: `adminlib.php`, `\mod_booking\form\customfield`, globale `$PAGE`/`$OUTPUT`/`$SITE`.

## Methoden
Kein Klassen-/Funktions-Body — reiner Request-Flow auf Top-Level.

### Request-/Permission-Flow
- **Z.25–26:** Bootstrap `config.php` + `adminlib.php`.
- **Z.29:** `require_login(0, false)` — kein Guest-Autologin; die eigentliche Berechtigungspruefung erfolgt durch `admin_externalpage_setup` (Site-Admin-Kontext der externen Admin-Seite).
- **Z.31–36:** PageURL + `admin_externalpage_setup('modbookingcustomfield', '', null, '', ['pagelayout' => 'report'])`; setzt Titel aus `$SITE->shortname` + `customfieldconfigure`-String.
- **Z.38–43:** Instanziiert `\mod_booking\form\customfield`; bei Cancel → `redirect($pageurl)`; bei gueltigem `get_data()` → ebenfalls `redirect($pageurl)` (Speicher-Logik liegt im Form selbst, hier nur PRG-Pattern).
- **Z.44–49:** Header, Heading, `$mform->display()`, Footer.
- **Seiteneffekte:** HTTP-Output (HTML-Seite) oder Redirect; keine direkten DB-Schreibvorgaenge im Script (delegiert ans Form).
- **Bewertung:** A — kanonischer Admin-Settings-Entry-Point, sauberes Post/Redirect/Get, Autorisierung korrekt ueber die externe Admin-Seite gekapselt.

## Bewertungs-Resümee
Minimaler, idiomatischer Admin-Seiten-Entry-Point. Berechtigung via `admin_externalpage_setup`, Persistenz delegiert ans Form. Keine funktionalen Auffaelligkeiten. Klassen-Score **A / P3**.
