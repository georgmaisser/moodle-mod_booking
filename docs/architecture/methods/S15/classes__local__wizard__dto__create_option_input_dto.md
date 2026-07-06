# create_option_input_dto — Methoden-Doku
**Datei:** `classes/local/wizard/dto/create_option_input_dto.php` · **LOC:** 82 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`create_option_input_dto` ist ein unveraenderliches Value Object fuer validierten Eingabe-Payload des Use-Case `booking.create_option`. Es kapselt ein assoziatives Array `$fields` hinter privatem Konstruktor und Factory `from_array()`, die das Pflichtfeld `text` (Optionstitel) erzwingt. Struktur-identisch zu `create_entity_input_dto` (gleiche Felder/Methoden, abweichend nur in Pflichtfeld und Fehlertext). Keine Persistenz, kein DB-Zugriff — reiner Datentraeger zwischen Planner/Engine und den create-option-Skills/Services. `declare(strict_types=1)`.

## Methoden

### `private function __construct(array $fields)` — private
- **Zweck:** Speichert das Feld-Array in `$this->fields`. **Seiteneffekte:** keine. **Bewertung:** A — privat, Konstruktion ueber Factory erzwungen.

### `public static function from_array(array $data): self` — public static
- **Zweck:** Factory, die ein DTO aus rohem Eingabe-Array erzeugt und das Pflichtfeld `text` validiert. **Seiteneffekte:** wirft `\InvalidArgumentException`, wenn `$data['text']` leer/abwesend ist (`empty()`). **Rueckgabe:** neue `self`-Instanz. **Bewertung:** A — Fail-Fast-Validierung; `empty()` verwirft `text === '0'`, fuer einen Optionstitel praxis-irrelevant.

### `public function to_array(): array` — public
- **Zweck:** Gibt das komplette Feld-Array unveraendert zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array<string,mixed>`. **Bewertung:** A.

### `public function get(string $key, mixed $default = null): mixed` — public
- **Zweck:** Liefert einen Einzelwert per Schluessel, sonst den Default. **Seiteneffekte:** keine. **Rueckgabe:** der Feldwert oder `$default`. **Bewertung:** A — `array_key_exists()` statt `isset()`, gibt explizite `null`-Werte korrekt zurueck.

### Triviale Properties
Eine private Property `$fields` (`array<string,mixed>`, Z.38).

## Bewertungs-Resümee
Minimaler, korrekt gebauter Immutable-DTO mit Factory und einer Pflichtfeld-Pruefung; identisches Muster zu `create_entity_input_dto` (Code-Duplikation der drei DTOs koennte ein gemeinsames Trait/Basisklasse teilen, ist aber funktional unkritisch). Klassen-Score **A / P3**.
