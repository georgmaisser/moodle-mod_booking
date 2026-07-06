# gotobookingoption — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/gotobookingoption.php` · **LOC:** 118 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`gotobookingoption` (extends `\mod_booking\placeholders\placeholder_base`) erzeugt einen anklickbaren Direktlink zur Detailansicht einer Buchungsoption (`view.php?id=<cmid>&optionid=<optionid>&whichview=showonlyone`). Der Wert ist optionspezifisch, aber userunabhaengig; entsprechend wird per `optionid` gecached. Keine eigene Persistenz; Memo ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()` (nur zur cmid-Aufloesung), `moodle_url`, `html_writer::link`, `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Baut bei vorhandener `$optionid` einen HTML-Link zur Buchungsoption; sonst Fehler-Platzhalter via `get_string`. **Seiteneffekte:** `global $CFG`; fehlt `$cmid`, Aufloesung ueber `singleton_service::get_instance_of_booking_option_settings($optionid)->cmid`; Lookup/Write im request-weiten Memo `placeholders_info::$placeholders["gotobookingoption-$optionid"]`; konstruiert `moodle_url` + `html_writer::link`. **Rueckgabe:** string mit dem `<a>`-Link bzw. Fehler-Platzhalter. **Bewertung:** B — korrekt und richtig per `optionid` gecached (userunabhaengig). Auffaellig: `$user = singleton_service::get_instance_of_user($userid);` (Z.88) wird geladen, aber nie verwendet — toter Code, der bei `$userid=0` einen unnoetigen Singleton-Lookup ausloest. Der Link-Text ist die rohe URL (`$gotobookingoptionlink->out()`), kein sprechendes Label.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Anwendbarkeit. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Funktional korrekte, optionsbasiert gecachte Link-Erzeugung mit sinnvoller cmid-Fallback-Aufloesung. Kleinere Maengel: ungenutzte User-Ladung (toter `singleton_service`-Aufruf) und URL als Link-Text statt sprechendem Label. Kein funktionaler Defekt. Klassen-Score **B / P3**.
