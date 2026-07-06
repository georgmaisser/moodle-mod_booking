# bookinganswer_slotmoved — Methoden-Doku
**Datei:** `classes/event/bookinganswer_slotmoved.php` · **LOC:** 195 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_slotmoved` ist ein Moodle-Logevent (`\core\event\base`) fuer das Verschieben von Slot-Buchungen (Slotbooking, S14). Im Unterschied zu den slotbooked/slotcancelled-Events traegt es zwei Slot-Listen (`other['oldslots']`, `other['newslots']`) und enthaelt eigene Logik, um aus diesen Listen das tatsaechliche Delta (entfernte vs. hinzugefuegte Slots) zu berechnen und menschenlesbar zu formatieren. `edulevel = LEVEL_TEACHING` (im Gegensatz zu LEVEL_PARTICIPATING der anderen Slot-Events). Keine eigene Persistenz; Bezug ueber `objecttable = 'booking_answers'`/`objectid`. Kollaborateure: `userdate`, `get_string` (inkl. singular/plural-Stringwahl), `report.php`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'u'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Mutiert `$this->data`. **Bewertung:** A — abweichendes `edulevel` (teaching) gegenueber den anderen Slot-Events ist plausibel (Move ist eine Verwaltungsaktion).

### `public static function get_name()` — public static
- **Zweck:** Name ueber `get_string('slot_move_event_name', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Normalisiert alte/neue Slot-Listen, bildet Lookup-Maps `start:end`, berechnet `removed` (in alt, nicht in neu) und `added` (in neu, nicht in alt); fallback auf vollstaendige alte/neue Listen, wenn kein Delta bestimmbar. Baut `$a` (u.a. `oldslots`/`newslots` als formatierte Strings, `slotcount = max(count(removed), count(added))`, `reason`) und waehlt singular- oder plural-String. **Seiteneffekte:** keine (rein lesend). **Rueckgabe:** string. **Bewertung:** B — solide Delta-Heuristik; die `slotcount`-Plural-Entscheidung kann taeuschen, wenn removed/added unterschiedlich gross sind (max-Wahl), und der „kein Delta"-Fallback zeigt im Grenzfall (identische alt==neu) beide vollen Listen statt „keine Aenderung".

### `private function normalise_slots($slots): array` — private
- **Zweck:** Wandelt eine beliebige Payload in `array<{start:int,end:int}>` um; verwirft Nicht-Arrays, ungueltige Zeiten (`start <= 0` oder `end <= start`) und dedupliziert per `start:end`-Key. **Seiteneffekte:** keine. **Rueckgabe:** `array_values(...)` (neu indizierte Slot-Liste). **Bewertung:** A — robuste, defensive Eingabenormalisierung.

### `private function format_slot_list(array $slots): string` — private
- **Zweck:** Formatiert eine Slot-Liste als `userdate(start) - userdate(end)`-Paare, durch `; ` verbunden; bei leerer Liste `'-'`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A — nutzt korrekt `strftimedatetime` aus `langconfig`.

### `public function get_url()` — public
- **Zweck:** Log-URL `/mod/booking/report.php?id=<contextinstanceid>&optionid=<optionid>`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Verlangt gesetztes `relateduserid` und `data['other']['optionid']`. **Seiteneffekte:** wirft `coding_exception`. **Rueckgabe:** void. **Bewertung:** A — beachte: `oldslots`/`newslots` werden NICHT validiert, sind aber in `get_description` defensiv mit `?? []` abgesichert.

## Bewertungs-Resümee
Das reichhaltigste der Slot-Events: enthaelt echte Praesentationslogik (Delta-Berechnung, Pluralisierung, Zeitformatierung) mit guter Eingabe-Robustheit. Kleinere Schwaechen in der `slotcount`-Pluralwahl (max) und im „kein Delta"-Fallback. Keine funktionalen Defekte. Klassen-Score **B / P3**.
