# lastname — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/lastname.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`lastname` ist eine Platzhalter-Klasse (erweitert `placeholder_base`), die `{lastname}` durch den Nachnamen des adressierten Nutzers ersetzt. Anders als die optionsbezogenen Platzhalter ist der Wert nutzerabhaengig; der Cachekey enthaelt daher die `$userid`. Keine eigene Persistenz; liest ueber `singleton_service::get_instance_of_user()` und nutzt den Request-Cache `placeholders_info::$placeholders`. Zusaetzlich als pollurl-faehig markiert. Kollaborateure: `placeholders_info`, `singleton_service`, `placeholder_base`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den Nachnamen (`$user->lastname`) des per `$userid` adressierten Nutzers, sonst eine Fehlerzeichenkette. **Seiteneffekte:** liest/schreibt `placeholders_info::$placeholders["lastname-$userid"]`; `singleton_service::get_instance_of_user($userid)`. **Rueckgabe:** string mit dem Nachnamen bzw. `get_string('sthwentwrongwithplaceholder', ...)` bei fehlender `$userid`. **Bewertung:** B — korrekt nutzerabhaengiger Cachekey; Standardmuster, breite ungenutzte Signatur (Kontrakt).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Schaltet den Platzhalter generell scharf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Erlaubt den Einsatz des Platzhalters in Pollurl-Kontexten (Ueberschreibt den `placeholder_base`-Default). **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Nutzerbezogener Platzhalter nach dem Standardmuster, korrekt per `$userid` gecacht und zusaetzlich pollurl-faehig. Funktional unkritisch. Klassen-Score **B / P3**.
