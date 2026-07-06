# courseid — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/courseid.php` · **LOC:** 119 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`courseid` ist eine Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`) im Messaging-/Placeholder-Subsystem. Sie loest den `{courseid}`-Platzhalter zur Moodle-Kurs-ID des mit der Buchungsoption verknuepften Kurses auf. Keine eigene Persistenz; statisch. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()`, Prozess-Singleton-Cache `placeholders_info::$placeholders`, `get_string`. Der Klassen-Kommentar ("Returns a link to a course") ist ein Copy-Paste aus `courselink`/`coursename` — diese Klasse liefert nur die ID, keinen Link.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert bei vorhandenem `$optionid` die `courseid` aus den `booking_option_settings`; `''` wenn keine Kurs-Verknuepfung besteht, Fehlerstring bei fehlendem optionid.
- **Seiteneffekte:** Memoisiert ueber `placeholders_info::$placeholders[$cachekey]` mit `$cachekey = "$classname-$optionid"` (options-, nicht userspezifisch — korrekt, da die courseid pro Option gleich ist). Laedt `booking_option_settings` via `singleton_service`.
- **Rueckgabe:** Kurs-ID (als int aus dem Settings-Objekt) oder `''`/Fehlerstring.
- **Bewertung:** B — funktional korrekt und gecached. Lokale Variable `$timeformat = get_string('strftimedate', 'langconfig')` (Z.82) wird nie verwendet — totes, aus den Datums-Platzhaltern uebernommenes Code-Fragment.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Aktivierung.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Markiert den Platzhalter als in Pollurl-Mails verwendbar.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A — konstanter Gate.

## Bewertungs-Resümee
Triviale ID-Lieferung mit sauberem options-bezogenem Memo-Cache und pollurl-Faehigkeit. Wermutstropfen: unbenutzte `$timeformat`-Zeile und ein irrefuehrender, kopierter Klassen-Kommentar. Funktional unkritisch. Klassen-Score **B / P3**.
