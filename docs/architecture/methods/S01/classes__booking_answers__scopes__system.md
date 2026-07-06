# system — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/system.php` · **LOC:** 176 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`system` ist die systemweite, aggregierte Scope-Sicht der Booking-Answers-Tracker-Hierarchie (Scopes: option | instance | course | system). Sie erbt von `scope_base_options` (das die geteilte aggregierte SELECT-/END-Logik und `get_wherepart` aus `scope_base` liefert) und konkretisiert den Scope `system`: alle Buchungsoptionen site-weit, gruppiert je Option mit Antwort- und Praesenz-Zaehlern. Keine eigene Persistenz; sie baut nur SQL-Fragmente und verdrahtet eine `manageusers_table` (`local_wunderbyte_table`). Kollaborateure: `scope_base_options`/`scope_base` (Vererbung: `get_selectpart`, `get_wherepart`, `get_endpart`, `return_classname`), `manageusers_table`, `context_system`, `moodle_url`, `get_config('booking', ...)`.

## Methoden

### `public function return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut das vierteilige SQL-Tupel `[$fields, $from, $where, $params]` fuer die gebuchten Nutzer/Optionen im System-Scope. Der `$from`-Teil ist ein in eine Subquery `s1` gewickelter `selectpart + wherepart + endpart`; `$where` ist konstant `1 = 1` (die Filterung passiert im `wherepart`). **Seiteneffekte:** `get_config('booking', 'bookingstrackerpresencecountervaluetocount')` fuer den `statustocount`-Parameter. **Rueckgabe:** `[$fields, $from, $where, $params]`. **Bewertung:** C — Bug: `get_selectpart($statusparam)` wird mit dem `$statusparam` statt mit `$scope` aufgerufen (vgl. Signatur `get_selectpart(string $scope)` im Parent). Dadurch enthaelt die SQL-Spalte `'<wert>' AS scope` die numerische Status-ID statt `'system'`. Da der Scope hier systemweit fix ist, ist der praktische Schaden gering (die Spalte fuettert allenfalls die zeilenweise Link-/Scope-Erkennung der Tabelle), aber semantisch falsch — und identisch in `course.php`/`instance.php` (Copy-Paste). Der nichtaggregierte `systemanswers`-Zwilling uebergibt korrekt `$scope`.

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Erzeugt und konfiguriert eine `manageusers_table` fuer den System-Scope: Tabellenname `"{prefix}_{scope}_{scopeid}"`, MUC-Cache `bookedusertable`, Spalten/Headers, optional sortable/paginate, setzt das SQL via `set_sql(...)`, haengt den Download-Button an und definiert Volltext- und Sortier-Spalten. Bei `statusparam == 0` (gebuchte Nutzer) wird zusaetzlich `presencecount` sortierbar. **Seiteneffekte:** instanziiert `manageusers_table`; `define_cache`, `define_columns/headers/baseurl/sortablecolumns/fulltextsearchcolumns`, `set_sql`; ruft `show_download_button`; mehrere `get_string`-Lookups. **Rueckgabe:** `wunderbyte_table` (Doc deklariert `|null`, real wird immer ein Table zurueckgegeben). **Bewertung:** B — geradlinige Tabellen-Verdrahtung; der `$customfields`-Parameter wird entgegengenommen, aber nie verwendet (im Gegensatz zur `join_customfields`-Faehigkeit der Basisklasse) — toter Parameter.

### `public function has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Capability-Pruefung fuer den eingeloggten Nutzer im System-Scope. **Seiteneffekte:** `has_capability($capability, context_system::instance())`. **Rueckgabe:** bool. **Bewertung:** B — bewusste Override: ignoriert `$scopeid` und prueft immer gegen den Systemkontext (korrekt fuer diesen Scope); identisch zur Parent-Implementierung in `scope_base`, daher eigentlich redundant.

### `public function show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Entscheidet scope-spezifisch, ob ein Download-Button gesetzt wird: nur wenn der Nutzer `mod/booking:updatebooking` (im Systemkontext) hat. Setzt dann die Baseurl auf `download_report2.php` (mit `scope = return_classname()` und `statusparam`) und aktiviert Download nur fuer `statusparam == 0` (gebuchte Nutzer). **Seiteneffekte:** `has_capability_in_scope`, mutiert `$table` (`define_baseurl`, `showdownloadbutton`, `showdownloadbuttonatbottom`). **Rueckgabe:** void. **Bewertung:** A — sauber gekapselte Per-Scope-Entscheidung; nutzt korrekt `return_classname()` (statt der fehlerhaften SQL-scope-Spalte) fuer die Download-URL.

### Triviale Properties
`public $scope = 'system';` (Z.47) — Scope-Identifier.

## Bewertungs-Resümee
Schlanke Scope-Konkretisierung, die fast vollstaendig aus der Basisklasse lebt. Funktional korrekt fuer den Hauptzweck (Download-Pfad via `return_classname()`), aber zwei Schoenheitsfehler: der `get_selectpart($statusparam)`-Vertauschungs-Bug (Scope-SQL-Spalte enthaelt die Status-ID), der ungenutzte `$customfields`-Parameter und die redundante `has_capability_in_scope`-Override. Kein Daten- oder Sicherheitsschaden. Klassen-Score **B / P3**.
