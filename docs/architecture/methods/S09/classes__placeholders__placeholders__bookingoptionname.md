# bookingoptionname — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookingoptionname.php` · **LOC:** 129 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookingoptionname` ist ein Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `[bookingoptionname]` durch den (praefigierten) Titel der Buchungsoption ersetzt. Besonderheit gegenueber den Link-Platzhaltern: er implementiert eine explizite **Loop-Prevention** ueber den statischen Cache `placeholders_info::$placeholders`, da der Titel selbst wiederum Platzhalter enthalten koennte und so eine Rekursion ausloesen wuerde. Keine eigene Persistenz. Kollaborateure: `singleton_service` (Settings + `get_title_with_prefix()`), `placeholders_info` (Cache/Loop-Marker), `format_string`. Drei statische Methoden.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den formatierten Optionstitel. Voraussetzung ist ein nicht-leeres `$userid` (sonst Fehlerstring). Settings werden via `singleton_service::get_instance_of_booking_option_settings($optionid)` geladen, `$cmid` ggf. aus `$settings->cmid` nachgezogen. Cache-Key `"$classname-$optionid-$userid"`.
- **Loop-Prevention:** Ist ein **nicht-numerischer** Wert gecached, gilt er als fertiger Titel und wird zurueckgegeben. Andernfalls wird der Slot als numerischer Marker belegt (`?? 1`). Beim Erststand (`=== 1`) wird der Marker auf `2` inkrementiert (markiert „in Bearbeitung"), der Titel via `format_string($settings->get_title_with_prefix())` erzeugt und der fertige String in den Cache geschrieben. Trifft ein rekursiver Aufruf waehrend der Bearbeitung (`=== 2`) ein, wird `get_string('loopprevention', ...)` zurueckgegeben, statt erneut abzusteigen.
- **Seiteneffekte:** Mutiert `placeholders_info::$placeholders[$cachekey]` mehrfach (Marker -> fertiger Titel); Singleton-Service-Zugriff.
- **Rueckgabe:** Formatierter Titel-String; `loopprevention`-String bei erkannter Rekursion; `sthwentwrongwithplaceholder` bei leerem `$userid`.
- **Bewertung:** B — die Marker-Maschinerie (numerisch = „in Arbeit", String = „fertig") ist korrekt und loest ein echtes Rekursionsproblem, aber dicht und nur ueber die Kommentare verstaendlich. `is_numeric`-Pruefung gegen den fertigen Titel ist robust, solange Titel nicht rein numerisch sind — ein rein numerischer Optionstitel wuerde faelschlich als Loop-Marker interpretiert und beim naechsten Lookup neu berechnet (kein Datenverlust, nur Cache-Miss).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. Konstant `true`.
- **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Markiert den Platzhalter als gueltig fuer Poll-URLs (Umfrage-Links). Konstant `true`.
- **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** A — triviale Konstante, ueberschreibt den (vermutlich `false`-defaultenden) Basis-Hook.

## Bewertungs-Resümee
Funktional der anspruchsvollste Platzhalter der Gruppe wegen der Loop-Prevention. Korrekt, aber die numerisch/string-basierte Marker-Heuristik ist subtil und hat einen theoretischen Edge-Case bei rein numerischen Titeln (Cache-Miss, kein Fehler). Klassen-Score **B / P3**.
