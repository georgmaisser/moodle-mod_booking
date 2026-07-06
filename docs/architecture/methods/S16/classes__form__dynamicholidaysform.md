# dynamicholidaysform — Methoden-Doku
**Datei:** `classes/form/dynamicholidaysform.php` · **LOC:** 263 · **Subsystem:** S16 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`dynamicholidaysform` ist ein `core_form\dynamic_form` mit `repeat_elements`, das die globale Feiertagsliste (`booking_holidays`) per Insert/Update/Delete-Diff direkt aus dem Formular heraus pflegt. Feiertage beeinflussen die Generierung von Optiondate-Serien (ausgesparte Tage). Kontext: `context_system`, Capability `moodle/site:config`. Persistenz: Tabelle `booking_holidays` (Felder `id`, `name`, `startdate`, `enddate`). Kollaborateure: `$DB` direkt, `cache_helper::purge_by_event('setbacksemesters')`. Die Klasse hat keinen Instanzzustand; alles fliesst durch den Form-Lifecycle.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Kontext = `context_system`. **Seiteneffekte:** keine. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `moodle/site:config`. **Seiteneffekte:** `require_capability`. **Bewertung:** A.

### `protected function transform_data_to_holidays_array(stdClass $data): array` — protected
- **Zweck:** Wandelt die parallelen Form-Arrays (`holidayid`/`holidaystart`/`holidayend`/`holidayendactive`/`holidayname`) in eine Liste von Holiday-stdClass-Objekten; ohne aktive Enddatum-Checkbox wird `enddate = startdate` gesetzt. **Seiteneffekte:** keine (rein). **Rueckgabe:** Array von Holiday-Objekten. **Bewertung:** B — klare Transformation; trimmt den Namen, defensiv per `is_array`-Guard.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt bestehende Feiertage (sortiert nach `startdate DESC`) in die parallelen Form-Arrays. **Seiteneffekte:** `$DB->get_records_sql("SELECT * FROM {booking_holidays} ORDER BY startdate DESC")`, `set_data($data)`. **Bewertung:** B — leichte Inkonsistenz im Leer-Zweig (`$data->id = []` statt `holidayid`, und `holidayname` wird dort nicht initialisiert), aber im Normalfall korrekt befuellt.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert den Diff: vorhandene id → Update, neue (id 0) → Insert, in der Form fehlende vorhandene ids → Delete; danach Cache-Purge. **Seiteneffekte:** `$DB->get_records('booking_holidays')`, je Zeile `insert_record`/`update_record`, je entferntem Eintrag `delete_records`, `cache_helper::purge_by_event('setbacksemesters')`. **Rueckgabe:** `get_data()`. **Bewertung:** C — funktional korrekt, aber per-Zeile-DB-Writes (akzeptabel bei kleiner Feiertagsliste). Die Insert-Erkennung `!in_array($holiday->id, array_keys($existingholidays))` ist umstaendlich (neue Zeilen tragen die Default-id 0); `insert_record` ignoriert die `id`-Property, daher unkritisch. Kein Transaktions-Wrapper um den Multi-Step-Diff.

### `public function definition(): void` — public
- **Zweck:** Baut die wiederholbaren Feiertags-Bloecke (hidden id, Start-Date-Selector, End-aktiv-Checkbox mit hideif, End-Date-Selector, Name, Loeschen-Button, hr) und initialisiert die Anzahl aus dem DB-Count. **Seiteneffekte:** `$DB->count_records('booking_holidays')`, `repeat_elements(...)`. **Bewertung:** B — solider Repeat-Aufbau; der doppelte `$numberofholidaystoshow`-Assignment (Z.218-219) ist kosmetischer Schrott.

### `public function validation($data, $files): array` — public
- **Zweck:** Prueft je Zeile mit aktivem Enddatum, dass `startdate <= enddate`. **Seiteneffekte:** keine. **Rueckgabe:** `$errors` (pro Index). **Bewertung:** B — korrekte Index-bezogene Fehlerschluessel.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Seiten-URL `/mod/booking/semesters.php`. **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Direkt-CRUD-Repeat-Form fuer eine kleine globale Stammdatentabelle. Korrekt und gut lesbar; Schwaechen sind kleinteilig: inkonsistenter Leer-Zweig in `set_data_for_dynamic_submission`, doppelter Assignment in `definition`, kein Transaktions-Wrapper um den Insert/Update/Delete-Diff. Funktional unkritisch. Klassen-Score **C / P3**.
