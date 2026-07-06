# price — Methoden-Doku

**Datei:** `classes/price.php` · **LOC:** 1341 · **Subsystem:** S05 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S05_pricing_shoppingcart.md)

## Klassenueberblick
`mod_booking\price` ist die zentrale Preis-Engine des Plugins: sie verwaltet Preiskategorien, baut die Preis-Felder in mforms (Buchungsoption/Subbooking), wendet die JSON-Preisformel (Faktoren timeslot/customfield/entity/unit) an, persistiert Preise in `booking_prices`, ermittelt den nutzerspezifischen Preis (inkl. Kampagnen-Override) und cached aggressiv ueber MUC. Persistenz: Tabellen `booking_prices`, `booking_pricecategories`, `booking_subbooking_options`; Caches `cachedprices`, `cachedpricecategories`, `bookforuser`. Hauptkollaborateure: `singleton_service` (Settings/User/PriceCategory-Caches), `booking_option::trigger_updated_event`, `dates_handler`, `campaigns_info`/`booking_campaign`, `customformstore`, `entitiesrelation_handler`, `local_shopping_cart\shopping_cart`. Die Klasse mischt UI-Form-Logik, Geschaeftslogik (Formel), Persistenz, Caching und Eventing — klassische God-Klasse mit gemischter Verantwortung und auffaelliger Duplizierung (form- vs. settings-Varianten).

## Methoden

### `destroy_singletons(): void` — public static
- **Zweck:** Setzt statischen Zustand `$bookforuserid` zurueck (Test-/Request-Reset).
- **Seiteneffekte:** Schreibt statische Property.
- **Aufrufkette:** Test-Teardown / Singleton-Reset-Pfade.
- **Bewertung:** A — trivial.

### `__construct(string $area, int $itemid = 0)` — public
- **Zweck:** Laedt alle aktiven Preiskategorien (sortiert je nach `pricecategorychoosehighest`) und merkt sich area/itemid.
- **Seiteneffekte:** DB-Read `booking_pricecategories` (raw SQL), `get_config`.
- **Aufrufkette:** ueberall wo Preis-Form gebaut/gespeichert wird (option_form, subbookings).
- **Bewertung:** B — Konstruktor mit DB-Query und String-interpolierter ORDER-BY-Richtung (price.php:89); die Sortierrichtung stammt aus einer Whitelist (`ASC`/`DESC`), daher kein Injection-Risiko, aber Konstruktor-DB-Last.

### `add_price_to_mform(MoodleQuickForm &$mform, bool $noformula = false, bool $canbeblockedbyconfigsetting = true)` — public
- **Zweck:** Fuegt Header, `useprice`-Checkbox, je Kategorie eine Preis-Gruppe und (optional) die Preisformel-Felder zum mform hinzu.
- **Seiteneffekte:** Mutiert mform; mehrfach `get_config` (priceisalwayson, defaultpriceformula, globalcurrency); `is_json`.
- **Aufrufkette:** option_form / subbooking-Forms.
- **Bewertung:** C — ~100 LOC, gemischte UI-Verantwortung, viele `get_config`-Calls, Inline-HTML (FA-Icon, alert-div). Tote lokale Variable `$useprice = false` (price.php:134) wird gesetzt, aber nie gelesen.

### `set_data(stdClass &$data)` — public
- **Zweck:** Befuellt Form-Daten mit existierenden Preisen (pro Kategorie) und Formel-Settings; setzt `useprice=1` beim Import.
- **Seiteneffekte:** DB-Read `booking_prices` (pro Kategorie, N+1-Schleife), `get_config`, `singleton_service::get_instance_of_booking_option_settings`.
- **Aufrufkette:** Form-Definition-after-data.
- **Bewertung:** C — N+1 `get_field` in der Kategorie-Schleife (price.php:227); gemischte Logik Preis + Formel.

### `calculate_price_from_form(stdClass $fromform, string $priceformula, string $pricecategoryidentifier)` — public static
- **Zweck:** Berechnet Preis aus JSON-Formel basierend auf Formdaten (Basispreis * Faktoren timeslot/customfield/entity + Unit-Faktor + optional Rundung).
- **Seiteneffekte:** DB-Read `booking_pricecategories`; `get_config` (applyunitfactor, roundpricesafterformula); ruft `dates_handler::prepare_day_info`, `apply_*`-Helfer.
- **Aufrufkette:** von `save_from_form`; ruft die privaten apply-Helfer.
- **Bewertung:** D — ~68 LOC, fast 1:1-Duplikat von `calculate_price_with_bookingoptionsettings` (DRY-Verstoss, price.php:275-343 vs. 355-428), tiefe Verschachtelung, mehrfaches Return-0-Magic. Korrekt: Schluessel-Extraktion via `key((array) $formulacomponent)` (price.php:313) ist PHP-8-sicher.

