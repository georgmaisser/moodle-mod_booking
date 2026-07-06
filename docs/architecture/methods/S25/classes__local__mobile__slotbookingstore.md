# slotbookingstore — Methoden-Doku
**Datei:** `classes/local/mobile/slotbookingstore.php` · **LOC:** 193 · **Subsystem:** S25 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S25_mobile.md)

## Klassenueberblick
`slotbookingstore` ist ein Cache-Store fuer die Slot-Auswahl zwischen Prepage und finaler Buchung. Es kapselt denselben MUC-Cache wie `customformstore` (`mod_booking/customformuserdata`), aber unter eigenem Key `<userid>_<optionid>_slotbooking` (Get/Set/Delete). Zusaetzlich parst es die gecachten Auswahl-Strings: `slot_selection` (komma-separierte `start:end`-Unix-Ranges) in Timestamp-Tupel und `slot_teacher_selection` (JSON `slotkey => [teacherids]`) in eine bereinigte Map. Persistenz: ausschliesslich Cache. Kollaborateur: `cache`; im Konstruktor `global $USER`. Diese Klasse ist — anders als ihre Mobile-Geschwister — sauber dokumentiert und defensiv geschrieben.

## Methoden

### `public function __construct(int $userid, int $optionid)` — public
- **Zweck:** Bindet Store an `userid`+`optionid`; normalisiert `userid <= 0` auf den eingeloggten `$USER->id`, damit Lese-/Schreibzugriffe in Prepage-Flows (die userid 0 = „aktueller User" senden) denselben Cachekey treffen. **Seiteneffekte:** `global $USER`, `cache::make('mod_booking','customformuserdata')`. **Bewertung:** A — die userid-Normalisierung ist ein bewusster, kommentierter Fix gegen Key-Mismatch.

### `public function get_slotbooking_data()` — public
- **Zweck:** Liest die gecachte Slot-Auswahl. **Seiteneffekte:** `cache->get`. **Rueckgabe:** object oder false. **Bewertung:** A.

### `public function set_slotbooking_data(object $data): void` — public
- **Zweck:** Persistiert die Slot-Auswahl. **Seiteneffekte:** `cache->set`. **Bewertung:** A.

### `public function delete_slotbooking_data(): void` — public
- **Zweck:** Loescht den Cache-Eintrag (nach Buchung/Abbruch). **Seiteneffekte:** `cache->delete`. **Bewertung:** A.

### `public function get_selected_range($data): array` — public
- **Zweck:** Bequemlichkeits-Wrapper, der nur den ersten geparsten Range zurueckgibt. **Seiteneffekte:** keine (delegiert an `get_selected_ranges`). **Rueckgabe:** `[start, end]` bzw. `[0, 0]` falls leer. **Bewertung:** A.

### `public function get_selected_ranges($data): array` — public
- **Zweck:** Parst `slot_selection` (object- oder array-Form) in eine Liste von `[start, end]`-Unix-Tupeln; akzeptiert komma-separierte `start:end`-Eintraege. **Seiteneffekte:** keine. **Rueckgabe:** `array<int, [int,int]>`. **Bewertung:** A — defensiv: trimmt/filtert leere Eintraege, ueberspringt Eintraege ohne `:`, validiert `start>0 && end>0 && end>start` (verwirft also auch Null-Dauer/invertierte Ranges); `explode(':', $entry, 2)` limitiert sauber.

### `public function get_selected_teachers_by_slot($data): array` — public
- **Zweck:** Dekodiert `slot_teacher_selection` (JSON-String) in eine Map `slotkey => int[]` bereinigter Teacher-IDs. **Seiteneffekte:** keine. **Rueckgabe:** `array<string, int[]>`. **Bewertung:** A — robust: `json_decode(...,true)` mit `is_array`-Guard, verwirft Eintraege ohne `:` im Slotkey oder nicht-Array-Werte, dedupliziert und filtert IDs ≤ 0 (`array_unique`/`array_filter`/`intval`).

### Triviale Properties
Vier geschuetzte Properties (`userid`, `optionid`, `cachekey`, `cache`, Z.33–43) als interne Werthalter.

## Bewertungs-Resümee
Sauber geschriebener, defensiver Cache-Store mit klarer Verantwortung. Alle Parser-Methoden validieren Eingaben gruendlich (Range-Plausibilitaet, JSON-Typchecks, ID-Bereinigung) und sind seiteneffektfrei abseits des Cache-Zugriffs. Die userid-Normalisierung im Konstruktor adressiert einen realen Key-Mismatch-Fehler. Keine funktionalen Maengel gefunden. Klassen-Score **B / P3**.
