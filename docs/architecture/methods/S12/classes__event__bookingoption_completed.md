# bookingoption_completed — Methoden-Doku
**Datei:** `classes/event/bookingoption_completed.php` · **LOC:** 95 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_completed` ist ein Moodle-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn eine Buchungsoption fuer einen User als „abgeschlossen" markiert wird (Trigger fuer Aktivitaetsabschluss/Zertifikate). Nicht eigen-DB-persistent; landet im Standard-Logstore. CRUD-Typ `c` (create), Edulevel `LEVEL_PARTICIPATING`, `objecttable` = `booking_answers`. Beschreibung unterscheidet Selbst- vs. Fremd-Abschluss (`userid` vs. `relateduserid`). Erzwingt `relateduserid` per `validate_data()`. Kollaborateure: Sprachstring `bookingoptioncompleted`, `\core\event\base`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='c'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`). **Seiteneffekte:** Mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — Standard-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('bookingoptioncompleted', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert die Beschreibung; unterscheidet ob der User sich selbst oder einen anderen User (`relateduserid`) als abgeschlossen markiert hat. **Seiteneffekte:** keine. **Rueckgabe:** string (hartkodiertes Englisch, interpoliert `userid`/`objectid`/`relateduserid`). **Bewertung:** B — nicht lokalisiert (festes Englisch, im Gegensatz zu `bookingoption_booked`, das `get_string` nutzt); im Fremd-Zweig ein doppeltes Leerzeichen „option with id  {…}" (kosmetisch). Funktional korrekt.

### `public function get_url()` — public
- **Zweck:** Ziel-URL zur Aktivitaetsansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url` auf `/mod/booking/view.php?id={$this->contextinstanceid}`. **Bewertung:** A — korrekt `contextinstanceid` (cmid).

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt gesetztes `relateduserid` (Pflicht fuer die Beschreibung). **Seiteneffekte:** `parent::validate_data()`; `throw \coding_exception` bei fehlendem `relateduserid`. **Rueckgabe:** void. **Bewertung:** A.

## Bewertungs-Resümee
Funktional korrektes Abschluss-Event mit cmid-URL, Selbst-/Fremd-Unterscheidung und Pflichtvalidierung. Einzige Schwaechen sind kosmetisch/konsistenzbezogen: hartkodierte (nicht lokalisierte) Beschreibung und ein doppeltes Leerzeichen im Fremd-Zweig. Keine funktionalen Befunde. Klassen-Score **B / P3**.
