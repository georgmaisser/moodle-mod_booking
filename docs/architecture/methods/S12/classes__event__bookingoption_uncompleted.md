# bookingoption_uncompleted — Methoden-Doku
**Datei:** `classes/event/bookingoption_uncompleted.php` · **LOC:** 97 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_uncompleted` ist ein Moodle-Standard-Event (`\core\event\base`), das ausgeloest wird, wenn der Abschluss-Status (completion) einer Buchungsantwort zurueckgenommen wird. Es ist das Gegenstueck zu einem completed-Event und bezieht sich auf `booking_answers` (`objecttable='booking_answers'`, `crud='c'`, `edulevel=LEVEL_PARTICIPATING`). Es unterscheidet zwischen Selbst-Aktion und Fremd-Aktion ueber `relateduserid` und erzwingt deshalb als einziges der Event-Geschwister eine eigene `validate_data()`. Kollaborateure: `get_string` (Sprachpaket `mod_booking`), `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='c'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`). **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Uebersetzter Anzeigename. **Seiteneffekte:** `get_string('bookingoptionuncompleted', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Klartext-Beschreibung mit Verzweigung: unterscheidet, ob ein Nutzer den Abschluss fuer sich selbst oder fuer einen anderen (`relateduserid`) zurueckgenommen hat. **Seiteneffekte:** keine; liest `$this->userid`, `$this->objectid`, `$this->data['relateduserid']`. **Rueckgabe:** string. **Bewertung:** B — funktional korrekt; greift `relateduserid` einmal ueber `$this->data[...]` (statt magisch `$this->relateduserid` wie in `validate_data`), kleine Inkonsistenz im Zugriffsstil. Der Doppel-Whitespace in `id  {$this->objectid}` ist ein kosmetischer Tippfehler.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Instanz-Ansicht. **Seiteneffekte:** `moodle_url('/mod/booking/view.php', ['id' => contextinstanceid])`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt, dass `relateduserid` gesetzt ist (Pflichtfeld fuer die Selbst-/Fremd-Verzweigung). **Seiteneffekte:** ruft `parent::validate_data()`; wirft `\coding_exception` bei fehlendem `relateduserid`. **Rueckgabe:** void. **Bewertung:** A — prueft ueber den magischen Property-Zugriff `$this->relateduserid`, konsistent mit dem Moodle-Event-Contract.

## Bewertungs-Resümee
Solides Event mit sinnvoller Pflichtfeld-Validierung; das einzige der fuenf Geschwister mit eigener `validate_data`. Schoenheitsmaengel: Doppel-Leerzeichen in der Beschreibung und gemischter Zugriffsstil auf `relateduserid` (`$this->data[...]` vs. magisch). Keine funktionalen Befunde. Klassen-Score **B / P3**.
