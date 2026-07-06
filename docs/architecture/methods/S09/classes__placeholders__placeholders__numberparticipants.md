# numberparticipants — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/numberparticipants.php` · **LOC:** 102 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`numberparticipants` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der die maximale Teilnehmerzahl (`maxanswers`) einer Buchungsoption liefert. Keine eigene Persistenz; liest ueber den `singleton_service` die `booking_option_settings` und nutzt den prozessweiten Request-Cache `placeholders_info::$placeholders` (statisches Array), um den Wert pro `classname-optionid` nur einmal aufzuloesen. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `placeholders_info`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt die maximale Platzzahl der Option zurueck; ist `maxanswers` leer/0, wird stattdessen der Sprachstring `unlimitedplaces` geliefert. **Seiteneffekte:** liest die (gecachten) Settings ueber den Singleton-Service; schreibt das Ergebnis in den statischen Request-Cache `placeholders_info::$placeholders["$classname-$optionid"]`. Keine DB-Schreibvorgaenge. Bei `optionid == 0` wird der Fehlerstring `sthwentwrongwithplaceholder` zurueckgegeben (kein Settings-Aufruf). **Rueckgabe:** Zahl (als int aus den Settings) oder lokalisierter String. **Bewertung:** B — sauberer Read-Only-Pfad mit Cache und 0-Guard; der Cachekey laesst userid/cmid bewusst weg (Wert ist pro Option gleich), was korrekt ist. Rueckgabetyp gemischt (int vs. string) ist im String-Interpolations-Kontext der Mails unkritisch.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter Platzhalter, der `maxanswers` ueber den Singleton-Service aufloest und per Request-Cache dedupliziert. Fehlende `optionid` ist abgefangen. Keine funktionalen Schwaechen. Klassen-Score **B / P3**.
