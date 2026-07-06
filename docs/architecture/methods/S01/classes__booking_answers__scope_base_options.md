# scope_base_options — Methoden-Doku
**Datei:** `classes/booking_answers/scope_base_options.php` · **LOC:** 107 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Zwischenbasis fuer die aggregierte Optionen-Sicht (eine Zeile pro Buchungsoption mit Teilnehmerzaehler). Liefert die SQL-SELECT-/GROUP-BY-Bausteine und die Spaltendefinition fuer diese Aggregat-Tabellen. Erbt von `scope_base`; Kollaborateure: `manageusers_table`, Moodle `get_config`/`get_string`.

## Methoden

### `get_selectpart(string $scope): string` — public
- **Zweck:** Baut den SELECT-/FROM-/JOIN-Block der Aggregat-Query (booking_options + LEFT JOIN booking_answers, course_modules, booking, course, modules; Subquery fuer presencecount aus booking_optiondates_answers).
- **Parameter:** `$scope` (wird als Literal in SELECT interpoliert).
- **Rueckgabe:** `string` (grosses SQL-Fragment).
- **Seiteneffekte:** Keine DB-Ausfuehrung; reiner Query-Bau mit Param `:statustocount`.
- **Aufrufkette:** Von `return_sql_for_booked_users` der konkreten Options-Scopes.
- **Bewertung:** C — grosser handgebauter SQL-String mit mehreren Joins und korrelierter Subquery; `$scope` per String-Konkatenation eingesetzt (statt Param), wenn auch intern kontrolliert. Smell scope_base_options.php:44-73.

### `get_endpart(): string` — public
- **Zweck:** Liefert GROUP BY / ORDER BY / `LIMIT 1000000` fuer die Aggregat-Query.
- **Rueckgabe:** `string`.
- **Seiteneffekte:** Keine.
- **Bewertung:** B — fester `LIMIT 1000000` als MySQL-Workaround ist ein Geruch, aber bewusst kommentiert.

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Spalten der Aggregat-Tabelle (titleprefix, text, answerscount, optional presencecount).
- **Rueckgabe:** `array` Spalten-Map.
- **Seiteneffekte:** `get_config('booking', ...)` Read, `get_string`.
- **Aufrufkette:** Vom Renderer/Tabellen-Bau; ueberschreibt `scope_base::return_cols_for_tables`.
- **Bewertung:** A.

## Notizen
Verantwortung sauber getrennt (Aggregat-SQL vs. Answers-SQL in der Schwesterklasse). Hauptgeruch ist der lange inline SQL-String in `get_selectpart`, der mit `scope_base_answers::get_selectpart` die presencecount-Subquery dupliziert.
