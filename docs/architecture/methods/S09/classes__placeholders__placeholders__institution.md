# institution — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/institution.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`institution` ist eine Platzhalter-Klasse im Messaging-/Placeholder-Subsystem; sie erweitert `\mod_booking\placeholders\placeholder_base` und ersetzt den Platzhalter `{institution}` in Instanz-/Options-/Mail-Texten durch das `institution`-Feld der Buchungsoption. Keine eigene Persistenz; liest ueber `singleton_service::get_instance_of_booking_option_settings()` (gecachte Option-Settings) und nutzt den prozessweiten Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `placeholders_info`, `singleton_service`, `placeholder_base`, `lib.php` (Konstanten wie `MOD_BOOKING_DESCRIPTION_WEBSITE`).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den Wert fuer `{institution}`: bei vorhandener `$optionid` das `institution`-Feld der Option, sonst eine Fehlerzeichenkette. **Seiteneffekte:** Liest/schreibt `placeholders_info::$placeholders["institution-$optionid"]` (Request-Memo); `singleton_service::get_instance_of_booking_option_settings($optionid)`. **Rueckgabe:** string mit dem Institutionsnamen bzw. `get_string('sthwentwrongwithplaceholder', ...)` bei fehlender Option. **Bewertung:** B — saubere Memoisierung pro Option; die breite Signatur (`$text`/`$params` als Referenz, diverse ungenutzte Parameter) folgt dem einheitlichen `placeholder_base`-Kontrakt, hier werden die meisten Parameter nicht verwendet. Kein Rueckgabe-Typehint (geerbter Kontrakt).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Schaltet den Platzhalter generell scharf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Schmale, gut lesbare Platzhalter-Klasse nach dem Standardmuster des Subsystems: Memo-Lookup, Option-Settings-Zugriff, Fehlerstring-Fallback. Funktional unkritisch; der einzige nennenswerte Punkt ist die generische, weitgehend ungenutzte Signatur (Kontraktvorgabe). Klassen-Score **B / P3**.
