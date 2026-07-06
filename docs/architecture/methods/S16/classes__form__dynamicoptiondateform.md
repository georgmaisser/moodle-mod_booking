# dynamicoptiondateform — Methoden-Doku
**Datei:** `classes/form/dynamicoptiondateform.php` · **LOC:** 202 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`dynamicoptiondateform` ist ein `core_form\dynamic_form` (Modal in `editoptions.php`), das fuer eine Buchungsoption eine Serie von Optionsterminen aus einem reoccurring-Datestring (z.B. „Mo, Mi 10:00-12:00") relativ zu einem gewaehlten Semester berechnet. Wichtig: die Form **persistiert keine Optiondates** — sie liefert die berechnete Datumsliste als JSON an das aufrufende JS zurueck, das sie clientseitig rendert. Kontext: `context_module` (per cmid), Capability `mod/booking:addeditownoption`. Kollaborateur: `mod_booking\option\dates_handler` (Mform-Elemente, Serien-Berechnung, Datestring-Validierung). Kein DB-Write in dieser Klasse.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut hidden `cmid`/`bookingid`/`optionid` und delegiert die eigentlichen Datums-Elemente an `dates_handler::add_optiondates_for_semesters_to_mform()`; beim Erstaufruf (kein `reoccurringdatestring` in ajaxformdata) werden bestehende Termine geladen. **Seiteneffekte:** Instanziiert `dates_handler($optionid, $bookingid)`. **Bewertung:** B — Identifier kommen direkt aus `_ajaxformdata` (kein optional_param-Fallback); bei fehlenden Keys koennte ein Undefined-Index entstehen, in der Praxis liefert das JS sie aber stets mit.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `mod/booking:addeditownoption` im Modul-Kontext. **Seiteneffekte:** `require_capability`. **Bewertung:** A — kontextscharfe Pruefung ueber `get_context_for_dynamic_submission()`.

### `public function process_dynamic_submission()` — public
- **Zweck:** Berechnet aus `chooseperiod` + `reoccurringdatestring` die Optiondate-Serie und gibt sie (plus cmid/optionid) zurueck. **Seiteneffekte:** keine DB-Writes; mehrere Guard-Returns (`false`) bei leerem String/Periode, bei Keyword `block` im String, oder bei ungueltigem Datestring; `dates_handler::get_optiondate_series(...)`. **Rueckgabe:** Array der Datumsserie oder `false`. **Bewertung:** B — defensive Vorpruefungen; der `block`-Sonderfall ist domaenenspezifisch und nur per Kommentar erklaert.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt leeres Default-Data. **Seiteneffekte:** `set_data(new stdClass())`. **Bewertung:** A — bewusst leer (Daten werden in `definition` ueber den dates_handler aufgebaut).

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Modul-Kontext per cmid, mit Fallback auf `optional_param('cmid')`. **Seiteneffekte:** `context_module::instance($cmid)`. **Rueckgabe:** `context_module`. **Bewertung:** B — `optional_param('cmid', '', PARAM_RAW)` mit Leerstring-Default ist ungewoehnlich (PARAM_INT waere passender), funktioniert aber.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Seiten-URL `/mod/booking/editoptions.php?id=<cmid>` (gleiche cmid-Fallback-Logik). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Validiert den reoccurring-Datestring. **Seiteneffekte:** keine. **Rueckgabe:** `$errors` (`reoccurringdatestringerror` bei Ungueltigkeit). **Bewertung:** A — delegiert an `dates_handler::reoccurring_datestring_is_correct`.

### `public function get_data()` — public
- **Zweck:** Override, das schlicht `parent::get_data()` zurueckgibt. **Seiteneffekte:** keine. **Rueckgabe:** Form-Daten. **Bewertung:** C — reiner Pass-through-Override ohne Mehrwert (toter Wrapper).

## Bewertungs-Resümee
Schlanke Berechnungs-/Vorschau-Form ohne eigene Persistenz; die schwere Logik liegt sauber im `dates_handler`. Korrekt kontextgescoped (Modul-Kontext + `addeditownoption`). Kleinkram: direkter `_ajaxformdata`-Zugriff ohne Guard, ein No-op `get_data()`-Override und `PARAM_RAW`-Fallback fuer eine cmid. Funktional unkritisch. Klassen-Score **B / P3**.
