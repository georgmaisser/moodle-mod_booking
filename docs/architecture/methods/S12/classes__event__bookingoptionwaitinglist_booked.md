# bookingoptionwaitinglist_booked — Methoden-Doku
**Datei:** `classes/event/bookingoptionwaitinglist_booked.php` · **LOC:** 99 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoptionwaitinglist_booked` ist ein Moodle-Standard-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn ein User auf die Warteliste einer Buchungsoption gesetzt wurde (`booking_answers`-Record). Im Unterschied zu den Optiondate-Events kennt es einen `relateduserid` (der gebuchte User, ggf. != ausloesender `userid`) und waehlt anhand dessen einen von zwei Beschreibungsstrings (Selbst- vs. Fremdbuchung); zusaetzlich erzwingt `validate_data()` die Praesenz von `relateduserid`. Persistenz: keine eigene; `objecttable = booking_answers`. Kollaborateure: `\core\event\base`, `get_string()`, `\moodle_url`, Trigger-Aufrufer im Waitinglist-Buchungspfad.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten: `crud = 'c'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — Edulevel PARTICIPATING korrekt fuer Teilnehmer-Buchung.

### `public static function get_name()` — public static
- **Zweck:** Uebersetzter Anzeigename (`bookingoptionwaitinglistbooked`). **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Waehlt anhand `userid != relateduserid` zwischen Fremdbuchungs- (`...otheruser...`) und Selbstbuchungs-String (`...sameuser...`) und fuettert beide mit `userid`/`relateduserid`/`objectid`. **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A — sauber uebersetzbar (anders als die Optiondate-Events) und Fremd-/Selbstbuchung differenziert.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Instanz-Ansicht (`view.php?id=<contextinstanceid>`). **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Ruft `parent::validate_data()` und erzwingt, dass `relateduserid` gesetzt ist, sonst `coding_exception`. **Seiteneffekte:** ggf. Exception. **Rueckgabe:** void. **Bewertung:** B — die Pruefung nutzt den Magic-Property-Zugriff `$this->relateduserid`, waehrend `get_description()` direkt `$this->data['relateduserid']` liest; beide Wege funktionieren ueber das Core-Event-Framework, die Inkonsistenz ist aber Stilbruch.

## Bewertungs-Resümee
Gut gebautes Event mit korrekter Validierung und uebersetzbarer, Selbst-/Fremdbuchung unterscheidender Beschreibung. Einzige Kleinigkeit ist der gemischte Zugriff (`$this->relateduserid` vs. `$this->data['relateduserid']`). Klassen-Score **B / P3**.
