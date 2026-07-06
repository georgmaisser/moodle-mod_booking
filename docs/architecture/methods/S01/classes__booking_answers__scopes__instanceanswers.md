# instanceanswers — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/instanceanswers.php` · **LOC:** 206 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Scope-Strategie „instanceanswers" (nicht-aggregierte Einzelantworten je Instanz/cmid). Erbt von `scope_base_answers`, baut eine pro-User-Tabelle mit Checkboxen, Lösch- und Zertifikats-Buttons. Kollaborateure: `manageusers_table`, `booked_users` (Action-Buttons), `moodle_url`. Nahezu identisch zu `courseanswers`.

## Methoden

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut die nicht-aggregierte SQL (Sub-Select s2 mit `LIMIT 1000000` für MySQL-Family) gefiltert auf `ba.waitinglist=:statusparam` und `cm.id=:cmid`.
- **Rueckgabe:** `[$fields, $from, $where, $params]`. **Seiteneffekte:** liest `get_config` (presencecounter-Flags) bedingt; baut SQL-String.
- **Aufrufkette:** von `return_users_table` und Download-Report; ruft geerbte `get_selectpart/get_endpart`.
- **Bewertung:** C — String-SQL mit fest verdrahtetem `LIMIT 1000000` (instanceanswers.php:77), Duplikat zu `courseanswers`; `$fields` doppelt zugewiesen (Z.57 + Z.69, toter Erst-Assignment).

### `return_users_table(...): wunderbyte_table|null` — public
- **Signatur:** wie Geschwister-Scopes (scope, scopeid, statusparam, tablenameprefix, columns, headers=[], sortable=false, paginate=false, customfields=[]).
- **Zweck:** Konfiguriert die Einzelantwort-Tabelle inkl. Sortier-Default `timemodified DESC`, Zertifikats-Button (nur BOOKED) und Lösch-Button (nicht DELETED, capability-gegated).
- **Seiteneffekte:** `return_sql_for_booked_users`; `show_download_button`; `booked_users::create_certificate_button()` und `create_delete_button()` (statische Renderer-Calls); MUC-Cache; `get_string`.
- **Aufrufkette:** vom `booked_users`-Renderer.
- **Bewertung:** D — ~57 LOC (instanceanswers.php:99-166), mehrere geschachtelte Bedingungen, gemischte Verantwortung (Daten + Sortierung + 3 Button-Varianten); fast wortgleiches Duplikat zu `courseanswers::return_users_table`; `$customfields` ungenutzt.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam): void` — public
- **Zweck/Seiteneffekte:** Download-Button bei `mod/booking:updatebooking` und statusparam 0; setzt baseurl (download_report2.php) + Flags.
- **Bewertung:** C — exakt dupliziert über alle Scopes (instanceanswers.php:179-196).

### Triviale Akzessoren
- `has_capability_in_scope($scopeid, $capability)` — public — `has_capability` gegen `context_module::instance($scopeid)`; dupliziert.
