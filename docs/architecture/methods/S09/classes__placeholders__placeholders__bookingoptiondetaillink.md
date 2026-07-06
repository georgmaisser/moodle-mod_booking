# bookingoptiondetaillink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookingoptiondetaillink.php` · **LOC:** 126 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookingoptiondetaillink` ist ein Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `[bookingoptiondetaillink]` durch einen HTML-Link auf die Detailseite einer Buchungsoption (`/mod/booking/optionview.php`) ersetzt. Im Gegensatz zu `bookinglink` haengt der Link Rueckkehr-Parameter (`returnto`/`returnurl`) an, abhaengig davon, ob der Request ein normaler Seitenaufruf oder ein AJAX/Webservice-Aufruf ist. Keine eigene Persistenz; nutzt den statischen Request-Cache `placeholders_info::$placeholders`. Kollaborateure: `singleton_service`, `modechecker` (Request-Typ), globales `$PAGE` (aktuelle URL), `moodle_url`/`html_writer`, `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Baut den Detailseiten-Link. Cache-Key `"$classname-$optionid"`, bei Treffer Sofort-Return. Sonst Settings via `singleton_service::get_instance_of_booking_option_settings($optionid)`. Nur wenn `$settings->cmid` gesetzt ist: Ermittlung der `returnurl` — bei normalem Request `$PAGE->url->out()`, bei AJAX/Webservice (`modechecker::is_ajax_or_webservice_request()`) Fallback `'/'`. Der `moodle_url` auf `optionview.php` traegt `optionid` (aus `$settings->id`), `cmid`, `userid`, `returnto='url'` und `returnurl`.
- **Seiteneffekte:** Liest globales `$PAGE`; schreibt Ergebnis in `placeholders_info::$placeholders[$cachekey]`; Singleton-Service-Zugriff.
- **Rueckgabe:** HTML-`<a>`-Link; bei leerem `$optionid` Fehlerstring `sthwentwrongwithplaceholder`; bei fehlendem `$settings->cmid` der leere String (gecached).
- **Bewertung:** B — sinnvolle AJAX-Unterscheidung fuer die Rueckkehr-URL. Schwaechen: `$timeformat` (Z.82) wird berechnet und nie genutzt (toter Code); der Cache ist nur nach `optionid` geschluesselt, obwohl die `returnurl` vom aktuellen `$PAGE` abhaengt — derselbe Platzhalter kann innerhalb eines Requests fuer unterschiedliche Quellseiten denselben (zuerst gecachten) Link liefern. In der Praxis (ein Mail-Render-Durchlauf pro Request) unkritisch.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. Konstant `true`.
- **Seiteneffekte:** keine.
- **Rueckgabe:** `true`.
- **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Korrekter Detail-Link-Platzhalter mit durchdachter AJAX/Webservice-Rueckkehrlogik. Reale Makel: ungenutzte `$timeformat`-Zeile und ein nur nach `optionid` geschluesselter Cache trotz `$PAGE`-abhaengiger `returnurl`. Funktional unkritisch. Klassen-Score **B / P3**.
