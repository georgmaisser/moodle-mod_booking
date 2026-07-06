# customform — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/customform.php` · **LOC:** 145 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_*.md)

## Klassenueberblick
`customform` ist ein Platzhalter-Handler (`extends placeholder_base`), der die Antworten eines an die Buchung gehaengten Custom-Formulars (`bo_availability\conditions\customform`) aus einem restaurierten Event aufloest und als HTML-Liste `Label: Wert` zurueckgibt. Persistenz: keine eigene; liest Option-Settings via `singleton_service` und Formularelemente via `conditions\customform::return_formelements()`. Kollaborateure: `singleton_service`, `bo_availability\conditions\customform`, restauriertes Event aus `$rulejson->datafromevent`. Besonderheit: nutzt — anders als die meisten Platzhalter — `$text` gar nicht, sondern liefert direkt den formatierten Wert.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Restauriert das Event aus `$rulejson->datafromevent`, liest dessen `other->json->condition_customform`-Antworten und uebersetzt fuer Select-Felder den gespeicherten Schluessel zurueck in den lesbaren Optionswert (anhand der `=>`-getrennten `formelement->value`-Zeilen). Ergebnis ist eine `<br>`-getrennte Liste `Label: Wert`.
- **Seiteneffekte:** `json_decode($rulejson)`; `$class::restore((array)$rulejson->datafromevent, [])` (Event-Restore); Lesen der Formelement-Definition. Keine DB-Schreibzugriffe, keine Memoisierung.
- **Rueckgabe:** `format_text($value)` — HTML-String der aufgeloesten Custom-Form-Werte (leer, wenn kein Event vorliegt).
- **Bewertung:** C — die Select-Aufloesung ist fragil: `$lines[$value]` indexiert das Zeilen-Array mit `$value` (funktioniert nur, wenn der gespeicherte Wert ein numerischer Auto-Index ist); die `count($linearray) < 2`-Heuristik und die mehrfache Neuzuweisung von `$returnvalue` innerhalb der Schleife sind schwer nachvollziehbar. `$json` wird nur unter `isset($eventdata["other"]->json)` belegt, danach aber in `isset($json->condition_customform)` geprueft — auf undefiniertem `$json` liefert `isset()` `false` ohne Warnung, also kein Fehler, aber implizit. `format_text()` ohne expliziten Format-/Kontext-Parameter. Im Gegensatz zu Geschwister-Klassen wird `$text` nicht ersetzt (Inkonsistenz im Engine-Kontrakt).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate fuer den Aufruf.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A.

## Bewertungs-Resümee
Der Handler erfuellt seinen Zweck (lesbare Custom-Form-Antworten in Benachrichtigungen), die Select-Wert-Aufloesung ist aber unnoetig verschachtelt und stuetzt sich auf fragile Index-Annahmen. Kein Daten-Verlust, kein N+1. Klassen-Score **C / P2** (CLASS_INDEX listet B/P3 als groben Hint; die Select-Aufloesungslogik rechtfertigt eine Stufe strenger).
