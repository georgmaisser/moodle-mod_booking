# teacher — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/teacher.php` · **LOC:** 97 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`teacher` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer Mails/Texte rund um Buchungsoptionen. Sie liefert die Lehrenden einer Option als **indexierbares Array** von `"Vorname Nachname"`-Strings — das Konsumenten-Framework `placeholders_info` greift dann per Index (`{teacher1}`, `{teacher2}`, ...) auf einzelne Eintraege zu, bzw. nimmt bei reinem `{teacher}` den ersten. Keine Persistenz; liest Lehrer-Stammdaten ueber `booking_option_settings`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()` (liefert `->teachers`), `placeholders_info` (Index-Aufloesung + Cache-Map), `get_string()` fuer den Fehlertext.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt die Lehrenden der Option `$optionid` als `array_values(array_map(fn($a) => "$a->firstname $a->lastname", $settings->teachers))` zurueck. **Seiteneffekte:** keine Schreibvorgaenge; liest via Singleton die Option-Settings. Ablauf: Klassenname aus `get_called_class()`; bei leerem `$optionid` Rueckgabe des `sthwentwrongwithplaceholder`-Fehlerstrings; bei gesetztem `$optionid` Cache-Lookup in `placeholders_info::$placeholders["teacher-$optionid"]`. **Rueckgabe:** Array von Lehrernamen (bzw. Fehler-String im Else-Pfad); kein deklarierter Rueckgabetyp. Die Array-Rueckgabe ist gewollt — `placeholders_info` (Z.153-163) loest sie per Platzhalter-Index bzw. `reset()` auf. **Bewertung:** B — funktional korrekt im Rahmen des Frameworks; siehe Resümee zur fehlenden Cache-Befuellung.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, indexierbarer Lehrer-Platzhalter; die Array-Rueckgabe ist mit dem Index-Aufloesungspfad in `placeholders_info` abgestimmt und somit kein Defekt. Einzige Schwaeche: der Cachekey wird gelesen, aber **nie geschrieben** (`placeholders_info::$placeholders[$cachekey] = $value;` fehlt, anders als z.B. in `type`), sodass der Request-Memo-Cache fuer diesen Platzhalter wirkungslos bleibt. Funktional unkritisch. Klassen-Score **B / P3**.
