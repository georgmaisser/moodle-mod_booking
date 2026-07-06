# title — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/title.php` · **LOC:** 90 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`title` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den Titel einer Buchungsoption liefert. Sie enthaelt keine eigene Logik mehr, sondern ist ein reiner Alias auf `bookingoptionname`, um Code-Duplikation zu vermeiden. Keine Persistenz, keine eigenen Kollaborateure ausser der delegierten Klasse `bookingoptionname`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE): string` — public static
- **Zweck:** Liefert den Optionstitel, indem alle Parameter unveraendert an `bookingoptionname::return_value(...)` durchgereicht werden. **Seiteneffekte:** keine eigenen; alle Effekte (Settings-Lookup, Cache) liegen in `bookingoptionname`. **Rueckgabe:** der von `bookingoptionname` gelieferte Titel-String (deklarierter `: string`-Rueckgabetyp). **Bewertung:** A — saubere, bewusst dokumentierte Delegation zur Vermeidung von Duplikation.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Minimaler Alias-Platzhalter, der `bookingoptionname` wiederverwendet. Anders als das Original deklariert `return_value` hier `: string`. Keine funktionalen Maengel. Klassen-Score **B / P3**.
