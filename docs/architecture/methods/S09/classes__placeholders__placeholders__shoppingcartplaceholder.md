# shoppingcartplaceholder — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/shoppingcartplaceholder.php` · **LOC:** 108 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`shoppingcartplaceholder` erweitert `\mod_booking\placeholders\placeholder_base` und rendert beim Auftreten eines `local_shopping_cart`-Zahlungs-Events eine Checkout-Erfolgsuebersicht (Preis, Rabatt, Historie, Beleg-URL). Datenquelle ist nicht die DB, sondern die im Regel-JSON (`$rulejson`) transportierte Event-Payload. Persistenz: keine. Kollaborateure: `$OUTPUT` (Mustache-Template `local_shopping_cart/checkout_success_content`), `core_plugin_manager` fuer die Verfuegbarkeitspruefung. Zustandslos (nur statische Methoden).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, prueft auf ein `\local_shopping_cart\event\payment_confirmed`-Event und rendert daraus die Checkout-Erfolgs-Zusammenfassung. Bei fehlender/abweichender Payload wird ein leerer String zurueckgegeben.
- **Seiteneffekte:** `json_decode($rulejson)` (ueberschreibt den Parameter), inneres `json_decode($event->other->cart, true)`; Template-Render via `$OUTPUT->render_from_template(...)`. `&$text`/`&$params` ungenutzt.
- **Rueckgabe:** `string` — gerendertes HTML der Checkout-Erfolgsmeldung oder leerer String.
- **Bewertung:** B — defensive `?? null`-Defaults fuer optionale Felder; `$eventdata['price']` und `$eventdata['currency']` werden jedoch ohne Coalescing gelesen (Undefined-Key-Warning moeglich, falls die Cart-Payload diese Schluessel nicht traegt). Erweitert die Basis-Signatur um `$rulejson` als 10. Parameter.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Aktiviert den Platzhalter nur, wenn das Plugin `local_shopping_cart` installiert ist. **Seiteneffekte:** `core_plugin_manager::instance()->get_plugin_info('local_shopping_cart')`. **Rueckgabe:** `bool`. **Bewertung:** B — korrekte Plugin-Gate-Pruefung; kosmetisch ein verirrtes zweites Semikolon (`;`) auf eigener Zeile (No-op).

## Bewertungs-Resümee
Event-getriebener Platzhalter mit klarer Aufgabe und sinnvoller Plugin-Gate-Logik. Schwaechen sind nur Robustheits-Details (nicht-coalesced `price`/`currency`-Zugriffe) und ein harmloses Doppel-Semikolon. Klassen-Score **B / P3**.