### `calculate_price_with_bookingoptionsettings($bookingoptionsettings, string $priceformula, string $pricecategoryidentifier)` — public static
- **Zweck:** Wie `calculate_price_from_form`, aber Quelle ist `booking_option_settings` statt Formdaten.
- **Seiteneffekte:** identisch zu `calculate_price_from_form`.
- **Aufrufkette:** Preis-Neuberechnung aus gespeicherten Optionen.
- **Bewertung:** E — Duplikat der Form-Variante UND **echter Bug**: `key($formulacomponent)` (price.php:398) erhaelt eine `stdClass` aus `json_decode`, nicht ein Array — `key()` erwartet seit PHP 8 ein Array und wirft `TypeError`. Die Form-Variante castet korrekt (`key((array) ...)`), hier fehlt der Cast trotz Kommentar "Use array_key_first for 8.1+". Die Methode ist damit fuer jede nicht-leere Formel auf PHP 8+ latent kaputt. Sofortiger Backlog-Kandidat.

### `apply_time_factor(array $timeobjects, array $dayinfo, float &$price)` — private static
- **Zweck:** Multipliziert Preis mit dem Multiplikator des ersten passenden Zeitfensters.
- **Seiteneffekte:** Mutiert `$price` per Referenz; ruft `is_in_time_scope`.
- **Bewertung:** A — kurz, klar.

### `apply_unit_factor(array $dayinfo, float &$price)` — private static
- **Zweck:** Wendet Dauer/Bildungseinheit-Faktor an (z.B. 90min bei 45min-Einheit = *2).
- **Seiteneffekte:** `get_config('educationalunitinminutes')`; mutiert `$price`; `sscanf`.
- **Bewertung:** B — OK, leicht arithmetiklastig.

### `apply_customfield_factor_from_form(array $customfieldobjects, stdClass $fromform, float &$price)` — private static
- **Zweck:** Sammelt `customfield_*`-Werte aus der Form und multipliziert Preis bei Formel-Match.
- **Seiteneffekte:** mutiert `$price`.
- **Aufrufkette:** aus `calculate_price_from_form`.
- **Bewertung:** C — vierfach verschachtelte Schleifen (O(n*m)), Duplikat zur Settings-Variante (price.php:485-515).

### `apply_entity_factor_from_form(stdClass $fromform, float &$price)` — private static
- **Zweck:** Wendet Entity-Preisfaktor an (falls local_entities installiert + Entity gesetzt).
- **Seiteneffekte:** externer Call `entitiesrelation_handler::get_pricefactor_by_entityid`; mutiert `$price`.
- **Bewertung:** B — sauber, defensiv (`class_exists`).

### `apply_customfield_factor_with_bookingoptionsettings(array $customfieldobjects, booking_option_settings $bookingoptionsettings, float &$price)` — private static
- **Zweck:** Wie `apply_customfield_factor_from_form`, Quelle ist `bookingoptionsettings->customfields`.
- **Seiteneffekte:** mutiert `$price`.
- **Bewertung:** C — Duplikat + verschachtelte Schleifen (price.php:546-578).

### `apply_entity_factor_with_bookingoptionsettings(booking_option_settings $bookingoptionsettings, float &$price)` — private static
- **Zweck:** Entity-Faktor aus Settings (`entity['id']`).
- **Seiteneffekte:** externer Call entitiesrelation_handler; mutiert `$price`.
- **Bewertung:** B — Duplikat zur Form-Variante, aber kurz/defensiv.

### `save_from_form(stdClass $fromform, bool $triggerevent = true): array` — public
- **Zweck:** Persistiert/loescht alle Kategorie-Preise einer Option; berechnet ggf. Formelpreise; baut Change-Liste und triggert ein konsolidiertes Update-Event.
- **Seiteneffekte:** DB-Read/Delete `booking_prices`, DB-Read `booking_subbooking_options`; `get_config`; ruft `add_price`, `calculate_price_from_form`, `get_active_pricecategory_from_cache_or_db`, `singleton_service`; Event `booking_option::trigger_updated_event` (Context system/module); liest `$USER`.
- **Aufrufkette:** Option-/Subbooking-Save-Pipeline.
- **Bewertung:** E — ~110 LOC, mehrere Verantwortlichkeiten (Delete-Pfad + Save-Pfad), Event-/Context-Aufbau-Block doppelt vorhanden (intern price.php:647-662 vs. 705-721, zusaetzlich Duplikat in `add_price`), N-Reads in Schleife, redundanter zweiter `useprice`-Leerpruefung (price.php:688), die im `useprice`-aktiven Pfad nie greifen kann.

