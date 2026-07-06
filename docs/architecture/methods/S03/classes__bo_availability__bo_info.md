# bo_info — Methoden-Doku

**Datei:** `classes/bo_availability/bo_info.php` · **LOC:** 1603 · **Subsystem:** S03 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`bo_info` ist die zentrale Orchestrierungsklasse fuer das gesamte „Conditional Availability"-System einer Buchungsoption. Sie sammelt alle Bedingungs-Klassen (hardcoded + JSON-konfiguriert) aus dem Namespace `bo_availability\conditions`, evaluiert sie (inkl. Override-Operatoren OR/AND), bestimmt die blockierende Bedingung mit hoechster Prioritaet und steuert daraus abgeleitet drei sehr unterschiedliche Domaenen: (1) mform-Integration beim Bearbeiten, (2) SQL-Filter-Generierung fuer Tabellen-Listen, (3) Rendering der Bookit-Buttons und der mehrstufigen Prebooking-Modal-Seiten. Hauptkollaborateure: `singleton_service`, die einzelnen `bo_condition`-Klassen, `booking_bookit`, `price`, `col_price`, `button_notifyme`, `shopping_cart` sowie die Helfer `condition_state_helper`, `condition_visibility_manager`. Die Klasse buendelt zu viele Verantwortlichkeiten (Evaluation + Persistenz + SQL + UI-Rendering) und ist damit eine God-Class.

## Methoden

### `set_enrollink_context(bool $active): void` — public static
- **Zweck:** Setzt statisches Flag `$isenrollinkcontext`, das spaeter Condition-Exclusion beeinflusst.
- **Seiteneffekte:** statischer Zustand `self::$isenrollinkcontext` (Prozess-global). **Bewertung:** B (kleiner statischer Mutable-State, aber bewusst).

### `__construct(booking_option_settings $settings)` — public
- **Zweck:** Initialisiert `optionid` aus Settings und `userid` aus `$USER`. **Seiteneffekte:** liest Global `$USER`. **Bewertung:** B.

### `is_available(?int $optionid, int $userid, bool $hardblock, bool $noblockingpages, array $ignoredconditionids): array` — public
- **Zweck:** Liefert `[id, isavailable, description]` der hoechstpriorisierten blockierenden Bedingung.
- **Parameter/Rueckgabe:** optionid (Fallback auf Property), userid, hardblock, noblockingpages (ueberspringt PRE/POSTBOOK-Seiten), ignoredconditionids → Array `[$id, $isavailable, $description]`.
- **Seiteneffekte:** `has_capability` auf system + module context; `singleton_service`-Lookup in der Schleife (pro Iteration!).
- **Aufrufkette:** ruft `get_condition_results`; gerufen u.a. aus `load_pre_booking_page`, `get_description` sowie extern aus `booking_bookit`/Views.
- **Bewertung:** C — tief geschachtelte Capability-Logik (bo_info.php:159-184), `singleton_service::get_instance_of_booking_option_settings` innerhalb der Result-Schleife redundant pro Treffer geholt.

### `get_condition_results(?int $optionid, int $userid, bool $onlyhardblock, array $ignoredconditionids): array` — public static
- **Zweck:** Kernfunktion: evaluiert ALLE Bedingungen (hardcoded-Instanzen + JSON-stdClass), wendet Override-Operatoren (OR/AND) an, filtert auf nicht-verfuegbare und sortiert nach id.
- **Parameter/Rueckgabe:** liefert assoziatives Array `id => [isavailable, description, classname, button, insertpage, condition, reciprocal]`.
- **Seiteneffekte:** `require_once lib.php`; `json_decode($settings->availability)`; instanziiert dynamisch Condition-Klassen; liest Global `$USER`/`$CFG`; wirft `moodle_exception` bei ungueltigem JSON.
- **Aufrufkette:** Herzstueck — gerufen von `is_available`, `load_pre_booking_page`, `add_continue_button`.
- **Bewertung:** E — ~198 LOC (bo_info.php:199-397), zwei nahezu duplizierte Instanziierungs-/Result-Bloecke (Zweig stdClass vs. Instanz, bo_info.php:260-315), sehr komplexe verschachtelte Override-Logik (OR/AND, bo_info.php:324-386) mit mehrfacher Mutation derselben Arrays, schwer testbar. Klarer Refactor-Kandidat (Strategy/Resolver auslagern).

