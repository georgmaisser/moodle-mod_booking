# availabilityconditions — Methoden-Doku
**Datei:** `availabilityconditions.php` · **LOC:** 169 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Admin-Einstiegspunkt (KEINE Klasse). Rendert das „Availability conditions dashboard" als Admin-Externalpage und verarbeitet das State-Formular fuer skip-/freeze-bare Verfuegbarkeitsbedingungen. Es ist zugleich Anzeige- und Speicher-Endpoint: GET rendert eine HTML-Tabelle (je Bedingung ein State-`select` plus optionaler Settings-Link), POST (`save=1` + sesskey) persistiert die gewaehlten States als JSON in der Plugin-Config `booking/availabilityconditionsettings`. Kollaborateure: `bo_info::get_skippable_conditions()` (Bedingungs-Katalog), `condition_state_helper` (State-Konstanten + Lese-Helfer `get_condition_state`), Core `admin_externalpage_setup`/`$OUTPUT`/`html_writer`, Plugin-Config via `get_config`/`set_config`. Persistenz: Plugin-Config-Tabelle (`booking`-Component), keine eigene DB-Tabelle.

## Ablauf (Request/Permission-Flow)

### Bootstrapping & Zugriffsschutz (Z.28–47)
- **Zweck:** `config.php` + `adminlib.php` laden, `require_login(0, false)` (kein Gastlogin), Kontext = `context_system`, dann `admin_externalpage_setup('modbookingavailabilityconditions', ..., ['pagelayout' => 'report'])`. **Seiteneffekte:** `admin_externalpage_setup` erzwingt die fuer diese Externalpage konfigurierte Capability (Admin-Tree-Gate) und initialisiert `$PAGE`. **Bewertung:** A — Standard-Admin-Gate; Berechtigung an die Externalpage-Registrierung delegiert.

### POST-Handler / State-Save (Z.51–66)
- **Zweck:** Bei `optional_param('save')` + `confirm_sesskey()` die per `optional_param_array('state', [], PARAM_INT)` uebermittelten States einlesen, auf die erlaubten Werte `STATE_FREEZE` / `STATE_SKIP_AND_FREEZE` filtern und als `[$conditionid => ['skipstate' => $state]]` sammeln. **Seiteneffekte:** `set_config('availabilityconditionsettings', json_encode($savedstates), 'booking')`; danach `redirect($pageurl, get_string('changessaved'), ..., NOTIFY_SUCCESS)`. **Bewertung:** B — sesskey-CSRF-Schutz + Whitelist-Validierung der States sind korrekt. Bedingungs-IDs werden NICHT gegen den Katalog (`get_skippable_conditions`) validiert, bevor sie als Config-Key gespeichert werden — ein Admin koennte beliebige numerische Keys einschleusen; da nur Admins via Gate hierherkommen und der Reader nur bekannte IDs ausliest, ist die Auswirkung gering (Datenmuell in der JSON-Config). `STATE_INACTIVE` wird bewusst weggefiltert, sodass Zuruecksetzen auf Default = Eintrag-Entfernen ist.

### Legacy-Hinweis (Z.85–94)
- **Zweck:** Eine Info-Notification anzeigen, wenn weder `availabilityconditionsettings` noch `availabilityconditionstates` gesetzt sind, aber alte Configs (`skipableconditions` / `enrollinkskipconditions`) noch Werte tragen — signalisiert einen nicht migrierten Legacy-Zustand. **Seiteneffekte:** vier `get_config`-Lesezugriffe + ggf. `$OUTPUT->notification`. **Bewertung:** B — sinnvoller Migrations-Hinweis; reine Anzeige.

### Tabellen-Rendering (Z.68–168)
- **Zweck:** `bo_info::get_skippable_conditions()` holen, numerisch `ksort`-en und je Bedingung eine Zeile erzeugen: Name (`s()`-escaped), ein State-`html_writer::select` (vorbelegt aus `$statehelper->get_condition_state($conditionid)`) und eine Settings-Zelle, die per fest verdrahteter `$conditionsettingsanchors`-Map auf den passenden Anker der Plugin-Settings-Seite (`/admin/settings.php?section=modsettingbooking#admin-<key>`) verlinkt oder `-` zeigt. **Seiteneffekte:** direktes `echo` von Header/Form/Tabelle/Footer; `$statehelper->get_condition_state` pro Zeile (Config-Lesen pro Aufruf moeglich — siehe Bewertung). **Bewertung:** C — Logik und HTML-Aufbau inline vermischt (kein Renderer/Template), schwer testbar. `get_condition_state` wird pro Bedingung erneut aufgerufen; falls dieser Helfer die Config bei jedem Call frisch parst, ist das eine kleine N-fach-Wiederholung ueber typischerweise wenige Bedingungen (unkritisch). Der `$conditionsettingsanchors`-Hardcode bedingt manuelle Pflege bei neuen Bedingungen (Kommentar weist explizit darauf hin).

## Bewertungs-Resümee
Funktional korrekter, ausreichend abgesicherter Admin-Endpoint (Admin-Gate + sesskey + State-Whitelist). Hauptschwaechen sind die fehlende Validierung der Bedingungs-IDs gegen den Katalog vor dem Persistieren und das inline gemischte HTML-/Logik-Rendering ohne Renderer/Template. Keine Daten-/Sicherheitskritik. Klassen-Score **C / P3**.
