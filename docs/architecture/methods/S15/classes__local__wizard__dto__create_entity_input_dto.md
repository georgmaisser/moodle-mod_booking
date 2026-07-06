# create_entity_input_dto — Methoden-Doku
**Datei:** `classes/local/wizard/dto/create_entity_input_dto.php` · **LOC:** 82 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`create_entity_input_dto` ist ein unveraenderliches Value Object, das validierten Eingabe-Payload fuer den Use-Case `entities.create_entity` traegt. Es kapselt ein einzelnes assoziatives Array `$fields` hinter einem privaten Konstruktor und einer Factory `from_array()`, die das Pflichtfeld `name` erzwingt. Keine Persistenz, kein DB-Zugriff, keine externen Kollaborateure — reiner Datentraeger zwischen Planner/Engine und dem Entity-Erzeugungsservice. `declare(strict_types=1)`.

## Methoden

### `private function __construct(array $fields)` — private
- **Zweck:** Speichert das uebergebene Feld-Array in `$this->fields`. **Seiteneffekte:** keine. **Bewertung:** A — bewusst privat, erzwingt Konstruktion ueber die Factory.

### `public static function from_array(array $data): self` — public static
- **Zweck:** Factory, die ein DTO aus rohem Eingabe-Array erzeugt und das Pflichtfeld `name` validiert. **Seiteneffekte:** wirft `\InvalidArgumentException`, wenn `$data['name']` leer/abwesend ist (`empty()`). **Rueckgabe:** neue `self`-Instanz. **Bewertung:** A — klare Fail-Fast-Validierung; `empty()` verwirft auch `name === '0'`, was fuer einen Entity-Namen praktisch irrelevant ist.

### `public function to_array(): array` — public
- **Zweck:** Gibt das komplette Feld-Array unveraendert zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array<string,mixed>`. **Bewertung:** A.

### `public function get(string $key, mixed $default = null): mixed` — public
- **Zweck:** Liefert einen Einzelwert per Schluessel, sonst den Default. **Seiteneffekte:** keine. **Rueckgabe:** der Feldwert oder `$default`. **Bewertung:** A — nutzt `array_key_exists()` statt `isset()`, gibt also auch explizit gesetzte `null`-Werte korrekt zurueck.

### Triviale Properties
Eine private Property `$fields` (`array<string,mixed>`, Z.38) als reiner Werte-Halter.

## Bewertungs-Resümee
Minimaler, korrekt gebauter Immutable-DTO mit Factory-Pattern und einer sinnvollen Pflichtfeld-Pruefung. Keine funktionalen Schwaechen. Klassen-Score **A / P3**.
