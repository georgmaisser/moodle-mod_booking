# eventtype — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/eventtype.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`eventtype` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den auf Booking-Instanz-Ebene konfigurierten `eventtype` als Platzhalterwert liefert. Im Gegensatz zu `enddate`/`endtime` (optionsbasiert) arbeitet sie cmid-basiert: der Wert ist fuer alle Optionen einer Instanz gleich. Keine eigene Persistenz; request-lokaler Memo-Cache. Kollaborateur: `singleton_service::get_instance_of_booking_settings_by_cmid()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert `eventtype` der Booking-Instanz zum gegebenen `$cmid`. **Seiteneffekte:** Lese-/Schreib-Memo `placeholders_info::$placeholders["$classname-$cmid"]` (Cachekey korrekt cmid-basiert, da instanzinvariant), sonst `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)`. Ohne `$cmid` Fehlerstring `sthwentwrongwithplaceholder`. **Rueckgabe:** `$bookingsettings->eventtype` (string) bzw. Fehlerstring; kein deklarierter Rueckgabetyp. **Bewertung:** B — sauberes Memo-Pattern. Der Wert `$bookingsettings->eventtype` wird ohne Null-/Empty-Fallback durchgereicht; ist kein Eventtype gesetzt, wird ein leerer/`null`-Wert gecached und ausgegeben (kein Fehler, aber leerer Platzhalter). Funktional unkritisch.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Schmaler, korrekter cmid-basierter Platzhalter mit passend gewaehltem Cachekey. Einzige Anmerkung ist das Fehlen eines Leer-Fallbacks fuer nicht gesetzten `eventtype`. Keine funktionalen Maengel. Klassen-Score **B / P3**.
