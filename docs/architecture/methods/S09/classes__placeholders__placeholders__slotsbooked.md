# slotsbooked — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/slotsbooked.php` · **LOC:** 76 · **Subsystem:** S09 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`slotsbooked` erweitert `\mod_booking\placeholders\placeholder_base` und rendert die Liste der gebuchten Slot(s), die im Payload eines Slot-Booked-Events transportiert werden. Die gesamte Logik ist in den geteilten Helper `mod_booking\local\slotbooking\slot_event_placeholders` ausgelagert; die Klasse ist ein duenner Adapter. Persistenz: keine. Zustandslos (nur statische Methoden).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Delegiert vollstaendig an `slot_event_placeholders::render($rulejson, ['bookedslots', 'newslots'])` und gibt die formatierte Liste der gebuchten/neuen Slots zurueck.
- **Seiteneffekte:** keine direkten; alle DB-/Render-Effekte liegen im Helper. `&$text`/`&$params` ungenutzt.
- **Rueckgabe:** `string` — gerenderte Slot-Liste (Ergebnis des Helpers).
- **Bewertung:** A — minimaler, klarer Delegations-Adapter; uebergibt zwei Payload-Schluessel (`bookedslots`, `newslots`) an den geteilten Renderer.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer, sehr schlanker Slotbooking-Platzhalter, der die Logik korrekt in den gemeinsamen `slot_event_placeholders`-Renderer auslagert. Keine funktionalen Maengel. Klassen-Score **A / P3**.
