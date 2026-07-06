# S05 — pricing_shoppingcart

## Zweck & Grenzen

Dieses Subsystem kapselt zwei eng verzahnte Aufgaben:

1. **Preislogik von mod_booking**: Verwaltung von Preiskategorien (`booking_pricecategories`), Speicherung und Auflösung von Preisen pro Buchungsoption/Subbooking (`booking_prices`), Anwendung der JSON-Preisformel (Zeit-, Customfield-, Entity-, Einheiten-Faktor) sowie die Anwendung von Kampagnen-Preisfaktoren (`booking_campaigns`).
2. **Integration mit `local_shopping_cart`**: Implementierung des `service_provider`-Callback-Vertrags, über den der Warenkorb Buchungsoptionen, Subbookings und Slot-Moves reserviert, beschreibt, kauft, storniert und freigibt.

**Grenzen**: Das eigentliche Checkout-/Zahlungs-/Ledger-Handling lebt im Fremdplugin `local_shopping_cart` (außerhalb des Scopes). mod_booking liefert nur die Callbacks (Preis, Cartitem-Beschreibung, Reservierung/Buchung/Storno). Die Availability-Prüfung (`bo_info`/`bo_availability`), die Buchungsantworten (`booking_answers`, `booking_bookit`) und Slotbooking (`slot_*`) sind eigene Subsysteme und werden hier nur konsumiert.

## Position im Gesamtsystem

- `price` ist die zentrale Preis-API und wird breit konsumiert (Tabellen-Rendering, Optionsformulare, `booking_option_settings::return_booking_option_information`, Webservices, Slotbooking).
- `service_provider` ist der einzige Brückenkopf von `local_shopping_cart` nach mod_booking. Der Cart ruft hier statische Callbacks auf; mod_booking reicht intern an `booking_bookit`, `subbookings_info`, `slot_mover` etc. durch.
- `pricecategories_handler` bedient das Admin-Formular `pricecategories_form` und einen programmatischen Upsert-Pfad (z. B. Agent-Skill).

## Schlüsselkonzepte

- **Preiskategorie** (`booking_pricecategories`): identifier + name + defaultvalue + sortorder + disabled. Der `identifier` wird gegen ein User-Profilfeld (`pricecategoryfield`) gematcht, um die für einen User gültige Kategorie zu bestimmen.
- **Preis-Record** (`booking_prices`): area + itemid + pricecategoryidentifier + price + currency. `area` ist `option`, `subbooking` o. ä.; `itemid` = optionid bzw. subbookingid.
- **Preisformel** (`defaultpriceformula`, JSON in Config): Komponenten `timeslot`, `customfield`, `entity` als Multiplikatoren auf den `defaultvalue` der Kategorie, plus optionaler Einheiten-Faktor (`educationalunitinminutes`) und manueller `priceformulamultiply`/`priceformulaadd`.
- **Kampagnen**: `campaigns_info::get_all_campaigns()` liefert aktive Kampagnen; `apply_campaigns()` modifiziert die Preise (allgemein und userspezifisch getrennt gecacht).
- **Fallback-Matching** (`pricecategoryfallback`): Steuert, ob bei fehlendem Kategorie-Match die `default`-Kategorie oder gar nichts greift.
- **Cart-Callback-Vertrag**: `load_cartitem` / `unload_cartitem` / `successful_checkout` / `cancel_purchase` / `quota_consumed` / `allowed_to_cancel` / `allow_add_item_to_cart` / `adjust_number_of_items` — implementiert das Interface `\local_shopping_cart\local\callback\service_provider`.
- **Areas im Cart**: `option`, `subbooking-<id>`, `moveslot` (Slot-Upgrade mit Preisdifferenz).

## Datenfluss

**Preis ermitteln (Lesepfad)**: `price::get_price(area, itemid, user)` → `get_pricecategory_for_user()` (Profilfeld) + `get_prices_from_cache_or_db()` (Cache `cachedprices`, sonst DB-Join + `apply_campaigns()`) → Match Kategorie → optional `customformstore::modify_price()` → `number_format`.

