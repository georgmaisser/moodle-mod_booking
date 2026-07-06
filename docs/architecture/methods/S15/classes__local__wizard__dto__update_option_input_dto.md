# update_option_input_dto — Methoden-Doku
**Datei:** `classes/local/wizard/dto/update_option_input_dto.php` · **LOC:** 78 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`update_option_input_dto` ist ein unveraenderliches Value Object fuer Eingabe-Payload des Use-Case `booking.update_option`. Es kapselt ein assoziatives Array `$fields` hinter privatem Konstruktor und Factory `from_array()`. Anders als die create-DTOs erzwingt `from_array()` hier KEIN Pflichtfeld — bewusst, da ein Update beliebige Teilmengen von Feldern setzen koennen muss (Validierung der Zielfelder erfolgt nachgelagert im Skill/Mutation-Service). Keine Persistenz, kein DB-Zugriff. `declare(strict_types=1)`.

## Methoden

### `private function __construct(array $fields)` — private
- **Zweck:** Speichert das Feld-Array in `$this->fields`. **Seiteneffekte:** keine. **Bewertung:** A.

### `public static function from_array(array $data): self` — public static
- **Zweck:** Factory ohne Validierung — erzeugt das DTO aus jedem beliebigen Eingabe-Array. **Seiteneffekte:** keine. **Rueckgabe:** neue `self`-Instanz. **Bewertung:** A — fehlende Pflichtfeld-Pruefung ist hier korrekt (Partial-Update-Semantik); die eigentliche Feld-/Optionid-Validierung liegt im update_option_skill und Mutation-Validator.

### `public function to_array(): array` — public
- **Zweck:** Gibt das komplette Feld-Array unveraendert zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array<string,mixed>`. **Bewertung:** A.

### `public function get(string $key, mixed $default = null): mixed` — public
- **Zweck:** Liefert einen Einzelwert per Schluessel, sonst den Default. **Seiteneffekte:** keine. **Rueckgabe:** der Feldwert oder `$default`. **Bewertung:** A — `array_key_exists()` statt `isset()`.

### Triviale Properties
Eine private Property `$fields` (`array<string,mixed>`, Z.38).

## Bewertungs-Resümee
Minimaler Immutable-DTO; bewusst validierungsfrei wegen Partial-Update-Semantik. Identisches Grundmuster zu den create-DTOs (Duplikation, funktional unkritisch). Klassen-Score **A / P3**.
