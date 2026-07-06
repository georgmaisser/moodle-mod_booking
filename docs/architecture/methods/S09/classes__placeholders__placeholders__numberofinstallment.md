# numberofinstallment — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/numberofinstallment.php` · **LOC:** 79 · **Subsystem:** S09 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`numberofinstallment` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der die laufende Ratennummer (Installment) als lesbaren Text liefert. Keine Persistenz, kein DB-Zugriff, kein Caching. Einziger Kollaborateur ist `get_string()` aus der Sprach-API; der relevante Eingabewert ist der durchgereichte Parameter `$installmentnr`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Erzeugt den Anzeigetext fuer die aktuelle Rate, indem `$installmentnr` (0-basiert) um 1 erhoeht und in den Sprachstring `numberofinstallmentstring` eingesetzt wird. **Seiteneffekte:** keine (kein DB, kein Cache, keine Mutation der `&$text`/`&$params`-Referenzen). **Rueckgabe:** der lokalisierte String (z. B. „Rate 1"). **Bewertung:** A — minimal, reine Transformation; die uebrigen Signaturparameter werden ignoriert, gehoeren aber zum gemeinsamen Platzhalter-Kontrakt.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Trivialer, zustandsloser Platzhalter: er addiert 1 auf die Ratennummer und formatiert sie ueber `get_string`. Keine Guards noetig, keine Schwaechen. Klassen-Score **A / P3**.
