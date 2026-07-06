# bookingconfirmationlink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookingconfirmationlink.php` · **LOC:** 98 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookingconfirmationlink` ist eine Platzhalter-Klasse (`extends placeholder_base`), die aus einem `local_shopping_cart\event\payment_confirmed`-Event den Beleg-/Bestaetigungs-URL (`receipturl`) extrahiert und als Link-Wert liefert. Keine Persistenz; arbeitet auf dem dekodierten `$rulejson`-Event-Payload. Kollaborateure: Booking-Rules-Trigger (befuellt `$rulejson`), `local_shopping_cart` (Event-Quelle), `$OUTPUT` (global importiert, aber ungenutzt).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, und falls das Event `\local_shopping_cart\event\payment_confirmed` ist, liest es `receipturl` aus dem JSON-dekodierten `other->cart`. **Seiteneffekte:** `global $OUTPUT` deklariert aber nicht genutzt; `json_decode($rulejson)` plus inneres `json_decode($event->other->cart, true)`. **Rueckgabe:** `$bookingconfirmationlink` (der `receipturl`). **Bewertung:** C — mehrere Robustheitsdefekte: (1) `$bookingconfirmationlink` wird **nur** im inneren `if ($class == '...payment_confirmed')`-Zweig definiert; ist `$rulejson` leer/kein `datafromevent` vorhanden oder das Event ein anderer Typ, trifft `return $bookingconfirmationlink;` auf eine **undefinierte Variable** (PHP-Warning, Rueckgabe `null`/`''` statt sauberem Leerstring). (2) Kein `?? ''`-Guard auf `$eventdata['receipturl']` → bei Cart ohne `receipturl`-Key wieder Warning. (3) Kein deklarierter `: string`-Rueckgabetyp, sodass `null` ungehindert zurueckgegeben werden kann.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Funktioniert im Happy-Path (payment_confirmed mit `receipturl`), ist aber gegen die naheliegenden Negativ-Faelle nicht abgesichert: bei jedem anderen Event-Typ oder fehlendem Cart-`receipturl` liefert die Methode eine undefinierte Variable / einen Notice und ggf. `null`. Im Gegensatz zu den Schwester-Platzhaltern (`bookedslotsfromevent`) fehlt die Fruehruckgabe-Defensive. Klassen-Score **C / P2**.
