# startdate — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/startdate.php` · **LOC:** 103 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`startdate` ist der Mail-/Text-Platzhalter (`[startdate]`), der das Startdatum einer Buchungsoption (Kursbeginn) als lokalisierten Datums-String liefert. Sie erbt von `\mod_booking\placeholders\placeholder_base`. Persistenz hat sie keine; gelesen wird ueber den `singleton_service` aus `booking_option_settings` (Feld `coursestarttime`). Als Performance-Schicht nutzt sie den prozessweiten Request-Memo `placeholders_info::$placeholders` (statisches Array), gekeyt nach `Klassenname-optionid`. Kollaborateure: `singleton_service`, `placeholders_info`, `userdate()`/`get_string()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt `coursestarttime` der Option als per `strftimedate` formatiertes Datum zurueck. **Seiteneffekte:** Lesezugriff via `singleton_service::get_instance_of_booking_option_settings($optionid)`; schreibt das Ergebnis in den statischen Memo `placeholders_info::$placeholders[$cachekey]`. Bei Cache-Treffer Fruehruckgabe ohne Settings-Load. Ist `$optionid` leer, wird die Fehler-Sprachzeichenkette `sthwentwrongwithplaceholder` zurueckgegeben. Der Klassenname wird via `get_called_class()` reflektiert (korrektes spaetes statisches Binding). **Rueckgabe:** formatiertes Datum, leerer String (wenn `coursestarttime` falsy), oder Fehlertext. **Bewertung:** B — saubere Memoisierung, korrektes Caching (anders als die Schwester `starttime`); leerer String bei fehlendem Startdatum ist als Platzhalter-Output akzeptabel.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Vertrags-Hook, ob der Platzhalter verarbeitet wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Standard-Field-Platzhalter mit korrekter Request-Memoisierung. Unkritisch. Klassen-Score **B / P3**.