**Preis speichern (Schreibpfad)**: Optionsform → `price::save_from_form()` → pro Kategorie ggf. `calculate_price_from_form()` (Formel) → `add_price()` (insert/update/delete in `booking_prices`) → `cache_helper::purge_by_event('setbackprices')` → konsolidiertes `booking_option::trigger_updated_event()`.

**Kauf (Cart-Pfad)**: `local_shopping_cart` → `service_provider::allow_add_item_to_cart()` (Availability via `bo_info`) → `load_cartitem()` (reserviert via `booking_bookit::answer_booking_option(...RESERVED)`, baut `cartitem` inkl. Beschreibung/Serviceperiode/Costcenter) → Zahlung im Cart → `successful_checkout()` (`user_confirm_response`/`user_submit_response`) → bei Storno `cancel_purchase()` bzw. `quota_consumed()` für Teilrückerstattung.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| classes/price.php | `mod_booking\price` | Service / Domänen-API (Preise + Formel + Kampagnen) | 1341 | 21 | D | P1 |
| classes/local/pricecategories_handler.php | `mod_booking\local\pricecategories_handler` | Service (CRUD Preiskategorien) | 226 | 7 | B | P3 |
| classes/shopping_cart/service_provider.php | `mod_booking\shopping_cart\service_provider` | Integration / Callback-Adapter zu local_shopping_cart | 1022 | 19 | C | P2 |

### `mod_booking\price` (classes/price.php)

**Verantwortung**: Zentrale, überwiegend statische Preis-API. Mischt Form-Handling (mform-Aufbau, set_data, validation), Persistenz (`booking_prices`), Preisformel-Berechnung, User-/Kategorie-Auflösung, Caching und Kampagnen-Anwendung in einer Klasse — klassische God-Class.

**Kollaborateure**: `singleton_service` (Option-Settings, User, Pricecategory-Cache, `get_pricecategory_for_user`), `booking_option::trigger_updated_event`, `campaigns_info`/`booking_campaign`, `dates_handler`, `entitiesrelation_handler` (optional), `customformstore`, `local_shopping_cart\shopping_cart` (return_buy_for_userid), `core_payment\helper`.

**Persistenz**: Tabellen `booking_pricecategories`, `booking_prices`, `booking_subbooking_options` (für event-optionid). Caches `cachedprices` (general + userspezifisch), `cachedpricecategories`, `bookforuser`. Cache-Invalidierung via `setbackprices`.

**Methoden-Inventar**:
- `static destroy_singletons(): void` — Singleton-State (`$bookforuserid`) zurücksetzen.
- `__construct(string $area, int $itemid = 0)` — lädt aktive Preiskategorien sortiert (ASC/DESC je `pricecategorychoosehighest`).
- `add_price_to_mform(MoodleQuickForm &$mform, bool $noformula, bool $canbeblockedbyconfigsetting)` — baut Preisfelder pro Kategorie + Formel-Felder in die Form (public).
- `set_data(stdClass &$data)` — befüllt Formdata mit bestehenden Preisen/Defaults + Formel-Settings (public).
- `static calculate_price_from_form(stdClass $fromform, string $priceformula, string $pricecategoryidentifier)` — Formel-Preis aus Formdaten (timeslot/customfield/entity/unit) (public).
- `static calculate_price_with_bookingoptionsettings($bookingoptionsettings, string, string)` — gleiche Formel, aber aus Settings-Objekt (Duplikat zu calculate_price_from_form) (public).
- `static apply_time_factor(array, array, float &$price)` — Zeitfenster-Multiplikator (private).
- `static apply_unit_factor(array $dayinfo, float &$price)` — Faktor aus Dauer/Einheitslänge (private).
- `static apply_customfield_factor_from_form(array, stdClass, float &)` / `..._with_bookingoptionsettings(array, booking_option_settings, float &)` — Customfield-Multiplikator (zwei nahezu identische private Varianten).
- `static apply_entity_factor_from_form(stdClass, float &)` / `..._with_bookingoptionsettings(booking_option_settings, float &)` — Entity-Preisfaktor (zwei private Varianten).
- `save_from_form(stdClass $fromform, bool $triggerevent = true): array` — speichert/löscht alle Kategoriepreise, sammelt Changes, triggert ein konsolidiertes Update-Event (public).
- `validation(array $data, array &$errors)` — verbietet negative/leere Preise bei aktiviertem useprice (public).
- `static add_price(string $area, int, string, string $price, ?string $currency, bool $triggerevent): array` — Upsert/Delete eines einzelnen Preis-Records + Event + Cache-Purge (public).
- `static get_price(string $area, int $itemid, ?object $user): array` — gültigen Preis für User auflösen (Kategorie-Match, Fallback, customformstore) (public).
- `static return_user_to_buy_for(int $userid = 0): stdClass` — Zieluser bestimmen (Cashier/Cart-Override, bookforuser-Cache) (public).
- `static set_bookforuser(int $userid)` — bookforuser-Cache 10s setzen (public).
- `static get_pricecategory_for_user(stdClass $user): string` — Kategorie-Identifier aus Profilfeld (perf-optimiert) (public).
- `static get_prices_from_cache_or_db(string $area, int $itemid, int $userid = 0): array` — Preise inkl. Kampagnenanwendung, doppelschichtiges Caching (public).
- `static get_active_pricecategory_from_cache_or_db(string $identifier)` — aktive Kategorie aus Singleton/Cache/DB (public).
- `static get_possible_currencies(): array` — vom Payment-Subsystem unterstützte Währungen (public).
- `static is_in_time_scope(array $dayinfo, object $rangeinfo): bool` — Wochentag/Zeitfenster-Prüfung für timeslot-Faktor (public).
- `static apply_campaigns(int $itemid, array &$prices, $userid = 0)` — aktive Kampagnen-Preisfaktoren auf Preise anwenden (public).

