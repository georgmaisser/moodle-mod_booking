# certificate_conditions — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/certificate_conditions.php` · **LOC:** 355 · **Subsystem:** S19 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S19_certificate_conditions.md)

## Klassenueberblick
Statische Helper-/Fassaden-Klasse fuer das Feature "Zertifikats-Bedingungen". Kapselt CRUD auf den Tabellen `booking_cert_cond` (Bedingungsdatensaetze) und `booking_cert_cond_item` (referenzierte Items), das Befuellen/Auslesen von Formulardaten sowie die Laufzeit-Auswertung (Filter → Condition → Action) bei Events. Kollaborateure: `filters_info`, `conditions_info`, `actions_info` (gleicher Namespace, Plugin-Registries fuer die jeweiligen Bausteine), `certificateconditionslist` (Renderable) und der `mod_booking`-Renderer. Haelt zwei statische Caches (`$condition`, `$optiontargets`).

## Methoden

### `get_rendered_list_of_saved_conditions(int $contextid = 1, bool $enableaddbutton = true): string` — public static
- **Zweck:** Liefert gerendertes HTML einer Liste gespeicherter Bedingungen fuer einen Kontext.
- **Parameter:** `$contextid` Kontext-Filter (Default 1 = System), `$enableaddbutton` Add-Button anzeigen.
- **Rueckgabe:** HTML-String.
- **Seiteneffekte:** Nutzt globalen `$PAGE`, holt `mod_booking`-Renderer; indirekt DB-Read ueber `get_list_of_saved_conditions`.
- **Aufrufkette:** Ruft `get_list_of_saved_conditions` + `certificateconditionslist`-Renderable + `renderer::render_certificateconditionslist`. Gerufen aus Settings-/Listen-UI.
- **Bewertung:** B — schlanke Render-Fassade. Magic-Default `$contextid = 1` (System-Kontext hartkodiert) statt `context_system::instance()->id` ist ein leichter Smell (Zeile 45).

### `get_list_of_saved_conditions(int $contextid = 0): array` — public static
- **Zweck:** Alle gespeicherten Bedingungen, optional kontextgefiltert.
- **Parameter:** `$contextid` (0 = alle, ohne Cache).
- **Rueckgabe:** Array von Records.
- **Seiteneffekte:** DB-Read `booking_cert_cond`; befuellt/liest statischen Cache `self::$condition`.
- **Aufrufkette:** Gerufen von `get_rendered_list_of_saved_conditions` und externer UI.
- **Bewertung:** B — kompakt. Cache-Logik nur fuer `$contextid != 0`; `array_filter` mit `==`-Vergleich okay. Cache wird nur ueber komplette Records gefuellt, Filterung pro Kontext im Speicher.

### `delete_conditions_by_context(int $contextid): void` — public static
- **Zweck:** Loescht alle Bedingungen eines Kontexts (Systemkontext ausgenommen).
- **Seiteneffekte:** DB-Read `booking_cert_cond`; delegiert Loeschung an `delete_condition` (DB-Writes + Cache-Reset).
- **Aufrufkette:** Ruft `delete_condition` je Record. Gerufen z. B. bei Kurs-/Instanz-Aufraeumung.
- **Bewertung:** B — Guard gegen System-Kontext sinnvoll; N+1-Deletes (pro Record eigener Cache-Reset) sind hier unkritisch wegen kleiner Mengen.

### `delete_condition(int $id): void` — public static
- **Zweck:** Loescht eine Bedingung samt zugehoerigen Items.
- **Seiteneffekte:** DB-Writes `booking_cert_cond_item` (per conditionid) und `booking_cert_cond` (per id); invalidiert beide statischen Caches.
- **Bewertung:** A — klar, atomar pro Aufruf, Cache korrekt invalidiert.

### `option_is_targeted_by_condition(int $optionid): bool` — public static
- **Zweck:** Prueft, ob eine Buchungsoption von irgendeiner Bedingung referenziert wird.
- **Rueckgabe:** bool.
- **Seiteneffekte:** Lazy-Aufbau von `self::$optiontargets` via `build_option_targets_cache`.
- **Aufrufkette:** Ruft `build_option_targets_cache`. Gerufen von Anzeige-/Logik-Pfaden, die wissen muessen ob eine Option zertifikatsrelevant ist.
- **Bewertung:** A — saubere Lazy-Lookup-Methode.

### `build_option_targets_cache(): void` — private static
- **Zweck:** Baut Cache aller von `mod_booking/bookingoption`-Items referenzierten Option-IDs.
- **Seiteneffekte:** DB-Read `booking_cert_cond_item` (gefiltert auf component/area); fuellt `self::$optiontargets`.
- **Bewertung:** A — fokussiert, hartkodierte component/area-Konstanten als Magic Strings (Zeile 128), aber tolerierbar.

