# slotsmovedto — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/slotsmovedto.php` · **LOC:** 75 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`slotsmovedto` ist das Gegenstueck zu `slotsmovedfrom`: der Mail-/Text-Platzhalter (`[slotsmovedto]`), der die *neuen* Slots rendert, auf die eine Buchung im Zuge eines Slot-Umzugs verschoben wurde. Sie erbt von `\mod_booking\placeholders\placeholder_base`, hat keine eigene Persistenz und delegiert die Formatierung vollstaendig an `mod_booking\local\slotbooking\slot_event_placeholders`. Daten kommen aus dem `rulejson`-Event-Payload, nicht aus den Option-Settings.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liefert die formatierte Liste der neuen Slots des ausloesenden Events. **Seiteneffekte:** keine; reine Delegation an `slot_event_placeholders::render($rulejson, ['newslots', 'bookedslots'])`. Der zweite Key dient als Fallback: gibt es keinen `newslots`-Key, wird auf `bookedslots` zurueckgegriffen. **Rueckgabe:** string mit der gerenderten Neu-Slot-Liste (leer, wenn beide Keys fehlen). **Bewertung:** B — symmetrisch zu `slotsmovedfrom`; einzige Abweichung ist die Key-Liste mit Fallback. Ignoriert wie alle Platzhalter die meisten Signatur-Parameter.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Vertrags-Hook, ob der Platzhalter verarbeitet wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Spiegelbildlicher Adapter zu `slotsmovedfrom`; nebenwirkungsfrei und korrekt, Substanz in `slot_event_placeholders::render`. Klassen-Score **B / P3**.