**Schuld-Stichworte**: God-Class 1341 LOC mit gemischten Verantwortlichkeiten (Form + Persistenz + Formel + Cache + Kampagnen). Massive Duplikation der Formel-Pfade (`calculate_price_from_form` price.php:275 vs `calculate_price_with_bookingoptionsettings` price.php:355; `apply_customfield_factor_from_form` price.php:485 vs `..._with_bookingoptionsettings` price.php:546; analog entity price.php:524/587). Komplexer dreischichtiger Cache-Branch mit `true`-Sentinel und BEHAT-Sonderfällen in `get_prices_from_cache_or_db` price.php:1107-1185 (schwer testbar). `save_from_form` price.php:614 lang und mit dupliziertem Event-/Context-Block. Statische `global $DB/$USER`-Zugriffe durchgängig → schlechte Testbarkeit. `pricecategoryfallback`-switch mit redundanten Zweigen price.php:943.

### `mod_booking\local\pricecategories_handler` (classes/local/pricecategories_handler.php)

**Verantwortung**: CRUD für Preiskategorien — verarbeitet das `pricecategories_form` (Updates/Inserts), liefert indexierte Listen und bietet einen idempotenten programmatischen `upsert_pricecategory`.

**Kollaborateure**: `pricecategories_form`, `pricecategory_changed`-Event, `cache_helper` (`setbackpricecategories`).

**Persistenz**: Tabelle `booking_pricecategories`. Cache-Invalidierung via `setbackpricecategories`.

**Methoden-Inventar**:
- `process_pricecategories_form($data)` — diff-basiert update/insert aus Formdaten, Event bei identifier-Änderung, Cache-Purge (public).
- `trigger_pricecategory_changed_event($oldidentifier, $newidentifier, $id)` — feuert `pricecategory_changed` nur bei tatsächlicher Identifier-Änderung (private).
- `get_pricecategory_changes($oldpricecategories, $data): array` — ermittelt updates/inserts aus Formdaten (private).
- `get_pricecategories(): array` — alle Kategorien (public).
- `get_pricecategories_indexed_by_identifier(): array` — Kategorien nach lowercase-Identifier indexiert (public).
- `upsert_pricecategory(string $identifier, string $name, float $defaultvalue, ?int $pricecatsortorder): array` — anlegen oder reaktivieren (disabled→0), sonst Fehlerstatus (public).

**Schuld-Stichworte**: Kleine, fokussierte Klasse. Leichte Duplikation des Record-Mappings zwischen update/insert-Zweig (pricecategories_handler.php:96-124). Sonst sauber.

### `mod_booking\shopping_cart\service_provider` (classes/shopping_cart/service_provider.php)

