# courseanswers — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/courseanswers.php` · **LOC:** 207 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`courseanswers` ist die nicht-aggregierte Antwortsicht ueber einen Kurs (eine Zeile pro User/Option). Sie erbt von `scope_base_answers` (das `get_selectpart`/`get_endpart` sowie die Spaltendefinition beisteuert) und liefert die kurs-spezifische WHERE-Einschraenkung (`c.id = :courseid`). Aufgebaut wird ein `manageusers_table` mit Buchungsoption-Spalten (titleprefix/text), Praesenz- und Zertifikats-/Loesch-Action-Buttons. Persistenz: keine eigene; liest aus `booking_answers` und assoziierten Tabellen ueber die geerbten SQL-Teile. Kollaborateure: `scope_base_answers`, `manageusers_table`, `booked_users` (Output-Buttons), `context_course`, `moodle_url`.

## Methoden

### `public function return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut die kurs-gefilterte SQL: `$selectpart` und `$endpart` aus der Basisklasse, WHERE `ba.waitinglist=:statusparam AND c.id=:courseid`, eingebettet in die s2/s1-Subquery-Struktur. **Seiteneffekte:** `get_config('booking', ...)` fuer Praesenzzaehler-Param (`statustocount`); keine Schreibzugriffe. **Rueckgabe:** `[fields, from, where, params]`. **Bewertung:** B — sauber an die Basisklasse delegiert; `$fields = 's1.*'` wird redundant zweimal gesetzt (Z.58 und Z.70); `LIMIT 1000000` als mysqlfamily-Workaround.

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Konstruiert die Tabelle: Cache, Spalten/Header, Fulltext-Suche (titleprefix/text/Name/Email), Sortierspalten (statusabhaengig um presencecount/status/notes erweitert), Default-Sortierung `timemodified DESC`. Fuegt bei BOOKED einen Zertifikats-Button und (ausser bei DELETED) Checkboxen plus — falls `mod/booking:deleteresponses` — einen Loesch-Button hinzu. **Seiteneffekte:** `set_sql`, `show_download_button`, `booked_users::create_certificate_button()` / `create_delete_button()`, `has_capability_in_scope`. **Rueckgabe:** `wunderbyte_table`. **Bewertung:** B — klare statusabhaengige Konfiguration; Capability-Gate fuer Loeschung vorhanden.

### `public function show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Aktiviert Download nur bei `mod/booking:updatebooking` und `statusparam == 0`. **Seiteneffekte:** `define_baseurl(download_report2.php)`, setzt Download-Flags. **Rueckgabe:** void. **Bewertung:** B — identisch zu den Schwesterscopes (Duplikation ueber die Scope-Familie).

### `public function has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Prueft Capability im Kurs-Kontext (`$scopeid` = courseid). **Seiteneffekte:** `has_capability($capability, context_course::instance($scopeid))`. **Rueckgabe:** bool. **Bewertung:** A — korrekte Kontext-Aufloesung fuer den Kurs-Scope.

### Triviale Properties
`public $scope = 'courseanswers';` (Z.46).

## Bewertungs-Resümee
Solider, gut strukturierter Kurs-Scope, der den Grossteil der SQL an `scope_base_answers` delegiert und die Tabelle statusabhaengig konfiguriert. Kleinere Schwaechen: doppeltes `$fields`-Setzen, `show_download_button` ist ueber die ganze Scope-Familie kopiert. Keine funktionalen Defekte. Klassen-Score **B / P3**.
