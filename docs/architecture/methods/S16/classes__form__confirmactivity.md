# confirmactivity — Methoden-Doku
**Datei:** `classes/form/confirmactivity.php` · **LOC:** 92 · **Subsystem:** S16 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`confirmactivity extends \moodleform` ist eine klassische (nicht dynamische) Moodle-Form zur Auswahl der Quelle einer Aktivitaetsbestaetigung: entweder ein Badge oder eine kursinterne Aktivitaet mit Completion-Tracking. Sie haelt keine Persistenz; sie liefert nur die Auswahl (`whichtype`, `certid`, `activity`) an den aufrufenden Confirm-Flow (`confirmactivity.php`-Skript, S21). Kollaborateure: `mod_booking\utils\db` (Badge-Liste), `get_fast_modinfo` (Aktivitaetsliste). Customdata: `$this->_customdata['course']`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Form: Radio-Gruppe `whichtype` (1 = Badge, 0 = Activity); Badge-Select `certid` aus `db::getbadges($course->id)`, disabled wenn Activity gewaehlt; Activity-Select aus allen `get_fast_modinfo($course)->get_cms()`, gefiltert auf `uservisible == 1` und `get_course_module_record()->completion > 0`, disabled wenn Badge gewaehlt; Submit-/Cancel-Buttongruppe. **Seiteneffekte:** instanziiert `db` und liest Badges (DB); `get_fast_modinfo` (gecacht); mutiert `$this->_form`. **Bewertung:** C — `global $CFG, $DB` werden deklariert aber nicht direkt genutzt (`$DB` ungenutzt); `get_course_module_record()` wird pro CM aufgerufen, was bei Modinfo-Cache-Treffer guenstig ist, andernfalls aber pro Modul DB-Reads ausloest (potenzieller N+1 in selten gecachten Pfaden); Sprachstrings ueber Komponente `booking` (Legacy-Frankenstring `booking` statt `mod_booking`).

### `public function validation($data, $files)` — public
- **Zweck:** Reicht ausschliesslich die Eltern-Validierung durch. **Seiteneffekte:** keine. **Rueckgabe:** `array` (Parent-Fehler). **Bewertung:** C — reiner Pass-Through-Stub ohne eigene Pruefung (z. B. keine Konsistenzpruefung whichtype↔certid/activity).

## Bewertungs-Resümee
Schmale Legacy-`moodleform` mit zwei Methoden; funktional ausreichend zum Einsammeln der Badge-/Activity-Auswahl. Schwaechen: ungenutzte Globals, Pass-Through-`validation` ohne Eigenlogik, Legacy-Komponentenname `booking` in Sprachstrings und ein theoretischer per-CM-DB-Zugriff bei kaltem Modinfo-Cache. Kein scharfer Bug, aber wenig defensiv. Klassen-Score **C / P3**.
