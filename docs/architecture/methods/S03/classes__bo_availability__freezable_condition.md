# freezable_condition — Methoden-Doku
**Datei:** `classes/bo_availability/freezable_condition.php` · **LOC:** 51 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`freezable_condition` ist ein reines Opt-in-Interface (kein Zustand, keine Persistenz). Eine Availability-Condition implementiert es, um dem `condition_visibility_manager` die Namen aller MoodleQuickForm-Elemente zu deklarieren, die sie zum Options-Form hinzufuegt — statt diese Liste in einem zentralen Switch im Manager zu pflegen. Das erste Element der Rueckgabe dient als Anker, an dem die Freeze-/Skip-Warnmeldung eingefuegt wird. Kollaborateure: `condition_visibility_manager` (einziger Konsument), die implementierenden Condition-Klassen.

## Methoden

### `public function get_condition_form_elements(): array` — public (interface)
- **Zweck:** Liefert eine geordnete Liste aller mform-Elementnamen, die diese Condition dem Options-Form hinzufuegt; das erste Element ist der Anker fuer die Warn-Einfuegung. **Seiteneffekte:** keine (Vertrag). **Rueckgabe:** `string[]` — Elementnamen, auch bedingt gerenderte (der Manager prueft `elementExists()` vor jeder Aktion, sodass fehlende Namen still uebersprungen werden). **Bewertung:** A — klarer, minimaler Vertrag mit dokumentierter Anker-Semantik.

## Bewertungs-Resümee
Schlankes, gut dokumentiertes Marker-/Vertrags-Interface, das eine zentrale Switch-Anweisung im Visibility-Manager ersetzt und damit die Erweiterbarkeit verbessert. Keine Logik, kein Zustand, keine Risiken. Klassen-Score **A / P3**.
