# bulk_update_options_input_dto — Methoden-Doku
**Datei:** `classes/local/wizard/dto/bulk_update_options_input_dto.php` · **LOC:** 78 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`bulk_update_options_input_dto` ist ein unveraenderliches Value Object, das validierten (bzw. roh durchgereichten) Input fuer den Use-Case `booking.bulk_update_options` traegt. Es haelt ein einziges privates assoziatives Feld-Array und bietet nur Lese-/Konstruktionszugriff. Persistenz: keine; reines In-Memory-DTO im wizard/AI-Pfad. Der Konstruktor ist privat, Instanzen entstehen ausschliesslich ueber die `from_array()`-Factory. Kollaborateure: `option_mutation_service`/`bulk_update_options_skill` (Erzeuger/Konsumenten).

## Methoden

### `private function __construct(array $fields)` — private
- **Zweck:** Speichert das uebergebene Feld-Array in `$this->fields`. Privat, um Konstruktion auf die Factory zu beschraenken.
- **Seiteneffekte:** Keine.
- **Bewertung:** A.

### `public static function from_array(array $data): self` — public static
- **Zweck:** Factory: erzeugt ein DTO aus einem rohen Input-Array.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** `self`.
- **Bewertung:** B — anders als die Schwester-DTOs (`create_option_input_dto`/`create_entity_input_dto`, die ein Pflichtfeld in `from_array` validieren) fuehrt diese Factory **keinerlei Validierung** durch und reicht jedes Array durch. Das ist fuer Bulk-Update bewusst (welche Felder gesetzt werden, ist offen), verschiebt aber die gesamte Pflichtfeld-/Typ-Pruefung auf den nachgelagerten Skill/Service. Kein Bug, nur Konsistenz-Hinweis.

### `public function to_array(): array` — public
- **Zweck:** Gibt alle Felder als assoziatives Array zurueck.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** array (Kopie-by-value des Feld-Arrays).
- **Bewertung:** A.

### `public function get(string $key, mixed $default = null): mixed` — public
- **Zweck:** Liefert einen Einzelwert per Key, sonst den Default. Nutzt `array_key_exists`, unterscheidet also korrekt zwischen „Key fehlt" und „Wert ist null".
- **Seiteneffekte:** Keine.
- **Rueckgabe:** mixed.
- **Bewertung:** A — saubere `array_key_exists`-Semantik statt `??`.

## Bewertungs-Resümee
Sauberes, unveraenderliches DTO mit Factory-Pattern und korrekter `array_key_exists`-Defaultsemantik. Einziger Diskussionspunkt: `from_array()` validiert (anders als verwandte DTOs derselben Familie) nichts und ist damit ein reiner Werte-Container — bewusst gewaehlt fuer den offenen Bulk-Update-Feldsatz. Funktional unkritisch. Klassen-Score **A / P3**.
