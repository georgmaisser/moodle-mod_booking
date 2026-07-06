# optionid — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/optionid.php` · **LOC:** 119 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`optionid` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der die ID einer Buchungsoption liefert; zusaetzlich ist der Platzhalter explizit fuer pollurl-Kontexte freigegeben. Keine eigene Persistenz; liest ueber den `singleton_service` die `booking_option_settings` und nutzt den Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `placeholders_info`, `get_string`. Die importierten `html_writer` und `moodle_url` werden nicht verwendet.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt die ID der aufgeloesten Buchungsoption (`$settings->id`) als Wert des Platzhalters zurueck. **Seiteneffekte:** liest die (gecachten) Settings ueber den Singleton-Service; schreibt das Ergebnis in den statischen Request-Cache `placeholders_info::$placeholders["$classname-$optionid"]`. Keine DB-Schreibvorgaenge. Bei `optionid == 0` wird der Fehlerstring `sthwentwrongwithplaceholder` geliefert. **Rueckgabe:** die Options-id (oder `''`, falls `$settings->id` nicht gesetzt ist) bzw. der Fehlerstring. **Bewertung:** B — korrekt mit Cache und 0-Guard. Die Zeile `$timeformat = get_string('strftimedate', 'langconfig');` (Z. 82) ist toter Code: die Variable wird nie verwendet (vermutlich aus einer kopierten Vorlage uebernommen). Funktional harmlos, aber unnoetiger `get_string`-Aufruf pro Cache-Miss.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Erlaubt die Verwendung dieses Platzhalters in pollurl-Kontexten (ueberschreibt den Default der Basisklasse). **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Read-Only-Platzhalter analog zu den anderen Settings-basierten Token; gibt die Options-id zurueck und ist zusaetzlich pollurl-faehig. Schwaechen rein kosmetisch (tote Variable `$timeformat`, ungenutzte `html_writer`/`moodle_url`-Imports). Klassen-Score **B / P3**.
