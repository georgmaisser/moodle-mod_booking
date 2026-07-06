# booking_answers — Methoden-Doku
**Datei:** `classes/reportbuilder/local/entities/booking_answers.php` · **LOC:** 281 · **Subsystem:** S17 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`booking_answers` ist eine Report-Builder-Entity (`extends core_reportbuilder\local\entities\base`) ueber `{booking_answers}` — die User-zu-Option-Pivot-Tabelle (Buchungen, Completions, Warteliste). Sie deklariert acht Spalten (completed, completeddate, timebooked, timemodified, timecreated, waitinglist, status, pricecategory) und sechs Filter (inkl. zwei Custom-`timestamp_years_past`-Filtern). Persistenz: keine — reine Spalten-/Filter-Definition; Werte werden via Callbacks formatiert. Kollaborateure: Core-`column`/`filter`/`format`-Helfer, `mod_booking\booking` (Presence-Status-Map), `timestamp_years_past`-Filter, `MOD_BOOKING_STATUSPARAM_*`-Konstanten aus `lib.php`.

## Methoden

### `protected function get_default_tables(): array` — protected
- **Zweck:** Nennt die genutzte Tabelle `booking_answers`. **Rueckgabe:** `['booking_answers']`. **Bewertung:** A.

### `protected function get_default_entity_title(): lang_string` — protected
- **Zweck:** Default-Titel der Entity. **Seiteneffekte:** `new lang_string('entitybookinganswer', 'mod_booking')`. **Bewertung:** A.

### `public function initialise(): base` — public
- **Zweck:** Registriert alle Spalten als Columns und alle Filter sowohl als Filter wie auch als Condition. **Seiteneffekte:** `add_column`/`add_filter`/`add_condition` in Schleifen. **Rueckgabe:** `$this`. **Bewertung:** A — Standard-Boilerplate; jeder Filter wird bewusst auch als Condition exponiert.

### `protected function get_all_columns(): array` — protected
- **Zweck:** Baut die acht Spaltendefinitionen mit Typ, Feld, Sortierbarkeit und Formatierungs-Callbacks. `completed` (Boolean→ja/nein), `completeddate`/`timebooked`/`timemodified`/`timecreated` (Timestamp→`format::userdate`), `waitinglist` (Integer-Status→Klartext per Switch ueber `MOD_BOOKING_STATUSPARAM_*`), `status` (Integer→`booking::get_array_of_possible_presence_statuses()`-Lookup), `pricecategory` (Text). **Seiteneffekte:** im `waitinglist`-Callback `require_once($CFG->dirroot.'/mod/booking/lib.php')` pro Zeile (lazy, idempotent); `booking::get_array_of_possible_presence_statuses()` im `status`-Callback pro Zeile. **Rueckgabe:** `column[]`. **Bewertung:** B — funktional korrekt; pro-Zeilen-`require_once` und der pro-Zeile aufgerufene Status-Array-Aufbau (`get_array_of_possible_presence_statuses`) sind milde Ineffizienzen (kein echtes N+1 gegen die DB, aber wiederholte Arbeit je Renderzeile). Der `waitinglist`-Callback castet `$value` nicht und faellt bei unbekanntem Status sauber auf `''` zurueck.

### `protected function get_all_filters(): array` — protected
- **Zweck:** Definiert sechs Filter: `completed` (boolean_select), `completeddate` (date), `timemodifiedyears` und `completeddateyears` (custom `timestamp_years_past`), `timebooked` (date) und `status` (number). **Seiteneffekte:** keine (nur Objektkonstruktion); `add_joins($this->get_joins())` je Filter. **Rueckgabe:** `filter[]`. **Bewertung:** A — die zwei Years-Past-Filter sind ein bewusster Workaround dafuer, dass `completeddate` nicht immer gesetzt ist (Kommentar im Code).

## Bewertungs-Resümee
Solide, deklarative Reporting-Entity. Funktional korrekt; der `status`-Callback ist NULL-tolerant und der `waitinglist`-Switch deckt alle Statusparam-Faelle ab. Einzige Schwaeche: pro-Renderzeile wiederholtes `require_once` und der Neuaufbau des Presence-Status-Arrays im Callback (kosmetische Ineffizienz, kein DB-N+1). Klassen-Score **B / P3**.
