# bookit — Methoden-Doku
**Datei:** `classes/external/bookit.php` · **LOC:** 142 · **Subsystem:** S11 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`bookit` ist die zentrale Buchungs-Webservice-Funktion (`external_api`): Sie bucht eine Option (`area='option'`) bzw. Subbooking (`area`-Praefix `subbooking`) und liefert die neu zu rendernden Button-Templatedaten zurueck. Der eigentliche Buchungsschritt ist an `booking_bookit::bookit` delegiert; die Funktion selbst kuemmert sich um Routing nach `area`, das Re-Rendering und die Invalidierung des user-spezifischen Answers-Cache. Kollaborateure: `booking_bookit` (bookit + render_bookit_template_data), `singleton_service` (option_settings), `subbookings_info`, `price` (set_bookforuser/return_user_to_buy_for), MUC-Cache `bookingoptionsanswers`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `area` (PARAM_RAW), `itemid` (PARAM_INT), `userid` (PARAM_INT), `data` (PARAM_RAW). **Seiteneffekte:** keine. **Bewertung:** A.

### `public static function execute(string $area, int $itemid, int $userid, string $data): array` — public static
- **Zweck:** Fuehrt die Buchung aus, ermittelt die zugehoerigen Option-Settings (direkt bei `option`, ueber Subbooking→optionid bei `subbooking*`), rendert die Button-Templatedaten und invalidiert den Answers-Cache des betroffenen Users. **Seiteneffekte:** `validate_parameters`, `require_login()`; `booking_bookit::bookit(...)`; `singleton_service::get_instance_of_booking_option_settings`; `subbookings_info::get_subbooking_by_area_and_id`; `price::set_bookforuser($userid)`; `booking_bookit::render_bookit_template_data`; `cache::make('mod_booking','bookingoptionsanswers')` lesen + `unset($bacache->usercache[$user->id])`. **Rueckgabe:** `['status','message','template','json']`. **Bewertung:** C — zwei Punkte:
  - **Cache-Invalidierung wirkungslos (Z.109–119):** Es wird `$bacache = $cache->get($cachekey)` gelesen, dann `unset($bacache->usercache[$user->id])` auf der lokalen Kopie ausgefuehrt, aber **nie `$cache->set($cachekey, $bacache)`** zurueckgeschrieben. Bei MUC-Stores, die unserialisierte Kopien liefern (application/persistent), bleibt der Cache-Eintrag unveraendert — die beabsichtigte Invalidierung greift nicht zuverlaessig. (Funktioniert nur zufaellig bei by-reference-Stores.)
  - **Raw-Param-Mix (Z.91/104):** Verzweigung/`set_bookforuser` nutzen `$area`/`$userid` statt `$params[...]`; funktional gleich (validate_parameters aendert Int/Raw-Werte nicht), aber inkonsistent.
  - Kein `validate_context()` (nur `require_login`); Kontextpruefung haengt an `booking_bookit::bookit`/Availability-Conditions.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt Ergebnis (`status` int, `message`/`json` raw, `template` text, jeweils mit Defaults). **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Korrekt strukturierter Buchungs-Endpunkt mit sauberer area-Verzweigung und Re-Render-Logik. Hauptmangel ist die nicht zurueckgeschriebene Cache-Invalidierung des user-spezifischen Answers-Cache (latenter Stale-Cache-Bug), dazu der kosmetische Raw-vs-validated-Param-Mix und das Fehlen einer expliziten Kontext-Validierung. Klassen-Score **B / P2**.
