# edit_optiontemplates — Methoden-Doku
**Datei:** `edit_optiontemplates.php` · **LOC:** 130 · **Subsystem:** S21 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point zum Bearbeiten/Speichern eines Buchungsoptions-Templates (Records in `booking_options` mit `bookingid = 0`). Rendert das `option_form` und persistiert das Ergebnis ueber `booking_option::update`. Templates dienen als Vorlage fuer spaeter erstellte Optionen. Kollaborateure: `option_form`, `singleton_service`, `booking_option::update`, `file_save_draft_area_files` (FileManager + Bild), `$DB`, Capability-API.

## Ablauf (Request-/Permission-Flow)

### Parameter + Page-/Login-Setup (Z.31-44)
- **Zweck:** Liest `id` (cmid, required), `optionid` (required), `sesskey` (optional). Setzt PAGE-URL/Redirect-URL, laedt jquery-ui-css, ermittelt `[$course, $cm]` via `get_course_and_cm_from_cmid($id)` und ruft `require_course_login`. **Seiteneffekte:** DB-Reads, Login-Gate, `$PAGE`-Mutation.

### Instanz-/Context-/Capability-Pruefung (Z.46-56)
- **Zweck:** Holt `booking`-Singleton, `context_module`, prueft `mod/booking:manageoptiontemplates`. **Seiteneffekte:** wirft `invalid_parameter_exception` / `moodle_exception('badcontext')` / `moodle_exception('nopermissions')`. **Bewertung:** B — korrektes Gate.

### Form-Init + Default-Load (Z.58-68)
- **Zweck:** Baut `customdata` (`bookingid => 0`!) und instanziiert `option_form`. Laedt den Template-Record `booking_options WHERE bookingid=0 AND id=optionid`, ergaenzt `optionid/bookingid/bookingname/id`. **Seiteneffekte:** `$DB->get_record`; wirft `moodle_exception` falls Template fehlt. **Bewertung:** B.

### Save-Branch (Z.70-121)
- **Zweck:** Bei Cancel -> Redirect. Bei validierten Daten (`$mform->get_data()`): erneute Gate-Pruefung (`confirm_sesskey()` UND (`updatebooking` ODER `addeditownoption`)), Default fuer `limitanswers`, dann **`$nbooking = booking_option::update($fromform, $context)`** und anschliessend Speichern der Draft-Areas `myfilemanageroption` (max 50) und `bookingoptionimage` (max 1) gegen die zurueckgegebene id. Abschliessend Redirect-Steuerung je nach `addastemplate`/`submittandaddnew`. **Seiteneffekte:** Persistiert Option (DB-Writes ueber `booking_option::update`), Dateispeicherung, Redirect. **Bewertung:** D — **Bug (P1):** Die Datei hat keinen Namespace und importiert `booking_option` nicht (`use`-Block Z.25-26 enthaelt nur `option_form` und `singleton_service`). `booking_option::update` (Z.83) resolved daher zu `\booking_option`, eine nicht existierende Klasse -> fataler `Error: Class "booking_option" not found`, sobald der Save-Zweig betreten wird. Vergleichbare Root-Skripte (`link.php` Z.25, `report.php` Z.28) deklarieren `use mod_booking\booking_option;`; hier fehlt es. Der gesamte Speicherpfad fuer Options-Templates ist damit gebrochen (Daten werden nicht gespeichert).

### Display-Branch (Z.122-131)
- **Zweck:** Bei GET/ungesendet: Title/Heading, Header, `set_data($defaultvalues)`, `$mform->display()`, Footer. **Seiteneffekte:** Echo HTML. **Bewertung:** A.

## Bewertungs-Resümee
Funktional schlanke Template-Edit-Seite mit korrektem Doppel-Gate (Capability + sesskey). Kritisch: der fehlende `use mod_booking\booking_option;` macht den Save-Pfad (Z.83) zu einem fatalen Fehler — Templates lassen sich nicht speichern. Bis zur Behebung ist die Seite effektiv read-only/kaputt. Klassen-Score **C / P1** (Score-Herabstufung gegenueber CLASS_INDEX-Hint C/P2 wegen des gebrochenen Save-Pfads).
