# bookingoption_bookedviaautoenrol — Methoden-Doku
**Datei:** `classes/event/bookingoption_bookedviaautoenrol.php` · **LOC:** 96 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_bookedviaautoenrol` ist ein Moodle-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn eine Buchung automatisch durch Auto-Enrolment entsteht. Nicht eigen-DB-persistent; landet im Standard-Logstore. CRUD-Typ `c` (create), Edulevel `LEVEL_PARTICIPATING`, `objecttable` = `booking_answers`. Strukturell nahezu identisch zu `bookingoption_booked`, jedoch ohne Selbst-/Fremd-Verzweigung in der Beschreibung. Erzwingt `relateduserid` per `validate_data()`. Kollaborateure: Sprachstring `bookingoptionbookedviaautoenroldesc`, `\core\event\base`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='c'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`). **Seiteneffekte:** Mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — Standard-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('bookingoptionbookedviaautoenrol', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine lokalisierte Beschreibung mit `userid`, `relateduserid`, `objectid` als Platzhaltern. **Seiteneffekte:** `get_string('bookingoptionbookedviaautoenroldesc', 'mod_booking', $data)`. **Rueckgabe:** string. **Bewertung:** A — einfach, lokalisiert.

### `public function get_url()` — public
- **Zweck:** Ziel-URL zur Aktivitaetsansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url` auf `/mod/booking/view.php?id={$this->contextinstanceid}`. **Bewertung:** A — korrekt `contextinstanceid` (cmid).

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt gesetztes `relateduserid`. **Seiteneffekte:** `parent::validate_data()`; `throw \coding_exception` bei fehlendem `relateduserid`. **Rueckgabe:** void. **Bewertung:** A.

## Bewertungs-Resümee
Standard-Event fuer Auto-Enrolment-Buchungen, sauber lokalisiert und validiert; faktisch eine vereinfachte Variante von `bookingoption_booked` (Duplikation der Init-/URL-/Validate-Boilerplate ueber die Event-Familie, aber unkritisch). Keine funktionalen Maengel. Klassen-Score **B / P3**.
