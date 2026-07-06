# qrid — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/qrid.php` · **LOC:** 105 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`qrid` ist ein Platzhalter-Field (`extends \mod_booking\placeholders\placeholder_base`), das die Moodle-`userid` als extern gerenderten QR-Code-`<img>` (api.qrserver.com) liefert. Keine eigene Persistenz; nutzt `singleton_service::get_instance_of_booking_option_settings()` nur zum Auffuellen von `cmid` (ohne weitere Verwendung) sowie den Request-Memo `placeholders_info::$placeholders`. Rein statisch, vom Placeholder-Resolver je Mail-Render aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Erzeugt aus `$userid` einen QR-Code-`<img>` (URL-kodiert per `rawurlencode`); bei fehlendem `$userid` einen lokalisierten Fehlerstring. **Seiteneffekte:** bei leerem `$cmid` `singleton_service::get_instance_of_booking_option_settings($optionid)` (das Ergebnis-`cmid` wird jedoch nirgends weiterverwendet — toter Aufruf), Lesen und Schreiben des Memo `placeholders_info::$placeholders["$classname-$userid"]`. **Rueckgabe:** QR-`<img>`-String oder Fehlerstring. **Bewertung:** B — korrekt geschluesselter (per `$userid`) und befuellter Memo; der `cmid`-Lookup ist funktional ueberfluessig. Datenschutzhinweis: die rohe `$userid` wird an den Drittanbieter `api.qrserver.com` uebertragen.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Platzhalter aufgeloest werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — triviales Vertrags-Gate.

## Bewertungs-Resümee
Kompakter, korrekt memoisierter Platzhalter. Schwaechen: ein folgenloser `cmid`-Lookup (unnoetige Singleton-Aufloesung) und die Uebertragung der internen `userid` an einen externen QR-Dienst. Funktional unkritisch. Klassen-Score **B / P3**.
