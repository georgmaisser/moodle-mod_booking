# pollstartdate — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/pollstartdate.php` · **LOC:** 102 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`pollstartdate` ist ein konkreter Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `{pollstartdate}` durch das formatierte Startdatum der Buchungsoption ersetzt. Reine statische Helferklasse ohne eigene Persistenz; die Optionsdaten kommen aus `booking_option_settings` ueber den `singleton_service`. Per-Request-Memoisierung ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `userdate()`, Sprachstrings.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert das nach `pollstrftimedate` formatierte `coursestarttime` der Option `$optionid`. Cachekey `"$classname-$optionid"` (option-, nicht user-spezifisch). Bei Treffer Rueckgabe des Memos; sonst Laden der `booking_option_settings` und Formatierung via `userdate((int)$settings->coursestarttime, get_string('pollstrftimedate','booking'))`. Ist `coursestarttime` falsy, wird leerer String geliefert. **Seiteneffekte:** Schreibt in `placeholders_info::$placeholders`; liest Option-Settings ueber den Singleton-Cache. Bei leerem `$optionid` Fallback auf `sthwentwrongwithplaceholder`. **Rueckgabe:** `string` — formatiertes Datum, '' oder Fehler-String. **Bewertung:** B — korrekte option-basierte Memoisierung; `userdate` nutzt die Server-/Default-Zeitzone (kein user-spezifischer TZ-Parameter), was bei diesem option-globalen Cachekey konsistent, aber nicht empfaengerlokal ist. Sprachstring `pollstrftimedate` aus Komponente `booking` (statt `mod_booking`) — historisch, funktioniert via Legacy-Alias.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate-Hook, ob der Platzhalter aufgerufen wird. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Datums-Platzhalter mit option-basierter Memoisierung. Kleine Reibungspunkte: serverseitige Zeitzone und der Komponentenname `booking` im Sprachstring. Funktional unkritisch. Klassen-Score **B / P3**.