### `validation(array $data, array &$errors)` — public
- **Zweck:** Validiert Preise (keine Negativwerte, keine leeren Strings bei aktivem useprice).
- **Seiteneffekte:** DB-Read `booking_pricecategories` (raw SQL); mutiert `$errors`.
- **Aufrufkette:** mform-validation.
- **Bewertung:** B — OK; laedt die Kategorien erneut per DB, obwohl sie bereits im Konstruktor vorliegen (`$this->pricecategories`) — vermeidbare Redundanz.

### `add_price(string $area, int $itemid, string $categoryidentifier, string $price, ?string $currency = null, bool $triggerevent = true): array` — public static
- **Zweck:** Insert/Update/Delete eines einzelnen Preis-Datensatzes; baut Change-Record; triggert optional Update-Event; purged Preis-Cache.
- **Seiteneffekte:** DB-Read/Insert/Update/Delete `booking_prices`, DB-Read `booking_subbooking_options`; Cache-Purge `cache_helper::purge_by_event('setbackprices')` (immer, auch ohne Aenderung, price.php:875); Event `trigger_updated_event`; `$USER`, `get_config`.
- **Aufrufkette:** von `save_from_form`; sonstige Preis-Setter.
- **Bewertung:** D — ~100 LOC, drei DB-Pfade + Event + Cache vermischt; Event-/Context-Aufbau dupliziert zu `save_from_form` (price.php:845-871). Unbedingte Cache-Purge bei jedem Aufruf kann in Schleifen (save_from_form pro Kategorie) zu mehrfacher globaler Invalidierung fuehren.

### `get_price(string $area, int $itemid, ?object $user = null): array` — public static
- **Zweck:** Ermittelt den fuer den User passenden Preis (Kategorie-Matching per substring/`default`-Fallback), wendet Custom-Form-Preisaenderung an, formatiert auf 2 Dezimalstellen.
- **Seiteneffekte:** `$USER`; `singleton_service::get_pricecategory_for_user`; `get_prices_from_cache_or_db`; `get_config('pricecategoryfallback')`; `customformstore::modify_price` (nur area=option); `get_active_pricecategory_from_cache_or_db`.
- **Aufrufkette:** Render-/Buchungs-Pfade (haeufig, in Tabellen).
- **Bewertung:** D — ~97 LOC, fehleranfaellige Matching-Heuristik via `strpos($categoryidentifier, $pricecategoryidentifier)` (price.php:927) — eine Teilstring-Treffer kann eine falsche Kategorie matchen (z.B. "student" in "studentplus"); verschachtelte Schleifen; `switch` mit identischen `default`/`case 2`-Zweigen (price.php:943-954).

### `return_user_to_buy_for(int $userid = 0)` — public static
- **Zweck:** Ermittelt den effektiven Kauf-User (Shopping-Cart-Cashier-Override > bookforuser-Cache > USER).
- **Seiteneffekte:** Capability-Check `local/shopping_cart:cashier`; externer Call `shopping_cart::return_buy_for_userid`; MUC `bookforuser`; `singleton_service::get_instance_of_user`; `$USER`.
- **Bewertung:** C — gegenintuitive Cache-Logik (price.php:1019-1024): bei gueltigem (`$expirationtime > time()`) Cache-Eintrag wird die soeben aus dem Cache gelesene `$userid` mit `$USER->id` ueberschrieben, d.h. der gecachte Kauf-User wird im Normalfall verworfen — Effekt und Absicht klaffen auseinander.

### `set_bookforuser(int $userid)` — public static
- **Zweck:** Setzt Kauf-User in statische Property + MUC-Cache.
- **Seiteneffekte:** statische Property `$bookforuserid`; MUC-Set `bookforuser`.
- **Bewertung:** B — kurz; Docstring sagt "30 seconds", Code setzt `time() + 10` (price.php:1052) — Doku-Drift.

### `get_pricecategory_for_user(stdClass $user)` — public static
- **Zweck:** Liefert Preiskategorie-Identifier des Users aus dessen Profilfeld (mit Fallback-Logik je `pricecategoryfallback`).
- **Seiteneffekte:** `get_config`; lazy `require_once user/profile/lib.php` + `profile_load_custom_fields` (mutiert `$user`).
- **Bewertung:** B — OK; laedt Profilfelder lazy als Seiteneffekt am uebergebenen User-Objekt.

