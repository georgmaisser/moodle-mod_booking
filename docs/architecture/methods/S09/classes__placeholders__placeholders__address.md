# address — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/address.php` · **LOC:** 97 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`address` ist eine konkrete Platzhalterklasse (erweitert `placeholder_base`), die das `{address}`-Token durch die Adresse/Location der Buchungsoption ersetzt. Sie haelt keinen eigenen Zustand; die Daten stammen aus den per `singleton_service::get_instance_of_booking_option_settings($optionid)` geladenen Options-Settings (`$settings->address`). Sie liest einen Request-Cache-Slot in `placeholders_info::$placeholders`. Kollaborateure: `placeholders_info` (Cache), `singleton_service`, `get_string`. Wird dynamisch von `placeholders_info::render_text()` via `return_value(...)` aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert bei vorhandenem `$userid` die `address` aus den Options-Settings; ohne `$userid` einen lokalisierten Fehlertext (`sthwentwrongwithplaceholder`). Vor dem Settings-Load wird ein Cache-Slot `"$classname-$optionid"` in `placeholders_info::$placeholders` geprueft. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)` (potenziell DB beim ersten Zugriff, danach Singleton-Cache); `$text`/`$params` sind per Referenz deklariert, werden aber nicht mutiert. **Rueckgabe:** `string` (Adresse oder Fehlertext; kein deklarierter Rueckgabetyp). **Bewertung:** B — funktional korrekt, aber der Cache-Slot `placeholders_info::$placeholders[$cachekey]` wird hier nur **gelesen, nie geschrieben**; solange keine andere Stelle ihn befuellt, ist der Lookup ein toter Pfad und die Settings werden bei jedem Aufruf erneut geholt (Caching-Kommentar irrefuehrend). Zudem ignoriert die Methode das von `render_text()` als 10. Argument uebergebene `$rulejson` (Signatur endet bei `$descriptionparam`) — PHP toleriert das, aber die Klasse weicht vom Aufrufer-Kontrakt ab. Der Cachekey haengt nicht an `$userid`, obwohl der Pfad nur fuer `$userid != 0` greift — fuer eine option-konstante Adresse korrekt, aber inkonsistent zum userabhaengigen Eintrittsguard.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Aktiviert den Platzhalter (ueberschreibt den Basis-Default `false`). **Seiteneffekte:** keine. **Rueckgabe:** `bool` — `true`. **Bewertung:** A. Hinweis: `for_pollurl()` wird nicht ueberschrieben, der Platzhalter ist also (korrekterweise) nicht pollurl-faehig.

## Bewertungs-Resümee
Schlanke, korrekte Platzhalterklasse nach dem Standardmuster. Einziger funktionaler Vorbehalt: der gelesene, aber nie geschriebene Cache-Slot (toter Lese-Pfad, kein echter Spareffekt) sowie die unkritische Signatur-Abweichung zum `return_value`-Aufrufkontrakt. Keine Daten-/Sicherheitsprobleme. Klassen-Score **B / P3**.
