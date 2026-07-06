# instance — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/instance.php` · **LOC:** 178 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Scope-Strategie „instance" (aggregierte Sicht je Buchungsinstanz/cmid). Erbt von `scope_base_options` und liefert SQL-Filter auf `cm.id`, baut die aggregierte `manageusers_table` und entscheidet ueber den Download-Button. Kollaborateure: `manageusers_table`, `moodle_url`, geerbte `get_selectpart/get_wherepart/get_endpart`. Stark dupliziert mit den Scopes `course` und `system`.

## Methoden

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Setzt die SQL-Teile (select/where/end) der Basisklasse zur aggregierten Abfrage gebuchter User je Instanz zusammen, gefiltert auf `cm.id = :cmid`.
- **Parameter:** Scope-Name, scopeid(=cmid), Statusfilter. **Rueckgabe:** `[$fields, $from, $where, $params]`.
- **Seiteneffekte:** liest `get_config('booking', 'bookingstrackerpresencecountervaluetocount')`; baut Sub-Select-SQL als String. Keine Writes.
- **Aufrufkette:** von `return_users_table` (gleiche Datei) und vom Download-Report; ruft geerbte `get_wherepart/get_selectpart/get_endpart`.
- **Bewertung:** C — manuelle SQL-String-Konkatenation (instance.php:64-68), nahezu identisch zu `course`/`system`; mischt Param-Aufbau und Query-Komposition.

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = []): wunderbyte_table|null` — public
- **Zweck:** Baut/konfiguriert die aggregierte `manageusers_table` (Spalten, Headers, Fulltext-/Sortable-Spalten, Download-Button).
- **Rueckgabe:** konfigurierte Table. **Seiteneffekte:** ruft `return_sql_for_booked_users` (DB-Read indirekt) und `show_download_button`; setzt MUC-Cache `mod_booking/bookedusertable`; `get_string`.
- **Aufrufkette:** vom `booked_users`-Renderer/Scope-Dispatcher.
- **Bewertung:** C — ~45 LOC, fast wortgleich zu `course::return_users_table` (Duplikat, instance.php:93-138); `$customfields` ungenutzt.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam): void` — public
- **Zweck:** Aktiviert Download-Button nur bei `mod/booking:updatebooking` und nur fuer gebuchte User (statusparam 0).
- **Seiteneffekte:** Capability-Check; setzt `define_baseurl` (download_report2.php) und Download-Flags am Table. **Aufrufkette:** aus `return_users_table`.
- **Bewertung:** C — exakt dupliziert in course/courseanswers/instanceanswers/system (instance.php:151-168); Kandidat fuer Verschiebung in die Basisklasse.

### Triviale Akzessoren
- `has_capability_in_scope($scopeid, $capability)` — public — `has_capability` gegen `context_module::instance($scopeid)`; einzige scope-spezifische Zeile, sonst dupliziert.
