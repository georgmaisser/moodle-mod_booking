# dynamicsemestersform — Methoden-Doku
**Datei:** `classes/form/dynamicsemestersform.php` · **LOC:** 292 · **Subsystem:** S16 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`dynamicsemestersform` ist ein `core_form\dynamic_form` mit `repeat_elements`, das die globale Semesterliste (`booking_semesters`) per Insert/Update/Delete-Diff direkt aus dem Formular pflegt. Anders als Holidays wird hier ueber den **`identifier`** (nicht die id) gematcht. Semester sind Bezugsrahmen fuer Optiondate-Serien. Kontext: `context_system`, Capability `moodle/site:config`. Persistenz: Tabelle `booking_semesters` (`identifier`, `name`, `startdate`, `enddate`). Kollaborateure: `$DB` direkt, `cache_helper::purge_by_event('setbacksemesters')`. Kein Instanzzustand.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Kontext = `context_system`. **Seiteneffekte:** keine. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `moodle/site:config`. **Seiteneffekte:** `require_capability`. **Bewertung:** A.

### `protected function transform_data_to_semester_array(stdClass $semesterdata): array` — protected
- **Zweck:** Wandelt die parallelen Form-Arrays in Semester-stdClass-Objekte (identifier/name getrimmt, start/end). **Seiteneffekte:** wirft `moodle_exception` bei leerem identifier. **Rueckgabe:** Array von Semester-Objekten. **Bewertung:** C — die Exception nutzt einen **hartkodierten englischen String** statt eines Lang-Keys (Z.91); der All-or-nothing `is_array`-Guard ueber alle vier Arrays bedeutet: fehlt eines komplett, wird stillschweigend ein leeres Array geliefert (potenziell unbeabsichtigtes Komplett-Delete im Process-Schritt).

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt bestehende Semester (sortiert `startdate DESC`) in die Form-Arrays. **Seiteneffekte:** `$DB->get_records_sql("SELECT * FROM {booking_semesters} ORDER BY startdate DESC")`, `set_data($data)`. **Bewertung:** A — konsistent befuellt (auch der Leer-Zweig initialisiert alle vier Arrays).

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert den Diff per identifier: neuer identifier → Insert, vorhandener → Update (id via Re-Lookup), in der Form fehlende vorhandene identifier → Delete; danach Cache-Purge. **Seiteneffekte:** `$DB->get_records('booking_semesters')`, je Update ein zusaetzlicher `$DB->get_record('booking_semesters', ['identifier' => ...])`, je Zeile insert/update, je entferntem `delete_records`, `cache_helper::purge_by_event('setbacksemesters')`. **Rueckgabe:** `get_data()`. **Bewertung:** C — funktional korrekt, aber pro Update ein zusaetzlicher Einzel-Lookup (Z.156), obwohl die existierenden Records bereits in `$existingsemesters` geladen sind (vermeidbarer Extra-Query); kein Transaktions-Wrapper um den Multi-Step-Diff. Bei kleiner Semesterzahl tolerierbar.

### `public function definition(): void` — public
- **Zweck:** Baut die wiederholbaren Semester-Bloecke (Label, identifier-Text, name-Text, Start-/End-Date-Selector, Loeschen-Button) und initialisiert die Anzahl aus dem DB-Count. **Seiteneffekte:** `$DB->get_records('booking_semesters')`, `repeat_elements(...)`. **Bewertung:** B — solide; auskommentierte Helpbutton-Optionen sind bewusst (Moodle-4.0-Repeat-Bug, dokumentiert).

### `public function validation($data, $files): array` — public
- **Zweck:** Prueft je Zeile: nicht-leerer identifier/name, **keine Duplikate** (per `array_count_values`), und `start < end`. **Seiteneffekte:** keine. **Rueckgabe:** `$errors` (pro Index). **Bewertung:** A — gruendliche, index-bezogene Validierung inkl. Duplikat-Erkennung, die genau den identifier-basierten Persistenz-Mechanismus absichert.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Seiten-URL `/mod/booking/semesters.php`. **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Direkt-CRUD-Repeat-Form fuer die globale Semestertabelle, identifier-basiert gematcht und durch eine starke Duplikat-Validierung abgesichert. Schwaechen: hartkodierte englische Exception statt Lang-String, redundanter Einzel-Lookup je Update, kein Transaktions-Wrapper, und ein All-or-nothing-Array-Guard, der bei unvollstaendigem Input still ein leeres Array (= potenzielles Massen-Delete) liefert. Funktional weitgehend solide. Klassen-Score **C / P3**.