### `get_full_information(?\course_modinfo $modinfo = null): string` — public
- **Zweck:** Sollte Restriktions-Beschreibung fuer Admins liefern. **Seiteneffekte:** keine. **Bewertung:** D — toter/leerer Code: gibt in beiden Pfaden `''` zurueck (bo_info.php:416-419), `$this->availability` wird nie gesetzt → Methode wirkungslos (Dead Code).

### `get_description(booking_option_settings $settings, $userid = null, $full = false): array` — public
- **Zweck:** Duenne Delegation an `is_available`. **Bewertung:** B (triviale Weiterleitung; `$full`-Param ignoriert — leichter Smell).

### `add_conditions_to_mform(MoodleQuickForm &$mform, int $optionid, ?\moodleform $moodleform): void` — public static
- **Zweck:** Fuegt fuer alle mform-faehigen Bedingungen Formularfelder hinzu, beachtet Skip/Freeze via `condition_visibility_manager`.
- **Seiteneffekte:** mutiert `$mform`. **Aufrufkette:** ruft `get_conditions(MFORM_ONLY)`. **Bewertung:** B.

### `set_defaults(stdClass &$defaultvalues, $jsonobject): void` — public static
- **Zweck:** Setzt Formular-Defaults pro persistierter Bedingung. **Seiteneffekte:** instanziiert Condition-Klassen, mutiert `$defaultvalues`. **Bewertung:** B.

### `save_json_conditions_from_form(stdClass &$fromform): void` — public static
- **Zweck:** Sammelt JSON-faehige Bedingungen aus Formdaten, baut das `availability`-JSON und setzt `sqlfilter`-Flag.
- **Seiteneffekte:** mutiert `$fromform->availability`/`->sqlfilter` (wird spaeter in `booking_options` persistiert); `singleton_service`-Lookup. **Bewertung:** C — gemischte Verantwortung (Form-Parsing + Erhalt bestehender Bedingungen + JSON-Encode), verschachtelte Schleifen (bo_info.php:503-530).

### `return_sql_from_conditions(int $userid): array` — public static
- **Zweck:** Baut WHERE-Fragment fuer Tabellen-Filter aus allen mform-Bedingungen; Bypass fuer bereits gebuchte User und fuer Cashier/updatebooking-Rechte.
- **Seiteneffekte:** `get_config`, `has_capability`, liest `$PAGE->cm`; ruft `$condition->return_sql()`. **Rueckgabe:** `['','','',$params,$where]`.
- **Bewertung:** D — Raw-SQL-String-Konkatenation (bo_info.php:597-612), und **potenzieller Bug:** `$wherearray` wird nur innerhalb `if (!empty($where))` (bo_info.php:585-587) befuellt, aber bei bo_info.php:591 `implode(" AND ", $wherearray)` unbedingt verwendet → bei keiner Where-liefernden Bedingung Undefined-Variable-Warning. Siehe notes.

### `get_conditions(int $condparam = MOD_BOOKING_CONDPARAM_ALL): array` — public static
- **Zweck:** Instanziiert alle Condition-Klassen und filtert nach Parameter (hardcoded/json/mform/overridable/all).
- **Seiteneffekte:** dynamische Instanziierung. **Aufrufkette:** ruft `get_condition_classes`. **Bewertung:** B — sauberer switch, leichte Wiederholung des instance()-Patterns.

### `get_available_conditions(int $condparam): array` — public static
- **Zweck:** `get_conditions` + Exclusion via `exclude_conditions`. **Bewertung:** A.

### `get_condition($conditionname): ?object` — private static
- **Zweck:** Soll Condition-Instanz nach Name liefern. **Bewertung:** D — Bug/Dead-Code: prueft `class_exists()` auf einen String mit `.php`-Suffix (bo_info.php:695-697), der nie eine gueltige Klasse ist → liefert praktisch immer `null`; zudem kein Aufrufer in dieser Datei erkennbar (toter, fehlerhafter Helper).

