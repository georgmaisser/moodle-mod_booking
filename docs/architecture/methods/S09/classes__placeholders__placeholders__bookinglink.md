# bookinglink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookinglink.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookinglink` ist ein Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der den Marker `[bookinglink]` in Mail-/Beschreibungstexten durch einen HTML-Link auf die Buchungsinstanz-Uebersicht (`/mod/booking/view.php?id=<cmid>`) ersetzt. Keine eigene Persistenz; nutzt den statischen Prozess-Singleton-Cache `placeholders_info::$placeholders` (Request-Lifetime), um pro `optionid` denselben Wert wiederzuverwenden. Kollaborateure: `singleton_service` (Option-Settings), `moodle_url`/`html_writer` (Link-Erzeugung), `placeholders_info` (Cache). Zwei rein statische Methoden, kein Zustand.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den HTML-Link zur Buchungsinstanz. Ableitung des Klassennamens via `get_called_class()`, Cache-Lookup unter Key `"$classname-$optionid"`; bei Treffer Sofort-Return. Sonst werden ueber `singleton_service::get_instance_of_booking_option_settings($optionid)` die Settings geladen und — nur wenn `$settings->cmid` gesetzt — der Link mit dem **uebergebenen `$cmid`** (nicht `$settings->cmid`) gebaut.
- **Seiteneffekte:** Schreibt das Ergebnis in `placeholders_info::$placeholders[$cachekey]` (statischer Request-Cache). Liest Option-Settings ueber den Singleton-Service.
- **Rueckgabe:** HTML-`<a>`-Link als String; bei leerem `$optionid` der Fehlerstring `get_string('sthwentwrongwithplaceholder', ...)`; bei fehlendem `$settings->cmid` der leere String (wird ebenfalls gecached).
- **Bewertung:** B — funktional korrekt und cache-bewusst, aber zwei Schoenheitsfehler: `$timeformat` (Z.81) wird berechnet und nie verwendet (toter Code, aus Copy/Paste der anderen Platzhalter uebernommen); der Cache-Set in Z.92 liegt nur im `if`-Zweig — wird `$optionid` mit nicht existierender Option aufgerufen, ist das unkritisch, aber die `cmid`-Gate-Logik nutzt `$settings->cmid` nur als Bedingung und baut den Link dann doch aus dem Parameter-`$cmid`.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter ueberhaupt aufgerufen werden soll. Hier konstant `true`.
- **Seiteneffekte:** keine.
- **Rueckgabe:** `true`.
- **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Schlanker Link-Platzhalter mit korrektem Request-Cache. Einziger realer Makel: ungenutzte `$timeformat`-Zeile (Copy/Paste-Erblast) und die implizite Annahme, dass der uebergebene `$cmid` zur Option passt (es wird nur die Existenz von `$settings->cmid` geprueft, der Link aber aus `$cmid` gebaut). Funktional unkritisch. Klassen-Score **B / P3**.