### `set_data_for_form(object &$data): object` — public static
- **Zweck:** Befuellt ein Formdaten-Objekt aus dem DB-Record (Filter/Condition/Action-JSON dekodiert, Bausteine setzen Defaults).
- **Parameter:** `$data` (by-ref) mit erwartetem `id`.
- **Rueckgabe:** Aufbereitetes Objekt (bzw. leeres stdClass falls kein Record).
- **Seiteneffekte:** DB-Read `booking_cert_cond`; ruft `filters_info/conditions_info/actions_info::get_*` und deren `set_defaults`.
- **Aufrufkette:** Gerufen aus dem dynamischen Form-Setup.
- **Bewertung:** C — gemischte Verantwortung (DB-Read + JSON-Decode + 3-fache Registry-Aufloesung + Default-Verteilung), redundante Fallback-Ketten `conditionname ?? logicname` (Zeile 164/168), by-ref UND Rueckgabe gleichzeitig (Zeile 146/179) ist verwirrend. ~35 LOC.

### `save_certificate_condition(stdClass &$data): int` — public static
- **Zweck:** Speichert (insert/update) eine Bedingung aus Formdaten inkl. Filter-/Condition-/Action-JSON.
- **Parameter:** `$data` (by-ref, von Bausteinen mutiert).
- **Rueckgabe:** Record-ID.
- **Seiteneffekte:** Ruft `save_filter/save_condition/save_action` der Bausteine (mutieren `$data`); DB-Write `booking_cert_cond` (insert ODER update); invalidiert beide Caches.
- **Aufrufkette:** Gerufen aus Form-Submit-Handling; ruft die drei Registries.
- **Bewertung:** C — ~44 LOC, drei nahezu identische bedingte JSON-Bloecke (Filter/Condition/Action; Duplikat-Smell Zeile 195-215), by-ref-Mutation der Bausteine als Seiteneffekt-Kanal fuer die JSON-Felder ist intransparent. Inkonsistenz: `filterjson` Default `json_encode(new stdClass())`, aber `logicjson`/`actionjson` koennen `null` werden (Zeile 206/213).

### `save_items_for_condition(int $conditionid, stdClass $data): void` — public static
- **Zweck:** Delegiert Speicherung der Items an die zustaendige Condition.
- **Seiteneffekte:** Ueber `conditions_info::get_condition(...)->save_items()` indirekt DB-Writes.
- **Bewertung:** A — reine Delegation.

### `evaluate_certificate_conditions(object $event, int $userid, int $optionid): void` — public static
- **Zweck:** Void-Fassade fuer die Event-getriebene Auswertung.
- **Seiteneffekte:** Delegiert vollstaendig an `evaluate_certificate_conditions_with_result`.
- **Aufrufkette:** Gerufen aus Event-Observern; ruft `..._with_result`.
- **Bewertung:** B — duenne Kompat-Fassade, Existenz neben der `_with_result`-Variante leichte API-Duplizierung.

### `evaluate_certificate_conditions_with_result(object $event, int $userid, int $optionid): bool` — public static
- **Zweck:** Wertet alle aktiven Bedingungen aus und meldet, ob mindestens eine Aktion ausgefuehrt wurde.
- **Rueckgabe:** bool (mind. eine Bedingung getriggert).
- **Seiteneffekte:** DB-Read `booking_cert_cond` (isactive=1); je Record `evaluate_single_condition` (kann Aktionen mit eigenen Seiteneffekten ausloesen).
- **Aufrufkette:** Ruft `evaluate_single_condition` in Schleife. Eintrittspunkt aus Event-Verarbeitung.
- **Bewertung:** B — klar strukturierte Schleife; baut `$eventcontext` als ad-hoc stdClass (kein DTO).

### `evaluate_single_condition(stdClass $record, stdClass $eventcontext, int $userid, int $optionid): bool` — private static
- **Zweck:** Dekodiert die JSON-Felder eines Records, instanziiert Filter/Condition/Action, wertet sie der Reihe nach aus und fuehrt die Aktion bei Erfolg aus.
- **Rueckgabe:** bool (Bedingung erfuellt und Aktion ausgefuehrt).
- **Seiteneffekte:** 3x `json_decode`; Registry-Lookups; `set_*data_from_json`/`set_logicdata` auf Bausteinen; `action->execute_action(...)` mit potenziell weitreichenden Effekten (Zertifikatsausstellung etc.); baut `$actioncontext`.
- **Aufrufkette:** Gerufen von `evaluate_certificate_conditions_with_result`; ruft die drei Registry-Bausteine.
- **Bewertung:** C — ~44 LOC, lineare Filter→Condition→Action-Pipeline mit mehreren Early-Returns und doppelten `if ($filter)`/`if ($condition)`-Checks (Zeile 321-336). Mischt Deserialisierung, Aufbau und Ausfuehrung; schwer isoliert testbar wegen statischer Registry-Calls.

### `reset_caches(): void` — public static
- **Zweck:** Setzt beide statischen Caches zurueck (Test-Teardown).
- **Bewertung:** A — trivialer Cache-Reset.

## Statische Felder (gebuendelt)
- `public static $condition = []` — Record-Cache. **Smell:** `public` statt `private` erlaubt externe Mutation des Caches (Zeile 33).
- `private static $optiontargets = null` — Option-ID-Lookup-Cache (lazy).
