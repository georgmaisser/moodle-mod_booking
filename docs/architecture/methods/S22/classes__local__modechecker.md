# modechecker — Methoden-Doku
**Datei:** `classes/local/modechecker.php` · **LOC:** 188 · **Subsystem:** S22 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`modechecker` ist eine rein statische Utility-Klasse zur **Request-Modus-Erkennung**. Sie unterscheidet AJAX-, Webservice-, CLI- und „normale" Page-Requests, um daraus abzuleiten, ob beim Rendern eine Sonderbehandlung der Booking-Buttons noetig ist (z.B. „nur auf Detailseite buchbar"). Kein State, keine Persistenz. Datenquellen: `$_SERVER`-Header, `$_REQUEST`, Moodle-Konstanten (`AJAX_SCRIPT`, `WS_SERVER`, `CLI_SCRIPT`, `PHPUNIT_TEST`), `optional_param` (wsfunction/wstoken/info), `$PAGE->url` und `get_config`. Kollaborateur: `mod_booking\price::return_user_to_buy_for`. Hinweis: Die Einordnung in S22 (db_layer) stammt aus dem CLASS_INDEX; inhaltlich ist die Klasse eine plattform-/request-nahe Util ohne DB-Bezug. Der Doc-Block „cartstore class handles the in and out of the cache" ist erneut Copy-Paste und falsch.

## Methoden

### `public static function is_ajax_or_webservice_request()` — public static
- **Zweck:** Sammel-Predikat: true bei AJAX- ODER Webservice-Request ODER `PHPUNIT_TEST`. **Seiteneffekte:** keine (delegiert). **Rueckgabe:** bool. **Bewertung:** B — das pauschale `|| PHPUNIT_TEST` zwingt Tests immer in den „ajax/ws"-Zweig, was Testbarkeit des Page-Pfades verhindert.

### `private static function is_ajax_request()` — private static
- **Zweck:** Erkennt AJAX via `X-Requested-With: xmlhttprequest`-Header, `$_REQUEST['ajax']` oder `AJAX_SCRIPT`. **Seiteneffekte:** liest `$_SERVER`/`$_REQUEST`. **Rueckgabe:** bool. **Bewertung:** B — Standard-Heuristik; `$_REQUEST['ajax']` ist client-setzbar, aber nur fuer Render-Verhalten relevant (keine Sicherheitsentscheidung).

### `public static function use_special_details_page_treatment()` — public static
- **Zweck:** Kernentscheidung: liefert true, wenn KEINE Sonderbehandlung noetig ist (Buttons direkt rendern), false, wenn im „bookonlyondetailspage"-Modus auf die Detailseite verlinkt werden soll. Beruecksichtigt CLI/Cron (immer true), nicht gesetzte `$PAGE->url` (true), Ausnahme der optionview-/cashier-Pfade und ob der aktuelle Request bookit/pre-booking-page ist; bucht-fuer-anderen-User wird ebenfalls auf true gefuehrt. **Seiteneffekte:** `global $PAGE, $USER`; `$PAGE->has_set_url()`/`$PAGE->url->out_omit_querystring()`; `get_config('booking','bookonlyondetailspage')`; `price::return_user_to_buy_for()`. **Rueckgabe:** bool. **Bewertung:** C — stark verschachtelte Bedingungslogik (mehrfach negiertes `!(... || ...)` plus geschachtelte AND/OR), schwer zu verifizieren; defensive Guards (CLI, `has_set_url`, `method_exists`) sind sinnvoll, aber die Methode buendelt zu viele orthogonale Faelle. Funktional plausibel, hohe kognitive Last.

### `public static function is_mod_booking_bookit()` — public static
- **Zweck:** Erkennt den `mod_booking_bookit`-WS-Aufruf via `info`- oder `wsfunction`-Param. **Seiteneffekte:** `optional_param`. **Rueckgabe:** bool. **Bewertung:** B — `@return [type]`-Phpdoc ist ein unausgefuellter Platzhalter (Lint-Schuld), funktional ok.

### `public static function is_load_pre_booking_page()` — public static
- **Zweck:** Erkennt den `mod_booking_load_pre_booking_page`-WS-Aufruf analog. **Seiteneffekte:** `optional_param`. **Rueckgabe:** bool. **Bewertung:** B — Duplikat von `is_mod_booking_bookit` bis auf den String-Vergleich; ebenfalls `@return [type]`-Platzhalter.

### `private static function is_webservice_request()` — private static
- **Zweck:** Erkennt Webservice-Kontext via `wsfunction`/`wstoken`-Param, `WS_SERVER`-Konstante oder `MoodleMobile`-User-Agent. **Seiteneffekte:** `optional_param`, liest `$_SERVER['HTTP_USER_AGENT']`. **Rueckgabe:** bool. **Bewertung:** B — User-Agent-Sniffing ist faelschbar, dient hier aber nur dem Render-Verhalten.

## Bewertungs-Resümee
Pragmatische Request-Modus-Util, deren Schwaeche die Komplexitaet von `use_special_details_page_treatment` ist (tief verschachtelte, mehrfach negierte Bedingungen). Daneben kosmetische Schuld: irrefuehrender Klassen-Doc-Block, `@return [type]`-Platzhalter und die fast identischen `is_mod_booking_bookit`/`is_load_pre_booking_page`. Keine Datenverlust-/Sicherheitsbefunde (alle Heuristiken steuern nur Render-/Routing-Verhalten, keine Autorisierung). Klassen-Score **C / P3**.
