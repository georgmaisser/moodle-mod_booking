# semester — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/semester.php` · **LOC:** 105 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`semester` (Platzhalter-Variante, nicht zu verwechseln mit dem Domaenenobjekt `mod_booking\semester`) erweitert `\mod_booking\placeholders\placeholder_base` und liefert den Anzeigenamen des Semesters einer Booking-Option im Format `"name (identifier)"`. Persistenz: keine eigene; liest `booking_semesters` direkt via `$DB` und die `semesterid` aus `booking_option_settings` (per `singleton_service`). Memoization ueber den Prozess-Cache `placeholders_info::$placeholders`. Zustandslos (nur statische Methoden).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den formatierten Semester-Namen der Option. Ohne `$optionid` wird der Fehler-String `sthwentwrongwithplaceholder` zurueckgegeben.
- **Seiteneffekte:** Kurznamen-Ableitung via `get_called_class()`; Prozess-Cache-Read/Write `placeholders_info::$placeholders["$classname-$optionid"]`; `singleton_service::get_instance_of_booking_option_settings($optionid)` fuer `semesterid`; **direkter DB-Read** `$DB->get_record('booking_semesters', ['id' => $semesterid])`. `&$text`/`&$params` ungenutzt.
- **Rueckgabe:** `string` — `"<name> (<identifier>)"`, oder Fehler-Lang-String bei fehlender `$optionid`.
- **Bewertung:** C — ungeschuetzter Zugriff `$record->name` / `$record->identifier`: wenn `semesterid` 0/leer ist oder kein Semester-Record existiert, liefert `get_record` `false` und der Property-Zugriff erzeugt eine Warning/Fehler (siehe Findings). `get_record` statt `get_field` fuer zwei Spalten ist akzeptabel.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter Field-Platzhalter mit korrekter Memoization, aber unsicherer Record-Behandlung: ein fehlendes/0-Semester fuehrt zu einem Property-Zugriff auf `false`. Redundante zweite `get_called_class()`-Ableitung im else-Zweig. Klassen-Score **C / P2**.