**Verantwortung**: Implementiert den `local_shopping_cart`-Callback-Vertrag. Reserviert/lädt Cartitems (option/subbooking/moveslot), baut die Cartitem-Beschreibung inkl. Slotbooking-Platzhalter, bucht bei erfolgreichem Checkout, storniert und gibt frei, prüft Hinzufügbarkeit und Stückzahl.

**Kollaborateure**: `cartitem` (Cart-DTO), `bo_info`/`bookitbutton` (Availability), `booking_bookit` (reserve/book/cancel-Antworten), `subbookings_info`, `booking_option`, `booking_answers`, `enrollink` (Lizenzen/Stückzahl), `semester`, `dates_handler`, `singleton_service`, Slotbooking (`slot_answer`, `slot_move_store`, `slot_mover`, `slot_price`), `booking_failed`-Event.

**Persistenz**: Keine eigene; delegiert Schreibvorgänge an `booking_bookit`/`subbookings_info`/`slot_*`. Liest `booking_subbooking_options`, Config (`canceldependenton`, `sccartdescription`). Cache-Purge via `booking_option::purge_cache_for_answers`.

**Methoden-Inventar**:
- `static load_cartitem(string $area, int $itemid, int $userid = 0): array` — Availability prüfen, reservieren, `cartitem` mit Preis/Serviceperiode/Costcenter/Beschreibung bauen (option/subbooking/moveslot) (public, Callback).
- `static build_moveslot_description(\stdClass $move): string` — "alt → neu"-Beschreibung für Slot-Move (nur geänderte Slots) (private).
- `static apply_reserved_slotbooking_price(object, array $item, $answer): array` — Cartitem-Preis aus reservierten Slot-Daten überschreiben (private).
- `static build_cartitem_description(object, array, $answer, array, int): string` — Beschreibung mit aufgelösten Platzhaltern + Slot-Kontext (private).
- `static flatten_booking_information(array): array` — verschachtelte Booking-Info (iambooked/...) flachklopfen (private).
- `static build_slotbooking_placeholder_values(array, array, array, int, bool): array` — Platzhalter-Map für Slot-/Buchungswerte (private).
- `static remove_price_information_from_description(string): string` — Preis-HTML aus Beschreibung entfernen (private).
- `static append_slotbooking_context_to_description(object, string, array, array, int, int): string` — Slot-Detailzeilen + JSON-Kontext-Node anhängen (private).
- `static unload_cartitem(string $area, int $itemid, int $userid = 0): array` — Reservierung freigeben (option/subbooking/moveslot), abhängige Subbookings melden (public, Callback).
- `static successful_checkout(string $area, int $itemid, int $paymentid, int $userid): bool` — nach Zahlung tatsächlich buchen/committen, sonst `booking_failed` (public, Callback).
- `static cancel_purchase(string $area, int $itemid, int $userid = 0): bool` — gebuchtes Item stornieren (DELETED) (public, Callback).
- `static quota_consumed(string $area, int $itemid, int $userid = 0): float` — Verbrauchsanteil für Teilrückerstattung (public, Callback).
- `static allowed_to_cancel(string $area, int $itemid): bool` — Storno erlaubt? (disablecancel option/instance) (public, Callback).
- `static unload_subbooking(string, int, int): array` — Subbooking-Reservierung lösen (private).
- `static get_cart_book_intent_ignored_condition_ids(object $settings, int $userid): array` — bei multiplebookings/iambooked zu ignorierende Conditions (private).
- `static allow_add_item_to_cart(string $area, int $itemid, int $userid = 0): array` — Hinzufügbarkeit via `bo_info`, Preis-Guard, Cartitem-Vorschau (public, Callback).
- `static adjust_number_of_items(string $area, int $itemid, int $nritems, int $userid = 0): bool` — Stückzahl (Lizenzen) gegen Kapazität anpassen (public, Callback).

