# courselink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/courselink.php` · **LOC:** 111 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`courselink` ist eine Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`) im Messaging-/Placeholder-Subsystem. Sie loest den `{courselink}`-Platzhalter zu einem HTML-Link auf die Kursansicht (`/course/view.php`) des mit der Buchungsoption verknuepften Kurses auf. Keine eigene Persistenz; statisch. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()`, `moodle_url`, `html_writer::link()`, Prozess-Singleton-Cache `placeholders_info::$placeholders`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Baut bei vorhandenem `$optionid` und gesetzter `courseid` einen `<a>`-Link auf `/course/view.php?id=<courseid>`; `''` wenn keine Kurs-Verknuepfung, Fehlerstring bei fehlendem optionid.
- **Seiteneffekte:** Memoisiert ueber `placeholders_info::$placeholders[$cachekey]` mit `$cachekey = "$classname-$optionid"` (options-, nicht userspezifisch — korrekt). Laedt `booking_option_settings` via `singleton_service`, erzeugt `new moodle_url(...)` und rendert via `html_writer::link()` (Linktext ist die ausgeschriebene URL `$courselink->out()`).
- **Rueckgabe:** HTML-Link-String, `''` oder Fehlerstring.
- **Bewertung:** B — funktional korrekt und gecached. Lokale Variable `$timeformat = get_string('strftimedate', 'langconfig')` (Z.82) wird nie verwendet — totes, aus den Datums-Platzhaltern uebernommenes Fragment.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Aktivierung.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A — konstanter Gate.

## Bewertungs-Resümee
Schlanker Kurs-Link-Platzhalter mit sauberem options-bezogenem Memo-Cache. Einziger Makel: die unbenutzte `$timeformat`-Zeile. Funktional unkritisch. Klassen-Score **B / P3**.
