# slotsmovedfrom — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/slotsmovedfrom.php` · **LOC:** 75 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`slotsmovedfrom` ist ein Mail-/Text-Platzhalter (`[slotsmovedfrom]`) aus dem Slotbooking-Umfeld. Er rendert die *alten* Slots, von denen eine Buchung weggebucht wurde, fuer eine Slot-Moved-Benachrichtigung. Die Klasse erbt von `\mod_booking\placeholders\placeholder_base` und implementiert nur die beiden vom Platzhalter-Vertrag geforderten statischen Methoden. Eigene Persistenz hat sie nicht; die gesamte Logik (JSON-Decode des Event-Payloads, Slot-Formatierung) ist an den Kollaborateur `mod_booking\local\slotbooking\slot_event_placeholders` ausgelagert. Anders als die meisten Platzhalter liest sie *nicht* aus `booking_option_settings`, sondern bezieht ihre Daten ausschliesslich aus dem `rulejson` der triggernden Rule/Event-Payload.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liefert die formatierte Liste der alten Slots des ausloesenden Events. **Seiteneffekte:** keine; reiner Delegations-Aufruf an `slot_event_placeholders::render($rulejson, ['oldslots'])`. Die Referenz-Parameter `$text`/`$params` werden nicht mutiert. **Rueckgabe:** string mit der gerenderten Alt-Slot-Liste (leer, wenn der Payload keinen `oldslots`-Key enthaelt). **Bewertung:** B — sauber duenn gehalten; ignoriert allerdings die meisten Signatur-Parameter (`$cmid`/`$optionid`/`$userid` etc.), was dem breiten Platzhalter-Vertrag geschuldet ist.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter ueberhaupt verarbeitet werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A — trivialer Vertrags-Hook.

## Bewertungs-Resümee
Schlanker Adapter-Platzhalter ohne eigene Logik; alle Substanz liegt in `slot_event_placeholders::render`. Korrekt und nebenwirkungsfrei. Klassen-Score **B / P3**.
