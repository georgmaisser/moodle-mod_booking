# slot_event_placeholders — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_event_placeholders.php` · **LOC:** 75 · **Subsystem:** S14 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_event_placeholders` ist ein gemeinsamer Renderer fuer die Booking-Rule-Placeholders der Slot-Events (`bookinganswer_slotbooked` / `_slotcancelled` / `_slotmoved`). Diese Events tragen Slot-Fragmente in ihrer `other`-Payload unter unterschiedlichen Keys (`bookedslots`, `oldslots`, `newslots`). Die einzelnen Placeholder-Klassen delegieren hierher, damit das Parsen und Formatieren an einer Stelle liegt. Persistenz: keine. Kollaborateure: `mod_booking\option\dates_handler::prettify_optiondates_start_end`, `current_language()`, die konkreten Placeholder-Klassen als Aufrufer.

## Methoden

### `public static function render(string $rulejson, array $keys): string` — public static
- **Zweck:** Dekodiert das Rule-JSON, sucht die erste nicht-leere Slot-Liste unter den uebergebenen `other`-Keys (in Reihenfolge) und rendert sie als `"start - end; start - end"`-Kette. **Seiteneffekte:** keine I/O; `json_decode` + Formatierung via `dates_handler::prettify_optiondates_start_end(..., current_language())`. **Rueckgabe:** formatierte, mit `'; '` verbundene Slot-Liste oder `''`, wenn nichts Gueltiges vorliegt. **Bewertung:** A — durchgaengig defensiv: prueft `datafromevent->other`, akzeptiert sowohl Objekt- als auch Array-Slot-Eintraege (`is_object`-Verzweigung), und filtert unsinnige Ranges via `$start <= 0 || $end <= $start`. Das `break` nach dem ersten Treffer-Key implementiert sauber die "first non-empty wins"-Semantik.

## Bewertungs-Resümee
Sehr kleine, einzweckige Renderer-Klasse mit robuster Payload-Behandlung (gemischte Objekt-/Array-Formen, Range-Plausibilisierung). Keine erkennbaren Funktions- oder Performance-Probleme. Klassen-Score **A / P3**.
