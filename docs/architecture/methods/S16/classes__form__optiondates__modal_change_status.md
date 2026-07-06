# modal_change_status — Methoden-Doku
**Datei:** `classes/form/optiondates/modal_change_status.php` · **LOC:** 283 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`modal_change_status` ist eine `core_form\dynamic_form` zum Setzen des **Anwesenheits-Status** (presence) gebuchter Nutzer — pro Optiondate (Session) oder pro Option, gesteuert ueber `scope`. Strukturell ist sie das Schwester-Formular zu `modal_change_notes`: identisches Feld-/Scope-/ID-Muster, nur dass statt eines Notes-Textareas ein `status`-Select (Werte aus `booking::get_possible_presences(true)`) gerendert wird und scope-abhaengig `optiondate_answer::add_or_update_status()` (Optiondate) bzw. `booking_option::changepresencestatus()` (Option) aufgerufen wird. Zugriff: `mod/booking:managebookedusers`. Kollaborateure: `optiondate_answer`, `booking::get_possible_presences`, `singleton_service`, `cache_helper`.

## Methoden

### `public function definition()` — public
- **Zweck:** Legt versteckte Felder (`cmid`, `optionid`, `scope`, `checkedids`, `id`) an und rendert — nur bei nicht-leerem `checkedids` — ein `status`-Select (Default 0) aus `booking::get_possible_presences(true)`, sonst eine „norowsselected"-Warnung. **Seiteneffekte:** mutiert `$this->_form`; liest Presence-Liste. **Bewertung:** B — sauberes Pattern, analog zu `modal_change_notes`.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Erzwingt `mod/booking:managebookedusers`. **Seiteneffekte:** `require_capability`. **Bewertung:** A.

### `public function process_dynamic_submission()` — public
- **Zweck:** Normalisiert Daten (status/optionid/checkedids-Fallbacks), filtert leere IDs, und schreibt scope-abhaengig: im `optiondate`-Scope je `optionid-optiondateid-userid` ein `optiondate_answer::add_or_update_status`; im `option`-Scope werden `checkedids` als Answer-IDs aufgeloest, die userids gesammelt und `booking_option::changepresencestatus($selectedusers, $status)` aufgerufen. Abschliessend `cache_helper::purge_by_event('setbackbookedusertable')`. **Seiteneffekte:** N DB-Writes (optiondate-Scope), Settings-/Answers-Lookups, Cache-Purge; `global $DB` ungenutzt. **Rueckgabe:** `$data`. **Bewertung:** C — gleiche Schwaechen wie das Notes-Pendant: `!is_int((int) $x)` ist ein wirkungsloser Guard (int-Cast ist stets `is_int`), `explode('-')`/List-Destructuring ungeschuetzt; `empty($data->status)` zwingt jeden Falsy-Wert auf 0 — wenn `0` ein gueltiger Status („kein Status") ist, deckungsgleich, andernfalls problematisch.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Castet `_ajaxformdata` zu Objekt und setzt es als Defaults. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** context_module per cmid (Ajaxdata/`optional_param`), sonst context_system. **Bewertung:** B.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Baut die `report2.php`-URL je nach Scope-IDs (optionid+optiondateid / nur optionid / nur cmid / leer). **Seiteneffekte:** `optional_param`-Aufrufe. **Bewertung:** B — wie im Notes-Pendant: `$optiondateid` nur im `optiondate`-Scope initialisiert, im finalen `!empty($optiondateid)` sonst potenziell undefiniert (Warning-Risiko).

### `public function validation($data, $files)` — public
- **Zweck:** Keine — leeres Fehler-Array. **Bewertung:** B — Select ist serverseitig durch die Optionsliste begrenzt.

### `public function get_data()` — public
- **Zweck:** Reiner Pass-Through auf `parent::get_data()`. **Bewertung:** C — ueberfluessiger Override.

## Bewertungs-Resümee
Funktional korrektes Presence-Update, das `modal_change_notes` spiegelt — inklusive derselben Schwaechen: No-op-`is_int`-Guard, ungeschuetztes `explode('-')`, potenziell undefiniertes `$optiondateid` in der URL-Bildung und ein leerer `get_data()`-Override. Capability-Gate und Cache-Purge sind vorhanden und korrekt; Optiondate-Scope skaliert linear in Einzelwrites. Die starke Duplizierung zu `modal_change_notes` legt eine gemeinsame Basisklasse nahe. Klassen-Score **C / P2**.
