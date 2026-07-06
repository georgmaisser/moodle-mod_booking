# selflearningcourse — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/selflearningcourse.php` · **LOC:** 119 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
Datei deklariert die Platzhalter-Klasse `sellearningcourse` (Achtung: Klassenname weicht vom Dateinamen `selflearningcourse` ab) als Erweiterung von `\mod_booking\placeholders\placeholder_base`. Sie liefert beim Versand von Booking-Mails/Texten den Wert des Option-Feldes `selflearningcourse` (Self-Learning-Course-Information). Keine eigene Persistenz; liest aus `booking_option_settings` ueber den `singleton_service` und nutzt den Prozess-Cache `placeholders_info::$placeholders` als Memoization-Schicht. Kollaborateure: `singleton_service`, `placeholders_info`, Lang-Strings. Klasse ist zustandslos (nur statische Methoden).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den `selflearningcourse`-Wert der Option als String fuer Platzhalter-Ersetzung. Ohne `$optionid` wird ein Fehler-String (`sthwentwrongwithplaceholder`) zurueckgegeben.
- **Seiteneffekte:** Leitet den Kurznamen via `get_called_class()` ab; liest/schreibt den statischen Prozess-Cache `placeholders_info::$placeholders["$classname-$optionid"]`; holt `booking_option_settings` ueber `singleton_service::get_instance_of_booking_option_settings($optionid)` (Singleton, kein direkter DB-Hit). `&$text`/`&$params` werden als Referenz uebergeben aber nicht mutiert.
- **Rueckgabe:** `string` — der Self-Learning-Course-Wert, leerer String wenn `$settings->selflearningcourse` nicht gesetzt, oder Fehler-Lang-String bei fehlender `$optionid`.
- **Bewertung:** B — saubere Cache-on-read-Memoization; Signatur traegt viele ungenutzte Parameter (Interface-Konformitaet zur `placeholder_base`).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter ueberhaupt aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Markiert den Platzhalter als fuer Pollurl-Texte verwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional schlanker, korrekt memoisierter Field-Platzhalter. Der dominierende Mangel ist der Klassenname-Datei-Mismatch (`sellearningcourse` vs. `selflearningcourse.php`), der bei Moodle-PSR-Autoloading bzw. namensbasierter Platzhalter-Aufloesung zu einem Class-not-found fuehren kann (siehe Findings). Klassen-Score **C / P2**.
