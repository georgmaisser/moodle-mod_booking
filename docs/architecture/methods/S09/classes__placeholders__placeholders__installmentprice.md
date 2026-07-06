# installmentprice — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/installmentprice.php` · **LOC:** 78 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`installmentprice` (extends `\mod_booking\placeholders\placeholder_base`) ist der einfachste Platzhalter der Familie: Er gibt schlicht den als Argument durchgereichten Ratenbetrag (`$price`) einer einzelnen Installment-Rate zurueck. Keine Persistenz, kein Cache, keine Kollaborateure — der Wert wird vom aufrufenden Mail-/Installment-Rendering bereits berechnet uebergeben.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den Raten-Preis als Platzhalterwert. **Seiteneffekte:** keine. **Rueckgabe:** gibt `$price` (float) unveraendert zurueck — ohne Waehrungs-/Formatierung und ohne den sonst ueblichen `sthwentwrongwithplaceholder`-Fehlerpfad. **Bewertung:** B — funktional korrekt und bewusst minimal (Berechnung liegt beim Aufrufer); der Rueckgabewert ist ein roher float statt eines formatierten Strings, und ein `$price` von `0` ist nicht von „nicht gesetzt" unterscheidbar (kein Guard).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Anwendbarkeit. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Minimaler Pass-Through-Platzhalter ohne Eigenlogik; korrekt fuer seinen Zweck. Einzige Anmerkungen: roher float statt formatiertem Betrag und keine Unterscheidung zwischen Rate `0` und fehlendem Wert. Unkritisch. Klassen-Score **B / P3**.
