# price — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/price.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`price` ist ein konkreter Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `{price}` ersetzt. Anders als die uebrigen Platzhalter laedt er nichts aus der DB, sondern leitet den Preis primaer aus dem an `return_value` durchgereichten `$price`-Parameter ab und ueberschreibt ihn nur dann, wenn ein `$rulejson`-Eventkontext eines `local_shopping_cart`-Zahlungsevents vorliegt. Reine statische Helferklasse ohne Persistenz und ohne Memoisierung. Kollaborateure: `local_shopping_cart\event\payment_confirmed`-Eventdaten (als JSON), `$OUTPUT` (global importiert, aber ungenutzt).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liefert den Buchungspreis. Dekodiert `$rulejson`; enthaelt es `datafromevent` mit `eventname == '\local_shopping_cart\event\payment_confirmed'`, wird `$event->other->cart` als JSON dekodiert und `$price` auf `"<price> <currency>"` gesetzt. Andernfalls wird der uebergebene `$price`-Parameter unveraendert durchgereicht. **Seiteneffekte:** keine (keine DB-, Cache- oder Memo-Schreibvorgaenge); deklariert `global $OUTPUT`, nutzt es aber nicht. **Rueckgabe:** gemischt — entweder `float` (durchgereichter `$price`) oder `string` `"<price> <currency>"` aus den Eventdaten. **Bewertung:** B — die lokale Variable `$value = ''` (Z.71) ist toter Code (nie zurueckgegeben). Inkonsistenter Rueckgabetyp (float vs. string) je nach Pfad. Zugriffe `$eventdata['price']`/`$eventdata['currency']` und `$event->other->cart` ohne isset-Pruefung — bei abweichender Eventstruktur drohen Warnings/Notices (P3).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate-Hook. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

## Bewertungs-Resümee
Eventgetriebener Preis-Platzhalter ohne DB-Last; der Normalfall reicht den `$price`-Parameter durch, der Sonderfall extrahiert Preis/Waehrung aus `payment_confirmed`-Eventdaten. Schwaechen: tote `$value`-Variable, ungenutztes `global $OUTPUT`, inkonsistenter Rueckgabetyp und fehlende Key-Guards beim Event-JSON. Funktional unkritisch. Klassen-Score **B / P3**.
