# journal — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/journal.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`journal` ist eine Platzhalter-Klasse (erweitert `placeholder_base`), die `{journal}` durch einen HTML-Link auf den Teacher-/Trainings-Journal-Report der Option (`optiondates_teachers_report.php`) ersetzt. Keine eigene Persistenz; nutzt `singleton_service::get_instance_of_booking_option_settings()` nur zum Nachladen der `cmid`, sowie den Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `html_writer`, `moodle_url`, `placeholders_info`, `singleton_service`, `placeholder_base`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Baut einen anklickbaren Link auf den Lehrer-/Journal-Report der Option und gibt ihn als HTML zurueck; bei fehlender `$optionid` eine Fehlerzeichenkette. **Seiteneffekte:** ggf. `singleton_service::get_instance_of_booking_option_settings($optionid)` zur Auffuellung von `$cmid` (wenn leer); liest/schreibt `placeholders_info::$placeholders["journal-$optionid"]`; erzeugt `moodle_url` + `html_writer::link()`. **Rueckgabe:** HTML-Link-String bzw. Fehlerstring. **Bewertung:** B — sinnvolle Memoisierung pro Option und defensives Nachladen der `cmid`. Anmerkung: Die `cmid`-Aufloesung steht vor dem Cache-Hit-Check; bei einem Cache-Treffer wird die Option-Settings-Singleton dennoch potenziell angefasst (geringer Overhead, da Singleton). Der erzeugte Link ist nutzerunabhaengig, der Cachekey ohne `$userid` daher korrekt.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Schaltet den Platzhalter generell scharf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Standard-Platzhalter mit kleiner Zusatzlogik (cmid-Fallback, URL-Bau). Lesbar und unkritisch; einziger Schoenheitsfehler ist die `cmid`-Aufloesung vor dem Cache-Hit-Check. Klassen-Score **B / P3**.
