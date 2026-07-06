# coursename — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/coursename.php` · **LOC:** 121 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`coursename` ist eine Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`) im Messaging-/Placeholder-Subsystem. Sie loest den `{coursename}`-Platzhalter zum (formatierten) vollen Kursnamen des mit der Buchungsoption verknuepften Kurses auf. Keine eigene Persistenz; statisch. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()`, `get_course()`, `format_string()`, Prozess-Singleton-Cache `placeholders_info::$placeholders`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert bei vorhandenem `$optionid` und gesetzter `courseid` den via `format_string()` aufbereiteten `fullname` des Kurses; `''` ohne Kurs-Verknuepfung, Fehlerstring bei fehlendem optionid.
- **Seiteneffekte:** Memoisiert ueber `placeholders_info::$placeholders[$cachekey]` mit `$cachekey = "$classname-$optionid"` (options-, nicht userspezifisch). Laedt `booking_option_settings` via `singleton_service` und den Kurs via `get_course($settings->courseid)` (eigener DB-/Cache-Zugriff der Core-API).
- **Rueckgabe:** formatierter Kursname, `''` oder Fehlerstring.
- **Bewertung:** B — funktional korrekt, gecached; `get_course()` ist intern gecached, daher kein N+1-Risiko. Lokale Variable `$timeformat = get_string('strftimedate', 'langconfig')` (Z.82) wird nie verwendet — totes Fragment.

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
Standardkonformer Kursnamen-Platzhalter (korrekt `format_string`-gefiltert) mit options-bezogenem Memo-Cache und pollurl-Faehigkeit. Einziger Makel: die unbenutzte `$timeformat`-Zeile (in allen drei course-*-Platzhaltern identisch dupliziert). Funktional unkritisch. Klassen-Score **B / P3**.