### `load_pre_booking_page(int $optionid, int $pagenumber, int $userid, string $skipcondition = ''): array` — public static
- **Zweck:** Rendert die Prebooking-Modal-Seite fuer eine bestimmte Bedingung/Seitennummer; loest dabei ggf. die eigentliche Buchung oder Warenkorb-Reservierung aus.
- **Seiteneffekte:** **Schreibvorgaenge ueber Domaenenlogik:** `booking_bookit::bookit()` (Buchung, ggf. doppelt), `shopping_cart::add_item_to_cart()`; rendert Mustache-Templates; wirft `moodle_exception`.
- **Aufrufkette:** ruft `get_condition_results`, `return_sorted_conditions`, `return_class_of_current_page`, `return_data_for_steps`, `add_continue_button`, `add_back_button`, `is_available`.
- **Bewertung:** D — ~100 LOC (bo_info.php:712-812), vermischt Page-Routing, Buchungs-Trigger (Mutation!) und Template-Assemblierung; „book twice"-Workaround (bo_info.php:756-759) ist fragiler Code-Smell.

### `render_conditionmessage(string $description, string $style, int $optionid, bool $showprice, ?stdClass $optionvalues, bool $shownotificationlist, ?stdClass $usertobuyfor, bool $modalfordescription): string` — public static
- **Zweck:** Rendert optional Preis (`col_price`) und Notify-Me-Button zu einer Bedingungsmeldung.
- **Seiteneffekte:** `$PAGE->get_renderer`, `singleton_service`-Lookups. **Bewertung:** C — grosser auskommentierter Code-Block (bo_info.php:851-858), `$description`-Param wird de-facto nicht mehr verwendet (irrefuehrende Signatur), 8 Parameter (lange Param-Liste).

