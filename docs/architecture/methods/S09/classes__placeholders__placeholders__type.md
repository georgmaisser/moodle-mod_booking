# type — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/type.php` · **LOC:** 117 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`type` ist eine Platzhalter-Klasse (`extends placeholder_base`), die das `type`-Feld einer Buchungsoption als Platzhalterwert liefert und auch fuer Pollurl-Texte zugelassen ist. Keine Persistenz; liest das Feld aus den Option-Settings. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()` (liefert `->type`), `placeholders_info` (Request-Memo-Cache), `get_string()` fuer den Fehlertext.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt `$settings->type` der Option `$optionid` zurueck (Leerstring, falls nicht gesetzt). **Seiteneffekte:** schreibt das Ergebnis in den Request-Memo `placeholders_info::$placeholders["type-$optionid"]`. Ablauf: Klassenname aus `get_called_class()`; bei leerem `$optionid` Rueckgabe des `sthwentwrongwithplaceholder`-Fehlerstrings; sonst Cache-Lookup, Settings ueber Singleton, `$value` aus `->type` (mit `isset`-Guard), anschliessend Cache-Befuellung. **Rueckgabe:** Typ-String bzw. Fehler-String; kein deklarierter Rueckgabetyp. **Bewertung:** B — korrekt und im Gegensatz zu `teacher`/`teachers` mit funktionierender Cache-Befuellung. Die Zeile `$timeformat = get_string('strftimedate', 'langconfig');` (Z.80) ist toter Code — die Variable wird nie verwendet (P3, harmlos).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Erlaubt die Verwendung dieses Platzhalters in Pollurl-Texten. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Solider Feld-Platzhalter mit korrekt befuelltem Request-Memo und Pollurl-Freigabe. Einziger Makel: die ungenutzte `$timeformat`-Zeile (Copy-Paste-Rest aus einem datumsbasierten Platzhalter). Funktional unkritisch. Klassen-Score **B / P3**.
