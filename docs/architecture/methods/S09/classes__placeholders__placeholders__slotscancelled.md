# slotscancelled — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/slotscancelled.php` · **LOC:** 76 · **Subsystem:** S09 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`slotscancelled` erweitert `\mod_booking\placeholders\placeholder_base` und rendert die Liste der stornierten Slot(s) aus dem Payload eines Slot-Cancelled-Events. Wie `slotsbooked` ist die Klasse ein duenner Adapter ueber den geteilten Helper `mod_booking\local\slotbooking\slot_event_placeholders`. Persistenz: keine. Zustandslos (nur statische Methoden).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Delegiert vollstaendig an `slot_event_placeholders::render($rulejson, ['bookedslots'])` und gibt die formatierte Liste der stornierten Slots zurueck.
- **Seiteneffekte:** keine direkten; alle Effekte liegen im Helper. `&$text`/`&$params` ungenutzt.
- **Rueckgabe:** `string` — gerenderte Slot-Liste (Ergebnis des Helpers).
- **Bewertung:** A — minimaler Delegations-Adapter; uebergibt nur den Schluessel `bookedslots` (im Cancel-Event traegt die Payload die betroffenen Slots unter diesem Key).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Spiegelbild zu `slotsbooked`: sauberer, schlanker Adapter auf den gemeinsamen Slot-Event-Renderer, nur mit anderem Payload-Schluessel. Keine funktionalen Maengel. Klassen-Score **A / P3**.
