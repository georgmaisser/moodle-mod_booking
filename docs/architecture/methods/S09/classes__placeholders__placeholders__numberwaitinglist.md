# numberwaitinglist — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/numberwaitinglist.php` · **LOC:** 102 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`numberwaitinglist` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der die Groesse der Warteliste (`maxoverbooking`) einer Buchungsoption liefert. Strukturell identisch zu `numberparticipants`, nur dass das Settings-Feld `maxoverbooking` statt `maxanswers` gelesen wird. Keine eigene Persistenz; nutzt `singleton_service` fuer die Settings und den Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `placeholders_info`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt die maximale Wartelisten-Groesse (`maxoverbooking`) der Option zurueck; ist sie leer/0, wird stattdessen der Sprachstring `unlimitedplaces` geliefert. **Seiteneffekte:** liest die (gecachten) Settings ueber den Singleton-Service; schreibt das Ergebnis in den statischen Request-Cache `placeholders_info::$placeholders["$classname-$optionid"]`. Keine DB-Schreibvorgaenge. Bei `optionid == 0` wird der Fehlerstring `sthwentwrongwithplaceholder` zurueckgegeben. **Rueckgabe:** Zahl (aus den Settings) oder lokalisierter String. **Bewertung:** B — wie `numberparticipants`; korrekt mit Cache und 0-Guard. Anmerkung: `maxoverbooking` als „Anzahl Wartelisten-Plaetze" zu interpretieren entspricht der Datendefinition (zusaetzlich buchbare Plaetze), ist also semantisch passend.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Read-Only-Platzhalter analog zu `numberparticipants` (Code-Duplikat bis auf das Settings-Feld). Cache und Fehler-Guard vorhanden, keine funktionalen Schwaechen. Klassen-Score **B / P3**.
