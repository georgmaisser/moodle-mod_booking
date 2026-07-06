# location — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/location.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`location` ist eine Platzhalter-Klasse (erweitert `placeholder_base`), die `{location}` durch das `location`-Feld der Buchungsoption ersetzt. Strukturell identisch zu `institution` (gleiche Signatur, gleicher Memo-/Fallback-Pfad, nur ein anderes Settings-Feld). Keine eigene Persistenz; liest ueber `singleton_service::get_instance_of_booking_option_settings()` und nutzt den Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `placeholders_info`, `singleton_service`, `placeholder_base`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert das `location`-Feld der Option (bei vorhandener `$optionid`), sonst eine Fehlerzeichenkette. **Seiteneffekte:** liest/schreibt `placeholders_info::$placeholders["location-$optionid"]`; `singleton_service::get_instance_of_booking_option_settings($optionid)`. **Rueckgabe:** string mit dem Ort bzw. `get_string('sthwentwrongwithplaceholder', ...)` bei fehlender Option. **Bewertung:** B — korrekte Memoisierung pro Option; Code ist eine 1:1-Kopie von `institution` mit getauschtem Feld (Duplikation ueber die Geschwisterklassen hinweg, dem flachen Platzhalter-Kontrakt geschuldet).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Schaltet den Platzhalter generell scharf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Schmaler optionsbezogener Platzhalter, strukturgleich zu `institution`. Funktional unkritisch; einziger Punkt ist die Code-Duplikation ueber die Field-Platzhalter (Kontraktmuster). Klassen-Score **B / P3**.
