# enddate — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/enddate.php` · **LOC:** 103 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`enddate` ist eine Platzhalter-Klasse (`extends placeholder_base`) im Messaging-/Mail-Templating: sie ersetzt den `enddate`-Platzhalter durch das formatierte Enddatum der Buchungsoption. Keine eigene Persistenz; liest die Option ueber den Singleton-Cache und nutzt einen prozess-lokalen Memo-Cache (`placeholders_info::$placeholders`) gegen Mehrfachberechnung im selben Request. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()`, `userdate()`, `get_string('strftimedate','langconfig')`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert `courseendtime` der Option als per `strftimedate` formatiertes Datum. **Seiteneffekte:** Lese-Memo `placeholders_info::$placeholders["$classname-$optionid"]` (Treffer → sofortige Rueckgabe), sonst `singleton_service::get_instance_of_booking_option_settings($optionid)` und Schreiben des Memos. Ohne `$optionid` wird statt eines Werts der Fehlerstring `get_string('sthwentwrongwithplaceholder', ...)` zurueckgegeben. Klassenname wird via `substr(strrchr(get_called_class(),'\\'),1)` aus dem FQCN extrahiert (Cachekey + Fehlertext). Ist `courseendtime` falsy (0/leer), wird Leerstring geliefert. **Rueckgabe:** formatiertes Datum (string) bzw. Leerstring bzw. Fehlerstring; kein deklarierter Rueckgabetyp, aber alle Pfade liefern String. **Bewertung:** B — sauberes Memo-Pattern; Cachekey ist `classname-optionid` (user-unabhaengig, korrekt da Option-Datum nutzerinvariant). Minimal: `&$text`/`&$params` werden ungenutzt by-ref entgegengenommen (Signaturvertrag der Familie).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Schmaler, korrekter Datums-Platzhalter mit request-lokalem Memo-Cache und Singleton-Lookup. Keine funktionalen Maengel. Klassen-Score **B / P3**.
