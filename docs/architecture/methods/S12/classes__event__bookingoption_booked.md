# bookingoption_booked — Methoden-Doku
**Datei:** `classes/event/bookingoption_booked.php` · **LOC:** 99 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_booked` ist ein Moodle-Event (`\core\event\base`-Subklasse) fuer den Vorgang „Buchungsoption gebucht". Es ist nicht DB-persistent im eigenen Sinn, sondern wird ueber das Standard-Logstore abgelegt. CRUD-Typ `c` (create), Edulevel `LEVEL_PARTICIPATING`, `objecttable` = `booking_answers`. Es unterscheidet in der Beschreibung, ob ein User sich selbst oder einen anderen User gebucht hat (`userid` vs. `relateduserid`) und erzwingt das Vorhandensein von `relateduserid` per `validate_data()`. Kollaborateure: Sprachstrings (`bookingoptionbooked*`), `\core\event\base`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='c'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`). **Seiteneffekte:** Mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — Standard-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('bookingoptionbooked', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine lokalisierte Beschreibung, unterscheidet Selbst- vs. Fremdbuchung anhand `userid != relateduserid`. **Seiteneffekte:** `get_string('bookingoptionbookedotheruserdesc' | 'bookingoptionbookedsameuserdesc', ...)`. **Rueckgabe:** string. **Bewertung:** A — saubere, lokalisierte Verzweigung; uebergibt strukturierte Platzhalterdaten.

### `public function get_url()` — public
- **Zweck:** Ziel-URL zur Aktivitaetsansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url` auf `/mod/booking/view.php?id={$this->contextinstanceid}`. **Bewertung:** A — nutzt korrekt `contextinstanceid` (cmid).

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt, dass `relateduserid` gesetzt ist (Pflicht fuer die Beschreibung). **Seiteneffekte:** `parent::validate_data()`; `throw \coding_exception` bei fehlendem `relateduserid`. **Rueckgabe:** void. **Bewertung:** A — Defensive Validierung; greift nur im Debug-Modus.

## Bewertungs-Resümee
Sauberes, lokalisiertes Buchungs-Event mit korrekter cmid-URL und Pflichtvalidierung von `relateduserid`. Keine funktionalen Maengel. Klassen-Score **B / P3** (B als Event-Standard, keine P-Befunde).
