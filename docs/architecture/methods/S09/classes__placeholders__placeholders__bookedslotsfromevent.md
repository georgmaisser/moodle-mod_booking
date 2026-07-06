# bookedslotsfromevent — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookedslotsfromevent.php` · **LOC:** 115 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookedslotsfromevent` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer das Slotbooking-Feature: sie extrahiert gebuchte Slots aus dem Event-Payload eines Booking-Rules-Triggers (uebergeben via `$rulejson`) und rendert sie als formatierte Zeitspannen-Liste. Keine Persistenz; arbeitet rein auf dem dekodierten JSON. Kollaborateure: `userdate()`, `get_string('strftimedatetime','langconfig')`, der Booking-Rules-Trigger, der das `$rulejson` befuellt.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liest aus `$rulejson->datafromevent->other` die gebuchten Slots und gibt sie als `"<start> - <end>; ..."`-String zurueck. **Seiteneffekte:** keine; reine Transformation. Logik: `json_decode($rulejson)`; bei fehlendem `datafromevent`/`other` Leerstring. Slot-Quelle ist `other->bookedslots`, mit Fallback-Kaskade auf `newslots` und dann `oldslots`, falls vorherige leer. Nicht-Array → Leerstring. Pro Slot: akzeptiert Objekt **und** Array-Form, castet `start`/`end` auf int, ueberspringt invalide (`start <= 0` oder `end <= start`), formatiert beide via `userdate(...,strftimedatetime)`. **Rueckgabe:** `implode('; ', $rows)`. **Bewertung:** B — robuste Defensive (Objekt/Array-Dualitaet, Validierung, Fallback-Kaskade); kein deklarierter `: string`-Rueckgabetyp, gibt aber in allen Pfaden String zurueck. Solide.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Sauber defensiv geschriebener Slotbooking-Platzhalter: deckt Objekt-/Array-Payloads ab, validiert Zeitstempel und fuehrt eine sinnvolle Fallback-Kaskade (bookedslots → newslots → oldslots). Keine funktionalen Maengel gefunden. Klassen-Score **B / P3**.
