# duration — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/duration.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`duration` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den Platzhalter `{duration}` durch das `duration`-Feld der Buchungsoption ersetzt. Stateless; reine statische API. Persistenz: keine eigene; liest `booking_option_settings->duration` via `singleton_service`. Request-scoped Memo via `placeholders_info::$placeholders` mit optionsbasiertem Cachekey. Kollaborateure: `singleton_service`, `placeholders_info`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den `duration`-Wert der Buchungsoption als Platzhalterwert; cacht das Ergebnis request-scoped pro Option.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)`; liest/schreibt `placeholders_info::$placeholders["$classname-$optionid"]`.
- **Rueckgabe:** mixed — der `duration`-Wert (typischerweise int/string); bei fehlender `optionid` `get_string('sthwentwrongwithplaceholder', ...)`.
- **Bewertung:** B — Sauberes, einfaches Cache-on-load-Muster ohne die Loop-Praeventions-Komplexitaet von `description`. `$settings->duration` wird ungeformt (roher Sekunden-/Wert) durchgereicht; ggf. nicht menschenlesbar, je nach Inhalt des Felds — fuer den Platzhalterzweck aber vom Aufrufer so vorgesehen.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### Triviale Properties
Keine eigenen Properties (rein statisch).

## Bewertungs-Resümee
Kompakter Platzhalter mit unkompliziertem request-scoped Memo. Keine funktionalen Maengel; lediglich wird der Rohwert `duration` ohne Formatierung ausgegeben. Klassen-Score **B / P3**.
