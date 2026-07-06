# pricecategories_form — Methoden-Doku
**Datei:** `classes/form/pricecategories_form.php` · **LOC:** 264 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`pricecategories_form` ist die Repeat-Elements-`dynamic_form` zur globalen Pflege der Preiskategorien (`booking_pricecategories`). Sie rendert pro Kategorie eine Zeile (verstecktes `pricecategoryid`/`ordernum`, Identifier, Name, Defaultwert, Sortierung, Disable-Checkbox), validiert Eindeutigkeit/Pflichtfelder und delegiert die eigentliche Persistenz vollstaendig an `mod_booking\local\pricecategories_handler`. Persistenz: keine direkte (DB-Zugriff via Handler); die Form ist reine Praesentations-/Validierungsschicht. Kollaborateure: `pricecategories_handler` (Load + `process_pricecategories_form`), `context_system`, Seite `pricecategories.php`. Kontext ist immer `context_system`, Zugriff erfordert `moodle/site:config`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Repeat-Element-Set fuer Preiskategorien auf; Anzahl Repeats = `max(count(geladene Kategorien), 1)`. **Seiteneffekte:** Instanziiert `pricecategories_handler` und ruft `get_pricecategories()` (DB-Read); `repeat_elements(...)`; friert `pricecategoryidentifier[0]` und `disablepricecategory[0]` ein (die Default-Kategorie bleibt unveraenderbar); `add_action_buttons(true)`. **Bewertung:** B — `global $DB` deklariert aber ungenutzt; sonst sauberes, idiomatisches Repeat-Setup mit per-Feld `disabledif`-Kopplung an die Disable-Checkbox.

### `public function validation($data, $files)` — public
- **Zweck:** Validiert jede Kategoriezeile: Identifier nicht leer; Zeile 0 muss `default` sein, alle anderen duerfen nicht `default` sein; Name nicht leer; Defaultwert max. 2 Nachkommastellen; Sortierung nicht leer; Identifier/Name/Sortierung jeweils eindeutig. **Seiteneffekte:** keine (DB nicht benutzt, `global $DB` ungenutzt). **Rueckgabe:** `array` der Feld→Fehlertext-Map. **Bewertung:** B — Logik korrekt und gut abgedeckt, aber der `pricecategoryname`-Leer-Check steht doppelt (Z.148-150 und Z.152-154); der zweite ist toter Code. Die Dezimalstellenpruefung haengt an `is_float($data[...])`, was bei aus Strings geparsten Werten je nach Cast-Pfad nicht immer greift.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Speichert die eingegebenen Kategorien. **Seiteneffekte:** `get_data()`, `new pricecategories_handler()->process_pricecategories_form($data)` (diff-basiertes Insert/Update/Disable in der DB). **Rueckgabe:** `stdClass` — ruft `get_data()` ein zweites Mal fuer den Rueckgabewert auf. **Bewertung:** B — funktional korrekt; `global $DB` ungenutzt; der doppelte `get_data()`-Aufruf ist unnoetig (Ergebnis haette wiederverwendet werden koennen).

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt das Formular mit den bestehenden Kategorien fuer die Anzeige. **Seiteneffekte:** Lazy-Load via `pricecategories_handler::get_pricecategories()` (DB-Read); baut indexierte Form-Keys (`pricecategoryid[i]` etc.) und ruft `set_data()`. **Bewertung:** B — speichert `pricecatsortorder` redundant auch unter `pricecategoryordernum` (Kommentar erklaert das Altlast-Feld); ansonsten klar.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_system::instance()` (globale Einstellung). **Seiteneffekte:** keine. **Rueckgabe:** `context`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz. **Seiteneffekte:** `require_capability('moodle/site:config', context_system::instance())`. **Bewertung:** A — korrekt restriktiv (nur Site-Admins).

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Rueckkehr-URL. **Seiteneffekte:** keine. **Rueckgabe:** `new moodle_url('/mod/booking/pricecategories.php')`. **Bewertung:** A.

### `private function value_is_duplicated(array $array, $value): bool` — private
- **Zweck:** Hilfsfunktion: prueft ob `$value` im Array mehr als einmal vorkommt. **Seiteneffekte:** keine (`array_count_values`). **Rueckgabe:** `bool`. **Bewertung:** A — kompakt und korrekt; wird pro Zeile fuer drei Felder aufgerufen, O(n) je Aufruf, bei realistischen Kategoriezahlen unkritisch.

## Bewertungs-Resümee
Solide, idiomatische Repeat-Elements-Form mit sauberer Trennung (Persistenz im Handler) und korrektem Site-Level-Access-Gate. Schwaechen sind kosmetisch: doppelter Name-Leer-Check (toter Code), drei ungenutzte `global $DB`-Deklarationen, doppelter `get_data()`-Aufruf. Funktional unkritisch. Klassen-Score **B / P3**.
