# endtime — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/endtime.php` · **LOC:** 103 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`endtime` ist eine Platzhalter-Klasse (`extends placeholder_base`) und das Uhrzeit-Pendant zu `enddate`: sie ersetzt den `endtime`-Platzhalter durch die formatierte Endzeit der Buchungsoption. Identische Struktur und Kollaborateure wie `enddate`, nur das Zeitformat unterscheidet sich (`strftimetime` statt `strftimedate`). Memo-Cache `placeholders_info::$placeholders`, Lookup via `singleton_service::get_instance_of_booking_option_settings()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert `courseendtime` der Option als per `strftimetime` formatierte Uhrzeit. **Seiteneffekte:** Lese-/Schreib-Memo `placeholders_info::$placeholders["$classname-$optionid"]`, sonst `singleton_service::get_instance_of_booking_option_settings($optionid)`. Ohne `$optionid` Fehlerstring `sthwentwrongwithplaceholder`. `courseendtime` falsy → Leerstring. **Rueckgabe:** formatierte Uhrzeit (string)/Leerstring/Fehlerstring; kein deklarierter Rueckgabetyp. **Bewertung:** B — exakte Kopie von `enddate` bis auf das Format-String; user-unabhaengiger Cachekey korrekt. Code-Duplikation zur `enddate`-Klasse (familientypisch).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Korrekter Uhrzeit-Platzhalter, strukturgleich zu `enddate`. Einzige Anmerkung ist die Duplikation der return_value-Logik ueber die Datums-/Zeit-Platzhalter hinweg. Keine funktionalen Maengel. Klassen-Score **B / P3**.
