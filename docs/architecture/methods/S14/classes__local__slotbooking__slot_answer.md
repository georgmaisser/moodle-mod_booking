# slot_answer — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_answer.php` · **LOC:** 75 · **Subsystem:** S14 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_answer` ist ein schlanker statischer Adapter (DTO-Helfer), der die Slot-Daten eines `booking_answers`-Records aus dessen `json`-Spalte liest und schreibt. Es gibt keine Instanz und keinen State — beide Methoden operieren direkt auf dem uebergebenen Answer-Objekt. Die Konvention: das Answer-JSON ist ein assoziatives Array, und alle Slot-spezifischen Daten leben unter dem Top-Level-Key `slot` (z. B. `['slot' => ['slots' => [...]]]`), damit andere Konsumenten ihre eigenen Top-Level-Keys im selben JSON unangetastet behalten. Persistenz: keine eigene — die Klasse mutiert nur `$answer->json` (das Persistieren obliegt dem Aufrufer). Kollaborateure: `slot_change_policy`/`slot_move_store`-Pfade und die Slot-Buchungs-Webservices als Leser/Schreiber.

## Methoden

### `public static function get_slot_data(object $answer): ?array` — public static
- **Zweck:** Liest das Slot-Subarray (`$payload['slot']`) aus dem Answer-JSON. **Seiteneffekte:** keine (reines `json_decode`). **Rueckgabe:** das Slot-Array bei Vorhandensein, sonst `null`. **Bewertung:** A — defensiv: prueft `empty`/`is_string` auf `json`, validiert Decode-Resultat als Array und das Vorhandensein/den Typ des `slot`-Keys, bevor zurueckgegeben wird.

### `public static function set_slot_data(object $answer, array $slotdata): void` — public static
- **Zweck:** Merge-Set: vereint `$slotdata` mit dem bestehenden `slot`-Subarray (via `array_replace_recursive`) und schreibt das Gesamt-JSON zurueck nach `$answer->json`, ohne andere Top-Level-Keys zu verlieren. **Seiteneffekte:** mutiert `$answer->json` (Objekt-Referenzsemantik); persistiert nicht selbst. **Rueckgabe:** void. **Bewertung:** A — robuster Roundtrip (gueltiges Bestands-JSON wird uebernommen, ungueltiges/leeres faellt auf `[]` zurueck); `array_replace_recursive` ist fuer das verschachtelte Mergen passend. Einzige latente Falle ist das rekursive Merge-Verhalten bei numerisch indizierten Listen (Index-weises Verschmelzen statt Ersetzen) — fuer den hier ueblichen `slots`-Vollersatz aber unkritisch, da der Aufrufer die komplette Liste mitgibt.

## Bewertungs-Resümee
Minimaler, gut gekapselter JSON-Adapter mit konsequent defensiver Eingabevalidierung. Keine DB-Zugriffe, keine Seiteneffekte ausser der beabsichtigten Mutation des Answer-Objekts. Klassen-Score **A / P3**.
