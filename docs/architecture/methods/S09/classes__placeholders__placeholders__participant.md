# participant — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/participant.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`participant` ist ein konkreter Platzhalter (`extends \mod_booking\placeholders\placeholder_base`) im Mail-/Template-Platzhaltersystem. Er ersetzt `{participant}` durch den vollen Namen des Empfaengers (`fullname()`). Reine statische Helferklasse ohne eigene Persistenz; die Daten kommen aus dem `singleton_service`-User-Cache. In-Request-Memoisierung erfolgt ueber das statische Array `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_user`, `placeholders_info`, Sprachstrings.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den vollen Namen des Users `$userid`. Leitet den Klassennamen via `get_called_class()` ab und bildet den per-Request-Cachekey `"$classname-$userid"`. Bei Treffer wird der memoisierte Wert zurueckgegeben, sonst per `singleton_service::get_instance_of_user($userid)` + `fullname()` ermittelt und in `placeholders_info::$placeholders` abgelegt. **Seiteneffekte:** Schreibt in das statische Memo-Array `placeholders_info::$placeholders`; liest den User ueber den Singleton-Cache. Bei leerem `$userid` Fallback auf den Sprachstring `sthwentwrongwithplaceholder`. **Rueckgabe:** `string` — Anzeigename oder Fehler-String. **Bewertung:** B — sauber memoisiert; `&$text`/`&$params` werden by-ref deklariert aber nicht genutzt (geerbte Signatur). Kein Berechtigungscheck auf den User, was hier dem Aufrufkontext (Mail an genau diesen User) entspricht.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter ueberhaupt aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — trivialer Gate-Hook.

## Bewertungs-Resümee
Schlanker Namens-Platzhalter mit korrekter per-Request-Memoisierung ueber den geteilten `placeholders_info::$placeholders`-Speicher. Die breite, by-ref-haltige Signatur ist dem gemeinsamen Platzhalter-Kontrakt geschuldet, nicht klassenspezifisch. Funktional unkritisch. Klassen-Score **B / P3**.