### `render_button(booking_option_settings $settings, int $userid, string $label, ...): array` — public static
- **Zweck:** Standard-Rendering-Builder fuer den Bookit-Button (inkl. Preisanzeige, Notify-Liste, „fuer anderen User"-Label).
- **Seiteneffekte:** `has_capability`, `get_config` (mehrfach), `price::get_price`, `singleton_service`-Lookups. **Rueckgabe:** `['mod_booking/bookit_button', $data]`.
- **Bewertung:** D — ~133 LOC (bo_info.php:902-1035), 13 Parameter, stark verschachtelte Preis-/Login-/Config-Verzweigungen (bo_info.php:961-1001), mehrere Verantwortungen (Label/Preis/Notify/Icon/Link) in einer Methode.

### `return_sorted_priceitems($itemid, $userid = 0): array` — private static
- **Zweck:** Holt Preis-Items, reichert sie mit Preiskategorie an und sortiert nach `pricecatsortorder`.
- **Seiteneffekte:** `price::get_prices_from_cache_or_db`, `price::get_active_pricecategory_from_cache_or_db`. **Bewertung:** B.

### `apply_billboard(bo_condition $condition, booking_option_settings $settings): string` — public static
- **Zweck:** Ueberschreibt Blocking-Warnungen mit konfiguriertem Billboard-Text (falls aktiviert).
- **Seiteneffekte:** `get_config`, `singleton_service`, `booking_context_helper::fix_booking_page_context($PAGE,...)` (mutiert Page-Context!), `format_text`. **Bewertung:** C — Page-Context-Manipulation als Nebeneffekt einer „apply"-Funktion (versteckter globaler Effekt, bo_info.php:1096-1097); `$condition`-Param ungenutzt.

### `return_sorted_conditions(array $results, string $skipcondition = ''): array` — public static
- **Zweck:** Ordnet blockierende Bedingungen in pre/book/post-Seitenstruktur, behandelt Confirmation/Checkout-Logik und unterdrueckt Modal bei <2 Seiten.
- **Seiteneffekte:** `class_exists` (shopping_cart). **Bewertung:** D — ~101 LOC (bo_info.php:1111-1212), dichte Zustandslogik mit vielen Flags (`showbutton`/`showcheckout`/`askforconfirmation`), verschachtelte Sonderfaelle fuer `skipcondition` (bo_info.php:1199-1210), schwer zu folgen.

### `return_data_for_steps(array $conditionsarray, int $pagenumber): array` — private static
- **Zweck:** Baut die Tab-Daten (Namen + aktiv-Flag) fuer den Modal-Header. **Seiteneffekte:** `get_string`. **Bewertung:** B.

### `return_class_of_current_page(array $conditionsarray, int $pagenumber): string` — private static
- **Zweck:** Liefert classname der aktuellen Seite (einfacher Array-Zugriff). **Bewertung:** B (Doc-Kommentar verspricht mehr als der triviale Body liefert).

### `has_price_set(array $results): bool` / `booked_on_waitinglist(array $results): bool` — private static
- **Zweck:** Prueft per String-Vergleich des classname, ob eine `priceisset`- bzw. `askforconfirmation`-Bedingung im Result-Set liegt.
- **Bewertung:** C — Logik haengt an hartkodierten, voll-qualifizierten Klassennamen-Strings (bo_info.php:1266, 1281); bruechig bei Refactor/Rename, Duplikat-Muster.

### `add_continue_button(array &$footerdata, array $conditions, array $results, int $pagenumber, int $totalpages, int $optionid, int $userid): void` — private static
- **Zweck:** Berechnet Label/Action/Link des Continue-Buttons im Modal-Footer abhaengig von Confirmation/Checkout/Slotmove und Modal-Modus.
- **Seiteneffekte:** `singleton_service`, `booking::get_value_of_json_by_key`, `get_config`, re-ruft `get_condition_results`, liest `$USER`. **Bewertung:** D — ~86 LOC (bo_info.php:1300-1386), `$totalpages` ungenutzt, verschachtelte switch/if-Logik, erneuter teurer `get_condition_results`-Aufruf (bo_info.php:1343) als Nebeneffekt der Button-Berechnung.

### `add_back_button(array &$footerdata, array $conditions, array $results, int $pagenumber, int $totalpages): void` — private static
- **Zweck:** Setzt Back-Button-Daten; deaktiviert auf erster Seite/Confirmation. **Bewertung:** B (`$results`/`$totalpages` ungenutzt).

### `check_for_sqljson_key_in_array(string $dbcolumn, string $jsonkey, int $index = 0): string` — public static
- **Zweck:** DB-familien-spezifisches SQL-Snippet zum Auslesen eines JSON-Keys aus einem Array-Element (postgres/mysql).
- **Seiteneffekte:** `$DB->get_dbfamily()`; wirft Exception bei unbekanntem Treiber. **Bewertung:** C — SQL-Fragment-Bau per String-Interpolation mit `addslashes()` (bo_info.php:1440-1443) statt Bind-Params; `$dbcolumn` direkt interpoliert.

### `check_for_sqljson_key_in_object(string $dbcolumn, string $jsonkey, string $type = 'text'): string` — public static
- **Zweck:** Wie oben fuer JSON-Objekt-Key mit Typ-Cast. **Seiteneffekte:** `$DB->get_dbfamily()`. **Bewertung:** C — gleiches SQL-String-Bau-/`addslashes`-Smell (bo_info.php:1468-1476), `$type` wird bei postgres unvalidiert in den Cast interpoliert.

### `validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Delegiert Formvalidierung an alle persistierten Bedingungen mit eigener `validation()`.
- **Seiteneffekte:** `singleton_service`, mutiert `$errors`. **Bewertung:** B.

### `get_for_user_button_string(int|string $userid): string` — public static
- **Zweck:** Baut Label „Fullname (ID:x)" wenn fuer anderen User gebucht wird. **Seiteneffekte:** `$USER`, `singleton_service`, `fullname`. **Bewertung:** B.

### `get_skippable_conditions(): array` — public static
- **Zweck:** Liefert id=>name aller skippbaren Bedingungen fuer die Settings-Seite. **Bewertung:** B.

### `get_condition_classes(): array` — private static
- **Zweck:** Liefert alle Klassen im Namespace `bo_availability\conditions` via `core_component`. **Bewertung:** A.

### `exclude_conditions(array &$conditions): void` — private static
- **Zweck:** Entfernt per `condition_state_helper` als ausgeschlossen markierte Bedingungen (beachtet enrollink-Kontext). **Seiteneffekte:** mutiert Input-Array. **Bewertung:** B.

### `destroy_singletons(): void` — public static
- **Zweck:** Reset des statischen enrollink-Flags + `destroy_instance()` auf allen Condition-Klassen (Test-/Request-Cleanup). **Bewertung:** B.

### Triviale Akzessoren
Properties `$visible`, `$availability`, `$optionid`, `$userid`, `$isenrollinkcontext` werden ueber Konstruktor und `set_enrollink_context` gesetzt; keine klassischen Getter/Setter vorhanden.
