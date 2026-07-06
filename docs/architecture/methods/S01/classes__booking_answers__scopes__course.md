# course — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/course.php` · **LOC:** 178 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Scope-Strategie „course" (aggregierte Sicht je Moodle-Kurs/courseid). Erbt von `scope_base_options`; SQL-Filter auf `c.id = :courseid`. Praktisch wortgleich zu `instance` und `system`, lediglich Filterspalte und Kontextklasse unterscheiden sich.

## Methoden

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Aggregierte SQL gefiltert auf `c.id = :courseid`.
- **Rueckgabe:** `[$fields, $from, $where, $params]`. **Seiteneffekte:** liest `get_config(...presencecountervaluetocount)`; SQL-String-Bau.
- **Aufrufkette:** von `return_users_table`/Download-Report; ruft geerbte `get_wherepart/get_selectpart/get_endpart`.
- **Bewertung:** C — SQL-String-Konkatenation (course.php:64-68), Duplikat zu `instance`/`system` (nur `c.id` statt `cm.id`).

### `return_users_table(...): wunderbyte_table|null` — public
- **Signatur:** wie Geschwister-Scopes. **Zweck:** baut aggregierte `manageusers_table` (Spalten, Fulltext, sortable answerscount/presencecount, Download).
- **Seiteneffekte:** `return_sql_for_booked_users` (DB indirekt); `show_download_button`; MUC-Cache; `get_string`.
- **Bewertung:** C — ~45 LOC, exaktes Duplikat zu `instance::return_users_table` (course.php:93-138); `$customfields` ungenutzt.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam): void` — public
- **Zweck/Seiteneffekte:** Download-Button bei `mod/booking:updatebooking` + statusparam 0; baseurl + Flags.
- **Bewertung:** C — dupliziert über alle Scopes (course.php:151-168).

### Triviale Akzessoren
- `has_capability_in_scope($scopeid, $capability)` — public — `has_capability` gegen `context_course::instance($scopeid)`; sonst dupliziert.