**Schuld-Stichworte**: Große Adapter-Klasse 1022 LOC, 19 Methoden. `load_cartitem` service_provider.php:63-283 ist sehr lang mit drei stark verzweigten Area-Branches und dupliziertem Costcenter-/Serviceperioden-Boilerplate (option vs subbooking vs moveslot). Gemischte Sprache in UI-Strings (hartkodiertes deutsches "Anzahl der Slots:" service_provider.php:564). `build_cartitem_description`-Cluster (service_provider.php:361-592) bündelt viel Slotbooking-spezifische Render-Logik im Cart-Adapter — Kopplung an Slotbooking-Internas (`slot_answer`, `slot_price`). Auskommentierter Code-Block in `allowed_to_cancel` service_provider.php:822-830. `successful_checkout` enthält doppeltes `singleton_service::get_instance_of_user` service_provider.php:679.

## Persistenz

| Tabelle | Genutzt von | Zweck |
|---|---|---|
| `booking_pricecategories` | price, pricecategories_handler | Definition der Preiskategorien (identifier/name/defaultvalue/sortorder/disabled) |
| `booking_prices` | price (add_price, get_prices_from_cache_or_db, save_from_form) | Konkrete Preise pro area+itemid+pricecategoryidentifier |
| `booking_subbooking_options` | price (Event-optionid), service_provider | optionid-Auflösung für Subbookings |

**Caches**: `cachedprices` (general `area+itemid` und userspezifisch `area+itemid_userid`, `true`-Sentinel = kein Preis), `cachedpricecategories`, `bookforuser`. Invalidierungs-Events: `setbackprices`, `setbackpricecategories`.

## Extension-Points

- **Interface-Implementierung**: `service_provider implements \local_shopping_cart\local\callback\service_provider` — der Hauptintegrationspunkt zum Fremdplugin (service_provider.php:53).
- **Preisformel** (`defaultpriceformula`, JSON-Config): erweiterbares Faktor-Schema (timeslot/customfield/entity).
- **Kampagnen-Hook**: `campaigns_info::get_all_campaigns()` + `booking_campaign`-Interface (`user_specific_price`, `campaign_is_active`, `get_campaign_price`) als Erweiterungspunkt der Preisbeeinflussung (price.php:1316).
- **Entity-Faktor**: optionale Kopplung an `local_entities\entitiesrelation_handler::get_pricefactor_by_entityid` via `class_exists`-Guard (price.php:525).
- **Cart-Beschreibung**: konfigurierbares Template `sccartdescription` mit `{platzhalter}` (service_provider.php:372).
- **customformstore::modify_price**: Hook zur preislichen Anpassung über benutzerdefinierte Formularfelder (price.php:978).

## Bekannte Schulden (→ Blueprint)

1. **price als God-Class (P1)**: 1341 LOC, mischt Form/Persistenz/Formel/Cache/Kampagnen/User-Resolution. Aufspaltung in `price_repository` (DB+Cache), `price_formula` (Formel), `price_form_helper` (mform) und `price_resolver` (Match/Fallback) empfehlenswert.
2. **Doppelte Formel-Pfade**: `calculate_price_from_form`/`calculate_price_with_bookingoptionsettings` und je zwei `apply_customfield_factor_*`/`apply_entity_factor_*`-Varianten sind nahezu identisch — auf eine einheitliche Eingabe-Abstraktion (Form vs Settings adaptieren) reduzieren.
3. **Cache-Komplexität**: dreischichtiges Caching mit `true`-Sentinel und mehreren `defined('BEHAT_SITE_RUNNING')`-Sonderfällen (price.php:1125ff) ist schwer nachvollziehbar/testbar.
4. **service_provider::load_cartitem zu lang (P2)**: drei verzweigte Area-Branches mit dupliziertem Boilerplate; Extraktion pro Area (`build_option_cartitem`, `build_subbooking_cartitem`, `build_moveslot_cartitem`) sinnvoll.
5. **Slotbooking-Kopplung im Cart-Adapter**: Beschreibungs-/Preislogik für Slotbooking (`apply_reserved_slotbooking_price`, `append_slotbooking_context_to_description`) sollte ins Slotbooking-Subsystem wandern; Adapter sollte dünn bleiben.
6. **Hartkodierte Sprache/Toter Code**: deutsches String-Literal service_provider.php:564, auskommentierter canceluntil-Block service_provider.php:822-830.
7. **Fehlende Testbarkeit**: durchgängige statische `global $DB/$USER`-Zugriffe in price erschweren Unit-Tests.
</content>
</invoke>