### `get_prices_from_cache_or_db(string $area, int $itemid, int $userid = 0): array` — public static
- **Zweck:** Mehrstufiger Cache-/DB-Resolver fuer alle Preise einer Option inkl. Kampagnen-Override (general + user-spezifisch), mit BEHAT-Bypass.
- **Seiteneffekte:** MUC `cachedprices` (get/set, beide Keys); DB-Read `booking_prices` JOIN `booking_pricecategories` (raw SQL); ruft `apply_campaigns`; `get_config`; `$USER`.
- **Aufrufkette:** von `get_price`.
- **Bewertung:** D — ~78 LOC, tief verschachtelte Cache-State-Maschine (`true` als Sentinel fuer "kein Preis"), mehrfach `defined('BEHAT_SITE_RUNNING')` verstreut (Test-Logik im Produktionscode, price.php:1125/1127/1132/1136), schwer testbar.

### `get_active_pricecategory_from_cache_or_db(string $identifier)` — public static
- **Zweck:** Liefert aktive Preiskategorie via Singleton > MUC > DB; cached Negativtreffer als `true`-Sentinel.
- **Seiteneffekte:** `singleton_service::get_price_category/set_price_category`; MUC `cachedpricecategories`; DB-Read `booking_pricecategories`; json en-/decode.
- **Bewertung:** C — dreistufiger Cache mit Sentinel-Magic + JSON-Roundtrip, gemischte Rueckgabetypen (`null`/`object`).

### `get_possible_currencies(): array` — public static
- **Zweck:** Liefert vom Payment-Subsystem unterstuetzte Waehrungen (Fallback EUR/USD), sortiert.
- **Seiteneffekte:** externer Call `core_payment\helper::get_supported_currencies`; lang_strings.
- **Bewertung:** A — klar, defensiv (`class_exists`).

### `is_in_time_scope(array $dayinfo, object $rangeinfo)` — public static
- **Zweck:** Prueft, ob Tag/Zeitfenster im angegebenen Range liegt (Wochentag-Abkuerzung sprachabhaengig + Zeit-Sekunden-Vergleich).
- **Seiteneffekte:** `get_string`, `current_language`, `sscanf`.
- **Aufrufkette:** von `apply_time_factor`.
- **Bewertung:** C — sprachabhaengige Wochentag-Abkuerzungslaenge (de=2/en=3, Default 3, price.php:1265-1273) ist fuer andere Sprachen fragil und kann fehlmatchen.

### `apply_campaigns(int $itemid, array &$prices, $userid = 0)` — public static
- **Zweck:** Wendet aktive Kampagnen-Preisfaktoren auf die Preisliste an (general vs. user-spezifisch).
- **Seiteneffekte:** mutiert `$prices` per Referenz; `campaigns_info::get_all_campaigns`; `singleton_service::get_instance_of_booking_option_settings`; ruft `booking_campaign`-Methoden.
- **Aufrufkette:** von `get_prices_from_cache_or_db`.
- **Bewertung:** B — OK; korrekte Reference-Unset-Disziplin nach der Schleife (price.php:1337).

### Triviale Akzessoren
Keine separaten Getter/Setter — die Properties `$pricecategories`, `$area`, `$itemid` sind public und werden im Konstruktor gesetzt; `$bookforuserid` ist private static (gesetzt via `set_bookforuser`, geleert via `destroy_singletons`).

## Bewertungs-Resümee
`price` ist eine ueber 1300 LOC grosse God-Klasse mit klar gemischter Verantwortung (mform-UI, Formel-Geschaeftslogik, `booking_prices`-Persistenz, mehrstufiges MUC-Caching, Eventing). Strukturell dominieren zwei Probleme: durchgaengige Duplizierung der form- vs. settings-Varianten (`calculate_price_*`, `apply_customfield_factor_*`, `apply_entity_factor_*`) und mehrfach kopierte Event-/Context-Aufbau-Bloecke (`save_from_form`, `add_price`). Der gravierendste Fund ist ein **echter latenter Bug** in `calculate_price_with_bookingoptionsettings` (price.php:398): `key()` auf einer `stdClass` wirft auf PHP 8+ einen `TypeError`, weil — anders als in der Form-Variante — der `(array)`-Cast fehlt; die settings-basierte Preisberechnung ist damit fuer nicht-leere Formeln gebrochen. Hinzu kommen ein fehleranfaelliges substring-Kategorie-Matching (`get_price`, price.php:927), eine gegenintuitive Kauf-User-Cache-Branch (`return_user_to_buy_for`, price.php:1019-1024), eine N+1-Leseschleife in `set_data`, verstreute `BEHAT_SITE_RUNNING`-Test-Logik im Produktionscode und kleinere Altlasten (tote `$useprice`-Variable, Doku-Drift 30s vs. 10s). Wegen des PHP-8-Bugs in einer Kern-Berechnungsmethode plus dem starken Refactoring-Druck (Duplizierung, Methodenlaenge, vermischte Schichten) lautet das Verdikt **Klassen-Score D / P1**.
